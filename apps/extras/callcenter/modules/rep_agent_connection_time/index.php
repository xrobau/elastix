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
include_once "libs/paloSantoQueue.class.php";

if (!function_exists('_tr')) {
    function _tr($s)
    {
        global $arrLang;
        return isset($arrLang[$s]) ? $arrLang[$s] : $s;
    }
}
if (!function_exists('load_language_module')) {
    function load_language_module($module_id, $ruta_base='')
    {
        $lang = get_language($ruta_base);
        include_once $ruta_base."modules/$module_id/lang/en.lang";
        $lang_file_module = $ruta_base."modules/$module_id/lang/$lang.lang";
        if ($lang != 'en' && file_exists("$lang_file_module")) {
            $arrLangEN = $arrLangModule;
            include_once "$lang_file_module";
            $arrLangModule = array_merge($arrLangEN, $arrLangModule);
        }

        global $arrLang;
        global $arrLangModule;
        $arrLang = array_merge($arrLang,$arrLangModule);
    }
}
function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoReporteGeneraldeTiempoConexionAgentesPorDia.class.php";
    include_once "libs/paloSantoConfig.class.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //conexion resource
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);
    $dsnAsteriskCdr = $arrConfig['AMPDBENGINE']['valor']."://".
                      $arrConfig['AMPDBUSER']['valor']. ":".
                      $arrConfig['AMPDBPASS']['valor']. "@".
                      $arrConfig['AMPDBHOST']['valor']."/asterisk";

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $pDB_asterisk = new paloDB($dsnAsteriskCdr);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];


    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        default:
            $content = reportReporteGeneraldeTiempoConexionAgentesPorDia($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $pDB_asterisk);
            break;
    }
    return $content;
}

