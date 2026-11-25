(function ($, App) {
  'use strict';

  var mainServiceCategories = ['installation', 'maintenance', 'central', 'emergency'];

  function renderCategoryCard(category) {
    return '<a href="' + App.escapeHtml(category.url) + '" class="bs-card bs-card-hover p-lg d-flex flex-column gap-sm h-100">' +
      '<span class="icon-tile"><span class="material-symbols-outlined">' + App.escapeHtml(category.icon) + '</span></span>' +
      '<h3 class="t-title-lg c-on-surface">' + App.escapeHtml(category.name) + '</h3>' +
      '<p class="t-body-md c-variant">' + App.escapeHtml(category.description) + '</p></a>';
  }

  $.getJSON(App.url('api/catalog.php'), { include: 'categories,featured,services' }).done(function (response) {
    $('#homeCategories').html($.map(response.categories.slice(0, 4), renderCategoryCard).join(''));
    $('#homeProducts').html($.map(response.featured, App.renderProductCard).join(''));

    var homeServices = $.grep(response.services, function (service) {
      return mainServiceCategories.indexOf(service.category) !== -1;
    });
    if (homeServices.length < 4) homeServices = response.services;
    $('#homeServices').html($.map(homeServices.slice(0, 4), App.renderServiceCard).join(''));

    App.fillServiceSelects(response.services);
  }).fail(function (request) {
    App.showToast(App.getErrorMessage(request), 'error');
  });
})(jQuery, window.App);
