{literal}
<link rel="stylesheet" href="web/_common/js/jquery/css/smoothness/jquery-ui-1.8.24.custom.css">
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
            <div class="col-md-12">
                {if $ELX_ACTION eq 'new'}
                    <button class="btn btn-default btn-sm" type="button" name="save_new" onclick='saveNewContact()'> <span class="glyphicon glyphicon-ok"></span> Save</button>
                {else}
                    <button class="btn btn-default btn-sm" type="button" name="save_edit" onclick='saveEditContact()'> <span class="glyphicon glyphicon-ok"></span> Save</button>
                {/if}
                <button class="btn btn-default btn-sm" type="button" name="cancel" onclick='cancelContact()'> Cancel</button>
            </div>
        </div>

        <div class="row" >
            <div class="col-md-6"><p> </p></div>
        </div>



        <div class="row">
            <div class="col-lg-6">
                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$contact_type.LABEL}</p></div>
                    <div class="col-lg-6 contact_type" id="contact_type">{$contact_type.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$first_name.LABEL} <span class="glyphicon-asterisk mandatory"></span> </p></div>
                    <div class="col-lg-6">
                        {$first_name.INPUT}
                        <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="{$TOOLTIP_FIRS_NAME}"></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$last_name.LABEL} <span class="glyphicon-asterisk mandatory"></span></p></div>
                    <div class="col-lg-6">
                        {$last_name.INPUT}
                        <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="{$TOOLTIP_LAST_NAME}"></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$work_phone_number.LABEL} <span class="glyphicon-asterisk mandatory"></span></p></div>
                    <div class="col-lg-6">
                        {$work_phone_number.INPUT}
                        <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="{$TOOLTIP_POHNE}"></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$cell_phone_number.LABEL}</p></div>
                    <div class="col-lg-6">{$cell_phone_number.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$home_phone_number.LABEL}</p></div>
                    <div class="col-lg-6">{$home_phone_number.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$fax_number_1.LABEL}</p></div>
                    <div class="col-lg-6">{$fax_number_1.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$fax_number_2.LABEL}</p></div>
                    <div class="col-lg-6">{$fax_number_2.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$email.LABEL}</p></div>
                    <div class="col-lg-6 input-group">
                        <span class="input-group-addon">@</span>
                        {$email.INPUT}
                        <a href="#" class="glyphicon glyphicon-exclamation-sign hidden-tooltip" data-toggle="tooltip" data-placement="auto" title="" data-original-title="{$TOOLTIP_EMAIL}"></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$province.LABEL}</p></div>
                    <div class="col-lg-6">{$province.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$city.LABEL}</p></div>
                    <div class="col-lg-6">{$city.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$address.LABEL}</p></div>
                    <div class="col-lg-6">{$address.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$company.LABEL}</p></div>
                    <div class="col-lg-6">{$company.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$contact_person.LABEL}</p></div>
                    <div class="col-lg-6">{$contact_person.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$contact_person_position.LABEL}</p></div>
                    <div class="col-lg-6">{$contact_person_position.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$notes.LABEL}</p></div>
                    <div class="col-lg-6">{$notes.INPUT}</div>
                </div>

                <div class="row">
                    <div class="col-lg-5 name-label"><p>{$picture.LABEL}</p></div>
                    <div class="col-lg-6">{$picture.INPUT}<input type="hidden" name="image" value=""></div>
                </div>
            </div>
            <div class="col-lg-5" id="previews">
                <img id='preview' class="img-responsive" alt='image' src='index.php?menu=contacts&action=getImageExtContact&image={$ID_PICTURE}&rawmode=yes'/>
            </div>
        </div>

         <div class="row" >
            <div class="col-md-6"><p> </p></div>
        </div>

    </div>
</div>
