<div>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          {if $userLevel eq 'superadmin'}
            <input class="button" type="submit" name="save_did" value="{$SAVE}" >
          {/if}
          <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
        {if $mode ne 'view'}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
        {/if}
     </tr>
   </table>
</div>
<div style="padding-left: 8px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="5px" class="tabForm">
        <tr>
            <td width="10%" nowrap>{$Organization}: </td>
            <td colspam="3"><span style="font-weight:bold; font-size:14px">{$DOMAIN}</span></td>
        </tr>
        {if $mode eq 'view'}
            <tr>
                <td width="10%" nowrap>{$did.LABEL}: </td>
                <td width="20%"> {$DIDS} </td>
            </tr>
        {else}
            <tr>
                <td width="10%" valign="top" nowrap>{$did.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
                <td width="20%" valign="top">{$did.INPUT}</td>
                <td rowspan="2">
                    <input class="button" name="remove" id="remove" value="<<" onclick="javascript:quitar_did();" type="button">
                    <select name="arr_did" size="4" id="arr_did" style="width: 120px;">
                    </select>
                    <input type="hidden" id="select_dids" name="select_dids" value={$DIDS}>
                </td>
            </tr>
        {/if}
    </table>
</div>
<input class="button" type="hidden" name="id" value="{$ID}" />
{literal}
<script type="text/javascript">
$(document).ready(function(){
    mostrar_select_dids();
});
</script>
{/literal}