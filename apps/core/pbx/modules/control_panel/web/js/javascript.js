var arrTime = new Array();
var is_refreshing = false;

// IE6 e IE8 funcionan incorrectamente al anidar draggables
// http://bugs.jqueryui.com/ticket/4333
$.extend($.ui.draggable.prototype, (function (orig) {
	  return {
	    _mouseCapture: function (event) {
	      var result = orig.call(this, event);
	      if (result && $.browser.msie) event.stopPropagation();
	      return result;
	    }
	  };
})($.ui.draggable.prototype["_mouseCapture"]));

/////////////////////////////////////////////////////////////
////////////CODE FOR THE ACCORDION AND RESIZABLE/////////////
/////////////////////////////////////////////////////////////
$(function(){
    /* 
    * Apply the FAQ plug-in to jQuery object <dl>
    * Parameter 1: (optional): the index [integer] of a <dt> to open on load
    */
    $('#faq').faq();//parametro de 0 a 3 o ninguno para todos
    //$('#accordion').accordion();

    $("[id^=sortable-]").sortable({
        connectWith: ".sortable",
        receive: listChanged,
        opacity: 0.6, cursor: 'move'
    });

//     $(".state1").dblclick(switchLists);
//     $(".state2").dblclick(switchLists);

    function listChanged(e,ui) {
        ui.item.toggleClass("state1"); 
        ui.item.toggleClass("state2");
    }

    function switchLists(e) {
        // determine which list they are in
        // this works if you only have 2 related lists.
        // otherwise you will need to specify the target list
        // the other list is one that has the connect with property but isn't
        // the current target's parent
        var otherList = $($(e.currentTarget).parent().sortable("option","connectWith")).not($(e.currentTarget).parent());
    
        // if the current list has no items, add a hidden one to keep style in place
        // when saving you will need to filter out items that have
        // display set to none to accommodate this scenario
        if ($(e.currentTarget).siblings().length == 0) {
            $(e.currentTarget).clone().appendTo($(e.currentTarget).parent()).css("display","none");
        }
        otherList.append(e.currentTarget);
        otherList.children().removeClass($(e.currentTarget).attr("class"));
        otherList.children().addClass(otherList.children().attr("class"));
        
        // remove any hidden siblings perhaps left over
        otherList.children(":hidden").remove();
    }
    
    $('.left_side').resizable({
        autoHide: true,
        //maxWidth: 576,
        minWidth: 380,//372//560//576
        minHeight: 100,
        alsoResize: '#content',
        stop: function(event, ui) {
            var id = $(this).attr('id');
            var heightsize = $(this).height();
            var widthsize = $(this).width();
            var tmp = id.split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "saveresize";
                arrAction["rawmode"]   = "yes";
                arrAction["height"]    = heightsize;
                arrAction["width"]     = widthsize;
                arrAction["area"]      = tmp[1];
                arrAction["type"]      = "alsoResize";
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            reFresh();
                    }
                );
        }
    });

    $('.right_side').resizable({
        autoHide: true,
        minHeight: 100,
        //maxWidth: 394,
        minWidth: 380,//394
        alsoResize: '.areaDropSub',
        stop: function(event, ui) {
            var id = $(this).attr('id');
            var tmp = id.split("_");
            var heightsize = $(this).height();
            var widthsize = $(this).width();
            var arrAction              = new Array();
                arrAction["action"]    = "saveresize";
                arrAction["rawmode"]   = "yes";
                arrAction["height"]    = heightsize;
                arrAction["width"]     = widthsize;
                arrAction["area"]      = tmp[1];
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                          //  reFresh();
                    }
                );
        }
    });
});

function actualizar()
{
    if(!is_refreshing){
	is_refreshing = true;
	var arrAction              = new Array();
	    arrAction["action"]    = "refresh";
	    arrAction["rawmode"]   = "yes";
	    request("index.php?menu=control_panel",arrAction,true,
		function(arrData,statusResponse,error)
		{

			if(statusResponse == "CHANGED"){

			    for(key in arrData){
				for(key2 in arrData[key]["data"]){
				    if(key2 == "speak_time"){
					if(arrData[key]["data"][key2] != " "){
					    if(arrData[key]["Tipo"] == "extension")
						arrTime[arrData[key]["key"]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2]; //reloadSpeakTime(arrData[key]["key"],arrData[key]["data"][key2],1);
					    else if(arrData[key]["Tipo"] == "trunk"){
						var clave = arrData[key]["key"].split("_");
						arrTime[clave[0]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2]+"_"+clave[1];
					    }
					}
				    }
				    else if(key2 == "call_dstn")
					eventCallDstn(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "status")
					eventStatus(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "voicemail")
					eventVoicemail(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "state_call")
					eventStateCall(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "parties")
					eventParties(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "activity")
					arrTime[arrData[key]["key"]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2];//reloadActivity(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "statusConf")
					eventStatusConf(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "statusTrunk")
					eventStatusTrunk(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "waiting")
					eventWaiting(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "time")
					arrTime[arrData[key]["key"]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2];//reloadTimeParking(arrData[key]["key"],arrData[key]["data"][key2]);
				    else if(key2 == "extension")
					eventExtensionParking(arrData[key]["key"],arrData[key]["data"][key2]);
				}
			    }
			    //reloadDevices(arrData);
			}
		}
	    );
    }
}

