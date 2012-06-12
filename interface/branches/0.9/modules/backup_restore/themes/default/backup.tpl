{literal}
<script type="text/javascript">
function ChequearTodos(chkbox)
{

for (var i=0;i < document.getElementById("backup_form").elements.length;i++)
{
var elemento = document.getElementById("backup_form").elements[i];
if (elemento.type == "checkbox")
{
elemento.checked = chkbox.checked
}
} 
}
</script>
{/literal}
<form method="POST" enctype="multipart/form-data" id="backup_form">
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
          <input class="button" type="submit" name="process_backup" value="{$PROCESS_BACKUP}">
        </td>
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<td width="35%"><INPUT type="checkbox" name="backup_total" id="backup_total" onClick=ChequearTodos(this); {$all_checked}><b>{$LBL_TODOS}</b></td>
      </tr>
  {foreach key=key item=item from=$backup_options}
      <tr>
	<td width="35%"><INPUT type="checkbox" name="{$key}" id="{$key}" value="{$key}" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>	
      </tr>
  {/foreach}
     </table>
    </td>
</tr>


</table>
</form>
