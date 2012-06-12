<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
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
                    <td width="22%" align="left">{$label_endpoint} <span  class="required"> * </span></td>
                    <td align="left">{$endpoint_mask.INPUT}</td>
                    <td style="text-align:justify">{$HeaderFile}</td>
                </tr>
                <tr>
                    <td width="22%" align="left">{$label_file}: <span  class="required"> * </span></td>
                    <td align="left">{$file.INPUT}</td>
                    <td>{$AboutUpdate}</td>
                </tr> 
            </table>
        </td>
    </tr>
</table>