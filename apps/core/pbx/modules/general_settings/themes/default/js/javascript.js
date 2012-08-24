$(document).ready(function(){
    $('td input:checkbox[class=check]').click(function(){
        var name=$(this).attr("name");
        var feature=name.substring(0,name.length-4);
        if($(this).is(":checked")){
            if($(this).attr("disabled")!="disabled"){
                $(this).val("on");
                $(this).attr("checked","checked");
                var fc=$(this).parents("tr:first").children("td").children("input:text[name="+feature+"]");
                //obtenemos el valor por default
                var arrAction = new Array();
                arrAction["action"]   = "fc_get_default_code";
                arrAction["menu"]     = "features_code";
                arrAction["rawmode"]  = "yes";
                arrAction["fc_name"]  = feature;
                request("index.php",arrAction,false,
                function(arrData,statusResponse,error)
                {
                    if(error!=""){
                        alert(error);
                    }else{
                        fc.val(arrData);
                    }
                });
                //no se puede editar el valor por default
                fc.attr("readonly","readonly");
                fc.css({background: '#D8D8D8 '});
            }
         }else{
            $(this).val("off");
            if($(this).attr("disabled")!="disabled"){
                var fc=$(this).parents("tr:first").children("td").children("input:text[name="+feature+"]");
                fc.removeAttr("readonly");
                fc.val("");
                $(this).removeAttr("checked");
                fc.css({background: '#FFFFFF '});
            }
        }
    });
	fc_use_deafault();
});

function fc_use_deafault(){
    $('td').children("input:checkbox[class=check]").each(function(){
        var name=$(this).attr("name");
        var feature=name.substring(0,name.length-4);
        if($(this).is(":checked")){
            var fc=$(this).parents("tr:first").children("td").children("input:text[name="+feature+"]");
            fc.attr("readonly","readonly");
            fc.css({background: '#D8D8D8 '});
        }
    });
    var arrFeature=new Array("pickup","blind_transfer","attended_transfer","one_touch_monitor","disconnect_call");
    for(var i=0; i< arrFeature.length; i++){
        $("input:text[name="+arrFeature[i]+"]").attr("readonly","readonly");
        $("input:text[name="+arrFeature[i]+"]").css({background: '#D8D8D8 '});
    }
}

