<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$IMG}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
        <td></td>
    </tr>
    <tr class="letra12">
        <td align="left">
        {if $MODE ne "view"}
         <input class="button" type="submit" name="accept_request" value="{$ACEPT}">&nbsp;
         <input class="button" type="submit" name="reject_request" value="{$REJECT}">&nbsp;
        {/if}
        {if $OPCION eq "yes"}
        <input class="button" type="submit" name="disconnect" value="{$DISCONNECT}">
        {/if}  
        <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td width="10%"><b>{$host.LABEL}: </b></td>
        <td width="15%">{$host.INPUT}</td>
        <td width="10%"><b>{$mac.LABEL}: </b></td>
        <td width="15%">{$mac.INPUT}</td>
        <td width="25%"></td>
        <td width="25%"></td>
    </tr>
    <tr class="letra12">
        <td width="10%"><b>{$company.LABEL}: </b></td>
        <td width="15%">{$company.INPUT}</td>
        <td width="10%" vAlign="top"><b>{$inkey.LABEL}: </b></td>
        <td width="15%" vAlign="top">{$inkey.INPUT}</td>
        <td width="25%"></td>
        <td width="25%"></td>

    </tr>

    <tr class="letra12">
        <td width="10%" vAlign="top"><b>{$comment.LABEL}: </b></td>
        <td width="15%">{$comment.INPUT}</td>
        <td width="10%" vAlign="top"><b>{$outkey.LABEL}: </b></td>
        <td width="15%" vAlign="top">{$outkey.INPUT}</td>
        <td width="25%"></td>
        <td width="25%"></td>

    </tr>
<input type="hidden" name="peerId" value="{$peerId}">
<input type="hidden" name="peerMac" value="{$peerMac}">
<input type="hidden" name="ipAsk" value="{$ipAsk}">
</table>
