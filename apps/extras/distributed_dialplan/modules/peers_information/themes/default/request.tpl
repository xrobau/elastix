<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td align="left">
	    <input class="button" type="submit" name="request" value="{$Request}" style="cursor:pointer;">&nbsp;&nbsp;
	    <input class="button" type="submit" name="show" value="{$Cancel}" style="cursor:pointer;">
	</td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td align="left" width="250px"><b>{$ip.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$ip.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$secret.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$secret.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$comment_request.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$comment_request.INPUT}</td>
    </tr>
</table>
