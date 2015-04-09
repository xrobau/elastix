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