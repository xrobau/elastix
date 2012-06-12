<table width="100%" border=0 class="tabForm" height="400">
  <tr>
    <td valign='top'>
          {foreach key=indice item=campo from=$FORMULARIO}
               <table cellpadding="2" cellspacing="0" width="100%" border="0" id="{$campo.ID_FORM}">
            {if $campo.TYPE eq 'LABEL'}
                <tr>
                    <td height='15' colspan='2' width='100%'><center>{$campo.INPUT} {$campo.ID_FORM}</center></td>
                </tr>
            {else} 
                {if $campo.TYPE eq 'DATE'}
                    <tr>
                        <td height='15' width='20%' valign="top">
                            <span style='color:#666666; FONT-SIZE: 12px;'>
                                {$campo.TAG}
                            </span>
                        </td>
                        <td height='15'>
                            {$campo.INPUT}{$campo.ID_FIELD}
                        </td>
                    </tr>
                {else}
                        <tr>
                            <td height='15' width='15%' valign="top"><span style='color:#666666; FONT-SIZE: 12px;'>{$campo.TAG}</span></td>
                            <td height='15' width='85%'>{$campo.INPUT} {$campo.ID_FIELD}</td>
                        </tr>
                {/if}
            {/if}
          {/foreach}
          </table>
    </td>
  </tr>
</table>




