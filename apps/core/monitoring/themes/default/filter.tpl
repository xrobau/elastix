<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        {if $user eq 'admin'}
            <td width="6%" align="right">{$date_start.LABEL}: <span  class="required">*</span></td>
            <td width="4%" align="left" nowrap>{$date_start.INPUT}</td>
            <td width="6%" align="right">{$date_end.LABEL}: <span  class="required">*</span></td>
            <td width="4%" align="left" nowrap>{$date_end.INPUT}</td>
            <td width="20%" align="right">
            {$filter_field.LABEL}:&nbsp;&nbsp;{$filter_field.INPUT}&nbsp;&nbsp;{$filter_value.INPUT}
            <input class="button" type="submit" name="show" value="{$SHOW}" />
            </td>
        {else}
            <td width="4%" align="right">{$date_start.LABEL}: <span  class="required">*</span></td>
            <td width="4%" align="left" nowrap>{$date_start.INPUT}</td>
            <td width="4%" align="right">{$date_end.LABEL}: <span  class="required">*</span></td>
            <td width="6%" align="left" nowrap>{$date_end.INPUT}</td>
            <td width="3%" align="right">
                <input class="button" type="submit" name="show" value="{$SHOW}" />
            </td>
        {/if}
    </tr>
</table>