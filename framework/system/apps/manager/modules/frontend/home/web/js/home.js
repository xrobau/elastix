$(document).ready(function(){
 table = $('#table');
 bodymail= $('#bodymail');
 newmail= $('#createmail');
 checkmail= $("input[name=checkmail]"); 
 row=$('.row'); 
 filter_pull =$('#filter_but');
 pull2 = $('#icn_disp1');
 prueba_filter= $('#filterdiv');
 pull = $('#pull');
 menu = $('nav > ul');
 menuHeight = menu.height();
 leftdiv = $('#leftdiv');
 centerdiv = $('#centerdiv');
 rightdiv = $('#rightdiv');
 pull3 = $('#icn_disp2');                    
 paginationdiv = $('#paginationdiv');
 contentdiv = $('#contentdiv');
 
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
    
    /* evento que modifica el estilo de todos los paneles, al pulsar el icono para desplegar u ocultar 
    el panel lateral izquierdo (leftpanel)*/      
    $(pull2).on('click', function(e) {    
        var w = $(window).width();
        if(rightdiv.is(':hidden') == false && leftdiv.is(':hidden')) {                                        
        leftdiv.show(10);   
            set_size (w,"left",320,140);
        } else{ if(rightdiv.is(':hidden')==false && leftdiv.is(':hidden')==false){
                leftdiv.hide(10);
                set_size (w,"left",180,0);
                } else{ if(rightdiv.is(':hidden') && leftdiv.is(':hidden')){
                    leftdiv.show(10);
                        set_size (w,"left",140,140);         
                    } else{
                                leftdiv.hide(10);
                                set_size (w,"left",0,0);
                                }
                    }
            } 
    });
    
    function set_size (w,position,size_tpanels,size_margin){
        var t=w-size_tpanels;
        paginationdiv.css("margin-"+position,size_margin+"px");
        if(w>=700){
            contentdiv.css("margin-"+position,size_margin+"px");
            contentdiv.css("width",t+"px");
        }else if(w<700) {
            set_size_contentdiv(w);
            if(w<400){
                if(leftdiv.is(':hidden')==false && rightdiv.is(':hidden')==false){ 
                    if(position=="left"){
                        rightdiv.hide(10);
                        paginationdiv.css("margin-right","0px");
                    }
                    if(position=="right"){
                        leftdiv.hide(10);
                        paginationdiv.css("margin-left","0px");
                    }
                    
                }
                }
            }
    }
    
    $(window).resize(function(){
        w = $(window).width();
        var tmpSize=0;
        
        /*setea el ancho del panel central al minimizar o maximar la pantalla dependiendo
        del estado de los paneles laterales */
        if(w>=700){
        if(rightdiv.is(':hidden') == false){
            tmpSize= tmpSize + 180;
            contentdiv.css("margin-right","180px");
        }
        if(leftdiv.is(':hidden') == false){
        tmpSize= tmpSize + 140;
        contentdiv.css("margin-left","140px");
        }
        }  
        tmpSize = w - tmpSize;
        contentdiv.css("width",tmpSize+"px");
        
        /*setea margenes y ancho del panel central para que los paneles laterales se superpongan*/
        if (w<700 && (rightdiv.is(':hidden') == false || leftdiv.is(':hidden') == false ) )
        set_size_contentdiv(w); 
        
        /*cierra el panel de chat en caso de minimizar pantalla con los 2 paneles abiertos*/    
        if (w<400 && rightdiv.is(':hidden') == false && leftdiv.is(':hidden') == false  ){
        rightdiv.hide(10);
        paginationdiv.css("margin-right","0px");
        }
        
        /*setea el estilo del menu una vez que se maximiza la pantalla*/   
        if(menu.is(':hidden')) 
        menu.removeAttr('style');
        
    });
    
    /*funcion que setea los margenes a 0px y el ancho enviado por parametro 
    del panel central (contentdiv)*/                
    function set_size_contentdiv(w){
        contentdiv.css("margin-left","0px");
        contentdiv.css("width",w+"px");
        contentdiv.css("margin-right","0px");
    }
});

function view_body(UID){
    table.hide(10);
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
                bodymail.append("<p>"+arrData+"</p>")
                    }     
        });
    bodymail.show(10);
    $('#0'+UID).attr('id','1'+UID);
    $('#1'+UID).css("background-color","rgb(255, 255, 255)");
}

function create_showInbox(){
    table.show(10);
    bodymail.hide(10);
    newmail.hide(10);
}

 