function getAllData()
{
    var arrAction              = new Array();
        arrAction["action"]    = "getAllData";
        arrAction["rawmode"]   = "yes";
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                for(key in arrData){
                    for(key2 in arrData[key]["data"]){
                        if(key2 == "speak_time"){
                            if(arrData[key]["data"][key2] != " "){
                                if(arrData[key]["Tipo"] == "extension")
                                    arrTime[arrData[key]["key"]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2]; //reloadSpeakTime(arrData[key]["key"],arrData[key]["data"][key2],1);
                                else if(arrData[key]["Tipo"] == "trunk"){
                                    var clave = arrData[key]["key"].split("_");
                                    arrTime[clave[0]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2]+"_"+clave[1];
                                }
                            }
                        }
                        else if(key2 == "call_dstn")
                                eventCallDstn(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "status")
                                eventStatus(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "voicemail")
                                eventVoicemail(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "state_call")
                                eventStateCall(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "parties")
                                eventParties(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "activity")
                                arrTime[arrData[key]["key"]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2];//reloadActivity(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "statusConf")
                                eventStatusConf(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "statusTrunk")
                                eventStatusTrunk(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "waiting")
                                eventWaiting(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "time")
                                arrTime[arrData[key]["key"]] = arrData[key]["Tipo"]+"_"+arrData[key]["data"][key2];//reloadTimeParking(arrData[key]["key"],arrData[key]["data"][key2]);
                        else if(key2 == "extension")
                                eventExtensionParking(arrData[key]["key"],arrData[key]["data"][key2]);
                    }
                }
                actualizar();
                        //reloadDevices(arrData);
            }
        );
}

function getDataBox()
{
   
    var arrAction              = new Array();
        arrAction["action"]    = "loadBoxes";
        arrAction["rawmode"]   = "yes";
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                drawBoxExtension(arrData[0]);
                drawBoxArea1(arrData[1]);
                drawBoxArea2(arrData[2]);
                drawBoxArea3(arrData[3]);
                drawBoxQueues(arrData[4]);
                drawBoxTrunksDAHDI(arrData[5]);
                drawBoxTrunksSIP(arrData[6]);
                drawBoxConferences(arrData[7]);
                drawBoxParkinglot(arrData[8]);
		if(!is_refreshing)
		    setInterval("recorrerArrTime()",1000);
                getAllData();
            }
        );
}


function drawBoxGeneric(id,type_,title_,info,module_name,img_name,status_,droppable)
{
    var clase = (droppable)?"class = 'areaDrop'":"";
    if(status_ == "on")
        var clase2 = "class = 'box boxOn'";
    else
        var clase2 = "class = 'box boxOff'";
    var html = "<div "+clase+" id = 'dev_"+id+"'>"+
                 "<div "+clase2+" id='box_"+id+"' >"+
                    "<div class='box_info'>"+
                        "<a class='tooltipInfo' href='#'>"+
                            "<img class='info_img' src='modules/"+module_name+"/images/info.png' />"+
                             "<span>"+info+"&nbsp;</span>"+
                        "</a>"+
                    "</div>"+
                    "<div class='box_description'>"+
                        title_+"<br />"+
                        "<span class='monitor' id='span1_"+id+"'>&nbsp;&nbsp;</span>"+
                        "<span class='monitor' id='span2_"+id+"'>&nbsp;</span>"+
                    "</div>"+
                    "<div class='box_info' id='img1_"+id+"'>"+
                    "</div>"+
                    "<div class='box_img2'>"+
                        "<img id='img2_"+id+"' class='img_box' src='modules/"+module_name+"/images/"+img_name+"' />"+
                    "</div>"+
                "</div>";
     return html;
}

function drawAreaGeneric(arrData)
{
    var i=0;
    html = "";
    for(key in arrData["data"]){
        if(i%arrData["length"] == 0){
           html = html + "<td valign='top'>"+
                            "<table border ='0' cellspacing='0' cellpadding='0'>";
       }
       html = html +  "<tr> <td>" + drawBoxGeneric(arrData["data"][key]["id"],arrData["data"][key]["type"],arrData["data"][key]["title"],arrData["data"][key]["info"],arrData["data"][key]["module_name"],arrData["data"][key]["img_name"],arrData["data"][key]["status"],arrData["data"][key]["droppable"]) + "</td> </tr>";
       if((i+1)%arrData["length"] == 0 || arrData["data"].length == i+1){
                html = html + "</table>"+
                        "</td>";
       }
       i++; 
    }
    return html;
}

function drawBoxParkinglot(arrData)
{
    html = drawAreaGeneric(arrData);
    html = html + "<SCRIPT>"+
                    "$(document).ready(function(){"+
                        "$('.img_box').droppable({"+
                            "over: function(event, ui) {"+
                                //$(this).css('background-color', '#A3C1F9');//cambia color
                            "},"+
                            "out: function(event, ui) {"+
                                //$(this).css('background-color', null);
                            "},"+
                            "drop: function(event, ui) {"+
                                "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                                "var idFinish = ($(this).attr('id')).split('_');"+
                                "var arrAction              = new Array();"+
                                    "arrAction['action']    = 'call';"+
                                    "arrAction['rawmode']   = 'yes';"+
                                    "arrAction['extStart']  =  idStart[1];"+
                                    "arrAction['extFinish'] =  idFinish[1];"+
                                    "request('index.php?menu=control_panel',arrAction,false,"+
                                        "function(arrData,statusResponse,error)"+
                                        "{"+
                                            "$('#contentRight').html(arrData);"+
                                        "}"+
                                ");"+
                            "}"+
                        "});"+
                        "$('.img_box').dblclick(function(ev)"+
                        "{"+
                            "var extStart = ($(this).attr('id')).split('_');"+
                            "var arrAction              = new Array();"+
                                "arrAction['action']    = 'hangup';"+
                                "arrAction['rawmode']   = 'yes';"+
                                "arrAction['extStart']  =  extStart[1];"+
                                "request('index.php?menu=control_panel',arrAction,false,"+
                                "function(arrData,statusResponse,error)"+
                                "{"+
                                        "$('#contentRight').html(arrData);"+
                                "}"+
                                ");"+        
                        "});"+
                    "});"+
                    "</SCRIPT>";
    $("#img_Parkinglots").remove();
    $("#tableParkinglots").empty().append(html);

}

