(function ($, App) {
  'use strict';

  App.callAdminApi = function (action, queryParams, postData) {
    var url = App.url('admin/api.php') + '?' + $.param($.extend({ action: action }, queryParams || {}));
    if (postData === undefined) return $.getJSON(url);
    var isFormData = postData instanceof FormData;
    return $.ajax({
      url: url,
      method: 'POST',
      data: postData,
      dataType: 'json',
      processData: !isFormData,
      contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8'
    });
  };

  function goToLoginPage(hasAdmin) {
    if (hasAdmin === false) {
      location.href = App.url('admin/setup.html');
    } else {
      location.href = App.url('admin/login.html') + '?next=' + encodeURIComponent(location.pathname + location.search);
    }
  }

  $(document).ajaxError(function (event, request) {
    if (request.status === 401) goToLoginPage(request.responseJSON && request.responseJSON.has_admin);
  });

  App.updateCounters = function (counters) {
    if (!counters) return;
    $.each(counters, function (name, count) {
      $('[data-counter="' + name + '"]').text(count).prop('hidden', !count);
    });
  };

  App.renderBadge = function (text, tone) {
    return '<span class="bs-badge bs-badge-' + App.escapeHtml(tone || 'neutral') + '">' + App.escapeHtml(text) + '</span>';
  };

  App.setActiveMenu = function (menuKey) {
    $('#adminNav a').each(function () {
      var isActive = $(this).data('key') === menuKey;
      $(this).toggleClass('active', isActive);
      if (isActive) $(this).attr('aria-current', 'page');
      else $(this).removeAttr('aria-current');
    });
  };

  function renderSidebarMenu(menuGroups, counters) {
    var html = '';
    $.each(menuGroups, function (_, group) {
      if (group.group) html += '<div class="group">' + App.escapeHtml(group.group) + '</div>';
      $.each(group.items, function (_, item) {
        var count = item.counter ? (counters[item.counter] || 0) : 0;
        var counterHtml = item.counter
          ? '<span class="count" data-counter="' + App.escapeHtml(item.counter) + '"' + (count ? '' : ' hidden') + '>' + count + '</span>'
          : '';
        html += '<a href="' + App.escapeHtml(item.href) + '" data-key="' + App.escapeHtml(item.key) + '">' +
          '<span><span class="material-symbols-outlined">' + App.escapeHtml(item.icon) + '</span><span>' + App.escapeHtml(item.label) + '</span></span>' +
          counterHtml + '</a>';
      });
    });
    $('#adminNav').html(html);
  }

  App.adminReady = $.getJSON(App.url('admin/auth.php'), { action: 'me' }).then(function (response) {
    App.csrfToken = response.csrf;
    renderSidebarMenu(response.menu, response.counters);
    App.setActiveMenu($('body').data('menu'));
    $('[data-admin-name]').text(response.admin.name);
    $('[data-admin-company]').text(response.company_short);
    if (response.logo) $('[data-admin-logo]').attr('src', response.logo);
    $('body').removeClass('is-loading');
    return response;
  });

  $(document).on('click', '[data-logout]', function () {
    $.post(App.url('admin/auth.php?action=logout')).always(function () {
      location.href = App.url('admin/login.html');
    });
  });

  function closeSidebar() {
    $('#admin-sidebar, .admin-backdrop').removeClass('open');
  }

  $(document).on('click', '[data-admin-open]', function () {
    $('#admin-sidebar, .admin-backdrop').addClass('open');
  });
  $(document).on('click', '[data-admin-close]', closeSidebar);
  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') closeSidebar();
  });

  $(document).on('focus', '[data-inline-status]', function () {
    $(this).data('previousValue', this.value);
  });
  $(document).on('change', '[data-inline-status]', function () {
    var $select = $(this);
    var $row = $select.closest('tr');
    var $selectedOption = $select.find('option:selected');
    $select.prop('disabled', true);
    App.callAdminApi('patch', { e: $select.data('entity') }, {
      _key: $row.attr('data-key'),
      field: $select.data('field'),
      value: $select.val()
    }).done(function (response) {
      $row.find('.status-cell').html(App.renderBadge($selectedOption.text(), $selectedOption.data('tone')));
      $select.data('previousValue', $select.val());
      App.updateCounters(response.counters);
      App.showToast(response.message);
    }).fail(function (request) {
      $select.val($select.data('previousValue'));
      App.showToast(App.getErrorMessage(request), 'error');
    }).always(function () {
      $select.prop('disabled', false);
    });
  });

  setInterval(function () {
    App.callAdminApi('counters').done(App.updateCounters);
  }, 60000);
})(jQuery, window.App);
