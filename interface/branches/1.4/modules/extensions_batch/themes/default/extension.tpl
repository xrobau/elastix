<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
    </tr>
    <tr>
        <td>
            <table width="100%" cellpadding="4" cellspacing="0" border="0">
                <tr>
                    <td><input class="button" type="submit" name="save" value="{$SAVE}"></td>
                    <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tr>
                    <td>{$label_file}&nbsp;(file.csv):<span  class="required">*</span></td>
                    <td><input type='file' id='userfile' name='userfile'></td>
                    <td><a href="{$LINK}" name="link_download">{$DOWNLOAD}</a></td>
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