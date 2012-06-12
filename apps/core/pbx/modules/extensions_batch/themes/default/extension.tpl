<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td>
            <table width="100%" cellpadding="4" cellspacing="0" border="0">
                <tr>
                    <td><input class="button" type="submit" name="save" value="{$SAVE}"></td>
                    <td align="right" nowrap><span class="letra12"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tr>
                    <td width="22%" align="right">{$label_file}:</td>
                    <td width="30%"><input type='file' id='userfile' name='userfile'></td>
                    <td width="30%" align='center'><a class="link1" href="?menu={$MODULE_NAME}&amp;accion=download_csv&amp;rawmode=yes">{$DOWNLOAD}</a></td>
                    <td align="center">{$DELETE_ALL}</td>
                </tr> 
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tr><td>{$HeaderFile}</td></tr>
                <tr><td>{$AboutUpdate}</td></tr>
            </table>
        </td>
    </tr>
</table>
