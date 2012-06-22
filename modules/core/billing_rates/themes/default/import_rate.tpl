<form method="POST" action="?menu=billing_rates" enctype="multipart/form-data">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          <input class="button" type="submit" name="submit_import_changes" value="{$SAVE}" >
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<td width="10%">{$importcsv.LABEL}: <span  class="required">*</span></td>
	<td width="90%">{$importcsv.INPUT}</td>
      </tr>
    </table>
  </td>
</tr>
</table>
<input type="hidden" name="id_rate" value="{$id_rate}">
</form>
