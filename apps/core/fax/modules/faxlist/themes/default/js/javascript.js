
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
                if(statusResponse=="CHANGED"){
                    var key = "";
                    for(key in arrData["faxes"]){
                        $('td[class*=table_data]').each(function(){
                            var field = $(this).text();
                            if(field==key)
                                $(this).parent().children(':nth-child(7)').text(arrData["faxes"][key]);
                        });
                    }
                }
                //return false; //continua la recursividad
            });
}