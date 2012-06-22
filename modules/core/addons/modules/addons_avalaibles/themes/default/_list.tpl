
<table width="{$width}" align="center" border="0" cellpadding="0" cellspacing="0">
  <tr class="moduleTitle">
	<td></td>
	<td width="10%" align="right">{$filter_field.INPUT}</td>
  </tr>
  <tr>
    <td colspan="2">
      <table class="table_data" align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
        <tr class="table_navigation_row_up_addon">
          <td colspan="2" class="table_navigation_row">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="table_navigation_text">
              <tr>
                <td align="left" width="200px">
                    <input type="text" value="{$ADDONS_SEARCH}" name="addons_search" id="search" onKeyPress="return enterEvent(event,'{$module_name}')"/>
                    <a href="javascript:void()" onclick="_search('{$module_name}')">
                        <img alt="" src='modules/{$module_name}/images/lupa.png' align='absmiddle' border='0' width='15' height='15' />
                    </a>
                </td>
                <td align="left" id="msg_status">
                </td>
                <td align="right"> 
                  {if $pagingShow}
                    {if $start<=1}
                    <img
                    src='images/start_off.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11' />&nbsp;{$lblStart}&nbsp;&nbsp;<img 
                    src='images/previous_off.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11' />
                    {else}
                    <a href="{$url}&nav=start&start={$start}"><img
                    src='images/start.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11' /></a>&nbsp;{$lblStart}&nbsp;&nbsp;<a href="{$url}&nav=previous&start={$start}"><img 
                    src='images/previous.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11' /></a>
                    {/if}
                    &nbsp;{$lblPrevious}&nbsp;<span 
                    class='pageNumbers'>({$start} - {$end} of {$total})</span>&nbsp;{$lblNext}&nbsp;
                    {if $end==$total}
                    <img 
                    src='images/next_off.gif'
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11' />&nbsp;{$lblEnd}&nbsp;<img 
                    src='images/end_off.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11' />
                    {else}
                    <a href="{$url}&nav=next&start={$start}"><img
                    src='images/next.gif' 
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11' /></a>&nbsp;{$lblEnd}&nbsp;<a 
                    href="{$url}&nav=end&start={$start}"><img 
                    src='images/end.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11' /></a>
                    {/if}
                  {/if}
                </td>
              </tr>
            </table>
          </td>
        </tr>
	{counter start=0 skip=1 print=false assign=cnt}
        {foreach from=$arrData key=k item=data name=filas}
        	{*{if $data[0] eq $instalados || $data[0] eq $no_instalados}*}
		  <!--<tr style="background-color:#fafafa;" class="backgroundTableTitle">
			<td class="table_dataAddonTitle" colspan="2">{$data[0]}</td>
		      </tr>-->
	  	{*{else}*}
		  {if $cnt%2==0}
      	  <tr style="background-color:#fafafa;" class="backgroundTable">
	    		{/if}
	      {if $data[0] eq 'relleno'}
		  <td class="table_data" width="50%">
			&nbsp;&nbsp;
		  </td>
	      {else}
		{if $data[0] neq ''}
		  <td class="table_data" width="50%">
		      {$data[0]}
		  </td>	    
		{/if}
	      {/if}
	    {if ($cnt+1)%2==0}
	      </tr>
	    {/if}
	    {counter}
	  {*{/if}*}
        {/foreach}
        <tr class="table_navigation_row_down_addon">
          <td colspan="2" class="table_navigation_row_down_addon">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="table_navigation_text">
              <tr>
                <td align="left">&nbsp;</td>
                <td align="right" colspan="3">
                  {if $pagingShow}
                    {if $start<=1}
                    <img
                    src='images/start_off.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11' />&nbsp;{$lblStart}&nbsp;&nbsp;<img
                    src='images/previous_off.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11' />
                    {else}
                    <a href="{$url}&nav=start&start={$start}"><img
                    src='images/start.gif' alt='{$lblStart}' align='absmiddle'
                    border='0' width='13' height='11' /></a>&nbsp;{$lblStart}&nbsp;&nbsp;<a href="{$url}&nav=previous&start={$start}"><img
                    src='images/previous.gif' alt='{$lblPrevious}' align='absmiddle' border='0' width='8' height='11' /></a>
                    {/if}
                    &nbsp;{$lblPrevious}&nbsp;<span
                    class='pageNumbers'>({$start} - {$end} of {$total})</span>&nbsp;{$lblNext}&nbsp;
                    {if $end==$total}
                    <img
                    src='images/next_off.gif'
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11' />&nbsp;{$lblEnd}&nbsp;<img
                    src='images/end_off.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11' />
                    {else}
                    <a href="{$url}&nav=next&start={$start}"><img
                    src='images/next.gif'
                    alt='{$lblNext}' align='absmiddle' border='0' width='8' height='11' /></a>&nbsp;{$lblEnd}&nbsp;<a
                    href="{$url}&nav=end&start={$start}"><img
                    src='images/end.gif' alt='{$lblEnd}' align='absmiddle' border='0' width='13' height='11' /></a>
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
<input type="hidden" name="installing" id="installing" value="0" />
<input type="text" style="display:none;" name="action_install" id="action_install" value="none" />
<input type="hidden" name="link_tmp" id="link_tmp" value="" />
<input type="hidden" name="textDownloading" id="textDownloading" value="{$textDownloading}" />
<input type="hidden" name="textRemoving" id="textRemoving" value="{$textRemoving}" />
<input type="hidden" name="textInstalling" id="textInstalling" value="{$textInstalling}" />
<input type="hidden" name="uninstallText" id="uninstallText" value="{$uninstall}" />
<input type="hidden" name="installText" id="installText" value="{$install}" />
<input type="hidden" name="tryItText" id="tryItText" value="{$tryItText}" />
<input type="hidden" name="tryItAction" id="tryItAction" value="" />
<input type="hidden" name="uninstallRpm" id="uninstallRpm" value="" />
<input type="hidden" name="actionToDo" id="actionToDo" value="" />
<input type="hidden" name="textDaemonOff" id="textDaemonOff" value="{$daemonOff}" />
<input type="hidden" name="textObservation" id="textObservation" value="{$textObservation}" />
<input type="hidden" name="errorDetails" id="errorDetails" value="{$error_details}" />
<input type="hidden" name="iniDownloading" id="iniDownloading" value="{$iniDownloading}" />
