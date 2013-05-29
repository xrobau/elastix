var module_name = "dashboard";

$(document).ready(
	function()
	{
        $(".column").sortable({
            connectWith: ".column",
            forcePlaceholderSize: true,
            forceHelperSize: true,
            scroll: false,
            stop: function() { 
                    var td_left  = document.getElementById("td_columns1");
                    var td_right = document.getElementById("td_columns2");
                    var children_left  = td_left.childNodes;
                    var children_right = td_right.childNodes;
                    var ids_applet = "";

                    // Recorro los applet de la izquierda
                    var j = 1;
                    for(i=0; i<children_left.length;i++){
                        if(children_left[i].nodeName == "DIV" || children_left[i].nodeName == "div"){
                            var id_div = children_left[i].getAttribute("id");
                            var tmp = id_div.split("-");
                            if(tmp[0] == "applet"){
                                var id_applet = tmp[2];
                                ids_applet = ids_applet + id_applet + ":" + j + ",";
                                j = j+2;
                            }
                        }
                    }

                    // Recorro los applet de la derecha
                    j = 2;
                    for(i=0; i<children_right.length;i++){
                        if(children_right[i].nodeName == "DIV" || children_right[i].nodeName == "div"){
                            var id_div = children_right[i].getAttribute("id");
                            var tmp = id_div.split("-");
                            if(tmp[0] == "applet"){
                                var id_applet = tmp[2];
                                ids_applet = ids_applet + id_applet + ":" + j + ",";
                                j = j+2;
                            }
                        }
                    }

                    var order = 'menu=' + module_name + '&action=updateOrder&rawmode=yes&ids_applet=' + ids_applet;
                    $.post("index.php", order,function(theResponse){});
                }

  });

		// Toggle Single Portlet
		/*$('a.toggle').click(function()
			{
				var p2 = $(this).parent('div');
				var p3 = p2.parent('div').next('div').toggle();
				var imgarrow = $(this).children("img").attr("src");
				var id = $(this).children("img").attr("id");
				var valor = changeArrow(imgarrow,id);
				$(this).children("img").attr("src",valor);
				return false;
			}
		);*/

		// Invert All Portlets
		$('a#all_invert').click(function()
			{
				$('div.portlet_content').toggle();
				return false;
			}
		);

		// Expand All Portlets
		$('a#all_expand').click(function()
			{
				$('div.portlet_content:hidden').show();
				arrowsExpand();
				return false;
			}
		);

		// Collapse All Portlets
		$('a#all_collapse').click(function()
			{
				$('div.portlet_content:visible').hide();
				arrowsCollapse();
				return false;
			}
		);

		// Open All Portlets
		$('a#all_open').click(function()
			{
				$('div.portlet:hidden').show();
				$('a#all_open:visible').hide();
				$('a#all_close:hidden').show();
				return false;
			}
		);

		// Close All Portlets
		$('a#all_close').click(function()
			{
				$('div.portlet:visible').hide();
				$('a#all_close:visible').hide();
				$('a#all_open:hidden').show();
				return false;
			}
		);

        // Applet admin
        $('a#applet_admin,#close_applet_admin').click(function()
            { // variable statusDivAppletAdmin declarada en tpl applet_admin
                if(statusDivAppletAdmin=='open'){
                    $('div.portlet:hide').show();
                    $('a#all_close:hide').show();
                    $('div#div_applet_admin:visible').hide();
                    $('a#all_open:hide').show();
                    statusDivAppletAdmin='closed';
                }
                else{
                    $('div.portlet:visible').hide();
                    $('a#all_close:visible').hide();
                    $('div#div_applet_admin:hide').show();
                    $('a#all_open:visible').hide();
                    statusDivAppletAdmin='open';
                }
                return false;
            }
        );

        $('.neo-applet-processes-row-menu').live('click', neoAppletProcesses_manejarMenu);
	}
);

function saveRegister()
{
    var vendor = document.getElementById("manufacturer").value;
    var num_se = document.getElementById("noSerie").value;
    var id_card = $("#idCard").val();
    var urlImaLoading = "<img src='images/loading.gif' height='20px' /><span style='font-size: 14px; position: relative; top: -10px; left: -5px; '></span>";
   
    if(vendor != "" && num_se != ""){
        var order = 'menu=' + module_name + '&action=saveRegister&rawmode=yes&num_serie=' + num_se + '&hwd=' + id_card + '&vendor=' + vendor ;
	 $('.loading').html(urlImaLoading);   
	  
        $.post("index.php", order,
            function(theResponse){
              //  alert("Card has been registered");
	       $('.message').css('color', 'blue');
               $('.message').html("Card has been Registered"); 
	       $('.loading').html("");   
               window.open("index.php?menu=dashboard","_self");
        });
    }
    else{
        //alert("The data input are blank");
	 $('.message').css('color', 'red');
	$('.message').html("The data input are blank"); 
    }
}

function getDataCard()
{
    var id_card = $("#idCard").val();
    var order = 'menu=' + module_name + '&action=getRegister&rawmode=yes&hwd=' + id_card;

    $.post("index.php", order,
        function(theResponse){
            salida = theResponse.split(',');
  	    $('#lman').css('padding-left',10);	
	    $('#lser').css('padding-left',10);	
	    $('#lman').append('<label>'+salida[0]+'</label>');
            $('#lser').append('<label>'+salida[1]+'</label>');
	   
            
		
    });
}

function changeArrow(urlimg,id){
  var sal = "";
  var imgID = document.getElementById(id);
  if(urlimg.indexOf('flecha_down.gif')!=-1){ 
    sal = "modules/"+module_name+"/images/flecha_up.gif";
  }
  else{
    sal = "modules/"+module_name+"/images/flecha_down.gif";
  }
  return sal;
}

