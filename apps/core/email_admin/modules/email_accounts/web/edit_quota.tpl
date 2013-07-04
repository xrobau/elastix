<form method="POST">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
	  <input class="button" type="submit" name="edit_quota" value="{$EDIT}">
	  <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
      </tr>
    </table>
  </td>
</tr>
<tr>
  <td>
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
	  <tr class="letra12">
	      <td width="12%"><b>{$quota.LABEL}:</b></td>
	      <td>{$quota.INPUT}</td>
	  </tr>
      </table>
  </td>
</tr>
</table>
<input type="hidden" name="old_quota" value="{$old_quota}">
<input type="hidden" name="username" value="{$username}">
</form>