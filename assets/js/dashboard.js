(function ($, App) {
  'use strict';

  var esc = App.escapeHtml;

  function renderStatCard(stat) {
    return '<div class="col-sm-6 col-xl-3"><a href="' + esc(stat.href) + '" class="bs-card bs-card-hover stat-card h-100">' +
      '<div><p class="t-label-lg c-variant">' + esc(stat.label) + '</p><p class="num">' + esc(stat.value) + '</p></div>' +
      '<span class="icon-tile ' + esc(stat.tone) + '"><span class="material-symbols-outlined">' + esc(stat.icon) + '</span></span></a></div>';
  }

  function renderSmallStat(stat) {
    return '<div class="col-6 col-xl-3"><a href="' + esc(stat.href) + '" class="bs-card bs-card-hover p-md d-flex align-items-center gap-sm h-100 c-on-surface">' +
      '<span class="material-symbols-outlined c-primary">' + esc(stat.icon) + '</span>' +
      '<span class="t-body-md c-variant flex-grow-1">' + esc(stat.label) + '</span>' +
      '<span class="t-title-md tabular">' + esc(stat.value) + '</span></a></div>';
  }

  function renderRequestRow(request, statusOptions) {
    var currentStatus = $.grep(statusOptions, function (option) { return option.value === request.status; })[0]
      || { text: request.status, tone: 'neutral' };

    var statusSelect = '<select class="form-select inline-select" data-inline-status data-entity="requests" data-field="status" aria-label="تغيير حالة الطلب">' +
      $.map(statusOptions, function (option) {
        return '<option value="' + esc(option.value) + '" data-tone="' + esc(option.tone) + '"' + (option.value === request.status ? ' selected' : '') + '>' + esc(option.text) + '</option>';
      }).join('') + '</select>';

    return '<tr data-key="' + esc(JSON.stringify({ request_id: request.id })) + '">' +
      '<td class="mono tabular text-nowrap" dir="ltr" style="text-align:right"><a href="manage.html?e=visits&f[request_id]=' + request.id + '">' + esc(request.code) + '</a></td>' +
      '<td class="strong">' + esc(request.customer) + '</td>' +
      '<td>' + esc(request.service) + '</td>' +
      '<td>' + esc(request.address || '—') + '</td>' +
      '<td class="tabular text-nowrap">' + esc(request.preferred_date || '—') + '</td>' +
      '<td>' + App.renderBadge(request.priority.text, request.priority.tone) + '</td>' +
      '<td class="status-cell">' + App.renderBadge(currentStatus.text, currentStatus.tone) + '</td>' +
      '<td>' + statusSelect + '</td></tr>';
  }

  function renderMessageRow(message) {
    return '<tr><td class="strong"><a class="c-on-surface" href="manage.html?e=messages&f[message_id]=' + message.id + '">' + esc(message.subject) + '</a></td>' +
      '<td>' + esc(message.sender) + '</td>' +
      '<td class="tabular text-nowrap">' + esc(message.date) + '</td>' +
      '<td>' + App.renderBadge(message.status.text, message.status.tone) + '</td></tr>';
  }

  function renderVisitRow(visit) {
    return '<tr><td class="tabular text-nowrap">' + esc(visit.when) + '</td><td class="strong">' + esc(visit.customer) + '</td><td>' + esc(visit.tech) + '</td></tr>';
  }

  function emptyRow(columns, text) {
    return '<tr><td colspan="' + columns + '" class="text-center py-4">' + text + '</td></tr>';
  }

  function showDashboard(data) {
    $('#stats').html($.map(data.stats, renderStatCard).join(''));
    $('#stats2').html($.map(data.stats2, renderSmallStat).join(''));

    $('#requestRows').html(data.requests.length
      ? $.map(data.requests, function (request) { return renderRequestRow(request, data.request_status); }).join('')
      : emptyRow(8, 'لا توجد طلبات بعد.'));

    $('#messageRows').html(data.messages.length
      ? $.map(data.messages, renderMessageRow).join('')
      : emptyRow(4, 'لا توجد رسائل.'));

    $('#visitRows').html(data.visits.length
      ? $.map(data.visits, renderVisitRow).join('')
      : emptyRow(3, 'لا توجد زيارات مجدولة.'));
  }

  App.adminReady.done(function () {
    App.callAdminApi('dashboard').done(showDashboard).fail(function (request) {
      App.showToast(App.getErrorMessage(request), 'error');
    });
  });
})(jQuery, window.App);
