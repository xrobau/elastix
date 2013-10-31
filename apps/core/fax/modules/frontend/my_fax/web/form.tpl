<div id="contsetting">
    
    <div class="my_settings">

       {if $ERROR_FIELD}
       <div id="initial_message_area" class="alert alert-dismissable" style="text-align:center;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
           <p id="msg-text" class="alert-danger">{$ERROR_FIELD}</p> 
       </div>
       {else}
       <div id="message_area" class="alert oculto alert-dismissable" style="text-align:center;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
           <p id="msg-text"> </p> 
       </div>
       {/if}

       <div class="row">
            <div class="col-md-3"><button class="button btn btn-default btn-sm" type="button" name="save_new" onclick='editFaxExten()'> <span class="glyphicon glyphicon-ok"></span> Save Configuration</div>
            <div class="col-md-5"><input class="button btn btn-default btn-sm" type="submit" name="cancel" value="Cancel"></div>
        </div>        
        
        <div class="row" >
            <div class="col-md-6"><p> </p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$EXTENSION_LABEL}</p></div>
            <div class="col-md-5"><p>{$EXTENSION}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$DEVICE_LABEL}</p></div>
            <div class="col-md-5"><p>{$DEVICE}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$STATUS_LABEL}</p></div>
            <div class="col-md-5"><p class="fax-status">{$STATUS}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$CID_NAME.LABEL}</p></div>
            <div class="col-md-5"><p>{$CID_NAME.INPUT}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$CID_NUMBER.LABEL}</p></div>
            <div class="col-md-5"><p>{$CID_NUMBER.INPUT}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$COUNTRY_CODE.LABEL}</p></div>
            <div class="col-md-5"><p>{$COUNTRY_CODE.INPUT}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$AREA_CODE.LABEL}</p></div>
            <div class="col-md-5"><p>{$AREA_CODE.INPUT}</p></div>
        </div>

        <div class="row" >
            <div class="col-md-6 subtitle"><p>{$FAX_EMAIL_SETTINGS}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$FAX_SUBJECT.LABEL}</p></div>
            <div class="col-md-5"><p>{$FAX_SUBJECT.INPUT}</p></div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$FAX_CONTENT.LABEL}</p></div>
            <div class="col-md-5"><p>{$FAX_CONTENT.INPUT}</p></div>
        </div>

        

    </div>
</div>
