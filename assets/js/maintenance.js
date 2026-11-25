(function ($, App) {
  'use strict';

  var selectedServiceId = App.getQueryParam('service') || '';
  var selectedProductId = parseInt(App.getQueryParam('product'), 10) || 0;
  $('select[data-services]').attr('data-keep', selectedServiceId);

  $.getJSON(App.url('api/catalog.php'), { include: 'services', product: selectedProductId || '' }).done(function (response) {
    App.fillServiceSelects(response.services);
    if (response.product) {
      $('input[name=product_id]').val(response.product.id);
      $('#productName').text(response.product.name);
      $('#productNotice').removeClass('d-none');
    }
  }).fail(function (request) {
    App.showToast(App.getErrorMessage(request), 'error');
  });
})(jQuery, window.App);
