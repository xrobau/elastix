{* Este DIV se usa para mostrar los mensajes de información *}
<div
    id="elastix-module-info-message"
    class="ui-state-highlight ui-corner-all"
    style="display: none;">
    <p>
        <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <span id="elastix-module-info-message-text"></span>
    </p>
</div>
{* Este DIV se usa para mostrar los mensajes de error *}
<div
    id="elastix-module-error-message"
    class="ui-state-error ui-corner-all"
    style="display: none;">
    <p>
        <span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
        <span id="elastix-module-error-message-text"></span>
    </p>
</div>
<div id="endpointConfigApplication" style="background-color: #f0f0f0;">

<script type="text/x-handlebars" data-template-name="loading">
<div style="text-align: center; padding: 40px;"><img src="images/loading.gif" /></div>
</script>

<script type="text/x-handlebars" data-template-name="endpoints">
  <div class="neo-endpointconfig-header-row">
    {literal}
    {{#view
        style="cursor:default" }}
        {{#if scanInProgress }}
            <div class="neo-table-header-row-filter">
                <img src="images/loading.gif" style="height: 21px; vertical-align: middle;" />
                <button class="neo-table-action" {{action "scanCancel"}} >{/literal}{$LBL_CANCEL}{literal}</button>
            </div>
        {{else}}
            {{#unless uiblock}}
                <div class="neo-table-header-row-filter">
                    {{view App.NetmaskView valueBinding="scanMask" }}
                    <button class="neo-table-action" title="{/literal}{$LBL_SCAN}{literal}" {{action "scanStart"}} ><img align="absmiddle" src="images/searchw.png"/></button>
                </div>
            {{/unless}}
        {{/if}}
        {{#if configInProgress }}
            <div class="neo-table-header-row-filter">
                {/literal}{$LBL_STEP}{literal} {{completedsteps}} {/literal}{$of}{literal} {{totalsteps}} {{#if founderror}}(with errors){{/if}}
            </div>
            <div class="neo-table-header-row-filter" style="width: 600px;">
            {{view App.ConfigProgressView valueBinding="completedsteps" maxBinding="totalsteps" }}
            </div>
        {{else}}
	        {{#unless uiblock }}
	            <div class="neo-table-header-row-filter" title="{/literal}{$TOOLTIP_CONFIGURE}{literal}" {{action "configStart"}} >
	                <button class="neo-table-action"><img align="absmiddle" src="images/endpoint.png"/></button>
	            </div>
	        {{/unless}}
        {{/if}}
        {{#unless uiblock }}
            {{#linkTo "endpoints.getconfiglog"}}
            <div class="neo-table-header-row-filter" title="{/literal}{$LBL_VIEW_LOG}{literal}">
                <button class="neo-table-action" ><img align="absmiddle" src="images/list.png"/></button>
            </div>
            {{/linkTo}}
        {{/unless}}
        {{#if unsetInProgress }}
            <div class="neo-table-header-row-filter">
                <img src="images/loading.gif" style="height: 21px; vertical-align: middle;" />
            </div>
        {{else}}
	        {{#unless uiblock }}
	            <div class="neo-table-header-row-filter" title="{/literal}{$LBL_FORGET}{literal}" {{action "forgetSelected"}} >
	                <button class="neo-table-action"><img align="absmiddle" src="images/delete5.png"/></button>
	            </div>
	        {{/unless}}
        {{/if}}
        {{#unless uiblock }}
            {{#view App.SubMenuView}}
                <div class="neo-table-header-row-filter" title="{/literal}{$LBL_DOWNLOAD}{literal}" {{action "toggleMenu" target="view"}}>
                <button class="neo-table-action" ><img align="absmiddle" src="images/download2.png"/></button>
                </div>
                <div class="neo-endpointconfig-submenu" {{bindAttr style="view.menuStyle"}}>
                    <a href="?menu={/literal}{$module_name}{literal}&amp;action=download&amp;rawmode=yes&amp;format=legacy">
                    <img src="images/csv.gif" border="0" align="absmiddle" title="CSV" />&nbsp;{/literal}{$LBL_CSV_LEGACY}{literal}
                    </a>
                    <a href="?menu={/literal}{$module_name}{literal}&amp;action=download&amp;rawmode=yes&amp;format=xml">
                    <img src="images/page.gif" border="0" align="absmiddle" title="XML" />&nbsp;{/literal}{$LBL_XML}{literal}
                    </a>
                    <a href="?menu={/literal}{$module_name}{literal}&amp;action=download&amp;rawmode=yes&amp;format=nested">
                    <img src="images/csv.gif" border="0" align="absmiddle" title="CSV2" />&nbsp;{/literal}{$LBL_CSV_NESTED}{literal}
                    </a>
                </div>
            {{/view}}
            {{#view App.SubMenuView}}
                <div class="neo-table-header-row-filter" title="{/literal}{$LBL_UPLOAD}{literal}" {{action "toggleMenu" target="view"}}>
                <button class="neo-table-action"><img align="absmiddle" src="modules/{/literal}{$module_name}{literal}/images/upload.png"/></button>
                </div>
                <div class="neo-endpointconfig-submenu" {{bindAttr style="view.menuStyleManual"}}>
                    {{#if fileUploadSupported}}
	                    {{view App.EndpointUploadView }}
	                    {{#if uploadInProgress }}
	                    {{view JQ.ProgressBar valueBinding="completedupload" maxBinding="totalupload" }}
	                    {{/if}}
                    {{else}}
                        <form method="POST" action="?" enctype="multipart/form-data">
                            <input type="hidden" name="module" value="{/literal}{$module_name}{literal}" />
                            <input type="hidden" name="action" value="upload" />
                            <input type="file" name="endpointfile[]" />
                            <input type="submit" class="button" name="legacyupload" value="{/literal}{$LBL_UPLOAD}{literal}" />
                        </form>
                    {{/if}}
                </div>
            {{/view}}
        {{/unless}}
    {{/view}}{/literal}
    {literal}{{view App.NeoNavigationView }}{/literal}
  </div>
<div id="addonlist">

<table align="center" cellspacing="0" cellpadding="0" width="100%">
<tr class="neo-table-title-row">
    <td class="neo-table-title-row">{literal}{{view Ember.Checkbox checkedBinding="seleccionTodos"}}{/literal}&nbsp;</td>
    <td class="neo-table-title-row">{$LBL_STATUS}</td>
    <td class="neo-table-title-row">{$LBL_MAC_ADDRESS}</td>
    <td class="neo-table-title-row">{$LBL_CURRENT_IP}</td>
    <td class="neo-table-title-row">{$LBL_MANUFACTURER}</td>
    <td class="neo-table-title-row">{$LBL_MODEL}</td>
    <td class="neo-table-title-row">{$LBL_OPTIONS}</td>
</tr>

{literal}{{#view tagName="tbody"}}
{{#each endpoint in content}}
<tr class="neo-table-data-row">
	<td class="neo-table-data-row">{{view Ember.Checkbox checkedBinding="endpoint.isSelected"}}</td>
    <td class="neo-table-data-row">
    {{#if endpoint.isFromBatch}}<span style="float: left;"><span class="ui-icon ui-icon-script" title="{/literal}{$TOOLTIP_FROM_BATCH}{literal}"></span></span>{{/if}}
    {{#if endpoint.isModified}}<span style="float: left;"><span class="ui-icon ui-icon-disk"  title="{/literal}{$TOOLTIP_MODIFIED}{literal}"></span></span>{{/if}}
    {{#if endpoint.hasExtensions}}<span style="float: left;"><span class="ui-icon ui-icon-person"  title="{/literal}{$TOOLTIP_HAS_EXTENSIONS}{literal}"></span></span>{{/if}}
    {{#if endpoint.is_missing}}<span style="float: left;"><span class="ui-icon ui-icon-alert"  title="{/literal}{$TOOLTIP_MISSING}{literal}"></span></span>{{/if}}
    </td>
    <td class="neo-table-data-row">{{endpoint.mac_address}}</td>
    <td class="neo-table-data-row">{{#if endpoint.last_known_ipv4}}<a target="_blank" {{bindAttr href="endpoint.adminUrl"}}>{{endpoint.last_known_ipv4}}</a>{{else}}{/literal}{$LBL_UNKNOWN}{literal}{{/if}}</td>
	<td class="neo-table-data-row">{{endpoint.name_manufacturer}}</td>
	<td class="neo-table-data-row">{{view Ember.Select
	    contentBinding="endpoint.modelSelect"
	    optionValuePath="content.id_model"
        optionLabelPath="content.name_model"
        valueBinding="endpoint.id_model"
        disabledBinding="scanInProgress"}}</td>
    <td class="neo-table-data-row">
        {{#linkTo "endpoints.endpointconfig" endpoint }}[{/literal}{$LBL_CONFIGURE}{literal} {{endpoint.last_known_ipv4}}]{{/linkTo}}
    </td>
</tr>
{{else}}
<tr class="neo-table-data-row">
    <td class="neo-table-data-row" colspan="7">{/literal}{$MSG_NO_ENDPOINTS}{literal}</td>
</tr>
{{/each}}
{{/view}}{/literal}
</table>

</div>
<div id="footer" style="background: url(modules/{$module_name}/images/endpointconfig_header_row_bg.png) repeat-x top; width: 100%; height:40px;">
{literal}{{view App.NeoNavigationView }}{/literal}
</div>

{literal}{{outlet}}{/literal}
</script>

<script type="text/x-handlebars" data-template-name="endpoints/endpointconfig">
<div
    id="elastix-module-error-message-dialog"
    class="ui-state-error ui-corner-all"
    style="display: none;">
    <p>
        <span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
        <span id="elastix-module-error-message-text-dialog"></span>
    </p>
</div>
{literal}{{outlet}}{/literal}
</script>

<script type="text/x-handlebars" data-template-name="endpoints/getconfiglog">
{literal}
{{view App.FullTextArea
    valueBinding="log"
    disabled="disabled"}}
{/literal}
</script>

<script type="text/x-handlebars" data-template-name="neo-navigation">
    <span {literal}{{action "displayStart"}}{/literal}><img style="cursor: pointer;" src="modules/{$module_name}/images/table-arrow-first.gif" width="16" height="16" alt='{$lblStart}' align='absmiddle' /></span>
    <span {literal}{{action "displayPrevious" }}{/literal}><img  style="cursor: pointer;" src="modules/{$module_name}/images/table-arrow-previous.gif" width="16" height="16" alt='{$lblPrevious}' align='absmiddle' /></span>
    ({$showing} {literal}{{startPosition}} - {{endPosition}}{/literal} {$of} <span>{literal}{{completeList.length}}{/literal}</span>)
    <span {literal}{{action "displayNext"}}{/literal}><img style="cursor: pointer;" src="modules/{$module_name}/images/table-arrow-next.gif" width="16" height="16" alt='{$lblNext}' align='absmiddle' /></span>
    <span {literal}{{action "displayEnd"}}{/literal}><img style="cursor: pointer;" src="modules/{$module_name}/images/table-arrow-last.gif" width="16" height="16" alt='{$lblEnd}' align='absmiddle' /></span>
</script>
</div>

<script type="text/javascript">
var lastop_error_message = {$LASTOP_ERROR_MESSAGE};
var arrLang_main = {$ARRLANG_MAIN};
</script>
