<div id='error' name='error'></div>
<div>
<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr>
        <td align="left">
            <input class="button" type="submit" name="delete" value="{$DELETE}" onclick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td align="left" width="12%"><b>{$Delete_Menu}:</b></td>
        <td width="30%"><input type="checkbox" name="delete_menu" id="delete_menu" checked='checked' /></td>
        <td></td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$Delete_Files}:</b></td>
        <td width="30%"><input type="checkbox" name="delete_files" id="delete_files" /></td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$Level}:</b></td>
        <td align="left">
            <select onchange='mostrar_menu()' id="select_level" name="select_level">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
            </select>
        </td>
    </tr>
    <tr class="letra12" id="level_1">
        {$level_1}
    </tr>
    <tr class="letra12" id="level_2">
    </tr>
    <tr class="letra12" id="level_3">
    </tr>
</table>
</div>
{literal}
<script type="text/javascript">
    function mostrar_menu()
    {
        var level = -1;
        var id_module_level_1 = '', id_module_level_2 = '', id_module_level_3 = '';

        var select_level = document.getElementById("select_level");
        var index_level = select_level.selectedIndex;
        level = select_level.options[ index_level ].value;

        var module_level_1 = document.getElementById("module_level_1");
        var index_level_1 = module_level_1.selectedIndex;
        id_module_level_1 = module_level_1.options[ index_level_1 ].value;

        var module_level_2 = document.getElementById("module_level_2");
        if(module_level_2 != null)
        {
            var index_level_2 = module_level_2.selectedIndex;
            id_module_level_2 = module_level_2.options[ index_level_2 ].value;
        }

        var module_level_3 = document.getElementById("module_level_3");
        if(module_level_3 != null)
        {
            var index_level_3 = module_level_3.selectedIndex;
            id_module_level_3 = module_level_3.options[ index_level_3 ].value;
        }

        xajax_mostrar_menu(level, id_module_level_1, id_module_level_2, id_module_level_3);
    }
</script>
{/literal}