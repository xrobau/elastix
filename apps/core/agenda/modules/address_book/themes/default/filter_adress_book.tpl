<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
            <td width="13%" align="right">{$Phone_Directory}:</td>
            <td width="15%" align="left">
                <select name="select_directory_type" onchange='submit();'>
                    <option value="internal" {$internal_sel}>{$Internal}</option>
                    <option value="external" {$external_sel}>{$External}</option>
                </select>
            </td>
            <td width="43%" align="right">{$field.LABEL}: </td>
            <td width="32%" align="left" nowrap>
                {$field.INPUT} &nbsp;{$pattern.INPUT}&nbsp;&nbsp;
                <input class="button" type="submit" name="report" value="{$SHOW}">
            </td>
    </tr>
</table>


