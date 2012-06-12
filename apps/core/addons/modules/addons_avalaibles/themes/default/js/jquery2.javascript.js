var module_name   = "addons_avalaibles";
var module_name2  = "addons_installed";
var percentValues = new Array();
var arrayLang     = null;
var refer = document.URL;

var there_install = false;

$(document).ready(function(){
    $('.install').click(function(){
    	var name_rpm = $(this).attr("id"); 
    	installAddon(name_rpm);
    });
    
    // proceso ajax q invoca a instalar los paquetes.....
    getPackagesCache();
    
    // nuevo para la compra de addons
    $('.buy').click(function(){
		var nameRpm = $(this).attr("id");
		nameRpm = nameRpm.replace(/_buy/gi,"");
		var idRpmName = "[id='"+nameRpm+"_link']";
		var link = $(idRpmName).val();
		window.open(link+refer);
    });
    
    $('.registrationServer').click(function(){
    	var arrAction = "action=getServerKey&rawmode=yes";
    	var nameRpm = $(this).attr("id");
		nameRpm = nameRpm.replace(/_buy/gi,"");
    	var idRpmName = "[id='"+nameRpm+"_link']";
    	var link = $(idRpmName).val();
        $.post("index.php",arrAction,
            function(arrData,statusResponse,error)
            {
				var message = JSONtoString(arrData);
				var serverKey = message["serverKey"];
				if(serverKey)
					window.open(link+refer+"&serverkey="+serverKey);
				else{
					$('#link_tmp').val(link+refer+"&serverkey=");
					showPopupElastix('registrar','Register',600,400);
				}
            }
        );
    });
    
    $('.updateAddon').tipsy({fade: true, html: true }); // se agrega la accion de tooltips
    $("[id^=progressBar]").progressbar({value: 0});
    $('#filter_field').change(function() {
    	_search(module_name);
    });

});



function installAddon(name_rpm)
{
      document.getElementById("msg_status").innerHTML = " ";
      if(!there_install){
	  var idRpmName = "[id='"+name_rpm+"']";
	  var startId = "[id='start_"+name_rpm+"']";
	  var idRpmNameBuy = "[id='"+name_rpm+"_buy']";
	  $(idRpmNameBuy).hide();
	  $(idRpmName).parent().parent().children(':first-child').children(':first-child').attr("style","display: none;");//oculta el loading
	  $(idRpmName).hide();//oculto el boton install
	  $(startId).attr("style","display: block;"); // muestra el gif de empezar a instalar

	  $('#progressBarTotal').remove(); // se remueve todos los elemento de progressbar que tenga como id progressBarTotal
	  $('#percent_loaded').remove();
	  var classRpm = "input[class='"+name_rpm+"']";
	  var data_exp = $(classRpm).val();
	  var order = 'menu='+module_name+'&name_rpm='+name_rpm+'&action=install&data_exp='+data_exp+'&rawmode=yes';
	  $.post('index.php',order,function(theResponse){
	      var message = JSONtoString(theResponse);
	      var name_rpm = message['name_rpm'];
	      var idRpmName = "[id='"+name_rpm+"']";
	      var idRpmNameBuy = "[id='"+name_rpm+"_buy']";
	      var startId = "[id='start_"+name_rpm+"']";
	      var nameAddons    = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
	      var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
	      if(message['error'] == "no_daemon"){
		  there_install = false;
		  $(idRpmName).parent().parent().children(':first-child').children(':first-child').attr("style","display: none;");//oculta el loading
		  $(idRpmName).show();//muestro el boton install
		  $(startId).attr("style","display: none;"); // oculta el gif de empezar a instalar
		  if(arrayLang)
		      alert(arrayLang['no_daemon']);
		  else
		      connectJSON("no_daemon");
		  var textDaemonOff = $('#textDaemonOff').val();
		  showPogressMessage(textDaemonOff);
		  return;
	      }
	      if(message['response'] == "there_install"){ //si existe una instalacion en progreso
		  there_install = true;
		  currentProcess();
		  /**** nueva implementacion ****/
		  // se muestra la barra de progreso de la instalacion
		  // se remueve el ultimo hijo y se añade el progressbar al final
		  $(startId).html(" ");
		  var progressbar  = "<div align='right'><div id='progressBarTotal' style='width: 100px;'></div><div id='percent_loaded'><span id='percentTotal' style='position: relative; top: -18px; right: 38px;'>0%</span></div><div class='timeDownload' style='position:relative; bottom:10px; color: #363636; font-weight: bold; font-size: 10px;'></div></div>";
		  $(startId).html(progressbar);
		  $(idRpmNameBuy).hide();
		  $("[id^=progressBar]").progressbar({value: 0});
		  getPercent();
		  /**** nueva implementacion ****/
	      }
	      else if(message['response'] == "OK"){ // listo para instalar
		      var textDownloading = $("#iniDownloading").val();
		      textDownloading = textDownloading.replace(/\./g,"");
		      showPogressMessage("Status: "+textDownloading+" "+nameAddons+" "+versionAddons);
		      $(idRpmNameBuy).hide();
		  there_install = true;
		  name_rpm = message['name_rpm'];
		  getStatusInstall(name_rpm);
	      }
	      else if(message['response'] == "error"){ // error no install
		  there_install = false;
		  $(idRpmNameBuy).show();
		  $(idRpmName).attr("style","display: block;");
		  $(startId).attr("style","display: none;");
		  document.getElementById("msg_status").innerHTML = " ";
		  if(arrayLang)
		      alert(arrayLang['error_start_install']);
		  else
		      connectJSON("error_start_install");
	      }
	  });
      }
      else
	  currentProcess();
}
      
function _search(module_name)
{
    var search_      = document.getElementById("search");
    var filter_field = $("#filter_field option:selected").val();
    window.open("index.php?menu="+module_name+"&action=search&addons_search="+search_.value+"&filter_field="+filter_field,"_self");
}