function drawBoxArea1(arrData)
{
    html = drawAreaGeneric(arrData);
    html = html + "<SCRIPT>"+
                    "$(document).ready(function(){"+
                        "$('.img_box').draggable({"+
                            "zIndex:     990,"+
                            "revert: true,"+
                            "cursor: 'crosshair',"+
                            "start: function(event, ui) {"+
                                //$(this).css('background-color','#ddddff');
                            "},"+
                            "out: function(event, ui) {"+
                                //$(this).css('background-color', null);
                            "},"+
                            "drag: function(event, ui) {"+
                            "}"+
                        "});"+
                    "});"+
                    "$('.box').draggable({"+
                        "zIndex:     989,"+
                        "revert: true,"+
                        "cursor: 'crosshair',"+
                        "start: function(event, ui) {"+
                            //$(this).css('background-color','#ddddff');
                        "}"+
                    "});"+
                    "$('.areaDrop').droppable({"+
                        "accept: '.box',"+//#lista_local
                        "drop: function(event, ui) {"+
                            "$(this).append($(ui.draggable));"+
                            "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                            "var idFinish = ($(this).attr('id')).split('_');"+
                            "var arrAction              = new Array();"+
                                "arrAction['action']    = 'savechange2';"+
                                "arrAction['rawmode']   = 'yes';"+
                                "arrAction['extStart']  =  idStart[1];"+
                                "arrAction['extFinish'] =  idFinish[1];"+
                                "request('index.php?menu=control_panel',arrAction,false,"+
                                    "function(arrData,statusResponse,error)"+
                                    "{"+
                                            "$('#contentRight').html(arrData);"+
                                    "}"+
                                ");"+
                        "}"+
                    "});"+
                    "$('.mail_box').droppable({"+
                        "over: function(event, ui)"+
                        "{"+
                            //$(this).css('background-color', '#F5F6BE');//cambia color
                        "},"+
                        "out: function(event, ui)"+
                        "{"+
                            //$(this).css('background-color', null);
                        "},"+
                        "drop: function(event, ui)"+
                        "{"+
                            "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                            //var idFinish = ($(this).attr("id")).split("_");
                            "var arrAction              = new Array();"+
                                "arrAction['action']    = 'voicemail';"+
                                "arrAction['rawmode']   = 'yes';"+
                                "arrAction['extStart']  =  idStart[1];"+
                                "request('index.php?menu=control_panel',arrAction,false,"+
                                    "function(arrData,statusResponse,error)"+
                                    "{"+
                                            "$('#contentRight').html(arrData);"+
                                    "}"+
                                ");"+
                        "}"+
                    "});"+
                "</SCRIPT>";
    if(document.getElementById("tableArea1")){
        $("#img_Area1").remove();
        $("#tableArea1").empty().append(html);
    }
}

function drawBoxArea2(arrData)
{
    html = drawAreaGeneric(arrData);
    html = html + "<SCRIPT>"+
                    "$(document).ready(function(){"+
                        "$('.img_box').draggable({"+
                            "zIndex:     990,"+
                            "revert: true,"+
                            "cursor: 'crosshair',"+
                            "start: function(event, ui) {"+
                                //$(this).css('background-color','#ddddff');
                            "},"+
                            "out: function(event, ui) {"+
                                //$(this).css('background-color', null);
                            "},"+
                            "drag: function(event, ui) {"+
                            "}"+
                        "});"+
                        "$('.box').draggable({"+
                        "zIndex:     989,"+
                        "revert: true,"+
                        "cursor: 'crosshair',"+
                        "start: function(event, ui) {"+
                            //$(this).css('background-color','#ddddff');
                        "}"+
                    "});"+
                    "$('.areaDrop').droppable({"+
                        "accept: '.box',"+//#lista_local
                        "drop: function(event, ui) {"+
                            "$(this).append($(ui.draggable));"+
                            "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                            "var idFinish = ($(this).attr('id')).split('_');"+
                            "var arrAction              = new Array();"+
                                "arrAction['action']    = 'savechange2';"+
                                "arrAction['rawmode']   = 'yes';"+
                                "arrAction['extStart']  =  idStart[1];"+
                                "arrAction['extFinish'] =  idFinish[1];"+
                                "request('index.php?menu=control_panel',arrAction,false,"+
                                    "function(arrData,statusResponse,error)"+
                                    "{"+
                                            "$('#contentRight').html(arrData);"+
                                    "}"+
                                ");"+
                        "}"+
                    "});"+
                    "$('.mail_box').droppable({"+
                        "over: function(event, ui)"+
                        "{"+
                            //$(this).css('background-color', '#F5F6BE');//cambia color
                        "},"+
                        "out: function(event, ui)"+
                        "{"+
                            //$(this).css('background-color', null);
                        "},"+
                        "drop: function(event, ui)"+
                        "{"+
                            "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                            //var idFinish = ($(this).attr("id")).split("_");
                            "var arrAction              = new Array();"+
                                "arrAction['action']    = 'voicemail';"+
                                "arrAction['rawmode']   = 'yes';"+
                                "arrAction['extStart']  =  idStart[1];"+
                                "request('index.php?menu=control_panel',arrAction,false,"+
                                    "function(arrData,statusResponse,error)"+
                                    "{"+
                                            "$('#contentRight').html(arrData);"+
                                    "}"+
                                ");"+
                        "}"+
                    "});"+
                    "});"+ 
                "</SCRIPT>";
    if(document.getElementById("tableArea2")){
        $("#img_Area2").remove();
        $("#tableArea2").empty().append(html);
    }
}

