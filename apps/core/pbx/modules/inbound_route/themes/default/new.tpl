<div>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
        {if $mode eq 'input'}
            <input class="button" type="submit" name="save_new" value="{$SAVE}" >
            {elseif $mode eq 'edit'}
            <input class="button" type="submit" name="save_edit" value="{$APPLY_CHANGES}">
            {elseif $userLevel eq 'admin'}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
            <input class="button" type="submit" name="delete" value="{$DELETE}"  onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
        {/if}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {if $mode ne 'view'}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
        {/if}
     </tr>
   </table>
</div>
<table width="100%" border="0" cellspacing="0" cellpadding="5px" class="tabForm">
    <tr class="extension">
        <td width="20%" nowrap>{$description.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
        <td width="30%">{$description.INPUT}</td>
    </tr>
    <tr class="extension">
        <td width="20%" nowrap>{$did_number.LABEL}: </td>
        <td width="20%">{$did_number.INPUT}</td>
        <td>{$cid_number.LABEL}</td>
        <td>{$cid_number.INPUT}</td> 
    </tr>
    <tr><th>{$OPTIONS}</th></tr>
        <tr class="extension">
        <td width="20%" nowrap>{$alertinfo.LABEL}:</td>
        <td width="30%">{$alertinfo.INPUT}</td>
        <td width="20%" nowrap>{$cid_prefix.LABEL}: </td>
        <td width="20%">{$cid_prefix.INPUT}</td>
    </tr>
    <tr class="extension">
        <td width="20%" nowrap>{$moh.LABEL}:</td>
        <td width="30%">{$moh.INPUT}</td>
        <td width="20%" nowrap>{$ringnig.LABEL}: </td>
        <td width="20%">{$ringnig.INPUT}</td>
    </tr>
    <tr class="extension">
        <td width="20%" nowrap>{$delay_answer.LABEL}:</td>
        <td width="30%">{$delay_answer.INPUT}</td>
    </tr>
    <tr><th>{$PRIVACY}</th></tr>
    <tr class="extension">
        <td nowrap>{$primanager.LABEL}:</td>
        <td >{$primanager.INPUT}</td>
    </tr>
    <tr class="extension">
        <td class="privacy">{$max_attempt.LABEL}:</td>
        <td class="privacy">{$max_attempt.INPUT}</td>
        <td class="privacy">{$min_length.LABEL}: </td>
        <td class="privacy">{$min_length.INPUT}</td>
    </tr>
    <tr><th>{$LANGUAGE}</th></tr>       
    <tr class="extension">
        <td>{$language.LABEL}:</td>
        <td>{$language.INPUT}</td>
    </tr>
    <tr><th>{$SETDESTINATION}</th></tr>       
    <tr class="extension">
        <td>{$goto.LABEL}:</td>
        <td>{$goto.INPUT} {$destination.INPUT}</td>
    </tr>
    <tr><td></td></tr>
</table>
<input type="hidden" name="id_inbound" id="id_inbound" value="{$id_inbound}">

{literal}
<style type="text/css">
.extension td, {
	padding-left: 12px;
}	
</style>
{/literal}