function enterEvent(event,module_name)
{
    if(event){
        if (event.keyCode == 13)
            _search(module_name);
    }
}

function getStatusInstall(name_rpm){
	var classRpm = "input[class='"+name_rpm+"']";
    var data_exp = $(classRpm).val(); // se recibe los datos q seran insertados en la db
    var order = 'menu='+module_name+'&action=get_status&data_exp='+data_exp+'&rawmode=yes';
    
    $.post("index.php", order,
        function(theResponse){
            response = JSONtoString(theResponse);
            var resp = response['response'];
            $('#action_install').val(resp);
            var status_action = response['status_action'];
            var name_rpm = response['name_rpm'];
            var idRpmName = "[id='"+name_rpm+"']";
            var idRpmNameBuy = "[id='"+name_rpm+"_buy']";
            var startId = "[id='start_"+name_rpm+"']";
	    var textIniDownloading = $('#iniDownloading').val();
            var textInstalling = $('#textDownloading').val();
            textInstalling = textInstalling.replace(/\./g,"");
      	    var nameAddons    = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
    	    var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
            if(resp == "OK"){
            	if(response['action'])
            		showPogressMessage("Status: "+textInstalling+" "+nameAddons+" "+versionAddons);
                changeStatus(response['name_rpm'],response['view_details']); // listo para instalar
            }
            else if(resp == "error"){
            	$(startId).hide();
            	$(idRpmName).show();
            	$(idRpmNameBuy).show(); // si es que existe el boton buy
            	$('#action_install').val("");
            	var errmsg = response['errmsg'];
            	showPogressMessage("Status: "+resp+" - "+errmsg);
            	clearAddons();
            }else{
           		showPogressMessage("Status: "+textIniDownloading+" "+nameAddons+" "+versionAddons);
                getStatusInstall(name_rpm);
            }
    });
}

function changeStatus(name_rpm, view_details ){
    // hide loading.gif
    $('.loading').attr("style","visibility:hidden;");
    var idRpmName = "[id='"+name_rpm+"']";
    var startId = "[id='start_"+name_rpm+"']";
    //$(idRpmName).parent().parent().children(':first-child').next().attr("style","visibility:visible;");
    //$(idRpmName).parent().parent().children(':first-child').next().children(':last-child').text(view_details);

    // seria bueno que se redireccione al modulo addons_installed, despues de 10 segundos si el usuario no lo ha hecho aun.
    // setTimeout("true",10000);
    // window.open(url_redirect,"_self");
    
    /**** nueva implementacion ****/
	// se muestra la barra de progreso de la instalacion
	// se remueve el ultimo hijo y se añade el progressbar al final
    var textInstalling = $('#textDownloading').val();
    textInstalling = textInstalling.replace(/\./g,"");
	var nameAddons    = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
	var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
	showPogressMessage("Status: "+textInstalling+" "+nameAddons+" "+versionAddons);
	$(startId).html(" ");
	var progressbar  = "<div align='right'><div id='progressBarTotal' style='width: 100px; height: 1.5em;'></div><div id='percent_loaded'><span id='percentTotal' style='position: relative; top: -15px; right: 38px;'>0%</span></div><div class='timeDownload' style='position:relative; bottom:10px; color: #363636; font-weight: bold; font-size: 10px;'></div></div>";
	$(startId).html(progressbar);
	var valueActual = "none";
	//$('.linkDetailBars a').attr("onclick", "showPopupSecondBars('barsDetails','Bars Details',538,345);");
	$("[id^=progressBar]").progressbar({value: 0});
	$(idRpmName).parent().next().hide();// icono de actualizacion
	getPercent();
	var html = "<div id='progressBarActual0'></div>";
	$('#PopupElastix').html(html);
	$('#progressBarActual0').progressbar('value', 60);
	/**** nueva implementacion ****/
    
}

// funcion que muestra el popup con las barras de progreso
function showPopupSecondBars(id,titles,widths,heights)
{
	var html = "<div class='margin_bars'>";
	// if exists a process install in progress
	var valueActual = percentValues;
    if( $.isArray(valueActual) && valueActual.length>0 ){
    	// creando los contenedores de los progress bar secundarios
    	/*for(var i=0; i<valueActual.length; i++){
    		html += "<div id='progressBarActual"+i+"'></div>";
    	}*/

    	//jBoxPopupAero(id ,titles, widths, heights, html);
        // obtain each package by Actual progressbar 
        for(var i=0; i<valueActual.length; i++){
            var percentActual = valueActual[i]['porcent_ins'];
            var lon_total = valueActual[i]['lon_total'];
            var lon_downl = valueActual[i]['lon_downl'];
            var status_pa = valueActual[i]['status_pa'];
            // setting textnodes
            /*var lon_total_lb  = document.getElementById('lon_downl'+i);
            var lon_downl_lb  = document.getElementById('lon_total'+i);
            var status_pa_lb  = document.getElementById('status_pa'+i);
            var percent_pa_lb = document.getElementById('percentTotal'+i);

            lon_total_lb.firstChild.nodeValue  = " "+lon_downl+" bytes";
            lon_downl_lb.firstChild.nodeValue  = lon_total+" bytes";
            status_pa_lb.firstChild.nodeValue  = status_pa;
            percent_pa_lb.firstChild.nodeValue = percentActual;*/
            


            // fill the progressBar actual(no main progressBar)
            //$('#progressBarActual'+i).progressbar('value', parseInt(percentActual));
            
        }    
    }else{
    	html += "No installing yet.</div>";
    	//jBoxPopupAero(id ,titles, widths, heights, html);
    }
}

