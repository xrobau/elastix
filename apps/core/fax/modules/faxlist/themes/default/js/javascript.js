$(document).ready(function(){
        var ext;
      checkAllStatus();
});

function checkAllStatus(){
    var arra = new Array(); 

      $('.load').each(function() {
          ext = $(this).attr("id");  
          $("#"+ext).html("<div style='color:blue'>Checking...</div>");
 
          arra.push(ext);
          //$('#'+ext).click();
             // ext = $(this).attr("id");
             // checkStatus(ext);
      });
      checkStatus(arra);
}

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
                            if(field==key){
                                $(this).parent().children(':nth-child(5)').text(arrData["faxes"][key]);
							}
                        });
                    }
                }
                //return false; //continua la recursividad
            });
}
function checkStatus(extension)
{
    var label = $("#load_"+extension+" a").html();

    $("#"+extension).html("<div style='color:blue'>Checking...</div>");
    if (extension != ""){
        var arrAction        = new Array();
        arrAction["action"]  = "checkFaxStatus2";
        arrAction["menu"]    = "faxlist";
        arrAction["rawmode"] = "yes";
        arrAction["ext"]     = extension;
        request("index.php",arrAction,true,
            function(arrData,statusResponse,error)
            {
                if(statusResponse){
                  //var ext = arrData["faxes"][].split("_");
                  $('.load').each(function() {
                       ext = $(this).attr("id");
                       if(typeof  arrData["faxes"][ext]!== "undefined" && arrData["faxes"][ext]){
                       var exten = arrData["faxes"][ext].split("_");
                       if(exten[0]=="Fax OK")
                         $("#"+ext).html("<img src='modules/faxlist/images/check.png' title='Online'/>");
                       else
		         $("#"+ext).html("<img src='modules/faxlist/images/error.png' title='Offline'/>");
                       }
                       
                   });
                  return true;
                }
                //return false; //continua la recursividad
            });
        }else{
          alert("Null Extension");
        }
}

