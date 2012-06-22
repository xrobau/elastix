$(document).ready(function(){
    showElementByTraffic();
    showElementByProtocol();

    $('#id_protocol').change(function(){
        var valor = $('#id_protocol option:selected').val();
        var arrAction              = new Array();
            arrAction["action"]    = "getPorts";
	    arrAction["menu"]	   = "sec_rules";
            arrAction["rawmode"]   = "yes";
            arrAction["protocol"]  =  valor;
            request("index.php",arrAction,false,
                function(arrData,statusResponse,error)
                {   
                    var html = "";
                    $('#port_in').html("");
                    $('#port_out').html("");
                    var key = "";
                    for(key in arrData){
                        valor = arrData[key];
                        html += "<option value = "+key+">"+valor+"</option>";
                    }
                    $('#port_in').html(html);
                    $('#port_out').html(html);
                }
            );
   
    });

    $(".up,.down").click(function(){
        var msg = document.getElementById("msg_status");
        msg.style.color = '#E35332';
        var adv = document.getElementById("message");
        var tab = document.getElementById("table_message");    
        var row  = $(this).parents("tr:first");
        var info = $(this).attr("id");
        //alert(info);
        var neighborrow = "";
        var changing = "";
        var p1 = "";
	var element = $(this);
	var changeToOtherPage = false;
        if ($(this).is(".up")) {
            if(row.prev().attr("class") != "table_title_row"){
                if(row.next().attr("class") == "table_navigation_row"){
                    row.children().attr("class","table_data");
                    row.prev().children().attr("class","table_data_last_row");              
                }
                p1 = row.prev().children().contents();
                neighborrow = p1.next().attr("id");
                row.insertBefore(row.prev());
                changing = "rulerup";
            }
            else{
		changeToOtherPage = true;
		var direction = "up";
	    }
        } else {
            if(row.next().attr("class") != "table_navigation_row"){
                if(row.next().next().attr("class") == "table_navigation_row"){
                    row.children().attr("class","table_data_last_row");
                    row.next().children().attr("class","table_data"); 
                }
                p1 = row.next().children().contents();
                neighborrow = p1.next().attr("id");
                row.insertAfter(row.next());
                changing = "rulerdown";
            }
            else{
		changeToOtherPage = true;
		var direction = "down";
	    }
        }

	if(!changeToOtherPage){
	    var arrAction                    = new Array();
		arrAction["action"]          = "change";
		arrAction["menu"]	     = "sec_rules";
		arrAction["rawmode"]         = "yes";
		arrAction["neighborrow"]     = neighborrow;
		arrAction["actualrow"]       = info;
		request("index.php",arrAction,false,
		    function(arrData,statusResponse,error)
		    {
			if(error)
			    alert(error);
			else if(p1!=""){
			    response = statusResponse.split(':');
			    $("#msg_status").html(response[0]);
			  // adv.html(response[1]);
			    setTimeout('$("#msg_status").html("")',300);
			    adv.style.display = '';
			    tab.style.border = '1px solid';
			    tab.style.color = '#AAAAAA';
			    adv.innerHTML = response[1] + "&nbsp;&nbsp;&nbsp;&nbsp;<input class='button' type='submit' name='exec' value='"+response[2]+"'>";
			    neighborrow = neighborrow.split('_');
			    actualrow = info.split('_');

			    p1.next().attr("id","rulerup_" + neighborrow[1] + "_" + actualrow[2]);
			    p1.next().next().attr("id","rulerdown_" + neighborrow[1] + "_" + actualrow[2]);

			    $("#div_"+actualrow[1]).html(neighborrow[2]);
			    $("#div_"+neighborrow[1]).html(actualrow[2]);

			    var nodo = $("#"+info);
			    
			    if(changing == "rulerup"){
				nodo.attr("id","rulerup_" + actualrow[1] + "_" + neighborrow[2]);
				nodo.next().attr("id","rulerdown_" + actualrow[1] + "_" + neighborrow[2]);
			    }
			    else{
				nodo.attr("id","rulerdown_" + actualrow[1] + "_" + neighborrow[2]);
				nodo.prev().attr("id","rulerup_" + actualrow[1] + "_" + neighborrow[2]);
			    }
			}else{
			    $("#msg_status").html(statusResponse);
			    setTimeout('$("#msg_status").html("")',300);
			}
		    }
		);
	}
	else{
	    var arrAction                = new Array();
		arrAction["action"]      = "changeOtherPage";
		arrAction["menu"]	 = "sec_rules";
		arrAction["direction"]	 = direction;
		arrAction["rawmode"]     = "yes";
		arrAction["actualrow"]   = info;
		request("index.php",arrAction,false,
		    function(arrData,statusResponse,error)
		    {
			if(error)
			    alert(error);
			else if(arrData){
			    response = statusResponse.split(':');
			    $("#msg_status").html(response[0]);
			  // adv.html(response[1]);
			    setTimeout('$("#msg_status").html("")',300);
			    adv.style.display = '';
			    tab.style.border = '1px solid';
			    tab.style.color = '#AAAAAA';
			    adv.innerHTML = response[1] + "&nbsp;&nbsp;&nbsp;&nbsp;<input class='button' type='submit' name='exec' value='"+response[2]+"'>";
			    actualrow = info.split('_');
			    if(direction == "up"){
				element.attr("id","rulerup_" + arrData["id"] + "_" + actualrow[2]);
				element.next().attr("id","rulerdown_" + arrData["id"] + "_" + actualrow[2]);
				element.prev().attr("id","div_" + arrData["id"]);
			    }
			    else{
				element.attr("id","rulerdown_" + arrData["id"] + "_" + actualrow[2]);
				element.prev().attr("id","rulerup_" + arrData["id"] + "_" + actualrow[2]);
				element.prev().prev().attr("id","div_" + arrData["id"]);
			    }
			    var tdParent = element.parents("td:first");
			    tdParent.prev().children().attr("name","id_" + arrData["id"]);
			    tdParent.next().html("<a><img src='"+arrData["traffic"]["image"]+"' border=0 title='"+arrData["traffic"]["title"]+"'</a>");
			    tdParent.next().next().html("<a><img src='"+arrData["target"]["image"]+"' border=0 title='"+arrData["target"]["title"]+"'</a>");
			    tdParent.next().next().next().html(arrData["interface"]);
			    tdParent.next().next().next().next().html(arrData["ipSource"]);
			    tdParent.next().next().next().next().next().html(arrData["ipDestiny"]);
			    tdParent.next().next().next().next().next().next().html(arrData["protocol"]);
			    tdParent.next().next().next().next().next().next().next().html(arrData["details"]);
			    var href = tdParent.next().next().next().next().next().next().next().next().children().attr("href");
			    tdParent.next().next().next().next().next().next().next().next().html(arrData["activate"]);
			    href = href.split('&');
			    if(href[3] && href[4]){
				var nav   = href[3];
				var start = href[4];				tdParent.next().next().next().next().next().next().next().next().children().attr("href",tdParent.next().next().next().next().next().next().next().next().children().attr("href") + "&" + nav + "&" + start);
			    }			    
			    tdParent.next().next().next().next().next().next().next().next().next().html(arrData["edit"]);
			}
			else{
			    $("#msg_status").html(statusResponse);
			    setTimeout('$("#msg_status").html("")',300);
			}
		    });
	}
    });

});

