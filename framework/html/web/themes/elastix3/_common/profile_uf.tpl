<div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="myModalLabel">{$TITLE_POPUP}({$nameProfile})</h3>
    </div>
    <div class="modal-body">
    
        <div class="row">
            <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <img id='preview' class="img-responsive" alt='image' src='index.php?menu=_elastixutils&action=getImage&ID={$ID_PICTURE}&rawmode=yes'/>
                    </div>
                </div> 
                
                <!--
                <div class="row">
                    <p> </p>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <p>{$deleteImageProfile.INPUT} {$deleteImageProfile.LABEL}</p>
                    </div>
                </div>
                -->
                
            </div>
            <div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$userProfile_label}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$userProfile}</p></div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$extenProfile_label}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$extenProfile}</p></div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$faxProfile_label}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$faxProfile}</p></div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$languageProfile.LABEL}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$languageProfile.INPUT}</p></div>
                </div>
                <div class="row">
                    <p> </p>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 text-center text-info"><label>{$CHANGE_PASSWD_POPUP}</label></div>
                </div>
                <div class="row">
                    <p> </p>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$currentPasswordProfile.LABEL}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$currentPasswordProfile.INPUT}</p></div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$newPasswordProfile.LABEL}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$newPasswordProfile.INPUT}</p></div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><label>{$repeatPasswordProfile.LABEL}</label></div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><p>{$repeatPasswordProfile.INPUT}</p></div>
                </div>
            </div>
        </div>
        
        
        <!--
        <div class="row">
            <p> </p>
        </div>
        <div class="row">
            <p> </p>
        </div>
        <div class="row">
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">{$picture.INPUT}<input type="hidden" name="image" value=""></div>
            </div>   
        </div>
        -->
        
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{$CLOSE_POPUP}</button>
        <button type="button" class="btn btn-primary" onclick='saveNewPasswordProfile()'>{$SAVE_POPUP}</button>
    </div>
</div><!-- /.modal-content -->