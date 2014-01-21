$(document).ready(
    function(){
         $( "#slider" ).slider({
            range: "min",
            min: 0,
            max: 20,
            value: $("#recording_priority").val(),
            slide: function(event, ui){
                $("#recording_priority_amount").text(ui.value);
                $("#recording_priority").val(ui.value);
            }
         });
         
         $("input[name|=phone_number_CF]").focus(
            function(){
                $(this).attr("value","");
            }
         );
         $("input[name|=phone_number_CFU]").focus(
            function(){
                $(this).attr("value","");
            }
         );
         $("input[name|=phone_number_CFB]").focus(
            function(){
                $(this).attr("value","");
            }
         );

         $("input[name|=chkoldcall_forward]").click(
            function()
            {       var statusCF = $("#call_forward").val();
                    if(statusCF == "off")
                       $("input[name|=phone_number_CF]").attr("disabled","disabled");
                    else
                        $("input[name|=phone_number_CF]").removeAttr("disabled");
            }
        );
        $("input[name|=chkoldcall_forward_U]").click(
            function()
            {       var statusCFU = $("#call_forward_U").val();
                    if(statusCFU == "off")
                        $("input[name|=phone_number_CFU]").attr("disabled","disabled");
                    else
                        $("input[name|=phone_number_CFU]").removeAttr("disabled");
            }
        );
        $("input[name|=chkoldcall_forward_B]").click(
            function()
            {       var statusCFB = $("#call_forward_B").val();
                    if(statusCFB == "off")
                        $("input[name|=phone_number_CFB]").attr("disabled","disabled");
                    else
                        $("input[name|=phone_number_CFB]").removeAttr("disabled");
            }
        );
         
         if($("#call_forward").val() == "off")
            $("input[name|=phone_number_CF]").attr("disabled","disabled");
         else
            $("input[name|=phone_number_CF]").removeAttr("disabled");

         if($("#call_forward_U").val() == "off")
            $("input[name|=phone_number_CFU]").attr("disabled","disabled");
         else
            $("input[name|=phone_number_CFU]").removeAttr("disabled");

         if($("#call_forward_B").val() == "off")
            $("input[name|=phone_number_CFB]").attr("disabled","disabled");
         else
            $("input[name|=phone_number_CFB]").removeAttr("disabled");
    }
);