function reportReporteGeneraldeTiempoConexionAgentesPorDia($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, &$pDB_asterisk)
{
    $pReporteGeneraldeTiempoConexionAgentesPorDia = new paloSantoReporteGeneraldeTiempoConexionAgentesPorDia($pDB);

    //palosanto se obtiene el arreglo con las colas para mostrarlas en el filtro
    $arrQueue = getQueue($pDB, $pDB_asterisk);

//palosanto
    // valores del filtro cola y fechas
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");
    $date_from = getParameter("date_from");
    $date_to = getParameter("date_to");
    //detallado o general
    $filter_field_tipo = getParameter("filter_field_tipo");
    $filter_value_tipo = getParameter("filter_value_tipo");

    // si la fecha no est�seteada en el filtro
    $_POST["date_from"] = isset($date_from)?$date_from:date("d M Y");
    $_POST["date_to"] = isset($date_to)?$date_to:date("d M Y");
    $date_from = isset($date_from)&&$date_from!=""?date('Y-m-d',strtotime($date_from)):date("Y-m-d");
    $date_to = isset($date_to)&&$date_to!=""?date('Y-m-d',strtotime($date_to)):date("Y-m-d");

    // para setear la cola la primera vez
    $filter_value = getParameter("filter_value");
    if (!isset($filter_value)) {
        $queue = array_shift(array_keys($arrQueue));
        $_POST["filter_value"] = $queue;
        $_GET["filter_value"] = $queue;
        $filter_value = $queue;
    }
    //validacion para que los filtros se queden seteados con el valor correcto, correccion de bug que se estaba dando en caso de pagineo
    $_POST["filter_value"] = $filter_value;
    $_POST["filter_value_tipo"] = $filter_value_tipo;

//palosanto fin

    
 
    //begin grid parameters
    $bElastixNuevo = method_exists('paloSantoGrid','setURL');
    $oGrid  = new paloSantoGrid($smarty);
    
    $oGrid->enableExport();
    $bExportando = $bElastixNuevo
        ? $oGrid->isExportAction()
        : (isset( $_GET['exportcsv'] ) && $_GET['exportcsv'] == 'yes');

        //begin section filter
    $arrFormFilterReporteGeneraldeTiempoConexionAgentesPorDia = createFieldFilter($arrQueue);
    $oFilterForm = new paloForm($smarty, $arrFormFilterReporteGeneraldeTiempoConexionAgentesPorDia);
    $smarty->assign("SHOW", _tr("Show"));

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $totalReporteGeneraldeTiempoConexionAgentesPorDia = $pReporteGeneraldeTiempoConexionAgentesPorDia->ObtainNumReporteGeneraldeTiempoConexionAgentesPorDia($filter_field, $filter_value, $filter_field_tipo, $filter_value_tipo, $date_from, $date_to);

    $limit  = 20;
    $total  = $totalReporteGeneraldeTiempoConexionAgentesPorDia;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    
    if($bElastixNuevo)
        $offset = $oGrid->calculateOffset();
    else{
        $action = getParameter("nav");
        $start  = getParameter("start");
        $oGrid->calculatePagination($action,$start);
        $offset = $oGrid->getOffsetValue();
        $end    = $oGrid->getEnd();
    }

    $url = array(
            "menu"               => $module_name,
            "filter_field"       => $filter_field,
            "filter_value"       => $filter_value,
            "filter_field_tipo"  => $filter_field_tipo,
            "filter_value_tipo"  => $filter_value_tipo,
            "date_from"          => $date_from,
            "date_to"            => $date_to);
    
//palosanto le enviamos la cola
     $arrData = null;
    $arrResult =$pReporteGeneraldeTiempoConexionAgentesPorDia->ObtainReporteGeneraldeTiempoConexionAgentesPorDia($limit, $offset, $filter_field, $filter_value,  $filter_field_tipo, $filter_value_tipo, $date_from, $date_to);
    $oGrid->showFilter($htmlFilter);
    $sTagInicio = (!$bExportando) ? '<b>' : '';
    $sTagFinal = ($sTagInicio != '') ? '</b>' : '';
    $sTagInicio2 = (!$bExportando) ? '<center>' : '';
    $sTagFinal2 = ($sTagInicio != '') ? '</center>' : '';
    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){
	    $arrTmp[0] = $value['number_agent'];
	    $arrTmp[1] = $value['name'];
	    $arrTmp[2] = $value['first_conecction'];
	    $arrTmp[3] = ($value['last_conecction']=='-'?$sTagInicio2.$sTagInicio.$value['last_conecction'].$sTagFinal.$sTagFinal2:$value['last_conecction']);
            $arrTmp[4] = $value['tiempo_total_sesion'];
	    $arrTmp[5] = is_null($value['tiempo_llamadas'])?'0':$value['tiempo_llamadas'];
	    $arrTmp[6] = number_format($value['porcentaje_servicio'],2);
	    $arrTmp[7] = $value['estado'];
            $arrData[] = $arrTmp;
        }
    }
        if($bElastixNuevo){
            $oGrid->setURL($url);
            $oGrid->setData($arrData);
            $arrColumnas = array(_tr("Number Agent"), _tr("Agent Name"), _tr("First Conecction"), _tr("Last Conecction"),_tr("Total time of session"),_tr("Time Total Calls"),_tr("Service %"),_tr("Status"));
            $oGrid->setColumns($arrColumnas);
            $oGrid->setTitle(_tr("Reporte General de Tiempo Conexion Agentes Por Dia"));
            $oGrid->pagingShow(true); 
            $oGrid->setNameFile_Export(_tr("Reporte General de Tiempo Conexion Agentes Por Dia"));
            return $oGrid->fetchGrid();
        } else {
            global $arrLang;

            $offset = 0;
            $limit = $total;
            $url = construirURL($url, array('nav', 'start'));
            $arrGrid = array("title"    => _tr("Reporte General de Tiempo Conexion Agentes Por Dia"),
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array(
			0 => array("name"      => _tr("Number Agent"),
                                   "property1" => ""),
			1 => array("name"      => _tr("Agent Name"),
                                   "property1" => ""),
			2 => array("name"      => _tr("First Conecction"),
                                   "property1" => ""),
			3 => array("name"      => _tr("Last Conecction"),
                                   "property1" => ""),
			4 => array("name"      => _tr("Total time of session"),
                                   "property1" => ""),
                        5 => array("name"      => _tr("Time Total Calls"),
                                   "property1" => ""),
			6 => array("name"      => _tr("Service %"),
                                   "property1" => ""),
			7 => array("name"      => _tr("Status"),
                                   "property1" => ""),
                                        )
                    );
            if($bExportando){
                    $fechaActual = date("d M Y");
                    header("Cache-Control: private");
                    header("Pragma: cache");
                    header('Content-Type: application/octec-stream');
                    $title = "\"".$fechaActual.".csv\"";
                    header("Content-disposition: inline; filename={$title}");
                    header('Content-Type: application/force-download');
            }
            if ($bExportando)
                return $oGrid->fetchGridCSV($arrGrid, $arrData);
            $sContenido = $oGrid->fetchGrid($arrGrid, $arrData, $arrLang);
            if (strpos($sContenido, '<form') === FALSE)
                $sContenido = "<form  method=\"POST\" style=\"margin-bottom:0;\" action=\"$url\">$sContenido</form>";
            return $sContenido;
        }
}

function createFieldFilter($arrQueue){
    $arrFilter = array(
	    "general" => _tr("General"),
	    "detallado" => _tr("Details"),
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Queue"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "text",
                                    "INPUT_EXTRA_PARAM"      => "no",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),

            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrQueue,
                                    "VALIDATION_TYPE"        => "",
                                    "VALIDATION_EXTRA_PARAM" => ""),

//palosanto para detallado y general
            "filter_field_tipo" => array("LABEL"                  => _tr("Type"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "text",
                                    "INPUT_EXTRA_PARAM"      => "no",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),

            "filter_value_tipo" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "",
                                    "VALIDATION_EXTRA_PARAM" => ""),

//palosanto fecha

            "date_from"    => array("LABEL"                  => _tr("Start date"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "DATE",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "ereg",
                                    "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),


            "date_to"      => array("LABEL"                  => _tr("End date"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "DATE",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "ereg",
                                    "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                    );

    return $arrFormElements;
}

//palosanto para obteer colas
function getQueue($pDB, $pDB_asterisk){
    $arrQueue=array();
    $oQueue  = new paloQueue($pDB_asterisk);
    $PBXQueues = $oQueue->getQueue();
    if (is_array($PBXQueues)) {
        foreach($PBXQueues as $key => $value) {
            $query = "SELECT id, queue from queue_call_entry where queue='".$value[0]."'";
            $result=$pDB->getFirstRowQuery($query, true);
            if (is_array($result) && count($result)>0) {
                $arrQueue[$result['id']] =  $result['queue'];
            }
        }
    }
    return $arrQueue;
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
