<?php
//bin/bash: indent: command not found
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
  $Id: new_campaign.php $ */

require_once "libs/paloSantoForm.class.php";
require_once "libs/misc.lib.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "modules/form_designer/libs/paloSantoDataForm.class.php";
require_once "libs/xajax/xajax.inc.php";

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
    load_language_module($module_name);

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;

    require_once "modules/$module_name/libs/paloSantoLoginLogout.class.php";
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $relative_dir_rich_text = "modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" . $arrConfig['AMPDBHOST']['valor'] . "/asterisk";
    $oDB = new paloDB($dsn);


    // se conecta a la base
    $pDB = new paloDB($arrConf["cadena_dsn"]);
    if (!is_object($pDB->conn) || $pDB->errMsg!="") {
        $smarty->assign("mb_message", _tr("Error when connecting to database")." ".$pDB->errMsg);
    }
    
    $htmlFilter = "";

    $bElastixNuevo = method_exists('paloSantoGrid','setURL');

    $oGrid = new paloSantoGrid($smarty);

    $oGrid->showFilter($htmlFilter); 

    $bExportando = $bElastixNuevo
        ? $oGrid->isExportAction()
        : ( (isset( $_GET['exportcsv'] ) && $_GET['exportcsv'] == 'yes') || 
            (isset( $_GET['exportspreadsheet'] ) && $_GET['exportspreadsheet'] == 'yes') || 
            (isset( $_GET['exportpdf'] ) && $_GET['exportpdf'] == 'yes')
          ) ;

    if($bExportando) {

        if(empty($_GET['txt_fecha_init'])) {
            $fecha_init = date("Y-m-d") . " 00:00:00"; 
        } else {
            $fecha_init = translateDate($_GET['txt_fecha_init']) . " 00:00:00";
        }
        if(empty($_GET['txt_fecha_end'])) { 
            $fecha_end = date("Y-m-d") . " 23:59:59"; 
        } else {
            $fecha_end  = translateDate($_GET['txt_fecha_end']) . " 23:59:59";
        }

        $arrFilterExtraVars = array("cbo_tipos" => $_GET['cbo_tipos'],
                                    "txt_fecha_init" => $fecha_init,
                                    "txt_fecha_end" => $fecha_end,
                                     );

    }
    
    if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) and count($arrFilterExtraVars)>0) {
	$url = construirURL($arrFilterExtraVars); 
    } else {
	$url = construirURL(); 
    }

    $oGrid = new paloSantoGrid($smarty);
    $arrGrid = array();
    $arrData = array();

    //llamamos a funcion que construye la vista
    $contenidoModulo = listadoLoginLogout($pDB, $smarty, $module_name, $local_templates_dir,$oGrid,$arrGrid,$arrData,$bElastixNuevo,$bExportando);
    return $contenidoModulo;
    
}


