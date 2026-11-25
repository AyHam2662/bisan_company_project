(function ($, App) {
  'use strict';

  var $alert = $('#authAlert');
  var isSetupPage = $('#setupForm').length > 0;

  function showError(message) {
    $alert.removeClass('d-none').find('span:last').text(message);
  }

  $.getJSON(App.url('admin/auth.php'), { action: 'status' }).done(function (status) {
    App.csrfToken = status.csrf;
    if (status.logo) $('[data-admin-logo]').attr('src', status.logo);

    if (status.logged_in) {
      location.href = App.url('admin/index.html');
    } else if (isSetupPage && status.has_admin) {
      location.href = App.url('admin/login.html');
    } else if (!isSetupPage && !status.has_admin) {
      location.href = App.url('admin/setup.html');
    }
  });

  $('#loginForm, #setupForm').on('submit', function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submitButton = $form.find('[type=submit]');

    if (!this.checkValidity()) {
      var errors = {};
      $form.find(':invalid').each(function () { errors[this.name] = this.validationMessage; });
      App.showFieldErrors($form, errors);
      return;
    }

    var formData = $form.serializeArray();
    if (!isSetupPage) formData.push({ name: 'next', value: App.getQueryParam('next') || '' });

    $alert.addClass('d-none');
    App.setButtonLoading($submitButton, true);
    var action = isSetupPage ? 'setup' : 'login';
    $.post(App.url('admin/auth.php?action=' + action), $.param(formData), null, 'json')
      .done(function (response) {
        location.href = response.redirect;
      })
      .fail(function (request) {
        var response = request.responseJSON || {};
        App.showFieldErrors($form, response.errors);
        showError(App.getErrorMessage(request));
        App.setButtonLoading($submitButton, false);
      });
  });
})(jQuery, window.App);
