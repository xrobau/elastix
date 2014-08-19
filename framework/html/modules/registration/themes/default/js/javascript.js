$(document).ready(function(){
    $('.cloud-login-input').each( function () {
        $(this).val($(this).attr('defaultVal'));
        $(this).css({color:'grey'});
    });
    
    $('.cloud-login-input').focus(function(){
        if ( $(this).val() == $(this).attr('defaultVal') ){
            $(this).val('');
            $(this).css({color:'black'});
        }
    });
    
    $('.cloud-login-input').blur(function(){
        if ( $(this).val() == '' ){
            $(this).val($(this).attr('defaultVal'));
            $(this).css({color:'grey'});
        }
    }); 
});

function showPopupCloudRegister(title, width, height){
    var arrAction         = new Array();
    arrAction['action']   = "registration";
    arrAction["rawmode"]  = "yes";

    request("register.php",arrAction,false,
        function(arrData,statusResponse,error)
        {            
            ShowModalPopUP(title,width,height,arrData['form']);
            $('.tdIdServer').hide();
            
            if(arrData['registered']=="yes-inc"){
                $('.tdIdServer').show();
                showLoading(arrData['msgloading']);
                getDataWebServer();
            }
        }
    );
}

function registration(){
    var arrAction               = new Array();
    arrAction['action']         = "saveregister";
    arrAction["rawmode"]        = "yes";
    arrAction['contactNameReg'] = $('#contactNameReg').val();
    arrAction["emailReg"]       = $('#emailReg').val();
    arrAction["emailConfReg"]   = $('#emailConfReg').val();
    arrAction["passwdReg"]      = $('#passwdReg').val();
    arrAction["passwdConfReg"]  = $('#passwdConfReg').val();
    arrAction["phoneReg"]       = $('#phoneReg').val();
    arrAction["companyReg"]     = $('#companyReg').val();
    arrAction["addressReg"]     = $('#addressReg').val();
    arrAction["cityReg"]        = $('#cityReg').val();
    arrAction["countryReg"]     = $('#countryReg option:selected').val();

    $('#btnAct').hide();
    showLoading($('#msgtmp').val());
    
    request("register.php",arrAction,false,
        function(arrData,statusResponse,error)
        {   
            $('#btnAct').show();
            
            if(error!=""){
                alert(error);
                clearMessage();
            }
            else {
                showMessage(arrData['msg']);
                
                $('.register_link').css('color',arrData['color']);
                $('.register_link').text(arrData['label']);
                    
                if(statusResponse=="TRUE") {
                    registrationEnd(arrData);                    
                }
            }
        }
    );
}

function getDataWebServer()
{
    var arrAction         = new Array();
    arrAction['action']   = "getDataRegisterServer";
    arrAction["rawmode"]  = "yes";
    
    $('#btnAct').hide();
    request("register.php",arrAction,false,
	function(arrData,statusResponse,error)
	{
            $('#btnAct').show();
            
            if(statusResponse == "TRUE"){
                clearMessage();
                
                $('#identitykey').text(arrData['identitykeyReg']);

                if(arrData["has_account"]=="yes"){
                    $('#contactNameReg').text(arrData['contactNameReg']);
                    $('#emailReg').text(arrData['emailReg']);
                    $('#phoneReg').text(arrData['phoneReg']);
                    $('#companyReg').text(arrData['companyReg']);
                    $('#cityReg').text(arrData['cityReg']);
                    $('#countryReg').text(arrData['countryReg']);
                }
                else {
                    $('#contactNameReg').val(arrData['contactNameReg']);
                    $('#emailReg').val(arrData['emailReg']);
                    $('#phoneReg').val(arrData['phoneReg']);
                    $('#companyReg').val(arrData['companyReg']);
                    $('#addressReg').val(arrData['addressReg']);
                    $('#cityReg').val(arrData['cityReg']);
                    $('#countryReg').val(arrData['countryReg']);
                }
            }
            
            if(error!="") showMessage(error);
            
	}
    );
}

function registrationByAccount()
{
    var username = $('#input_user').val().trim();
    var password = $('#input_pass').val().trim();
    var usernameDefaultVal = $('#input_user').attr('defaultVal').trim();
    var passwordDefaultVal = $('#input_pass').attr('defaultVal').trim();

    if(!((username == usernameDefaultVal) || (password == passwordDefaultVal))){        
        showLoading($('#msgtmp').val());
        
        var arrAction         = new Array();
        arrAction["action"]   = "savebyaccount";
        arrAction["rawmode"]  = "yes";
        arrAction["username"] = username;
        arrAction["password"] = password;
        
        request("register.php",arrAction,false,
            function(arrData,statusResponse,error)
            {
                showMessage(arrData['msg']);
                
                $('.register_link').css('color',arrData['color']);
                $('.register_link').text(arrData['label']);    
                
                if(statusResponse=="TRUE") {
                    registrationEnd(arrData);
                }
            }
        );            
    }
}

function showMessage(msg)
{
    $('#msnTextErr').text(msg);
}

function clearMessage()
{
    $('#msnTextErr').text("");
}

function showLoading(msg)
{var shtml  = "<div align='center'>" + msg + "</div>";
        shtml += "<img src='../../../images/loading.gif' width='22px' height='18px' alt='loading' />";
    $('#msnTextErr').html(shtml);
}

function registrationEnd(arrData)
{
    var callback = $('#callback').val();
    if(callback && callback !=""){ //cuando estamos en el menu addons        
        getElastixKey();
        hideModalPopUP();
    }
    else {
        $('.cloud-login-button').hide();
        showLoading(arrData['msg']);

        setTimeout(function(){
            showPopupCloudLogin(arrData['label'],540,460)
        }, 3000);
    }
}