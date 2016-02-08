<form method="post" enctype="multipart/form-data">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
{if !$FRAMEWORK_TIENE_TITULO_MODULO}
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle" />&nbsp;&nbsp;{$title}</td>
</tr>
{/if}
<tr>
  <td>
    <table width="100%" valign="top" cellpadding="4" cellspacing="0" border="0">
      <tr>
          {if $mode eq 'input'}
        <td align="left">
          <input class="button" type="submit" name="save" value="{$SAVE}" onclick="return enviar_datos();" />
          <input class="button" type="submit" name="cancel" value="{$CANCEL}" />
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
          {elseif $mode eq 'edit'}
        <td align="left">
          <input class="button" type="submit" name="apply_changes" value="{$APPLY_CHANGES}" onclick="return enviar_datos();" />
          <input class="button" type="submit" name="cancel" value="{$CANCEL}" />
        </td>
          {else}
{* Removido para eliminar xajax *}
          {/if}
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="900" valign="top" border="0" cellspacing="0" cellpadding="0" class="tabForm">

      <tr>
        <td align='right'>{$encoding.LABEL}: {if $mode eq 'input'}<span  class="required">*</span>{/if}</td>
        <td  colspan='4'>{$encoding.INPUT}</td>
      </tr>
      <tr>
        <td align='right'>{$phonefile.LABEL}: {if $mode eq 'input'}<span  class="required">*</span>{/if}</td>
        <td  colspan='4'>{$phonefile.INPUT}</td>
      </tr>

    </table>
  </td>
</tr>
</table>
<input type="hidden" name="id_campaign" id='id_campaign' value="{$id_campaign}" />
</form>
