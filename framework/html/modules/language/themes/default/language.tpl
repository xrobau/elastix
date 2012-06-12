<form method="POST">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<td width="15%">{$language.LABEL}:</td>
	<td width="35%">{$language.INPUT}</td>
        <td>
        {if $conectiondb}
        <input class="button" type="submit" name="save_language" value="{$CAMBIAR}" >
        {else}
        {$MSG_ERROR}
        {/if}
        </td>
      </tr>
    </table>
  </td>
</tr>
</table>
</form>