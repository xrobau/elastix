<form method="POST" action="?menu=userlist">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/user.png" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          {if $mode eq 'input'}
          <input class="button" type="submit" name="submit_save_user" value="{$SAVE}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {elseif $mode eq 'edit'}
          <input class="button" type="submit" name="submit_apply_changes" value="{$APPLY_CHANGES}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {else}
          <input class="button" type="submit" name="edit" value="{$EDIT}">
          <input class="button" type="submit" name="delete" value="{$DELETE}"  onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')"></td>
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
	<td width="15%">{$name.LABEL}: <span  class="required">*</span></td>
	<td width="35%">{$name.INPUT}</td>
	<td>{$description.LABEL}: <span  class="required">*</span></td>
	<td>{$description.INPUT}</td>
      </tr>
      <tr>

		<td width="20%">{$password1.LABEL}: <span  class="required">*</span></td>
	<td width="30%">{$password1.INPUT}</td>
	<td width="20%">{$password2.LABEL}: <span class="required">*</span></td>
	<td width="30%">{$password2.INPUT}</td>
      </tr>
      <tr>
	<td>{$group.LABEL}: <span  class="required">*</span></td>
	<td>{$group.INPUT}</td>
	<td width="20%">{$extension.LABEL}: <span class="required">*</span></td>
	<td width="30%">{$extension.INPUT}</td>
      </tr>
    </table>
  </td>
</tr>
</table>
<input type="hidden" name="id_user" value="{$id_user}">
</form>
