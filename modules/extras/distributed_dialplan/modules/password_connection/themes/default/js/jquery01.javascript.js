/*javascript*/
$(document).ready(function(){
    $('#getpass').click(function(){
        var arrAction              = new Array();
            arrAction["action"]    = "getpassconnect";
            arrAction["rawmode"]   = "yes";
            request("index.php",arrAction,false,
                function(arrData,statusResponse,error)
                {
                    $('#keyword').val(arrData["pass"]);
                }
            );
    });
});