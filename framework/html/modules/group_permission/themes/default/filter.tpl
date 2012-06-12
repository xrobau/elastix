<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <td width="60%" align="left">&nbsp;&nbsp;</td>
        <td width="30%" align="right">{$filter_group.LABEL}:&nbsp;&nbsp;{$filter_group.INPUT}</td>
        <td>&nbsp;</td>
    </tr>
    <tr class="letra12">
        <td align="left">&nbsp;&nbsp;</td>
        <td align="right">{$filter_resource.LABEL}:&nbsp;&nbsp;{$filter_resource.INPUT}</td>
        <td align="right"><input class="button" type="submit" name="show" value="{$SHOW}" /><td>
    </tr>
</table>

<input type="hidden" name="resource_apply" value="{$resource_apply}">
<input type="hidden" name="limit_apply" value="{$limit_apply}">
<input type="hidden" name="offset_apply" value="{$offset_apply}">

<input type="hidden" name="action_apply" value="{$action_apply}">
<input type="hidden" name="start_apply" value="{$start_apply}">
