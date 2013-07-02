$(document).ready(function(){
    if($("#filter_field").val() == "userfield"){
	document.getElementsByName("filter_value")[0].style.display="none";
	document.getElementById("filter_value_userfield").style.display="";
    }
    $("#filter_field").change(function(){
	if($(this).val() == "userfield"){
	    document.getElementsByName("filter_value")[0].style.display="none";
	    document.getElementById("filter_value_userfield").style.display="";
	}
	else{
	    document.getElementsByName("filter_value")[0].style.display="";
	    document.getElementById("filter_value_userfield").style.display="none";
	}
    });
});