function getPackagesCache(){
    var order = 'menu='+module_name+'&action=getPackagesCache&rawmode=yes';
    //$("#msg_status").html(" ");
    document.getElementById("msg_status").innerHTML = " ";
    $.post("index.php", order,
        function(theResponse){
            message = JSONtoString(theResponse);
            showPogressMessage(message['status_action']);
            if(message['error'] == "no_daemon"){
            	there_install = false;
		if(arrayLang)
		    alert(arrayLang['no_daemon']);
		else
		    connectJSON("no_daemon");
            	var textDaemonOff = $('#textDaemonOff').val();
            	showPogressMessage(textDaemonOff);
            }
            
            if(message['response'] == "there_install" || message['statusInstall'] == "there_install" || message['statusInstall'] == "status_confirm"){ //si existe una instalacion en progreso
                there_install = true;
                //connectJSON("process_installing");
                var rpm_installing = message['rpm_installing'];
                var processToDo = message['processToDo'];
                if(message['rpm_installing'] != "nothing"){
                	if(processToDo != "nothing"){
						if(message['statusInstall'] == "status_confirm" || message['response'] == "status_confirm"){
							confirmOperation();
						}
						if(processToDo == "install" || proccessToDo == "update"){
							var idRpmNameIns = "[id='"+rpm_installing+"']";
							var idRpmNameBuy = "[id='"+rpm_installing+"_buy']";
							//getStatusInstall(rpm_installing);
							$('#uninstallRpm').val(rpm_installing);
							$('#actionToDo').val("installing");
							$('.loadingAjax').hide();
							$('.uninstall').show();// cambiando los demas addons a un estado de cargados
							$('.install').show();
							$('.buy').show();
							$(idRpmNameIns).parent().parent().parent().children(':first-child').show(); // mostrando el loading del rpm que se esta instalando o actualizando
							$(idRpmNameIns).hide(); // ocultando el boton install/remove del rpm que se esta instalado
							$(idRpmNameBuy).hide();
							getStatusInstallAddonProgress(); // haciendo el cambio de mostrar la barra de progreso
						}else if(processToDo == "remove"){
							var idRpmNameBuy = "[id='"+rpm_installing+"_buy']";
							$('#uninstallRpm').val(rpm_installing);
							$('#actionToDo').val("remove");
							$('.loadingAjax').hide();
							$('.uninstall').show();// cambiando los demas addons a un estado de cargados
							$('.install').show();
							$('.buy').show();
							$(idRpmNameIns).parent().parent().parent().children(':first-child').show(); // ocultando el loading del rpm que se esta removiendo
							$(idRpmNameIns).hide(); // ocultando el boton install/remove del rpm que se esta removiendo
							$(idRpmNameBuy).hide();
							getStatusInstallAddonProgress(); // haciendo el cambio de mostrar la barra de progreso
						}
					}else{
						there_install = false;
						var arr_data = message['data_cache'];
						var link_img = "modules/"+module_name+"/images/warning.png";
						var errorDetails = $("#errorDetails").val();
						for(var i=0; i<arr_data.length; i++){
							var rpm_name = arr_data[i]["name_rpm"];
							var status = arr_data[i]["status"];
							var observation = arr_data[i]["observation"];
							var textObservation = $("#textObservation").val();
							var rpm_nameID = document.getElementById(rpm_name);
							var idRpmName = "[id='"+rpm_name+"']";
							var idRpmNameBuy = "[id='"+rpm_name+"_buy']";
							if(rpm_nameID){
								if(status == "1"){ // se muestra el boton de instalar
									$(idRpmName).show();
									$(idRpmNameBuy).show();
									var tryIt = "[id='try_"+rpm_name+"']";
									$(tryIt).show();
									$(idRpmName).parent().parent().parent().children(':first-child').hide();// se oculta el loading
								}else{ // se debe mostrar el error como descripcion
									$(idRpmName).parent().parent().parent().children(':first-child').attr('src',link_img);// cambiando el loading por img error
								}
								$(idRpmName).parent().parent().parent().children(':first-child').hide();
								var styleSeleRpm = $(idRpmName).attr("style");
								if(observation!="OK")
									$(idRpmName).parent().parent().parent().append("<a class='text_alert ttip' title='"+textObservation+" "+observation+"' style='float: right;'>"+errorDetails+"</a>");
							}
						}
                        $(".updateAddon").show();
                        $('.ttip').tipsy({fade: true, html: true }); // se agrega la accion de tooltips
            		}
                }
                return;
                //window.open("index.php?menu="+module_name2,"_self");
            }
            else if(message['response'] == "status_confirm"){
                var con = confirmOperation();
                if(con){
                    getStatusInstall(message['name_rpm']);
                }
            }
            else if(message['response'] == "OK"){ // listo para instalar
                there_install = true;
                //name_rpm = message['name_rpm'];
                getStatusCache();
            }
            else if(message['response'] == "error"){ // error no install
                there_install = false;
                showPogressMessage("Status: "+response['response']);
                clearAddons();
		if(arrayLang)
		    alert(arrayLang['error_start_install']);
		else
		    connectJSON("error_start_install");
            }else if(message['response'] == "noFillDataCache"){// ya esta actualizada la data
                there_install = false;
                var arr_data = message['data_cache'];
                var link_img = "modules/"+module_name+"/images/warning.png";
				var errorDetails = $("#errorDetails").val();
                //elastix-developer id
                //status_elastix-developer id
                for(var i=0; i<arr_data.length; i++){
                    var rpm_name = arr_data[i]["name_rpm"];
                    var status = arr_data[i]["status"];
                    var observation = arr_data[i]["observation"];
                    var textObservation = $("#textObservation").val();
                    //var id_status = "status_"+rpm_name;
                    var rpm_nameID = document.getElementById(rpm_name);
                    var idRpmName = "[id='"+rpm_name+"']";
                    var idRpmNameBuy = "[id='"+rpm_name+"_buy']";
                    if(rpm_nameID){
                        if(status == "1"){ // se muestra el boton de instalar
                            $(idRpmName).attr("style","display: block;");
                            $(idRpmNameBuy).attr("style","display: block;");
                            var tryIt = "[id='try_"+rpm_name+"']";
                            $(tryIt).attr("style","display: block;");
                            // se oculta el loading
                            $(idRpmName).parent().parent().parent().children(':first-child').attr('style','display: none;');
                            // cambiando la clase
                            //$("#status_"+rpm_name).attr("class","text_install");
                        }else{ // se debe mostrar el error como descripcion
                            // cambiando el loading por img error
                            $(idRpmName).parent().parent().parent().children(':first-child').attr('src',link_img);
                            //$("#status_"+rpm_name).attr("class","text_alert");
                        }
                        // cambiando la observacion
                        //$("#status_"+rpm_name).text(observation);
                        //agregado para que solo exista una sola columna
                        $(idRpmName).parent().parent().parent().children(':first-child').hide();
                        var styleSeleRpm = $(idRpmName).attr("style");
                    	if(observation!="OK")
                    		$(idRpmName).parent().parent().parent().append("<a class='text_alert ttip' title='"+textObservation+" "+observation+"' style='float: right;'>"+errorDetails+"</a>");
                    }
                }
                $(".updateAddon").attr("style", "display: block;");
                $('.ttip').tipsy({fade: true, html: true }); // se agrega la accion de tooltips
            }
    });
}

