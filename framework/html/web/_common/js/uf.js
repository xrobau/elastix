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
    
    $(this).on('click','.elx-msg-area-close',function(e){
        $("#elx_msg_area").slideUp();  
    });
                            
    $(window).resize(function(){
        w = $(window).width();
        var tmpSize=0;
        if(w>=500){
            if(rightdiv.is(':hidden') == false){ //esta abierto
                $("#elx_chat_space").css("right","200px");
                tmpSize= tmpSize + 180;
            }
            tmpSize = w - tmpSize;
            main_content_div.css("width",tmpSize+"px");
            $("#elx_chat_space").show(1);
        }else{
            if(rightdiv.is(':hidden') == false){ //esta abierto
                $("#elx_chat_space").hide(1,function(){
                    $("#elx_chat_space").css("right","15px");
                });
            }else
                $("#elx_chat_space").css("right","15px");
            main_content_div.css("width",w+"px"); 
        }
        
        adjustTabChatToWindow(w);
        
        /*setea el estilo del menu una vez que se maximiza la pantalla*/   
        if(menu.is(':hidden')) 
            menu.removeAttr('style');
        
        //calulamos la altura maxima del div del chat donde estan los contactos
        if(rightdiv.is(':hidden') == false){
            adjustHeightElxListUser();
        }
    });
        
    /* evento que modifica el estilo de todos los paneles, al pulsar el icono para desplegar u ocultar 
    el panel lateral derecho (rightpanel)*/         
    $(this).on('click','#icn_disp2',function(e){
        var w = $(window).width();
        if( rightdiv.is(':hidden') ){ //estaba oculto y lo abrimos
            //es necesario modificar la el margin right del espacio del chat
            if(w>=500){
                $("#elx_chat_space").css("right",200+"px");
                //modificamos el tamaño del div principal
                tmpSize = w - 180;
                main_content_div.css("width",tmpSize+"px");
            }else{
                //escondemos las pestañas del chat activas
                $("#elx_chat_space").hide(10);
            }
            rightdiv.show(10,function(){
                adjustHeightElxListUser();
            });
            adjustTabChatToWindow(w);
        }else{ //estaba abierto y lo cerramos
            if(w>=500){
                //es necesario modificar la el margin right del espacio del chat
                $("#elx_chat_space").css("right",15+"px");
                //si esta abierto lo coultamos y modificamos el tamaño de la pantalla
                tmpSize = w;
                main_content_div.css("width",tmpSize+"px");
            }else{
                $("#elx_chat_space").show(10);
            }
            rightdiv.hide(10);
            $("#elx_im_list_contacts").css('height','');
            adjustTabChatToWindow(w);
        }
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
            var uri=$(this).attr('data-uri');
            var alias=$(this).attr('data-alias');
            var name=$(this).attr('data-name');
            //verifcamos si ya existe un chat abierto a este usuario
            //en caso de existir se lo crea
            if($(window).width()<500){
                rightdiv.hide(10);
            }
            var elx_tab_chat=startChatUser(uri,name,alias,'sent');
            elx_tab_chat.find('.elx_text_area_chat > textarea').focus();
            $("#elx_chat_space").show(10);
        }
    );
    //actions to chat tabs
    $(this).on('click','.elx_close_chat',function(){
            $(this).parents(".elx_tab_chat").removeClass('elx_chat_active').addClass('elx_chat_close');
            //debemos comprobar si ahi pestañas minimizadas por falta de espacio
            //si existen entonces abrimos la ultima pestaña
            var chatMIn=$("#elx_chat_space_tabs > .elx_chat_min").last();
            if(chatMIn!=='undefined'){
                chatMIn.removeClass('elx_chat_min').addClass('elx_chat_active');
                removeElxUserNotifyChatMini(chatMIn);
            }
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
            // Ignore TAB and ESC.
            if (event.which == 9 || event.which == 27) {
                return false;
                // Enter pressed? so send chat.
            }else if ( event.which == 13 && $(this).val()!='') {
                event.preventDefault();
                //debemos mandar el mensaje y 
                //hacer que el texto del text area desaparezca y sea enviado la divdel chat al que corresponde
                var elx_txt_chat=$(this).val();
                var elx_tab_chat=$(this).parents('.elx_tab_chat:first');
                addMessageElxChatTab(elx_tab_chat,'out',elx_txt_chat);
                $(this).val('');
                sendMessage(elx_txt_chat,elx_tab_chat.attr('data-alias'));
                // Ignore Enter when empty input.
            }else if (event.which == 13 && $(this).val() == "") {
                event.preventDefault();
                return false;
            }
        }
    );
    $(this).on("click",".elx_tab_chat", function( event ) {
        $(this).children('.elx_header_tab_chat').removeClass('elx_blink_chat');
        $(this).find('.elx_text_area_chat > textarea').focus();
    });
    getElastixContacts();
    //motificaciones en pestañas de chat minimizadas por falta de espacio
    $('#elx_notify_min_chat_box').on("click",function(event){
        var hidMinList=$('#elx_hide_min_list').val();
        if(hidMinList=='yes'){
            //se deben ocultar la lista de las conversaciones minimizadas por falta de espacio
            $("#elx_notify_min_chat_box").removeClass('elx_notify_min_chat_box_act');
            $('#elx_hide_min_list').val('no');
            $("#elx_list_min_chat").css('visibility','hidden');
        }else{
            //antes de mostrar la lista debemos calcular si el espacio que queda es suficiente para
            //mostrar los elementos de la lista
            //si no queda mucho espacio cambiamos la direccion de la lista al otro lado
            //se deben mostrar la lista de las conversaciones minimizadas por falta de espacio
            $("#elx_notify_min_chat_box").addClass('elx_notify_min_chat_box_act');
            $('#elx_hide_min_list').val('yes');
            var offElement=$("#elx_notify_min_chat_box").offset();
            var widthList = $("#elx_list_min_chat > div > .elx_list_min_chat_ul").width();
            if((offElement.left-40)>(widthList)){
                //existe suficiente espacio para mostrar la lista 
                $("#elx_list_min_chat").css('right','0px');
                $("#elx_list_min_chat").css('left','');
            }else{
                //no existe suficiente espacio para mostrar la lista 
                $("#elx_list_min_chat").css('left','0px');
                $("#elx_list_min_chat").css('right','');
            }
            $("#elx_list_min_chat").css('visibility','visible');
        }
    });
    $(this).on('click','.elx_min_name',function(event){
        $(this).children(".elx_min_chat_num").css('visibility','hidden');
        //se deben ocultar la lista de las conversaciones minimizadas por falta de espacio
        $("#elx_notify_min_chat_box").removeClass('elx_notify_min_chat_box_act');
        $('#elx_hide_min_list').val('no');
        $("#elx_list_min_chat").css('visibility','hidden');
        var alias=$(this).parents('.elx_list_min_chat_li:first').attr('data-alias');
        var elx_tab_chat=startChatUser(alias,name,alias,'sent');
        elx_tab_chat.find('.elx_text_area_chat > textarea').focus();
        elx_tab_chat.find('.elx_tab_tittle_icon > span:first').removeClass("glyphicon-resize-vertical elx_max_chat").addClass("glyphicon-minus elx_min_chat");
        elx_tab_chat.find('.elx_body_tab_chat').css('display','block');
    });
    $(this).on('click','.elx_min_remove',function(event){
        var liIcon=$(this).parents('.elx_list_min_chat_li:first');
        var alias=liIcon.attr('data-alias');
        liIcon.remove();
        var tabChat=getTabElxChat(alias);
        tabChat.removeClass('elx_chat_min').addClass('elx_chat_close');
        //disminuir la cuenta de las conversaciones minimizadas y en caso de no quedar niguna ocultar tab notificaciones
        removeElxUserNotifyChatMini(tabChat);
    });
});
function elxTitleAlert(message){
     $.titleAlert(message, {
        requireBlur:true,
        stopOnFocus:true,
        interval:600
    });
}
function adjustHeightElxListUser(){
    h = $("#b3_1").height();
    max_h=h-$("#head_rightdiv").height()-15;
    $("#elx_im_list_contacts").css('height',max_h+"px");
}
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
function showElxUFMsgBar(status,msgbar){
    if(status=='error'){
        $("#elx_msg_area_text").removeClass("alert-success").addClass("alert-danger");
    }else{
        $("#elx_msg_area_text").removeClass("alert-danger").addClass("alert-success");
    }
    $("#elx_msg_area_text").html(msgbar);
    $("#elx_msg_area").slideDown(); 
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
                            var div=createDivContact(arrData[typeAcc][x]['idUser'],arrData[typeAcc][x]['display_name'],arrData[typeAcc][x]['uri'],arrData[typeAcc][x]['alias'],arrData[typeAcc][x]['presence'],arrData[typeAcc][x]['st_code']);
                            $("#elx_ul_list_contacts").append(div);
                        }
                    }
                }
            }
        }
    );
}
function createDivContact(idUser,display_name,uri,alias,presence,presence_code){
    var color=getColorPresence(presence_code);
    var divContact ='<li id="elx_li_contact" class="margin_padding_0" data-uri="'+uri+'" data-alias="'+alias+'" data-name="'+display_name+'" data-idUser="'+idUser+'"><div class="elx_contact">';
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
        uri = message.remote_identity;
        if(message.direction === 'incoming'){
            var display_name = request.from.display_name || request.from.uri.user;
            var elx_txt_chat = request.body;
            
            if(uri instanceof JsSIP.URI) {
                var uri2=uri.toAor().substr(4);
            }else{
                //revisar el formato de string y parsearlo para asegurarnos que tenemos
                //algo con el  fromato user@domain
                var uri2=uri.substr(4);
            }
            //verificamos si existe una conversacion abierta con el dispositivo
            //si no existe la creamos
            var elx_tab_chat=startChatUser(uri2,display_name,uri2,'receive');
            if(!elx_tab_chat.hasClass('elx_chat_min')){
                if(!elx_tab_chat.find('.elx_text_area_chat > textarea').is(':focus')){
                    //añadimos clase que torna header anaranjado para indicar que llego nuevo mensaje
                    elx_tab_chat.children('.elx_header_tab_chat').addClass('elx_blink_chat');
                }
            }
            elxTitleAlert("New Message "+display_name);
            addMessageElxChatTab(elx_tab_chat,'in',elx_txt_chat);
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
function sendMessage(msg_txt,alias)
{
    var eventHandlers = {
        'succeeded'   : function(e){ /* Your code here */ },
        'failed'      : function(e) { 
                var response = e.data.response;
                var elx_tab_chat=getTabElxChat(alias);
                if(elx_tab_chat!=false){
                    if (response)
                        var error_msg='<span style="color:red">'+response.status_code.toString()+" "+response.reason_phrase+'</span>';
                    else
                        var error_msg='<span style="color:red">'+e.data.cause.toString()+'</span>';
                        addMessageElxChatTab(elx_tab_chat,'in',error_msg);
                }else{
                    if (response)
                        alert(response.status_code.toString()+" "+response.reason_phrase);
                    else
                        alert(e.data.cause.toString());
                }
            },
    };
    var options = { 'eventHandlers': eventHandlers };
    elx_phone.sendMessage("sip:"+alias, msg_txt, options);
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
/**
 * function que busca el div que contine la conversacion de un usario dado su alias
 * En caso de existir retorna el div del chat
 * Si no existe retorn false
 **/
function getTabElxChat(alias){
    var chatTab=null;
    $("#elx_chat_space_tabs > .elx_tab_chat").each(function(i, chat) {
      if (alias == $(this).attr("data-alias")) {
        chatTab = $(chat);
        return false;
      }
    });
    if (chatTab)
      return chatTab;
    else
      return false;
}
//funcion que crea una nueva pestaña de chat
//con las opciones dadas
//devuelve el objeto jquery del div que continene el chat
function startChatUser(uri,name,alias,action){
    //name
    //uri chat
    //verificamos si existe la ventana actual, si no existe se la crea
    var elx_tab_chat=getTabElxChat(alias);
    var existTabChat=false;
    if(!elx_tab_chat){
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
        
        var content="<div class='elx_tab_chat elx_chat_min' data-alias='"+alias+"' data-uri='"+uri+"'>";
        content +=elx_im_cabecera+"<div class='elx_body_tab_chat'>"+elx_im_cabecera2+conversation+elx_text_area_chat+"</div>";
        content +="</div>";
    
        var can_add_chat=resizeElxChatTab($(window).width(),action);
        //añadimos el nuevo chat
        $("#elx_chat_space_tabs").prepend(content);
        elx_tab_chat = $("#elx_chat_space_tabs > .elx_tab_chat:first");
        if(can_add_chat){
            elx_tab_chat.removeClass('elx_chat_min').addClass('elx_chat_active');
        }
    }else{
        //la ventana existe y esta activa, no tenemos nada que hacer
        var can_add_chat=true;
        if(!elx_tab_chat.hasClass('elx_chat_active')){
            //la ventana solicitada esta minimizada o fue cerrada nateriormente
            //procedemos a abrirla pero antes comprabamos si ahi sufieciente espacio para ello
            var can_add_chat=resizeElxChatTab($(window).width(),action);
            if(can_add_chat){
                //funcion que maneja el hecho de que aparezca una venta del chat que estaba aculta
                if(elx_tab_chat.hasClass('elx_chat_close')){
                    //si existia el tab pero tenia esta clase significa que se chateo en un momento pero
                    //de ahi se cerro la ventana del chat por lo que la volvemos a abrir
                    elx_tab_chat.removeClass('elx_chat_close').addClass('elx_chat_active');
                    removeElxUserNotifyChatMini(elx_tab_chat);
                }else if(elx_tab_chat.hasClass('elx_chat_min')){
                    elx_tab_chat.removeClass('elx_chat_min').addClass('elx_chat_active');
                    removeElxUserNotifyChatMini(elx_tab_chat);
                }
            }
        }
    }
    //se recibio un nuevo mensaje y la pestaña del chat del que envia el mensaje no puede
    //ser abierta por falta de espacio
    //debemos mostrar una notificacion del nuevo mensaje
    if(action=='receive' && !can_add_chat){
        addElxUserNotifyChatMini(elx_tab_chat);
        elx_tab_chat.removeClass('elx_chat_close').addClass('elx_chat_min');
        $("#elx_notify_min_chat_box").addClass('elx_notify_min_chat_box_act');
        //necesitamos subrayar dentro de la lista de los chats minimizados, el item que corresponde a quien mando el mensaje
        $('#elx_list_min_chat > div > .elx_list_min_chat_ul > .elx_list_min_chat_li').each(function() {
            if(alias == $(this).attr('data-alias')){
                $(this).find(".elx_min_name > .elx_min_chat_num").css('visibility','inherit');
            }
        });
    }
    return elx_tab_chat;
}

/**
 * Esta funcion revisa el ancho de la pantalla y dependiendo del mismo determina cual es el maximo 
 * número de pestañas que pueden estar activas
 * recibe como parámetros el tamaño de la pantalla y la acción que hace que sea necesario empezar 
 * un nueva conversación
 * Las acciones pueden ser:  sent o receive
 *      sí la acción es 'sent' => 1) si ya se ha alcanzado el máximo número de pestañas activas
 *                                   se procede a minimizar una de las pestañas activas para hacer espacio
 *                                   para la nueva pestaña, retornamos true
 *                                 2) si no se ha alcanzado el máximo número de pestañas activas retornamos true
 *      sí la acción es 'receive' => 1) si se alcanzo el máxmimo número de pestañas activas retornamos false
 *                                   2) si no se ha alcanzo el máxmimo número de pestañas activas retornamos true 
 **/
function resizeElxChatTab(elx_w,action){
    var max_tab=getMaxNumTabChat(elx_w); 
    
    //revisar el número de pestañas activas
    var num_act_chat=$("#elx_chat_space_tabs > .elx_chat_active").size();
    
    //al número actual de chats abiertos le sumamos uno del nuevo chat que vamos a abrir
    if((num_act_chat+1)>max_tab){
        if(action=='sent'){
            var elxTabChatToMin=$("#elx_chat_space_tabs > .elx_chat_active").first();
            elxTabChatToMin.removeClass('elx_chat_active').addClass('elx_chat_min');
            addElxUserNotifyChatMini(elxTabChatToMin);
            return true;
        }else{
            return false;
        }
    }else{
        return true;
    }
}
function getMaxNumTabChat(elx_w){
    //esta la venta del chat abierta
    var is_chat_open=!$("#rightdiv").is(':hidden');
    var max_tab=1; 
    //se definio que el máximo número de pestañas abiertas que podían haber sin importar 
    //tamaño sería 4
    if(is_chat_open){
        if(elx_w>1200){
            max_tab=4;
        }else if(elx_w>=960 && elx_w<1200){
            max_tab=3;
        }else if( elx_w>=730 && elx_w<960){
            max_tab=2;
        }
    }else{
        if(elx_w>=1010){
            max_tab=4;
        }else if( elx_w>=780 && elx_w<1010){
            max_tab=3;
        }else if(elx_w>=550 && elx_w<780){
            max_tab=2;
        }
    }
    //para tamaños menores de pantalla el número máximo de pestañas es 1
    
    return max_tab;
}
function errorRegisterChatBar(error){
    alert(error);
    $('#b3_1').css('display','none');
    $('#startingSession').html(error);
    $('#startingSession').css({'display':'block',margin:'5px'});
}
function addMessageElxChatTab(chatTab,direction,message){
     var elx_content_chat = chatTab.children('.elx_body_tab_chat:first').children('.elx_content_chat:first');
    
    if(direction=='out'){
        elx_who="<b>me: </b>";
    }else{
        
        var send_name=chatTab.find('.elx_tab_chat_name > .elx_tab_chat_name_span').text();
        if(send_name!=='undefined' && send_name!=''){
            //si el nombre del que esta enviando el mensaje contine espacios en blanco
            //lo cortamos para evitar que el nombre ocupe mucho espacio
            var space_pos=send_name.indexOf(" ");
            if(space_pos!=-1){
                elx_who="<b>"+send_name.substr(0,space_pos)+": </b>";
            }else{
                elx_who="<b>"+send_name+": </b>";
            }
        }else{
            elx_who="<b>receive: </b>";
        }
    }
    elx_content_chat.append("<div>"+elx_who+message+"</div>");
    elx_content_chat.scrollTop(1e4);
}
function addElxUserNotifyChatMini(elxTabChatToMin){
    alias=elxTabChatToMin.attr('data-alias');
    var exist=false;
    var numMinchat=0;
    //debemos comprabar si el nuevo tab que se quiere añadir ya se encuentra en la lista
    //si se encuentra entonces no lo añadimos
    $('#elx_list_min_chat > div > .elx_list_min_chat_ul > .elx_list_min_chat_li').each(function() {
        if(alias == $(this).attr('data-alias')){
            exist=true;
        }else
            numMinchat++;
    });
    
    if(!exist){
        name=elxTabChatToMin.find('.elx_tab_chat_name > .elx_tab_chat_name_span').html();
        var elx_chat_user = "<li class='elx_list_min_chat_li' data-alias='"+alias+"'>";
        elx_chat_user +="<span class='elx_min_span'>";
        elx_chat_user +="<div class='glyphicon glyphicon-remove elx_min_remove'></div>"; //imagen de x para cerrar
        elx_chat_user +="<div class='elx_min_name'><span class='elx_min_chat_num' style='visibility:hidden'>*</span><span class='elx_min_chat_name'>"+name+"</span></div>";
        elx_chat_user +="</span>";
        elx_chat_user +="</li>";
        
        $('#elx_list_min_chat > div > .elx_list_min_chat_ul').prepend(elx_chat_user);
    
        //actualizamos el numero de chat minimizados
        $("#elx_num_mim_chat").html(numMinchat+1);
        
        //si no se estaba mostrando el chat con las notificaciones lo mostramos
        $("#elx_notify_min_chat").removeClass('elx_nodisplay');
    }
}
function removeElxUserNotifyChatMini(elxTabChatToMin){
    alias=elxTabChatToMin.attr('data-alias');
    var numMinchat=0
    $('#elx_list_min_chat > div > .elx_list_min_chat_ul > .elx_list_min_chat_li').each(function() {
        if(alias == $(this).attr('data-alias')){
            $(this).remove();
        }else
            numMinchat++;
    });
    if(numMinchat==0){
        //ocultamos el div con las notificaciones
        $("#elx_notify_min_chat").addClass('elx_nodisplay');
        $("#elx_notify_min_chat_box").removeClass('elx_notify_min_chat_box_act');
        $('#elx_hide_min_list').val('no');
        $("#elx_list_min_chat").css('visibility','hidden');
    }else{
        //actualizazamos la informacion del numero de chat abiertos minimizados
        $("#elx_num_mim_chat").html(numMinchat);
    }
}
function adjustTabChatToWindow(elxw){ 
    //contralamos el número de pestañas activas abiertas de acuerdo al tamamaño de la pantalla
    var max_tab=getMaxNumTabChat(elxw); 
    //revisar el número de pestañas activas
    var num_act_chat=$("#elx_chat_space_tabs > .elx_chat_active").size();
    //si el número de pestañas activas es mayor que el máximo
    //entones procedemos a minimizar pestañas
    if(num_act_chat>max_tab){
        for(var i=0;i<(num_act_chat-max_tab);i++){
            var elxTabChatToMin=$("#elx_chat_space_tabs > .elx_chat_active").first();
            elxTabChatToMin.removeClass('elx_chat_active').addClass('elx_chat_min');
            addElxUserNotifyChatMini(elxTabChatToMin);
        }
    }else{
        //si existen pestañas minimizadas y el número de activas es menor que el máximo procedemos
        //a minizar pestañas
        if(num_act_chat < max_tab){
            for(var i=0;i<(max_tab-num_act_chat);i++){
                //si existen entonces abrimos la ultima pestaña
                var chatMIn=$("#elx_chat_space_tabs > .elx_chat_min").last();
                if(chatMIn!=='undefined'){
                    chatMIn.removeClass('elx_chat_min').addClass('elx_chat_active');
                    removeElxUserNotifyChatMini(chatMIn);
                }else{
                    break;
                }
            }
        }
    }
}


function elxGridData(moduleName, action, arrFilter, page){
    var currentNumPage=$("#elxGridNumPage").val();
    
    //validar si page es un numero
    if(isNaN(page)){
        page=1;
    }else{
        if (page % 1 != 0) {
            page=1;
        } 
    }

    
    if(page>currentNumPage){
        page=currentNumPage;
    }
    
    var arrAction = new Array();
    arrAction["menu"]=moduleName;
    arrAction["action"]=action;
    arrAction["page"]=page;
    arrAction["nav"]='bypage';
    
    for( var x in arrFilter){   
        arrAction[x]=arrFilter[x];
    }
    
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            if (error != '' ){
                $("#message_area").slideDown();
                $("#msg-text").removeClass("alert-success").addClass("alert-danger");
                $("#msg-text").html(error['stringError']);
                // se recorre todos los elementos erroneos y se agrega la clase error (color rojo)
            }else{
                var content='';
                var grid=arrData['content'];
                for(var i=0;i<grid.length;i++){
                    content+='<tr>';
                    for(var j=0;j<grid[i].length;j++){
                        content +="<td>"+grid[i][j]+"</td>";
                    }
                    content+='</tr>';
                }
                $("#elx_data_grid > table > tbody").html(content);
                
                var url=arrData['url'];
                
                var newUrl=url+"&exportcsv=yes&rawmode=yes"; 
                
                $("#exportcsv > a").attr('href', newUrl);
                $("#exportspreadsheet > a").attr('href', newUrl);
                $("#exportpdf > a").attr('href', newUrl);
                
                if(arrData['numPage']==0){
                    arrData['numPage']=1;
                    page=1;
                }
                
                $("#elxGridNumPage").val(arrData['numPage']);
                $("#elxGridCurrent").val(page);
                
                var options = {
                    currentPage: page,
                    totalPages: $("#elxGridNumPage").val(),
                    }
                
                $('#elx_pager').bootstrapPaginator(options); 

            }
    });
}