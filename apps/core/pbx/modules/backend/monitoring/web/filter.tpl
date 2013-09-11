<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
	<td width="6%" align="right">{$date_start.LABEL}:</td>
	<td width="4%" align="left" nowrap>{$date_start.INPUT}</td>
	<td width="6%" align="right">{$date_end.LABEL}:</td>
	<td width="4%" align="left" nowrap>{$date_end.INPUT}</td>
	<td align="right">
	{$filter_field.LABEL}:&nbsp;&nbsp;{$filter_field.INPUT}&nbsp;&nbsp;{$filter_value.INPUT}
	  <select id="filter_value_userfield" name="filter_value_userfield" size="1" style="display:none">
                <option value="incoming" {$SELECTED_1} >{$INCOMING}</option>
                <option value="outgoing" {$SELECTED_2} >{$OUTGOING}</option>
                <option value="queue" {$SELECTED_3} >{$QUEUE}</option>
		<option value="group" {$SELECTED_4} >{$GROUP}</option>
           </select>
	<input class="button" type="submit" name="show" value="{$SHOW}" />
	</td>
    </tr>
</table>