function getStatusCache(){
    var order = 'menu='+module_name+'&action=getStatusCache&rawmode=yes';
    $.post("index.php", order,
        function(theResponse){
            response = JSONtoString(theResponse);

            showPogressMessage(response['status_action']);
            //var name_rpm = response['name_rpm'];
            var resp = response['response'];

            if(resp == "OK"){
                // aqui se muestran los botones de install y los errores que pudieron haber
                changeStatusButtonInstall(response);
                //$("#msg_status").html(" ");
                document.getElementById("msg_status").innerHTML = " ";
            }else if(resp == "error"){
                //alert("uno o algunos paquetes no se pueden instalar");
                changeStatusButtonInstall(response)
            }
            else
                getStatusCache();
    });
}

function changeStatusButtonInstall(response){
    var resp = response['response'];
    var order = 'menu='+module_name+'&action=get_lang&rawmode=yes';
    if(resp == "OK"){
        $.post("index.php", order,
            function(theResponse){
                message = JSONtoString(theResponse);
                $("div[id^='img_']").each(function(){
                    var id = $(this).attr('id');
                    // se oculta el loading
                    $(this).children(':first-child').attr("style","display: none;");
                    // se muestra el boton install/uninstall
                    $(this).children(':last-child').children(':first-child').children(':first-child').show();
                    id = id.replace(/img_/g,"");
                    var idRpmNameBuy = "[id='"+id+"_buy']";
                    if(document.getElementById(id+"_buy"))
                    	$(idRpmNameBuy).show();
                });
                $("div[id^='status_']").each(function(){
                    $(this).attr("class","text_install");
                    var ready = message['Ok'];
                    $(this).text(ready);
                });
        });
        there_install = false;
    }else{
        var arr_data = response['data_cache'];
        var link_img = "modules/"+module_name+"/images/warning.png";
	    var errorDetails = $("#errorDetails").val();
        for(var i=0; i<arr_data.length; i++){
            var rpm_name = arr_data[i]["name_rpm"];
            var status = arr_data[i]["status"];
            var observation = arr_data[i]["observation"];
            var textObservation = $("#textObservation").val();
            var idRpmNameBuy = "[id='"+rpm_name+"_buy']";
            var idRpmName = "[id='"+rpm_name+"']";
            if(document.getElementById(rpm_name)){
                if(status == "1"){ // se muestra el boton de instalar
                    $(idRpmName).show();// se muestra el boton install o try 
                    $(idRpmNameBuy).show(); // se muestra el boton buy
                    $(idRpmName).parent().parent().parent().children(':first-child').hide(); // se oculta el loading
                }else{ // se debe mostrar el error como descripcion
                    // cambiando el loading por img error
                    $(idRpmName).parent().parent().parent().children(':first-child').attr('src',link_img);
                }
		//agregado para que solo exista una sola columna
		$(idRpmName).parent().parent().parent().children(':first-child').hide();
		if(observation!="OK")
		    $(idRpmName).parent().parent().parent().append("<a class='text_alert ttip' style='float: right;' title='"+textObservation+" "+observation+"'>"+errorDetails+"</a>");
		document.getElementById("msg_status").innerHTML = " ";
            }
        }
        $(".updateAddon").attr("style", "display: block;");
        $('.ttip').tipsy({fade: true, html: true });
        there_install = false;
    }
}

var str_dot = ".";
function showPogressMessage(msg)
{
    if(msg){
        if(str_dot.length == 5)
            str_dot = ".";
        else
            str_dot += ".";
        $("#msg_status").html("<b style='font-size:10pt;color:#E35332'>" + msg + " " + str_dot + "</b>");
    }
}

/** funciones para obtener el porcetaje de instalacion de un addons**/

