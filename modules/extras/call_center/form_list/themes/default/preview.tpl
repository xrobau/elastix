<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
{if !$FRAMEWORK_TIENE_TITULO_MODULO}
    <table width="100%">
    <tr class="moduleTitle">
            <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle" />&nbsp;&nbsp;{$title} 
            </td>
    </tr>
    </table>
{/if}    
    <table class="tabForm">
    <tr>
            <td width="12%" valign="top">{$form_nombre.LABEL}: <span  class="required" {$style_field}>*</span></td>
            <td width="42%" valign="top">{$form_nombre.INPUT}</td>
            <td width="9%" valign="top">{$form_description.LABEL}:</td>
            <td width="45%" valign="top">{$form_description.INPUT}</td>
    </tr> 
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                {$formulario}
            </td>
        </tr>
    </table>
</table>

