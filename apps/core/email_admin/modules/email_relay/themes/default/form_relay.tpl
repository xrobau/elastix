<form method='POST' action="?menu=email_relay">
 <table width="99%" border="0" cellspacing="0" cellpadding="0" align="center"  class="tabForm">
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
</form>