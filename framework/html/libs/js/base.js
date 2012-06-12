    <!-- Begin
    var ie4 = (document.all) ? true : false;
    var ns4 = (document.layers) ? true : false;
    var ns6 = (document.getElementById && !document.all) ? true : false;
    var bshowMenu = 1;

    function changeMenu() {

      layerMenu='fullMenu';
      layerMenuMini='miniMenu';
      layerMenuIzq='tdMenuIzq';

      if(bshowMenu==1) {
          bshowMenu=0;
      } else {
          bshowMenu=1;
      }

      if (ie4) {
          if(bshowMenu==1) {
              document.all[layerMenu].style.visibility = "visible";
              document.all[layerMenu].style.position = "";
              if(document.all[layerMenuIzq]) {
                  document.all[layerMenuIzq].style.visibility = "visible";
                  document.all[layerMenuIzq].style.position = "";
              }
              document.all[layerMenuMini].style.visibility = "hidden";
              document.all[layerMenuMini].style.position = "absolute";
          } else {
              document.all[layerMenu].style.visibility = "hidden";
              document.all[layerMenu].style.position = "absolute";
              if(document.all[layerMenuIzq]) {
                  document.all[layerMenuIzq].style.visibility = "hidden";
                  document.all[layerMenuIzq].style.position = "absolute";
              }
              document.all[layerMenuMini].style.visibility = "visible";
              document.getElementById([layerMenuMini]).style.display = "";
              document.all[layerMenuMini].style.position = "";
          }
      }
      if (ns4) {
          if(bshowMenu==1) {
              document.layers[layerMenu].visibility = "show";
              if(document.layers[layerMenuIzq]) {
                  document.layers[layerMenuIzq].visibility = "show";
              }
              document.layers[layerMenuMini].visibility = "hide";
          } else {
              document.layers[layerMenu].visibility = "hide";
              if(document.layers[layerMenuIzq]) {
                  document.layers[layerMenuIzq].visibility = "hide";
              }
              document.layers[layerMenuMini].visibility = "show";
          }
      }
      if (ns6) {
          if(bshowMenu==1) {
              document.getElementById([layerMenu]).style.display = "";
              document.getElementById([layerMenu]).style.position = "";
              if(document.getElementById([layerMenuIzq])!=null) {
                  document.getElementById([layerMenuIzq]).style.display = "";
                  document.getElementById([layerMenuIzq]).style.position = "";
              }
              document.getElementById([layerMenuMini]).style.display = "none";
              document.getElementById([layerMenuMini]).style.position = "absolute";
          } else {
              document.getElementById([layerMenu]).style.display = "none";
              document.getElementById([layerMenu]).style.position = "absolute";
              if(document.getElementById([layerMenuIzq])!=null) {
                  document.getElementById([layerMenuIzq]).style.display = "none";
                  document.getElementById([layerMenuIzq]).style.position = "absolute";
              }
              document.getElementById([layerMenuMini]).style.display = "";
              document.getElementById([layerMenuMini]).style.position = "";
          }
      }
    }

    function openWindow(path)
    {
        var features = 'width=700,height=460,resizable=no,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        var popupWin = window.open(path, "_cmdWin", features);
        popupWin.focus();
        //return true;
    }

    function confirmSubmit(message)
    {
        var agree=confirm(message);
        if (agree)
            return true ;
        else
	    return false ;
    }
    function popUp(path,width_value,height_value)
    {
        var features = 'width='+width_value+',height='+height_value+',resizable=no,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        var popupWin = window.open(path, "_cmdWin", features);
        popupWin.focus();
        //return true;
    }
    // End -->
    
var current_setTimeout = null;
function request(url,arrParams, recursive, callback)
{
    var queryString    = array2QueryString(arrParams);
    callback           = callback  || null;
    recursive          = recursive || null;

    // Comienza petición por ajax
    $.post(url,
	queryString,
	function(dataResponse){
	    var message        = dataResponse.message;
	    var statusResponse = dataResponse.statusResponse;
	    var error          = dataResponse.error;
	    var stop_recursive = false;

	    if(callback)
		stop_recursive = callback(message,statusResponse,error);
	    if(statusResponse){
		if(recursive & !stop_recursive){
		  current_setTimeout = setTimeout(function(){request(url,arrParams,recursive,callback)},2);
		  //la funcion espera 200ms para ejecutarse,pero la funcion actual si se termina de ejecutar,creando un hilo.
		}
	    }
	    else{
		//alert("hubo un problema de comunicacion...");
	    }
	},
	'json');
    // Termina petición por ajax

}

function existsRequestRecursive()
{
    return (current_setTimeout)?true:false;
}

function clearResquestRecursive()
{
    clearTimeout(current_setTimeout);
}

function array2QueryString(arrayParams)//formato: arr["action"]="iniciar";arr["param1"]="mensaje1"
{
    var queryString="";
    var tamanio=0;
    var i=0;
    for(var key in arrayParams){
		tamanio++;
    }
    for(var key in arrayParams){
	if(i==tamanio-1)
	    queryString+=key+"="+arrayParams[key];
	else
	    queryString+=key+"="+arrayParams[key]+"&";
	i++;
    }
    return queryString;
}

function hide_message_error(){
    document.getElementById("message_error").style.display = 'none';
}
