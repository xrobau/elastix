{literal}
<link rel="stylesheet" href="web/_common/js/jquery/css/smoothness/jquery-ui-1.8.24.custom.css">
{/literal}
<div id="contsetting">
    
    <div class="my_settings">
       <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3"><button class="button btn btn-default btn-sm" type="button" name="save_new" onclick='editExten()'> <span class="glyphicon glyphicon-ok"></span> Save Configuration</div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3"><input class="button btn btn-default btn-sm" type="submit" name="cancel" value="Cancel"></div>
        </div>

        <div class="row" >
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6"><p> </p></div>
        </div>        

        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$DISPLAY_NAME_LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3"><p>{$clid_name}</p></div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$DISPLAY_EXT_LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3"><p>{$extension}</p></div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$DISPLAY_DEVICE_LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3"><p>{$device}</p></div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$language_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                {$language_vm.INPUT}
            </div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$doNotDisturb.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_do_not_disturb">{$doNotDisturb.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$callWaiting.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_call_waiting">{$callWaiting.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 subtitle"><p>{$DISPLAY_CFC_LABEL}</p></div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$callForwardOpt.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2" id="radio_call_forward">{$callForwardOpt.INPUT}</div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
                {$callForwardInp.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Just numeric characters are valid"></a>
            </div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$callForwardUnavailableOpt.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2" id="radio_call_forward_unavailable">{$callForwardUnavailableOpt.INPUT}</div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
                {$callForwardUnavailableInp.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Just numeric characters are valid"></a>
            </div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$callForwardBusyOpt.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2" id="radio_call_forward_busy">{$callForwardBusyOpt.INPUT}</div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
                {$callForwardBusyInp.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Just numeric characters are valid"></a>
            </div>
        </div>
      
        <div class="row" >
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 subtitle"><p>{$DISPLAY_CMS_LABEL}</p></div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$recordIncoming.LABEL}</p></div>
            <div class="col-xs-12 col-sm-5 col-md-4 col-lg-3" id="radio_record_incoming">{$recordIncoming.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$recordOutgoing.LABEL}</p></div>
            <div class="col-xs-12 col-sm-5 col-md-4 col-lg-3" id="radio_record_outgoing">{$recordOutgoing.INPUT}</div>
        </div>
    
        <div class="row" >
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 subtitle"><p>{$DISPLAY_VOICEMAIL_LABEL}</p></div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$status_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_status">{$status_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$email_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 input-group">
                <span class="input-group-addon">@</span>
                {$email_vm.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Invalid email"></a>
            </div>
        </div>
      
        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$password_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                {$password_vm.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Just numeric characters are valid"></a>
            </div>
        </div>
      
        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$emailAttachment_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_email_attachment">{$emailAttachment_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$playCid_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_play_cid">{$playCid_vm.INPUT}</div>
        </div>
      
        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$playEnvelope_vm.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_play_envelope">{$playEnvelope_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 name-label"><p>{$deleteVmail.LABEL}</p></div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" id="radio_delete_vmail">{$deleteVmail.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p> </p></div>
        </div>
    </div>
</div>
