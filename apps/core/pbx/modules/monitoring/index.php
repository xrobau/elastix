<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-18                                               |
  | http://www.elastix.com                                               |
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
  $Id: index.php,v 1.3 2007/09/05 00:26:21 gcarrillo Exp $
  $Id: index.php,v 1.3 2008/04/14 09:22:21 afigueroa Exp $
  $Id: index.php,v 2.0 2010/02/03 09:00:00 onavarre Exp $
  $Id: index.php,v 2.1 2010-03-22 05:03:48 Eduardo Cueva ecueva@palosanto.com Exp $ */
//include elastix framework

// exten => s,n,Set(CDR(userfield)=audio:${CALLFILENAME}.${MIXMON_FORMAT})   extensions_additional
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoMonitoring.class.php";
    include_once "libs/paloSantoACL.class.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $arrConf['dsn_conn_database'] = generarDSNSistema('asteriskuser', 'asteriskcdrdb');
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $pDBACL = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pDBACL);
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $extension = $pACL->getUserExtension($user);
    if ($extension == '') $extension = NULL;
    $esAdministrador = $pACL->isUserAdministratorGroup($user);

    // Sólo el administrador puede consultar con $extension == NULL
    if (is_null($extension)) {
        if ($esAdministrador)
            $smarty->assign("mb_message", "<b>"._tr("no_extension")."</b>");
        else{
            $smarty->assign("mb_message", "<b>"._tr("contact_admin")."</b>");
            return "";
        }
    }

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case 'delete':
            $content = deleteRecord($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
            break;
        case 'download':
            $content = downloadFile($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
            break;
        case "display_record":
            $content = display_record($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
            break;
        default:
            $content = reportMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
            break;
    }
    return $content;
}

function reportMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $pACL, $arrConf, $user, $extension, $esAdministrador)
{
    $pMonitoring = new paloSantoMonitoring($pDB);
    $filter_field = getParameter("filter_field");

    switch($filter_field){
        case "dst":
            $filter_field = "dst";
            $nameFilterField = _tr("Destination");
            break;
        case "recordingfile":
            $filter_field = "recordingfile";
            $nameFilterField = _tr("Type");
            break;
        default:
            $filter_field = "src";
            $nameFilterField = _tr("Source");
            break;
    }
    if($filter_field == "recordingfile"){
        $filter_value     = getParameter("filter_value_recordingfile");
        $filter           = "";
        $filter_recordingfile = $filter_value;
    }
    else{
        $filter_value     = getParameter("filter_value");
        $filter           = $filter_value;
        $filter_recordingfile = "";
    }
    switch($filter_value){
        case "outgoing":
              $smarty->assign("SELECTED_2", "Selected");
              $nameFilterUserfield = _tr("Outgoing");
              break;
        case "queue":
              $smarty->assign("SELECTED_3", "Selected");
              $nameFilterUserfield = _tr("Queue");
              break;
        case "group":
              $smarty->assign("SELECTED_4", "Selected");
              $nameFilterUserfield = _tr("Group");
              break;
        default:
              $smarty->assign("SELECTED_1", "Selected");
              $nameFilterUserfield = _tr("Incoming");
              break;
    }
    $date_ini = getParameter("date_start");
    $date_end = getParameter("date_end");

    $path_record = $arrConf['records_dir'];

    $_POST['date_start'] = isset($date_ini)?$date_ini:date("d M Y");
    $_POST['date_end']   = isset($date_end)?$date_end:date("d M Y");

    if($date_ini===""){
        $_POST['date_start'] = " ";
    }
    if($date_end==="")
        $_POST['date_end'] = " ";

    if (!empty($pACL->errMsg)) {
        echo "ERROR DE ACL: $pACL->errMsg <br>";
    }

    $date_initial = date('Y-m-d',strtotime($_POST['date_start']))." 00:00:00";
    $date_final   = date('Y-m-d',strtotime($_POST['date_end']))." 23:59:59";

    $_DATA = $_POST;
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Monitoring"));
    $oGrid->setIcon("modules/$module_name/images/pbx_monitoring.png");
    $oGrid->pagingShow(true); // show paging section.

    $oGrid->enableExport();   // enable export.
    $oGrid->setNameFile_Export(_tr("Monitoring"));

    // Se asume que sólo el administrador puede consultar con extension NULL
    $totalMonitoring = $pMonitoring->getNumMonitoring($filter_field, $filter_value,
        $esAdministrador ? NULL : $extension, $date_initial, $date_final);
    $url = array('menu' => $module_name);

    $paramFilter = array(
       'filter_field'           => $filter_field,
       'filter_value'           => $filter,
       'filter_value_recordingfile' => $filter_recordingfile,
       'date_start'             => $_POST['date_start'],
       'date_end'               => $_POST['date_end']
    );
    $url = array_merge($url, $paramFilter);
    $oGrid->setURL($url);

    $arrData = null;
    $oGrid->setTotal($totalMonitoring);
    if ($oGrid->isExportAction()) {
        $limit = $totalMonitoring;
        $offset = 0;

        $arrColumns = array(_tr("Date"), _tr("Time"), _tr("Source"),
            _tr("Destination"), _tr("Duration"),_tr("Type"),_tr("File"));
    } else {
        $limit  = 20;
        $oGrid->setLimit($limit);
        $offset = $oGrid->calculateOffset();

        $arrColumns = array('', _tr("Date"), _tr("Time"), _tr("Source"),
            _tr("Destination"),_tr("Duration"),_tr("Type"),_tr("Message"));
    }

    $oGrid->setColumns($arrColumns);

    // Se asume que sólo el administrador puede consultar con extension NULL
    $arrResult = $pMonitoring->getMonitoring($limit, $offset, $filter_field, $filter_value,
        $esAdministrador ? NULL : $extension, $date_initial, $date_final);

    if (is_array($arrResult)) {
        if ($oGrid->isExportAction()) {
            $arrData = array_map('formatCallRecordingTuple', $arrResult);
        } else foreach ($arrResult as $value) {
            $arrTmp = formatCallRecordingTuple($value);
            array_unshift($arrTmp, $esAdministrador ? "<input type='checkbox' name='id_".$value['uniqueid']."' />" : '');

            // checkbox(id_uniqueid) date time src dst hh:mm:ss rectype namefile
            if ($arrTmp[3] == '') $arrTmp[3] = "<font color='gray'>"._tr("unknown")."</font>";
            if ($arrTmp[4] == '') $arrTmp[4] = "<font color='gray'>"._tr("unknown")."</font>";
            $arrTmp[5] = "<label title='".$value['duration']." "._tr('seconds')."' style='color:green'>".$arrTmp[5]."</label>";

            if ($arrTmp[7] != 'deleted') {
                $urlparams = array(
                    'menu'      =>  $module_name,
                    'action'    =>  'display_record',
                    'id'        =>  $value['uniqueid'],
                    'namefile'  =>  $arrTmp[7],
                    'rawmode'   =>  'yes',
                );
                $recordingLink = "<a href=\"javascript:popUp('index.php?".urlencode(http_build_query($urlparams)."',350,100);")."\">"._tr("Listen")."</a>&nbsp;";

                $urlparams['action'] = 'download';
                $recordingLink .= "<a href='?".http_build_query($urlparams)."' >"._tr("Download")."</a>";
            } else {
                $recordingLink = '';
            }
            $arrTmp[7] = $recordingLink;

            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    if ($esAdministrador) {
        $oGrid->deleteList(_tr("message_alert"), 'submit_eliminar', _tr("Delete"));
    }

    //begin section filter
    $arrFormFilterMonitoring = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormFilterMonitoring);

    $smarty->assign("INCOMING", _tr("Incoming"));
    $smarty->assign("OUTGOING", _tr("Outgoing"));
    $smarty->assign("QUEUE", _tr("Queue"));
    $smarty->assign("GROUP", _tr("Group"));
    $smarty->assign("SHOW", _tr("Show"));
    $_POST["filter_field"]           = $filter_field;
    $_POST["filter_value"]           = $filter;
    $_POST["filter_value_recordingfile"] = $filter_recordingfile;

    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Start Date")." = ".$paramFilter['date_start'].", "._tr("End Date")." = ".$paramFilter['date_end'], $paramFilter,  array('date_start' => date("d M Y"),'date_end' => date("d M Y")),true);

    if($filter_field == "recordingfile"){
        $oGrid->addFilterControl(_tr("Filter applied ")." $nameFilterField = $nameFilterUserfield", $_POST, array('filter_field' => "src",'filter_value_recordingfile' => "incoming"));
    }
    else{
        $oGrid->addFilterControl(_tr("Filter applied ")." $nameFilterField = $filter", $_POST, array('filter_field' => "src","filter_value" => ""));
    }

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter
    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();

    //end grid parameters

    return $content;
}

function formatCallRecordingTuple($value)
{
    $namefile = basename($value['recordingfile']);
    if ($namefile == 'deleted') {
        $rectype = _tr('Deleted');
    } else switch($namefile[0]){
        case 'O':  // FreePBX 2.8.1
        case 'o':  // FreePBX 2.11+
            $rectype = _tr("Outgoing");
            break;
        case 'g':  // FreePBX 2.8.1
        case 'r':  // FreePBX 2.11+
            $rectype = _tr("Group");
            break;
        case "q":
            $rectype = _tr("Queue");
            break;
        default :
            $rectype = _tr("Incoming");
            break;
    }
    return array(
        date('d M Y',strtotime($value['calldate'])),
        date('H:i:s',strtotime($value['calldate'])),
        isset($value['src']) ? $value['src'] : '',
        isset($value['dst']) ? $value['dst'] : '',
        SecToHHMMSS($value['duration']),
        $rectype,
        $namefile,
    );
}

function downloadFile($smarty, $module_name, $local_templates_dir, &$pDB, $pACL,
    $arrConf, $user, $extension, $esAdministrador)
{
    $record = getParameter("id");
    $namefile = getParameter('namefile');
    $pMonitoring = new paloSantoMonitoring($pDB);
    if(!$esAdministrador){
        if(!$pMonitoring->recordBelongsToUser($record, $extension)){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message", _tr("You are not authorized to download this file"));
            return reportMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
        }
    }
    $path_record = $arrConf['records_dir'];

    if (is_null($record) || !preg_match('/^[[:digit:]]+\.[[:digit:]]+$/', $record)) {
        // Missing or invalid uniqueid
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    // Check record is valid and points to an actual file
    $filebyUid = $pMonitoring->getAudioByUniqueId($record, $namefile);
    if (is_null($filebyUid) || count($filebyUid) <= 0) {
        // Uniqueid does not point to a record with specified file
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }
    $file = basename($filebyUid['recordingfile']);
    $path = $path_record.$file;
    if ($file == 'deleted') {
        // Specified file has been deleted
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }
    if (!file_exists($path)) {
    	// Queue recordings might lack an extension
        $arrData = glob("$path*");
        if (count($arrData) > 0) {
        	$path = $arrData[0];
            $file = basename($path);
        }
    }

    if (file_exists($path) && is_file($path)) {
    	$ok_path = $path;
    } else {
        $path2 = $path_record.getPathFile($file);
        if (file_exists($path2) && is_file($path2)) {
            $ok_path = $path2;
        } else {
            // Failed to find specified file
            Header('HTTP/1.1 404 Not Found');
            die("<b>404 "._tr("no_file")." </b>");
        }
    }

    // Set Content-Type according to file extension
    $contentTypes = array(
        'wav'   =>  'audio/wav',
        'gsm'   =>  'audio/gsm',
        'mp3'   =>  'audio/mpeg',
    );
    $extension = substr(strtolower($file), -3);
    if (!isset($contentTypes[$extension])) {
        // Unrecognized file extension
    	Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    // Actually open and transmit the file
    $fp = fopen($ok_path, 'rb');
    if (!$fp) {
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: wav file");
    header("Content-Type: " . $contentTypes[$extension]);
    header("Content-Disposition: attachment; filename=" . $file);
    header("Content-Transfer-Encoding: binary");
    header("Content-length: " . filesize($ok_path));
    fpassthru($fp);
    fclose($fp);
}

function record_format(&$pDB, $arrConf){
    $record = getParameter("id");
    $pMonitoring = new paloSantoMonitoring($pDB);

    $path_record = $arrConf['records_dir'];
    if (isset($record) && preg_match("/^[[:digit:]]+\.[[:digit:]]+$/",$record)) {

        $filebyUid   = $pMonitoring->getAudioByUniqueId($record);
        $file   = basename($filebyUid['recordingfile']);
        $path   = $path_record.$file;

        if($file[0] == "q"){// caso de archivos de colas no se tiene el tipo de archivo gsm, wav,etc
            $arrData  = glob("$path*");
            $path = isset($arrData[0])?$arrData[0]:$path;
        }

    // See if the file exists
        if ($file == 'deleted' || !is_file($path)) {
            return "";
        }

        if (file_exists($path) && is_file($path)) {
        	$ok_path = $path;
        } else {
        	$path2  = $path_record.getPathFile($file);
            if (file_exists($path2) && is_file($path2)) {
            	$ok_path = $path2;
            } else {
            	return '';
            }
        }

        $name = basename($ok_path);

    //$extension = strtolower(substr(strrchr($name,"."),1));
        $extension=substr(strtolower($name), -3);

    // This will set the Content-Type to the appropriate setting for the file
        $ctype ='';
        switch( $extension ) {

            case "mp3": $ctype="audio/mpeg"; break;
            case "wav": $ctype="audio/wav"; break;
            case "gsm": $ctype="audio/gsm"; break;
            // not downloadable
            default: $ctype=""; break ;
        }
    }
    return $ctype;
}

function display_record($smarty, $module_name, $local_templates_dir, &$pDB, $pACL, $arrConf, $user, $extension, $esAdministrador){
    $file = getParameter("id");
    $namefile = getParameter('namefile');
    $pMonitoring = new paloSantoMonitoring($pDB);
    $path_record = $arrConf['records_dir'];
    $sContenido="";
    if(!$esAdministrador){
        if(!$pMonitoring->recordBelongsToUser($file, $extension)){
            return _tr("You are not authorized to listen this file");
        }
    }
    $session_id = session_id();
    $ctype=record_format($pDB, $arrConf);
	$audiourl = construirURL(array(
	    'menu'             =>  $module_name,
	    'action'           =>  'download',
	    'id'               =>  $file,
	    'namefile'         =>  $namefile,
	    'rawmode'          =>  'yes',
	    'elastixSession'   =>  $session_id,
	));
    $sContenido=<<<contenido
<!DOCTYPE html>
<html>
<head><title>Elastix</title></head>
<body>
    <audio src="$audiourl" controls autoplay>
        <embed src="$audiourl" width="300" height="20" autoplay="true" loop="false" type="$ctype" />
    </audio>
    <br/>
</body>
</html>
contenido;
    return $sContenido;
}

function deleteRecord($smarty, $module_name, $local_templates_dir, &$pDB, $pACL, $arrConf, $user, $extension, $esAdministrador)
{
    if(!$esAdministrador){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message", _tr("You are not authorized to delete any records"));
        return reportMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
    }
    $pMonitoring = new paloSantoMonitoring($pDB);
    $path_record = $arrConf['records_dir'];
    foreach($_POST as $key => $values){
        if(substr($key,0,3) == "id_")
        {
            $ID = substr($key, 3);
            $ID = str_replace("_",".",$ID);
            $recordName = $pMonitoring->getRecordName($ID);
            $record = substr($recordName,6);
            $record = basename($record);
            $path   = $path_record.$record;
            $path2  = $path_record.getPathFile($record);
            if(is_file($path)){
                // Archivo existe. Se borra si se puede actualizar CDR
                if($pMonitoring->deleteRecordFile($ID))
                    unlink($path);
            }
            else if(is_file($path2)){
                // Archivo existe. Se borra si se puede actualizar CDR
                if($pMonitoring->deleteRecordFile($ID))
                    unlink($path2);
            }
            else {
                // Archivo no existe. Se actualiza CDR para mantener consistencia
                $pMonitoring->deleteRecordFile($ID);
            }
        }
    }

    $content = reportMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf, $user, $extension, $esAdministrador);
    return $content;
}

function SecToHHMMSS($sec)
{
    $HH = 0;$MM = 0;$SS = 0;
    $segundos = $sec;

    if( $segundos/3600 >= 1 ){ $HH = (int)($segundos/3600);$segundos = $segundos%3600;} if($HH < 10) $HH = "0$HH";
    if(  $segundos/60 >= 1  ){ $MM = (int)($segundos/60);  $segundos = $segundos%60;  } if($MM < 10) $MM = "0$MM";
    $SS = $segundos; if($SS < 10) $SS = "0$SS";

    return "$HH:$MM:$SS";
}

function getPathFile($file)
{
    $arrTokens = explode('-',$file);
    if (count($arrTokens) < 4) return '/'.$file;
    $fyear     = substr($arrTokens[3],0,4);
    $fmonth    = substr($arrTokens[3],4,2);
    $fday      = substr($arrTokens[3],6,2);
    return  "/$fyear/$fmonth/$fday/$file";
}

function createFieldFilter(){
    $arrFilter = array(
            "src"       => _tr("Source"),
            "dst"       => _tr("Destination"),
            "recordingfile" => _tr("Type"),
                    );

    $arrFormElements = array(
            "date_start"  => array(           "LABEL"                  => _tr("Start_Date"),
                                              "REQUIRED"               => "yes",
                                              "INPUT_TYPE"             => "DATE",
                                              "INPUT_EXTRA_PARAM"      => "",
                                              "VALIDATION_TYPE"        => "ereg",
                                              "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
            "date_end"    => array(           "LABEL"                  => _tr("End_Date"),
                                              "REQUIRED"               => "yes",
                                              "INPUT_TYPE"             => "DATE",
                                              "INPUT_EXTRA_PARAM"      => "",
                                              "VALIDATION_TYPE"        => "ereg",
                                              "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
            "filter_field" => array(          "LABEL"                  => _tr("Search"),
                                              "REQUIRED"               => "no",
                                              "INPUT_TYPE"             => "SELECT",
                                              "INPUT_EXTRA_PARAM"      => $arrFilter,
                                              "VALIDATION_TYPE"        => "text",
                                              "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array(          "LABEL"                  => "",
                                              "REQUIRED"               => "no",
                                              "INPUT_TYPE"             => "TEXT",
                                              "INPUT_EXTRA_PARAM"      => "",
                                              "VALIDATION_TYPE"        => "text",
                                              "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}


function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("action")=="display_record")
        return "display_record";
    else if(getParameter("submit_eliminar"))
        return "delete";
    else if(getParameter("action")=="download")
        return "download";
    else if(getParameter("action")=="view")   //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else
        return "report"; //cancel
}
?>
