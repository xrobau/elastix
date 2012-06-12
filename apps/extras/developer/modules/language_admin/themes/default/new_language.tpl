<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr>
        <td align="left" width="10%"><input class="button" type="submit" name="save" value="{$SAVE}" type="submit"></td>
        <td align="left"><input class="button" type="submit" name="cancel" value="{$CANCEL}" type="submit"></td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" border="0">
    <tr class="letra12">
        <td colspan='2'>
            <input type="radio" name="option" id="select_language" value="select_language" {$check_language} onclick="Activate_Option_Record()" />
            {$new_language}&nbsp;&nbsp;
            <input type="radio" name="option" id="select_traslate" value="select_traslate" {$check_traslate} onclick="Activate_Option_Record()" />
            {$new_traslate}
        </td>
    </tr>
    <tr class="letra12" height='30' id="fila_select_module">
        <td width="20%" align="left">{$select_module.LABEL} </td>
        <td align="left">{$select_module.INPUT}</td>
    </tr>
    <tr class="letra12" height='30' id="fila_select_language">
        <td width="20%" align="left">{$select_lang.LABEL}</td>
        <td align="left">{$select_lang.INPUT}</td>
    </tr>
    <tr class="letra12" height='30' id="fila_select_lang_english">
        <td width="20%" align="left">{$lang_english.LABEL}</td>
        <td align="left">{$lang_english.INPUT}</td>
    </tr>
    <tr class="letra12" height='30' id="fila_select_lang_traslate">
        <td width="20%" align="left">{$lang_traslate.LABEL}</td>
        <td align="left">{$lang_traslate.INPUT}</td>
    </tr>
    <!--  **********************************************************    -->
    <tr class="letra12" height='30' id="fila_new_language">
        <td width="20%" align="left">{$language_new.LABEL} </td>
        <td align="left">{$language_new.INPUT}&nbsp;{$new_language_ej}</td>
    </tr>
</table >

{literal}
<script type="text/javascript">
    Activate_Option_Record();

    function Activate_Option_Record()
    {
        var record_by_phone = document.getElementById('select_language');
        var record_by_file = document.getElementById('select_traslate');
        if(record_by_file.checked==true)
        {
            document.getElementById('fila_select_module').style.display = '';
            document.getElementById('fila_select_language').style.display = '';
            document.getElementById('fila_select_lang_english').style.display = '';
            document.getElementById('fila_select_lang_traslate').style.display = '';

            document.getElementById('fila_new_language').style.display = 'none';
        }
        else
        {
            document.getElementById('fila_select_module').style.display = 'none';
            document.getElementById('fila_select_language').style.display = 'none';
            document.getElementById('fila_select_lang_english').style.display = 'none';
            document.getElementById('fila_select_lang_traslate').style.display = 'none';

            document.getElementById('fila_new_language').style.display = '';
        }
    }
</script>
{/literal}