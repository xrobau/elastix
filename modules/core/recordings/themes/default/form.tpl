<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$IMG}" border="0" align="absmiddle">&nbsp;&nbsp;{$TITLE}</td>
    </tr>
    <tr>
        <td align="left" colspan='2'><input class="button" type="submit" name="save" value="{$SAVE}" /></td>
    </tr>
    <tr>
        <table class="tabForm" style="font-size: 16px;" width="100%" border='0'>
            <tr class="letra12">
                <td colspan='2'>
                    <input type="radio" name="option_record" id="record_by_phone" value="by_record" {$check_record} onclick="Activate_Option_Record()" />
                    {$record} &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="option_record" id="record_by_file" value="by_file" {$check_file} onclick="Activate_Option_Record()" />
                    {$file_upload}
                </td>
            </tr>
            <tr class="letra12" id='record_option'>
                <td align="left" width='13%'><b>{$recording_name_Label}</b></td>
                <td align="left">
                    <input size='30' name="recording_name" id="recording_name" type="text" value="{$filename}" />&nbsp;[.gsm|.wav] &nbsp;&nbsp;
                    <input class="button" title={$record} type="submit" name="record" id="record" value="{$record}"  />
                </td>
            </tr>
            <tr class="letra12" id='upload_option'>
                <td align="left" width='13%'><b>{$record_Label}</b></td>
                <td align="left">
                    <input name="file_record" id="file_record" type="file" value="{$file_record_name}" size='30' />
                </td>
            </tr>
        </table>
    <tr>
</table>
<input type='hidden' name='filename' value='{$filename}' />

{literal}
    <script type="text/javascript">
        Activate_Option_Record();

        function Activate_Option_Record()
        {
            var record_by_phone = document.getElementById('record_by_phone');
            var record_by_file = document.getElementById('record_by_file');
            if(record_by_phone.checked==true)
            {
                document.getElementById('record_option').style.display = '';
                document.getElementById('upload_option').style.display = 'none';
            }
            else
            {
                document.getElementById('record_option').style.display = 'none';
                document.getElementById('upload_option').style.display = '';
            }
        }
    </script>
{/literal}