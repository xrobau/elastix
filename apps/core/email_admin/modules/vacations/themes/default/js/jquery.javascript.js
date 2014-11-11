
    $(document).ready(function(){
	$("input[id^='f-calendar-field']").change(function(){
	    var cadenaFecha1 = $("#f-calendar-field-1").val();
	    var cadenaFecha2 = $("#f-calendar-field-2").val();
	    var strDate1 = new Date(cadenaFecha1);
	    var strDate2 = new Date(cadenaFecha2);

	    //Resta fechas y redondea
	    var diferencia = strDate2.getTime() - strDate1.getTime();
	    var dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
	    var segundos = Math.floor(diferencia / 1000);
	    $('#num_days').text(dias);
	});

    });

    function popup_get_emails(url_popup){
        var ancho = 640;
        var alto = 400;
        var winiz = (screen.width-ancho)/2;
	var winal = (screen.height-alto)/2;
	my_window = window.open(url_popup,"my_window","width="+ancho+",height="+alto+",top="+winal+",left="+winiz+",location=yes,status=yes,resizable=yes,scrollbars=yes,fullscreen=no,toolbar=yes");
        my_window.document.close();
    }

    function getAccount(account,id){
	var subject  = $('#'+id+'info').children(":first-child").text();
	var body     = $('#'+id+'info').children(":nth-child(2)").text();
	var vacation = $('#'+id+'info').children(":nth-child(3)").text();
	var ini_date = $('#'+id+'info').children(":nth-child(4)").text();
	var end_date = $('#'+id+'info').children(":last-child").text();
	window.opener.document.getElementById("email").value = account;
	window.opener.document.getElementById("subject").value = subject;
	window.opener.document.getElementById("body").value = body;
	window.opener.document.getElementById("f-calendar-field-1").value = ini_date;
	window.opener.document.getElementById("f-calendar-field-2").value = end_date;
	//cambiando #dias
	window.opener.document.getElementById("num_days").firstChild.nodeValue = DiferenciaFechas();
	if(vacation=="yes"){
	    var lblDisactivate = window.opener.document.getElementById("lblDisactivate").value;
	    window.opener.document.getElementById("actionVacation").value = lblDisactivate;
	    window.opener.document.getElementById("actionVacation").setAttribute("name", "disactivate");
	}else{
	    var lblActivate = window.opener.document.getElementById("lblActivate").value;
	    window.opener.document.getElementById("actionVacation").value = lblActivate;
	    window.opener.document.getElementById("actionVacation").setAttribute("name", "activate");
	}
	window.close();
    }

    // implement JSON.parse de-serialization
    function JSONtoString(str) {
        if (str === "") str = '""';
        eval("var p=" + str + ";");
        return p;
    }

    function DiferenciaFechas(){
	//Obtiene los datos del formulario
	var cadenaFecha1 = window.opener.document.getElementById("f-calendar-field-1").value;
	var cadenaFecha2 = window.opener.document.getElementById("f-calendar-field-2").value;

	var strDate1 = new Date(cadenaFecha1);
        var strDate2 = new Date(cadenaFecha2);
	
	//Resta fechas y redondea
	var diferencia = strDate2.getTime() - strDate1.getTime();
	var dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
	var segundos = Math.floor(diferencia / 1000);
	window.opener.document.getElementById("f-calendar-field-2").value
	return dias;
    }
