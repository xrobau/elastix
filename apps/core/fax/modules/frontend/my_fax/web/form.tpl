<div id="contsetting">
    
    <div class="my_settings">

       <div class="row">
            <div class="col-md-3"><button class="button btn btn-default btn-sm" type="button" name="save_new" onclick='editFaxExten()' Title= "Save your configuration"> <span class="glyphicon glyphicon-ok"></span> Save Configuration</div>
            <div class="col-md-5"><input class="button btn btn-default btn-sm" Title= "Cancel your configuration" type="submit" name="cancel" value="Cancel"></div>
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
            <div class="col-md-5">
                {$CID_NAME.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Can not be empty"></a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$CID_NUMBER.LABEL}</p></div>
            <div class="col-md-5">
                {$CID_NUMBER.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Can not be empty, just numeric characters are valid"></a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$COUNTRY_CODE.LABEL}</p></div>
            <div class="col-md-5">
                {$COUNTRY_CODE.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Can not be empty, just numeric characters are valid"></a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$AREA_CODE.LABEL}</p></div>
            <div class="col-md-5">
                {$AREA_CODE.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Can not be empty, just numeric characters are valid"></a>
            </div>
        </div>

        <div class="row" >
            <div class="col-md-6 subtitle">
                <p>{$FAX_EMAIL_SETTINGS}</p>        
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$FAX_SUBJECT.LABEL}</p></div>
            <div class="col-md-5">
                {$FAX_SUBJECT.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Can not be empty"></a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 name-label"><p>{$FAX_CONTENT.LABEL}</p></div>
            <div class="col-md-5">
                {$FAX_CONTENT.INPUT}
            </div>
        </div>

    </div>
</div>
