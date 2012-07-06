<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        {if $mode eq 'input'}
        <td align="left">
            <input class="button" type="submit" name="save_new" value="{$SAVE}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {elseif $mode eq 'view'}
        <td align="left">
            <input class="button" type="submit" name="edit" value="{$EDIT}">
            {if $level_user eq 'super_admin'}
            <input class="button" type="submit" name="delete" value="{$DELETE}" onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
            {/if}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {elseif $mode eq 'edit'}
        <td align="left">
            <input class="button" type="submit" name="save_edit" value="{$APLICAR_CAMBIOS}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {/if}
		{if $mode ne 'view'}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
		{/if}
    </tr>
</table>
<table class="tabForm" style="font-size: 14px" width="100%" cellpadding="4" align="center">
    <tr>
        <td width="14%" align="left">{$name.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td width="31%" align="left">{$name.INPUT}</td>
		{if !$isMainOrg}
			{if $edit_entity}
				<td width="19%" align="left">{$domain.LABEL}: <b>{if $mode ne 'view'} <span  class="required">*</span>{/if}</b></td>
				<td width="31%" align="left">{$domain_name}</td>
			{else}
				<td width="19%" align="left">{$domain.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
				<td width="31%" align="left">{$domain.INPUT}</td>
			{/if}
		{else}
			<td width="19%" align="left">{$email_contact.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
			<td width="31%" align="left">{$email_contact.INPUT} </td>
		{/if}
    </tr>
    <tr>
        <td align="left">{$country.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td align="left">{$country.INPUT}</td>
        <td align="left">{$city.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td align="left">{$city.INPUT}</td>
    </tr>
    <tr>
        <td align="left">{$address.LABEL}: </td>
        <td align="left" colspan="3" width="74%">{$address.INPUT}</td>
    </tr>
	{if !$isMainOrg}
    <tr>
        <td align="left">{$country_code.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td align="left">{$country_code.INPUT} </td>
        <td align="left">{$area_code.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td align="left">{$area_code.INPUT} </td>
    </tr>
	{/if}
	{if !$isMainOrg}
    <tr>
		<td align="left">{$email_contact.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td align="left">{$email_contact.INPUT} </td>
        <td align="left">{$quota.LABEL}: <b>{if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
        <td align="left">{$quota.INPUT} </td>
    </tr>
	{/if}
</table>
<input class="button" type="hidden" name="id" value="{$ID}" />