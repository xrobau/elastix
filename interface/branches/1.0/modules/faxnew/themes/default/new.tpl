<form method="POST">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/kfaxview.png" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          {if $mode eq 'input'}
          <input class="button" type="submit" name="save" value="{$SAVE}">
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {elseif $mode eq 'edit'}
          <input class="button" type="submit" name="apply_changes" value="{$APPLY_CHANGES}">
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
	<td width="20%">{$extension.LABEL}: <span  class="required">*</span></td>
	<td width="30%">{$extension.INPUT}</td>
      </tr>
      <tr>
	<td>{$email.LABEL}: <span  class="required">*</span></td>
	<td>{$email.INPUT}</td>
	<td width="20%">{$secret.LABEL}: <span class="required">*</span></td>
	<td width="30%">{$secret.INPUT}</td>
      </tr>
      <tr>
	<td>{$clid_name.LABEL}:</td>
	<td>{$clid_name.INPUT}</td>
	<td>{$country_code.LABEL}: <span class="required">*</span></td>
	<td>{$country_code.INPUT}</td>
	</tr>
	<tr>
	<td>{$clid_number.LABEL}: </td>
	<td>{$clid_number.INPUT}</td>
	<td>{$area_code.LABEL}: <span class="required">*</span></td>
	<td>{$area_code.INPUT}</td>
	</tr>
      </table>
    </td>
  </tr>
</table>
<input type="hidden" name="id_fax" value="{$id_fax}">
{if $mode eq 'edit'}
{$port.INPUT}
{$dev_id.INPUT}
{/if}

</form>
