<div align="right" style="padding-right: 4px;">
    {if $mode ne 'view'}
    <span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span>
    {/if}
</div>
<div class="neo-table-header-row">
    <div  class="neo-table-header-row-filter tab">
        <input type="radio" id="tab-1" name="tab-group-1" onclick="radio('tab-1');" checked>
        <label for="tab-1">{$GENERAL}</label>
    </div>
    <div  class="neo-table-header-row-filter tab">
        <input type="radio" id="tab-2" name="tab-group-2" onclick="radio('tab-2');">
        <label for="tab-2">{$RULES}</label>
    </div>
    {if $TECH eq 'SIP' or $TECH eq 'IAX2'}
    <div  class="neo-table-header-row-filter tab">
        <input type="radio" id="tab-3" name="tab-group-3" onclick="radio('tab-3');">
        <label for="tab-3">{$SETTINGS}</label>
    </div>
    {/if}
    <div class="neo-table-header-row-navigation" align="right" style="display: inline-block;">
        {if $userLevel eq 'superadmin'}
            {if $mode eq 'input'}
            <input type="submit" name="save_new" value="{$SAVE}" >
            {elseif $mode eq 'edit'}
            <input type="submit" name="save_edit" value="{$APPLY_CHANGES}">
            {else}
            <input type="submit" name="edit" value="{$EDIT}">
            <input type="submit" name="delete" value="{$DELETE}"  onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
            {/if}
        {/if}
        <input type="submit" name="cancel" value="{$CANCEL}">
    </div>
