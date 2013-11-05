{literal}
<link rel="stylesheet" href="web/_common/js/jquery/css/blitzer/jquery-ui-1.8.24.custom.css">
{/literal}
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
            <div class="col-md-3"><button class="button btn btn-default btn-sm" type="button" name="save_new" onclick='saveVacation()'> <span class="glyphicon glyphicon-ok"></span> Enable vacation Message</div>
        </div>        
        
        <div class="row" >
            <div class="col-md-6"><p> </p></div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-2 col-md-2 col-lg-2 name-label"><p>{$PERIOD_LABEL}</p></div>
            <div class="col-xs-12 col-sm-7 col-md-6 col-lg-5 form-inline">
                <div class="form-group">
                    <label for="inputFrom" class="control-label">{$FROM.LABEL}</label>
                    {$FROM.INPUT}
                </div>
                <div class="form-group">
                    <label for="inputTo" class="control-label">{$TO.LABEL}</label>
                    {$TO.INPUT}
                </div>
                <div class="form-group">
                    <label for="inputTo" class="control-label" id="num_days"> 0 Days </label>
                </div>
            </div>
           
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-2 col-md-2 col-lg-2 name-label"><p>{$EMAIL_ADDRESS.LABEL}</p></div>
            <div class="col-xs-12 col-sm-7 col-md-6 col-lg-5 input-group"><span class="input-group-addon">@</span>{$EMAIL_ADDRESS.INPUT}</div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-2 col-md-2 col-lg-2 name-label"><p>{$EMAIL_SUBJECT.LABEL}</p></div>
            <div class="col-xs-12 col-sm-7 col-md-6 col-lg-5">
                {$EMAIL_SUBJECT.INPUT}
                <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Can not be empty"></a>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-2 col-md-2 col-lg-2 name-label"><p>{$EMAIL_CONTENT.LABEL}</p></div>
            <div class="col-xs-10 col-sm-7 col-md-6 col-lg-5">
                {$EMAIL_CONTENT.INPUT}
            </div>
        </div>

    </div>
</div>
