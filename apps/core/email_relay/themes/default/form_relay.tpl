<form method='POST' action="?menu=email_relay">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$EMAIL_RELAY}</td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">

  <tr>
    <td><i>{$EMAIL_RELAY_MSG}</i></td>
    <td>
       <textarea name='redes_relay' cols='40' rows='8'>{$RELAY_CONTENT}</textarea>
    </td>
  </tr>
  <tr>
   <td></td>
    <td align='left'>
      <input type='submit' name='update_relay' value='{$APPLY_CHANGES}'>
    </td>
  </tr>
 </table>
    </td>
  </tr>
</table>
</form>