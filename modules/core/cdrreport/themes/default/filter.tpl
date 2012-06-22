<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr class="letra12">
        <td width="10%" align="right">{$date_start.LABEL}: <span  class="required">*</span></td>
        <td width="10%" align="left" nowrap>{$date_start.INPUT}</td>
        <td width="10%" align="right">{$field_pattern.LABEL}: </td>
        <td width="10%" align="left" nowrap>{$field_name.INPUT}&nbsp;{$field_pattern.INPUT}</td>
        <td width="10%" align="center"><input class="button" type="submit" name="filter" value="{$Filter}" /></td>
        <td width="10%" align="center"><input class="button" type="submit" name="delete" value="{$Delete}" onclick="return confirmSubmit('{$Delete_Warning}');" /></td>
      </tr>
      <tr class="letra12">
        <td width="10%" align="right">{$date_end.LABEL}: <span  class="required">*</span></td>
        <td width="10%" align="left" nowrap>{$date_end.INPUT}</td>
        <td width="10%" align="right">{$status.LABEL}: </td>
        <td width="10%" align="left" nowrap>{$status.INPUT}</td>
      </tr>
      <tr class="letra12">
        <td width="10%" align="right">{$ringgroup.LABEL}: </td>
        <td width="10%" align="left" nowrap>{$ringgroup.INPUT}</td>
      </tr>
   </table>
  </td>
</tr>
</table>

