$(document).ready(function(){
    elx_mail_messages = $('#elx_mail_messages');
    elx_bodymail= $('#elx_bodymail');
    checkmail= $("input[name=checkmail]"); 
    row=$('.row'); 
    filter_pull =$('#filter_but');
    pull2 = $('#icn_disp1');
    prueba_filter= $('#filterdiv');
    pull = $('#pull');
    leftdiv = $('#leftdiv');
    centerdiv = $('#centerdiv');
    rightdiv = $('#rightdiv');
    pull3 = $('#icn_disp2');                    
    paginationdiv = $('#paginationdiv');
    paneldiv = $('#paneldiv');
    main_content_div = $('#main_content_elastix');
 
    checkmail.on("click", function(){
    mailnum=this.value; 
        if(this.checked){
            $('#1'+mailnum).css("background-color","rgb(200,200,200)");
            $('#0'+mailnum).css("background-color","rgb(200,200,200)");
        }else { 
            $('#1'+mailnum).css("background-color","rgb(255, 255, 255)");
            $('#0'+mailnum).css("background-color","rgb(229, 229, 229)");
        }
    });
 
    $(filter_pull).on('click', function(e) {
        prueba_filter.slideToggle();
    });
    $(pull2).on('click', function(e) {
        var w = $(window).width();
        var content_w=main_content_div.width();
        //panel está oculto y lo vamos a abrir
        if(leftdiv.is(':hidden')){
            leftdiv.show(10);
            if(w>=600){
                centerdiv.css('left',140);
                //centerdiv.css('width',content_w-140);
            }else{
                $(pull2).css('margin-left',140);
                centerdiv.css('left',0);
                //centerdiv.css('width',content_w+"px");
            }
        }else{
            //panel está abierto lo vamos a ocultar
            leftdiv.hide(10);
            if(w>=600){
                centerdiv.css('left',0);
                //centerdiv.css('width',content_w+140);
            }else{
                $(pull2).css('margin-left',0);
                centerdiv.css('left',0);
                //centerdiv.css('width',content_w+"px");
            }
        }
    });
    $(window).resize(function(){
        w = $(window).width();
        var content_w=main_content_div.width();
        if(w>=600){
            var center_w = centerdiv.width();
            $(pull2).css('margin-left',0);
            if(leftdiv.is(':hidden')==false){ //el panel izquierdo esta abierto
                centerdiv.css('left',140);
                var new_w=content_w-140;
                //centerdiv.css('width',new_w+"px");
            }else{ //el panel izquierdo esta cerrado
                centerdiv.css('left',0);
                var new_w=content_w+140;
                //centerdiv.css('width',new_w+"px");
            }
        }else{
            if(leftdiv.is(':hidden')==false){
                $(pull2).css('margin-left',140);
            }else{
                $(pull2).css('margin-left',0);
            }
            centerdiv.css('left',0);
            //centerdiv.css('width',content_w+'px');
        }
    });
});

function view_body(UID){
    var arrAction = new Array();
    arrAction["menu"]="user_home";
    arrAction["action"]="view_bodymail";
    arrAction["idMail"]=UID;
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
            function(arrData,statusResponse,error){
                if(error!=""){
                    alert(error);
                }else{
                    bodymail.append("<p>"+arrData+"</p>");
                    elx_mail_messages.hide(10);
                    bodymail.show(10);
                    $('#'+UID).removeClass('elx_msg_unseen').addClass('elx_msg_seen');
                }     
        });
}

function show_messages_folder(folder){
    var arrAction = new Array();
    arrAction["menu"]="user_home";
    arrAction["action"]="show_messages_folder";
    arrAction["folder"]=folder;
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            if(error!=""){
                alert(error);
            }else{
                if(arrData.lenght>0){
                    var messaje_list='';
                    for( var i=0; i<arrData.length; i++){
                        var message='';
                        if(arrData[i]['status']==1){
                            var seen_class='elx_msg_seen';
                        }else{
                            var seen_class='elx_msg_unseen';
                        }
                        message='<div class="elx_row '+seen_class+'" onclick="view_body(\"'+arrData[i]['UID']+'\"); id="'+arrData[i]['UID']+'" ';
                        message +='<div class="sel"><input type="checkbox" value="'+arrData[i]['UID']+'" class="inp1" name="checkmail"/></div>';
                        message +='<div class="ic">';
                        message +='<div class="icon"><img border="0" src="web/apps/home/images/mail2.png" class="icn_buz"></td></div>';
                        message +='<div class="star"><span class="st">e</span></div>';
                        message +='<div class="trash"><span class="st">ç</span></div>';
                        message +='</div>';
                        message +='<div class="from" ><span>"'+arrData[i]['from']+'"</span></div>';
                        message +='<div class="subject" ><span>"'+arrData[i]['subject']+'"</span></div>';
                        message +='<div class="date" ><span>"'+arrData[i]['date']+'"</span></div>';
                        messaje_list +=message;
                    }
                    elx_mail_messages.html(messaje_list);
                    elx_bodymail.hide(10);
                    elx_mail_messages.show(10);
                }else{
                    //no ahi mensaje para mostrar mostramos un mensaje
                    '<div class="elx_row" style="style="background-color:rgb(229,229,229);">There is not message</div>'
                }
            }     
    });
}


 


