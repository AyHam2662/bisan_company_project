(function ($, App) {
  'use strict';

  $.getJSON(App.url('api/catalog.php'), { include: 'services' }).done(function (response) {
    $('#servicesGrid').html($.map(response.services, App.renderServiceCard).join(''));
  }).fail(function (request) {
    $('#servicesGrid').html('<div class="bs-card empty-state" style="grid-column:1/-1"><p>' + App.escapeHtml(App.getErrorMessage(request)) + '</p></div>');
  });
})(jQuery, window.App);
