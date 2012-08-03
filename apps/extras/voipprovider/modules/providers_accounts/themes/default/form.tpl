
<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        {if $mode eq 'input'}
        <td align="left">
            <input class="button" type="submit" name="save_new" value="{$SAVE}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {elseif $mode eq 'view'}
        <td align="left">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {elseif $mode eq 'edit'}
        <td align="left">
            <input class="button" type="submit" name="save_edit" value="{$EDIT}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {/if}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>


<table class="tabForm" style="font-size: 16px; height: 400px" width="100%">
    <tr>
    <td id="header_detail" valign = 'top'>
        <div align = "right" class = "sombreado">{$advanced.LABEL} {$advanced.INPUT}</div>
        <fieldset class="fielform">
            <legend class="sombreado">{$General_Setting}</legend>
            <table border="0" width="100%" cellspacing="0" cellpadding="8" >
                <tr class="letra12">
                    <td align="left"><b>{$type_provider_voip.LABEL}: <span  class="required">*</span></b></td>
                    <td>{$type_provider_voip.INPUT}</td>
                </tr>
				{if $mode eq 'edit'}
				<tr class="letra12">
                    <td align="left"><b>{$status.LABEL}: <span  class="required">*</span></b></td>
                    <td align="left">{$status.INPUT}</td>
                </tr>
				{/if}
                <tr class="letra12">
                    <td align="left"><b>{$account_name.LABEL}: <span  class="required">*</span></b></td>
                    <td align="left">{$account_name.INPUT}</td>
                </tr>
                <tr class="letra12">
                    <td align="left"><b>{$username.LABEL}: <span  class="required">*</span></b></td>
                    <td align="left">{$username.INPUT}</td>
                </tr>
                <tr class="letra12">
                    <td align="left"><b>{$secret.LABEL}: <span  class="required">*</span></b></td>
                    <td align="left">{$secret.INPUT}</td>
                </tr>
                 <tr class="letra12">
                    <td align="left"><b>{$callerID.LABEL}:</b></td>
                    <td align="left">{$callerID.INPUT}</td>
                </tr>
            </table>
        </fieldset>
    </td>

    <td id="detail">
        <fieldset class="fielform" id="advanced_options" style = "display:none;">
            <legend class="sombreado">{$PEER_Details}</legend>
            <table border="0" width="100%" id="formContainer" align="center" cellspacing="0" cellpadding="8">
                <tr class="letra12">
                    <td align="left"><b> {$type.LABEL}:</b><span  class="required">*</span></td>
                    <td align="left">{$type.INPUT}</td>
                    <td align="left"><b><label> {$technology.LABEL}:</label></b></td>
                    <td >{$technology.INPUT}</td>
                </tr>
                <tr class="letra12">
                    <td align="left"><b><label> {$qualify.LABEL}:</label></b></td>
                    <td >{$qualify.INPUT}</td>
                    <td align="left"><b><label> {$canreinvite.LABEL}:</label></b></td>
                    <td >{$canreinvite.INPUT}</td>
                </tr>
                <tr class="letra12">
                    <td align="left"><b><label> {$insecure.LABEL}:</label></b></td>
                    <td >{$insecure.INPUT}</td>
                    <td align="left"><b><label> {$sendrpid.LABEL}:</label></b></td>
                    <td >{$sendrpid.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$dtmfmode.LABEL}:</label></b></td>
                    <td >{$dtmfmode.INPUT}</td>
                    <td align="left"><b><label> {$trustrpid.LABEL}:</label></b></td>
                    <td >{$trustrpid.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$host.LABEL}:</label><span class="required">*</span></b></td>
                    <td colspan="3">{$host.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$context.LABEL}:</label><span class="required">*</span></b></td>
                    <td colspan="3">{$context.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$disallow.LABEL}:</label></b></td>
                    <td colspan="3">{$disallow.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$allow.LABEL}:</label></b></td>
                    <td colspan="3">{$allow.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$fromuser.LABEL}:</label></b></td>
                    <td colspan="3">{$fromuser.INPUT}</td>
                </tr>
                <tr class="letra12" >
                    <td align="left"><b><label> {$fromdomain.LABEL}:</label></b></td>
                    <td colspan="3">{$fromdomain.INPUT}</td>
                </tr>
            </table>
        </fieldset>
    </td>
    </tr>
</table>


<input class="button" type="hidden" name="id" value="{$ID}" />
<input class="button" type="hidden" name="idTrunk" value="{$ID_TRUNK}" />
<input class="button" type="hidden" id="module_name" name="module_name" value="{$Module_name}" />

{if $mode eq 'edit'}
<input class="button" type="hidden" name="editStatus" id="editStatus" value="on" />
{else}
<input class="button" type="hidden" name="editStatus" id="editStatus" value="off" />
{/if}