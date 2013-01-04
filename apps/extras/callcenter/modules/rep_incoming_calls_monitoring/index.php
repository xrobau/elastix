<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2-3.1                                               |
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
  $Id: index.php,v 1.1.1.1 2009/07/27 09:10:19 dlopez Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
$base_dir=$_SERVER['DOCUMENT_ROOT'];
include_once "$base_dir/libs/xajax/xajax.inc.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoIncomingcallsmonitoring.class.php";

    // incluci� del xajax
    $xajax = new xajax();
    $xajax->waitCursorOff();
    $xajax->registerFunction("create_report");
    $xajax->processRequests();
    $content  = $xajax->printJavascript("libs/xajax/");

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);
//     $pDB = "";


    //actions
    $accion = getAction();

    switch($accion){
        default:
            $content .= 
                '<div id="body_report">'.
                reportIncomingcallsmonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang).
                '</div>';
            break;
    }
    return $content;
}

function reportIncomingcallsmonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pIncomingcallsmonitoring = new paloSantoIncomingcallsmonitoring($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");
    $action = getParameter("nav");
    $start  = getParameter("start");

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $totalIncomingcallsmonitoring = $pIncomingcallsmonitoring->ObtainNumIncomingcallsmonitoring($filter_field, $filter_value);

    $limit  = 20;
    $total  = $totalIncomingcallsmonitoring;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

    $oGrid->calculatePagination($action,$start);
    $offset = $oGrid->getOffsetValue();
    $end    = $oGrid->getEnd();
    $url    = "?menu=$module_name&filter_field=$filter_field&filter_value=$filter_value";

    $arrData = null;
    $arrResult =$pIncomingcallsmonitoring->ObtainIncomingcallsmonitoring($limit, $offset, $arrLang, $filter_field, $filter_value);

    if(is_array($arrResult) /*&& $total>0*/){
        foreach($arrResult as $key => $value){ 
	    $arrTmp[0] = $value['queue'];
	    $arrTmp[1] = isset($value['entered'])?$value['entered']:"0";
	    $arrTmp[2] = isset($value['answered'])?$value['answered']:"0";
	    $arrTmp[3] = isset($value['abandoned'])?$value['abandoned']:"0";
	    $arrTmp[4] = isset($value['waiting_calls'])?$value['waiting_calls']:"0";
	    $arrTmp[5] = isset($value['without_monitoring'])?$value['without_monitoring']:"0";
            $arrData[] = $arrTmp;
        }
    }


    $arrGrid = array("title"    => $arrLang["Incoming calls monitoring"],
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array(
			0 => array("name"      => $arrLang["Queue"],
                                   "property1" => ""),
			1 => array("name"      => $arrLang["Entered"],
                                   "property1" => ""),
			2 => array("name"      => $arrLang["Answered"],
                                   "property1" => ""),
			3 => array("name"      => $arrLang["Abandoned"],
                                   "property1" => ""),
			4 => array("name"      => $arrLang["Waiting calls"],
                                   "property1" => ""),
			5 => array("name"      => $arrLang["Without monitoring"],
                                   "property1" => ""),
                                        )
                    );


    //begin section filter
    $arrFormFilterIncomingcallsmonitoring = createFieldFilter($arrLang);
    $oFilterForm = new paloForm($smarty, $arrFormFilterIncomingcallsmonitoring);
//     $smarty->assign("SHOW", $arrLang["Show"]);
//     $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter
//     $oGrid->showFilter(trim($htmlFilter));


    $content = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    if (strpos($content, '<form') === FALSE)
        $content = "<form  method=\"POST\" style=\"margin-bottom:0;\" action=\"$url\">$sContenido</form>";
    $sReloadScript = <<<SCRIPT_RELOAD
<script>
function reload() {
    xajax_create_report();
    setTimeout("reload()",5000);
}
reload();
</script>
SCRIPT_RELOAD;
    $content = $sReloadScript.$content;

    return $content;
}


function createFieldFilter($arrLang){
    $arrFilter = array(
	    "queue" => $arrLang["Queue"],
	    "entered" => $arrLang["Entered"],
	    "answered" => $arrLang["Answered"],
	    "abandoned" => $arrLang["Abandoned"],
	    "waiting_calls" => $arrLang["Waiting calls"],
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => $arrLang["Search"],
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}


//FUNCIONES AJAX
function create_report() {
    $respuesta = new xajaxResponse();
    $module_name = get_module_name();
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";

    if (file_exists("$base_dir/$lang_file")) {
        include_once "$lang_file";
    } else {
        include_once "modules/$module_name/lang/en.lang";
    }
//  $respuesta->addAlert($lang_file);

    global $arrConf;
    global $smarty;
    global $arrLang;
    global $arrLangModule;
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $local_templates_dir=get_local_templates_dir();


    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $content = reportIncomingcallsmonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
    $respuesta->addAssign("body_report","innerHTML",$content);
    return $respuesta;
}
// FIN FUNCIONES AJAX

function get_local_templates_dir()
{
    global $arrConf;
    $module_name = get_module_name();
    //folder path for custom templates
    $base_dir=$_SERVER['DOCUMENT_ROOT'];
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    return "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
}

function get_module_name()
{
    return "rep_incoming_calls_monitoring";
}

if (!function_exists('getParameter')) {
function getParameter($parameter)
{
    if(isset($_POST[$parameter]))
        return $_POST[$parameter];
    else if(isset($_GET[$parameter]))
        return $_GET[$parameter];
    else
        return null;
}
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}?>