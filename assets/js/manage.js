(function ($, App) {
  'use strict';

  var esc = App.escapeHtml;
  var EMPTY_CELL = '<span class="c-outline-variant">—</span>';

  var entity = null;
  var state = { page: 1, search: '', statusFilter: App.getQueryParam('status_filter') || '' };
  var loadedRows = [];
  var referenceOptionsCache = {};
  var rowBeingEdited = null;
  var rowBeingDeleted = null;
  var editModal = new bootstrap.Modal('#editModal');
  var deleteModal = new bootstrap.Modal('#deleteModal');
  var tableColumns = [];
  var formFields = [];
  var parentFilters = {};

  function findOption(field, value) {
    var found = null;
    $.each(field.options || [], function (_, option) {
      if (option.value === String(value)) found = option;
    });
    return found;
  }

  function renderOptionBadge(field, value) {
    var option = findOption(field, value);
    return option ? App.renderBadge(option.text, option.tone) : EMPTY_CELL;
  }

  function primaryKeyValue(row) {
    return row[entity.pk[0]];
  }

  function renderStatusSelect(field, value) {
    var html = '<select class="form-select inline-select" data-inline-status data-entity="' + esc(entity.key) + '" data-field="' + esc(field.name) + '" aria-label="' + esc(field.label) + '">';
    $.each(field.options, function (_, option) {
      var selected = option.value === String(value) ? ' selected' : '';
      html += '<option value="' + esc(option.value) + '" data-tone="' + esc(option.tone) + '"' + selected + '>' + esc(option.text) + '</option>';
    });
    return html + '</select>';
  }

  function renderCell(field, row) {
    var value = row[field.name];

    switch (field.type) {
      case 'code':
        return '<td class="mono tabular strong text-nowrap" dir="ltr" style="text-align:right">#MNT-' + esc(value) + '</td>';

      case 'fk': {
        var label = row[field.name + '__label'];
        if (label) return '<td>' + esc(label) + '</td>';
        if (field.placeholder) return '<td>' + App.renderBadge(field.placeholder, field.empty_tone) + '</td>';
        return '<td>' + EMPTY_CELL + '</td>';
      }

      case 'enum':
        if (field.inline) return '<td>' + renderStatusSelect(field, value) + '</td>';
        return '<td>' + renderOptionBadge(field, value) + '</td>';
      case 'bool':
        return '<td>' + renderOptionBadge(field, value) + '</td>';

      case 'money':
        if (value === null) return '<td class="tabular strong">' + (field.name === 'base_price' ? 'حسب المعاينة' : EMPTY_CELL) + '</td>';
        return '<td class="tabular strong">' + App.formatPrice(value) + '</td>';

      case 'number': {
        if (value === null || value === '') return '<td>' + EMPTY_CELL + '</td>';
        var formatted = Number(value).toLocaleString('en-US');
        var isLowStock = field.low !== undefined && Number(value) < field.low;
        if (isLowStock) return '<td class="tabular">' + App.renderBadge(formatted + ' — منخفض', 'error') + '</td>';
        return '<td class="tabular">' + formatted + '</td>';
      }

      case 'image': {
        var imageUrl = row[field.name + '__url'];
        if (imageUrl) return '<td><img class="thumb" src="' + esc(imageUrl) + '" alt="" loading="lazy"></td>';
        return '<td><span class="thumb d-inline-flex align-items-center justify-content-center"><span class="material-symbols-outlined c-outline-variant">image</span></span></td>';
      }

      case 'file': {
        if (!value) return '<td>' + EMPTY_CELL + '</td>';
        var fileName = String(value).split('/').pop();
        return '<td><a class="link-bs" href="' + esc(row[field.name + '__url']) + '" target="_blank" rel="noopener">' +
          '<span class="material-symbols-outlined ms-18">download</span><span>' + esc(fileName) + '</span></a></td>';
      }

      case 'icon':
        return '<td><span class="icon-tile icon-tile-sm" style="width:36px;height:36px"><span class="material-symbols-outlined ms-20">' + esc(value) + '</span></span></td>';

      case 'datetime':
        return '<td class="tabular text-nowrap">' + (value ? esc(String(value).slice(0, 16)) : EMPTY_CELL) + '</td>';

      case 'time':
        return '<td class="tabular">' + (value ? esc(String(value).slice(0, 5)) : EMPTY_CELL) + '</td>';

      case 'date':
        return '<td class="tabular text-nowrap">' + (value ? esc(value) : EMPTY_CELL) + '</td>';

      case 'textarea': {
        if (!value) return '<td>' + EMPTY_CELL + '</td>';
        var text = String(value);
        var shortText = text.length > 70 ? text.slice(0, 70) + '…' : text;
        return '<td title="' + esc(text) + '" style="max-width:280px">' + esc(shortText) + '</td>';
      }

      default: {
        if (value === null || value === '') return '<td>' + EMPTY_CELL + '</td>';
        var isFirstColumn = tableColumns[0] && tableColumns[0].name === field.name;
        var classes = (field.mono ? 'mono ' : '') + (isFirstColumn ? 'strong' : '');
        var direction = field.mono ? ' dir="ltr" style="text-align:right"' : '';
        return '<td class="' + classes + '"' + direction + '>' + esc(value) + '</td>';
      }
    }
  }

  function renderTableHeader() {
    var html = '';
    $.each(tableColumns, function (_, column) {
      html += '<th scope="col">' + esc(column.label) + '</th>';
    });
    html += '<th scope="col">إجراءات</th>';
    $('#headRow').html(html);
  }

  function renderRowActions(row) {
    var html = '<div class="row-actions">';
    $.each(entity.children || [], function (_, child) {
      var childUrl = '?e=' + encodeURIComponent(child.entity) + '&f[' + encodeURIComponent(child.col) + ']=' + encodeURIComponent(primaryKeyValue(row));
      html += '<a class="btn-bs btn-ghost-bs btn-xs-bs" href="' + childUrl + '" title="' + esc(child.label) + '">' +
        '<span class="material-symbols-outlined ms-16">' + esc(child.icon) + '</span><span class="d-none d-xxl-inline">' + esc(child.label) + '</span></a>';
    });
    html += '<button type="button" class="btn-bs btn-soft-primary-bs btn-xs-bs" data-edit>تعديل</button>';
    if (entity.can_delete) html += '<button type="button" class="btn-bs btn-danger-soft-bs btn-xs-bs" data-delete>حذف</button>';
    return html + '</div>';
  }

  function renderEmptyTable() {
    var message = '';
    if (state.search) message = '<p>لا نتائج مطابقة لـ "' + esc(state.search) + '"</p>';
    else if (entity.can_add) message = '<p>ابدأ بإضافة ' + esc(entity.singular) + ' جديد.</p>';
    return '<tr><td colspan="' + (tableColumns.length + 1) + '"><div class="empty-state"><span class="material-symbols-outlined">inbox</span>' +
      '<p class="t-title-md c-on-surface">لا توجد سجلات</p>' + message + '</div></td></tr>';
  }

  function renderTableRows(response) {
    loadedRows = response.rows;
    if (!loadedRows.length) {
      $('#rows').html(renderEmptyTable());
    } else {
      var html = '';
      $.each(loadedRows, function (index, row) {
        html += '<tr data-index="' + index + '" data-key="' + esc(JSON.stringify(row._key)) + '">';
        $.each(tableColumns, function (_, column) { html += renderCell(column, row); });
        html += '<td>' + renderRowActions(row) + '</td></tr>';
      });
      $('#rows').html(html);
    }
    $('#totalText').text(response.total + ' سجل');
    $('#pageInfo').text(response.pages > 1 ? 'صفحة ' + response.page + ' من ' + response.pages : '');
    renderPagination(response.page, response.pages);
  }

  function renderPagination(page, totalPages) {
    var $pagination = $('#pager');
    if (totalPages <= 1) return $pagination.empty();
    var html = '<button type="button" data-page="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + ' aria-label="السابق"><span class="material-symbols-outlined ms-18">chevron_right</span></button>';
    for (var pageNumber = Math.max(1, page - 2); pageNumber <= Math.min(totalPages, page + 2); pageNumber++) {
      html += '<button type="button" data-page="' + pageNumber + '"' + (pageNumber === page ? ' class="active"' : '') + '>' + pageNumber + '</button>';
    }
    html += '<button type="button" data-page="' + (page + 1) + '"' + (page >= totalPages ? ' disabled' : '') + ' aria-label="التالي"><span class="material-symbols-outlined ms-18">chevron_left</span></button>';
    $pagination.html(html);
  }

  function loadRows() {
    $('#dataTable').css('opacity', 0.6);
    return App.callAdminApi('list', {
      e: entity.key,
      page: state.page,
      q: state.search,
      status_filter: state.statusFilter,
      f: parentFilters
    }).done(renderTableRows).fail(function (request) {
      $('#rows').html('<tr><td colspan="' + (tableColumns.length + 1) + '" class="text-center py-4 c-error">' + esc(App.getErrorMessage(request)) + '</td></tr>');
    }).always(function () {
      $('#dataTable').css('opacity', 1);
    });
  }

  function loadReferenceOptions(fieldName) {
    if (!referenceOptionsCache[fieldName]) {
      referenceOptionsCache[fieldName] = App.callAdminApi('options', { e: entity.key, field: fieldName })
        .then(function (response) { return response.options; });
    }
    return referenceOptionsCache[fieldName];
  }

  function renderSelectInput(field, inputId, value, required) {
    var html = '<select id="' + inputId + '" name="' + field.name + '" class="form-select"' + required + '>';
    if (field.type === 'enum' && !field.required) html += '<option value="">—</option>';
    $.each(field.options, function (_, option) {
      html += '<option value="' + esc(option.value) + '"' + (option.value === value ? ' selected' : '') + '>' + esc(option.text) + '</option>';
    });
    return html + '</select>';
  }

  function renderFileInput(field, inputId) {
    var currentFileUrl = rowBeingEdited && rowBeingEdited[field.name + '__url'] ? rowBeingEdited[field.name + '__url'] : '';
    var allowedTypes = field.ext ? ' (' + esc(field.ext.join(', ').toUpperCase()) + ')' : '';
    var preview = '';
    if (field.type === 'image') {
      preview = currentFileUrl ? '<img class="preview" src="' + esc(currentFileUrl) + '" alt="">' : '<img class="preview d-none" alt="">';
    }
    var html = '<div class="dropzone">' +
      '<span class="material-symbols-outlined ms-32 c-primary">cloud_upload</span>' +
      '<span class="t-body-md c-variant">اسحب الملف هنا أو انقر للاختيار' + allowedTypes + '</span>' +
      '<span class="t-label-sm c-primary file-name"></span>' + preview +
      '<input type="file" id="' + inputId + '" name="' + field.name + '"' +
        (field.ext ? ' accept=".' + field.ext.join(',.') + '"' : '') +
        (field.required && !currentFileUrl ? ' required' : '') + '>' +
      '</div>';
    if (currentFileUrl) {
      html += '<div class="d-flex justify-content-between align-items-center mt-1 t-label-sm">' +
        '<a href="' + esc(currentFileUrl) + '" target="_blank" rel="noopener" class="c-primary">الملف الحالي</a>' +
        (field.required ? '' : '<label class="check-bs"><input type="checkbox" name="_remove_' + field.name + '" value="1"><span>إزالة الملف الحالي</span></label>') +
        '</div>';
    }
    return html;
  }

  function renderTextInput(field, inputId, value, required, placeholder) {
    var inputType = { email: 'email', tel: 'tel', number: 'number', money: 'number', date: 'date', time: 'time', password: 'password' }[field.type] || 'text';
    if (field.type === 'time') value = value.slice(0, 5);
    var extra = '';
    if (field.min !== undefined) extra += ' min="' + field.min + '"';
    if (field.max !== undefined) extra += ' max="' + field.max + '"';
    if (field.type === 'money') extra += ' step="0.01"';
    else if (field.step) extra += ' step="' + field.step + '"';
    if (field.maxlength) extra += ' maxlength="' + field.maxlength + '"';
    if (field.type === 'password') {
      extra += ' autocomplete="new-password"';
      value = '';
    }
    var leftToRight = (field.type === 'tel' || field.type === 'email' || field.mono) ? ' dir="ltr" style="text-align:right"' : '';
    var numberClass = inputType === 'number' ? ' tabular' : '';
    return '<input type="' + inputType + '" id="' + inputId + '" name="' + field.name + '" value="' + esc(value) + '" class="form-control' + numberClass + '"' +
      required + placeholder + extra + leftToRight + '>';
  }

  function renderFormField(field, initialValue) {
    var inputId = 'fld_' + field.name;
    var required = field.required ? ' required' : '';
    var value = initialValue === null || initialValue === undefined ? '' : String(initialValue);
    var placeholder = field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '';
    var input;

    switch (field.type) {
      case 'textarea': {
        var rows = field.name === 'specs' ? 6 : 4;
        var maxLength = field.maxlength ? ' maxlength="' + field.maxlength + '"' : '';
        input = '<textarea id="' + inputId + '" name="' + field.name + '" rows="' + rows + '" class="form-control"' + required + placeholder + maxLength + '>' + esc(value) + '</textarea>';
        break;
      }
      case 'enum':
      case 'bool':
        input = renderSelectInput(field, inputId, value, required);
        break;
      case 'fk':
        input = '<select id="' + inputId + '" name="' + field.name + '" class="form-select" data-reference-field="' + field.name + '" data-selected-value="' + esc(value) + '"' + required + '>' +
          '<option value="">' + esc(field.placeholder || 'اختر…') + '</option></select>';
        break;
      case 'image':
      case 'file':
        input = renderFileInput(field, inputId);
        break;
      case 'icon':
        input = '<div class="d-flex gap-sm align-items-center"><span class="icon-tile icon-tile-sm"><span class="material-symbols-outlined icon-preview">' + esc(value || 'help') + '</span></span>' +
          '<input type="text" id="' + inputId + '" name="' + field.name + '" value="' + esc(value) + '" class="form-control" dir="ltr" pattern="[a-z0-9_]+"' + required + placeholder + '></div>';
        break;
      default:
        input = renderTextInput(field, inputId, value, required, placeholder);
    }

    var requiredStar = field.required ? ' <span class="req">*</span>' : '';
    var helpText = field.help ? '<span class="t-label-sm c-variant fw-normal">' + esc(field.help) + '</span>' : '';
    return '<div class="col-md-' + (field.col || 6) + '"><div class="field"><label class="lbl" for="' + inputId + '">' + esc(field.label) + requiredStar + '</label>' + input + helpText + '</div></div>';
  }

  function fillReferenceSelects() {
    $('#formFields select[data-reference-field]').each(function () {
      var $select = $(this);
      var fieldName = $select.data('reference-field');
      var selectedValue = String($select.data('selected-value') || '');
      $select.prop('disabled', true);
      loadReferenceOptions(fieldName).done(function (options) {
        $.each(options, function (_, option) {
          $('<option>').val(option.value).text(option.label).prop('selected', String(option.value) === selectedValue).appendTo($select);
        });
      }).always(function () {
        $select.prop('disabled', false);
      });
    });
  }

  function openEditForm(row) {
    rowBeingEdited = row;
    $('#editTitle').text(row ? 'تعديل ' + entity.singular : 'إضافة ' + entity.singular + ' جديد');
    $('#formAlert').addClass('d-none');

    var html = '';
    $.each(formFields, function (_, field) {
      var value;
      if (row) value = row[field.name];
      else if (parentFilters[field.name] !== undefined) value = parentFilters[field.name];
      else value = field.default !== undefined ? field.default : '';
      if (field.type === 'bool' && value !== '' && value !== null) value = String(Number(value));
      html += renderFormField(field, value);
    });
    $('#formFields').html(html);
    fillReferenceSelects();
    editModal.show();
  }

  $(document).on('click', '[data-add]', function () {
    openEditForm(null);
  });
  $(document).on('click', '[data-edit]', function () {
    openEditForm(loadedRows[$(this).closest('tr').data('index')]);
  });

  $('#editForm').on('submit', function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submitButton = $form.find('[type=submit]');
    var formData = new FormData(this);
    if (rowBeingEdited) formData.append('_key', JSON.stringify(rowBeingEdited._key));

    App.setButtonLoading($submitButton, true);
    App.callAdminApi('save', { e: entity.key }, formData).done(function (response) {
      editModal.hide();
      App.showToast(response.message);
      referenceOptionsCache = {};
      loadRows();
    }).fail(function (request) {
      var response = request.responseJSON || {};
      App.showFieldErrors($form, response.errors);
      $('#formAlert').removeClass('d-none').text(App.getErrorMessage(request));
    }).always(function () {
      App.setButtonLoading($submitButton, false);
    });
  });

  $(document).on('click', '[data-delete]', function () {
    rowBeingDeleted = loadedRows[$(this).closest('tr').data('index')];
    deleteModal.show();
  });
  $('#confirmDelete').on('click', function () {
    var $button = $(this).prop('disabled', true);
    App.callAdminApi('delete', { e: entity.key }, { _key: JSON.stringify(rowBeingDeleted._key) }).done(function (response) {
      deleteModal.hide();
      App.showToast(response.message);
      App.updateCounters(response.counters);
      loadRows();
    }).fail(function (request) {
      deleteModal.hide();
      App.showToast(App.getErrorMessage(request), 'error');
    }).always(function () {
      $button.prop('disabled', false);
    });
  });

  $(document).on('change', '.dropzone input[type=file]', function () {
    var file = this.files && this.files[0];
    var $dropzone = $(this).closest('.dropzone');
    $dropzone.find('.file-name').text(file ? file.name : '');
    if (file && file.type.indexOf('image/') === 0) {
      var reader = new FileReader();
      reader.onload = function (loadEvent) {
        $dropzone.find('.preview').attr('src', loadEvent.target.result).removeClass('d-none');
      };
      reader.readAsDataURL(file);
    }
  });
  $(document).on('dragover dragenter', '.dropzone', function () { $(this).addClass('drag'); })
    .on('dragleave drop', '.dropzone', function () { $(this).removeClass('drag'); });
  $(document).on('input', 'input[name][pattern="[a-z0-9_]+"]', function () {
    $(this).closest('.field').find('.icon-preview').text(this.value || 'help');
  });

  var searchTimer = null;
  $('#search').on('input', function () {
    var searchText = this.value;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      state.search = searchText;
      state.page = 1;
      loadRows();
    }, 300);
  });
  $('#statusFilter').on('change', function () {
    state.statusFilter = this.value;
    state.page = 1;
    loadRows();
  });
  $('#pager').on('click', 'button[data-page]:not(:disabled)', function () {
    state.page = $(this).data('page');
    loadRows();
  });

  $(document).on('change', '#rows [data-inline-status]', function () {
    if (state.statusFilter) setTimeout(loadRows, 600);
  });

  function showFilterChips() {
    var chips = $.map(entity.filters || {}, function (filter) {
      return '<span class="filter-chip">' + esc(filter.label) + ': ' + esc(filter.text) +
        ' <a href="?e=' + encodeURIComponent(entity.key) + '" aria-label="إزالة الفلتر"><span class="material-symbols-outlined ms-16">close</span></a></span>';
    });
    if (chips.length) {
      $('#filterChips').removeClass('d-none').html('<span class="t-label-sm c-variant">مُصفّى حسب:</span>' + chips.join(''));
    }
  }

  function showStatusFilter() {
    var statusField = null;
    $.each(entity.fields, function (_, field) {
      if (field.inline && !statusField) statusField = field;
    });
    if (!statusField) return;
    var $statusFilter = $('#statusFilter').removeClass('d-none').attr('aria-label', 'تصفية حسب ' + statusField.label);
    $.each(statusField.options, function (_, option) {
      $('<option>').val(option.value).text(option.text).appendTo($statusFilter);
    });
    $statusFilter.val(state.statusFilter);
  }

  function setupPage(entityInfo) {
    entity = entityInfo;
    tableColumns = $.map(entity.fields, function (field, name) { return field.list ? $.extend({ name: name }, field) : null; });
    formFields = $.map(entity.fields, function (field, name) { return field.form === false ? null : $.extend({ name: name }, field); });
    $.each(entity.filters || {}, function (column, filter) { parentFilters[column] = filter.value; });

    document.title = entity.title + ' — ' + document.title.split(' — ').pop();
    $('#pageTitle').text(entity.title);
    $('#entityIcon').text(entity.icon);
    $('#entityTitle').text('إدارة ' + entity.title);
    $('#search').attr('placeholder', 'بحث في ' + entity.title + '…');
    App.setActiveMenu(entity.active_menu);

    if (entity.can_add) {
      $('#addLabel').text('إضافة ' + entity.singular);
      $('[data-add]').removeClass('d-none');
    }

    showFilterChips();
    showStatusFilter();
    renderTableHeader();
    loadRows();
  }

  App.adminReady.done(function () {
    var queryParams = { e: App.getQueryParam('e') || '' };
    new URLSearchParams(location.search).forEach(function (value, name) {
      if (/^f\[[a-z_]+\]$/.test(name)) queryParams[name] = value;
    });
    App.callAdminApi('meta', queryParams).done(function (response) {
      setupPage(response.entity);
    }).fail(function (request) {
      App.showToast(App.getErrorMessage(request), 'error');
      setTimeout(function () { location.href = 'index.html'; }, 1200);
    });
  });
})(jQuery, window.App);
