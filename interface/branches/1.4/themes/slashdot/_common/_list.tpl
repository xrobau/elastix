<table width="{$width}" align="center" border="0" cellpadding="0" cellspacing="0">
  <tr class="moduleTitle">
    <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
  </tr>
  {if !empty($contentFilter)}
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0" class="filterForm"><tr><td>{$contentFilter}</td></tr></table>
    </td>
  </tr>
  {/if}
  <tr>
    <td>
      <table class="table_data" align="center" cellspacing="0" cellpadding="0" width="100%">
        <tr class="table_navigation_row">
          <td colspan="{$numColumns}" class="table_navigation_row">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="table_navigation_text">
              <tr>
                <td align="left">&nbsp;{if $enableExport==true}<a href="{$url}&exportcsv=yes&rawmode=yes"><img src="/images/export.gif" border="0">&nbsp;<font class="letranodec">{$lblExport}</font></a>{/if}</td>
                <td align="right">
                  {if $pagingShow}  
                    {if $start<=1}
                    <img
                    src='/images/start_off.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11'>&nbsp;{$lblStart}&nbsp;&nbsp;<img 
                    src='/images/previous_off.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11'>
                    {else}
                        {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('start',{$start})"
                        {else}
                            <a href="{$url}&nav=start&start={$start}">
                        {/if}
                    <img
                    src='/images/start.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11'></a>&nbsp;{$lblStart}&nbsp;&nbsp;
                        {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('previous',{$start})"
                        {else}
                            <a href="{$url}&nav=previous&start={$start}">
                        {/if}
                    <img 
                    src='/images/previous.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11'></a>
                    {/if}
                    &nbsp;{$lblPrevious}&nbsp;<span 
                    class='pageNumbers'>({$start} - {$end} of {$total})</span>&nbsp;{$lblNext}&nbsp;
                    {if $end==$total}
                    <img 
                    src='/images/next_off.gif'
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11'>&nbsp;{$lblEnd}&nbsp;<img 
                    src='/images/end_off.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11'>
                    {else}
                        {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('next','{$start}')"
                        {else}
                            <a href="{$url}&nav=next&start={$start}">
                        {/if}
                    <img
                    src='/images/next.gif' 
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11'></a>&nbsp;{$lblEnd}&nbsp;
                        {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('end',{$start})"
                        {else}
                            <a href="{$url}&nav=end&start={$start}">
                        {/if}
                    <img 
                    src='/images/end.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11'></a>
                    {/if}
                  {/if}
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr class="table_title_row">
          {section name=columnNum loop=$numColumns start=0 step=1}
          <td class="table_title_row">{$header[$smarty.section.columnNum.index].name}&nbsp;</td>
          {/section}
        </tr>
        {foreach from=$arrData key=k item=data name=filas}
        <tr onMouseOver="this.style.backgroundColor='#f2f2f2';" onMouseOut="this.style.backgroundColor='#ffffff';">
          {if $smarty.foreach.filas.last}
            {section name=columnNum loop=$numColumns start=0 step=1}
            <td class="table_data_last_row">{if $data[$smarty.section.columnNum.index] eq ''}&nbsp;{/if}{$data[$smarty.section.columnNum.index]}</td>
            {/section}
          {else}
            {section name=columnNum loop=$numColumns start=0 step=1}
            <td class="table_data">{if $data[$smarty.section.columnNum.index] eq ''}&nbsp;{/if}{$data[$smarty.section.columnNum.index]}</td>
            {/section}
          {/if}
        </tr>
        {/foreach}
        <tr class="table_navigation_row">
          <td colspan="{$numColumns}" class="table_navigation_row">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="table_navigation_text">
              <tr>
                <td align="left">&nbsp;</td>
                <td align="right">
                  {if $pagingShow}  
                    {if $start<=1}
                    <img
                    src='/images/start_off.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11'>&nbsp;{$lblStart}&nbsp;&nbsp;<img
                    src='/images/previous_off.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11'>
                    {else}
                    {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('start',{$start})"
                        {else}
                            <a href="{$url}&nav=start&start={$start}">
                        {/if}
                    <img
                    src='/images/start.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11'></a>&nbsp;{$lblStart}&nbsp;&nbsp;
                        {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('previous',{$start})"
                        {else}
                            <a href="{$url}&nav=previous&start={$start}">
                        {/if}
                    <img
                    src='/images/previous.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11'></a>
                    {/if}
                    &nbsp;{$lblPrevious}&nbsp;<span
                    class='pageNumbers'>({$start} - {$end} of {$total})</span>&nbsp;{$lblNext}&nbsp;
                    {if $end==$total}
                    <img
                    src='/images/next_off.gif'
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11'>&nbsp;{$lblEnd}&nbsp;<img
                    src='/images/end_off.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11'>
                    {else}
                    {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('next','{$start}')"
                        {else}
                            <a href="{$url}&nav=next&start={$start}">
                        {/if}
                    <img
                    src='/images/next.gif'
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11'></a>&nbsp;{$lblEnd}&nbsp;
                        {if $withAjax eq 1}
                            <a href="javascript:void(0);" onclick="javascript:{$functionName}('end',{$start})"
                        {else}
                            <a href="{$url}&nav=end&start={$start}">
                        {/if}
                    <img
                    src='/images/end.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11'></a>
                    {/if}
                  {/if}
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
