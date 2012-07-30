$(document).ready(function(){
	$("#create_vm").change(function (){
		if($("#create_vm").is(":checked")){
			$("#create_vm").val("yes");
			$("#create_vm").attr("checked","checked");
			$(".voicemail").attr("style","visibility: visible;");
		}else{
			$("#create_vm").val("off");
			$(".voicemail").attr("style","display: none;");
		}
	});
	
	$(".adv_opt").click(function(){
		if($("#mostra_adv").val()=="no"){
			$("#mostra_adv").val("yes");
			$(".show_more").attr("style","visibility: visible;");
		}else{
			$("#mostra_adv").val("no");
			$(".show_more").attr("style","display: none;");
		}
	});
});
