<link href="modules/{$module_name}/themes/default/css/styles.css" rel="stylesheet" />

<div id="moduleContainer">
    <div id="moduleTitle" valign="middle" align="left"><span class="div_title_style">&nbsp;&nbsp;&nbsp;{$registration_server}</span></div>

    <div id="formContainer" class="div_content_style">
        <div align="center">{$alert_message}</div>
    </div>
    <div class="div_content_style">
	<div id="msnTextErr" align="center"></div>
        <div id='cloud-login-content'>
            <div id="cloud-login-logo">
                <img src="modules/{$module_name}/images/cloud_logo_login.png" width="281px" height="70px" alt="elastix logo" />
            </div>
            <div class="cloud-login-line">
                <img src="modules/{$module_name}/images/user_login.png" width="23px" height="26px" alt="elastix logo" class="cloud-login-img-input"/>
                <input type="text" id="input_user" name="input_user" class="cloud-login-input" defaultVal="{$EMAIL}"/>
            </div>
            <div class="cloud-login-line">
                <img src="modules/{$module_name}/images/psswrd_login.png" width="23px" height="26px" alt="elastix logo" class="cloud-login-img-input"/>
                <input type="password" id="input_pass" name="input_pass" class="cloud-login-input" defaultVal="{$PASSWORD}"/>
            </div>
            <div class="cloud-login-line">                
                <input type="button" name="input_register" class="cloud-login-button" onclick="registrationByAccount();" value="{$REGISTER_ACTION}"/>
                <input type="hidden" name="msgtmp" id="msgtmp" value="{$sending}" />
            </div>
            <div class="cloud-login-line" >
                <a class="cloud-link_subscription" href="#" onclick="showPopupCloudRegister('{$registration}',540,500)">{$DONT_HAVE_ACCOUNT}</a>
            </div>
            <div class="cloud-footernote"><a href="http://www.elastix.org" style="text-decoration: none;" target='_blank'>Elastix</a> {$ELASTIX_LICENSED} <a href="http://www.opensource.org/licenses/gpl-license.php" style="text-decoration: none;" target='_blank'>GPL</a> {$BY} <a href="http://www.palosanto.com" style="text-decoration: none;" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.</div>
            <br>
        </div>
    </div>
</div>

{literal}
<script src="modules/{/literal}{$module_name}{literal}/themes/default/js/javascript.js" type="text/javascript"></script>
{/literal}