function drawBoxArea3(arrData)
{
        html = drawAreaGeneric(arrData);
        html = html + "<SCRIPT>"+
                        "$(document).ready(function(){"+
                            "$('.img_box').draggable({"+
                                "zIndex:     990,"+
                                "revert: true,"+
                                "cursor: 'crosshair',"+
                                "start: function(event, ui) {"+
                                    //$(this).css('background-color','#ddddff');
                                "},"+
                                "out: function(event, ui) {"+
                                    //$(this).css('background-color', null);
                                "},"+
                                "drag: function(event, ui) {"+
                                "}"+
                            "});"+
                            "$('.box').draggable({"+
                            "zIndex:     989,"+
                            "revert: true,"+
                            "cursor: 'crosshair',"+
                            "start: function(event, ui) {"+
                                //$(this).css('background-color','#ddddff');
                            "}"+
                        "});"+
                        "$('.areaDrop').droppable({"+
                            "accept: '.box',"+//#lista_local
                            "drop: function(event, ui) {"+
                                "$(this).append($(ui.draggable));"+
                                "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                                "var idFinish = ($(this).attr('id')).split('_');"+
                                "var arrAction              = new Array();"+
                                    "arrAction['action']    = 'savechange2';"+
                                    "arrAction['rawmode']   = 'yes';"+
                                    "arrAction['extStart']  =  idStart[1];"+
                                    "arrAction['extFinish'] =  idFinish[1];"+
                                    "request('index.php?menu=control_panel',arrAction,false,"+
                                        "function(arrData,statusResponse,error)"+
                                        "{"+
                                                "$('#contentRight').html(arrData);"+
                                        "}"+
                                    ");"+
                            "}"+
                        "});"+
                        "$('.boxDrop').droppable({"+
                            "accept: '.box',"+//#lista_local
                            "drop: function(event, ui) {"+
                                "$(this).append($(ui.draggable));"+
                                "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                                "var idArea = ($(this).attr('id')).split('_');"+
                                "var arrAction              = new Array();"+
                                    "arrAction['action']    = 'savechange';"+
                                    "arrAction['rawmode']   = 'yes';"+
                                    "arrAction['extStart']  =  idStart[1];"+
                                    "arrAction['area']      =  idArea[1];"+
                                    "request('index.php?menu=control_panel',arrAction,false,"+
                                        "function(arrData,statusResponse,error)"+
                                        "{"+
                                                "$('#contentRight').html(arrData);"+
                                        "}"+
                                    ");"+
                            "}"+
                        "});"+
                        "$('.mail_box').droppable({"+
                            "over: function(event, ui)"+
                            "{"+
                                //$(this).css('background-color', '#F5F6BE');//cambia color
                            "},"+
                            "out: function(event, ui)"+
                            "{"+
                                //$(this).css('background-color', null);
                            "},"+
                            "drop: function(event, ui)"+
                            "{"+
                                "var idStart = ($(ui.draggable).attr('id')).split('_');"+
                                //var idFinish = ($(this).attr("id")).split("_");
                                "var arrAction              = new Array();"+
                                    "arrAction['action']    = 'voicemail';"+
                                    "arrAction['rawmode']   = 'yes';"+
                                    "arrAction['extStart']  =  idStart[1];"+
                                    "request('index.php?menu=control_panel',arrAction,false,"+
                                        "function(arrData,statusResponse,error)"+
                                        "{"+
                                                "$('#contentRight').html(arrData);"+
                                        "}"+
                                    ");"+
                            "}"+
                        "});"+
                        "});"+
                    "</SCRIPT>";
        if(document.getElementById("tableArea3")){
            $("#img_Area3").remove();
            $("#tableArea3").empty().append(html);
        }
}

function drawBoxTrunksDAHDI(arrData, module_name)
{
    html = drawAreaGeneric(arrData);
    //document.getElementById("tableTrunks").innerHTML = html;
    $("#img_Trunks").remove();
    $("#tableTrunks").empty().append(html);
}

function drawBoxTrunksSIP(arrData)
{
    html = drawAreaGeneric(arrData);
    //document.getElementById("tableTrunksSIP").innerHTML = html;
    $("#img_TrunksSIP").remove();
    $("#tableTrunksSIP").empty().append(html);
}

function drawBoxConferences(arrData)
{
    html = drawAreaGeneric(arrData);
    $("#img_Conferences").remove();
    $("#tableConferences").empty().append(html);
}

function drawBoxQueues(arrData)
{
    html = drawAreaGeneric(arrData);
    $("#img_Queues").remove();
    $("#tableQueues").empty().append(html);
}

