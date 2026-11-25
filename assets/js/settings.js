(function ($, App) {
  'use strict';

  var esc = App.escapeHtml;

  function renderSettingField(field) {
    var inputId = 's_' + field.key;
    var required = field.required ? ' required' : '';
    var input;

    if (field.type === 'textarea') {
      input = '<textarea id="' + inputId + '" name="' + esc(field.key) + '" rows="3" class="form-control"' + required + '>' + esc(field.value) + '</textarea>';
    } else if (field.type === 'image') {
      input = '<div class="d-flex align-items-center gap-md">' +
        '<img src="' + esc(field.url) + '" alt="" class="thumb" style="width:72px;height:72px;object-fit:contain;background:#fff" data-preview="' + esc(field.key) + '">' +
        '<label class="btn-bs btn-soft-primary-bs btn-sm-bs mb-0" style="cursor:pointer">' +
        '<span class="material-symbols-outlined">upload</span><span>تغيير الصورة</span>' +
        '<input type="file" id="' + inputId + '" name="' + esc(field.key) + '" accept=".jpg,.jpeg,.png,.webp" class="d-none" data-preview-input="' + esc(field.key) + '">' +
        '</label></div>';
    } else {
      var inputType = { email: 'email', tel: 'tel', url: 'url' }[field.type] || 'text';
      var leftToRight = inputType !== 'text' ? ' dir="ltr" style="text-align:right"' : '';
      var placeholder = field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '';
      input = '<input type="' + inputType + '" id="' + inputId + '" name="' + esc(field.key) + '" value="' + esc(field.value) + '" class="form-control"' + required + placeholder + leftToRight + '>';
    }

    var requiredStar = field.required ? ' <span class="req">*</span>' : '';
    var helpText = field.help ? '<span class="t-label-sm c-variant fw-normal">' + esc(field.help) + '</span>' : '';
    return '<div class="col-md-' + (field.col || 6) + '"><div class="field">' +
      '<label class="lbl" for="' + inputId + '">' + esc(field.label) + requiredStar + '</label>' + input + helpText +
      '</div></div>';
  }

  function renderSettingsGroup(group) {
    return '<section class="bs-card overflow-hidden"><div class="card-head">' +
      '<h2 class="t-title-lg c-on-surface d-flex align-items-center gap-sm"><span class="material-symbols-outlined c-primary">' + esc(group.icon) + '</span>' + esc(group.title) + '</h2>' +
      '</div><div class="p-lg row g-3 m-0">' + $.map(group.fields, renderSettingField).join('') + '</div></section>';
  }

  App.adminReady.done(function () {
    App.callAdminApi('settings_get').done(function (response) {
      $('#settingsGroups').replaceWith($.map(response.groups, renderSettingsGroup).join(''));
    });
  });

  $(document).on('change', '[data-preview-input]', function () {
    var file = this.files && this.files[0];
    var previewKey = $(this).data('preview-input');
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (event) {
      $('[data-preview="' + previewKey + '"]').attr('src', event.target.result);
    };
    reader.readAsDataURL(file);
  });

  $('#settingsForm').on('submit', function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submitButton = $form.find('[type=submit]');
    var $alert = $('#settingsAlert');
    App.setButtonLoading($submitButton, true);

    App.callAdminApi('settings', {}, new FormData(this)).done(function (response) {
      App.showFieldErrors($form, {});
      $form.find('input[type=file]').val('');
      $alert.removeClass('d-none error').addClass('success').text(response.message);
      App.showToast(response.message);
    }).fail(function (request) {
      var response = request.responseJSON || {};
      App.showFieldErrors($form, response.errors);
      $alert.removeClass('d-none success').addClass('error').text(App.getErrorMessage(request));
    }).always(function () {
      App.setButtonLoading($submitButton, false);
    });
  });
})(jQuery, window.App);
