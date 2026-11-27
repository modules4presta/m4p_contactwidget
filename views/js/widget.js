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
    var BUBBLE_DELAY = 1500;
    var STATUS_EVERY = 60000;
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]):not([tabindex="-1"]), [tabindex]:not([tabindex="-1"])';

    // Y-m-d of a moment in the shop's time zone, so "today" and "tomorrow" follow the shop's clock.
    function shopDate(timezone, time) {
        try {
            return new Intl.DateTimeFormat('en-CA', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(time);
        } catch (e) {
            return new Date(time).toISOString().slice(0, 10);
        }
    }

    // The same rules as the server render: inside a period the desk is open until its end, otherwise the
    // next period tells when it opens.
    function scheduleStatus(schedule, now) {
        var labels = schedule.labels;
        var seconds = Math.floor(now / 1000);
        var today = shopDate(schedule.timezone, now);
        var tomorrow = shopDate(schedule.timezone, now + 86400000);
        var fill = function (text, period, time) {
            return text.replace('%time%', time).replace('%day%', period.day);
        };
        for (var i = 0; i < schedule.periods.length; i++) {
            var period = schedule.periods[i];
            if (seconds >= period.end) {
                continue;
            }
            if (seconds >= period.start) {
                return { open: true, button: fill(labels.button_open, period, period.to), badge: fill(labels.badge_open, period, period.to) };
            }
            var when = period.date === today ? 'today' : (period.date === tomorrow ? 'tomorrow' : 'later');

            return { open: false, button: fill(labels['button_' + when], period, period.from), badge: fill(labels['badge_' + when], period, period.from) };
        }

        return { open: false, button: '', badge: labels.closed };
    }

    // The bubble comes back in a new session; within one it shows once.
    function seenThisSession(key) {
        try {
            return window.sessionStorage.getItem(key) === '1';
        } catch (e) {
            return false;
        }
    }

    function markSeenThisSession(key) {
        try {
            window.sessionStorage.setItem(key, '1');
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
        var input = form ? form.elements.contact_value : null;
        var label = root.querySelector('[data-m4p-contact-value-label]');
        var title = root.querySelector('[data-m4p-contact-title]');
        var errors = {
            value: document.getElementById('m4p-contact-value-error'),
            consent: document.getElementById('m4p-contact-consent-error'),
            form: document.getElementById('m4p-contact-error'),
        };
        var submit = form ? form.querySelector('[type="submit"]') : null;
        var done = root.querySelector('[data-m4p-contact-done]');
        var submitLabel = root.querySelector('[data-m4p-contact-submit]');
        var badge = root.querySelector('[data-m4p-contact-badge]');
        var status = root.querySelector('[data-m4p-contact-status]');
        var note = root.querySelector('[data-m4p-contact-note]');
        var token = null;
        var schedule = null;

        try {
            schedule = JSON.parse(root.getAttribute('data-schedule') || 'null');
        } catch (e) {
            schedule = null;
        }

        function refreshStatus() {
            if (!schedule) {
                return;
            }
            var current = scheduleStatus(schedule, Date.now());
            root.classList.toggle('is-available', current.open);
            if (badge) {
                badge.textContent = current.badge;
                badge.classList.toggle('is-open', current.open);
            }
            if (status) {
                status.textContent = current.button;
                status.hidden = current.button === '';
            }
            toggle.setAttribute('aria-label', toggle.getAttribute('data-label') + (current.button ? ', ' + current.button : ''));
            if (note) {
                note.textContent = note.getAttribute(current.open ? 'data-open' : 'data-closed');
            }
        }

        refreshStatus();
        window.setInterval(refreshStatus, STATUS_EVERY);

        // The button steps aside (hides) while it would cover one of the listed elements, e.g. slider controls.
        var avoid = root.getAttribute('data-avoid');
        var avoidFrame = 0;
        function stepAside() {
            avoidFrame = 0;
            if (!panel.hidden) {
                root.classList.remove('is-yielding');

                return;
            }
            var own = toggle.getBoundingClientRect();
            var covered = false;
            try {
                covered = Array.prototype.some.call(document.querySelectorAll(avoid), function (element) {
                    var box = element.getBoundingClientRect();

                    return box.width > 0 && box.height > 0 && box.left < own.right && box.right > own.left && box.top < own.bottom && box.bottom > own.top;
                });
            } catch (e) {
                avoid = null;
            }
            root.classList.toggle('is-yielding', covered);
        }
        function scheduleStepAside() {
            if (avoid && !avoidFrame) {
                avoidFrame = window.requestAnimationFrame(stepAside);
            }
        }
        if (avoid) {
            window.addEventListener('scroll', scheduleStepAside, { passive: true });
            window.addEventListener('resize', scheduleStepAside);
            window.setInterval(scheduleStepAside, 1000);
            scheduleStepAside();
        }

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
            markSeenThisSession(BUBBLE_KEY);
        }

        function open() {
            if (!root.getAttribute('data-cta-trigger')) {
                root.setAttribute('data-cta-trigger', 'click');
            }
            hideBubble();
            refreshStatus();
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

        // where: 'value' or 'consent' for a field error under that control, 'form' for a message under the form.
        function showError(message, where) {
            clearError();
            var box = errors[where];
            box.querySelector('[data-m4p-contact-error-text]').textContent = message;
            box.hidden = false;
            if (where === 'value') {
                input.setAttribute('aria-invalid', 'true');
            } else if (where === 'consent') {
                form.elements.consent.setAttribute('aria-invalid', 'true');
            }
        }

        function clearError() {
            Object.keys(errors).forEach(function (key) {
                errors[key].hidden = true;
            });
            input.removeAttribute('aria-invalid');
            form.elements.consent.removeAttribute('aria-invalid');
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
            if (panel.hidden) {
                return;
            }
            if (e.key === 'Escape') {
                close(true);

                return;
            }
            // Focus stays in the panel and its toggle while the panel is open.
            if (e.key === 'Tab') {
                var items = Array.prototype.filter.call(panel.querySelectorAll(FOCUSABLE), function (item) {
                    return item.offsetParent !== null;
                }).concat(window.getComputedStyle(toggle).visibility === 'hidden' ? [] : [toggle]);
                var first = items[0];
                var last = items[items.length - 1];
                var inside = items.indexOf(document.activeElement) !== -1;
                if (e.shiftKey && (document.activeElement === first || !inside)) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && (document.activeElement === last || !inside)) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        document.addEventListener('click', function (e) {
            if (!panel.hidden && !root.contains(e.target)) {
                close(false);
            }
        });

        if (form) {
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
                if (submitLabel) {
                    submitLabel.textContent = option.getAttribute('data-submit');
                }
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
                    showError(root.getAttribute(channel === 'email' ? 'data-error-email' : 'data-error-phone'), 'value');
                    input.focus();

                    return;
                }
                if (!form.elements.consent.checked) {
                    showError(root.getAttribute('data-error-consent'), 'consent');
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
                            showError(json.message || root.getAttribute('data-error-request'), 'form');

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
                        showError(root.getAttribute('data-error-request'), 'form');
                    })
                    .finally(function () {
                        submit.disabled = false;
                    });
            });
        }

        // The bubble waits until the visitor has scrolled past the first screen, so it neither covers the page
        // opening nor shows during a Lighthouse run. data-cta-trigger tells an analytics script why the widget is
        // on screen.
        if (bubble && !seenThisSession(BUBBLE_KEY)) {
            var onScroll = function () {
                if (window.scrollY < window.innerHeight) {
                    return;
                }
                window.removeEventListener('scroll', onScroll);
                window.setTimeout(function () {
                    if (panel.hidden && !seenThisSession(BUBBLE_KEY)) {
                        bubble.hidden = false;
                        root.setAttribute('data-cta-trigger', 'scroll');
                        markSeenThisSession(BUBBLE_KEY);
                    }
                }, BUBBLE_DELAY);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
        } else {
            root.setAttribute('data-cta-trigger', 'static');
        }
    }

    // `once`: some checkout scripts dispatch DOMContentLoaded a second time.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
