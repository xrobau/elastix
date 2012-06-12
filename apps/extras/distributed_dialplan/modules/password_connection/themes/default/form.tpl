<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle" colspan='2'>&nbsp;&nbsp;<img src="{$IMG}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
    </tr>
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
        <td align="left">{$keyword.INPUT}&nbsp;&nbsp;<input type="button" class="button" id="getpass" value="{$GET_PASS}" /></td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$emails.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$emails.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$message.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$message.INPUT}</td>
    </tr>
</table>
<input class="button" type="hidden" name="id" value="{$ID}" />