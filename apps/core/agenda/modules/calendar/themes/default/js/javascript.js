// Definir la función String.trim() en caso necesario
if (!String.prototype.trim) {
	String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }
}

// Definir la función Array.map() en caso necesario
if (!Array.prototype.map) {
	Array.prototype.map = function(fun /*, thisArg */) {
		"use strict";

		if (this === void 0 || this === null)
			throw new TypeError();

		var t = Object(this);
		var len = t.length >>> 0;
		if (typeof fun !== "function")
			throw new TypeError();

		var res = new Array(len);
		var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
		for (var i = 0; i < len; i++) {
			// NOTE: Absolute correctness would demand Object.defineProperty
			//       be used.  But this method is fairly new, and failure is
			//       possible only if Object.prototype or Array.prototype
			//       has a property |i| (very unlikely), so use a less-correct
			//       but more portable alternative.
			if (i in t)
				res[i] = fun.call(thisArg, t[i], i, t);
		}

		return res;
	};
}

// Definir toISOString en caso necesario
if (!Date.prototype.toISOString ) {
( function() {
	function pad(number) {
		if ( number < 10 ) {
			return '0' + number;
		}
		return number;
	}

    Date.prototype.toISOString = function() {
    	return this.getUTCFullYear() +
    		'-' + pad( this.getUTCMonth() + 1 ) +
    		'-' + pad( this.getUTCDate() ) +
    		'T' + pad( this.getUTCHours() ) +
    		':' + pad( this.getUTCMinutes() ) +
    		':' + pad( this.getUTCSeconds() ) +
    		'.' + (this.getUTCMilliseconds() / 1000).toFixed(3).slice( 2, 5 ) +
    		'Z';
    };
	}());
}

