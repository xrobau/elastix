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

function showPopupCloudRegister(title, width, height)
{
    var arrAction         = new Array();

    request("register.php", {
    	action:		'registration',
    	rawmode:	'yes'
    }, false, function(arrData,statusResponse,error) {
        ShowModalPopUP(title,width,height,arrData['form']);
        $('.tdIdServer').hide();
        $('.neo-modal-elastix-popup-box').css({
            height: '388px',
        });
        
        if(arrData['registered']=="yes-inc"){
            $('.tdIdServer').css("display","");
            $('.neo-modal-elastix-popup-box').css({
              height: '410px',
            });
            showLoading(arrData['msgloading']);
            getDataWebServer();
        }
    });
}

function registration()
{
    showLoading($('#msgtmp').val());
    $('#btnAct').css("visibility","hidden");
    
    request("register.php", {
    	action:			'saveregister',
    	contactNameReg:	$('#contactNameReg').val(),
    	emailReg:		$('#emailReg').val(),
    	emailConfReg:	$('#emailReg').val(),
    	passwdReg:		$('#passwdReg').val(),
    	passwdConfReg:	$('#passwdConfReg').val(),
    	phoneReg:		$('#phoneReg').val(),
    	companyReg:		$('#companyReg').val(),
    	addressReg:		$('#addressReg').val(),
    	cityReg:		$('#cityReg').val(),
    	countryReg:		$('#countryReg option:selected').val(),
    	rawmode:		'yes'
    }, false, function(arrData,statusResponse,error) {
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
        $('#btnAct').css("visibility","visible");
    });
}

function getDataWebServer()
{
    $('#btnAct').hide();
    request("register.php", {
    	action:		'getDataRegisterServer',
    	rawmode:	'yes'
    }, false, function(arrData,statusResponse,error) {
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
	});
}

function registrationByAccount()
{
    var username = $('#input_user').val().trim();
    var password = $('#input_pass').val().trim();
    var usernameDefaultVal = $('#input_user').attr('defaultVal').trim();
    var passwordDefaultVal = $('#input_pass').attr('defaultVal').trim();

    if(!((username == usernameDefaultVal) || (password == passwordDefaultVal))){        
        showLoading($('#msgtmp').val());
        
        $(".action_register_button").css({
            visibility: 'hidden'
        });
        
        request("register.php", {
        	action:		'savebyaccount',
        	username:	username,
        	password:	password,
        	rawmode:	'yes'
        }, false, function(arrData,statusResponse,error) {
            $(".action_register_button").css({
                visibility: ''
            });
            if(arrData['msg'])
               showMessage(arrData['msg']);
            else
               showMessage(arrData);
                            
            console.log(arrData['msg']);
            $('.register_link').css('color',arrData['color']);
            $('.register_link').text(arrData['label']);    
            
            if(statusResponse=="TRUE") {
                registrationEnd(arrData);
            }
        });            
    }
}

function showMessage(msg)
{
    $('#msnTextErr').text(msg).css({
        color: '#FF0000',
    });
}

function clearMessage()
{
    $('#msnTextErr').text("");
}

function showLoading(msg)
{var shtml  = "<div align='center'>" + msg + "</div>";
        shtml += "<img src='../../../images/loading.gif' width='22px' height='18px' alt='loading' />";
    $('#msnTextErr').html(shtml).css({
        color: '#000000',
    });
}

function registrationEnd(arrData)
{
    var callback = $('#callback').val();
    if(callback && callback !=""){ //cuando estamos en el menu addons        
        getElastixKey();
        hideModalPopUP();
    }
    else {
        $('.cloud-login-button, .cloud-signup-button').hide();
        showLoading(arrData['msg']);
        
        setTimeout(function(){
            showPopupCloudLogin("",540,335)
        }, 3000);
    }
}

function checkSubmit(e)
{
   if(e && e.keyCode == 13)
   {
      registrationByAccount();
   }
}

function showInfoRegistration(){
    $(".text_info_registration").show('400', function() {});
}

function hideInfoRegistration(){
    $(".text_info_registration").hide('400', function() {});
}

