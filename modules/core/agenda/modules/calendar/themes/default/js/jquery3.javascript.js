var module_name = "calendar";

$(document).ready(function(){
    $('#divReminder').corner();
    $('#divNotification').corner();
    textarea();
    $('#colorSelector').ColorPicker({
        color: '#3366CC',
        onShow: function (colpkr) {
            $(colpkr).fadeIn(500);
            return false;
        },
        onHide: function (colpkr) {
            $(colpkr).fadeOut(500);
            return false;
        },
        //onSubmit: function(hsb, hex, rgb, el) {
        onSubmit: function(hsb, hex, rgb, el) {
            $(el).ColorPickerHide();
        },
        onChange: function (hsb, hex, rgb) {
            $('#colorSelector div').css('backgroundColor', '#' + hex);
            $('#colorHex').val('#' + hex);
        }
    });

    $('#CheckBoxRemi').change(function(){
        var estado;
        if($(this).is(":checked")){
            $(this).next("label").addClass("LabelSelected");
            var checkChange = document.getElementsByName('chkoldreminder')[0];
            checkChange.setAttribute("checked","checked");
            estado = $('#reminder').val("on");
            $('.remin').attr("style","visibility: visible;");
        }else{
            $(this).next("label").removeClass("LabelSelected");
            var checkChange = document.getElementsByName('chkoldreminder')[0];
            RemoveAttributeCheck(checkChange);
            estado = $('#reminder').val("off");
            $('.remin').attr("style","display: none;");
        }
    });
    $('#CheckBoxNoti').change(function(){
        var estado;
        if($(this).is(":checked")){
            $(this).next("label").addClass("LabelSelected");
            var checkChange = document.getElementsByName('chkoldnotification')[0];
            checkChange.setAttribute("checked","checked");
            estado = $('#notification').val("on");
            $('#notification_email').show();
            $('#grilla').attr("style","visibility: visible;");
        }else{
            $(this).next("label").removeClass("LabelSelected");
            var checkChange = document.getElementsByName('chkoldnotification')[0];
            RemoveAttributeCheck(checkChange);
            estado = $('#notification').val("off");
            $('#notification_email').hide();
            $('#grilla').attr("style","visibility: collapse;");
        }
    });

    $('#formNewEvent').submit(
        function(){
            //the event'name is filled
            if(!getStatusEvent()){
                connectJSON("error_eventName");
                return false;
            }
            //date1 <= date2
            if(getDatesValid()){
                connectJSON("error_date");
                return false;
            }

            if(getStatusReminder()){ // si en on => true
                if(!existRecording()){
                    connectJSON("error_recording");
                    return false;
                }
                if(!validCallsTo()){
                    connectJSON("call_to_error");
                    return false;
                }
            }
            //es valido el contenido de notification_email
            if(getStatusNotification()){
                var result = obtainEmails();
                if(result == false){
                    connectJSON("error_notification_emails");
                    return false;
                }
                if(result == "error_email"){
                    connectJSON("email_no_valid");
                    return false;
                }
            }
        $("#box").hide();
        var urlImaLoading = "<h1><img src='modules/"+module_name+"/images/busy.gif' /> "+$('#lblSending').val()+"...</h1>";
        $.blockUI({ message: urlImaLoading });
        return true;
    });
    
    $('.close_box, .cancel').click(function(){
        $('#box').hide();
        $('#title_box').html("");
        $('#tags').val("");
        $('#emails').val("");
        $('#colorSelector').ColorPickerSetColor('#3366CC');
        $('#colorHex').val('#3366CC');
        $('#colorSelector div').css('backgroundColor', '#3366CC');
    });

    $('#edit').click(function(){
        $('#new_box').attr("style","display:none;");
        $('#edit_box').attr("style","display:block;");
        $('#view_box').attr("style","display:none;");
        $('#email_to').attr("style","visibility:visible;");
        $('.del_contact').attr("style","visibility:visible;");
        $('#divReminder').show('slow');
        $('#divNotification').show('slow');
        $('#notification_email').hide();
        $('#lblCheckBoxNoti').attr("for","CheckBoxNoti");
        $('#lblCheckBoxRemi').attr("for","CheckBoxRemi");
        var title_box = $('#lblEdit').val();
        $('#title_box').html(title_box);
        var estado = $('#notification').val();
        if(estado == "on"){
            $('#notification_email').show();
        }
        var event_name        = document.getElementById('event');
        var description_event = document.getElementsByName('description')[0];
        var date_ini          = document.getElementById('f-calendar-field-1');
        var date_end          = document.getElementById('f-calendar-field-2');
        var tts               = document.getElementsByName('tts')[0];
        var inputCallTo       = document.getElementById('call_to');
        var chkoldnoti        = document.getElementsByName('chkoldnotification')[0];
        var id_event_input    = document.getElementById('id_event');
        var uid               = document.getElementById('id');

        $('#ReminderTime').attr("disabled","disabled");
        $('#add_phone').attr("style","display: inline;");
        RemoveAttributeDisable(event_name);
        RemoveAttributeDisable(description_event);
        RemoveAttributeDisable(date_ini);
        RemoveAttributeDisable(date_end);
        RemoveAttributeDisable(tts);
        RemoveAttributeDisable(inputCallTo);
        RemoveAttributeDisable(chkoldnoti);
        $('#ReminderTime').removeAttr("disabled");
        if(inputCallTo.value == ""){
            getNumExtesion();
        }
    });

    $('#delete').click(function(){ //hace un submit sin pasar por el submit validador
        var id_event = $('#id_event').val();
	$("#box").hide();
        var urlImaLoading = "<h1><img src='modules/"+module_name+"/images/busy.gif' /> "+$('#lblDeleting').val()+"...</h1>";
        $.blockUI({ message: urlImaLoading });
        var order = "menu="+module_name+"&action=delete_box&id_event="+id_event+"&rawmode=yes";
        $.post("index.php", order,
                function(theResponse){
                    $.unblockUI();
                    var message = JSONtoString(theResponse);
                    var error = message['error_delete_JSON'];
                    var status_error = message['error_delete_status'];
                    if(status_error == "on"){
                        //then close box
                        $('#box').hide();
                        $('#title_box').html("");
                        document.formNewEvent.submit();
                        alert(error);
                    }else{
			$('#box').show();
                        alert(error);
                    }
        });
    });

    $('#box').draggable({handle: 'tr.titleBox'}); //, opacity: 0.7

    $("#datepicker").datepicker({
        firstDay: 1,
        //showOtherMonths: true,
        //yearRange: '2010:2099',
        changeYear: true,
        changeMonth: true,
        showButtonPanel: false, //today buttom
        onChangeMonthYear: function(year, month, inst){
            $('#calendar').fullCalendar('gotoDate', year, month-1);
        },
        onSelect: function(dateText, inst){
            //// dateText mm/dd/yyyy
            //// año , mes[0,1,2,3...11], day
            var date = dateText.split("/",3);
            $('#calendar').fullCalendar( 'gotoDate', date[2], date[0] - 1, date[1]);
            $('#calendar').fullCalendar('changeView', 'agendaDay');
        }
    });

    $( "#tags" )
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function(request, response){
                $.ajax({
                    url: 'index.php?menu='+module_name+'&action=get_contacts2&rawmode=yes',
                    dataType: "json",
                    data: {
                        name_startsWith: extractLast( request.term )
                    },
                    success: function( data ) {
                        response( $.map( data, function( item ) {
                            return {
                                label: replaceTagHtml(item.caption),
                                value: item.value
                            }
                        }));
                    }
                });
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                //terms = entityToHtml(terms);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.label );
                // add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join( ", " );
                //$('#emails').val(this.value);
                return false;
            }
        });

    $('#add_news').hover(
        function () {
            $('#btnNewEvent').addClass("ui-state-hover");
        },
        function () {
            $('#btnNewEvent').removeClass("ui-state-hover");
        }
    );

    $('textarea[name=tts]').keyup(function(){
        var count = $(this).val().length;
        var available = 140 - count;
        if(available < 0){
            $('.counter').addClass("countExceeded");
        }else{
            $('.counter').removeClass("countExceeded");
        }
        $('.counter').text(available);
    });

    $('textarea[name=tts]').change(function(){
        var count = $(this).val().length;
        var available = 140 - count;
        if(available < 0){
            $('.counter').addClass("countExceeded");
        }else{
            $('.counter').removeClass("countExceeded");
        }
        $('.counter').text(available);
    });

    $('#listenTTS').click(function(){
        var number = $('#call_to').val();
        var tts    = $('textarea[name=tts]').val();
        var order  = "action=getTextToSpeach&call_to="+number+"&tts="+tts+"&rawmode=yes";
        if(isInteger(number) && number != ""){
            if(tts != ""){
                $.post("index.php", order,function(){
                    //var message = JSONtoString(theResponse); 
                });
            }else{
                connectJSON("error_recording");
            }
        }else{
            connectJSON("call_to_error");
        }
    });

    var id_event = $('#id').val();
    if(id_event != ""){
	openBoxById(id_event);
    }
});


    function textarea(){
        var maximos = new Array ();
        $('textarea[name=tts]').attr("maxlength", function (i) {
            if (maximos[i] = this.getAttribute('maxlength')) {
                $(this).keypress(function(event) {
                    return ((event.which == 8) ||(event.which == 9) || (this.value.length < maximos[i]));// 8 == borrar, 9 == tabulador
                })
            }
        });
    }

    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

    function popup_phone_number(url_popup){
        var ancho = 600;
        var alto = 400;
        my_window = window.open(url_popup,"my_window","width="+ancho+",height="+alto+",location=yes,status=yes,resizable=yes,scrollbars=yes,fullscreen=no,toolbar=yes");
        my_window.moveTo((screen.width-ancho)/2,(screen.height-alto)/2);
        my_window.document.close();
    }

    function return_phone_number(number, type, id)
    {
        window.opener.document.getElementById("call_to").value = number;
        window.opener.document.getElementById("phone_type").value = type;
        window.opener.document.getElementById("phone_id").value = id;
        window.close();
    }
        // true => email_no_valid   false => empty field
    function obtainEmails(){
        //format ("name" <dd@ema.com>, "name2" <ff@ema.com>, )
        var cad = $("#tags").val();
        var total_emails = quitSimbols(cad);
        var email = "";
        var error = "error_email";
        if(total_emails==true)
            return error;
        total_emails = total_emails + obtainTablesEmails();
        //obtain emails by table_emails
        if(total_emails=="")    return false;
        $("#emails").val(total_emails);
        return true;
    }

    function existRecording(){
        var recording = document.getElementsByName("tts")[0];
        if(recording.value != "")
            return true;
        else 
            return false;
    }

    // this function remove the simbols < or > and return only email
    function quitSimbols(cad){
        var arr = cad.split(",");
        var mail = "";
        var i = 0;
        var j = 0;
        var k = 0;
        var str = "";
        var strTmp = "";
        for(var k=0; k<arr.length; k++){
            mail = trim(arr[k]);
            i = mail.indexOf("<");
            j = mail.indexOf(">");
            email   = mail.substring(i+1,j);
            names   = mail.substring(0,i-1);
            strTmp  = "\""+trim(names)+"\" "+"&lt;"+trim(email)+"&gt;, ";
            if(mail != ""){
                if(names == "" && email != ""){
                    if(validarEmail(email))
                        str += "&lt;"+email+"&gt;, ";
                    else
                        return true;
                }else if(names == "" && email == ""){
                        if(validarEmail(mail))
                            str += "&lt;"+mail+"&gt;, ";
                        else   return true;
                }else if(validarEmail(email)){
                            str += strTmp;
                        }else return true;
            }

        }
        //cad = cad.replace(/</gi, "&lt;");
        //cad = cad.replace(/>/gi, "&gt;");
        str = str.replace(/\n/gi, "");
        return str;
    }
    // replace &lt and &gt by <  >
    function replaceTagHtml(cad){
        cad = cad.replace(/&lt;&gt;/gi, "");
        cad = cad.replace(/&lt;/gi, "<");
        cad = cad.replace(/&gt;/gi, ">");
        return cad;
    }

    function validarEmail(valor) {
        if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(valor)){
            return true; //valido
        } else {
            return false; //no valido
        }
    }

    function trim(stringToTrim) {
        return stringToTrim.replace(/^\s+|\s+$/g,"");
    }


    // get status of notification checkbox
    function getStatusNotification(){
        var id = document.getElementById('notification');
        var text_value = id.value;
        if(text_value == "on")
            return true;
        else
            return false;
    }

    // get status of call me checkbox
    function getStatusCallsNotification(){
        var id = document.getElementById('asterisk_call_me');
        var text_value = id.value;
        if(text_value == "on")
            return true;
        else
            return false;
    }

    // get status of call me checkbox
    function getStatusReminder(){
        var id = document.getElementById('reminder');
        var text_value = id.value;
        if(text_value == "on")
            return true;
        else
            return false;
    }

    function getStatusEvent(){
        var id = document.getElementById('event');
        var text_value = id.value;
        if(text_value != "")
            return true;
        else
            return false;
    }

    function isCorrectTime(){
        var date1 = document.getElementById('f-calendar-field-1').value;
        var date2 = document.getElementById('f-calendar-field-2').value;
        strDate1 = new Date(date1);
        strDate2 = new Date(date2);

        var starttime = strDate1.getHours()+":"+strDate1.getMinutes();
        var endtime   = strDate2.getHours()+":"+strDate2.getMinutes();
        if(endtime > starttime)
            return true;
        else 
            return false;
    }

    function getDatesValid(){
        var date1 = document.getElementById('f-calendar-field-1').value;
        var date2 = document.getElementById('f-calendar-field-2').value;
        strDate1 = new Date(date1);
        strDate2 = new Date(date2);
        if(strDate1 >= strDate2)
            return true;
        else
            return false;
    }

    function getStatusDescription(){
        var id = document.getElementsByName('description')[0];
        var text_value = id.value;
        if(text_value != "") return true;
        else    return false;
    }

    // valid number for asterisk_calls
    function validCallsTo(){
        var id = document.getElementById('call_to');
        var titles = document.getElementById('label_call');
        titles.innerHTML = "";
        var text_value = id.value;
        if(text_value == "") return false;
        if(isInteger(text_value))   return true;
        else    return false;
    }

    function isInteger(s)
    {   var i;
        for (i = 0; i < s.length; i++)
        {
            // Check that current character is number.
            var c = s.charAt(i);
            if (((c < "0") || (c > "9"))) return false;
        }
        // All characters are numbers.
        return true;
    }

    // implement JSON.stringify serialization
    function StringtoJSON(obj) {
        var t = typeof (obj);
        if (t != "object" || obj === null) {
            // simple data type
            if (t == "string") obj = '"'+obj+'"';
            return String(obj);
        }
        else {
            // recurse array or object  
            var n, v, json = [], arr = (obj && obj.constructor == Array);  
            for (n in obj) {  
                v = obj[n]; t = typeof(v);  
                if (t == "string") v = '"'+v+'"';  
                else if (t == "object" && v !== null) v = JSON.stringify(v);
                    json.push((arr ? "" : '"' + n + '":') + String(v));  
            }
            return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
        }
    }

    // implement JSON.parse de-serialization  
    function JSONtoString(str) {
        if (str === "") str = '""';
        eval("var p=" + str + ";");
        return p;
    }

    // uso de JSON para obtener el arreglo lang.php
    function connectJSON(mensaje_error) {
        var order = 'menu='+module_name+'&action=get_lang&rawmode=yes';
        var message = "";
        $.post("index.php", order,
                function(theResponse){
                    message = JSONtoString(theResponse);
                    alert(message[mensaje_error]);
            });
    }

    function verifyNumExtesion() {
        var id    = document.getElementById("id").value;
        var order = 'menu='+module_name+'&action=get_num_ext&userid='+id+'&rawmode=yes';
        var message = "";
        $.post("index.php", order,
                function(theResponse){
                    message = JSONtoString(theResponse);
                    var ext = message['ext'];
                    var titles = document.getElementById('label_call');
                    var call_to = document.getElementById('call_to');
                    if(ext == "empty")
                        titles.innerHTML = message['error_ext'];
                    else{
                        titles.innerHTML = "";
                        call_to.value = ext;
                    }
            });
    }

    function getNumExtesion() {
        var order = 'menu='+module_name+'&action=get_num_ext&rawmode=yes';
        var message = "";
        $.post("index.php", order,
                function(theResponse){
                    message = JSONtoString(theResponse);
                    var ext = message['ext'];
                    var call_to = document.getElementById('call_to');
                    if(ext == "empty")
                        call_to.value = "";
                    else{
                        call_to.value = ext;
                    }
            });
    }
    
    // view box detail event
    function getDataAjaxForm(order, e){
        // blocking screen
        var urlImaLoading = "<h1><img src='modules/"+module_name+"/images/busy.gif' /> "+$('#lblLoading').val()+"...</h1>";
        $.blockUI({ message: urlImaLoading });
        var eje_x = ((screen.width)/2) - 250;
	var eje_y = e.pageY;
	if(eje_y < 300){
	     eje_y = eje_y; 
	}else{
	     eje_y = eje_y - 50;
	}
	//$('#box').css('position','absolute');
        $('#box').css('top',eje_y+"px");
        $('#box').css('left',eje_x+"px");
        //alert(order);
        $('#new_box').attr("style","display:none;");
        $('#edit_box').attr("style","display:none;");
        $('#view_box').attr("style","display:none;");
        $('.remin').attr("style","display:none;");
        $('.noti_email').attr("style","display:none;");
        $('#notification_email').hide();
        $('#title_box').html("");
        $('#box').hide();
        $('#lblCheckBoxNoti').attr("for","CheckBoxNoti1");
        $('#lblCheckBoxRemi').attr("for","CheckBoxRemi1");
        $('.counter').text("140");
        $('textarea[name=tts]').val("");
////// to remove checkbox status in reminder call or notification /////////////////
        RemoveAttributeImageCheck();
///////////////////////////////////////////////////////////////////////////////////
        $.post("index.php", order,
                function(theResponse){
                    var content           = $('#table_box');
                    var box               = $('#box');
                    var message           = JSONtoString(theResponse);          //response JSON to array
                    var tts_msg           = message['recording'];                //recording name
                    var event             = message['event'];                     //name's event
                    var desc_event        = message['description'];          //description's event
                    var start             = message['date'];                      //start date event
                    var end               = message['to'];                          //end date event
                    var title_box         = message['title'];                 //title box(view event,edit event)
                    var notificacion      = message['notification'];       //notification (on, off)
                    var email_noti        = message['emails_notification'];  //emails to notify
                    var visibility_noti   = message['visibility'];      //visible or not emails_notification
                    var visibility_rep    = message['visibility_repeat'];//visible or not days_repeat
					var reminderTimer     = message['reminderTimer']; //reminderTimer
                    var color             = message['color'];
             /***********************      var by DOM      **************************/
                    var title_evt         = document.getElementById('title_box');
                    var event_name        = document.getElementById('event');
                    var description_event = document.getElementsByName('description')[0];
                    var date_ini          = document.getElementById('f-calendar-field-1');
                    var date_end          = document.getElementById('f-calendar-field-2');
                    var tts               = document.getElementsByName('tts')[0];
                    var inputCallTo       = document.getElementById('call_to');
                    var chkoldnoti        = document.getElementsByName('chkoldnotification')[0];
                    var chkolremin        = document.getElementsByName('chkoldreminder')[0];
                    var inputNotification = document.getElementById('notification');
                    var id                = document.getElementById('id');
                    var id_event_input    = document.getElementById('id_event');
                    var email_to          = document.getElementById('email_to');
                    var tabla_grilla      = document.getElementById('grilla');
             /**********************************************************************/

                    if(title_box == "View Event"){
                        var i = 0; //cont
                        //show buttons for view even
                        $('#view_box').attr("style","display:block;");

                        /*Set Color*/
                        $('#colorSelector').ColorPickerSetColor(color);
                        $('#colorHex').val(color);
                        $('#colorSelector div').css('backgroundColor', color);
                        /*end set Color*/

                        //disabled all input and select
                        event_name.setAttribute("disabled","disabled");
                        description_event.setAttribute("disabled","disabled");
                        date_ini.setAttribute("disabled","disabled");
                        date_end.setAttribute("disabled","disabled");
                        tts.setAttribute("disabled","disabled");
                        chkoldnoti.setAttribute("disabled","disabled");
                        inputCallTo.setAttribute("disabled","disabled");

                        // add title
                        $('#title_box').text(message['View Event']);
                        $('#desc').show();
                        //fill event name
                        event_name.value = event;

                        //fill event description
                        description_event.value = desc_event;

                        //fill date init event
                        date_ini.value = start;

                        //fill date end event
                        date_end.value = end;

                        $('#ReminderTime').children().each(function(){
                            var tmpRem = $(this).val();
                            if(reminderTimer == tmpRem)
                                $(this).attr("selected","selected");
                            else
                                $(this).removeAttr("selected");
                        });
                        $('#ReminderTime').attr("disabled","disabled");
                        RemoveAttributeCheck(chkoldnoti);

                        //fill email_to
                        $('#notification_email').hide();
                        $('#email_to').attr("style","visibility:visible;");
                        // fill tr and td in table contacts email with DOM
                        var size_emails = message['size_emails'];
                        var src_img_delete = "modules/"+module_name+"/images/delete.png";
                        $('#grilla').html("");
                        // fill labels to table emails
                        // create tr and td for title table emails and textnodes
                        if(message['notification_status'] == "on"){
                            var tr_titles             = document.createElement("tr");
                            var td_spaces1            = document.createElement("td");
                            var td_spaces2            = document.createElement("td");
                            var td_contact_title      = document.createElement("td");
                            var td_email_title        = document.createElement("td");
                            var td_contact_title_text = document.createTextNode(message['Contact']);
                            var td_email_title_text   = document.createTextNode(message['Email'])

                            // set attributes
                            tr_titles.setAttribute("class","letra12");
                            td_contact_title.setAttribute("style","color:#666666; font-weight:bold;font-size:12px;");
                            td_contact_title.setAttribute("align","center");
                            td_email_title.setAttribute("style","color:#666666; font-weight:bold;font-size:12px;");
                            td_email_title.setAttribute("align","center");
 
                            // append tds, trs, textnodes
                            td_email_title.appendChild(td_email_title_text);
                            td_contact_title.appendChild(td_contact_title_text);
                            tr_titles.appendChild(td_spaces1);
                            tr_titles.appendChild(td_contact_title);
                            tr_titles.appendChild(td_email_title);
                            tr_titles.appendChild(td_spaces2);
                            tabla_grilla.appendChild(tr_titles);

                            for(i = 0; i<size_emails; i++){
                                //create tr and tds
                                var tr_email   = document.createElement("tr");
                                var td_num     = document.createElement("td");
                                var td_contact = document.createElement("td");
                                var td_email   = document.createElement("td");
                                var td_delete  = document.createElement("td");
                                //create <a> for link delete
                                var a_delete   = document.createElement("a");
                                //create <img> for link delete 
                                var img_delete = document.createElement("img");
                                //create textnode &nbsp;&nbsp;&nbsp;&nbsp;
                                var spaces = document.createTextNode(" ");

                                // obtain emails var
                                var num_email  = "num_email" + i;
                                var cont_email = "cont_email" + i;
                                var name_email = "name_email" + i;

                                // set attributes to tr_email
                                tr_email.setAttribute("class","letra12");
                                // set attributes to td_num
                                td_num.setAttribute("align","center");
                                td_contact.setAttribute("align","center");
                                td_email.setAttribute("align","center");
                                td_delete.setAttribute("align","center");
                                td_delete.setAttribute("style","visibility:hidden;");
                                td_delete.setAttribute("class","del_contact");
                                // set attributes to <a>
                                a_delete.setAttribute("class","delete_email");
                                // set attributes to <img>
                                img_delete.setAttribute("src",src_img_delete);
                                img_delete.setAttribute("align","absmiddle");
                                img_delete.setAttribute("onclick","del_email_tab("+i+");");

                                // create textnode num, contact, email
                                var td_num_text = document.createTextNode(message[num_email]);
                                var td_contact_text = document.createTextNode(message[cont_email]);
                                var td_email_text = document.createTextNode(message[name_email]);

                                // append textnodes num, contact, email, a, img
                                td_num.appendChild(td_num_text);
                                td_contact.appendChild(td_contact_text);
                                td_email.appendChild(td_email_text);
                                a_delete.appendChild(spaces);
                                a_delete.appendChild(img_delete);
                                td_delete.appendChild(a_delete);

                                //append td to tr
                                tr_email.appendChild(td_num);
                                tr_email.appendChild(td_contact);
                                tr_email.appendChild(td_email);
                                tr_email.appendChild(td_delete);
                                tabla_grilla.appendChild(tr_email);
                            }
                        }

                        // fill checkbox my extension
                        if(message['call_to'] != ""){ //asterisk_call_me
                            $('#reminder').val('on');
                            tts.value = tts_msg;
                            var count = tts_msg.length;
                            var available = 140 - count;
                            $('.counter').text(available);
                            chkolremin.setAttribute("checked","checked");
                            $('.remin').attr("style","visibility: visible;");
                            $('#CheckBoxRemi').attr('checked','checked');
                            $('#CheckBoxRemi').next("label").addClass("LabelSelected");
                        }

                        // fill input call_to
                        inputCallTo.value = message['call_to'];

                        // fill input uid hidden
                        id.value = message['uid'];

                        // fill input id hidden
                        id_event_input.value = message['id'];

                        // hide the messages
                        $('#add_phone').attr("style","display: none;");
                        //$('.new_box_rec').attr("style","display: none;");

                        // fill checkbox notification emails
                        if(message['notification_status'] == "on"){
                            chkoldnoti.setAttribute("checked","checked");
                            $('.noti_email').attr("style","visibility:visible;");
                            inputNotification.value = "on";
                            $('#CheckBoxNoti').attr('checked','checked');
                            $('#CheckBoxNoti').next("label").addClass("LabelSelected");
                            $('#grilla').attr("style","visibility:visible;");
                        }else{
                            inputNotification.value = "off";
                            $('.noti_email').attr("style","display:none;");
                            $('#select2').html("");
                            $('#CheckBoxNoti').removeAttr('checked');
                            $('#CheckBoxNoti').next("label").removeClass("LabelSelected");
                        }
                    }
                //unblocking
                $.unblockUI();
		$('#box').show();
            });
    }

    function displayNewEvent(e){
        //centrar box
        var eje_x = ((screen.width)/2) - 250;
        if(!e) var e = window.event;
        var coordX = e.pageX;
        var coordY = e.pageY + 80;
        $('#box').css('top',coordY+"px");//'38%');
        $('#box').css('left',eje_x+"px");
	//$('#box').css('position','absolute');	

        $('#new_box').attr("style","display:none;");
        $('#edit_box').attr("style","display:none;");
        $('#view_box').attr("style","display:none;");
        $('.remin').attr("style","display:none;");
        $('#grilla').html("");
        $('#notification_email').hide();
        $('#title_box').html("");
        $('#divReminder').show();
        $('#divNotification').show();
        $('#lblCheckBoxNoti').attr("for","CheckBoxNoti");
        $('#lblCheckBoxRemi').attr("for","CheckBoxRemi");
        $('#desc').hide();
        $('.counter').text("140");
        $('textarea[name=tts]').val("");
        var order = "menu="+module_name+"&action=new_box&rawmode=yes";
////// to remove checkbox status in reminder call or notification /////////////////
        RemoveAttributeImageCheck();

        $.post("index.php", order,
                function(theResponse){
                    var content = $('#table_box');
                    var box = $('#box');
                    var message = JSONtoString(theResponse);          //response JSON to array


             /***********************      var by DOM      **************************/
                    var title_evt         = document.getElementById('title_box');
                    var event_name        = document.getElementById('event');
                    var description_event = document.getElementsByName('description')[0];
                    var date_ini          = document.getElementById('f-calendar-field-1');
                    var date_end          = document.getElementById('f-calendar-field-2');
                    var tts               = document.getElementsByName('tts')[0];
                    var call_to_event     = document.getElementById('call_to');
                    var inputCallTo       = document.getElementById('call_to');
                    var chkoldnoti        = document.getElementsByName('chkoldnotification')[0];
                    var inputNotification = document.getElementById('notification');
                    var id_event_input    = document.getElementById('id_event');
                    var uid               = document.getElementById('id');
                    var email_to          = document.getElementById('email_to');
             /**********************************************************************/

             /****************seteando variables ************************************/
					$('#ReminderTime').children().each(function(){
						var valueReminder = $(this).attr("value");
						if(valueReminder == "10"){
							$(this).attr("selected","selected");
						}else{
							$(this).removeAttr("selected");
						}
					});
                    $('#ReminderTime').removeAttr("disabled");
                    //show buttons for new event
                    $('#new_box').attr("style","display:block;");
                    $('#email_to').attr("style","visibility:hidden;");

                    //disabled all input and select
                    RemoveAttributeDisable(event_name);
                    RemoveAttributeDisable(description_event);
                    RemoveAttributeDisable(date_ini);
                    RemoveAttributeDisable(date_end);
                    RemoveAttributeDisable(tts);
                    RemoveAttributeDisable(inputCallTo);
                    RemoveAttributeDisable(chkoldnoti);
                    RemoveAttributeCheck(chkoldnoti);

                    // hide the sections email_to
                    $('#notification_email').hide();
                    $('#email_to').attr("style","display:none;");

                    // add title
                    $('#title_box').text(message['New_Event']);

                    event_name.value="";
                    description_event.value="";
                    date_ini.value = message['now'];
                    date_end.value = message['after'];
                    var ext = message['ext'];
                    if(ext == "empty")
                        $('#call_to').val("");
                    else{
                        $('#call_to').val(ext);
                    }

                    $('#add_phone').attr("style","display: inline;");
                    //$('.new_box_rec').attr("style","display: inline;");
                    inputNotification.value = "off";
                    $('#box').show();
            });
    }

    function RemoveAttributeSelect(selectObject){
        for(var j = 0; j<selectObject.childNodes.length; j++){
            selectObject.childNodes[j].removeAttribute('selected');
        }
    }

    function RemoveAttributeCheck(selectObject){
        selectObject.removeAttribute('checked');
    }

    function RemoveAttributeDisable(selectObject){
        selectObject.removeAttribute('disabled');
    }

    function RemoveAttributeImageCheck(){
        var chkoldnoti = document.getElementsByName('chkoldnotification')[0];
        var chkolremin = document.getElementsByName('chkoldreminder')[0];
        RemoveAttributeCheck(chkoldnoti);
        RemoveAttributeCheck(chkolremin);
        $('#reminder').val('off');
        $('#notification').val('off');
        $('.remin').attr("style","display: none;");
        $('#notification_email').hide();
        $('#CheckBoxNoti').removeAttr('checked');
        $('#CheckBoxRemi').removeAttr('checked');
        $('#CheckBoxNoti').next("label").removeClass("LabelSelected");
        $('#CheckBoxRemi').next("label").removeClass("LabelSelected");
    }

    function obtainTablesEmails(){
        //("eduardo cueva" <ecueva@palosanto.com>, <edu19432@hotmail.com>, )
        var table = document.getElementById('grilla');
        var add_text = "";
        for(var i=0; i<table.childNodes.length; i++){
            if(i>0){
                var contact = table.childNodes[i].childNodes[1].firstChild.nodeValue;
                var email = table.childNodes[i].childNodes[2].firstChild.nodeValue;
                if(contact == "-"){
                    add_text += "<"+email+">, ";
                }else{
                    add_text += "\""+contact+"\" "+"<"+email+">, ";
                }
            }
        }
        return add_text;
    }

    function del_email_tab(ind){
        ind++;
        var before_td = 0;
        var band = 0;
        var img = "";
        var on_click_value = 0;
        var table = document.getElementById("grilla");
        for(var i=0; i<table.childNodes.length; i++){
            if(i>0){
                var tr = table.childNodes[i];
                var id = table.childNodes[i].firstChild.firstChild.nodeValue;

                if(id == ind){
                    band = 1;
                    table.removeChild(tr);
                }

            }
        }

        for(var i=0; i<table.childNodes.length; i++){
            if(i>0){
                table.childNodes[i].firstChild.firstChild.nodeValue = i;
                var del_i = i - 1;
                var img2 = table.childNodes[i].childNodes[3].firstChild.childNodes[1];
                img2.setAttribute("onclick","del_email_tab("+del_i+");");
            }
        }
    }

    function openBoxById(id_event){
	if(id_event != ""){
	    var urlImaLoading = "<h1><img src='modules/"+module_name+"/images/busy.gif' /> "+$('#lblLoading').val()+"...</h1>";
	    $.blockUI({ message: urlImaLoading });
	    var eje_x = ((screen.width)/2) - 250;
	    var eje_y = 38;

	    $('#box').css('top',eje_y+"%");
	    $('#box').css('left',eje_x+"px");
	    $('#new_box').attr("style","display:none;");
	    $('#edit_box').attr("style","display:none;");
	    $('#view_box').attr("style","display:none;");
	    $('.remin').attr("style","display:none;");
	    $('.noti_email').attr("style","display:none;");
	    $('#notification_email').hide();
	    $('#title_box').html("");
	    $('#box').hide();
	    $('#lblCheckBoxNoti').attr("for","CheckBoxNoti1");
	    $('#lblCheckBoxRemi').attr("for","CheckBoxRemi1");
	    $('.counter').text("140");
	    $('textarea[name=tts]').val("");
	    var order = "menu=calendar&action=view_box&rawmode=yes&id_event="+id_event;
    ////// to remove checkbox status in reminder call or notification /////////////////
	    RemoveAttributeImageCheck();
    ///////////////////////////////////////////////////////////////////////////////////
	    $.post("index.php", order,
		    function(theResponse){
			var content           = $('#table_box');
			var box               = $('#box');
			var message           = JSONtoString(theResponse);          //response JSON to array
			var tts_msg           = message['recording'];                //recording name
			var event             = message['event'];                     //name's event
			var desc_event        = message['description'];          //description's event
			var start             = message['date'];                      //start date event
			var end               = message['to'];                          //end date event
			var title_box         = message['title'];                 //title box(view event,edit event)
			var notificacion      = message['notification'];       //notification (on, off)
			var email_noti        = message['emails_notification'];  //emails to notify
			var visibility_noti   = message['visibility'];      //visible or not emails_notification
			var visibility_rep    = message['visibility_repeat'];//visible or not days_repeat
					    var reminderTimer     = message['reminderTimer']; //reminderTimer
			var color             = message['color'];
		/***********************      var by DOM      **************************/
			var title_evt         = document.getElementById('title_box');
			var event_name        = document.getElementById('event');
			var description_event = document.getElementsByName('description')[0];
			var date_ini          = document.getElementById('f-calendar-field-1');
			var date_end          = document.getElementById('f-calendar-field-2');
			var tts               = document.getElementsByName('tts')[0];
			var inputCallTo       = document.getElementById('call_to');
			var chkoldnoti        = document.getElementsByName('chkoldnotification')[0];
			var chkolremin        = document.getElementsByName('chkoldreminder')[0];
			var inputNotification = document.getElementById('notification');
			var id                = document.getElementById('id');
			var id_event_input    = document.getElementById('id_event');
			var email_to          = document.getElementById('email_to');
			var tabla_grilla      = document.getElementById('grilla');
		/**********************************************************************/

			if(title_box == "View Event"){
			    var i = 0; //cont
			    //show buttons for view even
			    $('#view_box').attr("style","display:block;");

			    /*Set Color*/
			    $('#colorSelector').ColorPickerSetColor(color);
			    $('#colorHex').val(color);
			    $('#colorSelector div').css('backgroundColor', color);
			    /*end set Color*/

			    //disabled all input and select
			    event_name.setAttribute("disabled","disabled");
			    description_event.setAttribute("disabled","disabled");
			    date_ini.setAttribute("disabled","disabled");
			    date_end.setAttribute("disabled","disabled");
			    tts.setAttribute("disabled","disabled");
			    chkoldnoti.setAttribute("disabled","disabled");
			    inputCallTo.setAttribute("disabled","disabled");

			    // add title
			    $('#title_box').text(message['View Event']);
			    $('#desc').show();
			    //fill event name
			    event_name.value = event;

			    //fill event description
			    description_event.value = desc_event;

			    //fill date init event
			    date_ini.value = start;

			    //fill date end event
			    date_end.value = end;

			    $('#ReminderTime').children().each(function(){
				var tmpRem = $(this).val();
				if(reminderTimer == tmpRem)
				    $(this).attr("selected","selected");
				else
				    $(this).removeAttr("selected");
			    });
			    $('#ReminderTime').attr("disabled","disabled");
			    RemoveAttributeCheck(chkoldnoti);

			    //fill email_to
			    $('#notification_email').hide();
			    $('#email_to').attr("style","visibility:visible;");
			    // fill tr and td in table contacts email with DOM
			    var size_emails = message['size_emails'];
			    var src_img_delete = "modules/"+module_name+"/images/delete.png";
			    $('#grilla').html("");
			    // fill labels to table emails
			    // create tr and td for title table emails and textnodes
			    if(message['notification_status'] == "on"){
				var tr_titles             = document.createElement("tr");
				var td_spaces1            = document.createElement("td");
				var td_spaces2            = document.createElement("td");
				var td_contact_title      = document.createElement("td");
				var td_email_title        = document.createElement("td");
				var td_contact_title_text = document.createTextNode(message['Contact']);
				var td_email_title_text   = document.createTextNode(message['Email'])

				// set attributes
				tr_titles.setAttribute("class","letra12");
				td_contact_title.setAttribute("style","color:#666666; font-weight:bold;font-size:12px;");
				td_contact_title.setAttribute("align","center");
				td_email_title.setAttribute("style","color:#666666; font-weight:bold;font-size:12px;");
				td_email_title.setAttribute("align","center");

				// append tds, trs, textnodes
				td_email_title.appendChild(td_email_title_text);
				td_contact_title.appendChild(td_contact_title_text);
				tr_titles.appendChild(td_spaces1);
				tr_titles.appendChild(td_contact_title);
				tr_titles.appendChild(td_email_title);
				tr_titles.appendChild(td_spaces2);
				tabla_grilla.appendChild(tr_titles);

				for(i = 0; i<size_emails; i++){
				    //create tr and tds
				    var tr_email   = document.createElement("tr");
				    var td_num     = document.createElement("td");
				    var td_contact = document.createElement("td");
				    var td_email   = document.createElement("td");
				    var td_delete  = document.createElement("td");
				    //create <a> for link delete
				    var a_delete   = document.createElement("a");
				    //create <img> for link delete
				    var img_delete = document.createElement("img");
				    //create textnode &nbsp;&nbsp;&nbsp;&nbsp;
				    var spaces = document.createTextNode(" ");

				    // obtain emails var
				    var num_email  = "num_email" + i;
				    var cont_email = "cont_email" + i;
				    var name_email = "name_email" + i;

				    // set attributes to tr_email
				    tr_email.setAttribute("class","letra12");
				    // set attributes to td_num
				    td_num.setAttribute("align","center");
				    td_contact.setAttribute("align","center");
				    td_email.setAttribute("align","center");
				    td_delete.setAttribute("align","center");
				    td_delete.setAttribute("style","visibility:hidden;");
				    td_delete.setAttribute("class","del_contact");
				    // set attributes to <a>
				    a_delete.setAttribute("class","delete_email");
				    // set attributes to <img>
				    img_delete.setAttribute("src",src_img_delete);
				    img_delete.setAttribute("align","absmiddle");
				    img_delete.setAttribute("onclick","del_email_tab("+i+");");

				    // create textnode num, contact, email
				    var td_num_text = document.createTextNode(message[num_email]);
				    var td_contact_text = document.createTextNode(message[cont_email]);
				    var td_email_text = document.createTextNode(message[name_email]);

				    // append textnodes num, contact, email, a, img
				    td_num.appendChild(td_num_text);
				    td_contact.appendChild(td_contact_text);
				    td_email.appendChild(td_email_text);
				    a_delete.appendChild(spaces);
				    a_delete.appendChild(img_delete);
				    td_delete.appendChild(a_delete);

				    //append td to tr
				    tr_email.appendChild(td_num);
				    tr_email.appendChild(td_contact);
				    tr_email.appendChild(td_email);
				    tr_email.appendChild(td_delete);
				    tabla_grilla.appendChild(tr_email);
				}
			    }

			    // fill checkbox my extension
			    if(message['call_to'] != ""){ //asterisk_call_me
				$('#reminder').val('on');
				tts.value = tts_msg;
				var count = tts_msg.length;
				var available = 140 - count;
				$('.counter').text(available);
				chkolremin.setAttribute("checked","checked");
				$('.remin').attr("style","visibility: visible;");
				$('#CheckBoxRemi').attr('checked','checked');
				$('#CheckBoxRemi').next("label").addClass("LabelSelected");
			    }

			    // fill input call_to
			    inputCallTo.value = message['call_to'];

			    // fill input uid hidden
			    id.value = message['uid'];

			    // fill input id hidden
			    id_event_input.value = message['id'];

			    // hide the messages
			    $('#add_phone').attr("style","display: none;");
			    //$('.new_box_rec').attr("style","display: none;");

			    // fill checkbox notification emails
			    if(message['notification_status'] == "on"){
				chkoldnoti.setAttribute("checked","checked");
				$('.noti_email').attr("style","visibility:visible;");
				inputNotification.value = "on";
				$('#CheckBoxNoti').attr('checked','checked');
				$('#CheckBoxNoti').next("label").addClass("LabelSelected");
				$('#grilla').attr("style","visibility:visible;");
			    }else{
				inputNotification.value = "off";
				$('.noti_email').attr("style","display:none;");
				$('#select2').html("");
				$('#CheckBoxNoti').removeAttr('checked');
				$('#CheckBoxNoti').next("label").removeClass("LabelSelected");
			    }
			}
		    //unblocking
		    $.unblockUI();
		    $('#box').show();
		});
	}
    }