$(document).ready(function() {
	/* El DatePicker debe de inicializarse ANTES que el FullCalendar porque la
     * inicialización del FullCalendar toma todo el ancho restante disponible
     * al momento de dibujarse, y si el DatePicker se inicializa después, la
     * columna del último día de la semana se aplasta. */

    // Definición del calendario para navegar
    $("#calendar_datepick").datepicker({
        firstDay: 1,
        changeYear: true,
        changeMonth: true,
        showButtonPanel: false, //today buttom
        onChangeMonthYear: function(year, month, inst) {
            $('#calendar_main').fullCalendar('gotoDate', year, month - 1);
        },
        onSelect: function(dateText, inst){
            //// dateText mm/dd/yyyy
            //// año , mes[0,1,2,3...11], day
            var date = dateText.split("/",3);
            $('#calendar_main').fullCalendar('gotoDate', date[2], date[0] - 1, date[1]);
            $('#calendar_main').fullCalendar('changeView', 'agendaDay');
        }
    });

    // Botón de nuevo evento
    $("#calendar_newevent").click(function() {
        clearEventDialog();
        $('#calendar_eventdialog').dialog({
            title: arrLang_main['LBL_NEW_EVENT'],
            buttons: [
                {
                    text: arrLang_main['LBL_SAVE'],
                    click: function() {
                        var self = this;
                        var d = saveEventDialog('/rest.php/' + getCurrentElastixModule() + '/CalendarEvent');
                        if (d != null) {
                            d.done(function() {
                                $(self).dialog('close');
                                $('#calendar_main').fullCalendar('refetchEvents');
                            })
                            .fail(function(result) {
                                // TODO: i18n
                                result = $.parseJSON(result.responseText);
                                alert('Failed to create new event: ' + result.error.fm + ' - ' + result.error.fd);
                            });
                        }
                    }
                },
                {
                    text: arrLang_main['LBL_CANCEL'],
                    click: function() { $(this).dialog('close'); }
                }
            ]
        }).dialog('open');
    });

    /* Definición del widget principal del calendario. Este script también se
     * pide desde el popup de contactos telefónicos, que no incluye fullCalendar.
     * Por lo tanto se verifica si fullCalendar está disponible. */
    if (typeof $('#calendar_main').fullCalendar != 'undefined') $('#calendar_main').fullCalendar({
        theme: true,    // Usar tema actual de jQueryUI
        editable: true, // Habilitar modificación de eventos (drag/resize)
        timeFormat: 'H:mm{ - H:mm}',
        events: '/rest.php/' + getCurrentElastixModule() + '/CalendarEvent?format=fullcalendar',
        header: {
            left:   'prev,next today create',
            center: 'title',
            right:  'month,agendaWeek,agendaDay'
        },
        firstDay: 1,        // Calendario usa lunes como primer día de semana
        year: $('input[name="server_year"]').val(),
        month: $('input[name="server_month"]').val(),
        /*
        loading: function(isLoading, view) {
            console.debug("fullCalendar.loading: " + (isLoading ? 'TRUE' : 'FALSE'));
            console.debug(view);
        },
        */
        eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc,
                jsEvent, ui, view ) {
            /* El uso de toISOString() hace uso del hecho de que la función strtotime()
             * de PHP puede parsear el texto de Date.toISOString() */
            $.post(event.url, {
                startdate:  event.start.toISOString(),
                enddate:    event.end.toISOString()
            }, function(response) {
                //console.debug(response);
            }).fail(function(result) {
                revertFunc();
                // TODO: i18n
                result = $.parseJSON(result.responseText);
                alert('Failed to update event start: ' + result.error.fm + ' - ' + result.error.fd);
            });
        },
        eventResize: function( event, dayDelta, minuteDelta, revertFunc,
                jsEvent, ui, view ) {
            /* El uso de toISOString() hace uso del hecho de que la función strtotime()
             * de PHP puede parsear el texto de Date.toISOString() */
            $.post(event.url, {
                startdate:  event.start.toISOString(),
                enddate:    event.end.toISOString()
            }, function(response) {
                //console.debug(response);
            }).fail(function(result) {
                revertFunc();
                result = $.parseJSON(result.responseText);
                alert('Failed to update event duration: ' + result.error.fm + ' - ' + result.error.fd);
            });
        },
        eventClick: function(event, jsEvent, view) {
            openEventDialog(event.url);

            // Al devolver FALSE se impide que se abra en navegador el url REST
            return false;
        }
    });

    // Color del título del calendario
    $('.fc-header-title').css('color', '#E35332');

    // Preparar diálogo y widgets
    $('#calendar_eventdialog').dialog({
        autoOpen: false,
        width: 450,
        height: 500,
        modal: true
    });
    $('#CheckBoxRemi').button();
    $('#CheckBoxNoti').button();

    // Mostrar u ocultar recordatorio y correos según sea necesario
    $('#CheckBoxRemi').click(function() {
        if ($('#CheckBoxRemi').is(':checked')) {
            $('.remin').show();
        } else {
            $('.remin').hide();
        }
    });
    $('#CheckBoxNoti').click(function() {
        if ($('#CheckBoxNoti').is(':checked')) {
            $('.notif').show();
        } else {
            $('.notif').hide();
        }
    });

    // Un click en el icono de borrar correo quita la fila correspondiente
    $('#grilla').on('click', 'a.delete_email', function() {
        $($(this).parents('tr')[0]).remove();
        reindexEmailList($('#grilla tbody'));
    });

    // Cuenta de caracteres disponibles para TTS
    $(':input[name="tts"]')
        .change(updateTTSCharCount)
        .keyup(updateTTSCharCount);

    // Llamada de prueba con texto TTS
    $('#listenTTS').click(function() {
        var call_to = $(':input[name="call_to"]').val();
        var tts = $(':input[name="tts"]').val();
        if (tts == '') {
            alert(arrLang_main['MSG_ERROR_RECORDING']);
            return;
        }
        if (! /^\d+$/.test(call_to)) {
            alert(arrLang_main['MSG_ERROR_CALLTO']);
            return;
        }

        $.post('index.php', {
            menu:       getCurrentElastixModule(),
            action:     'previewtts',
            call_to:    call_to,
            tts:        tts,
            rawmode:    'yes'
        }, function(response) {
            if (response.status == 'error') {
                alert(response.message);
            }
        });
    });

    // Se prepara autocompletado con fuente de contactos remotos en not. correo
    $("#tags")
    // don't navigate away from the field on tab when selecting an item
    .bind('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB &&
            $(this).data( "autocomplete" ).menu.active ) {
            event.preventDefault();
        }
    })
    .autocomplete({
        minLength: 0,
        source: function(request, response) {
            var search = extractLast(request.term);

            // No se realiza búsqueda para cadenas vacías
            if (search.trim() == '') {
                response([]);
                return
            }

            // Se espera que la respuesta sea un arreglo de {label, value}
            $.get('rest.php/address_book/ContactList/external', {
                querytype:  'emailsearch',
                q:          search
            }, response)
            .fail(function() {
                // En fallo, el API requiere que se llame response() siempre
                response([]);
            });
        },
        focus: function() {
            // prevent value inserted on focus
            return false;
        },
        select: function( event, ui ) {
            var terms = split(this.value);
            // remove the current input
            terms.pop();
            // add the selected item
            terms.push( ui.item.label );
            // add placeholder to get the comma-and-space at the end
            terms.push("");
            this.value = terms.join(", ");
            return false;
        }
    });

    // Popup de búsqueda de número de teléfono
    $('#add_phone a').click(function() {
        var ancho = 600;
        var alto = 400;
        var winiz = (screen.width-ancho)/2;
        var winal = (screen.height-alto)/2;
        my_window = window.open(
                '?menu=address_book&gridformat=phonepick&rawmode=yes',
                "my_window",
                "width="+ancho+",height="+alto+",top="+winal+",left="+winiz+
                    ",location=no,status=no,resizable=yes,scrollbars=yes,fullscreen=no,toolbar=no");
        my_window.document.close();

    });

    if ($('input[name="event_id"]').length > 0) {
        var event_id = $('input[name="event_id"]').val();
        if (event_id != undefined && event_id != '') openEventDialog('/rest.php/' + getCurrentElastixModule() + '/CalendarEvent/' + event_id);
    }
});

