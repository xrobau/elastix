function getDevices(model,mac)
{
    var arrAction              = new Array();
	arrAction["action"]    = "getDevices";
	arrAction["menu"]      = "endpoint_configuration";
	arrAction["rawmode"]   = "yes";
	arrAction["id_model"]  = model.value;
	request("index.php",arrAction,false,
                function(arrData,statusResponse,error)
                {
		    if(error != "yes"){
			$('#id_device_'+mac).html("");
			var html = "";
			for(key in arrData){
			    valor = arrData[key];
			    html += "<option value = "+key+">"+valor+"</option>";
			}
			$('#id_device_'+mac).html(html);
		    }
                }
            );
}
