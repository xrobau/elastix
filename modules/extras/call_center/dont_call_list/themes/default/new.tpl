{literal}
<script type="text/javascript">


function inhabilitar(){
    ctrl = document.getElementById("form_new_number").new_accion;
    if(ctrl[0].checked){
        document.getElementById("form_new_number").file_number.disabled=false;
        document.getElementById("form_new_number").txt_new_number.disabled=true;
    }else{
        document.getElementById("form_new_number").file_number.disabled=true;
        document.getElementById("form_new_number").txt_new_number.disabled=false;
    }
}

</script>
{/literal}

<script src="modules/{$MODULE_NAME}/libs/js/base.js"></script>
<table width="100%" cellpadding="1" cellspacing="1" height="100%" border=0>
{if !$FRAMEWORK_TIENE_TITULO_MODULO}
    <tr class="moduleTitle">
        <td colspan="4" class="moduleTitle" align="left">
            <img src="{$icon}" border="0" align="absmiddle" />&nbsp;&nbsp;{$title}
        </td>
    </tr>
{/if}    
    <tr>
        <td>
            <form name="form_new_number" id="form_new_number" style='margin-bottom:0;' method="post" enctype="multipart/form-data">
            <table align='left' border=0 class="filterForm" cellspacing="0" cellpadding="0" width="100%">
	    <tr>
                <td class="letra12" width='60'>
                    <input type="radio" name="new_accion" id="new_accion" value="file" checked onClick="inhabilitar()">
                    {$label_file}:
                </td>
                <td align='left' width='15'>
		    <input name="file_number" type="file" size='45'  />
		</td>
	    </tr>
	    <tr>
                <td class="letra12" width='60'>
                    <input type="radio" name="new_accion" id="new_accion" value="text" onClick="inhabilitar()">
                    {$label_text}:
                </td>
                <td align='left' width='15'>
		    <input name="txt_new_number" type="text" size='20' disabled />
		</td>
	    </tr>
	    <tr>
                <td colspan='4' align='left'>
                    <input class='button' type = 'submit' name='submit_new' 
                        value='{$NAME_BUTTON_SUBMIT}' onClick="return validarFile(this.form.fileCRM.value)" />
                        &nbsp;&nbsp;&nbsp;&nbsp;
                    <input class='button' type = 'submit' name='submit_cancel' 
                        value='{$NAME_BUTTON_CANCEL}' />
                </td>
	    </tr>
	    <tr>
		<td class="letra12" align='left'>&nbsp;</td>
	    </tr>
            </table>
            </form>
        </td>
    </tr>
</table>


