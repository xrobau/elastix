var frmvalidator = null;
$( document ).ready(function() {
    $(".close").click(function() {
        $("#message_area").slideUp();        
    });


    $(function() {
        $( "#progressbar" ).progressbar({
          value: false
        });
    });
    
});

function editFaxExten(){
    showElastixUFStatusBar("Saving...");
    var arrAction = new Array();
    arrAction["menu"]="my_fax";
    arrAction["action"]="editFaxExten";
    arrAction["CID_NAME"]=$("input[name='CID_NAME']").val();
    arrAction["CID_NUMBER"]=$("input[name='CID_NUMBER']").val();
    arrAction["COUNTRY_CODE"]=$("input[name='COUNTRY_CODE']").val();
    arrAction["AREA_CODE"]=$("input[name='AREA_CODE']").val();
    arrAction["FAX_SUBJECT"]=$("input[name='FAX_SUBJECT']").val();
    arrAction["FAX_CONTENT"]=$("textarea[name='FAX_CONTENT']").val();
    
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            hideElastixUFStatusBar();
            if(error!=""){
                //alert(error);
                $("#message_area").slideDown();
                $("#my_extension_errorloc").removeClass("alert-success").addClass("alert-danger");
                $("#my_extension_errorloc").html(error);
            }else{
                //alert(arrData);
                $("#message_area").slideDown();
                $("#my_extension_errorloc").removeClass("alert-danger").addClass("alert-success");
                $("#my_extension_errorloc").html(arrData);  
            }
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
    arrAction["menu"]    = "my_fax";
    arrAction["rawmode"] = "yes";

    request("index.php",arrAction,true,
            function(arrData,statusResponse,error)
            {
                if(statusResponse=="CHANGED"){
                    $(".fax-status").html(arrData);
                }
                
            });
}


