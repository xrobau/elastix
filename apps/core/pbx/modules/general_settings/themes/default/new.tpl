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
        <input type="radio" id="tab-1" name="tab-group-2" onclick="radio('tab-2');" checked>
        <label for="tab-2">{$SIP_GENERAL}</label>
    </div>
    <div  class="neo-table-header-row-filter tab">
        <input type="radio" id="tab-3" name="tab-group-3" onclick="radio('tab-3');">
        <label for="tab-3">{$IAX_GENERAL}</label>
    </div>
    <div class="neo-table-header-row-filter tab">
        <input type="radio" id="tab-4" name="tab-group-4" onclick="radio('tab-4');">
        <label for="tab-4">{$VM_GENERAL}</label>
    </div>
    <div class="neo-table-header-row-navigation" align="right" style="display: inline-block;">
        {if $mode eq 'input'}
        <input type="submit" name="save_new" value="{$SAVE}" >
        {elseif $mode eq 'edit'}
        <input type="submit" name="save_edit" value="{$APPLY_CHANGES}" >
        {elseif $userLevel eq 'admin'}
        <input type="submit" name="edit" value="{$EDIT}">
        <input type="submit" name="delete" value="{$DELETE}"  onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
        {else}
        <input type="submit" name="edit" value="{$EDIT}">
        {/if}
        <input type="submit" name="cancel" value="{$CANCEL}">
    </div>
</div>
<div class="tabs">
    <div class="tab">
       <div class="content" id="content_tab-1" style="padding-left: 8px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding-left: 8px;" class="tabForm">
                <tr>
                    <td style="padding-left: 2px; color: #E35332; font-weight: bold;" colspan=4>{$DIAL_OPT}</td>
                </tr>
                <tr class="feature">
                    <td nowrap>{$DIAL_OPTIONS.LABEL}:</td>
                    <td>{$DIAL_OPTIONS.INPUT}</td>
                    <td nowrap>{$TRUNK_OPTIONS.LABEL}:</td>
                    <td>{$TRUNK_OPTIONS.INPUT}</td>
                </tr>
                <tr>
                    <td style="padding-left: 2px; color: #E35332; font-weight: bold;" colspan=4>{$CALL_RECORDING}</td>
                </tr>
                <tr class="feature">
                    <td nowrap>{$RECORDING_STATE.LABEL}:</td>
                    <td>{$RECORDING_STATE.INPUT}</td>
                    <td nowrap>{$MIXMON_FORMAT.LABEL}:</td>
                    <td>{$MIXMON_FORMAT.INPUT}</td>
                </tr>
            </table>
       </div>
       <div class="content" id="content_tab-2" style="padding-left: 8px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding-left: 8px;" class="tabForm">
                <tr>
                    <td style="padding-left: 2px; color: #E35332; font-weight: bold;" colspan=4>{$DIAL_OPT}</td>
                </tr>
                <tr class="feature">
                    <td nowrap>{$DIAL_OPTIONS.LABEL}:</td>
                    <td>{$DIAL_OPTIONS.INPUT}</td>
                    <td nowrap>{$TRUNK_OPTIONS.LABEL}:</td>
                    <td>{$TRUNK_OPTIONS.INPUT}</td>
                </tr>
                <tr>
                    <td style="padding-left: 2px; color: #E35332; font-weight: bold;" colspan=4>{$CALL_RECORDING}</td>
                </tr>
                <tr class="feature">
                    <td nowrap>{$RECORDING_STATE.LABEL}:</td>
                    <td>{$RECORDING_STATE.INPUT}</td>
                    <td nowrap>{$MIXMON_FORMAT.LABEL}:</td>
                    <td>{$MIXMON_FORMAT.INPUT}</td>
                </tr>
            </table>
       </div>
       <div class="content" id="content_tab-3" style="padding-left: 8px;">
       </div>
       <div class="content" id="content_tab-4" style="padding-left: 8px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding-left: 8px;" class="tabForm">
                <tr>
                    <td style="padding-left: 2px; color: #E35332; font-weight: bold;" colspan=4>{$DIAL_OPT}</td>
                </tr>
                <tr class="feature">
                    <td nowrap>{$DIAL_OPTIONS.LABEL}:</td>
                    <td>{$DIAL_OPTIONS.INPUT}</td>
                    <td nowrap>{$TRUNK_OPTIONS.LABEL}:</td>
                    <td>{$TRUNK_OPTIONS.INPUT}</td>
                </tr>
                <tr>
                    <td style="padding-left: 2px; color: #E35332; font-weight: bold;" colspan=4>{$CALL_RECORDING}</td>
                </tr>
                <tr class="feature">
                    <td nowrap>{$RECORDING_STATE.LABEL}:</td>
                    <td>{$RECORDING_STATE.INPUT}</td>
                    <td nowrap>{$MIXMON_FORMAT.LABEL}:</td>
                    <td>{$MIXMON_FORMAT.INPUT}</td>
                </tr>
            </table>
       </div>
    </div>
</div>