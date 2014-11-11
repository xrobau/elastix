<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td align="left">
            <input class="button" type="submit" name="save_newList" value="{$SAVE}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>

<div class="tabForm" style="font-size: 16px" width="100%">
    {if $StatusNew}
    <div id="mailman_detail">	
	<table border="0" width="100%" cellspacing="0" cellpadding="8" >
	    <tr class="letra12">
		<td align="left" width="23%"><b style="color: rgb(227, 83, 50); font-size: 12px; font-family: 'Lucida Console';">{$Mailman_Setting}</b></td>
	    </tr>
	    <tr class="letra12">
		<td align="left"><b>{$emailmailman.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$emailmailman.INPUT}</td>
	    </tr>

	    <tr class="letra12">
		<td align="left"><b>{$passwdmailman.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$passwdmailman.INPUT}</td>
	    </tr>
	</table>
    </div>
    {/if}

    <div id="list_detail">
	<table border="0" width="100%" cellspacing="0" cellpadding="8" >
	    <tr class="letra12">
		<td align="left"><b style="color: rgb(227, 83, 50); font-size: 12px; font-family: 'Lucida Console';">{$List_Setting}</b></td>
	    </tr>
	    <tr class="letra12">
		<td align="left" width="23%"><b>{$domain.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$domain.INPUT}</td>
	    </tr>

	    <tr class="letra12">
		<td align="left"><b>{$namelist.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$namelist.INPUT}</td>
	    </tr>

	    <tr class="letra12">
		<td align="left"><b>{$emailadmin.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$emailadmin.INPUT}</td>
	    </tr>

	    <tr class="letra12">
		<td align="left"><b>{$password.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$password.INPUT}</td>
	    </tr>

	    <tr class="letra12">
		<td align="left"><b>{$passwordconfirm.LABEL}: <span  class="required">*</span></b></td>
		<td align="left">{$passwordconfirm.INPUT}</td>
	    </tr> 
	</table>
    </div>
</div>
