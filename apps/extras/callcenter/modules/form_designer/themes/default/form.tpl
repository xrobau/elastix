<!-- Message board -->
<div class="mb_title" id="mb_title"></div>
<div class="mb_message" id="mb_message"></div>
<!-- end of Message board -->
<form method="POST" name="form_formulario">
    <table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
        <tr>
            <td>
                <table width="100%" cellpadding="3" cellspacing="0" border="0">
                    <tr>
                        <td align="left">
                        <input class="button" type="button" name="apply_changes" value="{$SAVE}" />
                        <input class="button" type="submit" name="cancel" value="{$CANCEL}" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                    <tr>
                        <td align="right" valign="top">{$form_nombre.LABEL}: <span  class="required" {$style_field}>*</span></td>
                        <td valign="top">{$form_nombre.INPUT}</td>
                    </tr>
                    <tr>
	                    <td align="right" valign="top">{$form_description.LABEL}:</td>
	                    <td valign="top">{$form_description.INPUT}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td>
<table border='0' cellspacing='0' cellpadding='0' width='100%' align='center'>
<thead>
<tr class='table_title_row'>
    <td class='table_title_row' width="50">{$LABEL_ORDER|escape:html}</td>
    <td class='table_title_row'>{$LABEL_NAME|escape:html}</td>
    <td class='table_title_row'>{$LABEL_TYPE|escape:html}</td>
    <td class='table_title_row'>{$LABEL_ENUMVAL|escape:html}</td>
    <td class='table_title_row' width="40">&nbsp;</td>
</tr>
</thead>
<tbody id="tbody_fieldlist" style="background-color: rgb(255, 255, 255);">
<tr title="{$TOOLTIP_DRAGDROP}">
    <td valign="top" class='table_data'><span class="formfield_order">?</span><input type="hidden" name="formfield_id" value="" /></td>
    <td valign="top" class='table_data formfield_name'><input type="text" name="formfield_name" value="(no name)" /></td>
    <td valign="top" class='table_data formfield_type'><select>{$CMB_TIPO}</select></td>
    <td valign="top" class='table_data formfield_enumval'>
        <span class="formfield_enumval_wrap">
	        <span class="formfield_enumval_passive"></span>
	        <div class="formfield_enumval_active">
	            <table width="100%" border="0" cellspacing="0" cellpadding="0">
	                <tr>
	                    <td rowspan='2' valign="top" width="50"><input type="text" name="formfield_enumlist_newitem" id='formfield_enumlist_newitem' value="" /></td>
	                    <td valign="top" width="40"><input class="button" type="button" name="formfield_additem" value=">>"/></td>
	                    <td rowspan='2' valign="top">
	                        <select name="formfield_enumlist_items" size="4" class="formfield_enumlist_items" style="width:120px"></select>
	                    </td>
	                </tr>
	                <tr>
	                    <td width="40"><input class="button" type="button" name="formfield_delitem" value="<<" /></td>
	                </tr>
	            </table>
	        </div>
        </span>
    </td>
    <td class='table_data formfield_order'><input class="button" type="button" name="formfield_add" value="{$LABEL_FFADD|escape:html}" /><input class="button" type="button" name="formfield_del" value="{$LABEL_FFDEL|escape:html}" /></td>
</tr>
</tbody>
</table>            
        </td></tr>
    </table>
    {$id_formulario.INPUT}
</form>
<script type="text/javascript">
CAMPOS_FORM = {$CAMPOS_FORM};
</script>