</div>
<div class="tabs">
    <div class="tab" >
       <div class="content" id="content_tab-1">
        <table width="100%" border="0" cellspacing="0" cellpadding="5px" class="tabForm">
            <tr class="tech">
                <td width="20%" nowrap>{$trunk_name.LABEL}: {if $mode ne 'view'}<span class="required">*</span>{/if}</td>
                <td width="30%">{$trunk_name.INPUT}</td>
            </tr>
            <tr class="tech">
                <td width="20%" nowrap>{$outcid.LABEL}: {if $mode ne 'view'}<span class="required">*</span>{/if}</td>
                <td width="30%">{$outcid.INPUT}</td>
                <td width="20%" nowrap>{$keepcid.LABEL}</td>
                <td width="30%">{$keepcid.INPUT}</td>
            </tr>
            <tr><th>{$SEC_SETTINGS}</th></tr>
            <tr class="tech">
                <td nowrap>{$maxchans.LABEL}</td>
                <td>{$maxchans.INPUT}</td>
                <td nowrap>{$disabled.LABEL}</td>
                <td>{$disabled.INPUT}</td>
            </tr>
            <tr class="tech">
                <td nowrap>{$sec_call_time.LABEL}</td>
                <td>{$sec_call_time.INPUT}</td>
            </tr>
            {if $mode ne 'view' || $SEC_TIME eq 'yes' }
            <tr class="tech sec_call_time">
                <td nowrap>{$maxcalls_time.LABEL}</td>
                <td>{$maxcalls_time.INPUT}</td>
                <td nowrap>{$period_time.LABEL}</td>
                <td>{$period_time.INPUT}</td>
            </tr>
            {/if}
            {if $TECH eq 'DAHDI'}
                <tr><th>{$DAHDI_CHANNEL}</th></tr>
                <tr class="tech">
                    <td nowrap>{$channelid.LABEL}:</td>
                    <td >{$channelid.INPUT}</td>
                </tr>
            {/if}
            <tr><th>{$ORGANIZATION_PERM}</th></tr>
            {if $mode eq 'view'}
                <tr class="tech">
                    <td width="15%" nowrap>{$org.LABEL}: </td>
                    <td width="20%"> {$ORGS} </td>
                    <td ></td>
                </tr>
            {else}
                <tr class="tech">
                    <td width="15%" valign="top" nowrap>{$org.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
                    <td width="10%" valign="top">{$org.INPUT}</td>
                    <td rowspan="2">
                        <input class="button" name="remove" id="remove" value="<<" onclick="javascript:quitar_org();" type="button">
                        <select name="arr_org" size="4" id="arr_org" style="width: 120px;">
                        </select>
                        <input type="hidden" id="select_orgs" name="select_orgs" value={$ORGS}>
                    </td>
                </tr>
            {/if}
         </table>
       </div>       
   </div>
   <div class="tab" >
      <div class="content" id="content_tab-2">
        <table width="100%" border="0" cellspacing="0" cellpadding="5px" class="tabForm" id="destine">
        <thead>
            <tr>
                <th>{$PREPEND}</th>
                <th></th>  
                <th>{$PREFIX}</th>
                <th></th>
                    <th>{$MATCH_PATTERN}</th>
                    <th>{if $mode ne 'view'}<div class="add" style="cursor:pointer; float: left"><img src='modules/ivr/images/add1.png' title='Add'/></div>{/if}</th>
                 </tr>
        </thead>
        {if $mode eq 'view'}
		   {foreach from=$items key=myId item=i}
		      <tr><td align="center">{if $i.3 eq ''}(  ){else}({$i.3}){/if}</td>
			  <td align="center">+</td>
			  <td align="center">{$i.1}</td>
			  <td align="center">|</td>
			  <td align="center">{$i.2}</td>
		      </tr>
		    {/foreach}
        {else}
		 <tr id="test" style="display:none;">
            <td align="center">({$prepend_digit__.INPUT})</td>
            <td align="center">+</td>
            <td align="center">{$pattern_prefix__.INPUT}</td>
            <td align="center">|</td>
            <td align="center">{$pattern_pass__.INPUT}</td>
            <td width="50px">
                <div class='delete' style='float:left; cursor:pointer;'><img src='modules/ivr/images/remove1.png' title='Remove'/></div>     
            </td>
         </tr>
         {foreach from=$items key=myId item=i}
            <input type="hidden" value"{$j++}" />
            <tr class="content-destine" id="{$j}">
                <td align="center" >(<input type="text" name="prepend_digit{$j}" value="{$i.3}" style="width:60px;text-align:center;">)</td>
                <td align="center">+</td>
                <td align="center" ><input type="text" name="pattern_prefix{$j}" value="{$i.1}" style="width:30px;text-align:center;"></td>
                <td align="center">|</td>
                <td align="center" ><input type="text" name="pattern_pass{$j}" value="{$i.2}" style="width:150px;text-align:center;"></td>
                <td width="50px"><div class='delete' style='float:left; cursor:pointer;'><img src='modules/ivr/images/remove1.png' title='Remove'/></div></td>
            </tr>
         {/foreach}
        {/if}
        </table>
     </div>       
   </div>
   {if $TECH eq 'SIP' | $TECH eq 'IAX2'}
   <div class="tab">
      <div class="content" id="content_tab-3">
        <table width="100%" border="0" cellspacing="0" cellpadding="5px" class="tabForm">
            <tr>
                <td style="padding-left: 2px; font-size: 13px; color: #E35332; font-weight: bold;" colspan=4>{$REGISTRATION}</td>
            </tr>
            <tr class="tech">
                <td width="20%" nowrap>{$register.LABEL}:</td>
                <td colspan=3 >{$register.INPUT}</td>
            </tr>
            <tr>
                <td style="padding-left: 2px; font-size: 13px; color: #E35332; font-weight: bold;" colspan=4>{$PEER_Details}</td>
            </tr>
            <tr class="tech">
                <td width="15%" nowrap>{$name.LABEL}: {if $mode eq 'input'}<span  class="required">*</span>{/if}</td>
                {if $mode eq 'edit'}
                    <td width="31%">{$NAME}</td>
                {else}
                    <td width="31%">{$name.INPUT}</td>
                {/if}
            </tr>
            <tr class="tech">
                <td nowrap>{$type.LABEL}: {if $mode eq 'input'}<span class="required">*</span>{/if}</td>
                <td>{$type.INPUT}</td>
                <td width="21%" nowrap>{$secret.LABEL}: {if $mode eq 'input'}<span class="required">*</span>{/if}</td>
                <td>{$secret.INPUT}</td>
            </tr>
            <tr class="tech">
                <td nowrap>{$username.LABEL}:</td>
                <td>{$username.INPUT}</td>
                <td nowrap>{$host.LABEL}: </td>
                <td>{$host.INPUT}</td>
            </tr>
            <tr class="tech">
                {if $TECH eq 'SIP'}
                    <td nowrap>{$insecure.LABEL}: </td>
                    <td >{$insecure.INPUT}</td>
                {else}
                    <td nowrap>{$auth.LABEL}: </td>
                    <td >{$auth.INPUT}</td>
                {/if}
                <td nowrap>{$qualify.LABEL}: </td>
                <td>{$qualify.INPUT}</td>
            </tr>
            <tr class="tech">
                {if $TECH eq 'SIP'}
                    <td nowrap>{$nat.LABEL}: </td>
                    <td >{$nat.INPUT}</td>
                {else}
                    <td nowrap>{$trunk.LABEL}: </td>
                    <td >{$trunk.INPUT}</td>
                {/if}
                <td nowrap>{$context.LABEL}: </td>
                <td>{$context.INPUT}</td>
            </tr>
            <tr class="tech">
                <td nowrap>{$disallow.LABEL}: </td>
                <td>{$disallow.INPUT}</td>
                <td nowrap>{$allow.LABEL}: </td>
                <td>{$allow.INPUT}</td>
            </tr>
            <tr class="tech">
                <td nowrap>{$deny.LABEL}: </td>
                <td>{$deny.INPUT}</td>
                <td nowrap>{$permit.LABEL}: </td>
                <td>{$permit.INPUT}</td>
            </tr>
            <tr class="tech">
                <td nowrap>{$amaflags.LABEL}: </td>
                <td>{$amaflags.INPUT}</td>
                {if $TECH eq 'SIP'}
                    <td nowrap>{$dtmfmode.LABEL}: </td>
                    <td>{$dtmfmode.INPUT}</td>
                {/if}
            </tr>
            {if $mode eq 'edit'}
                <tr>
                    <td style="padding-left: 2px; font-size: 13px" colspan=4><a href="javascript:void(0);" class="adv_opt"><b>{$ADV_OPTIONS}</b></a></td>
                </tr>
                {if $TECH eq 'SIP'}
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$fromuser.LABEL}: </td>
                        <td >{$fromuser.INPUT}</td>
                        <td nowrap>{$fromdomain.LABEL}: </td>
                        <td>{$fromdomain.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td align="left">{$sendrpid.LABEL}: </td>
                        <td >{$sendrpid.INPUT}</td>
                        <td align="left">{$trustrpid.LABEL}: </td>
                        <td >{$trustrpid.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$canreinvite.LABEL}: </td>
                        <td>{$canreinvite.INPUT}</td>
                        <td nowrap>{$useragent.LABEL}: </td>
                        <td>{$useragent.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$videosupport.LABEL}: </td>
                        <td>{$videosupport.INPUT}</td>
                        <td nowrap>{$maxcallbitrate.LABEL}: </td>
                        <td>{$maxcallbitrate.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$qualifyfreq.LABEL}: </td>
                        <td>{$qualifyfreq.INPUT}</td>
                        <td nowrap>{$rtptimeout.LABEL}: </td>
                        <td>{$rtptimeout.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$rtpholdtimeout.LABEL}: </td>
                        <td>{$rtpholdtimeout.INPUT}</td>
                        <td nowrap>{$rtpkeepalive.LABEL}: </td>
                        <td>{$rtpkeepalive.INPUT}</td>
                    </tr>
                {else}
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$trunkfreq.LABEL}: </td>
                        <td>{$trunkfreq.INPUT}</td>
                        <td nowrap>{$trunktimestamps.LABEL}: </td>
                        <td>{$trunktimestamps.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$sendani.LABEL}: </td>
                        <td>{$sendani.INPUT}</td>
                        <td nowrap>{$adsi.LABEL}: </td>
                        <td>{$adsi.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$requirecalltoken.LABEL}: </td>
                        <td>{$requirecalltoken.INPUT}</td>
                        <td nowrap>{$encryption.LABEL}: </td>
                        <td>{$encryption.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$jitterbuffer.LABEL}: </td>
                        <td>{$jitterbuffer.INPUT}</td>
                        <td nowrap>{$forcejitterbuffer.LABEL}: </td>
                        <td>{$forcejitterbuffer.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$codecpriority.LABEL}: </td>
                        <td>{$codecpriority.INPUT}</td>
                        <td nowrap>{$qualifysmoothing.LABEL}: </td>
                        <td>{$qualifysmoothing.INPUT}</td>
                    </tr>
                    <tr class="tech show_more" {$SHOW_MORE}>
                        <td nowrap>{$qualifyfreqok.LABEL}: </td>
                        <td>{$qualifyfreqok.INPUT}</td>
                        <td nowrap>{$qualifyfreqnotok.LABEL}: </td>
                        <td>{$qualifyfreqnotok.INPUT}</td>
                    </tr>
                {/if}
            {/if}
         </table>
       </div>       
    </div>
   {/if}
</div>
<div style="display:none" id="terminate">
{foreach from=$arrTerminate key=k item=v}
<option value="{$k}">{$v}</option>
{/foreach}
</div>
<input type="hidden" name="mode_input" id="mode_input" value="{$mode}">
<input type="hidden" name="id_trunk" id="id_trunk" value="{$id_trunk}">
<input type="hidden" name="tech_trunk" id="tech_trunk" value="{$tech_trunk}">
<input type="hidden" name="mostra_adv" id="mostra_adv" value="{$mostra_adv}">
<input type="hidden" name="arrDestine"  id="arrDestine" value="{$arrDestine}">
<input type="hidden" name="index"  id="index" value="{$j+1}">
{literal}
<script type="text/javascript">
$(document).ready(function(){
    radio("tab-1");
});
</script>
<style type="text/css">
.tech td{
	padding-left: 12px;
}
</style>
{/literal}
