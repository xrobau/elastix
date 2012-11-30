<form style='margin-bottom:0;' method='POST' action='?menu=email_accounts'>
  <table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
      <tr class="letra12">
        <td width="5%" align="right">{$domain.LABEL}: </td>
        <td width="12%" align="left" nowrap>{$domain.INPUT}</td>
{if $LINK <> ''}
        <td align="left"><a href="{$LINK}">{$EXPORT}</a></td>
{/if}
      </tr>
   </table>
</form>
          