function openEventDialog(url)
{
    clearEventDialog();
    $.get(url, function(result) {
        fillEventDialog(result);

        $('#calendar_eventdialog').dialog({
            title: arrLang_main['LBL_EDIT_EVENT'],
            buttons: [
                {
                    text: arrLang_main['LBL_SAVE'],
                    click: function() {
                        var self = this;
                        var d = saveEventDialog(url);
                        if (d != null) {
                            d.done(function() {
                                $(self).dialog('close');
                                $('#calendar_main').fullCalendar('refetchEvents');
                            })
                            .fail(function(result) {
                                // TODO: i18n
                                result = $.parseJSON(result.responseText);
                                alert('Failed to update event: ' + result.error.fm + ' - ' + result.error.fd);
                            });
                        }
                    }
                },
                {
                    text: arrLang_main['LBL_DELETE'],
                    click: function() {
                        var self = this;

                        // TODO: i18n
                        if (confirm('Are you sure this event should be removed?')){
                            $.ajax({
                                type: 'DELETE',
                                url: url,
                                success: function() {
                                    $(self).dialog('close');
                                    $('#calendar_main').fullCalendar('refetchEvents');
                                }
                            })
                            .fail(function(result) {
                                // TODO: i18n
                                result = $.parseJSON(result.responseText);
                                alert('Failed to remove event: ' + result.error.fm + ' - ' + result.error.fd);
                            });
                        }
                    }
                },
                {
                    text: arrLang_main['LBL_CANCEL'],
                    click: function() { $(this).dialog('close'); }
                }
            ]
        }).dialog('open');
    }).fail(function (result) {
        // TODO: i18n
        result = $.parseJSON(result.responseText);
        alert('Failed to fetch event: ' + result.error);
    });
}

