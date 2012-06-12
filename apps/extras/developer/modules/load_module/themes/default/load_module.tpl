{literal}
<script type="text/javascript">
function removeContent(d) 
{
    document.getElementById(d).style.display = "none";
}

function insertContent(d) 
{
    document.getElementById(d).style.display = "";
}

function show_form_menu()
{
  type_val = document.getElementById('SELECT_MENU');
  indice = type_val.selectedIndex
  valor = type_val.options[indice].value
  if (valor == 0){
     removeContent('fila_extended1');
     insertContent('fila_extended0');
  }
  else{
     removeContent('fila_extended0');
     insertContent('fila_extended1');

  }
}
</script>
{/literal}
<form method="POST" enctype="multipart/form-data">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          {if $refresh}
          <input class="button" type="submit" name="refresh" value="{$REFRESH}">
          {else}
          <input class="button" type="submit" name="save" value="{$SAVE}">
          {/if}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
        <tr>
	<td>{$label_module_file}&nbsp;(module.tar.gz):<span  class="required">*</span></td>
	<td><input type='file' name='module_file'></td>
      </tr>          
      </table>
    </td>
  </tr>
</table>
</form>