<form method="POST" action="?menu=grouplist">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          {if $mode eq 'input'}
          <input class="button" type="submit" name="save_group" value="{$SAVE}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {elseif $mode eq 'edit'}
          <input class="button" type="submit" name="apply_changes" value="{$APPLY_CHANGES}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {else}
          <input class="button" type="submit" name="edit" value="{$EDIT}">
          <input class="button" type="submit" name="delete" value="{$DELETE}"  onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
		  <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {/if}
        {if $mode ne 'view'}<td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>{/if}
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
		<td>{$group.LABEL}:{if $mode ne 'view'} <span  class="required">*</span>{/if}</td>
		{if $mode ne 'edit'}
			<td>{$group.INPUT}</td>
		{else}
			<td>{$GROUP}</td>
		{/if}
        <td width="50%"></td>
      </tr>
      <tr>
		<td>{$description.LABEL}:{if $mode ne 'view'} <span  class="required">*</span>{/if}</td>
		<td>{$description.INPUT}</td>
        <td width="50%"></td>
      </tr>
    </table>
  </td>
</tr>
</table>
<input type="hidden" name="id" value="{$id_group}">
</form>
