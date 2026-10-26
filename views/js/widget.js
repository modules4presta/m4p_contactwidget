/**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

(function () {
    'use strict';

    var BUBBLE_KEY = 'm4p_contact_bubble_seen';
    var BUBBLE_DELAY = 4000;

    function remembered(key) {
        try {
            return window.localStorage.getItem(key) === '1';
        } catch (e) {
            return false;
        }
    }

    function remember(key) {
        try {
            window.localStorage.setItem(key, '1');
        } catch (e) {}
    }

    function init() {
        var root = document.getElementById('m4p-contact-widget');
        if (!root) {
            return;
        }

        var toggle = root.querySelector('[data-m4p-contact-toggle]');
        var panel = document.getElementById('m4p-contact-panel');
        var bubble = root.querySelector('[data-m4p-contact-bubble]');
        var form = root.querySelector('[data-m4p-contact-form]');
        var input = form.elements.contact_value;
        var label = root.querySelector('[data-m4p-contact-value-label]');
        var title = root.querySelector('[data-m4p-contact-title]');
        var error = document.getElementById('m4p-contact-error');
        var submit = form.querySelector('[type="submit"]');
        var done = root.querySelector('[data-m4p-contact-done]');
        var token = null;

        // The token comes from the endpoint rather than the markup, so cached pages stay the same for everyone.
        function fetchToken() {
            if (token) {
                return Promise.resolve(token);
            }
            var url = root.getAttribute('data-url');

            return fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'action=token', { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    token = json.token;

                    return token;
                });
        }

        function hideBubble() {
            if (bubble) {
                bubble.hidden = true;
            }
            remember(BUBBLE_KEY);
        }

        function open() {
            hideBubble();
            panel.hidden = false;
            root.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            panel.querySelector('#m4p-contact-title').setAttribute('tabindex', '-1');
            panel.querySelector('#m4p-contact-title').focus();
        }

        function close(returnFocus) {
            panel.hidden = true;
            root.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            if (returnFocus) {
                toggle.focus();
            }
        }

        function showError(message) {
            error.textContent = message;
            error.hidden = false;
            input.setAttribute('aria-invalid', 'true');
        }

        function clearError() {
            error.hidden = true;
            input.removeAttribute('aria-invalid');
        }

        toggle.addEventListener('click', function () {
            if (panel.hidden) {
                open();
            } else {
                close(false);
            }
        });

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-m4p-contact-close]')) {
                close(true);
            } else if (e.target.closest('[data-m4p-contact-bubble-close]')) {
                hideBubble();
            } else if (bubble && e.target.closest('[data-m4p-contact-bubble]')) {
                open();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) {
                close(true);
            }
        });

        document.addEventListener('click', function (e) {
            if (!panel.hidden && !root.contains(e.target)) {
                close(false);
            }
        });

        // Phone or e-mail: the field takes the type, label and placeholder of the chosen option, the heading
        // says whether we call back or reply by e-mail.
        form.addEventListener('change', function (e) {
            if (e.target.name !== 'channel') {
                return;
            }
            var option = e.target;
            input.type = option.getAttribute('data-input-type');
            input.autocomplete = option.getAttribute('data-autocomplete');
            input.inputMode = option.value === 'phone' ? 'tel' : 'email';
            input.placeholder = option.getAttribute('data-placeholder');
            label.textContent = option.getAttribute('data-label');
            if (title) {
                title.textContent = option.getAttribute('data-title');
            }
            input.value = '';
            clearError();
        });

        form.addEventListener('input', clearError);

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var channel = form.elements.channel.value;
            var value = input.value.trim();

            if (channel === 'email' ? !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) : !/^\+?[0-9]{7,15}$/.test(value.replace(/[\s().-]/g, ''))) {
                showError(root.getAttribute(channel === 'email' ? 'data-error-email' : 'data-error-phone'));
                input.focus();

                return;
            }
            if (!form.elements.consent.checked) {
                showError(root.getAttribute('data-error-consent'));
                form.elements.consent.focus();

                return;
            }

            var data = new FormData(form);
            data.append('page_url', window.location.href);
            submit.disabled = true;

            fetchToken()
                .then(function (value) {
                    data.append('m4p_contact_token', value);

                    return fetch(root.getAttribute('data-url'), { method: 'POST', body: data, credentials: 'same-origin' });
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    if (!json.success) {
                        showError(json.message || root.getAttribute('data-error-request'));

                        return;
                    }
                    form.hidden = true;
                    done.querySelector('[data-m4p-contact-message]').textContent = json.message;
                    done.hidden = false;
                    if (typeof window.m4pTrack === 'function') {
                        window.m4pTrack('contact_request', { channel: channel });
                    }
                })
                .catch(function () {
                    showError(root.getAttribute('data-error-request'));
                })
                .finally(function () {
                    submit.disabled = false;
                });
        });

        // The bubble waits for the visitor's first scroll or touch, so it never shows during a Lighthouse run.
        if (bubble && !remembered(BUBBLE_KEY)) {
            window.addEventListener('scroll', function () {
                window.setTimeout(function () {
                    if (panel.hidden && !remembered(BUBBLE_KEY)) {
                        bubble.hidden = false;
                        remember(BUBBLE_KEY);
                    }
                }, BUBBLE_DELAY);
            }, { once: true, passive: true });
        }
    }

    // `once`: some checkout scripts dispatch DOMContentLoaded a second time.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
