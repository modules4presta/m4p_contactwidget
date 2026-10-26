{**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}
{strip}
<div class="m4p-contact-widget" id="m4p-contact-widget" data-url="{$m4p_contact.submit_url|escape:'html':'UTF-8'}" data-error-phone="{l s='Enter a valid phone number.' d='Modules.M4pcontactwidget.Shop'}" data-error-email="{l s='Enter a valid e-mail address.' d='Modules.M4pcontactwidget.Shop'}" data-error-consent="{l s='Tick the consent to the processing of your data.' d='Modules.M4pcontactwidget.Shop'}" data-error-request="{l s='Connection error. Please try again.' d='Modules.M4pcontactwidget.Shop'}">

  {if $m4p_contact.bubble}
    <div class="m4p-contact-widget__bubble" data-m4p-contact-bubble hidden>
      <p class="m4p-contact-widget__bubble-text">{$m4p_contact.bubble|escape:'html':'UTF-8'}</p>
      <button type="button" class="m4p-contact-widget__bubble-close" data-m4p-contact-bubble-close title="{l s='Close' d='Modules.M4pcontactwidget.Shop'}" aria-label="{l s='Close' d='Modules.M4pcontactwidget.Shop'}">{$m4p_contact.icons.xmark nofilter}</button>
    </div>
  {/if}

  <button type="button" class="m4p-contact-widget__fab" data-m4p-contact-toggle aria-expanded="false" aria-controls="m4p-contact-panel" title="{l s='Contact us' d='Modules.M4pcontactwidget.Shop'}" aria-label="{l s='Contact us' d='Modules.M4pcontactwidget.Shop'}">
    <span class="m4p-contact-widget__fab-icon m4p-contact-widget__fab-icon--open">{$m4p_contact.icons['comment-dots'] nofilter}</span>
    <span class="m4p-contact-widget__fab-icon m4p-contact-widget__fab-icon--close">{$m4p_contact.icons.xmark nofilter}</span>
  </button>

  <section class="m4p-contact-widget__panel" id="m4p-contact-panel" role="dialog" aria-modal="false" aria-labelledby="m4p-contact-title" hidden>
    <div class="m4p-contact-widget__head">
      <div class="h2 m4p-contact-widget__title" id="m4p-contact-title">{$m4p_contact.title|escape:'html':'UTF-8'}</div>
      <button type="button" class="m4p-contact-widget__close" data-m4p-contact-close title="{l s='Close' d='Modules.M4pcontactwidget.Shop'}" aria-label="{l s='Close' d='Modules.M4pcontactwidget.Shop'}">{$m4p_contact.icons.xmark nofilter}</button>
    </div>

    {if $m4p_contact.intro}
      <p class="m4p-contact-widget__intro">{$m4p_contact.intro|escape:'html':'UTF-8'}</p>
    {/if}

    {if $m4p_contact.phone || $m4p_contact.email || $m4p_contact.hours}
      <ul class="m4p-contact-widget__channels">
        {if $m4p_contact.phone}
          <li>
            <a class="m4p-contact-widget__channel" href="tel:{$m4p_contact.phone_href|escape:'html':'UTF-8'}">
              <span class="m4p-contact-widget__channel-icon">{$m4p_contact.icons.phone nofilter}</span>
              <span class="m4p-contact-widget__channel-body">
                <span class="m4p-contact-widget__channel-label">{l s='Phone' d='Modules.M4pcontactwidget.Shop'}</span>
                <span class="m4p-contact-widget__channel-value">{$m4p_contact.phone|escape:'html':'UTF-8'}</span>
              </span>
            </a>
          </li>
        {/if}
        {if $m4p_contact.email}
          <li>
            <a class="m4p-contact-widget__channel" href="mailto:{$m4p_contact.email|escape:'html':'UTF-8'}">
              <span class="m4p-contact-widget__channel-icon">{$m4p_contact.icons.envelope nofilter}</span>
              <span class="m4p-contact-widget__channel-body">
                <span class="m4p-contact-widget__channel-label">{l s='E-mail' d='Modules.M4pcontactwidget.Shop'}</span>
                <span class="m4p-contact-widget__channel-value">{$m4p_contact.email|escape:'html':'UTF-8'}</span>
              </span>
            </a>
          </li>
        {/if}
        {if $m4p_contact.hours}
          <li>
            <div class="m4p-contact-widget__channel">
              <span class="m4p-contact-widget__channel-icon">{$m4p_contact.icons.clock nofilter}</span>
              <span class="m4p-contact-widget__channel-body">
                <span class="m4p-contact-widget__channel-label">{l s='Opening hours' d='Modules.M4pcontactwidget.Shop'}</span>
                <span class="m4p-contact-widget__channel-value">{$m4p_contact.hours|escape:'html':'UTF-8'}</span>
              </span>
            </div>
          </li>
        {/if}
      </ul>
    {/if}

    <form class="m4p-contact-widget__form" data-m4p-contact-form novalidate>
      <p class="m4p-contact-widget__form-title" data-m4p-contact-title>{l s='We will call you back' d='Modules.M4pcontactwidget.Shop'}</p>

      <div class="m4p-contact-widget__switch" role="radiogroup" aria-label="{l s='How should we contact you?' d='Modules.M4pcontactwidget.Shop'}">
        <label class="m4p-contact-widget__switch-option">
          <input type="radio" name="channel" value="phone" checked data-title="{l s='We will call you back' d='Modules.M4pcontactwidget.Shop'}" data-input-type="tel" data-autocomplete="tel" data-label="{l s='Your phone number' d='Modules.M4pcontactwidget.Shop'}" data-placeholder="{l s='e.g. 600 100 200' d='Modules.M4pcontactwidget.Shop'}">
          <span>{l s='Phone' d='Modules.M4pcontactwidget.Shop'}</span>
        </label>
        <label class="m4p-contact-widget__switch-option">
          <input type="radio" name="channel" value="email" data-title="{l s='We will write back to you' d='Modules.M4pcontactwidget.Shop'}" data-input-type="email" data-autocomplete="email" data-label="{l s='Your e-mail address' d='Modules.M4pcontactwidget.Shop'}" data-placeholder="{l s='e.g. name@company.com' d='Modules.M4pcontactwidget.Shop'}">
          <span>{l s='E-mail' d='Modules.M4pcontactwidget.Shop'}</span>
        </label>
      </div>

      {* Honeypot: hidden from people, filled by bots that complete every field. *}
      <input type="text" name="m4p_hp" class="m4p-contact-widget__hp" tabindex="-1" autocomplete="off" aria-hidden="true">

      <label class="m4p-contact-widget__field-label" for="m4p-contact-value" data-m4p-contact-value-label>{l s='Your phone number' d='Modules.M4pcontactwidget.Shop'}</label>
      <input class="m4p-contact-widget__input" id="m4p-contact-value" type="tel" inputmode="tel" name="contact_value" autocomplete="tel" placeholder="{l s='e.g. 600 100 200' d='Modules.M4pcontactwidget.Shop'}" aria-describedby="m4p-contact-error" required>

      <label class="m4p-contact-widget__consent">
        <input type="checkbox" name="consent" value="1" required>
        <span>
          {l s='I agree to the processing of my personal data in order to be contacted.' d='Modules.M4pcontactwidget.Shop'}
          {if $m4p_contact.privacy_url}
            {' '}<a href="{$m4p_contact.privacy_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{l s='Privacy policy' d='Modules.M4pcontactwidget.Shop'}</a>
          {/if}
        </span>
      </label>

      <p class="m4p-contact-widget__error" id="m4p-contact-error" role="alert" hidden></p>

      <button type="submit" class="m4p-contact-widget__submit">
        {l s='Call me back' d='Modules.M4pcontactwidget.Shop'}
        {$m4p_contact.icons['paper-plane'] nofilter}
      </button>
    </form>

    <div class="m4p-contact-widget__done" data-m4p-contact-done hidden>
      <span class="m4p-contact-widget__done-icon">{$m4p_contact.icons['circle-check'] nofilter}</span>
      <p class="m4p-contact-widget__done-text" role="status" data-m4p-contact-message></p>
    </div>
  </section>
</div>
{/strip}
