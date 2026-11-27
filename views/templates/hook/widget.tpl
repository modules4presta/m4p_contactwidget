{**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}
{strip}
{$m4pSchedule = $m4p_contact.schedule}
<div class="m4p-contact-widget{if $m4pSchedule} has-status{if $m4pSchedule.status.open} is-available{/if}{/if}" id="m4p-contact-widget" data-cta-placement="widget" data-cta-kind="widget" data-url="{$m4p_contact.submit_url|escape:'html':'UTF-8'}" data-error-phone="{l s='Enter a valid phone number.' d='Modules.M4pcontactwidget.Shop'}" data-error-email="{l s='Enter a valid e-mail address.' d='Modules.M4pcontactwidget.Shop'}" data-error-consent="{l s='Tick the consent to the processing of your data.' d='Modules.M4pcontactwidget.Shop'}" data-error-request="{l s='Connection error. Please try again.' d='Modules.M4pcontactwidget.Shop'}"{if $m4pSchedule} data-schedule="{$m4pSchedule.script|json_encode|escape:'html':'UTF-8'}"{/if}{if $m4p_contact.avoid} data-avoid="{$m4p_contact.avoid|escape:'html':'UTF-8'}"{/if}{if $m4p_contact.offset_mobile} style="--m4p-contact-offset-mobile: {$m4p_contact.offset_mobile|intval}px"{/if}>

  {if $m4p_contact.bubble}
    <div class="m4p-contact-widget__bubble" data-m4p-contact-bubble hidden>
      <p class="m4p-contact-widget__bubble-text">{$m4p_contact.bubble|escape:'html':'UTF-8'}</p>
      <button type="button" class="m4p-contact-widget__bubble-close" data-m4p-contact-bubble-close data-cta-dismiss title="{l s='Close' d='Modules.M4pcontactwidget.Shop'}" aria-label="{l s='Close' d='Modules.M4pcontactwidget.Shop'}">{$m4p_contact.icons.xmark nofilter}</button>
    </div>
  {/if}

  <section class="m4p-contact-widget__panel" id="m4p-contact-panel" role="dialog" aria-modal="true" aria-labelledby="m4p-contact-title" hidden>
    <div class="m4p-contact-widget__head">
      <div class="m4p-contact-widget__heading">
        <div class="h2 m4p-contact-widget__title" id="m4p-contact-title">{$m4p_contact.title|escape:'html':'UTF-8'}</div>
        {if $m4pSchedule}
          <p class="m4p-contact-widget__badge{if $m4pSchedule.status.open} is-open{/if}" data-m4p-contact-badge>{$m4pSchedule.status.badge|escape:'html':'UTF-8'}</p>
        {/if}
      </div>
      <button type="button" class="m4p-contact-widget__close" data-m4p-contact-close data-cta-dismiss title="{l s='Close' d='Modules.M4pcontactwidget.Shop'}" aria-label="{l s='Close' d='Modules.M4pcontactwidget.Shop'}">{$m4p_contact.icons.xmark nofilter}</button>
    </div>

    {if $m4p_contact.intro}
      <p class="m4p-contact-widget__intro">{$m4p_contact.intro|escape:'html':'UTF-8'}</p>
    {/if}

    {if $m4p_contact.phone || $m4p_contact.email || (!$m4pSchedule && $m4p_contact.hours)}
      <ul class="m4p-contact-widget__channels">
        {if $m4p_contact.phone}
          <li>
            <a class="m4p-contact-widget__channel m4p-contact-widget__channel--phone" data-cta-channel="phone" href="tel:{$m4p_contact.phone_href|escape:'html':'UTF-8'}">
              <span class="m4p-contact-widget__channel-icon">{$m4p_contact.icons.phone nofilter}</span>
              <span class="m4p-contact-widget__channel-body">
                <span class="m4p-contact-widget__channel-label">{l s='Phone' d='Modules.M4pcontactwidget.Shop'}</span>
                <span class="m4p-contact-widget__channel-value">{$m4p_contact.phone|escape:'html':'UTF-8'}</span>
              </span>
              <span class="m4p-contact-widget__channel-go">{$m4p_contact.icons['chevron-right'] nofilter}</span>
            </a>
          </li>
        {/if}
        {if $m4p_contact.email}
          <li>
            <a class="m4p-contact-widget__channel" data-cta-channel="email" href="mailto:{$m4p_contact.email|escape:'html':'UTF-8'}">
              <span class="m4p-contact-widget__channel-icon">{$m4p_contact.icons.envelope nofilter}</span>
              <span class="m4p-contact-widget__channel-body">
                <span class="m4p-contact-widget__channel-label">{l s='E-mail' d='Modules.M4pcontactwidget.Shop'}</span>
                <span class="m4p-contact-widget__channel-value">{$m4p_contact.email|escape:'html':'UTF-8'}</span>
              </span>
              <span class="m4p-contact-widget__channel-go">{$m4p_contact.icons['chevron-right'] nofilter}</span>
            </a>
          </li>
        {/if}
        {if !$m4pSchedule && $m4p_contact.hours}
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

    {if $m4pSchedule}
      <dl class="m4p-contact-widget__hours" aria-label="{l s='Opening hours' d='Modules.M4pcontactwidget.Shop'}">
        {foreach $m4pSchedule.hours as $row}
          <div class="m4p-contact-widget__hours-row{if $row.today} is-today{/if}">
            <dt>{$row.days|escape:'html':'UTF-8'}</dt>
            <dd>{if $row.hours}{$row.hours|escape:'html':'UTF-8'}{else}{l s='closed' d='Modules.M4pcontactwidget.Shop'}{/if}</dd>
          </div>
        {/foreach}
        {foreach $m4pSchedule.exceptions as $row}
          <div class="m4p-contact-widget__hours-row is-exception{if $row.today} is-today{/if}">
            <dt>{$row.days|escape:'html':'UTF-8'}</dt>
            <dd>{if $row.hours}{$row.hours|escape:'html':'UTF-8'}{else}{l s='closed' d='Modules.M4pcontactwidget.Shop'}{/if}</dd>
          </div>
        {/foreach}
      </dl>
    {/if}

    {if $m4p_contact.form_channels}
    {$m4pFirstPhone = $m4p_contact.form_channels[0] == 'phone'}
    <form class="m4p-contact-widget__form" data-m4p-contact-form novalidate>
      <p class="m4p-contact-widget__form-title">
        <span data-m4p-contact-title>{if $m4pFirstPhone}{$m4p_contact.form_title_phone|escape:'html':'UTF-8'}{else}{$m4p_contact.form_title_email|escape:'html':'UTF-8'}{/if}</span>
        {if $m4p_contact.callback_open || $m4p_contact.callback_closed}
          <small class="m4p-contact-widget__form-note" data-m4p-contact-note data-open="{$m4p_contact.callback_open|escape:'html':'UTF-8'}" data-closed="{$m4p_contact.callback_closed|escape:'html':'UTF-8'}">{if !$m4pSchedule || $m4pSchedule.status.open}{$m4p_contact.callback_open|escape:'html':'UTF-8'}{else}{$m4p_contact.callback_closed|escape:'html':'UTF-8'}{/if}</small>
        {/if}
      </p>

      <div class="m4p-contact-widget__switch" role="radiogroup" aria-label="{l s='How should we contact you?' d='Modules.M4pcontactwidget.Shop'}"{if $m4p_contact.form_channels|count < 2} hidden{/if}>
        {foreach $m4p_contact.form_channels as $m4pChannel}
          {if $m4pChannel == 'phone'}
            <label class="m4p-contact-widget__switch-option">
              <input type="radio" name="channel" value="phone"{if $m4pChannel@first} checked{/if} data-title="{$m4p_contact.form_title_phone|escape:'html':'UTF-8'}" data-submit="{$m4p_contact.submit_phone|escape:'html':'UTF-8'}" data-input-type="tel" data-autocomplete="tel" data-label="{l s='Your phone number' d='Modules.M4pcontactwidget.Shop'}" data-placeholder="{l s='e.g. 600 100 200' d='Modules.M4pcontactwidget.Shop'}">
              <span>{l s='Phone' d='Modules.M4pcontactwidget.Shop'}</span>
            </label>
          {else}
            <label class="m4p-contact-widget__switch-option">
              <input type="radio" name="channel" value="email"{if $m4pChannel@first} checked{/if} data-title="{$m4p_contact.form_title_email|escape:'html':'UTF-8'}" data-submit="{$m4p_contact.submit_email|escape:'html':'UTF-8'}" data-input-type="email" data-autocomplete="email" data-label="{l s='Your e-mail address' d='Modules.M4pcontactwidget.Shop'}" data-placeholder="{l s='e.g. name@company.com' d='Modules.M4pcontactwidget.Shop'}">
              <span>{l s='E-mail' d='Modules.M4pcontactwidget.Shop'}</span>
            </label>
          {/if}
        {/foreach}
      </div>

      {* Honeypot: hidden from people, filled by bots that complete every field. *}
      <input type="text" name="m4p_hp" class="m4p-contact-widget__hp" tabindex="-1" autocomplete="off" aria-hidden="true">

      {if $m4pFirstPhone}
        <label class="m4p-contact-widget__field-label" for="m4p-contact-value" data-m4p-contact-value-label>{l s='Your phone number' d='Modules.M4pcontactwidget.Shop'}</label>
      {else}
        <label class="m4p-contact-widget__field-label" for="m4p-contact-value" data-m4p-contact-value-label>{l s='Your e-mail address' d='Modules.M4pcontactwidget.Shop'}</label>
      {/if}
      <div class="m4p-contact-widget__row">
        {if $m4pFirstPhone}
          <input class="m4p-contact-widget__input form-control" id="m4p-contact-value" type="tel" inputmode="tel" name="contact_value" autocomplete="tel" placeholder="{l s='e.g. 600 100 200' d='Modules.M4pcontactwidget.Shop'}" aria-describedby="m4p-contact-value-error" required>
        {else}
          <input class="m4p-contact-widget__input form-control" id="m4p-contact-value" type="email" inputmode="email" name="contact_value" autocomplete="email" placeholder="{l s='e.g. name@company.com' d='Modules.M4pcontactwidget.Shop'}" aria-describedby="m4p-contact-value-error" required>
        {/if}
        <button type="submit" class="m4p-contact-widget__submit btn btn-primary">
          {$m4p_contact.icons['paper-plane'] nofilter}
          <span data-m4p-contact-submit>{if $m4pFirstPhone}{$m4p_contact.submit_phone|escape:'html':'UTF-8'}{else}{$m4p_contact.submit_email|escape:'html':'UTF-8'}{/if}</span>
        </button>
      </div>
      <div class="m4p-field-error m4p-contact-widget__field-error" id="m4p-contact-value-error" hidden>{$m4p_contact.icons['circle-alert'] nofilter}<span data-m4p-contact-error-text></span></div>

      <label class="m4p-contact-widget__consent">
        <input type="checkbox" name="consent" value="1" aria-describedby="m4p-contact-consent-error" required>
        <span>
          {$m4p_contact.consent|escape:'html':'UTF-8'}
          {if $m4p_contact.privacy_url && $m4p_contact.privacy_label}
            {' '}<a href="{$m4p_contact.privacy_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$m4p_contact.privacy_label|escape:'html':'UTF-8'}</a>
          {/if}
        </span>
      </label>

      <div class="m4p-field-error m4p-contact-widget__field-error" id="m4p-contact-consent-error" hidden>{$m4p_contact.icons['circle-alert'] nofilter}<span data-m4p-contact-error-text></span></div>

      {* Classes m4p-msg, m4p-field-error and their variants come from the theme's message component when it has one. *}
      <div class="m4p-msg m4p-msg--danger m4p-contact-widget__msg" id="m4p-contact-error" role="alert" hidden>{$m4p_contact.icons['circle-alert'] nofilter}<p class="m4p-msg__body" data-m4p-contact-error-text></p></div>
    </form>
    {/if}

    <div class="m4p-contact-widget__done" data-m4p-contact-done hidden>
      <div class="m4p-msg m4p-msg--success m4p-contact-widget__msg" role="status">{$m4p_contact.icons['circle-check'] nofilter}<p class="m4p-msg__body" data-m4p-contact-message></p></div>
    </div>
  </section>

  <button type="button" class="m4p-contact-widget__fab" data-m4p-contact-toggle aria-expanded="false" aria-controls="m4p-contact-panel" data-label="{$m4p_contact.label|escape:'html':'UTF-8'}" aria-label="{$m4p_contact.label|escape:'html':'UTF-8'}{if $m4pSchedule && $m4pSchedule.status.button && $m4p_contact.fab_status}, {$m4pSchedule.status.button|escape:'html':'UTF-8'}{/if}">
    {if $m4pSchedule && $m4p_contact.fab_status}<span class="m4p-contact-widget__dot" aria-hidden="true"></span>{/if}
    <span class="m4p-contact-widget__fab-icon m4p-contact-widget__fab-icon--open">{$m4p_contact.icons.phone nofilter}</span>
    <span class="m4p-contact-widget__fab-icon m4p-contact-widget__fab-icon--close">{$m4p_contact.icons.xmark nofilter}</span>
    <span class="m4p-contact-widget__fab-label" aria-hidden="true">{$m4p_contact.label|escape:'html':'UTF-8'}</span>
    {if $m4pSchedule && $m4pSchedule.status.button && $m4p_contact.fab_status}
      <span class="m4p-contact-widget__fab-status" aria-hidden="true" data-m4p-contact-status>{$m4pSchedule.status.button|escape:'html':'UTF-8'}</span>
    {/if}
  </button>
</div>
{/strip}
