<table width="99%" cellpadding="4" cellspacing="0" border="0" align="center">
    <tr class="letra12">
        <td align="right">{$date_start.LABEL}:</td>
        <td align="left">{$date_start.INPUT}</td>
        <td align="right">{$field_pattern.LABEL}: </td>
        <td align="left" colspan="3">{$field_name.INPUT}&nbsp;{$field_pattern.INPUT} <input class="button" type="submit" name="filter" value="{$SHOW}" /></td>
    </tr>
    <tr class="letra12">
        <td align="right">{$date_end.LABEL}:</td>
        <td align="left">{$date_end.INPUT}</td>
        <td align="right">{$status.LABEL}: </td>
        <td align="left">{$status.INPUT}</td>
    </tr>
    <tr class="letra12">
        {if $userLevel ne 'superadmin'}
        <td align="right">{$ringgroup.LABEL}: </td>
        <td align="left">{$ringgroup.INPUT}</td>
        {/if}
        <td align="right">{$calltype.LABEL}: </td>
        <td align="left">{$calltype.INPUT}</td>
    </tr>
</table>


