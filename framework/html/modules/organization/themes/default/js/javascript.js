$(document).ready(function(){
    $("td select[name=did]").change(function(){
        var did=$("select[name=did] option:selected").val();
        if(did!="none" && did!=""){
            var act_dids=$("#select_dids").val();
            //se agrega el elemento a la lista
            $("select[name='arr_did']").append("<option value="+did+">"+did+"</option>");
            //se quita el elemento de la lista de seleccion
            $("select[name=did] option:selected").remove();
            
            $("#select_dids").val(act_dids+did+",");
            $("select[name=did]").val("none");
        }
    });
});

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

function quitar_did(){
    var did=$("select[name=arr_did] option:selected").val();
    //se quita el elemento de la lista de seleccionados
    $("select[name=arr_did] option:selected").remove();
    //se agrega el elemento de la lista de canales disponibles
    $("select[name='did']").append("<option value="+did+">"+did+"</option>");
    var val=$("#select_dids").val();
    var arrVal=val.split(",");
    var option="";
    for (x in arrVal){
        if(arrVal[x]!=did && arrVal[x]!="")
            option += arrVal[x]+",";
    }
    $("#select_dids").val(option);
}

function mostrar_select_dids(){
    var val=$("#select_dids").val();
    var arrVal=val.split(",");
    
    for (x in arrVal){
        if(arrVal[x]!=""){
            $("select[name='arr_did']").append("<option value="+arrVal[x]+">"+arrVal[x]+"</option>");
        }
    }
    
    var chann=$("select[name='did']");
    var options = $('option', chann);
        options.each(function() {
            if(arrVal.indexOf($(this).text())!=-1){
                $("select[name=did] option[value='"+$(this).text()+"']").remove();
            }
        });
}