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
      const athleteId = $(this).data('athlete-id');
      const status = $(this).find('.wpnt-att-status').val();
      const notes = $(this).find('.wpnt-att-notes').val();
      if (status) {
        records.push({ athlete_id: athleteId, status: status, notes: notes });
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
    const athleteName = $('#wpnt-obs-athlete').val().trim();

    if (!note) {
      alert('Please enter an observation note.');
      return;
    }

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    apiFetch('observations', 'POST', {
      session_id: sessionId,
      note: note,
      confidence_level: confidence,
      athlete_name: athleteName,
    })
      .done(function () {
        $('#wpnt-obs-note').val('');
        $('#wpnt-obs-confidence').val('');
        $('#wpnt-obs-athlete').val('');
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
  // Session Groups — toggle edit plan panel
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-toggle-edit-plan', function () {
    $(this).closest('.wpnt-group-block').find('.wpnt-edit-plan-panel').toggle();
  });

  // -------------------------------------------------------------------
  // Session Groups — save plan (planned + actual skills)
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-save-plan', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const bpGroupId = $btn.data('bp-group-id');
    const $block = $btn.closest('.wpnt-group-block');

    const plannedSkills = [];
    $block.find('[data-field="planned_skills"] input[type="checkbox"]:checked').each(function () {
      plannedSkills.push(parseInt($(this).val(), 10));
    });

    const actualSkills = [];
    $block.find('[data-field="actual_skills"] input[type="checkbox"]:checked').each(function () {
      actualSkills.push(parseInt($(this).val(), 10));
    });

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    apiFetch('sessions/' + sessionId + '/groups/' + bpGroupId, 'PUT', {
      planned_skills: plannedSkills,
      actual_skills: actualSkills,
    })
      .done(function () {
        $btn.text(wpntAdmin.l10n.saved);
        setTimeout(function () { location.reload(); }, 800);
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Save Plan');
        alert(wpntAdmin.l10n.error);
      });
  });

  // -------------------------------------------------------------------
  // Session Groups — save group attendance
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-save-group-att', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const bpGroupId = $btn.data('bp-group-id');
    const $block = $btn.closest('.wpnt-group-block');
    const records = [];

    $block.find('.wpnt-group-att-row').each(function () {
      const $row = $(this);
      const athleteId = $row.data('athlete-id');
      const status = $row.find('.wpnt-att-status-select').val();
      const notes = $row.find('.wpnt-att-notes-input').val();
      const skills = [];

      $row.find('.wpnt-skill-check-input:checked').each(function () {
        skills.push(parseInt($(this).data('skill-id'), 10));
      });

      records.push({ athlete_id: athleteId, status: status || 'attended', notes: notes || '', skills: skills });
    });

    $btn.prop('disabled', true).text(wpntAdmin.l10n.saving);

    apiFetch('sessions/' + sessionId + '/groups/' + bpGroupId + '/attendance', 'POST', {
      records: records,
    })
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
  // Session Groups — athlete search typeahead filter
  // -------------------------------------------------------------------
  $(document).on('input', '.wpnt-athlete-search', function () {
    const val = $(this).val().trim().toLowerCase();
    const $select = $(this).siblings('.wpnt-athlete-select');

    if (val.length >= 2) {
      $select.show().find('option').each(function () {
        $(this).toggle($(this).val() === '' || $(this).text().toLowerCase().includes(val));
      });
    } else {
      $select.hide();
    }
  });

  // -------------------------------------------------------------------
  // Session Groups — add ad-hoc athlete
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-add-athlete-btn', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const bpGroupId = $btn.data('bp-group-id');
    const $row = $btn.closest('.wpnt-add-athlete-row');
    const athleteId = $row.find('.wpnt-athlete-select').val();
    const enroll = $row.find('.wpnt-enroll-in-course').is(':checked');

    if (!athleteId) {
      alert(wpntAdmin.l10n.confirm);
      return;
    }

    $btn.prop('disabled', true);

    apiFetch('sessions/' + sessionId + '/groups/' + bpGroupId + '/add-athlete', 'POST', {
      athlete_id: parseInt(athleteId, 10),
      enroll_in_course: enroll,
    })
      .done(function () { location.reload(); })
      .fail(function () {
        $btn.prop('disabled', false);
        alert(wpntAdmin.l10n.error);
      });
  });

  // -------------------------------------------------------------------
  // Session Groups — remove ad-hoc athlete
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-remove-adhoc', function () {
    const $btn = $(this);
    if (!confirm(wpntAdmin.l10n.confirm)) return;

    apiFetch(
      'sessions/' + $btn.data('session-id') + '/groups/' + $btn.data('bp-group-id') + '/athletes/' + $btn.data('athlete-id'),
      'DELETE'
    )
      .done(function () { $btn.closest('tr').fadeOut(200, function () { $(this).remove(); }); })
      .fail(function () { alert(wpntAdmin.l10n.error); });
  });

  // -------------------------------------------------------------------
  // Session Groups — add a new group to the session
  // -------------------------------------------------------------------
  $(document).on('click', '.wpnt-add-group', function () {
    const $btn = $(this);
    const sessionId = $btn.data('session-id');
    const bpGroupId = parseInt(prompt('BuddyPress Group ID for this cohort:'), 10);
    if (!bpGroupId) return;
    const label = prompt('Group label (e.g. "Tackers 1"):') || '';

    $btn.prop('disabled', true);

    apiFetch('sessions/' + sessionId + '/groups', 'POST', { bp_group_id: bpGroupId, label: label })
      .done(function () { location.reload(); })
      .fail(function () {
        $btn.prop('disabled', false);
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