//funcion que construye la vista del reporte
function listadoLoginLogout($pDB, $smarty, $module_name, $local_templates_dir,&$oGrid,&$arrGrid,&$arrData, $bElastixNuevo, $bExportando) 
{
    $arrData = array();
    $oCalls = new paloSantoLoginLogout($pDB);
    $fecha_init = date("d M Y");
    $fecha_end = date("d M Y");


    // preguntamos por el TIPO del filtro (Entrante/Saliente)
    if (!isset($_POST['cbo_tipos']) || $_POST['cbo_tipos']=="") {
        $_POST['cbo_tipos'] = "D";//por defecto las consultas seran de Llamadas Entrantes
    }

    $tipo = 'D';
    if(isset($_POST['cbo_tipos']))
        $tipo = $_POST['cbo_tipos'];


       //validamos la fecha
    if( isset($_POST['txt_fecha_init']) && isset($_POST['txt_fecha_end'])) {
        $fecha_init_actual = $_POST['txt_fecha_init'];
        $fecha_end_actual = $_POST['txt_fecha_end'];
    }elseif(isset($_GET['txt_fecha_init']) && isset($_GET['txt_fecha_end'])){
        $fecha_init_actual = $_GET['txt_fecha_init'];
        $fecha_end_actual = $_GET['txt_fecha_end'];
    } 
    else {
        $fecha_init_actual  = $fecha_init;
        $fecha_end_actual  = $fecha_end;
    }


    $sValidacion = "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$";
    if( isset($_POST['submit_fecha']) || isset($_POST['cbo_tipos'] )) {
        // si se ha presionado el boton pregunto si hay una fecha de inicio elegida
        if ( (isset( $_POST['txt_fecha_init']) && $_POST['txt_fecha_init']!="") && (isset( $_POST['txt_fecha_end']) && $_POST['txt_fecha_end']!="") ) {
            // sihay una fecha de inicio pregunto si es valido el formato de la fecha
            if ( ereg( $sValidacion , $_POST['txt_fecha_init'] ) && ereg( $sValidacion , $_POST['txt_fecha_end'] )) {
                // si el formato es valido procedo a convertir la fecha en un arreglo que contiene 
                // el anio , mes y dia seleccionados
                $fecha_init = $fecha_init_actual;//$_POST['txt_fecha_init'];
                $fecha_end = $fecha_end_actual;
                $arrFecha_init = explode('-',translateDate($fecha_init));
                $arrFecha_end = explode('-',translateDate($fecha_end));
            }else {
                // si la fecha esta en un formato no valido se envia un mensaje de error
                $smarty->assign("mb_title", _tr("Error"));
                $smarty->assign("mb_message", _tr("Debe ingresar una fecha valida"));
            }


        //PRUEBA

            $arrFilterExtraVars = array("cbo_tipos" => $tipo,
                                      "txt_fecha_init" => $_POST['txt_fecha_init'], 
                                      "txt_fecha_end" => $_POST['txt_fecha_end'],
                                    );
        //PRUEBA
        } elseif( (isset( $_GET['txt_fecha_init']) && $_GET['txt_fecha_init']!="") && (isset( $_GET['txt_fecha_end']) && $_GET['txt_fecha_end']!="")){
            if ( ereg( $sValidacion , $_GET['txt_fecha_init'] ) && ereg( $sValidacion , $_GET['txt_fecha_end'] )) {
                // si el formato es valido procedo a convertir la fecha en un arreglo que contiene 
                // el anio , mes y dia seleccionados
                $fecha_init = $fecha_init_actual;//$_POST['txt_fecha_init'];
                $arrFecha_init = explode('-',translateDate($fecha_init));

                $fecha_end = $fecha_end_actual;//$_POST['txt_fecha_init'];
                $arrFecha_end = explode('-',translateDate($fecha_end));
            }else {
                // si la fecha esta en un formato no valido se envia un mensaje de error
                $smarty->assign("mb_title", _tr("Error"));
                $smarty->assign("mb_message", _tr("Debe ingresar una fecha valida"));
            }

            $tipo =  $_GET['cbo_tipos'];

            $arrFilterExtraVars = array("cbo_tipos" => $_GET['cbo_tipos'],
                                    "txt_fecha_init" => $_GET['txt_fecha_init'],
                                    "txt_fecha_end" => $_GET['txt_fecha_end'],
                                     );

        }
        elseif(!isset($fecha_init) && !isset($fecha_end)) {
            // si se ha presionado el boton para listar por fechas, y no se ha ingresado una fecha
            // se le muestra al usuario un mensaje de error
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", _tr("Debe ingresar una fecha inicio/fin"));
        }
    }

//para el pagineo
   // LISTADO
    $limit =50;
    $offset = 0;
    $arrCallsTmp  = $oCalls->getRegistersLoginLogout($tipo,translateDate($fecha_init),translateDate($fecha_end),$limit, $offset);
    $totalCalls  = $arrCallsTmp['NumRecords'];
    
    if($bElastixNuevo){
        $oGrid->setLimit($limit);
        $oGrid->setTotal($totalCalls);
        $offset = $oGrid->calculateOffset();
     } else {
        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="end") {
            // Mejorar el sgte. bloque.
                if(($totalCalls%$limit)==0) {
                    $offset = $totalCalls - $limit;
                } else {
                    $offset = $totalCalls - $totalCalls%$limit;
                }
            
        }
        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="next") {
            $offset = $_GET['start'] + $limit - 1;
        }

        // Si se quiere retroceder
        if(isset($_GET['nav']) && $_GET['nav']=="previous") {
            $offset = $_GET['start'] - $limit - 1;
        }
    }

    // Construyo el URL base
    $url = array('menu' => $module_name);
    if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) && count($arrFilterExtraVars)>0) {
        $url = array_merge($url, $arrFilterExtraVars);
    }
    $url = construirURL($url, array("nav", "start")); 

