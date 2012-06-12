
/*
$(document).ready(function(){
    checkFaxStatus();

});*/

checkFaxStatus();
function checkFaxStatus()
{
    var arrAction        = new Array();
    arrAction["action"]  = "checkFaxStatus";
    arrAction["menu"]    = "faxlist";
    arrAction["rawmode"] = "yes";

    request("index.php",arrAction,true,
            function(arrData,statusResponse,error)
            {
		var from = $("#from option:selected'").text();
		var toRemove = 'chk_';
		from = from.replace("/", '');
		from = from.replace(/^.*\s+|\s+$/g,"")
	
                if(statusResponse=="CHANGED"){
                    var key = "";
                    for(key in arrData["faxes"]){
                       $('td').each(function(){
                            //var field = $(this).text();
                            if(from==key)
                                if(arrData["faxes"][from]){
					var status = arrData["faxes"][from];
					status = status.substring(1,8);
					//alert(status);
					if(status=="Running"){
						$("#sending_fax").css("display","none");
						$("#success_fax").css("display","block");
						$("#statusFax").html("");
					}
					else{
						$("#sending_fax").css("display","none");
						$("#statusFax").html(arrData["faxes"][from]+"...");
					}
				}
			
				
                        
                        });
                    }
                }
		
                //return false; //continua la recursividad
            });
}
