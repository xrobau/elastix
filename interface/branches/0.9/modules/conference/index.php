<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2008/01/30 15:55:57 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoConference.php";
    global $arrConf;
    global $arrLang;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];


    /** Inicio de Wrapper para terminar la instalacion de la base de datos meetme de este modulo. */
    $accion = (isset($_GET['accion']))?$_GET['accion']:"";
    $msmError = "";
    $isInstalled = isModuleTotalInstalled($module_name,$accion,$arrLang,$msmError);
    if($isInstalled == "error"){
        $smarty->assign("MESSAGE",$msmError);
        return $smarty->fetch("file:$local_templates_dir/wrapper.tpl");
    }
    else if($isInstalled == "not_installed"){
        $smarty->assign("MESSAGE",$msmError);
        return $smarty->fetch("file:$local_templates_dir/wrapper.tpl");
    }
    else if($isInstalled == "now_installed" || $isInstalled == "installed"){
        //No se hace nada se supone todo esta bien, por lo tanto se deja que continue la ejecucion
        //del modulo.
    }
    /** Fin de Wrapper para terminar la instalacion de la base de datos meetme de este modulo. */



    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsnMeetme =  $arrConfig['AMPDBENGINE']['valor'] . "://".
                  $arrConfig['AMPDBUSER']['valor'] . ":". 
                  $arrConfig['AMPDBPASS']['valor'] . "@".
                  $arrConfig['AMPDBHOST']['valor'] . "/meetme";

    $dsn_agi_manager['password'] = $arrConfig['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrConfig['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = 'admin';

    //solo para obtener los devices (extensiones) creadas.
    $dsnAsterisk = $arrConfig['AMPDBENGINE']['valor']."://".
                   $arrConfig['AMPDBUSER']['valor']. ":".
                   $arrConfig['AMPDBPASS']['valor']. "@".
                   $arrConfig['AMPDBHOST']['valor']."/asterisk";

    $pDB     = new paloDB($dsnMeetme);

    if(isset($_POST["new_conference"])) $accion = "new_conference";
    else if(isset($_POST["add_conference"])) $accion = "add_conference";
    else if(isset($_POST["cancel"])) $accion = "cancel";
    else if(isset($_POST["delete_conference"])) $accion = "delete_conference";
    else if(isset($_GET["accion"]) && $_GET["accion"]=="show_callers") $accion = "show_callers";
    else if(isset($_POST["callers_mute"])) $accion = "callers_mute";
    else if(isset($_POST["callers_kick"])) $accion = "callers_kick";
    else if(isset($_POST["callers_kick_all"])) $accion = "callers_kick_all";
    else if(isset($_POST["caller_invite"])) $accion = "caller_invite";
    else if(isset($_POST["update_show_callers"])) $accion = "update_show_callers";
    else if(isset($_GET["accion"]) && $_GET["accion"]=="view_conference") $accion = "view_conference";
    else $accion ="report_conference";
    $content = "";
    switch($accion)
    {
        case "new_conference":
            $content = new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig,$dsnAsterisk);
            break;
        case "add_conference":
            $content = add_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig,$dsnAsterisk);
            break;
        case "cancel":
            header("Location: ?menu=$module_name");
            break;
        case "delete_conference":
            $content = delete_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "show_callers":
            $content = show_callers($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "callers_mute":
            $content = callers_mute($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "callers_kick":
            $content = callers_kick($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "view_conference":
            $content = view_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig,$dsnAsterisk);
            break;
        case "callers_kick_all":
            $content = callers_kick_all($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "caller_invite":
            $content = caller_invite($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "update_show_callers":
            $room = getParametro('roomNo');
            header("location: ?menu=$module_name&accion=show_callers&roomNo=$room");
            break;
        default:
            $content = report_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
    }

    return $content;
}

function report_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $arrConference = array("Past_Conferences" => $arrLang["Past Conferences"], "Current_Conferences" => $arrLang["Current Conferences"], "Future_Conferences" => $arrLang["Future Conferences"]);

    $arrFormElements = array(
                                "conference"  => array(  "LABEL"                  => $arrLang["State"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrConference,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "EDITABLE"               => "no",
                                                    "SIZE"                   => "1"),

                                "filter" => array(  "LABEL"                  => $arrLang["Filter"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SHOW", $arrLang["Show"]);
    $smarty->assign("NEW_CONFERENCE", $arrLang["New Conference"]);

    $startDate = $endDate = date("Y-m-d H:i:s");

    $conference = getParametro("conference");
    $field_pattern = getParametro("filter");
    if($conference)
        $_POST['conference'] = $conference;
    else $_POST['conference'] = "Current_Conferences";

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/conference.tpl", "", $_POST);

    $pConference = new paloSantoConference($pDB);
    $total_datos =$pConference->ObtainNumConferences($startDate, $endDate, "confDesc", $field_pattern, $conference);

    //Paginacion
    $limit  = 8;
    $total  = $total_datos[0];

    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $url = "?menu=$module_name&conference=".$conference."&filter=$field_pattern";
    $smarty->assign("url", $url);
    //Fin Paginacion

    $arrResult =$pConference->ObtainConferences($limit, $offset, $startDate, $endDate, "confDesc", $field_pattern, $conference);

    $arrData = null;
    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $conference){
            $arrTmp[0]  = "<input type='checkbox' name='conference_{$conference['bookId']}'  />";
            $arrTmp[1] = $conference['roomNo'];
            $arrTmp[2] = "<a href='?menu=$module_name&accion=view_conference&conferenceId=".$conference['bookId']."'>{$conference['confDesc']}</a>";
            $arrTmp[3] = $conference['startTime'];
            $arrTmp[4] = $conference['endTime'];
            if($_POST['conference'] == "Current_Conferences")
            {
                $arrCallers = $pConference->ObtainCallers($dsn_agi_manager, $conference['roomNo']);
                $numCallers = count($arrCallers);
                $arrTmp[5] = "<a href='?menu=$module_name&accion=show_callers&roomNo=".$conference['roomNo']."'>{$numCallers} / {$conference['maxUser']}</a>";
            }
            else
                $arrTmp[5] = $conference['maxUser'];
            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array("title"    => $arrLang["Conference"],
                        "icon"     => "images/conference.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "columns"  => array(0 => array("name"      => "<input type='submit' name='delete_conference' value='{$arrLang["Delete"]}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to delete conference (es)?"]}');\" />",
                                                    "property1" => ""),
                                            1 => array("name"      => $arrLang["Conference #"],
                                                    "property1" => ""),
                                            2 => array("name"      => $arrLang["Conference Name"],
                                                    "property1" => ""),
                                            3 => array("name"      => $arrLang["Starts"],
                                                    "property1" => ""),
                                            4 => array("name"      => $arrLang["Ends"],
                                                    "property1" => ""),
                                            5 => array("name"      => $arrLang["Participants"],
                                                    "property1" => "")
                                        )
                    );

    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";

    return $contenidoModulo;
}

function new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsnAsterisk)
{
    $arrFormConference = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("Show", 1);
    $smarty->assign("REQUIRED_FIELD", "Required field");
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("TITLE", $arrLang["Conference"]);
    $smarty->assign("announce", $arrLang["Announce"]);
    $smarty->assign("record", $arrLang["Record"]);
    $smarty->assign("listen_only", $arrLang["Listen Only"]);
    $smarty->assign("wait_for_leader", $arrLang["Wait for Leader"]);

    $pConference = new paloSantoConference($pDB);
    while(true)
    {
        $number = rand(0,99999);
        $existe = $pConference->ConferenceNumberExist($number);
        if(!$existe)
        {
            $_POST['conference_number'] = $number;
            break;
        }
    }

    $_POST['max_participants'] = 10;
    $_POST['duration'] = 1;
    $_POST['duration_min'] = 0;
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_conference.tpl", "", $_POST);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldForm($arrLang)
{
/*
    $arrReoccurs_period = array("Daily" => $arrLang["Daily"], "Weekly" => $arrLang["Weekly"], "Bi-weekly" => $arrLang["Bi-weekly"]);
    $arrReoccurs_days = array("2" => "2 ".$arrLang["days"], "3" => "3 ".$arrLang["days"],
                              "4" => "4 ".$arrLang["days"], "5" => "5 ".$arrLang["days"],
                              "6" => "6 ".$arrLang["days"], "7" => "7 ".$arrLang["days"],
                              "8" => "8 ".$arrLang["days"], "9" => "9 ".$arrLang["days"],
                              "10" => "10 ".$arrLang["days"], "11" => "11 ".$arrLang["days"],
                              "12" => "12 ".$arrLang["days"], "13" => "13 ".$arrLang["days"],
                              "14" => "14 ".$arrLang["days"]);
*/

    $arrFields =       array("conference_name"  => array("LABEL"              => $arrLang['Conference Name'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:300px;"),
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "conference_owner" => array("LABEL"              => $arrLang['Conference Owner'],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "conference_number" => array("LABEL"              => $arrLang['Conference Number'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "moderator_pin"     => array("LABEL"              => $arrLang['Moderator PIN'],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "moderator_options_1" => array("LABEL"            => $arrLang['Moderator Options'],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "moderator_options_2" => array("LABEL"            => "",
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_pin"          => array("LABEL"              => $arrLang['User PIN'],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_options_1"    => array("LABEL"              => $arrLang['User Options'],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_options_2"    => array("LABEL"              => "",
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_options_3"    => array("LABEL"              => "",
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "start_time"        => array("LABEL"              => $arrLang['Start Time (PST/PDT)'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "DATE",
                                                     "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "%Y-%m-%d %H:%M","TIMEFORMAT" => "12"),
                                                     "VALIDATION_TYPE"        => "ereg",
                                                     "VALIDATION_EXTRA_PARAM" => "^(([1-2][0,9][0-9][0-9])-((0[1-9])|(1[0-2]))-((0[1-9])|([1-2][0-9])|(3[0-1]))) (([0-1][0-9]|2[0-3]):[0-5][0-9])$"),
                            "duration"          => array("LABEL"              => $arrLang['Duration (HH:MM)'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:20px;text-align:center","maxlength" =>"2"),
                                                     "VALIDATION_TYPE"        => "integer",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "duration_min"      => array("LABEL"              => $arrLang['Duration (HH:MM)'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:20px;text-align:center","maxlength" =>"2"),
                                                     "VALIDATION_TYPE"        => "integer",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
/*
                            "recurs"            => array("LABEL"              => $arrLang['Recurs'],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "reoccurs_period"   => array("LABEL"              => $arrLang["Reoccurs"],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "SELECT",
                                                     "INPUT_EXTRA_PARAM"      => $arrReoccurs_period,
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => "",
                                                     "EDITABLE"               => "no",
                                                     "SIZE"                   => "1"),
                            "reoccurs_days"     => array("LABEL"              => $arrLang["for"],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "SELECT",
                                                     "INPUT_EXTRA_PARAM"      => $arrReoccurs_days,
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => "",
                                                     "EDITABLE"               => "no",
                                                     "SIZE"                   => "1"),
*/
                            "max_participants"  => array("LABEL"              => $arrLang['Max Participants'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:50px;"),
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                        );
    return $arrFields;
}

function add_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsnAsterisk)
{
    $arrFormConference = createFieldForm($arrLang);
    $oForm = new paloForm($smarty, $arrFormConference);

    $bandera = true;
    if(!empty($_POST['moderator_pin']) && !empty($_POST['user_pin']) &&  $_POST['moderator_pin']==$_POST['user_pin'])
        $bandera = false;
    if(!$oForm->validateForm($_POST) || !$bandera) {
        // Falla la validación básica del formulario
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
        }
        if(!$bandera)
            $strErrorMsg .= $arrLang['Moderator and user PINs must not be equal'];
        $smarty->assign("mb_message", $strErrorMsg);

        $smarty->assign("Show", 1);
        $smarty->assign("REQUIRED_FIELD", "Required field");
        $smarty->assign("SAVE", $arrLang["Save"]);
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("TITLE", $arrLang["Conference"]);
        $smarty->assign("announce", $arrLang["Announce"]);
        $smarty->assign("record", $arrLang["Record"]);
        $smarty->assign("listen_only", $arrLang["Listen Only"]);
        $smarty->assign("wait_for_leader", $arrLang["Wait for Leader"]);

        $htmlForm = $oForm->fetchForm("$local_templates_dir/new_conference.tpl", $arrLang, $_POST);

        $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

        return $contenidoModulo;
    }else{
        $data = array();

        //$_POST['recurs'];
        //$_POST['reoccurs_period'];
        //$_POST['reoccurs_days'];

        $data['confDesc'] = "'".$_POST['conference_name']."'";
        $data['confOwner'] = empty($_POST['conference_owner'])?"''":"'".$_POST['conference_owner']."'";
        $data['roomNo'] = $_POST['conference_number'];

        $data['silPass'] = empty($_POST['moderator_pin'])?"''":$_POST['moderator_pin'];

        $announce = ($_POST['moderator_options_1']=='on')?'i':'';
        $record = ($_POST['moderator_options_2']=='on')?'r':'';
        $data['aFlags'] = "'asdA".$announce.$record."'";

        $data['roomPass'] = empty($_POST['user_pin'])?"''":$_POST['user_pin'];

        $announce = ($_POST['user_options_1']=='on')?'i':'';
        $listen_only = ($_POST['user_options_2']=='on')?'m':'';
        $wait_for_leader = ($_POST['user_options_3']=='on')?'w':'';
        $data['uFlags'] = "'d".$announce.$listen_only.$wait_for_leader."'";

        $data['startTime'] = "'".$_POST['start_time']."'";

        $fecha = strtotime($_POST['start_time']);
        $duracion = ($_POST['duration']*3600)+($_POST['duration_min']*60);
        $fecha = $fecha + $duracion;
        $data['endTime'] = "'".date("Y-m-d H:i:s", $fecha)."'";

        $data['maxUser'] = $_POST['max_participants'];

        $data['clientId'] = 0;
        $data['status'] = "'A'";
        $data['sequenceNo'] = 0; //Si se usa recurrencia debe autoincrementar
        $data['recurInterval'] = 0; //Si se usa recurrencia debe calcularse el tiempo

        $pConference = new paloSantoConference($pDB);
        $result = $pConference->AddConference($data);

        header("Location: ?menu=$module_name");
    }
}

function delete_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    foreach($_POST as $key => $values){
        if(substr($key,0,11) == "conference_")
        {
            $tmpBookID = substr($key, 11);

            $result = $pConference->DeleteConference($tmpBookID);
        }
    }
    $content = report_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk);

    return $content;
}

function show_callers($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);
    $room = getParametro("roomNo");
    $arrCallers = $pConference->ObtainCallers($dsn_agi_manager, $room);
    $arrDevices = $pConference->getDeviceFreePBX($dsnAsterisk);
    $arrData = null;

    if(is_array($arrCallers) && count($arrCallers)>0){
        foreach($arrCallers as $key => $caller){
            $arrTmp[0] = $caller['userId'];
            $arrTmp[1] = $arrDevices[$caller['callerId']];
            $arrTmp[2] = $caller['duration'];
            $mode = strstr($caller['mode'], "Muted");
            if(!$mode)
            {
                $arrTmp[3] = $arrLang["UnMuted"];
                $checked = 'off';
            }
            else{
                $arrTmp[3] = $arrLang["Muted"];
                $checked = 'on';
            }
            $arrTmp[4] = checkbox("mute_".$caller['userId'], $checked);
            $arrTmp[5] = checkbox("kick_".$caller['userId']);
            $arrData[] = $arrTmp;
        }
    }
    $total = count($arrCallers);

    $arrGrid = array("title"    => $arrLang["Conference"],
                        "icon"     => "images/conference.png",
                        "width"    => "99%",
                        "start"    => 1,
                        "end"      => $total,
                        "total"    => $total,
                        "columns"  => array(0 => array("name"      => $arrLang["Id"],
                                                    "property1" => ""),
                                            1 => array("name"      => $arrLang["CallerId"],
                                                    "property1" => ""),
                                            2 => array("name"      => $arrLang["Duration"],
                                                    "property1" => ""),
                                            3 => array("name"      => $arrLang["Status"],
                                                    "property1" => ""),
                                            4 => array("name"      => "<input type='submit' name='callers_mute' value='{$arrLang["Mute"]}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to Mute caller (s)?"]}');\" />",
                                                    "property1" => ""),
                                            5 => array("name"      => "<input type='submit' name='callers_kick' value='{$arrLang["Kick"]}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to Kick caller (s)?"]}');\" />",
                                                    "property1" => "")
                                        )
                    );
    $oGrid  = new paloSantoGrid($smarty);

    $smarty->assign("INVITE_CALLER", $arrLang["Invite Caller"]);
    $smarty->assign("KICK_ALL", $arrLang["Kick All"]);
    $smarty->assign("UPDATE", $arrLang["Update"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("accion", "show_callers");

    $arrFormElements = array(
                            "device"  => array(     "LABEL"                  => "DEVICE",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDevices,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "")
                                );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $_POST['device']="unselected";
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/conference.tpl", "", $_POST);
    $oGrid->showFilter(trim($htmlFilter));

    $url_room= "&roomNo=".$_GET['roomNo'];
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name$url_room'>".$oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";

    return $contenidoModulo;
}

function callers_mute($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParametro('roomNo');
    foreach($_POST as $key => $values)
    {
        if(substr($key,0,5) == "mute_")
        {
            $tmpCallerId = substr($key, 5);
            $arrCallers = $pConference->MuteCaller($dsn_agi_manager, $room, $tmpCallerId, $_POST["$key"]);
        }
    }

    header("location: ?menu=$module_name&accion=show_callers&roomNo=$room");
}

function callers_kick($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParametro('roomNo');
    foreach($_POST as $key => $values)
    {
        if(substr($key,0,5) == "kick_")
        {
            $tmpCallerId = substr($key, 5);
            if($_POST["$key"] == "on")
                $arrCallers = $pConference->KickCaller($dsn_agi_manager, $room, $tmpCallerId);
        }
    }
    sleep(3);
    header("location: ?menu=$module_name&accion=show_callers&roomNo=$room");
}

function callers_kick_all($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParametro('roomNo');

    $pConference->KickAllCallers($dsn_agi_manager, $room);

    sleep(3);
    header("location: ?menu=$module_name");
}

function caller_invite($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParametro('roomNo');

    $device = getParametro("device");

    if($device != null)
    {
        if(eregi('^[0-9]+$', $device))
        {
            $callerId = $arrLang['Conference']. "<$room>";
            $result = $pConference->InviteCaller($dsn_agi_manager, $room, $device, $callerId);
            if(!$result)
            {
                $smarty->assign("mb_title", $arrLang['ERROR'].":");
                $smarty->assign("mb_message", $arrLang["The device couldn't be added to the conference"]);
            }else sleep(3);
        }else{
            $smarty->assign("mb_title", $arrLang['ERROR'].":");
            $smarty->assign("mb_message", $arrLang["The device must be numeric"]);
        }
    }
    else{
        $smarty->assign("mb_title", $arrLang['ERROR'].":");
        $smarty->assign("mb_message", $arrLang["The device wasn't write"]);
    }

    return show_callers($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
}

function view_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConfig, $dsnAsterisk)
{
    $arrFormConference = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("Show", 0);
    $smarty->assign("REQUIRED_FIELD", "Required field");
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("TITLE", $arrLang["Conference"]);
    $smarty->assign("announce", $arrLang["Announce"]);
    $smarty->assign("record", $arrLang["Record"]);
    $smarty->assign("listen_only", $arrLang["Listen Only"]);
    $smarty->assign("wait_for_leader", $arrLang["Wait for Leader"]);

    $pConference = new paloSantoConference($pDB);
    $conferenceId = isset($_GET['conferenceId'])?$_GET['conferenceId']:"";

    $conferenceData = $pConference->ObtainConferenceData($conferenceId);

    $arrData['conference_number'] = $conferenceData['roomNo'];
    $arrData['conference_owner'] = $conferenceData['confOwner'];
    $arrData['conference_name'] = $conferenceData['confDesc'];
    $arrData['moderator_pin'] = $conferenceData['silPass'];
    $arrData['user_pin'] = $conferenceData['roomPass'];
    $arrData['start_time'] = $conferenceData['startTime'];
    $arrData['max_participants'] = $conferenceData['maxUser'];
    if(strpos($conferenceData['aFlags'], 'i', 4))
        $arrData['moderator_options_1'] = 'on';
    if(strpos($conferenceData['aFlags'], 'r', 4))
        $arrData['moderator_options_2'] = 'on';

    if(strpos($conferenceData['uFlags'], 'i', 1))
        $arrData['user_options_1'] = 'on';
    if(strpos($conferenceData['uFlags'], 'm', 1))
        $arrData['user_options_2'] = 'on';
    if(strpos($conferenceData['uFlags'], 'w', 1))
        $arrData['user_options_3'] = 'on';

    $fecha_ini = strtotime($conferenceData['startTime']);
    $fecha_fin = strtotime($conferenceData['endTime']);
    $duracion = $fecha_fin - $fecha_ini;

    $arrData['duration'] = number_format($duracion/3600, 0, ",", "");
    $arrData['duration_min'] = ($duracion%3600)/60;

    $oForm->setViewMode();
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_conference.tpl", "", $arrData);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function getParametro($parametro)
{
    if(isset($_POST[$parametro]))
        return $_POST[$parametro];
    else if(isset($_GET[$parametro]))
        return $_GET[$parametro];
    else
        return null;
}

function isModuleTotalInstalled($module_name, $accion, $arrLang, &$msmError)
{
    $usuario = "root";
    $clave   = "eLaStIx.2oo7";

    //PASO 1
    $pDB = new paloDB("mysql://$usuario:$clave@localhost/information_schema");
    if(!empty($pDB->errMsg)) {
        $msmError = $arrLang['ERROR']." DB: ".$pDB->errMsg;
    }
    $sql     = "select count(*) existe from tables where table_schema='meetme'";
    $result  = $pDB->getFirstRowQuery($sql,true);
    $pDB->disconnect();

    //PASO 2
    if(is_array($result) && count($result) > 0){
        if($result['existe']==0 && $accion=='crear'){ // no existe la base completamente 
            // ejecutar comanado para crear la base de datos.
            exec("/usr/bin/mysql --user=$usuario --password=$clave < /var/www/html/schema.meetme", $arrConsole,$flagStatus); 
            if($flagStatus==0){
                sleep(5);
                return "now_installed";
            }
            else{
                $msmError = $arrLang['ERROR'];
                return "Error";
            }
        }
        else if($result['existe']==0){
            $msmError = $arrLang['The conference installation is almost done. To complete it please']." <a href='?menu=$module_name&accion=crear'>".$arrLang['click here']."</a>";
            return "not_installed";
        }
        else{  //si existe la base de datos
            return "installed";
        }
    }
    else{
        $msmError =  $arrLang['ERROR']." DB: ".$pDB->errMsg;
        return "Error";
    }
}
?>