function recorrerArrTime()
{
    for(key in arrTime){
        var tmp = arrTime[key].split("_");
        var tipo = tmp[0];
        var time = tmp[1];
        if(tmp[2])
            var trunk = tmp[2];
        if(tipo == "extension"){
            eventSpeakTimeExten(key,time);
            var tmp2 = time.split(":");
            var seconds = parseInt(tmp2[2],10);
            var minutes = parseInt(tmp2[1],10);
            var hours = parseInt(tmp2[0],10);
            seconds++;
            if(seconds > 59){
                minutes++;
                seconds = 0;
            }
            if(minutes > 59){
                hours++;
                minutes = 0;
            }
            arrTime[key] = tipo+"_"+formatear_long2(hours)+":"+formatear_long2(minutes)+":"+formatear_long2(seconds);
        }else if(tipo == "trunk"){
            eventSpeakTimeTrunk(key+"_"+tmp[2],time);
            var tmp = time.split(":");
            var seconds = parseInt(tmp[2],10);
            var minutes = parseInt(tmp[1],10);
            var hours = parseInt(tmp[0],10);
            seconds++;
            if(seconds > 59){
                minutes++;
                seconds = 0;
            }
            if(minutes > 59){
                hours++;
                minutes = 0;
            }
            arrTime[key] = tipo+"_"+formatear_long2(hours)+":"+formatear_long2(minutes)+":"+formatear_long2(seconds)+"_"+trunk;
        }else if(tipo == "conference"){
            eventActivity(key,time);
            var tmp2 = time.split(":");
            var seconds = parseInt(tmp2[2],10);
            var minutes = parseInt(tmp2[1],10);
            var hours = parseInt(tmp2[0],10);
            seconds++;
            if(seconds > 59){
                minutes++;
                seconds = 0;
            }
            if(minutes > 59){
                hours++;
                minutes = 0;
            }
            arrTime[key] = tipo+"_"+formatear_long2(hours)+":"+formatear_long2(minutes)+":"+formatear_long2(seconds);
        }else if(tipo == "parkinglot"){
            eventTimeParking(key,time);
            if(time != " "){
                var tmp2 = time.split(":");
                var seconds = parseInt(tmp2[2],10);
                var minutes = parseInt(tmp2[1],10);
                var hours = parseInt(tmp2[0],10);
                seconds--;
                if(seconds < 0){
                    minutes--;
                    seconds = 59;
                }
                if(minutes < 0){
                    hours--;
                    minutes = 59;
                }
                arrTime[key] = tipo+"_"+formatear_long2(hours)+":"+formatear_long2(minutes)+":"+formatear_long2(seconds);
                if(hours == -1)
                    delete arrTime[key];
            }else
                delete arrTime[key];
        }
    }
}

function formatear_long2(val){
    var salida2=(val.toString().length==1)? "0"+val : val;
    return salida2;
}

function drawBoxExtension(arrData)
{
    html = drawAreaGeneric(arrData);
    $("#img_Extension").remove();
    $("#tableExtension").empty().append(html);
}

function eventExtensionParking(parkingLot, extension){
    var span = document.getElementById("span1_" + parkingLot);
    if(span){
        span.firstChild.nodeValue = extension;
        if(extension == " "){
            var span = document.getElementById("span2_" + parkingLot);
            span.firstChild.nodeValue = "";
        }
    }
}

function eventTimeParking(parkingLot, time){
    var span = document.getElementById("span2_" + parkingLot);
    if(span){
        span.firstChild.nodeValue = time;
    }
}

function eventWaiting(queue,waiting){
    var span = document.getElementById("span1_" + queue); 
    if(waiting!=0){
        if(span)
            span.firstChild.nodeValue = waiting;
    }else{
        if(span)
            span.firstChild.nodeValue = "";
    }
}

function eventStatusTrunk(trunk, statusTrunk){
    if(statusTrunk== "off"){
        var span1 = document.getElementById("span1_" + trunk);
        var span2 = document.getElementById("span2_" + trunk);
        span1.firstChild.nodeValue = "";
        span2.firstChild.nodeValue = "";
    }
}

function eventStatusConf(conference, statusConf){
    if(statusConf == "off"){
        delete arrTime[conference];
        var span1 = document.getElementById("span1_" + conference);
        var span2 = document.getElementById("span2_" + conference);
        span1.firstChild.nodeValue = "";
        span2.firstChild.nodeValue = "";
    }
}

function eventActivity(conference, activity){
    var span = document.getElementById("span2_" + conference);
    if(span){
        span.firstChild.nodeValue = activity;
    }
}

function eventParties(conference, parties){
     var span = document.getElementById("span1_" + conference);
     if(span){
        span.firstChild.nodeValue = parties;
    }
}

function eventStateCall(exten, state_call){
    var img = document.getElementById("img2_"+ exten);
    if(img){
        if(state_call=="Ringing"){
                img.setAttribute("src","modules/control_panel/images/phoneRinging.gif");
        }if(state_call=="Up"){
                img.setAttribute("src","modules/control_panel/images/icon_upPhone.png");
        }if(state_call=="Down"){
                delete arrTime[exten];
                var span1 = document.getElementById("span1_"+ exten);
                var span2 = document.getElementById("span2_"+ exten);
                span1.firstChild.nodeValue = "";
                span2.firstChild.nodeValue = "";
                img.setAttribute("src","modules/control_panel/images/phhonez0.png");
        }
    }
}


function eventVoicemail(exten, voicemail){ 
    var div = document.getElementById("img1_" + exten);
    var tmp = voicemail.split("_");
    if(tmp[0]=="1"){
        div.innerHTML = "<a class='Ntooltip' href='#'><img id='mail_"+exten+"' class='mail_box' src='modules/control_panel/images/mail.png'/><span>"+tmp[1]+"&nbsp;</span></a>";
    }else{
        div.innerHTML = "";
    }
}

function eventStatus(exten, status_){
     var div = document.getElementById("box_" + exten);
     if(div){
        if(status_ =='on'){
                div.setAttribute("class","box boxOn");
            }else{
                div.setAttribute("class","box boxOff");
            }
    }
}

function eventCallDstn(exten,call_dstn){
    var span = document.getElementById("span1_" + exten);
    if(span){
        span.firstChild.nodeValue = call_dstn;
    }
}

function eventSpeakTimeExten(exten,speak_time){
    var span = document.getElementById("span2_" + exten);
    if(span){
        span.firstChild.nodeValue = speak_time;
    }
}

