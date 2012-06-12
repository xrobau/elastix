<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/bardoc.png" border="0" align="absmiddle">&nbsp;&nbsp;{$Destination_Distribution}</td>
</tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0" class="filterForm"><tr><td>
{$contentFilter}
    </td></tr></table>
    </td>
  </tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<td>
          <p align='center'><img alt="Graphic" src="{$URL_GRAPHIC}" /></p>
        </td>
      </tr>
      {if $mostrarSumario}
      <tr>
	<td>
          <table class="table_data" align="center" cellspacing="0" cellpadding="0">
           <tr class="table_title_row">
            <td align='center'>{$Rate_Name}</td>
            <td align='center'>{$Title_Criteria}</td>
            <td align='center'>%</td>
           </tr>
           {foreach name=outer item=fila from=$results}
           <tr>
             {foreach key=key item=item from=$fila}
               <td class="table_data" align="right">{$item}</td>
             {/foreach}
           </tr>
          {/foreach}
          </table>
        </td>
      </tr>
      {/if}
    </table>
  </td>
</tr>
</table>
