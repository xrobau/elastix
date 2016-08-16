<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td align="left">
	    {if $activate eq 'enabled'}
	    <input class="button" id="actionVacation" type="submit" name="disactivate" value="{$DISACTIVATE_MESSAGE}">
	    {else}
	    <input class="button" id="actionVacation" type="submit" name="activate" value="{$ACTIVATE_MESSAGE}">
	    {/if}
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" width="100%" >
    <tr class="letra12">
        <td align="left" width="10%"><b>{$DATE}: <span  class="required">*</span></b></td>
        <td align="left"><b>{$FROM}</b>&nbsp;&nbsp;&nbsp;&nbsp;{$ini_date.INPUT}&nbsp;&nbsp;&nbsp;&nbsp;<b>{$TO}</b>&nbsp;&nbsp;&nbsp;&nbsp;{$end_date.INPUT}&nbsp;&nbsp;&nbsp;&nbsp;<b><span id="num_days">{$num_days}</span>&nbsp;{$days}</b></td>
    </tr>
    <tr class="letra12">
        <td align="left" width="10%"><b>{$email.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$email.INPUT}&nbsp;&nbsp;{$link_emails}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$subject.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$subject.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$body.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$body.INPUT}</td>
    </tr>
</table>
<input class="button" type="hidden" name="id" id="id" value="{$ID}" />
<input class="button" type="hidden" name="title_popup" id="title_popup" value="{$title_popup}" />
<input type="hidden" id="lblDisactivate" name="lblDisactivate" value="{$DISACTIVATE_MESSAGE}" />
<input type="hidden" id="lblActivate" name="lblActivate" value="{$ACTIVATE_MESSAGE}" />