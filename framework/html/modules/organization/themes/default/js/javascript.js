function select_country()
{
    var country=$("#country").find('option:selected').val();
    var message = "";
    var arrAction = new Array();
    arrAction["menu"]="organization";
    arrAction["action"]="get_country_code";
    arrAction["country"]=country;
    arrAction["rawmode"]="yes";
    request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            if(error!=""){
                alert(error);
            }else{
                $('input[name="country_code"]').val(arrData);
            }
    });
}