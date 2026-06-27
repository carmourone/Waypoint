/* global wpntData, jQuery */
(function ($) {
  'use strict';

  const api = wpntData.restUrl;
  const nonce = wpntData.nonce;

  function apiFetch(path, method, data) {
    return $.ajax({
      url: api + path,
      method: method || 'GET',
      contentType: 'application/json',
      data: data ? JSON.stringify(data) : undefined,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', nonce);
      },
    });
  }

  // -------------------------------------------------------------------
  // Load today's sessions into .wpnt-todays-sessions containers
  // -------------------------------------------------------------------
  function loadTodaysSessions() {
    const $container = $('.wpnt-todays-sessions');
    if (!$container.length) return;

    apiFetch('sessions/today').done(function (sessions) {
      if (!sessions.length) {
        $container.html('<p class="wpnt-empty">No sessions today.</p>');
        return;
      }

      let html = '<div class="wpnt-session-cards">';
      sessions.forEach(function (s) {
        const time = s.scheduled_start
          ? new Date(s.scheduled_start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
          : '';
        html +=
          '<div class="wpnt-session-card">' +
          '<h3><a href="' + escHtml(s.url) + '">' + escHtml(s.title) + '</a></h3>' +
          '<p class="wpnt-meta">' + (time ? '⏰ ' + time : '') + (s.location ? ' · ' + escHtml(s.location) : '') + '</p>' +
          '<span class="wpnt-status-pill ' + escHtml(s.status) + '">' + ucfirst(s.status) + '</span>' +
          '</div>';
      });
      html += '</div>';
      $container.html(html);
    });
  }

  // -------------------------------------------------------------------
  // Front-end attendance marking
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-fe-save-att', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const records = [];

    $btn.closest('.wpnt-att-widget').find('.wpnt-fe-att-row').each(function () {
      const sailorId = $(this).data('sailor-id');
      const status = $(this).find('.wpnt-fe-status').val();
      if (status) {
        records.push({ sailor_id: sailorId, status: status });
      }
    });

    if (!records.length) return;

    $btn.prop('disabled', true).text('Saving…');
    apiFetch('attendance', 'POST', { session_id: sessionId, records: records })
      .done(function () {
        $btn.text('Saved ✓');
        setTimeout(function () {
          $btn.prop('disabled', false).text('Save Attendance');
        }, 2500);
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Save Attendance');
      });
  });

  // -------------------------------------------------------------------
  // Progress bar animation on page load
  // -------------------------------------------------------------------
  function animateProgressBars() {
    $('.wpnt-progress-bar-fill').each(function () {
      const target = $(this).data('pct') || 0;
      $(this).css('width', 0).animate({ width: target + '%' }, 600);
    });
  }

  // -------------------------------------------------------------------
  // Utilities
  // -------------------------------------------------------------------
  function escHtml(str) {
    return $('<span>').text(str || '').html();
  }

  function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // -------------------------------------------------------------------
  // Init
  // -------------------------------------------------------------------
  $(function () {
    loadTodaysSessions();
    animateProgressBars();
  });
})(jQuery);
