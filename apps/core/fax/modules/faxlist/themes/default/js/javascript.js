$(document).ready(function(){
        var ext;
      checkAllStatus();
});

function checkAllStatus(){
      $('.load').each(function() {
              ext = $(this).attr("id");
              checkStatus(ext);
      });
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

    $("#"+extension).attr("onclick","");
    $("#"+extension).css("cursor","auto");
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
                  if(arrData["faxes"]=="Fax OK")
                     $("#"+extension).html("<img src='modules/faxlist/images/check.png' title='OK'/>");
                  else
                     $("#"+extension).html("<img src='modules/faxlist/images/error.png' title='ERROR'/>");

                  $("#"+extension).attr("onclick","checkStatus("+extension+")");
                  $("#"+extension).css("cursor","pointer");
                  return true;
                }
                //return false; //continua la recursividad
            });
        }else{
          alert("Null Extension");
        }
}