function eventSpeakTimeTrunk(exten,speak_time){
    var tmp = exten.split("_");
    var span = document.getElementById("span2_" + tmp[0]);
    if(span){
        span.firstChild.nodeValue = speak_time;
        var spanTrun1 = document.getElementById("span1_" + tmp[1]);
        var spanTrun2 = document.getElementById("span2_" + tmp[1]);
        spanTrun1.innerHTML = tmp[0];
        spanTrun2.innerHTML = speak_time;
    }
}

function reloadDevices(arrRefresh){
     for(key in arrRefresh){
        var user = arrRefresh[key]["user"];
        var speak_time = arrRefresh[key]["speak_time"];
        var call_dstn = arrRefresh[key]["call_dstn"];
        var status_ = arrRefresh[key]["status"];
        var voicemail = arrRefresh[key]["voicemail"];
        var state_call = arrRefresh[key]["state_call"];
        var voicemail_cnt = arrRefresh[key]["voicemail_cnt"];
        var context = arrRefresh[key]["context"];
        var trunk = arrRefresh[key]["trunk"];
        var numconf = arrRefresh[key]["numconf"];
        var parties = arrRefresh[key]["parties"];
        var activity = arrRefresh[key]["activity"];

        var div = document.getElementById("ext_" + user);

        var subdiv = div.getElementsByTagName("div");
        var span = subdiv[1].getElementsByTagName("span");

        if(status_ =='on'){
            div.setAttribute("class","box boxOn");
        }else{
            div.setAttribute("class","box boxOff");
        }

        if(voicemail=="1"){
            var a = subdiv[2].getElementsByTagName("a");
            var img = a[0].getElementsByTagName("img");
            img[0].setAttribute("src","modules/control_panel/images/mail.png");
        }else{
            subdiv[2].innerHTML = "";
        }

        var img = subdiv[3].getElementsByTagName("img");

        if(state_call=="Ringing"){
            img[0].setAttribute("src","modules/control_panel/images/phoneRinging.gif");
        }if(state_call=="Up"){
            img[0].setAttribute("src","modules/control_panel/images/icon_upPhone.png");
        }if(state_call=="Down"){
            img[0].setAttribute("src","modules/control_panel/images/phhonez0.png");
        }

        if(call_dstn!=null && speak_time!=null){
            span[0].firstChild.nodeValue = call_dstn;
            span[1].firstChild.nodeValue = speak_time;
        }

        if(context=="macro-dialout-trunk"){
            var divTrun = document.getElementById("tru_" + trunk);
            var subdivTrun = divTrun.getElementsByTagName("div");
            var spanTrun = subdivTrun[1].getElementsByTagName("span");
            spanTrun[0].innerHTML = user;
            spanTrun[1].innerHTML = speak_time;
        }else{

            var divTrun = document.getElementById("trunks");
            var spanTrun = divTrun.getElementsByTagName("span");
            //alert(spanTrun.length);
            spanTrun[0].firstChild.nodeValue = " ";
            spanTrun[1].firstChild.nodeValue = " ";
        }
        if(numconf != " "){  
            var divConf = document.getElementById("conference_" + numconf);
            var spanConf = divConf.getElementsByTagName("span");
            spanConf[0].firstChild.nodeValue = parties;
            spanConf[1].firstChild.nodeValue = activity;
        }
        else{
            var divConf = document.getElementById("conference_2525");
            var spanConf = divConf.getElementsByTagName("span");
            spanConf[0].firstChild.nodeValue = "";
            spanConf[1].firstChild.nodeValue = "";
            var divConf = document.getElementById("conference_23");
            var spanConf = divConf.getElementsByTagName("span");
            spanConf[0].firstChild.nodeValue = "";
            spanConf[1].firstChild.nodeValue = "";
        }
    }
}

function loadSizeArea()
{
    $('div[id*="box_"]').remove();
    var arrAction              = new Array();
        arrAction["action"]    = "loadArea";
        arrAction["rawmode"]   = "yes";
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                    loadArea(arrData['xml'],arrData['module_name'],arrData['loading']);
            }
        );
}

function insertLoadingAnimation(aTableRow, nodeValue, module_name, loading)
{
	//aTableRow.innerHTML = "<td id='img_"+nodeValue+"'><img class='ima' src='modules/"+module_name+"/images/loading.gif' border='0' align='absmiddle' />&nbsp;"+loading+"</td>";
	oTD = document.createElement("td");
	oTD.setAttribute("id", "img_"+nodeValue);
	oIMG = document.createElement("img");
	oIMG.setAttribute("class", "ima");
	oIMG.setAttribute("src", "modules/"+module_name+"/images/loading.gif");
	oIMG.setAttribute("border", "0");
	oIMG.setAttribute("align", "absmiddle");
	oTD.appendChild(oIMG);
	oText = document.createTextNode("\u00a0" /* &nbsp; */ + loading);
	oTD.appendChild(oText);
	$('#'+aTableRow).empty().append(oTD);
}

