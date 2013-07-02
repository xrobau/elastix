<form method="POST" enctype='multipart/form-data' action='?menu=email_accounts'>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
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
          <input class="button" type="submit" name="delete" value="{$DELETE}"  onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
	  <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {/if}    
	{if $mode ne 'view'}
	    <td id="required_field" align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
	{/if}
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      {if $mode eq 'input'}
	  <tr class="letra12">
	      <td colspan='2'>
		  <input type="radio" name="option_create_account" id="by_account" value="by_account" {$check_record} onclick="Activate_Option()" />
		  {$account} &nbsp;&nbsp;&nbsp;
		  <input type="radio" name="option_create_account" id="upload_file" value="by_file" {$check_file} onclick="Activate_Option()" />
		  {$file_upload}
	      </td>
	  </tr>
      {/if}

      <tr id="save_by_account1" {$DISPLAY_SAVE_ACCOUNT}>
	{if $mode eq 'input'}
		<td width="15%"><b>{$address.LABEL}:</b> <span  class="required">*</span></td>
		<td width="35%">{$address.INPUT}{$domain_name}</td>
	{else}
		<td width="15%"><b>{$account_name_label}:</b> {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
		<td width="35%">{$username}</td>
	{/if}
	    <td width="15%"><b>{$quota.LABEL}:</b> {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
	    <td width="35%">{$quota.INPUT}</td>
      </tr>
      <tr id="save_by_account2" {$DISPLAY_SAVE_ACCOUNT}>
		<td width="20%"><b>{$password1.LABEL}:</b> {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
	    <td width="30%">{$password1.INPUT}</td>
		<td width="20%"><b>{$password2.LABEL}:</b> {if $mode ne 'view'}<span class="required">*</span>{/if}</td>
		<td width="30%">{$password2.INPUT}</td>
      </tr>

      <tr id="save_by_file" {$DISPLAY_FILE_UPLOAD}>
	  <td align="left" width='13%'><b>{$file_Label}</b></td>
	  <td align="left" width='37%'>
	      <input name="file_accounts" id="file_accounts" type="file" value="{$file_accounts_name}" size='30' />
	  </td>
	  <td align="left" width="55%"><i>{$INFO}</i></td>
      </tr>
    </table>
    </td>
  </tr>
</table>
<input type="hidden" name="id_domain" value="{$id_domain}">
<input type="hidden" name="domain" value="{$id_domain}">
<input type="hidden" name="username" value="{$username}">
<input type="hidden" name="old_quota" value="{$old_quota}">
<input type="hidden" name="domain_name" value="{$domainName}">
</form>