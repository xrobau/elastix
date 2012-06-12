{literal}
<script type="text/javascript">
function ChequearTodos(chkbox)
{

for (var i=0;i < document.getElementById("restore_form").elements.length;i++)
{
var elemento = document.getElementById("restore_form").elements[i];
if (elemento.type == "checkbox")
{
elemento.checked = chkbox.checked
}
} 
}
</script>
{/literal}
<form method="POST" enctype="multipart/form-data" id="restore_form" name='main'>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">{$ERROR_MSG}</td>

     </tr>
      <tr>
        <td align="left">
          <input class="button" type="submit" name="back" value="{$BACK}">
          <input class="button" type="submit" name="process" value="{$PROCESS_RESTORE}">
        </td>
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<td width="35%"><INPUT type="checkbox" name="restore_total" id="restore_total" onClick=ChequearTodos(this); {$all_checked}><b>{$LBL_TODOS}</b>
        <input type='hidden' name='backup_file' value='{$BACKUP_FILE}'></td>
      </tr>
  {foreach key=key item=item from=$restore_options}
      <tr>
	<td width="35%"><INPUT type="checkbox" name="{$key}" id="{$key}" value="{$key}" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>	
      </tr>
  {/foreach}
     </table>
    </td>
</tr>

</table>
</form>