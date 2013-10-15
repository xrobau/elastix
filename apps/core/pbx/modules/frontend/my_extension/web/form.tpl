<div id="contsetting">
    
    <div class="my_settings">

       {if $ERROR_FIELD}
       <div id="message_area" class="alert oculto alert-dismissable" style="text-align:center;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
           <p id="my_extension_errorloc">{$ERROR_FIELD}</p> 
       </div>
       {else}
       <div id="message_area" class="alert oculto alert-dismissable" style="text-align:center;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
           <p id="my_extension_errorloc"> </p> 
       </div>
       {/if}
        
       <div class="row">
            <div class="col-md-3"><button class="button btn btn-default btn-sm" type="button" name="save_new" onclick='editExten()'> <span class="glyphicon glyphicon-ok"></span> Save Configuration</div>
            <div class="col-md-5"><input class="button btn btn-default btn-sm" type="submit" name="cancel" value="Cancel"></div>
        </div>        

        <div class="row">
            <div class="col-md-3"><p>{$DISPLAY_NAME_LABEL}</p></div>
            <div class="col-md-5"><p>{$clid_name}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3"><p>{$DISPLAY_EXT_LABEL}</p></div>
            <div class="col-md-5"><p>{$extension}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3"><p>{$DISPLAY_DEVICE_LABEL}</p></div>
            <div class="col-md-5"><p>{$device}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3"><p>{$language_vm.LABEL}</p></div>
            <div class="col-md-5">{$language_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$doNotDisturb.LABEL}</p></div>
            <div class="col-md-5" id="radio_do_not_disturb">{$doNotDisturb.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$callWaiting.LABEL}</p></div>
            <div class="col-md-5" id="radio_call_waiting">{$callWaiting.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-8"><p>{$DISPLAY_CFC_LABEL}</p></div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$callForwardOpt.LABEL}</p></div>
            <div class="col-md-2" id="radio_call_forward">{$callForwardOpt.INPUT}</div>
            <div class="col-md-3">{$callForwardInp.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$callForwardUnavailableOpt.LABEL}</p></div>
            <div class="col-md-2" id="radio_call_forward_unavailable">{$callForwardUnavailableOpt.INPUT}</div>
            <div class="col-md-3">{$callForwardUnavailableInp.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$callForwardBusyOpt.LABEL}</p></div>
            <div class="col-md-2" id="radio_call_forward_busy">{$callForwardBusyOpt.INPUT}</div>
            <div class="col-md-3">{$callForwardBusyInp.INPUT}</div>
        </div>
      
        <div class="row" >
            <div class="col-md-8"><p>{$DISPLAY_CMS_LABEL}</p></div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$recordIncoming.LABEL}</p></div>
            <div class="col-md-5" id="radio_record_incoming">{$recordIncoming.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$recordOutgoing.LABEL}</p></div>
            <div class="col-md-5" id="radio_record_outgoing">{$recordOutgoing.INPUT}</div>
        </div>
    
        <div class="row" >
            <div class="col-md-8"><p>{$DISPLAY_VOICEMAIL_LABEL}</p></div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$status_vm.LABEL}</p></div>
            <div class="col-md-5" id="radio_status">{$status_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$email_vm.LABEL}</p></div>
            <div class="col-md-5 input-group"><span class="input-group-addon">@</span>{$email_vm.INPUT}</div>
        </div>
      
        <div class="row" >
            <div class="col-md-3"><p>{$password_vm.LABEL}</p></div>
            <div class="col-md-5">{$password_vm.INPUT}</div>
        </div>
      
        <div class="row" >
            <div class="col-md-3"><p>{$emailAttachment_vm.LABEL}</p></div>
            <div class="col-md-5" id="radio_email_attachment">{$emailAttachment_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$playCid_vm.LABEL}</p></div>
            <div class="col-md-5" id="radio_play_cid">{$playCid_vm.INPUT}</div>
        </div>
      
        <div class="row" >
            <div class="col-md-3"><p>{$playEnvelope_vm.LABEL}</p></div>
            <div class="col-md-5" id="radio_play_envelope">{$playEnvelope_vm.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-3"><p>{$deleteVmail.LABEL}</p></div>
            <div class="col-md-5" id="radio_delete_vmail">{$deleteVmail.INPUT}</div>
        </div>

        <div class="row" >
            <div class="col-md-8"><p> </p></div>
        </div>
    </div>
</div>