// Limpiar el diálogo de eventos para evento nuevo
function clearEventDialog()
{
    $(':input[name="event"]').val('');
    $(':input[name="description"]').val('');

    var today_str = formatdate(new Date);
    $(':input[name="date"]').val(today_str);
    $(':input[name="to"]').val(today_str);

    // Campos de llamada de recordatorio
    $(':input[name="ReminderTime"]').val('10');
    $(':input[name="call_to"]').val('');
    $(':input[name="tts"]').val('').change();
    $('#CheckBoxRemi').prop('checked', false).button('refresh');
    $('.remin').hide();

    // Campos de notificaciones
    $('#CheckBoxNoti').prop('checked', false).button('refresh');
    $('#tags').val('');
    $('.notif').hide();
    $('#grilla tbody').empty();

    // Para lista vacía de correos, no se mostrará tabla
    $('#grilla').hide();
}

// Llenar el diálogo de eventos con los datos del evento cargado
function fillEventDialog(eventdata)
{
    $(':input[name="event"]').val(eventdata.subject);
    $(':input[name="description"]').val(eventdata.description);

    /* Se tiene que usar la utilidad parseISO8601 para que el parseo de la fecha
     * funcione en IE8 (...típico) */
    $(':input[name="date"]').val(formatdate($.fullCalendar.parseISO8601(eventdata.starttime)));
    $(':input[name="to"]').val(formatdate($.fullCalendar.parseISO8601(eventdata.endtime)));

    // Campos de llamada de recordatorio
    if (eventdata.reminder_timer != null) $(':input[name="ReminderTime"]').val(eventdata.reminder_timer);
    if (eventdata.call_to != null) $(':input[name="call_to"]').val(eventdata.call_to);
    if (eventdata.recording != null) $(':input[name="tts"]').val(eventdata.recording).change();
    if (eventdata.asterisk_call) {
        $('#CheckBoxRemi').prop('checked', true).button('refresh');
        $('.remin').show();
    }

    // Campos de notificaciones
    if (eventdata.emails_notification.length > 0) {
        $('#CheckBoxNoti').prop('checked', true).button('refresh');
        $('.notif').show();
        $('#grilla').show();

        var module_name = getCurrentElastixModule();
        var grilla_tbody = $('#grilla tbody');
        var email_regexp = /^"?(.*?)"?\s*<?(\S+@\S+?)>?$/;
        for (var i = 0; i < eventdata.emails_notification.length; i++) {
            var emailrow = $(
                '<tr class="letra12">'
                    +'<td align="center"><!-- INDEX --></td>'
                    +'<td align="center"><!-- CONTACT --></td>'
                    +'<td align="center"><!-- EMAIL --></td>'
                    +'<td align="center" class="del_contact" >'+
                        '<a class="delete_email"><img align="absmiddle" src="modules/'+ module_name +'/images/delete.png"/></a>'
                    +'</td>'
                +'</tr>');
            var emailcells = emailrow.children('td');
            var email = eventdata.emails_notification[i];
            if (email.indexOf('"') == -1 && email.indexOf('<') == 0)
                email = '"" ' + email;
            var m = email_regexp.exec(email);
            if (m != null) {
                if (m[1] == '') m[1] = '-';
                $(emailcells[1]).text(m[1]);
                $(emailcells[2]).text(m[2]);
            } else {
                $(emailcells[1]).text('-');
                $(emailcells[2]).text(eventdata.emails_notification[i]);
            }
            emailrow.data('email', eventdata.emails_notification[i]);
            grilla_tbody.append(emailrow);
        }
        reindexEmailList(grilla_tbody);
    }
}

