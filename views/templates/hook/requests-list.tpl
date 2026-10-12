{**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="panel">
  <div class="panel-heading">
    <i class="icon-list"></i> {l s='Latest requests' d='Modules.M4pcontactwidget.Admin'}
    <span class="badge">{$m4pRequests|@count}</span>
  </div>

  {if $m4pRequests|@count === 0}
    <p style="padding: 15px;">{l s='No requests yet.' d='Modules.M4pcontactwidget.Admin'}</p>
  {else}
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Date' d='Modules.M4pcontactwidget.Admin'}</th>
          <th>{l s='Channel' d='Modules.M4pcontactwidget.Admin'}</th>
          <th>{l s='Contact' d='Modules.M4pcontactwidget.Admin'}</th>
          <th>{l s='Consent' d='Modules.M4pcontactwidget.Admin'}</th>
          <th>{l s='Page' d='Modules.M4pcontactwidget.Admin'}</th>
          <th>{l s='IP address' d='Modules.M4pcontactwidget.Admin'}</th>
          {if $m4pMultistore}
            <th>{l s='Shop' d='Modules.M4pcontactwidget.Admin'}</th>
          {/if}
          <th class="text-right">{l s='Actions' d='Modules.M4pcontactwidget.Admin'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$m4pRequests item=row}
          <tr>
            <td>{$row.date_add|escape:'html':'UTF-8'}</td>
            <td>{if $row.channel === 'phone'}{l s='Phone' d='Modules.M4pcontactwidget.Admin'}{else}{l s='E-mail' d='Modules.M4pcontactwidget.Admin'}{/if}</td>
            <td><strong>{$row.contact_value|escape:'html':'UTF-8'}</strong></td>
            <td>{if $row.consent}<span class="label label-success">{l s='Yes' d='Modules.M4pcontactwidget.Admin'}</span>{else}<span class="label label-danger">{l s='No' d='Modules.M4pcontactwidget.Admin'}</span>{/if}</td>
            <td>
              {if $row.page_url}
                <a href="{$row.page_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener noreferrer">{$row.page_url|truncate:40:'…'|escape:'html':'UTF-8'}</a>
              {else}
                &ndash;
              {/if}
            </td>
            <td>{$row.ip_address|escape:'html':'UTF-8'}</td>
            {if $m4pMultistore}
              <td>{$row.shop_name|escape:'html':'UTF-8'}</td>
            {/if}
            <td class="text-right">
              <a class="btn btn-default btn-sm" href="{$row.url_delete|escape:'html':'UTF-8'}"
                 onclick="return confirm('{l s='Delete this submission?' d='Modules.M4pcontactwidget.Admin' js=1}');">
                <i class="icon-trash"></i> {l s='Delete' d='Modules.M4pcontactwidget.Admin'}
              </a>
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {/if}
</div>
