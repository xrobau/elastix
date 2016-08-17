$(document).ready(function() {
    $('input[name="ini_date"], input[name="end_date"]').change(function() {
        update_date_span($(document));
    });
});

function update_date_span(op)
{
    var cadenaFecha1 = op.find('input[name="ini_date"]').val();
    var cadenaFecha2 = op.find('input[name="end_date"]').val();
    var strDate1 = new Date(cadenaFecha1);
    var strDate2 = new Date(cadenaFecha2);

    //Resta fechas y redondea
    var diferencia = strDate2.getTime() - strDate1.getTime();
    var dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
    op.find('span#num_days').text(dias);
}

function popup_get_emails(url_popup)
{
    var ancho = 640;
    var alto = 400;
    var winiz = (screen.width-ancho)/2;
    var winal = (screen.height-alto)/2;
    my_window = window.open(url_popup,"my_window","width="+ancho+",height="+alto+",top="+winal+",left="+winiz+",location=yes,status=yes,resizable=yes,scrollbars=yes,fullscreen=no,toolbar=yes");
}

function getAccount(account,id)
{
    var op = $(window.opener.document);
    var divinfo = $('#'+id+'info');

    op.find('#email').val(account);
    op.find('#subject').val(divinfo.children(":first-child").text());
    op.find('#body').val(divinfo.children(":nth-child(2)").text());

    op.find('input[name="ini_date"]').val(divinfo.children(":nth-child(4)").text());
    op.find('input[name="end_date"]').val(divinfo.children(":last-child").text());
    update_date_span(op);

    var vacation = divinfo.children(":nth-child(3)").text();
    if (vacation == "yes") {
        var lblDisactivate = window.opener.document.getElementById("lblDisactivate").value;
        window.opener.document.getElementById("actionVacation").value = lblDisactivate;
        window.opener.document.getElementById("actionVacation").setAttribute("name", "disactivate");
    } else {
        var lblActivate = window.opener.document.getElementById("lblActivate").value;
        window.opener.document.getElementById("actionVacation").value = lblActivate;
        window.opener.document.getElementById("actionVacation").setAttribute("name", "activate");
    }
    window.close();
}
