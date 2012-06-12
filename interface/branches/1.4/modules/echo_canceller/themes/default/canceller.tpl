<form method="POST">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/kfaxview.png" align="absmiddle" border="0">&nbsp;&nbsp;{$title}</td>
</tr>
{if $ERROR_MSG ne ""}
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">&nbsp;{$ERROR_MSG}
        </td>
     </tr>
   </table>
  </td>
</tr>
{/if}
<tr>
  <td>
    <table class="tabForm" border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
    <tbody>
    <tr>
        <td width="4%" align="left"><b>{$status_label}:</b></td>
        <td width="4%" align="left">{$status}</td>
        <td align="left" width="30%"><input class="button" name="action" value="{$action}" type="submit"></td>
    </tr>
{if $STATS ne ""}
    <tr>       
        <td width="4%" valign="top" align="left"><b>{$info}:</b></td>
        <td align="left" valign="top" colspan="2"><textarea cols="60" rows="15" name="archivo_textarea" style='border-style:none;overflow:
hidden;' readonly>{$STATS}</textarea>
        </td>
    </tr>    
{/if}
    </tbody>        
    </table>
  </td>
</tr>
</table>
<input type="hidden" name="accion" value="{$accion}">
</form>
