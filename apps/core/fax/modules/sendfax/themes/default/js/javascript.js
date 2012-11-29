$(document).ready(function() {
 var valor = $("input[name='to']").val();
   if(valor!=""){
      var intervalo  = setInterval("checkSendStatus("+valor+")",30000);
      checkFaxStatus();
   }
});

/*
$(document).ready(function(){
    checkFaxStatus();

});*/

//checkFaxStatus();
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
                                    //if(status=="Running"){
                                    //	$("#sending_fax").css("display","none");
                                    //	$("#success_fax").css("display","block");
                                    //	$("#statusFax").html("");
                                    //}
                                    //else{
                                        $("#sending_fax").css("display","none");
                                        $("#statusFax").html(arrData["faxes"][from]+"...");
                                    //}
                                }
                        });
                    }
                }
                //return false; //continua la recursividad
            });
}
function checkSendStatus(ext)
{
    var arrAction        = new Array();
    arrAction["action"]  = "checkSendStatus";
    arrAction["menu"]    = "faxlist";
    arrAction["rawmode"] = "yes";
    arrAction["ext"] = ext;
    request("index.php",arrAction,true,
            function(arrData,statusResponse,error)
            {
                if((arrData=="")||(arrData==null)||(!arrData)||(arrData==false)){
                   var jid = $("#jid").val();
                   stateFax(jid);
                   //clearInterval(int);
                   return true;

                }else{

                $("#jid").val(arrData["jid"][0]);
                $("#statusFax").html("Dials: "+arrData["dial"][0]+" "+arrData["status"]);
                var times = ["dial"][0];
                if(times=="11:12"){
                   clearInterval(intervalo);
                   var file = arrData["jid"][0];
                   var pdf_file = "doc"+file+".pdf";
                   setSendFaxMsg(pdf_file);
                }
                return true
                }

            });
}


function stateFax(jid)
{
    var arrAction        = new Array();
    arrAction["action"]  = "stateFax";
    arrAction["menu"]    = "faxlist";
    arrAction["rawmode"] = "yes";
    arrAction["jid"] = jid;
    request("index.php",arrAction,true,
            function(arrData,statusResponse,error)
            {
                if (arrData["state"][0][2]=="F")
                    $("#statusFax").html("SEND FAILED: "+arrData["state"][0][7]+" "+arrData["state"][0][8]);
                    //$("#success_fax").css("display", "none");
                if (arrData["state"][0][2]=="D"){
                    $("#statusFax").html("SUCCESFULL SEND");
                   // $("#success_fax").css("display", "none");

                }
                return true;


            });
}