function getPercent()
{
    var order = 'menu='+module_name+'&action=progressbar&rawmode=yes';
    $.post("index.php", order,
        function(theResponse){
            var response = JSONtoString(theResponse);
            process(response);
            if(response['status']=="finished" || response['status']=="not_install"){
                var idParent = $('#progressBarTotal').parent().parent().attr("id"); // padre start_nameRPM
		$('#progressBarTotal').parent().parent().html(" "); // se blanquea a los hijos de start_nameRPM
		there_install = false;
		var textRemoving = $("#textRemoving").val();
		var idChild = $("#"+idParent).next().children(':first-child').children(':first-child').attr("id");
		if(idParent)
		    idChild = idParent.replace(/start_/g,"");
		var idRpmName = "[id='"+idChild+"']";
		var idRpmNameBuy = "[id='"+idChild+"_buy']";
		var startId = "[id='start_"+idChild+"']";
		$("#"+idParent).html(" ");
		$("#"+idParent).append("<div class='text_starting' align='right'>"+textRemoving+"</div><div><img class='startingAjax' src='modules/addons_avalaibles/images/starting.gif' alt='' align='right'></div>");
		$("#"+idParent).attr("style", "display: none;");
		var textUninstall = $("#uninstallText").val();
		$(idRpmName).remove();
		$("#"+idParent).next().children(':first-child').html("<input type='button' style='display: block;' name='uninstallButton' id='"+idChild+"' class='uninstall' value='"+textUninstall+"'  />");
		$(idRpmNameBuy).show();
		var idRef = document.getElementById(idChild);
		if(idRef)
		    idRef.setAttribute("onclick","removeAddon('"+idChild+"');");
		$(idRpmName).parent().next().children(':first-child').hide(); // icono de actualizacion
		$(idRpmName).parent().show();
		$(idRpmName).show();
		document.getElementById("msg_status").innerHTML = " ";
		$('#uninstallRpm').val("");
		//$("#msg_status").html(" ");
		clearAddons();// hacer un clear en el servidor
            }
            else if(response['status']=="not_install"){
                // nada que hacer
            }
            else if(response['response']=="error"){
            	there_install = false;
            	$('#uninstallRpm').val("");
            	var errmsg = response['errmsg'];
            	showPogressMessage("Status: "+response['response']+" - "+errmsg);
            	var idParent = $('#progressBarTotal').parent().parent().attr("id"); // padre start_nameRPM
		$('#progressBarTotal').parent().parent().html(" "); // se blanquea a los hijos de start_nameRPM
		var textInstalling = $("#textInstalling").val();
		var idChild = $("#"+idParent).next().children(':first-child').children(':first-child').attr("id");
		if(idParent)
		    idChild = idParent.replace(/start_/g,"");
		var idRpmName = "[id='"+idChild+"']";
		var idRpmNameBuy = "[id='"+idChild+"_buy']";
		var startId = "[id='start_"+idChild+"']";
		$("#"+idParent).html(" ");
		$("#"+idParent).append("<div class='text_starting' align='right'>"+textInstalling+"</div><div><img class='startingAjax' src='modules/addons_avalaibles/images/starting.gif' alt='' align='right'></div>");
		$("#"+idParent).attr("style", "display: none;");
		$(idRpmName).show();
		$(idRpmNameBuy).show();
		var idRef = document.getElementById(idChild);
		$(idRpmName).parent().next().children(':first-child').hide(); // icono de actualizacion
		$(idRpmName).parent().show();
		$(idRpmName).show();
            	clearAddons();
            }
            else {
                getPercent();
            }
    });
}

/******************************************************************************************** 
VALUES OF response (this the response of server in format JSON)
        valueActual        : Object. It content all information about actual downloading package by each mini progressBar. The values included are:
            "action"       : It the action actual, It can be "install" or "none",
            "name"         : The name of package to install,
            "lon_total"    : Size of package in bytes,
            "lon_downl"    : Size of package downloaded in bytes,
            "status_pa"    : The status of request, It can be "downloading" or "not_install" or "waiting",
            "porcent_ins"  : Percent value of this package but no all.
        valueTotal         : Percent total of installation,
        status             : Status of intallation about addons. If all if fine this can be  "progress",
        action             : The action do in that instant, it can be "downloading" or "installing",
        process_installed  : The current process can be "process_installed"

*********************************************************************************************/
function process(response)
{
    var valueActual = response['valueActual'];
    var valueTotal  = response['valueTotal'];
	var timeDownload = response['timeDownload'];
    if(parseInt(valueTotal)>0){
	    $('#progressBarTotal').progressbar('value', parseInt(valueTotal));
	    $('#percentTotal').text(valueTotal+"%");
		$('.timeDownload').text(timeDownload);
    }
    // if no preocess to install
	if (response['status'] == "not_install")
		return;
    // if the process to install is finished
    if(response['action'] != "none") {
        var ctl_percent = document.getElementById('percentTotal');
        if (ctl_percent != null) {
	    ctl_percent.firstChild.nodeValue=valueTotal+"%";
	}else {
	    return;
        }
    }

    if(parseInt(valueTotal)>95 && response['valueActual'][0]['action']=="install"){//muestra el mensaje apropiado instalacion
	var idParent = $('#progressBarTotal').parent().parent().attr("id");
	var idChild = $("#"+idParent).next().children(':first-child').children(':first-child').attr("id");
	var textInstalling = $('#textInstalling').val();
	textInstalling = textInstalling.replace(/\./g,"");
	if(idChild){
	    var nameAddons    = $('#'+idChild).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
	    var versionAddons = $('#'+idChild).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
	    showPogressMessage("Status: "+textInstalling+" "+nameAddons+" "+versionAddons);
	}
    }

    //showPopupSecondBars('barsDetails','Bars Details',538,345);
    // if exists a process install in progress
    if(valueActual != "none"){
        // obtain each package by Actual progressbar 
        /*for(var i=0; i<valueActual.length; i++){
            var percentActual = valueActual[i]['porcent_ins'];
            var lon_total = valueActual[i]['lon_total'];
            var lon_downl = valueActual[i]['lon_downl'];
            var status_pa = valueActual[i]['status_pa'];
            // setting textnodes
            var lon_total_lb  = document.getElementById('lon_downl'+i);
            var lon_downl_lb  = document.getElementById('lon_total'+i);
            var status_pa_lb  = document.getElementById('status_pa'+i);
            var percent_pa_lb = document.getElementById('percentTotal'+i);

            lon_total_lb.firstChild.nodeValue  = " "+lon_downl+" bytes";
            lon_downl_lb.firstChild.nodeValue  = lon_total+" bytes";
            status_pa_lb.firstChild.nodeValue  = status_pa;
            percent_pa_lb.firstChild.nodeValue = percentActual;

            // fill the progressBar actual(no main progressBar)
            $('#progressBarActual'+i).progressbar('value', parseInt(percentActual));
        }*/
        // fill the main progressBar by the correcta value
        
        //$('#progressBarTotal').progressbar('value', parseInt(valueTotal));
    	percentValues = valueActual;

    }
}