function loadArea(xmlLoad,module_name,loading){
    if (window.DOMParser)
    {
        parser = new DOMParser();
        xmlDoc = parser.parseFromString(xmlLoad,"text/xml");
    }
    else // Internet Explorer
    {
        xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = "false";
        xmlDoc.loadXML(xmlLoad);
    }

    var db=xmlDoc.getElementsByTagName("areas");
    var area_box=db[0].getElementsByTagName("area_box");
    //alert("Presione [Enter] o de clic en [Aceptar] para recargar Areas");
    for(var i=0;i<area_box.length;i++)
    {
        var namearea   = area_box[i].getElementsByTagName("name")[0];
        var heightsize = area_box[i].getElementsByTagName("height")[0];
        var widthsize  = area_box[i].getElementsByTagName("width")[0];
        var id         = area_box[i].getElementsByTagName("id")[0];
        var color      = area_box[i].getElementsByTagName("color")[0];
        var area       = document.getElementById("area_"+id.firstChild.nodeValue);

        area.style.height = heightsize.firstChild.nodeValue+"px";
        area.style.width  = widthsize.firstChild.nodeValue+"px";
        area.style.backgroundColor = color.firstChild.nodeValue;
        insertLoadingAnimation("table"+namearea.firstChild.nodeValue, namearea.firstChild.nodeValue, module_name, loading);
        if(namearea.firstChild.nodeValue=="Extension"){
            var content = document.getElementById("content");
            content.style.width  = widthsize.firstChild.nodeValue+"px";
            content.style.height = "auto";

            var tool = document.getElementById("tool");
            tool.style.width = widthsize.firstChild.nodeValue+"px";
        }
    }
    getDataBox();
}

function saveDescriptionArea1(){
    var descripA1 =document.getElementById("descrip1").value;
    var arrAction                  = new Array();
        arrAction["action"]        = "saveEdit";
        arrAction["rawmode"]       = "yes";
        arrAction["description"]   = descripA1;
        arrAction["area"]          = 2;
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                    controlSaveDescripion1(arrData);
            }
        );
}
function controlSaveDescripion1(message) {
    alert(message);
    $("#layerCM").hide();
    var headArea2 = document.getElementById("headArea1");
    var lengthA2 = headArea2.firstChild.nodeValue.split(" -- ")[1];
    headArea2.firstChild.nodeValue = ""+document.getElementById("descrip1").value+" -- "+lengthA2+"";
}


function saveDescriptionArea2() {
    var descripA2 =document.getElementById("descrip2").value;
    var arrAction                  = new Array();
        arrAction["action"]        = "saveEdit";
        arrAction["rawmode"]       = "yes";
        arrAction["description"]   = descripA2;
        arrAction["area"]          = 3;
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                    controlSaveDescripion2(arrData);
            }
        );
}

function controlSaveDescripion2(message) {
    alert(message);
    $("#layerCM").hide();
    var headArea3 = document.getElementById("headArea2");
    var lengthA3 = headArea3.firstChild.nodeValue.split(" -- ")[1];
    headArea3.firstChild.nodeValue = ""+document.getElementById("descrip2").value+" -- "+lengthA3+"";
}


function saveDescriptionArea3() {
    var descripA3 =document.getElementById("descrip3").value;
    var arrAction                  = new Array();
        arrAction["action"]        = "saveEdit";
        arrAction["rawmode"]       = "yes";
        arrAction["description"]   = descripA3;
        arrAction["area"]          = 4;
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                    controlSaveDescripion3(arrData);
            }
        );
}
function controlSaveDescripion3(message) {
    alert(message);
    $("#layerCM").hide();
    var headArea4 = document.getElementById("headArea3");
    var lengthA4 = headArea4.firstChild.nodeValue.split(" -- ")[1];
    headArea4.firstChild.nodeValue = ""+document.getElementById("descrip3").value+" -- "+lengthA4+"";
}


loadSizeArea();



function reFresh() {
    location.reload(true)
}

function actualizarQueues()
{
    var arrAction                  = new Array();
        arrAction["action"]        = "refreshQueues";
        arrAction["rawmode"]       = "yes";
        request("index.php?menu=control_panel",arrAction,false,
            function(arrData,statusResponse,error)
            {
                    reloadQueues(arrData);
            }
        );
}