function showElementByTraffic()
{
    var traffic = document.getElementById('id_traffic');

    if(traffic){
        if( traffic.value == 'INPUT' ){
            document.getElementById('id_interface_in').style.display = '';
            document.getElementById('id_interface_out').style.display = 'none';
        }
        else if( traffic.value == 'OUTPUT' ){
            document.getElementById('id_interface_in').style.display = 'none';
            document.getElementById('id_interface_out').style.display = '';
        }
        else if( traffic.value == 'FORWARD' ){
            document.getElementById('id_interface_in').style.display = '';
            document.getElementById('id_interface_out').style.display = '';
        }
    }
}

function showElementByProtocol()
{
    var protoc = document.getElementById('id_protocol');

    if(protoc){
        if( protoc.value == 'TCP' ){
            document.getElementById('id_port_in').style.display = '';
            document.getElementById('id_port_out').style.display = '';
            document.getElementById('id_type_icmp').style.display = 'none';
            document.getElementById('id_established').style.display = 'none';
            document.getElementById('id_related').style.display = 'none';
            document.getElementById('id_id_ip').style.display = 'none';
        }
        else if( protoc.value == 'UDP' ){
            document.getElementById('id_port_in').style.display = '';
            document.getElementById('id_port_out').style.display = '';
            document.getElementById('id_type_icmp').style.display = 'none';
            document.getElementById('id_established').style.display = 'none';
            document.getElementById('id_related').style.display = 'none';
            document.getElementById('id_id_ip').style.display = 'none';
        }
        else if( protoc.value == 'ICMP' ){
            document.getElementById('id_port_in').style.display = 'none';
            document.getElementById('id_port_out').style.display = 'none';
            document.getElementById('id_type_icmp').style.display = '';
            document.getElementById('id_established').style.display = 'none';
            document.getElementById('id_related').style.display = 'none';
            document.getElementById('id_id_ip').style.display = 'none';
        }
        else if( protoc.value == 'IP' ){
            document.getElementById('id_port_in').style.display = 'none';
            document.getElementById('id_port_out').style.display = 'none';
            document.getElementById('id_type_icmp').style.display = 'none';
            document.getElementById('id_established').style.display = 'none';
            document.getElementById('id_related').style.display = 'none';
            document.getElementById('id_id_ip').style.display = '';
        }
        else if( protoc.value == 'ALL' ){
            document.getElementById('id_port_in').style.display = 'none';
            document.getElementById('id_port_out').style.display = 'none';
            document.getElementById('id_type_icmp').style.display = 'none';
            document.getElementById('id_established').style.display = 'none';
            document.getElementById('id_related').style.display = 'none';
            document.getElementById('id_id_ip').style.display = 'none';
        }
        else if( protoc.value == 'STATE' ){
            document.getElementById('id_port_in').style.display = 'none';
            document.getElementById('id_port_out').style.display = 'none';
            document.getElementById('id_type_icmp').style.display = 'none';
            document.getElementById('id_id_ip').style.display = 'none';
            var state = document.getElementById('state');
            var input_ = state.getElementsByTagName('input');
            var established_check = false;
            var related_check = false;
            if(input_[0].value == ""){
                 established_check = false;
                 related_check = false;
            }else{
                var tmp = input_[0].value.split(",");
                if(tmp[0]=="Established"){
                     established_check = true;
                    if(tmp[1]=="Related")
                         related_check = true; 
                }else if(tmp[0]=="Related")
                        related_check = true;
            }
            var established = document.getElementById('id_established');
            established.style.display = '';
            var checkbox1 = established.getElementsByTagName("input");
            checkbox1[0].checked = established_check;
            if(established_check)
                document.getElementById('established').value = "on";
            else
                document.getElementById('established').value = "off";
            var related = document.getElementById('id_related');
            related.style.display = '';
            var checkbox2 = related.getElementsByTagName("input");
            checkbox2[0].checked = related_check;
            if(related_check)
                document.getElementById('related').value = "on";
            else
                document.getElementById('related').value = "off";
        }
    }
}
