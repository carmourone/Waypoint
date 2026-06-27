/* global wpntAdmin, jQuery */
(function ($) {
  'use strict';

  const api = wpntAdmin.restUrl;
  const nonce = wpntAdmin.nonce;

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
  // Attendance — save button
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-save-attendance', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const records = [];

    $('.wpnt-att-row').each(function () {
      const sailorId = $(this).data('sailor-id');
      const status = $(this).find('.wpnt-att-status').val();
      const notes = $(this).find('.wpnt-att-notes').val();
      if (status) {
        records.push({ sailor_id: sailorId, status: status, notes: notes });
      }
    });

    if (!records.length) {
      alert(wpntAdmin.l10n.error);
      return;
    }

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    apiFetch('attendance', 'POST', { session_id: sessionId, records: records })
      .done(function () {
        $btn.text(wpntAdmin.l10n.saved);
        setTimeout(function () {
          $btn.prop('disabled', false).text('Save Attendance');
        }, 2000);
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Save Attendance');
        alert(wpntAdmin.l10n.error);
      });
  });

  // -------------------------------------------------------------------
  // Today screen — save session notes
  // -------------------------------------------------------------------
  $(document).on('click', '#wpnt-save-notes', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const note = $('#wpnt-group-note').val();

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    // Save via WP post meta REST endpoint.
    $.ajax({
      url: wpApiSettings.root + 'wp/v2/wpnt_session/' + sessionId,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ meta: { _wpnt_actual_notes: note } }),
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', nonce);
      },
    })
      .done(function () {
        $btn.text(wpntAdmin.l10n.saved);
        setTimeout(function () {
          $btn.prop('disabled', false).text('Save Session Notes');
        }, 2000);
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Save Session Notes');
        alert(wpntAdmin.l10n.error);
      });
  });

  // -------------------------------------------------------------------
  // Today screen — add observation
  // -------------------------------------------------------------------
  $(document).on('click', '#wpnt-add-obs', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const note = $('#wpnt-obs-note').val().trim();
    const confidence = $('#wpnt-obs-confidence').val();

    if (!note) {
      alert('Please enter an observation note.');
      return;
    }

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    apiFetch('observations', 'POST', {
      session_id: sessionId,
      note: note,
      confidence_level: confidence,
    })
      .done(function () {
        $('#wpnt-obs-note').val('');
        $('#wpnt-obs-confidence').val('');
        $btn.prop('disabled', false).text('Add Observation');

        const $list = $('#wpnt-obs-list');
        if ($list.length) {
          $list.prepend('<li>' + $('<span>').text(note).html() + '</li>');
        }
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Add Observation');
        alert(wpntAdmin.l10n.error);
      });
  });

  // -------------------------------------------------------------------
  // Course admin — generate sessions button
  // -------------------------------------------------------------------
  $(document).on('click', '#wpnt-generate-sessions', function () {
    const $btn = $(this);
    const courseId = $btn.data('course-id');

    if (!confirm(wpntAdmin.l10n.confirm)) {
      return;
    }

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    apiFetch('course/' + courseId + '/generate-sessions', 'POST')
      .done(function (res) {
        $btn.prop('disabled', false).text('Generate Sessions');
        alert('Created ' + res.created + ' session(s).');
        location.reload();
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Generate Sessions');
        alert(wpntAdmin.l10n.error);
      });
  });

})(jQuery);
