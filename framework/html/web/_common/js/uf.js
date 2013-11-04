var elx_phone = null;
$(document).ready(function(){
    pull = $('#pull');
    menu = $('nav > ul');
    menuHeight = menu.height();
    //leftdiv = $('#leftdiv');
    //centerdiv = $('#centerdiv');
    main_content_div = $('#main_content_elastix'); //div que contiene lo que cada modulo tiene 
    rightdiv = $('#rightdiv'); //panel lateral en donde aparece el chat
    pull3 = $('#icn_disp2'); //icono que despliega y oculta el chat
    
    /*despliegue del menu en pantallas pequeñas*/           
    $(this).on('click','#pull',function(e){
        e.preventDefault();
        menu.slideToggle();
    });
                            
    $(window).resize(function(){
        w = $(window).width();
        var tmpSize=0;
        
        /*setea el ancho del panel central al minimizar o maximar la pantalla dependiendo
        del estado del panel lateral */
        //if(w>=700){
            if(rightdiv.is(':hidden') == false){
                tmpSize= tmpSize + 180;
            }
        //}
        
        tmpSize = w - tmpSize;
        main_content_div.css("width",tmpSize+"px");
        
        /*if (w<400 && rightdiv.is(':hidden') == false){
            rightdiv.hide(10);
        }*/
        
        /*setea el estilo del menu una vez que se maximiza la pantalla*/   
        if(menu.is(':hidden')) 
            menu.removeAttr('style');
    });
        
    /* evento que modifica el estilo de todos los paneles, al pulsar el icono para desplegar u ocultar 
    el panel lateral derecho (rightpanel)*/         
    $(this).on('click','#icn_disp2',function(e){
        var w = $(window).width();
        if( rightdiv.is(':hidden') ){
            //es necesario modificar la el margin right del espacio del chat
            $("#elx_chat_space").css("right",200+"px");
            //si esta oculto lo procedemos a abrir y modificar el tamaño de la pantalla
            rightdiv.show(10);  
            tmpSize = w - 180;
            
        }else{
            //es necesario modificar la el margin right del espacio del chat
            $("#elx_chat_space").css("right",15+"px");
            //si esta abierto lo coultamos y modificamos el tamaño de la pantalla
            rightdiv.hide(10);
            tmpSize = w;
        }
        main_content_div.css("width",tmpSize+"px");
        
    });
    /* funciones para Popup*/
    $(this).on('click','#activator',function(){
    //$('#activator').click(function(){
        $('#overlay').fadeIn('fast',function(){
            $('#box').animate({'top':'160px'},500);
        });
    });

    $(this).on('click','#boxclose',function(){
    //$('#boxclose').click(function(){
        $('#box').animate({'top':'-200px'},500,function(){
            $('#overlay').fadeOut('fast');
        });
    });
    
    $(this).on('click','#elx_li_contact',function(){
            var idUser=$(this).attr('data-idUser');
            var device=$(this).attr('data-device');
            var name=$(this).attr('data-name');
            //verifcamos si ya existe un chat abierto a este usuario
            //en caso de existir no se lo vuelve a crear
            if(!($("#tab_chat_"+device).length)){
                var chatTab=startChatUser(idUser,device,name);
                $("#elx_chat_space").append(chatTab);
            }
        }
    );
    //actions to chat tabs
    $(this).on('click','.elx_close_chat',function(){
            $(this).parents(".elx_tab_chat").remove();
        }
    );
    $(this).on('click','.elx_min_chat',function(){
            $(this).removeClass("glyphicon-minus elx_min_chat").addClass("glyphicon-resize-vertical elx_max_chat");
            $(this).parents(".elx_header_tab_chat").next('.elx_body_tab_chat').css('display','none');
        }
    );
    $(this).on('click','.elx_max_chat',function(){
            $(this).removeClass("glyphicon-resize-vertical elx_max_chat").addClass("glyphicon-minus elx_min_chat");
            $(this).parents(".elx_header_tab_chat").next('.elx_body_tab_chat').css('display','block');
        }
    );
    //accion que controla cuando damos enter en el text-area de una de la pestañas del chat
    $(this).on("keydown",".elx_chat_imput", function( event ) {
            if ( event.which == 13 && $(this).val()!='') {
                event.preventDefault();
                //debemos mandar el mensaje y 
                //hacer que el texto del text area desaparezca y sea enviado la divdel chat al que corresponde
                var elx_txt_chat=$(this).val();
                $(this).parents('.elx_body_tab_chat:first').children('.elx_content_chat:first').append("<div>"+elx_txt_chat+"</div>");
                $(this).val('');
                var elx_device=$(this).parents('.elx_tab_chat:first').attr('id').substring(9);
                sendMessage(elx_txt_chat,elx_device);
            }
        }
    );
    getElastixContacts();
});