function updateAddon(name_rpm)
{
    var classRpm = "input[class='"+name_rpm+"']";
    var data_exp = $(classRpm).val();
    var order = 'menu='+module_name+'&name_rpm='+name_rpm+'&action=update&data_exp='+data_exp+'&rawmode=yes';
    $('#actionToDo').val("");
    $('#uninstallRpm').val(name_rpm);
    document.getElementById("msg_status").innerHTML = " ";
    if(!there_install){
	there_install = true;
	$.post('index.php',order,function(theResponse){
	    var message = JSONtoString(theResponse);
	    var name_rpm = message['name_rpm'];
	    var idRpmName = "[id='"+name_rpm+"']";
	    var idRpmNameBuy = "[id='"+name_rpm+"_buy']";
	    var startId = "[id='start_"+name_rpm+"']";
      	    var nameAddons    = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
    	    var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
            if(message['error'] == "no_daemon"){
		there_install = false;
		if(arrayLang)
		    alert(arrayLang['no_daemon']);
		else
		    connectJSON("no_daemon");                	
		var textDaemonOff = $('#textDaemonOff').val();
		showPogressMessage(textDaemonOff);
		$(idRpmName).parent().next().show();
		$(idRpmNameBuy).show();
		$(startId).hide();
		clearAddons();
		$('#uninstallRpm').val("");
		$('#actionToDo').val("");
		return;
            }
	    if(message['response'] == "OK"){
		var textDownloading = $("#textDownloading").val();
		$(startId).children(':first-child').text(textDownloading);
		textDownloading = textDownloading.replace(/\./g,"");
		showPogressMessage(textDownloading+" "+nameAddons+" "+versionAddons);
		$('#actionToDo').val("update");
		$(idRpmName).hide(); // ocultando el boton uninstall/install
		$(idRpmName).parent().next().children(':first-child').hide(); //ocultando el boton upgrade
		$(idRpmNameBuy).hide(); //ocultando el boton buy
		$(startId).show(); // mostrando la barra de instalacion
		confirmOperation();
	    }else if(message['response'] == "error"){
		document.getElementById("msg_status").innerHTML = " ";
		there_install = false;
		$('#uninstallRpm').val("");
		$(idRpmName).parent().next().show();
		$(idRpmNameBuy).show();
		$(startId).hide();
		clearAddons();
		$('#actionToDo').val("");
		$('#uninstallRpm').val("");
		if(arrayLang)
		    alert(arrayLang['error_start_update']);
		else
		    connectJSON("error_start_update");
	    }else if(message['response'] == "there_install"){
		document.getElementById("msg_status").innerHTML = " ";
		there_install = false;
		$('#uninstallRpm').val("");
		$(idRpmName).parent().next().show();
		$(idRpmNameBuy).show();
		$(startId).hide();
		$('#actionToDo').val("");
		$('#uninstallRpm').val("");
		alert(message['msg_error']);
	    }
	});
    }else
	currentProcess();
}

function removeAddon(name_rpm)
{
    var classRpm = "input[class='"+name_rpm+"']";
    var data_exp = $(classRpm).val();
    var order = 'menu='+module_name+'&name_rpm='+name_rpm+'&action=remove&data_exp='+data_exp+'&rawmode=yes';
    $('#actionToDo').val(" ");
    document.getElementById("msg_status").innerHTML = " ";
    if(!there_install){
	there_install = true;
	$('#uninstallRpm').val(name_rpm);
	$.post('index.php',order,function(theResponse){
	    var message = JSONtoString(theResponse);
	    var name_rpm = message['name_rpm'];
	    var idRpmName = "[id='"+name_rpm+"']";
	    var nameAddons    = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
	    var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
            if(message['error'] == "no_daemon"){
		there_install = false;
		var rpmName = $('#uninstallRpm').val();
		var idRpmNameBuy = "[id='"+rpmName+"_buy']";
		$(idRpmNameBuy).show();
		$('#uninstallRpm').val("");
		var textDaemonOff = $('#textDaemonOff').val();
		showPogressMessage(textDaemonOff);
		if(arrayLang)
		    alert(arrayLang['no_daemon']);
		else
		    connectJSON("no_daemon");
		return;
            }
	    if(message['response'] == "OK"){
		var textRemoving = $("#textRemoving").val();
	        textRemoving = textRemoving.replace(/\./g,"");
		showPogressMessage(textRemoving+" "+nameAddons+" "+versionAddons);
		$('#actionToDo').val("remove");
		var rpmName = $('#uninstallRpm').val();
		var idRpmName = "[id='"+name_rpm+"']";
		var idRpmNameBuy = "[id='"+name_rpm+"_buy']";
		var startId = "[id='start_"+name_rpm+"']";
		$(idRpmName).hide(); // ocultando el boton uninstall/install
		$(idRpmName).parent().next().children(':first-child').hide(); //ocultando el boton upgrade
		$(idRpmNameBuy).hide(); //ocultando el boton buy
		$(startId).show(); // mostrando la barra de instalacion
		confirmOperation();
	    }else if(message['response'] == "error"){
		there_install = false;
		var rpmName = $('#uninstallRpm').val();
		var idRpmNameBuy = "[id='"+rpmName+"_buy']";
		$(idRpmNameBuy).show();
		$('#uninstallRpm').val("");
		clearAddons();
		if(arrayLang)
		    alert(arrayLang['error_start_remove']);
		else
		    connectJSON("error_start_remove");
	    }else if(message['response'] == "there_install"){
		there_install = false;
		var idRpmNameBuy = "[id='"+rpmName+"_buy']";
		$(idRpmNameBuy).show();
		alert(message['msg_error']);
	    }
	});
    }else{
	currentProcess();
	
    }
}