//fin de pagineo

    //llamamos  a la función que hace la consulta  a la base según los criterios de búsqueda
    $arrCalls = $oCalls->getRegistersLoginLogout($tipo,translateDate($fecha_init),translateDate($fecha_end), $limit, $offset);

    //numero de registros
    $arrCalls_1 = $oCalls->getRegistersLoginLogout($tipo,translateDate($fecha_init),translateDate($fecha_end),NULL, $offset);

    $end = $arrCalls_1['NumRecords'];
//Llenamos el contenido de las columnas
    $arrTmp    = array();
    $sTagInicio = (!$bExportando) ? '<b>' : '';
    $sTagFinal = ($sTagInicio != '') ? '</b>' : '';
//print_r($arrCalls);
    if (is_array($arrCalls)) {
        $sumTimeLogin = $sumTimeCalls = 0;
        foreach($arrCalls['Data'] as $intervalo=>$calls) {
            $arrTmp[0] = $calls['number'];
	    $arrTmp[1] = $calls['name'];
	    $arrTmp[2] = $calls['datetime_init'];
	    $arrTmp[3] = $calls['estado']=='En linea'? $sTagInicio.$calls['datetime_end'].$sTagFinal:$calls['datetime_end'];
	    $arrTmp[4] = format_time($calls['total_sesion']);
	    $arrTmp[5] = format_time($calls['total_sumas_in_out']);
	    $arrTmp[6] = number_format($calls['service'],2);
	    $arrTmp[7] = $calls['estado'];
            $arrData[] = $arrTmp;
            
            $sumTimeLogin += $calls['total_sesion'];
            $sumTimeCalls += $calls['total_sumas_in_out'];
        }

        $arrTmp[0] = $sTagInicio._tr("Total").$sTagFinal;
        $arrTmp[1] = "";
        $arrTmp[2] = "";
        $arrTmp[3] = "";
        $arrTmp[4] = $sTagInicio.format_time($sumTimeLogin).$sTagFinal;
        $arrTmp[5] = $sTagInicio.format_time($sumTimeCalls).$sTagFinal;
        $arrTmp[6] = "";
        $arrTmp[7] = "";
        $arrData[] = $arrTmp;
    }
    $tipos = array("D"=>_tr("Detallado"), "G"=>_tr("General"));
    $combo_tipos = "<select name='cbo_tipos' id='cbo_tipos' onChange='submit();'>".combo($tipos,$_POST['cbo_tipos'])."</select>";

     $oGrid->showFilter( insertarCabeceraCalendario()."

            <table width='100%' border='0'>
                <tr>
                    <td align='left'>
                        <table>
                        <tr>
                            <td class='letra12'>
                                "._tr("Date Init")."
                                <span  class='required'>*</span>
                            </td>
                            <td>
                                ".insertarDateInit($fecha_init_actual)."
                            </td>
                            <td class='letra12'>
                                "._tr("Date End")."
                                <span  class='required'>*</span>
                            </td>
                            <td>
                                ".insertarDateEnd($fecha_end_actual)."
                            </td>
                            <td class='letra12'>
                                &nbsp;
                            </td>
                            <td class='letra12' align='left'>"._tr("Tipo")."</td>
                            <td>$combo_tipos</td>
                            <td>
                                <input type='submit' name='submit_fecha' value="._tr("Find")." class='button'>
                            </td>
                        </tr>

                        </table>
                    </td>
                </tr>
            </table>

        ");
    $oGrid->enableExport();   // enable export.
    if($bElastixNuevo){
        $oGrid->setURL($url);
        $oGrid->setData($arrData);
        $arrColumnas = array(_tr("Agente"), _tr("Nombre"), _tr("Login"), _tr("Logout"),_tr("Total Login"),_tr("Tiempo en Llamadas"),_tr("Service(%)"),_tr("Estado"));
        $oGrid->setColumns($arrColumnas);
        $oGrid->setTitle(_tr("Login Logout"));
        $oGrid->pagingShow(true); 
        $oGrid->setNameFile_Export(_tr("Login Logout"));
     
        $smarty->assign("SHOW", _tr("Show"));
        return $oGrid->fetchGrid();
     } else {
            global $arrLang;

            $offset = 0;
            $total = count($arrCalls['Data']) + 1;
            $limit = $total;
            //Llenamos las cabeceras
            $arrGrid = array("title"    => _tr("Login Logout"),
                "url"      => $url,
                "icon"     => "images/list.png",
                "width"    => "99%",
                "start"    => ($end==0) ? 0 : $offset + 1,
                "end"      => ($offset+$limit)<=$end ? $offset+$limit : $end,
                "total"    => $end,
                "columns"  => array(0 => array("name"      => _tr("Agente"),
                                            "property1" => ""),
                                    1 => array("name"      => _tr("Nombre"),
                                            "property1" => ""),
                                    2 => array("name"      => _tr("Login"), 
                                            "property1" => ""),
                                    3 => array("name"      => _tr("Logout"),
                                            "property1" => ""),
                                    4 => array("name"      => _tr("Total Login"),
                                            "property1" => ""),
                                    5 => array("name"      => _tr("Tiempo en Llamadas"),
                                            "property1" => ""),
                                    6 => array("name"      => _tr("Service(%)"), 
                                            "property1" => ""),
                                    7 => array("name"      => _tr("Estado"), 
                                            "property1" => ""),
        
                                ));
            if (isset( $_GET['exportpdf'] ) && $_GET['exportpdf'] == 'yes' && method_exists($oGrid, 'fetchGridPDF'))
                return $oGrid->fetchGridPDF($arrGrid, $arrData);
            if (isset( $_GET['exportspreadsheet'] ) && $_GET['exportspreadsheet'] == 'yes' && method_exists($oGrid, 'fetchGridXLS'))
                return $oGrid->fetchGridXLS($arrGrid, $arrData);
            if($bExportando){
                
                    header("Cache-Control: private");
                    header("Pragma: cache");
                    header('Content-Type: application/octec-stream');
                    $title = "\"".$fecha_init."-".$fecha_end.".csv\"";
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

/*    Esta funcion inserta el codigo necesario para visualizar el control fecha inicio
*/
function insertarDateInit($fecha_init) {
    return 
    " <input style='width: 10em; color: #840; background-color: #fafafa; border: 1px solid #999999;text-align: center' name='txt_fecha_init' value='{$fecha_init}' id='f-calendar-field-1' type='text' editable='false' class='button'/> "
    .
    insertarCalendario(1);
}

/*
    Esta funcion inserta el codigo necesario para visualizar el control fecha fin
*/
function insertarDateEnd($fecha_end) {
    return 
    " <input style='width: 10em; color: #840; background-color: #fafafa; border: 1px solid #999999;text-align: center' name='txt_fecha_end' value='{$fecha_end}' id='f-calendar-field-2' type='text' editable='false' class='button'/> "
    .
    insertarCalendario(2);
}

/*
    Esta funcion inserta el codigo necesario para visualizar y utilizar un calendario par escoger
    una fecha determinada.
*/
function insertarCalendario($index) {

    return 
    "<a href='#' id='f-calendar-trigger-$index'>
        <img align='middle' border='0' src='/libs/js/jscalendar/img.gif' alt='' />
    </a>
    
    <script type='text/javascript'>
        Calendar.setup(
            {
                'ifFormat':'%d %b %Y',
                'daFormat':'%Y-%m-%d',
                'firstDay':1,
                'showsTime':true,
                'showOthers':true,
                'timeFormat':24,
                'inputField':'f-calendar-field-$index',
                'button':'f-calendar-trigger-$index'
            }
        );
    </script> " ;
}

/*
    Esta funcion inserta las dependencias necesarias para el calendario
*/
function insertarCabeceraCalendario() {

    return 
    "<link rel='stylesheet' type='text/css' media='all' href='/libs/js/jscalendar/calendar-win2k-2.css' />
        <script type='text/javascript' src='/libs/js/jscalendar/calendar_stripped.js'></script>
        <script type='text/javascript' src='/libs/js/jscalendar/lang/calendar-en.js'></script>
        <script type='text/javascript' src='/libs/js/jscalendar/calendar-setup_stripped.js'></script>
    ";
}

function format_time($iSec)
{
	$iMin = ($iSec - ($iSec % 60)) / 60; $iSec %= 60;
    $iHora =  ($iMin - ($iMin % 60)) / 60; $iMin %= 60;
    return sprintf('%02d:%02d:%02d', $iHora, $iMin, $iSec);
}

?>