$(document).ready(function(){

	$(".move").draggable({
        zIndex:     20,
        ghosting:   false,
        opacity:    0.7
    });

    $('#editArea2').click(function() {
        var fieldDescrip1 = document.getElementById("headArea1");
        html = "<div class='div_content_bubble'><table align='center'>" +
                "<tr>" +
                    "<td colspan='2' style='font-size: 11px'>" +
                        "<font style='color:red'>Display Settings</font>" +
                    "</td>" +
                "</tr>" +
                "<tr>" +
                    "<td><label style='font-size: 11px; color: gray;'>Name:</label></td>" +
                    "<td><input type='text' value='' name='descrip1' id='descrip1' /></td>" +
                "</tr> <tr>" +
                    "<td align='center' colspan='2'>" +
                        "<input type='button' value='Save' class='boton'onclick='saveDescriptionArea1()'/>" +
                    "</td>" +
                "</tr>" +
            "</table></div>";

        document.getElementById("layerCM_content").innerHTML = html;
        var dataDescrip1 = fieldDescrip1.firstChild.nodeValue.split(" -- ")[0];
        document.getElementById("descrip1").value = dataDescrip1;
        $("#layerCM").show(); 
    });

    $('#editArea3').click(function() {
        var fieldDescrip2 = document.getElementById("headArea2");
        var dataDescrip2 = fieldDescrip2.firstChild.nodeValue.split(" -- ")[0];
        html = "<div class='div_content_bubble'><table align='center'>" +
                "<tr>" +
                    "<td colspan='2' style='font-size: 11px'>" +
                        "<font style='color:red'>Display Settings</font>" +
                    "</td>" +
                "</tr>" +
                "<tr>" +
                    "<td><label style='font-size: 11px; color: gray;'>Name:</label></td>" +
                    "<td><input type='text' value='' name='descrip2' id='descrip2' /></td>" +
                "</tr> <tr>" +
                    "<td align='center' colspan='2'>" +
                        "<input type='button' value='Save' class='boton'onclick='saveDescriptionArea2()'/>" +
                    "</td>" +
                "</tr>" +
            "</table></div>";

        document.getElementById("layerCM_content").innerHTML = html;
        document.getElementById("descrip2").value = dataDescrip2;
        $("#layerCM").show(); 
    });

    $('#editArea4').click(function() {
        var fieldDescrip3 = document.getElementById("headArea3");
        var dataDescrip3 = fieldDescrip3.firstChild.nodeValue.split(" -- ")[0];
        html = "<div class='div_content_bubble'><table align='center'>" +
                "<tr>" +
                    "<td colspan='2' style='font-size: 11px'>" +
                        "<font style='color:red'>Display Settings</font>" +
                    "</td>" +
                "</tr>" +
                "<tr>" +
                    "<td><label style='font-size: 11px; color: gray;'>Name:</label></td>" +
                    "<td><input type='text' value='' name='descrip3' id='descrip3' /></td>" +
                "</tr> <tr>" +
                    "<td align='center' colspan='2'>" +
                        "<input type='button' value='Save' class='boton'onclick='saveDescriptionArea3()'/>" +
                    "</td>" +
                "</tr>" +
            "</table></div>";

        document.getElementById("layerCM_content").innerHTML = html;
        document.getElementById("descrip3").value = dataDescrip3;
        $("#layerCM").show(); 
    });
    
    $(".img_box").draggable({
        zIndex:     990,
        revert: true,
        cursor: 'crosshair',
        start: function(event, ui) {
            //$(this).css('background-color','#ddddff');
        },
        out: function(event, ui) {
            //$(this).css('background-color', null);
        },
        drag: function(event, ui) {
        }
    });

    $(".img_box").droppable({
        over: function(event, ui) {
            //$(this).css('background-color', '#A3C1F9');//cambia color
        },
        out: function(event, ui) {
            //$(this).css('background-color', null);
        },
        drop: function(event, ui) {
            var idStart = ($(ui.draggable).attr("id")).split("_");
            var idFinish = ($(this).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "call";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  =  idStart[1];
                arrAction["extFinish"] =  idFinish[1];
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                        $("#contentRight").html(arrData);
                    }
            );
        }
    });


    //Evento Doble clic Accion Hangup//
    $(".img_box").dblclick(function(ev)
    {
        var extStart = ($(this).attr("id")).split("_");
        var arrAction              = new Array();
            arrAction["action"]    = "hangup";
            arrAction["rawmode"]   = "yes";
            arrAction["extStart"]  =  extStart[1];
            request("index.php?menu=control_panel",arrAction,false,
               function(arrData,statusResponse,error)
               {
                    $("#contentRight").html(arrData);
               }
            );        
    });

    
    $(".mail_box").droppable({
        over: function(event, ui)
        {
            //$(this).css('background-color', '#F5F6BE');//cambia color
        },
        out: function(event, ui)
        {
            //$(this).css('background-color', null);
        },
        drop: function(event, ui)
        {
            var idStart = ($(ui.draggable).attr("id")).split("_");
            //var idFinish = ($(this).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "voicemail";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  =  idStart[1];
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            $("#contentRight").html(arrData);
                    }
                );
        }
    });

    $(".box").draggable({
        zIndex:     989,
        revert: true,
        cursor: 'crosshair',
        start: function(event, ui) {
            //$(this).css('background-color','#ddddff');
        }
    });

//     $(".item_box").droppable({
//         accept: ".item_box",
//         drop: function(event, ui) {
//             $(this).append($(ui.draggable));
//         }
//     });
    //NUEVA AGREGACION
//     $(".item_box").droppable({
//         accept: ".item_box",
//         drop: function(event, ui) {
//             $(this).append($(ui.draggable));
//         }
//     });

    $(".areaDrop").droppable({
        accept: ".box",//#lista_local
        drop: function(event, ui) {
            $(this).append($(ui.draggable));
            var idStart = ($(ui.draggable).attr("id")).split("_");
            var idFinish = ($(this).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "savechange2";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  =  idStart[1];
                arrAction["extFinish"] =  idFinish[1];
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            $("#contentRight").html(arrData);
                    }
                );
        }
    });


    $(".areaDropSub1").droppable({
        accept: ".item_box",
        drop: function(event, ui) {
            $(this).append($(ui.draggable));
            var idStart = ($(ui.draggable).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "savechange";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  =  idStart[1];
                arrAction["area"]      = 2;
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            $("#contentRight").html(arrData);
                    }
                );
        }
    });

    $(".areaDropSub2").droppable({
        accept: ".item_box",
        drop: function(event, ui)
        {
            $(this).append($(ui.draggable));
            var idStart = ($(ui.draggable).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "savechange";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  =  idStart[1];
                arrAction["area"]      = 3;
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            $("#contentRight").html(arrData);
                    }
                );
        }
    });

    $(".areaDropSub3").droppable({
        accept: ".item_box",
        drop: function(event, ui)
        {
            $(this).append($(ui.draggable));
            var idStart = ($(ui.draggable).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "savechange";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  =  idStart[1];
                arrAction["area"]      = 4;
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            $("#contentRight").html(arrData);
                    }
                );
        }
    });

    $(".phone_boxqueue").droppable({
        //accept: ".phone_box",
        drop: function(event, ui)
        {
            var idStart = ($(ui.draggable).attr("id")).split("_");
            var queue = ($(this).attr("id")).split("_");
            var arrAction              = new Array();
                arrAction["action"]    = "addExttoQueue";
                arrAction["rawmode"]   = "yes";
                arrAction["extStart"]  = idStart[1];
                arrAction["queue"]     = queue[1];
                request("index.php?menu=control_panel",arrAction,false,
                    function(arrData,statusResponse,error)
                    {
                            $("#contentRight").html(arrData);
                    }
                );
        }
    });
    $('#closeCM').click(function() {
            $("#layerCM").hide();
        });
    $('#layerCM').draggable();
});

