<link href="modules/{$module_name}/themes/default/css/styles.css" rel="stylesheet" />

<div id="moduleContainer">
    <div id="moduleTitle" valign="middle" align="left"><span class="div_title_style">&nbsp;&nbsp;&nbsp;{$registration}</span></div>

    <div id="formContainer" class="div_content_style"><div align="center">{$alert_message}</div></div>
    
    <div class="div_content_style">
	<div id="msnTextErr" align="center"></div>
        <div class="div_table_style">
            <div class="div_tr1_style">
                <div class="div_td1_style tdIdServer">{$identitykeylbl}</div>
                <div class="div_td2_style tdIdServer"><b id="identitykey" class="b-style"></b></div>               
            </div>
            <div class="div_tr2_style">
                <div class="div_td1_style">{$companyReg.LABEL}</div>
                <div class="div_td2_style">{$companyReg.INPUT} <span class="required">*</span></div>              
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$countryReg.LABEL}</div>
                <div class="div_td2_style">{$countryReg.INPUT} <span class="required">*</span></div>               
            </div>
            <div class="div_tr2_style">
                <div class="div_td1_style">{$cityReg.LABEL}</div>
                <div class="div_td2_style" style="width:140px">{$cityReg.INPUT} <span class="required">*</span></div>              
                <div class="div_td1_style" style="width:75px">{$phoneReg.LABEL}</div>
                <div class="div_td2_style" style="width:140px">{$phoneReg.INPUT} <span class="required">*</span></div> 
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$addressReg.LABEL}</div>
                <div class="div_td2_style">{$addressReg.INPUT} <span class="required">*</span></div>               
            </div>           
            <div class="div_tr2_style">
                <div class="div_td1_style">{$contactNameReg.LABEL}</div>
                <div class="div_td2_style">{$contactNameReg.INPUT} <span class="required">*</span></div>                              
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$emailReg.LABEL}</div>
                <div class="div_td2_style">{$emailReg.INPUT} <span class="required">*</span> ({$USERNAME})</div>
            </div>
            <div class="div_tr2_style">
                <div class="div_td1_style">{$emailConfReg.LABEL}</div>
                <div class="div_td2_style">{$emailConfReg.INPUT} <span class="required">*</span></div>
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$passwdReg.LABEL}</div>
                <div class="div_td2_style">{$passwdReg.INPUT} <span class="required">*</span></div>                                             
            </div>
            <div class="div_tr2_style">
                <div class="div_td1_style">{$passwdConfReg.LABEL}</div>
                <div class="div_td2_style">{$passwdConfReg.INPUT} <span class="required">*</span></div>                                                                   
            </div>
            <div class="div_tr1_style" id="tdButtons">
                <input type="button" class="cloud-login-button" style="width:160px" value="{$Activate_registration}" name="btnAct" id="btnAct" onclick="registration();" />
                <input type="hidden" name="msgtmp" id="msgtmp" value="{$sending}"/>
            </div>
       </div>
    </div>
</div>

