(function ($) {
  'use strict';

  var mainScript = document.currentScript || $('script[src*="assets/js/main.js"]')[0];
  var App = window.App = {
    baseUrl: mainScript.src.replace(/\/assets\/js\/main\.js.*$/, ''),
    csrfToken: ''
  };

  App.url = function (path) {
    return App.baseUrl + '/' + String(path).replace(/^\//, '');
  };

  $.ajaxSetup({
    beforeSend: function (request) {
      if (App.csrfToken) request.setRequestHeader('X-CSRF-Token', App.csrfToken);
    }
  });

  App.escapeHtml = function (text) {
    return String(text == null ? '' : text).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
    });
  };

  App.getQueryParam = function (name) {
    return new URLSearchParams(location.search).get(name);
  };

  App.showToast = function (message, type) {
    type = type || 'success';
    var icon = type === 'error' ? 'error' : 'check_circle';
    var $toast = $('<div class="toast-bs ' + type + '" role="status"><span class="material-symbols-outlined">' + icon + '</span><span></span></div>');
    $toast.find('span:last').text(message);
    $('#toasts').append($toast);
    setTimeout(function () {
      $toast.fadeOut(250, function () { $toast.remove(); });
    }, 4200);
  };

  App.formatPrice = function (amount) {
    if (amount === null || amount === '' || amount === undefined) return 'حسب المعاينة';
    var number = Number(amount);
    return number.toLocaleString('en-US', { maximumFractionDigits: number % 1 ? 2 : 0 }) + ' ₪';
  };

  App.getErrorMessage = function (request) {
    var response = request && request.responseJSON;
    return (response && response.message) || 'حدث خطأ غير متوقع، حاول مجدداً.';
  };

  App.showFieldErrors = function ($form, errors) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback.js').remove();
    $.each(errors || {}, function (fieldName, message) {
      var $field = $form.find('[name="' + fieldName + '"]');
      if (!$field.length) return;
      $field.addClass('is-invalid');
      var $after = $field.closest('.input-icon').length ? $field.closest('.input-icon') : $field;
      $('<div class="invalid-feedback js d-block"></div>').text(message).insertAfter($after);
    });
    var $firstInvalid = $form.find('.is-invalid').first();
    if ($firstInvalid.length) $firstInvalid.trigger('focus');
  };

  App.setButtonLoading = function ($button, isLoading) {
    if (isLoading) {
      $button.data('originalHtml', $button.html()).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>جارٍ الإرسال…</span>');
    } else {
      $button.prop('disabled', false).html($button.data('originalHtml'));
    }
  };

  App.fillPageData = function (data, $container) {
    $container = $container || $(document);
    $container.find('[data-text]').each(function () {
      var value = data[$(this).data('text')];
      if (value !== undefined) $(this).text(value);
    });
    $container.find('[data-src]').each(function () {
      var value = data[$(this).data('src')];
      if (value) $(this).attr('src', value);
    });
    $container.find('[data-href]').each(function () {
      var value = data[$(this).data('href')];
      if (value) $(this).attr('href', value);
      else if ($(this).is('[data-hide-empty]')) $(this).remove();
    });
    $container.find('[data-alt]').each(function () {
      var altText = $(this).data('alt').replace(/\{(\w+)\}/g, function (_, key) { return data[key] || ''; });
      $(this).attr('alt', altText);
    });
    $container.find('[data-show-if]').each(function () {
      $(this).toggleClass('d-none', !data[$(this).data('show-if')]);
    });
  };

  App.renderProductCard = function (product) {
    var stockBadge = product.available
      ? '<span class="bs-badge bs-badge-success">متوفر</span>'
      : '<span class="bs-badge bs-badge-error">غير متوفر</span>';
    var link = App.escapeHtml(product.url);
    return '' +
      '<article class="bs-card product-card"><div>' +
        '<a href="' + link + '" class="media" tabindex="-1" aria-hidden="true"><img src="' + App.escapeHtml(product.image) + '" alt="' + App.escapeHtml(product.name) + '" loading="lazy"></a>' +
        '<div class="body"><div class="d-flex align-items-center justify-content-between gap-xs">' +
          '<span class="brand-name">' + App.escapeHtml(product.brand) + '</span>' + stockBadge + '</div>' +
          '<h3 class="t-title-md"><a href="' + link + '">' + App.escapeHtml(product.name) + '</a></h3>' +
        '</div></div>' +
        '<footer class="foot"><span class="price">' + App.escapeHtml(product.price_text) + '</span>' +
          '<a href="' + link + '" class="btn-bs btn-primary-bs btn-sm-bs">التفاصيل</a></footer>' +
      '</article>';
  };

  App.renderServiceCard = function (service) {
    return '' +
      '<article class="bs-card p-lg d-flex flex-column justify-content-between gap-md h-100">' +
        '<div class="d-flex flex-column gap-sm">' +
          '<span class="icon-tile' + (service.emergency ? ' icon-tile-error' : '') + '"><span class="material-symbols-outlined">' + App.escapeHtml(service.icon) + '</span></span>' +
          '<h3 class="t-title-lg c-on-surface">' + App.escapeHtml(service.name) + '</h3>' +
          '<p class="t-body-md c-variant">' + App.escapeHtml(service.description) + '</p>' +
        '</div>' +
        '<div class="divider-top pt-3 d-flex align-items-center justify-content-between gap-sm">' +
          '<span class="t-title-md c-primary tabular">' + App.escapeHtml(service.price_text) + '</span>' +
          '<a href="' + App.escapeHtml(service.url) + '" class="btn-bs btn-soft-bs btn-sm-bs">اطلب الخدمة</a>' +
        '</div>' +
      '</article>';
  };

  App.fillServiceSelects = function (services) {
    $('select[data-services]').each(function () {
      var $select = $(this);
      var selectedId = $select.attr('data-keep') || '';
      $.each(services, function (_, service) {
        $('<option>').val(service.id).text(service.name).appendTo($select);
      });
      if (selectedId) $select.val(selectedId);
    });
  };

  function showMap(site) {
    var $mapBox = $('[data-map]');
    if (!$mapBox.length || !site.map_embed) return;
    $mapBox.html('<iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="خريطة موقع المعرض"></iframe>')
      .find('iframe').attr('src', site.map_embed);
  }

  function loadPartial($placeholder) {
    return $.get($placeholder.data('include')).then(function (html) {
      var $content = $(html);
      $placeholder.replaceWith($content);
      return $content;
    });
  }

  function todayDate() {
    var now = new Date();
    return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
  }

  var partialRequests = $('[data-include]').map(function () { return loadPartial($(this)); }).get();
  var siteRequest = $.getJSON(App.url('api/site.php'));

  App.siteReady = $.when.apply($, [siteRequest].concat(partialRequests)).then(function (siteResult) {
    var response = (siteResult && siteResult[0]) || siteResult;
    var site = response.site;
    App.csrfToken = response.csrf;

    var currentPage = $('body').data('page');
    $('.main-nav a, #mobile-nav a').each(function () {
      var isCurrent = $(this).data('nav') === currentPage;
      $(this).toggleClass('active', isCurrent);
      if (isCurrent) $(this).attr('aria-current', 'page');
    });

    App.fillPageData(site);
    showMap(site);
    $('input[type=date][data-min-today]').attr('min', todayDate());
    if (site.logo) $('link[rel=icon]').attr('href', site.logo);
    return site;
  });

  App.siteReady.fail(function (request) {
    App.showToast(App.getErrorMessage(request), 'error');
  });

  $(document).on('click', '[data-toggle-nav]', function () {
    var $mobileNav = $('#mobile-nav').toggleClass('open');
    $(this).attr('aria-expanded', $mobileNav.hasClass('open'));
  });

  $(document).on('submit', 'form[data-ajax]', function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submitButton = $form.find('[type=submit]').first();
    var $alert = $form.find('.form-alert');

    if (this.checkValidity && !this.checkValidity()) {
      var errors = {};
      $form.find(':invalid').each(function () {
        if (this.name) errors[this.name] = this.validationMessage || 'هذا الحقل مطلوب';
      });
      App.showFieldErrors($form, errors);
      return;
    }

    App.setButtonLoading($submitButton, true);
    $alert.addClass('d-none');

    $.ajax({
      url: App.url($form.data('ajax')),
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json'
    }).done(function (response) {
      App.showFieldErrors($form, {});
      $form[0].reset();
      $form.find('select[data-keep]').each(function () { $(this).val($(this).attr('data-keep')); });
      $alert.removeClass('d-none error').addClass('success')
        .html('<span class="material-symbols-outlined">check_circle</span><span></span>')
        .find('span:last').text(response.message);
      App.showToast(response.message, 'success');
    }).fail(function (request) {
      var response = request.responseJSON || {};
      App.showFieldErrors($form, response.errors);
      $alert.removeClass('d-none success').addClass('error')
        .html('<span class="material-symbols-outlined">error</span><span></span>')
        .find('span:last').text(App.getErrorMessage(request));
    }).always(function () {
      App.setButtonLoading($submitButton, false);
    });
  });

  $(document).on('input change', '.is-invalid', function () {
    var $field = $(this).removeClass('is-invalid');
    var $wrapper = $field.closest('.field').length ? $field.closest('.field') : $field.parent();
    $wrapper.find('.invalid-feedback.js').remove();
  });
})(jQuery);
