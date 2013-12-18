$(document).ready(function(){
    elx_mail_messages = $('#elx_mail_messages');
    elx_bodymail= $('#elx_bodymail');
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
 
    $(filter_pull).on('click', function(e) {
        prueba_filter.slideToggle();
    });
    
    //this is necesary to avoid appeard scroll in the windown
    main_content_div.css('overflow','hidden');
    
    $(pull2).on('click', function(e) {
        var w = $(window).width();
        var content_w=main_content_div.width();
        //panel está oculto y lo vamos a abrir
        if(leftdiv.is(':hidden')){
            leftdiv.show(10);
            if(w>=600){
                centerdiv.css('margin-left',140);
            }else{
                centerdiv.css('margin-left',0);
            }
            $('#display1').css('left',140);
        }else{
            //panel está abierto lo vamos a ocultar
            leftdiv.hide(10);
            centerdiv.css('margin-left',0);
            $('#display1').css('left',0);
        }
    });
    $(window).resize(function(){
        w = $(window).width();
        var content_w=main_content_div.width();
        if(w>=600){
            var center_w = centerdiv.width();
            if(leftdiv.is(':hidden')==false){ //el panel izquierdo esta abierto
                centerdiv.css('margin-left',140);
            }else{ //el panel izquierdo esta cerrado
                centerdiv.css('margin-left',0);
            }
        }else{
            centerdiv.css('margin-left',0);
        }
        h_main_content_div=main_content_div.height();
        leftdiv.css('height',main_content_div.height()+'px');
        $('#email_contentdiv').css('max-height',(h_main_content_div-55)+'px');
        
    });
    
    h_main_content_div=main_content_div.height();
    leftdiv.css('height',h_main_content_div+'px');
    $('#email_contentdiv').css('max-height',(h_main_content_div-55)+'px');
    
    //fix email filter1_email
    var btnh=$("#elx_email_fil_view > .btn-group > .btn:first").height();
    $("#elx_email_fil_view > .btn-group > .dropdown-toggle").height(btnh+"px");
    
    $(".elx_close_email_msg").click(function() {
        $("#initial_message_area").slideUp();
        $("#message_area").slideUp();
    });
    
    $("#email_new").click(function() {
        
    });
    
    $("#email_refresh").click(function() {
        $("input[name='elx_sel_view_filter_h']").val('all');
        show_email_msg()
    });
    
    $("#email_trash").click(function() {
        if($("input[name='current_mailbox']").val()=='Trash'){
            delete_msg_trash();
        }else{
            mv_msg_to_folder('Trash');
        }
    });
    
    $(this).on("click",".elx_row_email_msg",function(e){
        var UID=$(this).parent('.elx_row').attr('id');
        view_body(UID)
    });
    //mark msg as important
    $(this).on("click",".elx_unflagged_email",function(e){
        var UID=$(this).parents('.elx_row').attr('id');
        toggle_important('flagged',UID);
    });
    //mark msg as unimportant
    $(this).on("click",".elx_flagged_email",function(e){
        var UID=$(this).parents('.elx_row').attr('id');
        toggle_important('unflagged',UID);
    });
});
function show_email_msg(){
    showElastixUFStatusBar("Searching...");
    var arrAction = new Array();
    arrAction["menu"]="home";
    arrAction["action"]="show_messages_folder";
    arrAction["folder"]=$("input[name='current_mailbox']").val();
    arrAction["email_filter1"]=$("input[name='elx_sel_view_filter_h']").val();
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            hideElastixUFStatusBar();
            if(error!=""){
                alert(error);
            }else{
                var mailMsg=arrData['email_content'];
                if(mailMsg.length>0){
                    var messaje_list='';
                    for( var i=0; i<mailMsg.length; i++){
                        for( var j=0; j<mailMsg[i].length; j++){
                            messaje_list +=mailMsg[i][j];
                        }
                    }
                }else{
                    //no ahi mensaje para mostrar mostramos un mensaje
                    messaje_list='<div class="elx_row elx_unseen_email" style="text-align:center">There is not message</div>';
                }
                
                //este es para marcar por el valor correcto en el filtro1 (seen,unseen, ...)
                $("input[name='elx_sel_view_filter_h']").val(arrData['email_filter1']);
                var name_tag=$("#elx_email_vsel_"+arrData['email_filter1']).html();
                $("#elx_sel_view_filter").html(name_tag);
                
                elx_mail_messages.html(messaje_list);
                elx_bodymail.hide(10);
                $("#elx-bodymsg-tools").hide(10);
                $("#tools-paginationdiv").show(10);
                elx_mail_messages.show(10);
            }     
    });
}
function show_messages_folder(folder){
    showElastixUFStatusBar("Loading...");
    var arrAction = new Array();
    arrAction["menu"]="home";
    arrAction["action"]="show_messages_folder";
    arrAction["folder"]=folder;
    arrAction["email_filter1"]=$("#email_filter1 option:selected").val();
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            hideElastixUFStatusBar();
            if(error!=""){
                alert(error);
            }else{
                $("input[name='current_mailbox']").val(folder);
                var mailMsg=arrData['email_content'];
                if(mailMsg.length>0){
                    var messaje_list='';
                    for( var i=0; i<mailMsg.length; i++){
                        for( var j=0; j<mailMsg[i].length; j++){
                            messaje_list +=mailMsg[i][j];
                        }
                    }
                }else{
                    //no ahi mensaje para mostrar mostramos un mensaje
                    messaje_list='<div class="elx_row elx_unseen_email" style="text-align:center">There is not message</div>';
                }
                //actualizamos el listado de carpetas a las que podemos mover los mensajes seleccionados
                var li_mailbox_mv='';
                var listMailboxMv=arrData['move_folders'];
                for( var x in listMailboxMv){
                    li_mailbox_mv +="<li><a href='#' onclick='mv_msg_to_folder(\""+x+"\")'>"+listMailboxMv[x]+"</a></li>";
                }
                $("#elx_email_mv_ul").html(li_mailbox_mv);
                
                elx_mail_messages.html(messaje_list);
                elx_bodymail.hide(10);
                $("#elx-bodymsg-tools").hide(10);
                $("#tools-paginationdiv").show(10);
                elx_mail_messages.show(10);
            }     
    });
}
function search_email_message_view(id_tag){
    $("input[name='elx_sel_view_filter_h']").val(id_tag);
    show_email_msg();
}
function mv_msg_to_folder(folder){
    //necesito obtener la lista de los mails seleccionados
    var listUIDs='';
     $('.checkmail:checked').each(function (e){
        if(typeof $(this).val() === "string"){
            listUIDs +=$(this).val()+",";
        }
    });
    if(listUIDs!=''){
        showElastixUFStatusBar("Doing...");
        var arrAction = new Array();
        arrAction["menu"]="home";
        arrAction["action"]="mv_msg_to_folder";
        arrAction["current_folder"]=$("input[name='current_mailbox']").val();
        arrAction["new_folder"]=folder;
        arrAction["UIDs"]=listUIDs;
        arrAction["rawmode"]="yes";
        request("index.php", arrAction, false,
            function(arrData,statusResponse,error){
                hideElastixUFStatusBar();
                if(error!=""){
                    alert(error);
                    showElxUFMsgBar('error',error);
                }else{
                    showElxUFMsgBar('success',arrData);
                    show_email_msg();
                }     
        });
    }
}
function mark_email_msg_as(tag){
    //necesito obtener la lista de los mails seleccionados
    var listUIDs='';
    $('.checkmail:checked').each(function (e){
        if(typeof $(this).val() === "string"){
            listUIDs +=$(this).val()+",";
        }
    });
    if(listUIDs!=''){
        showElastixUFStatusBar("Doing...");
        var arrAction = new Array();
        arrAction["menu"]="home";
        arrAction["action"]="mark_msg_as";
        arrAction["folder"]=$("input[name='current_mailbox']").val();
        arrAction["tag"]=tag;
        arrAction["UIDs"]=listUIDs;
        arrAction["rawmode"]="yes";
        request("index.php", arrAction, false,
            function(arrData,statusResponse,error){
                hideElastixUFStatusBar();
                if(error!=""){
                    alert(error);
                    showElxUFMsgBar('error',error);
                }else{
                    showElxUFMsgBar('success',arrData);
                    show_email_msg();
                }     
        });
    }
}
function toggle_important(tag,uid){
    var arrAction = new Array();
    arrAction["menu"]="home";
    arrAction["action"]="toggle_important";
    arrAction["folder"]=$("input[name='current_mailbox']").val();
    arrAction["tag"]=tag;
    arrAction["uid"]=uid;
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            if(error!=""){
                alert(error);
            }else{
                if(tag=='flagged'){
                    $("#"+uid+" > div.ic > div.star > span").removeClass('elx_unflagged_email').addClass('elx_flagged_email');
                }else{
                    $("#"+uid+" > div.ic > div.star > span").removeClass('elx_flagged_email').addClass('elx_unflagged_email');
                }
                    
            }     
    });
}
function delete_msg_trash(){
    var listUIDs='';
    $('.checkmail:checked').each(function (e){
        if(typeof $(this).val() === "string"){
            listUIDs +=$(this).val()+",";
        }
    });
    if(listUIDs!=''){
        showElastixUFStatusBar("Doing...");
        var arrAction = new Array();
        arrAction["menu"]="home";
        arrAction["action"]="delete_msg_trash";
        arrAction["UIDs"]=listUIDs;
        arrAction["rawmode"]="yes";
        request("index.php", arrAction, false,
            function(arrData,statusResponse,error){
                hideElastixUFStatusBar();
                if(error!=""){
                    alert(error);
                    showElxUFMsgBar('error',error);
                }else{
                    showElxUFMsgBar('success',arrData);
                    show_email_msg();
                }     
        });
    }
}
function view_body(UID){
    showElastixUFStatusBar("Loading...");
    var arrAction = new Array();
    arrAction["menu"]="home";
    arrAction["action"]="view_bodymail";
    arrAction["uid"]=UID;
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
            function(arrData,statusResponse,error){
                hideElastixUFStatusBar();
                if(error!=""){
                    alert(error);
                }else{
                    createBodyMsg(arrData);
                    elx_mail_messages.hide(10);
                    $("#tools-paginationdiv").hide(10);
                    elx_bodymail.show(10);
                    $("#elx-bodymsg-tools").show(10);
                    $('#'+UID).removeClass('elx_unseen_email').addClass('elx_seen_email');
                }     
        });
}
function createBodyMsg(arrData){
    
    var subject="<div id='elx_bodymsg_subject'>";
    subject +="<h1>"+arrData['header']['subject']+"</h1>";
    subject +="</div>";
    
    var hTable=new Array('fromaddress','toaddress','date','ccaddress','bccaddress');
    var header="<div id='elx_bodymsg_header'>";
    header +="<table id='elx_bodymsg_theader'>";
    for( var x in hTable){
        if(typeof arrData['header'][hTable[x]] !== 'undefined'){
            if(arrData['header'][hTable[x]]['content'] != ''){
                header +="<tr class='elx_bodymsg_trheader'>";
                header +="<td class='elx_bodymsg_tdheader'>"+arrData['header'][hTable[x]]["tag"]+":</td>";
                header +="<td class='elx_bodymsg_tdheader'>"+arrData['header'][hTable[x]]["content"]+"</td>";
                header +='</tr>';
            }
        }
    }
    header +="</table>";
    header +="</div>";
    
    var divattachment='';
    if(typeof arrData['attachment'] !== 'undefined'){
        var attachment=arrData['attachment'];
        if(attachment.length > 0){
            divattachment="<div id='elx_bodymsg_attachment'>";
            divattachment +="<img src='web/apps/home/images/Paper-Clip.png' style='background-color: white;'  class='elx_bodymsg_file_att' />";
            for( var i=0; i<attachment.length ; i++){
                divattachment +="<div class='elx_bodymsg_file_att'><a href='index.php?menu=home&action=download_attach&rawmode=yes&enc="+arrData['attachment'][i]['enc']+"&partnum="+arrData['attachment'][i]['partNum']+"'>"+arrData['attachment'][i]['name']+"</a></div>";
            }
            divattachment +="</div>";
        }
    }
    
    
    var content="<div id='elx_bodymsg_body'>";
    if(typeof arrData['body']['html']!=='undefined'){
        for(var x in arrData['body']['html']){
            content +=arrData['body']['html'][x]+'</br></br>';
        }
    }else if(typeof arrData['body']['plaintext']!=='undefined'){
        for(var x in arrData['body']['plaintext']){
            content +=arrData['body']['plaintext'][x]+'</br></br>';
        }
    }
    content +="</div>";

    var bodymail=subject+header+divattachment+content;
    elx_bodymail.html(bodymail);
}


 


