$(document).ready(function() {
    $('div.callcenter-recordings').click(function () {
        // Ocultar o mostrar items según la clase
        if ($(this).hasClass('collapsed'))
            $(this).removeClass('collapsed');
        else $(this).addClass('collapsed');
    });
});