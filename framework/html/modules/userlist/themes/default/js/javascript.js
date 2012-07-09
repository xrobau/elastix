function apply_changes()
{
    var arrAction                     = new Array();
	arrAction["action"]           = "apply_changes_UserExtension";
	arrAction["menu"]             = "userlist";
	arrAction["group"]            = document.getElementById("group").value;
	arrAction["extension"]        = document.getElementById("extension").value;
	arrAction["description"]      = document.getElementsByName("description")[0].value;
	arrAction["password1"]        = document.getElementsByName("password1")[0].value;
	arrAction["password2"]        = document.getElementsByName("password2")[0].value;
	arrAction["webmailuser"]      = document.getElementsByName("webmailuser")[0].value;
	arrAction["webmaildomain"]    = document.getElementsByName("webmaildomain")[0].value;
	arrAction["webmailpassword1"] = document.getElementsByName("webmailpassword1")[0].value;
	arrAction["id_user"]          = document.getElementsByName("id_user")[0].value;
	arrAction["rawmode"]          = "yes";
	request("index.php",arrAction,false,
	    function(arrData,statusResponse,error)
	    {   
		if(arrData["success"]){
		    if (window.opener && !window.opener.closed) {
			window.opener.location.reload();
		    }
		    window.close();
		}
		else{
		    if(arrData["mb_title"] && arrData["mb_message"]){
			if(document.getElementById("table_error"))
			  document.getElementById("table_error").style.display='';
			else
			  document.getElementById("message_error").style.display='';
			document.getElementById("mb_title").innerHTML="&nbsp;" + arrData["mb_title"];
			document.getElementById("mb_message").innerHTML= arrData["mb_message"];
		    }
		}
	    }
	);
}

function select_organization()
{
	var id=$("#organization").find('option:selected').val();
	var message = "";
	var arrAction = new Array();
	arrAction["menu"]="userlist";
	arrAction["action"]="get_groups";
	arrAction["idOrganization"]=id;
	arrAction["rawmode"]="yes";
	request("index.php", arrAction, false,
		function(arrData,statusResponse,error){
			if(error!=""){
				alert(error);
			}else{
				$("select[name='group'] option").remove();
				var i=0;
				for( x in arrData){
					var opcion=arrData[x][0];
					var valor=arrData[x][1];
					if(x<3){
						$('input[name="'+opcion+'"]').val(valor);
					}else if( x==3){
						$("select[name='group']").append("<option value="+opcion+" selected='selected'>"+valor+"</option>");
					}else
						$("select[name='group']").append("<option value="+opcion+">"+valor+"</option>");
				}
			}
	});
}