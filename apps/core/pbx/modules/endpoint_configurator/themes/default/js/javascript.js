function getDevices(model,mac)
{
    var arrAction              = new Array();
	arrAction["action"]    = "getDevices";
	arrAction["menu"]      = "endpoint_configurator";
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

function activate_option_lan()
{
    var static = document.getElementById('lan_static');
    var dhcp   = document.getElementById('lan_dhcp');
    if(static){
	if(static.checked==true)
	{
	    document.getElementById('lan_ip').style.display = '';
	    document.getElementById('lan_mask').style.display = '';
	}
	else
	{
	    document.getElementById('lan_ip').style.display = 'none';
	    document.getElementById('lan_mask').style.display = 'none';
	}
    }
}

function activate_option_wan()
{
    var static = document.getElementById('wan_static');
    var dhcp   = document.getElementById('wan_dhcp');
    if(static){
	if(static.checked==true)
	{
	    document.getElementById('wan_ip').style.display = '';
	    document.getElementById('wan_mask').style.display = '';
	}
	else
	{
	    document.getElementById('wan_ip').style.display = 'none';
	    document.getElementById('wan_mask').style.display = 'none';
	}
    }
}

function changeFields(element)
{
    var value = $(element).val();
    var static_wan = document.getElementById('wan_static');
    if(value == "yes"){
	document.getElementById('side').style.display='';
	document.getElementById('wan').style.display='';
	document.getElementById('check_wan').style.display='';
	if(static_wan){
	    if(static_wan.checked==true){
		document.getElementById('wan_ip').style.display='';
		document.getElementById('wan_mask').style.display='';
	    }
	}
    }else if(value == "no"){
	document.getElementById('side').style.display='none';
	document.getElementById('wan').style.display='none';
	document.getElementById('check_wan').style.display='none';
	document.getElementById('wan_ip').style.display='none';
	document.getElementById('wan_mask').style.display='none';
    }
}