function confirmOperation(){
    var order = 'menu='+module_name+'&action=getconfirmAddons&rawmode=yes';
    
    $.post("index.php", order,
        function(theResponse){
            response = JSONtoString(theResponse);
            var resp = response['response'];
            var action = response['action'];
            if(resp == "OK"){
		if(action == "confirm"){// se muestra el progressbar
		    var rpmName = $('#uninstallRpm').val();
		    var idRpmName = "[id='"+rpmName+"']";
		    var startId = "[id='start_"+rpmName+"']";
		    $(startId).html(" ");
		    var progressbar  = "<div align='right'><div id='progressBarTotal' style='width: 100px; height: 1.5em;'></div><div id='percent_loaded'><span id='percentTotal' style='position: relative; top: -15px; right: 38px;'>0%</span></div><div class='timeDownload' style='position:relative; bottom:10px; color: #363636; font-weight: bold; font-size: 10px;'></div></div>";
		    $(startId).html(progressbar);
		    $("[id^=progressBar]").progressbar({value: 0});
		}
                getStatusInstallAddonProgress();
            }else{
		if(response["clear"]){
		    var rpmName = $('#uninstallRpm').val();
		    var idRpmName = "[id='"+rpmName+"']";
		    var startId = "[id='start_"+rpmName+"']";
		    there_install = false;
		    //$("#msg_status").html(" ");
		    document.getElementById("msg_status").innerHTML = " ";
		    var warmsg = response['warnmsg'][0];
		    if(warmsg)
			showPogressMessage("Status: warnmsg - "+warmsg+" to "+rpmName);
		    // mostrando los botones 
		    $(idRpmName).show(); // mostrando el boton uninstall/install
		    $(idRpmName).parent().next().children(':first-child').show(); // mostrando el boton upgrade
		    $(idRpmName).parent().next().next().children(':first-child').show(); // mostrando el boton buy
		    $(idRpmName).show(); // mostrando el boton uninstall
		    $(startId).hide(); // ocultando la barra de instalacion
		    $('#uninstallRpm').val("");
		    $('#actionToDo').val(" ");
		    clearAddons();
		    return;
		}
		confirmOperation();
            }
    });
}

function clearAddons()
{
    var link = 'menu='+module_name+'&action=toDoclearAddon&rawmode=yes';
    $.post("index.php", link,
	function(theResponse){
	    response = JSONtoString(theResponse);
	    //if(response['response'] == "OK")
	    there_install = false;
    });
}

