<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td align="left">
            <input class="button" type="submit" name="send" value="{$SEND}">&nbsp;&nbsp;
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td align="left"><b>{$keyword.LABEL}: <span  class="required">*</span></b></td>
        <td align="left" colspan=2>{$keyword.INPUT}&nbsp;&nbsp;<input type="button" class="button" id="getpass" value="{$GET_PASS}" /></td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$emails.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$emails.INPUT}</td>
	<td align="left" style="padding:0 70px; font-style:italic; text-align:justify;"><div style="color:red">{$title_note}</div>{$note}</td>
    </tr>
    <tr class="letra12">
        <td align="left" width="70px"><b>{$message.LABEL}: <span  class="required">*</span></b></td>
        <td align="left" colspan=2>{$message.INPUT}</td>
    </tr>
</table>
<input class="button" type="hidden" name="id" value="{$ID}" />