function arrowsCollapse(){
  for(var i=1; i<=12; i++){
    var id = "imga"+i;
    var imgID = document.getElementById(id);
    imgID.src = "modules/"+module_name+"/images/flecha_down.gif";
  }
}

function arrowsExpand(){
  for(var i=1; i<=12; i++){
    var id = "imga"+i;
    var imgID = document.getElementById(id);
    imgID.src = "modules/"+module_name+"/images/flecha_up.gif";
  }
}

function loadAppletData()
{
    var arrAction          = new Array();
    arrAction["action"]    = "loadAppletData";
    arrAction["rawmode"]   = "yes";
    request("index.php",arrAction,false,
	function(arrData,statusResponse,error)
	{
	    if(statusResponse != "end"){
		document.getElementById(arrData["code"]).innerHTML = arrData["data"];
		loadAppletData();
	    }
	}
    );
}

function jfunction(id)
{
    var arrID = id.split("_"); 
    var a_id_card = arrID[1];
    if(arrID[0]=="editMan1"){
	var arrAction = new Array();
    	arrAction["action"]  = "saveRegisterForm";
    	arrAction["rawmode"] = "yes";
	//var id = $(this).attr('id');
    	request("index.php",arrAction,false,
          function(arrData,statusResponse,error)
          {
  	      ShowModalPopUP(arrData['title'],285,120,arrData['html']);
	      $("#idCard").val(a_id_card);
          }
   	 );
    }//openWndMan1(a_id_card);
    else{
	var arrAction = new Array();
    	arrAction["action"]  = "saveRegisterForm";
    	arrAction["rawmode"] = "yes";
	//var id = $(this).attr('id');
    	request("index.php",arrAction,false,
          function(arrData,statusResponse,error)
          {
  	      ShowModalPopUP(arrData['title'],290,60,arrData['html']);
	      $("#idCard").val(a_id_card);
	      $('.message').css('color', 'blue');
	      $('.message').html(""); 
	      $('.viewButton').remove();
	      $('#manufacturer').remove();
	      $('#noSerie').remove();	
	      getDataCard();
	      
          }
   	 );
	}
   
}

function refresh(element)
{
    var code = $(element).attr("id");
    code = code.split("refresh_");
    code = code[1];
    var loading = $("#loading").val();
    // Se obtiene la imagen loading con su texto traducido
    $("#"+code).html("<img class='ima' src='modules/"+module_name+"/images/loading.gif' border='0' align='absmiddle' />&nbsp;"+loading);

    // Se realiza la petición para obtener los datos del applet
    var arrAction	 = new Array();
    arrAction["action"]  = "refreshDataApplet";
    arrAction["code"]    = code;
    arrAction["rawmode"] = "yes";
    request("index.php",arrAction,false,
        function(arrData,statusResponse,error)
        {
            $("#"+code).html(arrData);
        }
    );
}

function neoAppletProcesses_esconderMenu()
{
	$('.neo-applet-processes-menu').unbind('click');
	$('html').unbind('click', neoAppletProcesses_esconderMenu);
	$('.neo-applet-processes-menu').hide();
	return false;
}

// Mostrar menú de administración en applet de procesos
//function neoAppletProcesses_manejarMenu(divObject, sProc, sCurrState)
function neoAppletProcesses_manejarMenu(event)
{
	sCurrState = $(this).children('#status-servicio').val();
	isActivate = $(this).children('#activate-process').val();
	sProc = $(this).children('#key-servicio').val();
	if (sCurrState != 'OK' && sCurrState != 'Shutdown') return;
	
	if ($('.neo-applet-processes-menu').is(':visible')) {
		neoAppletProcesses_esconderMenu();
	} else {
		event.stopPropagation();

		// Operaciones para cerrar menú cuando se hace clic fuera
		$('.neo-applet-processes-menu').click(function(event) {
			event.stopPropagation();
		});
		$('html').click(neoAppletProcesses_esconderMenu);

		// Se recuerda qué proceso se va a manejar
		$('#neo_applet_selected_process').val(sProc);
		
		$('#neo-applet-processes-controles').show();
		$('#neo-applet-processes-processing').hide();
		
		$('.neo_applet_process').unbind('click');
		module_name = 'dashboard';
		
		$('.neo_applet_process').click(function() {
			$('#neo-applet-processes-controles').hide();
			$('#neo-applet-processes-processing').show();
			$.post('index.php?menu=' + module_name + '&rawmode=yes', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		$(this).attr('name'),
				process:	$('#neo_applet_selected_process').val()
			},
			
			function (respuesta) {
				neoAppletProcesses_esconderMenu();
				refresh($('#refresh_Applet_ProcessesStatus').get(0));
			});
		});
		
		$('.neo-applet-processes-menu').show();
		$('.neo-applet-processes-menu').css("position","fixed");
		$('.neo-applet-processes-menu').position({
			of: $(this),
			my: "right top",
			at: "right bottom",
			offset: "-7 1"
		});
		
		if (sCurrState == 'OK') {
			$('#neo_applet_process_stop').show();
			$('#neo_applet_process_restart').show();
			$('#neo_applet_process_start').hide();
		}
		if (sCurrState == 'Shutdown') {
			$('#neo_applet_process_stop').hide();
			$('#neo_applet_process_restart').hide();
			$('#neo_applet_process_start').show();
		}
		if(isActivate == '1')
		{
			$('#neo_applet_process_activate').hide();
			$('#neo_applet_process_deactivate').show(); 
		}else{
			$('#neo_applet_process_activate').show();
			$('#neo_applet_process_deactivate').hide();
		}
	}
}
