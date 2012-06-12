<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <td width="6%" align="right">{$date_start.LABEL}:</td>
        <td width="4%" align="left" nowrap>{$date_start.INPUT}</td>
        <td width="6%" align="right">{$date_end.LABEL}:</td>
        <td width="4%" align="left" nowrap>{$date_end.INPUT}</td>
        <td width="40%" align="right">
            <div style="width: 400px;">
                <div class="time2" align="right">
                    {$filter_field.LABEL}:&nbsp;&nbsp;
                    {$filter_field.INPUT}&nbsp;&nbsp;
                </div>
                <div id="textfield" class="time2" align="right" {$style_text} >
                    {$filter_value.INPUT}&nbsp;&nbsp;
                </div>
                <div id="duration" class="time2" align="right" {$style_time} >
                    {$horas.INPUT}&nbsp;H&nbsp;
                    {$minutos.INPUT}&nbsp;M&nbsp;
                    {$segundos.INPUT}&nbsp;S&nbsp;
                </div>
                <div class="time3" align="left">
                    &nbsp;<input class="button" type="submit" name="show" value="{$SHOW}" />
                </div>
            </div>
        </td>
    </tr>
</table>
