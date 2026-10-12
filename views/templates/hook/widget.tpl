{**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div id="m4p-contact-widget" class="m4p-contact-widget" data-token="{$m4pContactToken|escape:'html':'UTF-8'}">
  <div class="m4p-contact-widget__bubble" id="m4pContactBubble" role="status">
    <button type="button" class="m4p-contact-widget__bubble-close" id="m4pContactBubbleClose" aria-label="{l s='Close' d='Modules.M4pcontactwidget.Shop'}">&times;</button>
    <p class="m4p-contact-widget__bubble-title">{l s='Need a hand?' d='Modules.M4pcontactwidget.Shop'}</p>
    <p class="m4p-contact-widget__bubble-text">{l s='Leave your number – we will call you back.' d='Modules.M4pcontactwidget.Shop'}</p>
  </div>

  <button type="button" class="m4p-contact-widget__fab" id="m4pContactFab" aria-expanded="false" aria-controls="m4pContactPanel" aria-label="{l s='Request a callback' d='Modules.M4pcontactwidget.Shop'}">
    {* Inline SVG rather than an icon font: the module carries its own icons and needs nothing from the theme. *}
    <svg class="m4p-contact-widget__fab-icon-open" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path fill="currentColor" d="M12 3c-5.02 0-9 3.36-9 7.5 0 2.3 1.23 4.36 3.17 5.73-.14 1.22-.6 2.3-1.3 3.2-.2.26.03.63.35.56 1.84-.4 3.2-1.12 4.1-1.76.85.18 1.75.27 2.68.27 5.02 0 9-3.36 9-7.5S17.02 3 12 3z"/>
    </svg>
    <svg class="m4p-contact-widget__fab-icon-close" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path fill="currentColor" d="M6.4 5 5 6.4 10.6 12 5 17.6 6.4 19 12 13.4 17.6 19 19 17.6 13.4 12 19 6.4 17.6 5 12 10.6z"/>
    </svg>
  </button>

  <div class="m4p-contact-widget__panel" id="m4pContactPanel" role="dialog" aria-modal="false" aria-labelledby="m4pContactPanelTitle" hidden>
    <div class="m4p-contact-widget__panel-header">
      <span id="m4pContactPanelTitle">{l s='Request a callback' d='Modules.M4pcontactwidget.Shop'}</span>
      <button type="button" class="m4p-contact-widget__panel-close" id="m4pContactPanelClose" aria-label="{l s='Close' d='Modules.M4pcontactwidget.Shop'}">&times;</button>
    </div>

    <form id="m4pContactForm" class="m4p-contact-widget__form" novalidate>
      <p class="m4p-contact-widget__form-intro">{l s='Leave a phone number or an e-mail address and we will get back to you as soon as we can.' d='Modules.M4pcontactwidget.Shop'}</p>

      <div class="m4p-contact-widget__tabs" role="tablist">
        <button type="button" class="m4p-contact-widget__tab is-active" role="tab" aria-selected="true" data-channel="phone">{l s='Phone' d='Modules.M4pcontactwidget.Shop'}</button>
        <button type="button" class="m4p-contact-widget__tab" role="tab" aria-selected="false" data-channel="email">{l s='E-mail' d='Modules.M4pcontactwidget.Shop'}</button>
      </div>

      <input type="hidden" name="channel" id="m4pContactChannel" value="phone">
      {* Honeypot: hidden from real users via CSS, bots that autofill every field trip this. *}
      <input type="text" name="m4p_hp" id="m4pContactHp" class="m4p-contact-widget__hp" tabindex="-1" autocomplete="off" aria-hidden="true">

      <input
        type="text"
        inputmode="tel"
        name="contact_value"
        id="m4pContactValue"
        class="m4p-contact-widget__input"
        placeholder="{l s='e.g. +48 123 456 789' d='Modules.M4pcontactwidget.Shop'}"
        autocomplete="tel"
        required
      >

      <label class="m4p-contact-widget__consent">
        <input type="checkbox" name="consent" id="m4pContactConsent" required>
        <span>
          {l s='I agree to my personal data being processed so that you can contact me back.' d='Modules.M4pcontactwidget.Shop'}
          {if $m4pPrivacyUrl}
            <a href="{$m4pPrivacyUrl|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{l s='Privacy policy' d='Modules.M4pcontactwidget.Shop'}</a>
          {/if}
        </span>
      </label>

      <p class="m4p-contact-widget__error" id="m4pContactError" role="alert" hidden></p>
      <p class="m4p-contact-widget__success" id="m4pContactSuccess" role="status" hidden></p>

      <button type="submit" class="m4p-contact-widget__submit" id="m4pContactSubmit">
        {l s='Request a callback' d='Modules.M4pcontactwidget.Shop'}
      </button>
    </form>
  </div>
</div>
