<table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        {if $mode eq 'input'}
        <td align="left">
            <input class="button" type="submit" name="save_new" value="{$SAVE}">&nbsp;&nbsp;
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        {/if}
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td  align="left" colspan=2;><br /><b style ="color:#E35332; font-weigth:bold;font-size:15px;">{$EXTENSION}</b><br /><br /></td>
    </tr>
    <tr class="letra12">
        <td align="left" width="300px"><b>{$do_not_disturb.LABEL}:</b></td>
        <td align="left">{$do_not_disturb.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$call_waiting.LABEL}:</b></td>
        <td align="left">{$call_waiting.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b style ="color:#E35332; font-weigth:bold;font-size:12px;font-family:'Lucida Console';">{$TAG_CALL_FORW_CONF}</b></td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$call_forward.LABEL}:</b></td>
        <td align="left">{$call_forward.INPUT} {$phone_number_CF.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$call_forward_U.LABEL}:</b></td>
        <td align="left">{$call_forward_U.INPUT} {$phone_number_CFU.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$call_forward_B.LABEL}:</b></td>
        <td align="left">{$call_forward_B.INPUT} {$phone_number_CFB.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b style ="color:#E35332; font-weigth:bold;font-size:12px;font-family:'Lucida Console';">{$TAG_CALL_MON_SET}</b></td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$recording_in_external.LABEL}:</b></td>
        <td align="left">{$recording_in_external.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$recording_out_external.LABEL}:</b></td>
        <td align="left">{$recording_out_external.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$recording_in_internal.LABEL}:</b></td>
        <td align="left">{$recording_in_internal.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$recording_out_internal.LABEL}:</b></td>
        <td align="left">{$recording_out_internal.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$recording_ondemand.LABEL}:</b></td>
        <td align="left">{$recording_ondemand.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$recording_priority.LABEL}:</b></td>
        <td align="left">
            <div style="width:270px">
                <span id="recording_priority_amount" name="recording_priority_amount" style="border:0; color:#f6931f; font-weight:bold; float: right">{$recording_priority_value}</span>
                <div id="slider" style="width:240px;"></div>
                {$recording_priority.INPUT}
            </div>    
        </td>
    </tr> 
</table>