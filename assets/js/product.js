(function ($, App) {
  'use strict';

  var productId = parseInt(App.getQueryParam('id'), 10) || 0;
  var siteTitle = document.title.split(' — ').pop();

  function showNotFound() {
    $('#productPage').addClass('d-none');
    $('#notFound').removeClass('d-none');
    document.title = 'المنتج غير موجود — ' + siteTitle;
  }

  function renderSpecRow(spec) {
    return '<tr><th scope="row">' + App.escapeHtml(spec[0]) + '</th><td class="tabular">' + App.escapeHtml(spec[1]) + '</td></tr>';
  }

  function showProduct(response) {
    var product = response.product;
    document.title = product.name + ' — ' + siteTitle;
    $('meta[name=description]').attr('content', String(product.description || '').slice(0, 160));

    $('#crumbName').text(product.name);
    if (product.category_name) {
      $('#crumbCat').removeClass('d-none').css('display', 'contents')
        .find('a').attr('href', product.category_url).text(product.category_name);
    }

    $('#pImage').attr({ src: product.image, alt: product.name });
    $('#pBrand').text(product.brand_label);
    $('#pStock').html(product.available
      ? '<span class="bs-badge bs-badge-success">متوفر في المخزون</span>'
      : '<span class="bs-badge bs-badge-error">غير متوفر حالياً</span>');
    $('#pName').text(product.name);
    if (product.model) $('#pModel').removeClass('d-none').text('Model: ' + product.model);
    $('#pPrice').text(product.price_text);
    $('#pWarranty').text(product.warranty_note || '');
    $('#pDesc').text(product.description || '').toggleClass('d-none', !product.description);
    $('[data-order]').attr('href', product.order_url);

    if (product.specs.length) {
      $('#specsBody').html($.map(product.specs, renderSpecRow).join(''));
      $('#specsCard').removeClass('d-none');
    }

    if (response.similar.length) {
      $('#similarGrid').html($.map(response.similar, App.renderProductCard).join(''));
      $('#similarSection').removeClass('d-none');
    }
  }

  if (!productId) {
    showNotFound();
  } else {
    $.getJSON(App.url('api/product.php'), { id: productId }).done(showProduct).fail(showNotFound);
  }
})(jQuery, window.App);
