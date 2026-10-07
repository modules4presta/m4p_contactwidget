/**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

/**
 * Floating contact widget behaviour: bubble auto-show/dismiss (once per
 * browser, via localStorage), FAB open/close (icon swaps to an X while the
 * panel is open), channel tabs, client-side validation, and AJAX submit.
 * Server-side validation in controllers/front/submit.php is the source of
 * truth — this only avoids obviously-bad round trips.
 */
(function () {
  var placeholders = window.m4pContactWidgetPlaceholders || { phone: '', email: '' };
  'use strict';

  var BUBBLE_DISMISS_KEY = 'm4pContactBubbleDismissed';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var root = document.getElementById('m4p-contact-widget');
    if (!root) { return; }

    var fab = document.getElementById('m4pContactFab');
    var panel = document.getElementById('m4pContactPanel');
    var panelClose = document.getElementById('m4pContactPanelClose');
    var bubble = document.getElementById('m4pContactBubble');
    var bubbleClose = document.getElementById('m4pContactBubbleClose');
    var tabs = root.querySelectorAll('.m4p-contact-widget__tab');
    var channelInput = document.getElementById('m4pContactChannel');
    var valueInput = document.getElementById('m4pContactValue');
    var form = document.getElementById('m4pContactForm');
    var submitBtn = document.getElementById('m4pContactSubmit');
    var errorEl = document.getElementById('m4pContactError');
    var successEl = document.getElementById('m4pContactError') && document.getElementById('m4pContactSuccess');

    function showBubble() {
      var dismissed = false;
      try { dismissed = window.localStorage.getItem(BUBBLE_DISMISS_KEY) === '1'; } catch (e) { /* private mode */ }
      if (!dismissed) {
        root.classList.add('is-bubble-visible');
      }
    }

    function hideBubble(persist) {
      root.classList.remove('is-bubble-visible');
      if (persist) {
        try { window.localStorage.setItem(BUBBLE_DISMISS_KEY, '1'); } catch (e) { /* private mode */ }
      }
    }

    function openPanel() {
      hideBubble(true);
      panel.hidden = false;
      root.classList.add('is-open');
      fab.setAttribute('aria-expanded', 'true');
      window.setTimeout(function () { valueInput.focus(); }, 50);
    }

    function closePanel() {
      panel.hidden = true;
      root.classList.remove('is-open');
      fab.setAttribute('aria-expanded', 'false');
    }

    fab.addEventListener('click', function () {
      if (panel.hidden) { openPanel(); } else { closePanel(); }
    });
    panelClose.addEventListener('click', closePanel);
    bubbleClose.addEventListener('click', function () { hideBubble(true); });

    // Clicking the bubble itself opens the panel too (bigger, friendlier target).
    bubble.addEventListener('click', function (e) {
      if (e.target === bubbleClose) { return; }
      openPanel();
    });

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        tabs.forEach(function (t) {
          t.classList.remove('is-active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('is-active');
        tab.setAttribute('aria-selected', 'true');

        var channel = tab.getAttribute('data-channel');
        channelInput.value = channel;
        valueInput.value = '';
        hideError();

        if (channel === 'email') {
          valueInput.type = 'email';
          valueInput.inputMode = 'email';
          valueInput.autocomplete = 'email';
          valueInput.placeholder = placeholders.email;
        } else {
          valueInput.type = 'text';
          valueInput.inputMode = 'tel';
          valueInput.autocomplete = 'tel';
          valueInput.placeholder = placeholders.phone;
        }
      });
    });

    function hideError() {
      errorEl.hidden = true;
      errorEl.textContent = '';
    }

    function showError(message) {
      successEl.hidden = true;
      errorEl.textContent = message;
      errorEl.hidden = false;
    }

    function showSuccess(message) {
      hideError();
      successEl.textContent = message;
      successEl.hidden = false;
      form.reset();
      channelInput.value = 'phone';
    }

    function isValidClientSide() {
      var value = valueInput.value.trim();
      var consent = document.getElementById('m4pContactConsent').checked;

      if (!consent) {
        showError('Zaznacz zgodę na przetwarzanie danych, aby wysłać zgłoszenie.');
        return false;
      }

      if (channelInput.value === 'email') {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          showError('Podaj prawidłowy adres e-mail.');
          return false;
        }
      } else {
        if (!/^\+?[0-9]{7,15}$/.test(value.replace(/[\s-]/g, ''))) {
          showError('Podaj prawidłowy numer telefonu.');
          return false;
        }
      }

      hideError();
      return true;
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!isValidClientSide() || typeof window.m4pContactWidgetUrl === 'undefined') { return; }

      submitBtn.disabled = true;

      var data = new FormData();
      data.append('channel', channelInput.value);
      data.append('contact_value', valueInput.value.trim());
      data.append('consent', document.getElementById('m4pContactConsent').checked ? '1' : '');
      data.append('m4p_hp', document.getElementById('m4pContactHp').value);
      data.append('m4p_contact_token', root.getAttribute('data-token'));
      data.append('page_url', window.location.href);

      fetch(window.m4pContactWidgetUrl, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (json) {
          submitBtn.disabled = false;
          if (json && json.success) {
            showSuccess(json.message || 'Dziękujemy, oddzwonimy najszybciej jak to możliwe.');
            if (typeof window.m4pTrack === 'function') {
              window.m4pTrack('contact_request', { channel: channelInput.value });
            }
          } else {
            showError((json && json.message) || 'Nie udało się wysłać zgłoszenia. Spróbuj ponownie.');
          }
        })
        .catch(function () {
          submitBtn.disabled = false;
          showError('Wystąpił błąd połączenia. Spróbuj ponownie.');
        });
    });

    window.setTimeout(showBubble, 1200);
  });
})();
