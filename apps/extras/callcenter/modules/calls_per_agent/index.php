<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:21 gcarrillo Exp $ */

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
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";
    require_once "libs/misc.lib.php";

    //Incluir librería de lenguaje
    load_language_module($module_name);

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoCallPerAgent.class.php";
    global $arrConf;
    global $arrLang;
    $arrCallsAgentTmp  = 0;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $pDB     = new paloDB($cadena_dsn);
    $arrData = array();
    $oCallsAgent = new paloSantoCallsAgent($pDB);

    $urlVars = array('menu' => $module_name);
    $smarty->assign("menu","calls_per_agent");
    $smarty->assign("Filter",_tr('Query'));

    $arrFormElements = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormElements);
    
    // Por omision las fechas toman el sgte. valor (la fecha de hoy)
    $date_start = date("Y-m-d") . " 00:00:00"; 
    $date_end   = date("Y-m-d") . " 23:59:59";
    $arrFilterExtraVars = null;
    $fieldPat = array();
    if(isset($_POST['filter'])) {
        if($oFilterForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $date_start = translateDate($_POST['date_start']) . " 00:00:00"; 
            $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
            if (!empty($_POST['field_pattern']))
                $fieldPat[$_POST['field_name']][] = $_POST['field_pattern'];
            if (!empty($_POST['field_pattern_1']))
                $fieldPat[$_POST['field_name_1']][] = $_POST['field_pattern_1'];
            $arrFilterExtraVars = array("date_start" => $_POST['date_start'], 
                                        "date_end" => $_POST['date_end'], 
                                        "field_name" => $_POST['field_name'], 
                                        "field_pattern" => $_POST['field_pattern'],
                                        "field_name_1" => $_POST['field_name_1'], "field_pattern_1" => $_POST['field_pattern_1']);
        } else {
            // Error
            $smarty->assign("mb_title", _tr("Validation Error"));
            $arrErrores=$oFilterForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
        }
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);

    } else if(isset($_GET['date_start']) AND isset($_GET['date_end'])) {
        $date_start = translateDate($_GET['date_start']) . " 00:00:00";
        $date_end   = translateDate($_GET['date_end']) . " 23:59:59";
        if (!empty($_GET['field_pattern']))
            $fieldPat[$_GET['field_name']][] = $_GET['field_pattern'];
        if (!empty($_GET['field_pattern_1']))
            $fieldPat[$_GET['field_name_1']][] = $_GET['field_pattern_1'];

        $arrFilterExtraVars = array("date_start" => $_GET['date_start'], "date_end" => $_GET['date_end']);
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_GET);
    } else {
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", 
                      array('date_start' => date("d M Y"), 'date_end' => date("d M Y"),'field_name' => 'agent','field_pattern' => '','field_name_1' => 'agent','field_pattern_1' => ''/*,'status' => 'ALL' */));
    }
    
    $bElastixNuevo = method_exists('paloSantoGrid','setURL');

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->enableExport();   // enable export.
    $oGrid->showFilter($htmlFilter); 

    $bExportando = $bElastixNuevo
    ? $oGrid->isExportAction()
    : ( (isset( $_GET['exportcsv'] ) && $_GET['exportcsv'] == 'yes') || 
        (isset( $_GET['exportspreadsheet'] ) && $_GET['exportspreadsheet'] == 'yes') || 
        (isset( $_GET['exportpdf'] ) && $_GET['exportpdf'] == 'yes')
      ) ;

    if (isset($fieldPat['type'])) $fieldPat['type'] = array_map('strtoupper', $fieldPat['type']);
    $arrCallsAgentTmp = $oCallsAgent->obtenerCallsAgent($date_start, $date_end, $fieldPat);
    if (!is_array($arrCallsAgentTmp)) {
        $smarty->assign(array(
            'mb_title'      =>  _tr('ERROR'),
            'mb_message'    =>  $oCallsAgent->errMsg,
        ));
    	$arrCallsAgentTmp = array();
    }
    $totalCallsAgents = count($arrCallsAgentTmp);
    $offset = 0;

    // Si se quiere avanzar a la sgte. pagina
    if($bElastixNuevo){
        $oGrid->setLimit($totalCallsAgents);
        $oGrid->setTotal($totalCallsAgents + 1);
        $offset = $oGrid->calculateOffset();
    }

    // Bloque comun
    $arrData = array();
    $sumCallAnswered = $sumDuration = $timeMayor = 0;
    foreach($arrCallsAgentTmp as $cdr) {
        $arrData[] = array(
            $cdr['agent_number'],
            htmlentities($cdr['agent_name'], ENT_COMPAT, 'UTF-8'),
            $cdr['type'],
            $cdr['queue'],
            $cdr['num_answered'],
            formatoSegundos($cdr['sum_duration']),
            formatoSegundos($cdr['avg_duration']),
            formatoSegundos($cdr['max_duration']),
        );

        $sumCallAnswered += $cdr['num_answered'];   // Total de llamadas contestadas
        $sumDuration += $cdr['sum_duration'];       // Total de segundos en llamadas
        $timeMayor = ($timeMayor < $cdr['max_duration']) ? $cdr['max_duration'] : $timeMayor;
    }
    $sTagInicio = (!$bExportando) ? '<b>' : '';
    $sTagFinal = ($sTagInicio != '') ? '</b>' : '';
    $arrData[] = array(
        $sTagInicio._tr('Total').$sTagFinal,
        '', '', '',
        $sTagInicio.$sumCallAnswered.$sTagFinal,
        $sTagInicio.formatoSegundos($sumDuration).$sTagFinal,
        $sTagInicio.formatoSegundos(($sumCallAnswered > 0) ? ($sumDuration / $sumCallAnswered) : 0).$sTagFinal,
        $sTagInicio.formatoSegundos($timeMayor).$sTagFinal,
    );

    // Construyo el URL base
    if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) && count($arrFilterExtraVars)>0) {
         $urlVars = array_merge($urlVars, $arrFilterExtraVars);
    }

    if($bElastixNuevo){
        $oGrid->setURL(construirURL($urlVars, array("nav", "start")));
        $oGrid->setData($arrData);
        $arrColumnas = array(_tr("No.Agent"), _tr("Agent"), _tr("Type"), _tr("Queue"),_tr("Calls answered"),_tr("Duration"),_tr("Average"),_tr("Call longest"));
        $oGrid->setColumns($arrColumnas);
        $oGrid->setTitle(_tr("Calls per Agent"));
        $oGrid->pagingShow(false); 
        $oGrid->setNameFile_Export(_tr("Calls per Agent"));
     
        $smarty->assign("SHOW", _tr("Show"));
        return $oGrid->fetchGrid();
    } else {
         $url = construirURL($urlVars, array("nav", "start"));
         $offset = 0;
         $total = count($arrData);
         $limit = $total;
         $arrGrid = array("title"    => _tr("Calls per Agent"),
                     "url"      => $url,
                     "icon"     => "images/user.png",
                     "width"    => "99%",
                     "start"    => ($total==0) ? 0 : $offset + 1,
                     "end"      => ($offset+$limit)<=$total ? $offset+$limit : $total,
                     "total"    => $total,
                     "columns"  => array(0 => array("name"      => _tr("No.Agent"),
                                                    "property" => ""),
                                         1 => array("name"      => _tr("Agent"),
                                                    "property" => ""),
                                         2 => array("name"	=> _tr("Type"),
                                         	     "property"	=> ""),
                                         3 => array("name"	=> _tr("Queue"),
                                                     "property"	=> ""),
                                         4 => array("name"	=> _tr("Calls answered"),
                                                     "property"	=> ""),
                                         5 => array("name"	=> _tr("Duration"),
                                                     "property"	=> ""),
                                         6 => array("name"	=> _tr("Average"),
                                         	     "property"	=> ""),
                                         7 => array("name"	=> _tr("Call longest"),
                                         	     "property"	=> ""),
                                        )
                    );
        if (isset( $_GET['exportpdf'] ) && $_GET['exportpdf'] == 'yes' && method_exists($oGrid, 'fetchGridPDF'))
            return $oGrid->fetchGridPDF($arrGrid, $arrData);
        if (isset( $_GET['exportspreadsheet'] ) && $_GET['exportspreadsheet'] == 'yes' && method_exists($oGrid, 'fetchGridXLS'))
            return $oGrid->fetchGridXLS($arrGrid, $arrData);
        if($bExportando){
            header("Cache-Control: private");
            header("Pragma: cache");
            header('Content-Type: application/octet-stream'); 
            header('Content-disposition: inline; filename="calls_per_agent.csv"');
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

function formatoSegundos($iSeg)
{
    $iSeg = (int)$iSeg;
    $iHora = $iMinutos = $iSegundos = 0;
    $iSegundos = $iSeg % 60; $iSeg = ($iSeg - $iSegundos) / 60;
    $iMinutos = $iSeg % 60; $iSeg = ($iSeg - $iMinutos) / 60;
    $iHora = $iSeg;
    return sprintf('%02d:%02d:%02d', $iHora, $iMinutos, $iSegundos);
}

function createFieldFilter()
{
    $arrFormElements = array(    "date_start"  => array("LABEL"                  => _tr('Start Date'),
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "DATE",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                                 "date_end"    => array("LABEL"                  => _tr("End Date"),
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "DATE",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                                 "field_name"  => array("LABEL"                  => _tr("Column"),
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "SELECT",
                                                        "MULTIPLE"               => NULL,
                                                        "SIZE"                   => NULL,
                                                        "INPUT_EXTRA_PARAM"      => array( "number"=> _tr("No.Agent"),
                                                                                           "queue"  => _tr("Queue"),
                                                                                           "type"   => _tr("Type")),
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^(number|queue|type)$"),
                                 "field_pattern" => array("LABEL"                  => _tr("Column"),
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "TEXT",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:alnum:]@_\.,/\-]+$"),

                                "field_name_1"  => array("LABEL"                  => _tr("Column"),
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "SELECT",
                                                        "MULTIPLE"               => NULL,
                                                        "SIZE"                   => NULL,
                                                        "INPUT_EXTRA_PARAM"      => array( "number"=> _tr("No.Agent"),
                                                                                            "queue"   => _tr("Queue"),
                                                                                            "type"    => _tr("Type")),
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^(number|queue|type)$"),
                                "field_pattern_1" => array("LABEL"                  => _tr("Column"),
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "TEXT",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:alnum:]@_\.,/\-]+$"),
                                 );
    return $arrFormElements;
}

?>