(function ($, App) {
  'use strict';

  var $filtersForm = $('#filters');
  var $productsGrid = $('#grid');
  var $pagination = $('#pager');
  var $resultCount = $('#resultCount');
  var currentPage = 1;
  var searchTimer = null;
  var activeRequest = null;

  var urlParams = new URLSearchParams(location.search);
  if (urlParams.get('page')) currentPage = parseInt(urlParams.get('page'), 10) || 1;

  function restoreFiltersFromUrl() {
    ['q', 'sort', 'min', 'max'].forEach(function (name) {
      if (urlParams.get(name)) $('[name="' + name + '"]').val(urlParams.get(name));
    });
    urlParams.getAll('brand[]').forEach(function (brand) {
      $filtersForm.find('[name="brand[]"]').filter(function () { return this.value === brand; }).prop('checked', true);
    });
    urlParams.getAll('category[]').forEach(function (categoryId) {
      $filtersForm.find('[name="category[]"][value="' + categoryId + '"]').prop('checked', true);
    });
    if (urlParams.get('available') === '1') $filtersForm.find('[name=available][value=1]').prop('checked', true);
  }

  function renderCheckbox(name, value, label) {
    return '<label class="check-bs"><input type="checkbox" name="' + name + '" value="' + App.escapeHtml(value) + '"><span>' + App.escapeHtml(label) + '</span></label>';
  }

  function renderPagination(page, totalPages) {
    if (totalPages <= 1) {
      $pagination.empty();
      return;
    }
    var html = '<button type="button" data-page="' + (page - 1) + '" aria-label="الصفحة السابقة"' + (page <= 1 ? ' disabled' : '') + '><span class="material-symbols-outlined ms-18">chevron_right</span></button>';
    for (var pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
      var isFarFromCurrent = totalPages > 7 && pageNumber > 2 && pageNumber < totalPages - 1 && Math.abs(pageNumber - page) > 1;
      if (isFarFromCurrent) {
        if (pageNumber === 3 || pageNumber === totalPages - 2) html += '<button type="button" disabled>…</button>';
        continue;
      }
      html += '<button type="button" data-page="' + pageNumber + '"' + (pageNumber === page ? ' class="active" aria-current="page"' : '') + '>' + pageNumber + '</button>';
    }
    html += '<button type="button" data-page="' + (page + 1) + '" aria-label="الصفحة التالية"' + (page >= totalPages ? ' disabled' : '') + '><span class="material-symbols-outlined ms-18">chevron_left</span></button>';
    $pagination.html(html);
  }

  function loadProducts(scrollToGrid) {
    if (activeRequest) activeRequest.abort();
    $productsGrid.attr('aria-busy', 'true').css('opacity', 0.55);
    var queryString = $filtersForm.serialize() + '&page=' + currentPage;

    activeRequest = $.getJSON(App.url('api/products.php'), queryString).done(function (response) {
      if (!response.items.length) {
        $productsGrid.html('<div class="bs-card empty-state" style="grid-column:1/-1"><span class="material-symbols-outlined">search_off</span>' +
          '<p class="t-title-md c-on-surface">لا توجد منتجات مطابقة</p><p class="t-body-md">جرّب تعديل الفلاتر أو كلمة البحث.</p></div>');
      } else {
        $productsGrid.html($.map(response.items, App.renderProductCard).join(''));
      }
      $resultCount.text(response.total ? 'عرض ' + response.items.length + ' من أصل ' + response.total + ' منتج' : '');
      currentPage = response.page;
      renderPagination(response.page, response.pages);

      var cleanQuery = queryString.replace(/(^|&)[^=&]+=(?=&|$)/g, '').replace(/^&/, '');
      history.replaceState(null, '', '?' + cleanQuery);
      if (scrollToGrid) $('html,body').animate({ scrollTop: $productsGrid.offset().top - 120 }, 200);
    }).fail(function (request) {
      if (request.statusText === 'abort') return;
      $productsGrid.html('<div class="bs-card empty-state" style="grid-column:1/-1"><span class="material-symbols-outlined">error</span><p>' + App.escapeHtml(App.getErrorMessage(request)) + '</p></div>');
    }).always(function () {
      $productsGrid.attr('aria-busy', 'false').css('opacity', 1);
    });
  }

  function reloadFromFirstPage() {
    currentPage = 1;
    loadProducts();
  }

  $filtersForm.on('change', 'input[type=checkbox], input[type=radio]', reloadFromFirstPage);
  $(document).on('change', '#sort', reloadFromFirstPage);
  $(document).on('input', '#q, #filters input[type=number]', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reloadFromFirstPage, 350);
  });
  $('#resetFilters').on('click', function () {
    setTimeout(function () {
      $('#q').val('');
      $('#sort').val('popular');
      reloadFromFirstPage();
    }, 0);
  });
  $pagination.on('click', 'button[data-page]:not(:disabled)', function () {
    currentPage = parseInt($(this).data('page'), 10);
    loadProducts(true);
  });

  $.getJSON(App.url('api/catalog.php'), { include: 'categories,brands' }).done(function (response) {
    $('#categoryFilter').append($.map(response.categories, function (category) {
      return renderCheckbox('category[]', category.id, category.name);
    }).join(''));
    $('#brandFilter').append($.map(response.brands, function (brand) {
      return renderCheckbox('brand[]', brand.name, brand.label);
    }).join(''));
    $filtersForm.find('[name=min]').attr('placeholder', response.price_range.min);
    $filtersForm.find('[name=max]').attr('placeholder', response.price_range.max);
    restoreFiltersFromUrl();
  }).always(function () {
    loadProducts();
  });
})(jQuery, window.App);
