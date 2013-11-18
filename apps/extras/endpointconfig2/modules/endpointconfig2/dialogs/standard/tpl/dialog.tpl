<script type="text/x-handlebars" data-template-name="endpointconfig-standard">
    <ul>
        <li><a href="#endpointconfig-standard-information">{$DIALOG_STANDARD_TITLE_INFORMATION}</a></li>
        <li><a href="#endpointconfig-standard-accounts">{$DIALOG_STANDARD_TITLE_ACCOUNTS}</a></li>
        <li><a href="#endpointconfig-standard-network">{$DIALOG_STANDARD_TITLE_NETWORK}</a></li>
        <li><a href="#endpointconfig-standard-credentials">{$DIALOG_STANDARD_TITLE_CREDENTIALS}</a></li>
        <li><a href="#endpointconfig-standard-endpointproperties">{$DIALOG_STANDARD_TITLE_PROPERTIES}</a></li>
    </ul>
    <div id="endpointconfig-standard-information">
        <table border="0">
            <tbody>
                <tr><td><b>{$DIALOG_STANDARD_LBL_MANUFACTURER}:</b></td><td>{literal}{{name_manufacturer}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_LBL_MODEL}:</b></td><td>{literal}{{#if modelObj.name_model}}{{modelObj.name_model}} ({{modelObj.description}}){{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN_MODEL}{literal}{{/if}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_LBL_MAX_SIP_ACCOUNTS}:</b></td><td>{literal}{{#if details}}{{details.max_sip_accounts}}{{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN}{literal}{{/if}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_LBL_MAX_IAX2_ACCOUNTS}:</b></td><td>{literal}{{#if details}}{{details.max_iax2_accounts}}{{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN}{literal}{{/if}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_LBL_MAC}:</b></td><td>{literal}{{mac_address}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_LBL_CURRENT_IP}:</b></td><td>{literal}{{#if last_known_ipv4 }}{{last_known_ipv4}}{{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN}{literal}{{/if}}{/literal}</td></tr>
                <tr title="{$DIALOG_STANDARD_TOOLTIP_DYNIP}"><td><b>{$DIALOG_STANDARD_LBL_DYNIP}:</b></td><td>{literal}{{#if modelObj}}{{#if modelObj.dynamic_ip_supported}}{/literal}{$DIALOG_STANDARD_LBL_YES}{literal}{{else}}{/literal}{$DIALOG_STANDARD_LBL_NO}{literal}{{/if}}{{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN}{literal}{{/if}}{/literal}</td></tr>
                <tr title="{$DIALOG_STANDARD_TOOLTIP_STATICIP}"><td><b>{$DIALOG_STANDARD_LBL_STATICIP}:</b></td><td>{literal}{{#if modelObj}}{{#if modelObj.static_ip_supported}}{/literal}{$DIALOG_STANDARD_LBL_YES}{literal}{{else}}{/literal}{$DIALOG_STANDARD_LBL_NO}{literal}{{/if}}{{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN}{literal}{{/if}}{/literal}</td></tr>
                <tr title="{$DIALOG_STANDARD_TOOLTIP_VLAN}"><td><b>{$DIALOG_STANDARD_LBL_VLAN}:</b></td><td>(unimplemented in DB)</td></tr>
                <tr title="{$DIALOG_STANDARD_TOOLTIP_STATICPROV}"><td><b>{$DIALOG_STANDARD_LBL_STATICPROV}:</b></td><td>{literal}{{#if modelObj}}{{#if modelObj.static_prov_supported}}{/literal}{$DIALOG_STANDARD_LBL_YES}{literal}{{else}}{/literal}{$DIALOG_STANDARD_LBL_NO}{literal}{{/if}}{{else}}{/literal}{$DIALOG_STANDARD_LBL_UNKNOWN}{literal}{{/if}}{/literal}</td></tr>
            </tbody>
        </table>
    </div>
    <div id="endpointconfig-standard-accounts">
        <table border="0" width="100%">
        <thead>
            <tr>
                <th>{$DIALOG_STANDARD_UNASSIGNED_ACCOUNTS} ({literal}{{App.accountsController.length}}{/literal})</th>
                <th>{$DIALOG_STANDARD_ASSIGNED_ACCOUNTS} ({literal}{{details.accountsController.length}}{/literal})</th>
            </tr>
        </thead>
        <tbody><tr>
        {literal}
        <td valign="top" width="50%">
            {{#view App.StandardUnboundAccountsView controllerBinding="App.accountsController"}}
            {{#each controller}}
                <li {{bindAttr id="idattr"}} {{action "selectAccount" this }} >
                    {{#if priority}}{{priority}}: {{/if}}{{tech}}/{{account}} ({{extension}}) - {{description}}
                    {{#if registerip}}<br/><span title="{/literal}{$DIALOG_STANDARD_TOOLTIP_REGISTERED}{literal}" style="color: red">{/literal}{$DIALOG_STANDARD_LBL_REGISTERED_AT}{literal}: {{registerip}}</span>{{/if}}
                </li>
            {{/each}}
            {{/view}}
        </td>
        <td valign="top" >
            <table border="0" width="100%"><tbody><tr><td>
            {{#view App.StandardBoundAccountsView controllerBinding="details.accountsController"}}
            {{#each controller}}
                <li {{bindAttr id="idattr"}} {{action "selectAccount" this }} >
                    {{#if priority}}{{priority}}: {{/if}}{{tech}}/{{account}} ({{extension}}) - {{description}}
                    {{#if registerip}}<br/><span title="{/literal}{$DIALOG_STANDARD_TOOLTIP_REGISTERED}{literal}" style="color: red">{/literal}{$DIALOG_STANDARD_LBL_REGISTERED_AT}{literal}: {{registerip}}</span>{{/if}}
                </li>
            {{/each}}
            {{/view}}
            </td></tr>
            <tr><td>
            {{#if details.accountsController.selectedAccount}}
                {{#with details.accountsController.selectedAccount}}
                <b>{/literal}{$DIALOG_STANDARD_LBL_PROPERTIES}{literal} {{tech}}/{{account}} ({{extension}}) - {{description}}</b>
                {{view App.EndpointconfigPropertylistView controllerBinding="propertiesController" }}
                {{/with}}
            {{else}}
                {/literal}{$DIALOG_STANDARD_LBL_NOACCOUNTS}{literal}
            {{/if}}
            </td></tr>
            </tbody></table>
        </td>
        {/literal}
        </tr>
        </tbody></table>
    </div>
    <div id="endpointconfig-standard-network">
        {literal}{{#view App.NetworkTypeView controllerBinding="controller" }}
            <input value="1" id="networktype-isDHCP" type="radio" name="networktype" {{action "setDHCP" on="change"}} {{bindAttr checked="isDHCP"}} />
            <label for="networktype-isDHCP">{/literal}{$DIALOG_STANDARD_LBL_DYNIP}{literal}</label>
            <input value="0" id="networktype-isStatic" type="radio" name="networktype" {{action "setStatic" on="change"}} {{bindAttr checked="isStatic"}} />
            <label for="networktype-isStatic">{/literal}{$DIALOG_STANDARD_LBL_STATICIP}{literal}</label>
        {{/view}}{/literal}
        {literal}{{#if isStatic}}{/literal}
        <fieldset class="ui-corner-all">
            <legend>{$DIALOG_STANDARD_LBL_STATIC_NETATTR}:</legend>
            <table border="0">
            <tbody>
                <tr><td><b>{$DIALOG_STANDARD_STATIC_IP}:</b></td><td>{literal}{{view Ember.TextField placeholderBinding="last_known_ipv4" pattern="^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$" valueBinding="details.static_ip"}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_STATIC_NETMASK}:</b></td><td>{literal}{{view Ember.TextField placeholder="255.255.255.0" pattern="^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$" valueBinding="details.static_mask"}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_STATIC_GW}:</b></td><td>{literal}{{view Ember.TextField placeholder="192.168.0.1" pattern="^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$" valueBinding="details.static_gw"}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_STATIC_DNS1}:</b></td><td>{literal}{{view Ember.TextField placeholder="8.8.8.8" pattern="^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$" valueBinding="details.static_dns1"}}{/literal}</td></tr>
                <tr><td><b>{$DIALOG_STANDARD_STATIC_DNS2}:</b></td><td>{literal}{{view Ember.TextField placeholder="8.8.4.4" pattern="^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$" valueBinding="details.static_dns2"}}{/literal}</td></tr>
            </tbody>
            </table>
        </fieldset>
        {literal}{{/if}}{/literal}
    </div>
    <div id="endpointconfig-standard-credentials">
        <table border="0">
        <tbody>
            <tr><td><b>{$DIALOG_STANDARD_TELNET_USER}:</b></td><td>{literal}{{view Ember.TextField valueBinding="details.telnet_username"}}{/literal}</td></tr>
            <tr><td><b>{$DIALOG_STANDARD_TELNET_PASS}:</b></td><td>{literal}{{view Ember.TextField type="password" valueBinding="details.telnet_password"}}{/literal}</td></tr>
            <tr><td><b>{$DIALOG_STANDARD_HTTP_USER}:</b></td><td>{literal}{{view Ember.TextField valueBinding="details.http_username"}}{/literal}</td></tr>
            <tr><td><b>{$DIALOG_STANDARD_HTTP_PASS}:</b></td><td>{literal}{{view Ember.TextField type="password" valueBinding="details.http_password"}}{/literal}</td></tr>
            <tr><td><b>{$DIALOG_STANDARD_SSH_USER}:</b></td><td>{literal}{{view Ember.TextField valueBinding="details.ssh_username"}}{/literal}</td></tr>
            <tr><td><b>{$DIALOG_STANDARD_SSH_PASS}:</b></td><td>{literal}{{view Ember.TextField type="password" valueBinding="details.ssh_password"}}{/literal}</td></tr>
        </tbody>
        </table>
    </div>
    <div id="endpointconfig-standard-endpointproperties">
        <p>{$DIALOG_STANDARD_PROPERTIES_MESSAGE}</p>
        {literal}{{view App.EndpointconfigPropertylistView controllerBinding="details.endpointPropertiesController" }}{/literal}
    </div>
</script>
<script type="text/x-handlebars" data-template-name="endpointconfig-propertylist">
    <table  cellspacing="0" cellpadding="0" class="neo-mini-table" ><tbody>
		<tr class="neo-table-title-row">
		    <td class="neo-table-title-row">{$DIALOG_STANDARD_LBL_PROPERTY}</td>
		    <td class="neo-table-title-row">{$DIALOG_STANDARD_LBL_VALUE}</td>
		    <td class="neo-table-title-row">&nbsp;</td>
		</tr>
    {literal}
    {{#each controller }}
    <tr class="neo-table-data-row">
        <td class="neo-table-data-row"><b>{{key}}:</b></td>
        <td class="neo-table-data-row">{{view Ember.TextField valueBinding="value"}}</td>
        <td class="neo-table-data-row">{{view App.DeletePropertyButtonView label="-" propkeyBinding="key"}}</td>
    </tr>
    {{/each}}
    <tr class="neo-table-data-row">
        <td class="neo-table-data-row">{{view Ember.TextField size="20" valueBinding="controller.tempKey"}}</td>
        <td class="neo-table-data-row">{{view Ember.TextField size="20" valueBinding="controller.tempValue"}}</td>
        <td class="neo-table-data-row">{{view App.AddPropertyButtonView label="+"}}</td>
    </tr>
    {/literal}
    </tbody></table>
</script>