<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <form name="form_filter" id="form_filter" method='POST' style='margin-bottom:0;' action='?menu={$module_name}'>
            <td width="30%" align="right">{$Phone_Directory}:</td>
            <td width="15%" align="left">
                <select name="select_directory_type" onchange='report_by_directory_type()'>
                    <option value="Internal" {$internal_sel}>{$Internal}</option>
                    <option value="External" {$external_sel}>{$External}</option>
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
        function return_phone_number(number, type, id)
        {
            window.opener.document.getElementById("call_to").value = number;
            window.opener.document.getElementById("phone_type").value = type;
            window.opener.document.getElementById("phone_id").value = id;
            window.close();
        }
        
        function report_by_directory_type()
        {
            var form_filter = document.getElementsByName('form_filter')[0];
            form_filter.submit();
        }
    </script>
{/literal}
