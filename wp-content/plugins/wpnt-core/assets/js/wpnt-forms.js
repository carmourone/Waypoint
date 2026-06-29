/* global wpntData, jQuery */
(function ($) {
  'use strict';

  const api   = wpntData.restUrl;
  const nonce = wpntData.nonce;

  function apiFetch(path, method, data) {
    return $.ajax({
      url:         api + path,
      method:      method || 'POST',
      contentType: 'application/json',
      data:        data ? JSON.stringify(data) : undefined,
      beforeSend:  function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', nonce);
      },
    });
  }

  // Combine date + time inputs into a single ISO-8601 local datetime string.
  // Returns '' if date is absent.
  function buildDatetime(date, time) {
    if (!date) return '';
    return time ? date + 'T' + time : date + 'T00:00';
  }

  // Read all named form fields into a plain object, applying datetime merging.
  function serializeForm($form) {
    var raw = {};
    $form.serializeArray().forEach(function (pair) {
      raw[pair.name] = pair.value;
    });

    var payload = {};

    // Merge split date/time fields into scheduled_start / scheduled_end.
    if ('_date' in raw || '_start_time' in raw) {
      var dt = buildDatetime(raw['_date'] || '', raw['_start_time'] || '');
      if (dt) payload['scheduled_start'] = dt;
      delete raw['_date'];
      delete raw['_start_time'];
    }
    if ('_end_time' in raw && raw['_date']) {
      var dtEnd = buildDatetime(raw['_date'] || '', raw['_end_time']);
      if (dtEnd) payload['scheduled_end'] = dtEnd;
    }
    delete raw['_end_time'];

    // Copy remaining fields, skipping empty strings unless they're intentional.
    Object.keys(raw).forEach(function (k) {
      payload[k] = raw[k];
    });

    return payload;
  }

  function showFormError($form, message) {
    var $err = $form.find('.wpnt-form-error');
    if (!$err.length) {
      $err = $('<p class="wpnt-form-error" role="alert"></p>').prependTo($form.find('.form-actions'));
    }
    $err.text(message).show();
  }

  function showFormSuccess($form, message) {
    var $msg = $form.find('.wpnt-form-success');
    if (!$msg.length) {
      $msg = $('<p class="wpnt-form-success" role="status"></p>').prependTo($form.find('.form-actions'));
    }
    $msg.text(message).show();
  }

  // -------------------------------------------------------------------
  // Generic .wpnt-form handler
  // -------------------------------------------------------------------
  $(document).on('submit', '.wpnt-form', function (e) {
    e.preventDefault();

    var $form    = $(this);
    var endpoint = $form.data('endpoint');
    var method   = ($form.data('method') || 'POST').toUpperCase();
    var redirect = $form.data('redirect'); // 'none' = stay; anything else = redirect to res.url

    if (!endpoint) return;

    $form.find('.wpnt-form-error').hide();
    $form.find('.wpnt-form-success').hide();

    var payload = serializeForm($form);

    var $submit = $form.find('[type=submit]');
    $submit.prop('disabled', true);
    var origText = $submit.text();
    $submit.text(wpntData.i18n.saving || 'Saving…');

    apiFetch(endpoint, method, payload)
      .done(function (res) {
        if (redirect === 'none') {
          showFormSuccess($form, wpntData.i18n.saved || 'Saved.');
          $submit.prop('disabled', false).text(origText);
        } else if (res && res.url) {
          window.location.href = res.url;
        } else {
          // Fallback: reload the page.
          window.location.reload();
        }
      })
      .fail(function (xhr) {
        var msg = wpntData.i18n.error || 'Something went wrong. Please try again.';
        try {
          var body = JSON.parse(xhr.responseText);
          if (body && body.message) msg = body.message;
        } catch (ex) { /* ignore */ }
        showFormError($form, msg);
        $submit.prop('disabled', false).text(origText);
      });
  });

})(jQuery);
