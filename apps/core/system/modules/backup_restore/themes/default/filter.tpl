<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
  <td>
    <input class="button" type="submit" name="backup" value="{$BACKUP}">
  </td>
  <td align="right">
    <input class="button" type="submit" name="automatic"  value="{$AUTOMATIC}">
  </td>
  <td>
    <select name="time">
        <option value="DISABLED" {$SEL_DISABLED}>{$DISABLED}</option>
        <option value="DAILY" {$SEL_DAILY}>{$DAILY}</option>
        <option value="MONTHLY" {$SEL_MONTHLY}>{$MONTHLY}</option>
        <option value="WEEKLY" {$SEL_WEEKLY}>{$WEEKLY}</option>
    </select>
  </td>
  <td align="right">
    <input class="button" type="submit" name="view_form_FTP" value="{$FTP_BACKUP}">
  </td>
<!--
  <td>
    {$FILE_UPLOAD}: <input type="file" name="file_upload">
    <input class="button" type="submit" name="upload" value="{$UPLOAD}">
  </td>
-->
</tr>
</table>
