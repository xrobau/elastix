<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <form  method='POST' style='margin-bottom:0;' action='?menu={$module_name}'>
            <td width="15%" align="center"><input class="button" type="submit" name="new" value="{$NEW_adress_book}"></td>
        </form>
        <form name="form_filter" method='POST' style='margin-bottom:0;' action='?menu={$module_name}'>
            <td width="30%" align="right">{$Phone_Directory}:</td>
            <td width="15%" align="left">
                <select name="select_directory_type" onchange='report_by_directory_type()'>
                    <option value="internal" {$internal_sel}>{$Internal}</option>
                    <option value="external" {$external_sel}>{$External}</option>
                </select>
            </td>
            <td width="10%" align="right">{$field.LABEL}: </td>
            <td width="15%" align="left" nowrap>
                {$field.INPUT} &nbsp;{$pattern.INPUT}&nbsp;&nbsp;
                <input class="button" type="submit" name="report" value="{$SHOW}">
            </td>
        </form>
    </tr>
</table>

{literal}
    <script type="text/javascript">
        function report_by_directory_type()
        {
            form_filter.submit();
        }
    </script>
{/literal}