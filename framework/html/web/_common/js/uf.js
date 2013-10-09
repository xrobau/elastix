$(document).ready(function(){
 pull = $('#pull');
 menu = $('nav ul');
 menuHeight = menu.height();
 pull2 = $('#icn_disp1');
 leftdiv = $('#leftdiv');
 centerdiv = $('#centerdiv');
 rightdiv = $('#rightdiv');
 pull3 = $('#icn_disp2');					
 paginationdiv = $('#paginationdiv');
 contentdiv = $('#contentdiv');
 filter_pull =$('#filter_but');
 prueba_filter= $('#filterdiv')
 /*despliegue del menu en pantallas pequeñas*/			
 $(pull).on('click', function(e) {
     e.preventDefault();
     menu.slideToggle();
 });
			
  $(filter_pull).on('click', function(e) {
     prueba_filter.slideToggle();
 });
						
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
	
 /* evento que modifica el estilo de todos los paneles, al pulsar el icono para desplegar u ocultar 
 el panel lateral derecho (rightpanel)*/			
 $(pull3).on('click', function(e) {	
    var w = $(window).width();
    if( leftdiv.is(':hidden') == false && rightdiv.is(':hidden')) {                                        
	rightdiv.show(10);	
        set_size (w,"right",320,180);     				
       }else { if(leftdiv.is(':hidden')==false && rightdiv.is(':hidden')==false){
                  rightdiv.hide(10);
                  set_size (w,"right",140,0);
                } else{	if(leftdiv.is(':hidden') && rightdiv.is(':hidden')){
			rightdiv.show(10);
                        set_size (w,"right",180,180);
			} else{
                               rightdiv.hide(10);
                               set_size (w,"right",0,0);   
                              }
                      }
             }
 });

 /*funcion que setea los margenes a 0px y el ancho enviado por parametro 
 del panel central (contentdiv)*/				
 function set_size_contentdiv(w){
      contentdiv.css("margin-left","0px");
      contentdiv.css("width",w+"px");
      contentdiv.css("margin-right","0px");
  }

 /*funcion que setea el estilo (anchos y margenes) utlizada al pulsar los iconos
 que despliegan u ocultan paneles*/
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
	
});
