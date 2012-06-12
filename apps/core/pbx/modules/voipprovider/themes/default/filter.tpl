<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <form  method='POST' style='margin-bottom:0;' action='{$url}'>
                <td width="10%" align="left"><input class="button" type="submit" name="new_account" value="{$NEW_ACCOUNT}"></td>
        </form>
        <td width="10%" align="left">&nbsp;&nbsp;</td>
        <form  method='POST' style='margin-bottom:0;' action='{$url}'>
                <td width="10%" align="right">
                    {$filter_field.LABEL}:&nbsp;&nbsp;{$filter_field.INPUT}&nbsp;&nbsp;{$filter_value.INPUT}
                    <input class="button" type="submit" name="show" value="{$SHOW}" />
                </td>
        </form>
    </tr>
</table>