function changeModuleUF(moduleName){
    if(typeof(moduleName) == 'undefined' || moduleName === null) //nada que hacer no paso el modulo
        return false;
    
    var regexp_user = /^[\w-_]+$/;
    if (moduleName.match(regexp_user) == null) return false;
    
    showElastixUFStatusBar('Loading Module...');
    
    var arrAction = new Array();
    arrAction["changeModule"]  = "yes";
    arrAction["rawmode"] = "yes";
    arrAction["menu"] = moduleName;
    request("index.php",arrAction,false,
        function(arrData,statusResponse,error){
            hideElastixUFStatusBar();
            if(error!=''){
                alert(error);
            }else{
                //var cssFiles=arrData['CSS_HEAD'];
                
                //var jsFiles=arrData['JS_HEAD'];
                //cargamos los scripts del modulo
                var content="<input type='hidden' id='elastix_framework_module_id' value='"+moduleName+"' />";
                content +=arrData['JS_CSS_HEAD'];
                content +=arrData['data'];
                $("#module_content_framework").html(content);
                //se debe setear el contenido de la barra #main_opc en cada modulo
                //aun no se quien deberia hacer esto
                //$("#main_opc").html(arrData['CONTENT_OPT_MENU']);
            }
        }
    );
}

function showElastixUFStatusBar(msgbar){
    $("#notify_change_elastix").css('display','block');
    if(msgbar){
        $(".progress-bar-elastix").html(msgbar);
    }else{
        $(".progress-bar-elastix").html('Loading..');
    }
}
function hideElastixUFStatusBar(){
    $("#notify_change_elastix").css('display','none');
}
function getElastixContacts(){
    var hDiv=$('#rightdiv').height();
    $('#startingSession').css({'top':hDiv/2-10,'display':'block'});
    var arrAction = new Array();
    arrAction["action"]  = "getElastixAccounts";
    arrAction["menu"] = "_elastixutils";
    request("index.php",arrAction,false,
        function(arrData,statusResponse,error){
            if(error!=''){
                errorRegisterChatBar(error);
            }else{
                //revisamos la informacion del contacto
                if(arrData['my_info'] !== 'undefined'){
                    //mandamos a crear la instancia del web phone para el usario
                    if(createUserAgent(arrData['my_info'])===false){
                        //argumentos faltantes
                        return false;
                    }
                }else{
                    //error porque no tenemos los datos del configuración del usuario
                    errorRegisterChatBar('Missing Configurations..');
                    return false;
                }
                
                $('#startingSession').css('display','none');
                $('#b3_1').css('display','block');
                
                //contactos disponibles
                var arrType = new Array('ava','unava','not_found');
                for( var i=0; i<arrType.length; i++){
                    typeAcc=arrType[i];
                    if( typeof arrData[typeAcc] !== 'undefined'){
                        for( var x in arrData[typeAcc]){
                            var div=createDivContact(arrData[typeAcc][x]['idUser'],arrData[typeAcc][x]['display_name'],arrData[typeAcc][x]['device'],arrData[typeAcc][x]['presence'],arrData[typeAcc][x]['st_code']);
                            $("#elx_im_list_contacts").append(div);
                        }
                    }
                }
            }
        }
    );
}
function createDivContact(idUser,display_name,device,presence,presence_code){
    var color=getColorPresence(presence_code);
    var divContact ='<li id="elx_li_contact" class="margin_padding_0" data-device="'+device+'" data-name="'+display_name+'" data-idUser="'+idUser+'"><div class="elx_contact">';
    divContact +="<div id='elx_im_status_user' class='elx_im_status_user'><div class='box_status_contact' style='background-color:"+color+"'></div></div>";
    divContact +="<div class='elx_contact_div'>"
    divContact +="<div class='elx_im_name_user'>"+display_name+"</div>";
    divContact +="<div class='extension_status'>"+presence+"</div>";
    divContact +="</div>";
    divContact +="</div></li>";
    return divContact; 
}
function createDivPersonal(){
    var divContact ="<div class='elx_personal_info'>";
    divContact +="<div class='elx_im_name_user'>"+display_name+"</div>";
    divContact +="<div id='elx_im_status_user' class='elx_im_status_user'><div class='box_status_contact' style='background-color:green'></div></div>";
    divContact +="</div>";
    return divContact;
}
function getColorPresence(presence_code){
    /*-1 = Extension not found
    0 = Idle
    1 = In Use
    2 = Busy
    4 = Unavailable
    8 = Ringing
    16 = On Hold*/
    
    var color='';
    if(presence_code=='-1'){
       color='';
    }else if(presence_code=='0'){
       color='green';
    }else if(presence_code=='1'){
        color='red';
    }else if(presence_code=='2'){
        color='black';
    }else if(presence_code=='4'){
        color='grey';
    }else if(presence_code=='8'){
        color='orange';
    }else if(presence=='16'){
        color='orange';
    }
    return color;
}
/******************************************************************
 * Funciones usadas para el crear el dispositivo sip del usuario
*******************************************************************/
function createUserAgent(UAParams){
    var configuration = new Array();
    var UAManParam = new Array('uri','password','ws_servers');
    var UAOpParam = new Array('display_name', 'authorization_user', 'register', 'register_expires', 'no_answer_timeout', 'trace_sip', 'stun_servers', 'turn_servers', 'use_preloaded_route', 'connection_recovery_min_interval', 'connection_recovery_max_interval', 'hack_via_tcp', 'hack_ip_in_contact');
    for( var i=0; i<UAManParam.length; i++){
        param=UAManParam[i];
        if(undefinedUAParam(UAParams[param])){
            errorRegisterChatBar("Missing Mandatory Param: '"+param+"'");
            return false;
        }else{
            configuration[param] = UAParams[param];
        }
    }
    for( var i=0; i<UAOpParam.length; i++){
        param=UAOpParam[i];
        if(!undefinedUAParam(UAParams[param])){
            configuration[param] = UAParams[param];
        }
    }

    elx_phone = new JsSIP.UA(configuration);

    elx_phone.on('newMessage', function(e){
        var text,
        message = e.data.message,
        request = e.data.request;

        if(message.direction === 'incoming'){
            text = request.body;
            alert(text);
        }
    });

    elx_phone.on('unregistered', function(e){
        alert("Desconectado... " + elx_phone.configuration.display_name );    
    });

    elx_phone.on('registrationFailed', function(e) {
        if (! e.data.response) {
            console.info("SIP registration error:\n" + e.data.cause);
            errorRegisterChatBar("SIP registration error:\n" + e.data.cause);
        }else {
            console.info("SIP registration error:\n" + e.data.response.status_code.toString() + " " + e.data.response.reason_phrase)
            errorRegisterChatBar("SIP registration error:\n" + e.data.response.status_code.toString() + " " + e.data.response.reason_phrase);
        }
    });
    elx_phone.start();
}
function undefinedUAParam(param){
    if(typeof param !== 'undefined'){
        if(param != '')
            return false;
    }
    return true;
}
function sendMessage(msg_txt,device)
{
    var eventHandlers = {
        'succeeded'   : function(e){ /* Your code here */ },
        'failed'      : function(e) { 
                $("#tab_chat_"+device+" > elx_body_tab_chat").children('.elx_content_chat:first').append('<div>Failure</div>');
            },
    };
    var options = { 'eventHandlers': eventHandlers };
    elx_phone.sendMessage("sip:"+device+"@192.168.5.110", msg_txt, options);
}
function inconmigMessage(){
    
}
function inconmigMessageNotify(){
    
}
function registerPhone()
{
    elx_phone.register();
}
function unregisterPhone()
{
    elx_phone.unregister();
}
function startChatUser(idUser,device,name){
    //name
    //uri chat
    var elx_im_cabecera="<div class='elx_header_tab_chat'>";
    elx_im_cabecera +="<div class='elx_tab_chat_name'>";
    elx_im_cabecera +="<span class='elx_tab_chat_name_span'>"+name+"</span>";
    elx_im_cabecera +="</div>";
    elx_im_cabecera +="<div class='elx_tab_tittle_icon'>";
    elx_im_cabecera +="<span class='glyphicon glyphicon-minus elx_icon_chat elx_min_chat' alt='Minimize' data-tooltip='Minimize' aria-label='Minimize'></span>";
    elx_im_cabecera +="<span class='glyphicon glyphicon-remove elx_icon_chat elx_close_chat' alt='Close' data-tooltip='Close' aria-label='Close'></span>";
    elx_im_cabecera +="</div>";
    elx_im_cabecera +="</div>"
    
    var elx_im_cabecera2="<div class='elx_header2_tab_chat'>";
    elx_im_cabecera2 +="<span class='glyphicon glyphicon-earphone elx_icon_chat elx_icon_chat2' alt='Call' data-tooltip='Call' aria-label='Call'></span>";
    elx_im_cabecera2 +="<span class='glyphicon glyphicon-envelope elx_icon_chat elx_icon_chat2' alt='Send E-Mail' data-tooltip='Send E-Mail' aria-label='Send E-Mail'></span>";
    elx_im_cabecera2 +="<span class='glyphicon glyphicon-print elx_icon_chat elx_icon_chat2' alt='Send Fax' data-tooltip='Send Fax' aria-label='Send Fax'></span>";
    elx_im_cabecera2 +="</div>";
    
    var conversation="<div class='elx_content_chat'>";
    conversation +="</div>";
    
    var elx_text_area_chat="<div class='elx_text_area_chat'>";
    elx_text_area_chat +="<textarea class='elx_chat_imput'></textarea>";
    elx_text_area_chat +="</div>"
    
    var content="<div class='elx_tab_chat' id='tab_chat_"+device+"'>";
    content +=elx_im_cabecera+"<div class='elx_body_tab_chat'>"+elx_im_cabecera2+conversation+elx_text_area_chat+"</div>";
    content +="</div>";
    
    return content;
}
function errorRegisterChatBar(error){
    alert(error);
    $('#b3_1').css('display','none');
    $('#startingSession').html(error);
    $('#startingSession').css({'display':'block',margin:'5px'});
}