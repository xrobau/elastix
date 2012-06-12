<form method="POST" action="?menu=billing_sendmail">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          {if $mode eq 'input'}
          <input class="button" type="submit" name="submit_save_rate" value="{$SAVE}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {elseif $mode eq 'edit'}
          <input class="button" type="submit" name="submit_apply_changes" value="{$APPLY_CHANGES}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
          {/if}          
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<tr>
	 <td>{$name.LABEL}:<span class="required">*</span></td>
         <td>{$name.INPUT}</td>
         <td>{$predefined.LABEL}:</td>
         <td>{$predefined.INPUT}</td>
         <td>{$sources_mode.LABEL}:<span class="required">*</span></td>
         <td>{$sources_mode.INPUT}</td>
        </tr>
	<tr>
         <td>{$recipient.LABEL}:<span class="required">*</span></td>
         <td>{$recipient.INPUT}</td>
         <td>{$daysrange.LABEL}:</td>
         <td>{$daysrange.INPUT}</td>
	</tr>
       </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<tr>
          <td width="20%">{$sources.LABEL}:<span class="required">*</span></td>
          <td width="15%">{$minutes.LABEL}:</td>
          <td width="15%">{$hours.LABEL}:</td>
          <td width="15%">{$days.LABEL}:</td>
          <td width="20%">{$months.LABEL}:</td>
          <td width="15%">{$weekdays.LABEL}:</td>
        </tr>
          <td>{$sources.INPUT}</td>
	  <td>{$minutes.INPUT}</td>
	  <td>{$hours.INPUT}</td>
          <td>{$days.INPUT}</td>
          <td>{$months.INPUT}</td>
          <td>{$weekdays.INPUT}</td>
      </tr>
    </table>
  </td>
</tr>
</table>
<input type="hidden" name="id_bill_sendmail" value="{$id_bill_sendmail}">
</form>