// Recalcular los índices de la lista de correos cuando se quita una fila
function reindexEmailList(grilla_tbody)
{
    grilla_tbody.children('tr').map(function(index) {
        $($(this).children('td')[0]).text(index + 1);
    });
}

// Recoger los valores del formulario para enviar vía POST
function saveEventDialog(url)
{
    var postvars = {
        startdate:      datehhmm($(':input[name="date"]').val()),
        enddate:        datehhmm($(':input[name="to"]').val()),
        subject:        $(':input[name="event"]').val(),
        description:    $(':input[name="description"]').val(),
        color:          rgb2hex($('#colorSelector .colorpicker-box').css('backgroundColor')),
        asterisk_call:  $('#CheckBoxRemi').is(':checked'),
        recording:      $(':input[name="tts"]').val(),
        call_to:        $(':input[name="call_to"]').val(),
        reminder_timer: $(':input[name="ReminderTime"]').val(),

        emails_notification: ($('#CheckBoxNoti').is(':checked'))
            ? $('#grilla tbody tr').map(function(tr) { return $(this).data('email'); }).get()
            : []
    };
    if ($('#CheckBoxNoti').is(':checked')) {
        var email_regexp = /^"?(.*?)"?\s*<?(\S+@\S+?)>?$/;
        var newemails = $('#tags').val().split(',').map(function (s) { return s.trim(); });
        for (var i = 0; i < newemails.length; i++) if (newemails[i] != '') {
            var m = email_regexp.exec(newemails[i]);
            if (m == null) {
                alert(arrLang_main['MSG_ERROR_INVALID_EMAIL']);
                return null;
            }
            postvars.emails_notification.push('"' + m[1] + '" <' + m[2] + '>');
        }
        if (postvars.emails_notification.length <= 0) {
            alert(arrLang_main['MSG_ERROR_NO_EMAILS']);
            return null;
        }
    }

    if (postvars.subject == '') {
        alert(arrLang_main['MSG_ERROR_EVENTNAME']);
        return null;
    }
    if (postvars.startdate > postvars.enddate) {
        alert(arrLang_main['MSG_ERROR_DATE']);
        return null;
    }
    if (postvars.asterisk_call) {
        if (postvars.recording == '') {
            alert(arrLang_main['MSG_ERROR_RECORDING']);
            return null;
        }
        if (! /^\d+$/.test(postvars.call_to)) {
            alert(arrLang_main['MSG_ERROR_CALLTO']);
            return null;
        }
    }

    return $.post(url, postvars);
}

function updateTTSCharCount()
{
    var count = $('textarea[name=tts]').val().length;
    var available = 140 - count;
    if (available < 0){
        $('.counter').addClass("countExceeded");
    } else {
        $('.counter').removeClass("countExceeded");
    }
    $('.counter').text(available);
}

function rgb2hex(rgb)
{
    if (rgb.indexOf('#') == 0) return rgb;
    var m = /^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/.exec(rgb);
    return '#' + ('000000' + ((parseInt(m[1]) * 256 + parseInt(m[2])) * 256 + parseInt(m[3])).toString(16)).substr(-6);
}

function datehhmm(s)
{
    var m = /^(\d+)-(\d+)-(\d+) (\d+):(\d+)$/.exec(s);
    return new Date(m[1], parseInt(m[2]) - 1, m[3], m[4], m[5]).toISOString();
}

function split(val) { return val.split(/,\s*/); }
function extractLast(term) { return split(term).pop(); }

function formatdate(d)
{
	// Date.print es provisto por jsCalendar en proceso de ser reemplazado
	if (typeof d.print != 'undefined')
		return d.print('%Y-%m-%d %H:%M');
	else
		return $.datepicker.formatDate('yy-mm-dd', d) + ' ' +
			$.datepicker.formatTime('HH:mm', {hour: d.getHours(), minute: d.getMinutes()});
}