function getStatusInstallAddonProgress(){ 
    var order = 'menu='+module_name+'&action=get_statusBar&rawmode=yes';

    $.post("index.php", order,
        function(theResponse){
            var response = JSONtoString(theResponse);
            var resp = response['response'];
	    var statusGet = response['status'];
            var process_installed = response['process_installed'];
            var toDo = $('#actionToDo').val();
	    var idChild = $('#uninstallRpm').val();
	    var idRpmName = "[id='"+idChild+"']";
	    var idRpmNameBuy = "[id='"+idChild+"_buy']";
	    var startId = "[id='start_"+idChild+"']";
	    var idParent = $(idRpmName).parent().parent().parent().attr("id");
	    var textInstall = $("#installText").val();
	    var textDownloading = $("#textDownloading").val();
	    var textRemoving = $("#textRemoving").val();
	    var textUninstall = $("#uninstallText").val();
	    $('.loadingAjax').hide();
            /*if(resp == "OK"){
                return;
            }else */
	    if (resp == "not_install") {
            	document.getElementById("msg_status").innerHTML = " ";
            	if(toDo == "remove"){
		    $(idRpmName).remove();
		    $(startId).html(" ");
		    $(startId).append("<div class='text_starting' align='right'>"+textDownloading+"</div><div><img class='startingAjax' src='modules/addons_avalaibles/images/starting.gif' alt='' align='right'></div>");
		    $(startId).attr("style", "display: none;");
		    if(document.getElementById(idChild+"_buy"))
			textInstall = $("#tryItText").val();
		    $("#"+idParent).children(':last-child').children(':first-child').append("<input type='button' style='display: block;' name='installButton' id='"+idChild+"' class='install' value='"+textInstall+"'  />");

		    var idRef = document.getElementById(idChild);
		    if(idRef)
			idRef.setAttribute("onclick","installAddon('"+idChild+"');");
		    $(idRpmName).parent().next().hide(); // icono de actualizacion
			$(idRpmNameBuy).show();
            	}else if(toDo == "installing"){
		    $(idRpmName).remove();
		    $(startId).html(" ");
		    $(startId).append("<div class='text_starting' align='right'>"+textRemoving+"</div><div><img class='startingAjax' src='modules/addons_avalaibles/images/starting.gif' alt='' align='right'></div>");
		    $(startId).attr("style", "display: none;");
		    $("#"+idParent).children(':last-child').children(':first-child').append("<input type='button' style='display: block;' name='uninstallButton' id='"+idChild+"' class='uninstall' value='"+textUninstall+"'  />");
		    //$(idRpmName).attr("onclick","installAddon('"+idChild+"');");
		    var idRef = document.getElementById(idChild);
		    if(idRef)
			    idRef.setAttribute("onclick","removeAddon('"+idChild+"');");
		    $(idRpmNameBuy).show();
		}else{
		    $(idRpmName).remove();
		    $(startId).html(" ");
		    $(startId).append("<div class='text_starting' align='right'>"+textRemoving+"</div><div><img class='startingAjax' src='modules/addons_avalaibles/images/starting.gif' alt='' align='right'></div>");
		    $(startId).attr("style", "display: none;");
		    $("#"+idParent).children(':last-child').children(':first-child').append("<input type='button' style='display: block;' name='uninstallButton' id='"+idChild+"' class='uninstall' value='"+textUninstall+"'  />");
		    //$(idRpmName).attr("onclick","installAddon('"+idChild+"');");
		    var idRef = document.getElementById(idChild);
		    if(idRef)
			idRef.setAttribute("onclick","removeAddon('"+idChild+"');");
		    $(idRpmName).parent().next().hide();// icono de actualizacion
		    $(idRpmNameBuy).show();
            	}
            	there_install = false;
            	$('#actionToDo').val(" ");
            	$('#uninstallRpm').val(" ");
            	clearAddons();// hacer un clear en el servidor
            }else {
            	if(process_installed == "process_installed" & toDo == "installing"){
		    // se debe mostrar los progressbar
		    var textInstalling = $('#textDownloading').val();
		    textInstalling = textInstalling.replace(/\./g,"");
		    var nameAddons    = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
		    var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
		    showPogressMessage("Status: "+textInstalling+" "+nameAddons+" "+versionAddons);
		    $(startId).html(" ");
		    if(!document.getElementById("progressBarTotal")){
			var progressbar  = "<div align='right'><div id='progressBarTotal' style='width: 100px; height: 1.5em;'></div><div id='percent_loaded'><span id='percentTotal' style='position: relative; top: -15px; right: 38px;'>0%</span></div><div class='timeDownload' style='position:relative; bottom:10px; color: #363636; font-weight: bold; font-size: 10px;'></div></div>";
			$(startId).html(progressbar);
			var valueActual = "none";
			$("[id^=progressBar]").progressbar({value: 0});
			$(idRpmName).parent().next().hide();// icono de actualizacion
		    }
		    $(idRpmName).parent().parent().parent().children(':first-child').hide(); // ocultando el loading del rpm que se esta instalando
		    $(idRpmName).hide(); // ocultando el boton install/remove del rpm que se esta instalado
		    $(startId).show();
		    //getPercent();
            	}
            	if(process_installed == "process_installed" & toDo == "remove"){
		    // se debe mostrar los progressbar
		    var textRemoving = $('#textRemoving').val();
		    textRemoving = textRemoving.replace(/\./g,"");
		    var nameAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':first-child').text();
		    var versionAddons = $(idRpmName).parent().parent().parent().parent().parent().children(':first-child').children(':nth-child(2)').text();
		    showPogressMessage("Status: "+textRemoving+" "+nameAddons+" "+versionAddons);
		    $(startId).html(" ");
		    if(!document.getElementById("progressBarTotal")){
			var progressbar  = "<div align='right'><div id='progressBarTotal' style='width: 100px; height: 1.5em;'></div><div id='percent_loaded'><span id='percentTotal' style='position: relative; top: -15px; right: 38px;'>0%</span></div><div class='timeDownload' style='position:relative; bottom:10px; color: #363636; font-weight: bold; font-size: 10px;'></div></div>";
			$(startId).html(progressbar);
			var valueActual = "none";
			$("[id^=progressBar]").progressbar({value: 0});
			$(idRpmName).parent().next().hide();// icono de actualizacion
		    }
		    $(idRpmName).parent().parent().parent().children(':first-child').hide(); // ocultando el loading del rpm que se esta instalando
		    $(idRpmName).hide(); // ocultando el boton install/remove del rpm que se esta instalado
		    $(startId).show();
		    //getPercent();
            	}
            	process(response);
                getStatusInstallAddonProgress();
            }
    });
}

//implement JSON.stringify serialization
function StringtoJSON(obj) {
    var t = typeof (obj);
    if (t != "object" || obj === null) {
        // simple data type
        if (t == "string") obj = '"'+obj+'"';
        return String(obj);
    }
    else {
        // recurse array or object  
        var n, v, json = [], arr = (obj && obj.constructor == Array);  
        for (n in obj) {  
            v = obj[n]; t = typeof(v);  
            if (t == "string") v = '"'+v+'"';  
            else if (t == "object" && v !== null) v = JSON.stringify(v);
                json.push((arr ? "" : '"' + n + '":') + String(v));  
        }
        return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
    }
}

//implement JSON.parse de-serialization  
function JSONtoString(str) {
    if (str === "") str = '""';
    eval("var p=" + str + ";");
    return p;
}

//uso de JSON para obtener el arreglo lang.php
function connectJSON(mensaje_error) {
    var order = 'menu='+module_name+'&action=get_lang&rawmode=yes';
    var message = "";
    $.post("index.php", order,
	function(theResponse){
	    message = JSONtoString(theResponse);
	    arrayLang = message;
	    alert(message[mensaje_error]);
    });
}

//uso de JSON para obtener el arreglo lang.php
function currentProcess() {
    var order = 'menu='+module_name+'&action=currentProcess&rawmode=yes';
    var message = "";
    $.post("index.php", order,
	function(theResponse){
	    message = JSONtoString(theResponse);
	    alert(message['message']);
    });
}

function quitCharacterSpecial(str)
{
    if(/\./.test(str)){
	    str = str.replace(/\./g,"!");
    }
    return str;
}

function putCharacterSpecial(str)
{
    if(/\!/.test(str)){
	    str = str.replace(/!/g,".");
    }
    return str;
}