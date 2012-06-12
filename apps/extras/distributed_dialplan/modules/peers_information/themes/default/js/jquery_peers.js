var refreshId = setInterval(function()
{  
    $(".resp").each(function() {   
	var valor=$(this).attr('id')
	var url = "index.php";
        var module_name = "peers_information";
        var arrParams = new Array();
        arrParams["menu"]	= module_name;
        arrParams["action"] = "status";
        arrParams["rawmode"] = "yes";
	arrParams["conId"] = valor;
      	
	request(url,arrParams,false,
            function(arrData,statusResponse,error)
            {
		var status= arrData["status"];
		var color= arrData["color"];
		var ide = arrData["id"];
		var view = arrData["view"];
		var con = arrData["con"];
		var cia = arrData["cia"];
		if(con==1){
			connect(ide);
		}
		$("#option"+ide).html(view);
		$("#cia"+ide).html(cia);
                $("#status"+ide).fadeOut("slow").html(status).fadeIn("slow");
		//$("#his_status"+ide).fadeOut("slow").html(his_status).fadeIn("slow");
		$("#status"+ide).css("color",color);
   
           }
        );
     });
}, 15000);

setTimeout(function()
{  
    $(".resp").each(function() {   
	var valor=$(this).attr('id')
	var url = "index.php";
        var module_name = "peers_information";
        var arrParams = new Array();
        arrParams["menu"]	= module_name;
        arrParams["action"] = "status";
        arrParams["rawmode"] = "yes";
	arrParams["conId"] = valor;
      	
	request(url,arrParams,false,
            function(arrData,statusResponse,error)
            {
		var status= arrData["status"];
		var color= arrData["color"];
		var ide = arrData["id"];
		var view = arrData["view"];
		var con = arrData["con"];
		var cia = arrData["cia"];
		
		if(con==1){
			connect(ide);
		}
		$("#cia"+ide).html(cia);
		$("#status"+ide).html("");	
		$("#option"+ide).html(view);
		$("#status"+ide).css("display","block");	
                $("#status"+ide).fadeOut("slow").html(status).fadeIn("slow");
		//$("#his_status"+ide).fadeOut("slow").html(his_status).fadeIn("slow");
		$("#status"+ide).css("color",color);
   
           }
        );
     });
}, 500);


function connect(id){
	var url = "index.php";
        var module_name = "peers_information";
        var arrParams = new Array();
        arrParams["menu"]	= module_name;
        arrParams["action"] = "connect";
        arrParams["rawmode"] = "yes";
	arrParams["peerId"] = id;
      	$("#status"+id).fadeOut("slow").html("<img src='images/loading.gif' height='20px' />").fadeIn("slow");
	request(url,arrParams,false,
            function(arrData,statusResponse,error)
            {
		//alert("connected");
   
           }
        );
}

function accept(id){
	var url = "index.php";
        var module_name = "peers_information";
        var arrParams = new Array();
        arrParams["menu"]	= module_name;
        arrParams["action"] = "request_accept";
        arrParams["rawmode"] = "yes";
	arrParams["peerId"] = id;
      	$("#status"+id).fadeOut("slow").html("<img src='images/loading.gif' height='20px' />").fadeIn("slow");
	request(url,arrParams,false,
            function(arrData,statusResponse,error)
            {
		//alert("connected");
   
           }
        );
}

/*function reject(id){
	var url = "index.php";
        var module_name = "peers_information";
        var arrParams = new Array();
        arrParams["menu"]	= module_name;
        arrParams["action"] = "request_reject";
        arrParams["rawmode"] = "yes";
	arrParams["peerId"] = id;
      	$("#status"+id).fadeOut("slow").html("<img src='images/loading.gif' height='20px' />").fadeIn("slow");
	$(this).parent().parent().remove();

	request(url,arrParams,false,
            function(arrData,statusResponse,error)
            {
		//alert("connected");
   
           }
        );
}*/

function disconnect(id){
	var url = "index.php";
        var module_name = "peers_information";
        var arrParams = new Array();
        arrParams["menu"]	= module_name;
        arrParams["action"] = "disconnected";
        arrParams["rawmode"] = "yes";
	arrParams["peerId"] = id;
      	$("#status"+id).fadeOut("slow").html("<img src='images/loading.gif' height='20px' />").fadeIn("slow");
	request(url,arrParams,false,
            function(arrData,statusResponse,error)
            {
		//alert("connected");
   
           }
        );
}
$(function () {
$('.checkall').click(function () {
		$(".resp").find(':checkbox').attr('checked', this.checked);
	});

});
