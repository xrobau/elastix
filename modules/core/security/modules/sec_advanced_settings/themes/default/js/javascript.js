$(document).ready(function (){
    changeActivateDefault();
    $(":checkbox").iButton({
        labelOn: "On",
        labelOff: "Off",
        change: function ($input){
            var value_fpbx_frontend;
            if($input.is(":checked"))
		value_fpbx_frontend = "1";
	    else
		value_fpbx_frontend = "0";
	    
	    if($("#hidden_status_fpbx_frontend").val() != "2"){
		//GUARDAR EL ESTADO DEL "ENABLE FRONT-END FREEPBX"
		var arrAction = new Array();
		arrAction["action"]              = "update_status_fpbx_frontend";
		arrAction["menu"]	   	 = "sec_advanced_settings";
		arrAction["rawmode"]             = "yes";
		arrAction["new_status_fpbx_frontend"] = value_fpbx_frontend;
		request("index.php",arrAction,false,
		    function(arrData,statusResponse,error)
		    {   
			if(arrData['result']){
			    $("#status_fpbx_frontend").val(value_fpbx_frontend);
			}
			$("#message_error").remove();
			if($(".neo-module-content")){
			  var message= "<div class='div_msg_errors' id='message_error'>" +
					    "<div style='float:left;'>" +
						"<b style='color:red;'>&nbsp;&nbsp;"+arrData['message_title']+"</b>" +
					    "</div>" +
					    "<div style='text-align:right; padding:5px'>" +
						"<input type='button' onclick='hide_message_error();' value='"+arrData['button_title']+"'/>" +
					    "</div>" +
					    "<div style='position:relative; top:-12px; padding: 0px 5px'>" +
						arrData['message'] +
					    "</div>" +
					"</div>";
			  $(".neo-module-content:first").prepend(message);
			}
			else{
			    var message= "<div style='background-color: rgb(255, 238, 255);' id='message_error'><table width='100%'><tr><td align='left'><b style='color:red;'>" +
					  arrData['message_title'] + "</b>" + arrData['message'] + "</td> <td align='right'><input type='button' onclick='hide_message_error();' value='" +
					  arrData['button_title']+ "'/></td></tr></table></div>";
			    $("body > table > tbody > tr > td").prepend(message);
			}
		    }
		);
	    }
	    $("#hidden_status_fpbx_frontend").val($("#status_fpbx_frontend").val());
        }
    }).trigger("change");
});

function changeActivateDefault()
{  
    var flag_status = $("#hidden_status_fpbx_frontend").val();
    if(flag_status == "1"){
        $("input[name=chkoldstatus_fpbx_frontend]").attr("checked", "checked"); 
        $("#status_fpbx_frontend").val("1");
    }else{
        $("input[name=chkoldstatus_fpbx_frontend]").removeAttr("checked");
        $("#status_fpbx_frontend").val("0");
    }
    $("#hidden_status_fpbx_frontend").val("2");
}
