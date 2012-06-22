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
  | Autor: Carlos Barcos
  | Fecha: 2007-11-19
*/

require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoDB.class.php";
require_once "libs/paloSantoGrid.class.php";
require_once "libs/misc.lib.php";

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
function _moduleContent(&$smarty,$module_name) {
    require_once "modules/$module_name/configs/default.config.php";
    require_once "modules/$module_name/libs/paloSantoReportsCalls.class.php";
    // obtengo la ruta del template a utilizar para generar el filtro.
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($config['templates_dir']))?$config['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$config['theme'];

    load_language_module($module_name);    

    // se crea el objeto conexion a la base de datos
    $pDB = new paloDB($cadena_dsn);
    // valido lacreacion del objeto conexion, presentando un mensaje deerror si es invalido.

    $bElastixNuevo = method_exists('paloSantoGrid','setURL');
    $url = array('menu' => $module_name);
    $htmlFilter="";
    $oGrid = new paloSantoGrid($smarty);
    $bExportando = $bElastixNuevo
        ? $oGrid->isExportAction()
        : (isset( $_GET['exportcsv'] ) && $_GET['exportcsv'] == 'yes');

    if (!is_object($pDB->conn) || $pDB->errMsg!="") {
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", _tr("Error when connecting to database")." ".$pDB->errMsg);
    // sio el objeto coenxion a la base no tiene problemas, consulto si se han seteado las variables GET.
    }elseif($bExportando) {
        if(empty($_GET['txt_fecha_init'])) {
            $fecha_actual_init = date("d M Y") ; 
        } else {
            $fecha_actual_init = $_GET['txt_fecha_init'];
        }
        if(empty($_GET['txt_fecha_end'])) { 
            $fecha_actual_end = date("d M Y"); 
        } else {
            $fecha_actual_end = $_GET['txt_fecha_end'];
        }
    // sino hay variables GET seteadas....
    } else {
        // creo un arreglo con la informacion necesario para el filtro que se desea definir.
        $arrFormElements = createFieldFilter();
        // obtengo la fecha actual del sistema.
        $fecha_actual_init = date("d M Y"); 
        $fecha_actual_end  = date("d M Y");
        // nombre del boton que me permitira enviar los valores del formulario.
        $smarty->assign("btn_consultar",_tr('Find'));
        // nombre del modulo actual.
        $smarty->assign("module_name",$module_name);
        // creo un objeto paloForm para crear el filtro del formulario.
        $oFilterForm = new paloForm($smarty, $arrFormElements);

        // valido si se ha presionado el boton "Consultar", cuyo name es "submit_fecha".
        if(isset($_POST['submit_fecha'])) {
            // valido la informacion obtenida del formulario.
            if($oFilterForm->validateForm($_POST)) {
                // si la informacion es correcta  procedo a procesar los datos,
                // en este caso la fecha del reporte deseado es asignada a una variable.
                $fecha_actual_init = $_POST['txt_fecha_init']; 
                $fecha_actual_end  = $_POST['txt_fecha_end']; 
                // Envio al arreglo la fecha obtenida del formulario.
                // txt_fecha es el nombre del campo de texto en el quese guarda la fecha
                $arrFilterExtraVars = array(
                                            "txt_fecha_init" => $fecha_actual_init,
                                            "txt_fecha_end"  => $fecha_actual_end,
                                           );
            } else {
                // si la informacion es invalida presento un mensaje de error con la cadena "Error de validacion"
                // dependiendo del idioma.
                $smarty->assign("mb_title", _tr("Validation Error"));
                // en este arreglo se guarada los posibles errores de validacion generados.
                $arrErrores=$oFilterForm->arrErroresValidacion;
                // cadena que almacena el mensaje de error a mostrarse en la pantalla.
                $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br>";
                // se recorre el arreglo para revisar todos los errores encontrados en la informacion del formulario
                foreach($arrErrores as $k=>$v) {
                    // se concatena los mensajes de error encontrados.
                    $strErrorMsg .= "$k, ";
                }
                $strErrorMsg .= "";
                // se presenta la cadena de error en la pantalla.
                $smarty->assign("mb_message", $strErrorMsg);
            }
            // se asigna el template elegido , asi como tambien la variable super global $_POST, al filtro.
            $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/form.tpl", "", $_POST);
        // si existe una fecha en el GET...
        }else if( isset( $_GET['txt_fecha_init']) && isset( $_GET['txt_fecha_end'])) {
                // se toma la fecha del GET
                $fecha_actual_init = $_GET['txt_fecha_init'];
                $fecha_actual_end  = $_GET['txt_fecha_end'];
                // envio la fecha obtenida.  txt_fecha es el nombre del campo de texto en el quese guarda la fecha
                $arrFilterExtraVars = array(
                                            "txt_fecha_init" => $_GET['txt_fecha_init'],
                                            "txt_fecha_end"  => $_GET['txt_fecha_end'],
                                           );
                // seteo el template elegido junto con la variable GET que contine los datos del formulario(txt_fecha).
                $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/form.tpl", "", $_GET);
            // sino se ha presionado el boton Consultar y no se ha clickado en el enlace "Export".
        } else {
            // asigno el template deseado, y obtengo la fecha actual del sistema, y se la envio junto al template
            // en un array asociativo .
            // txt_fecha es el nombre del campo de texto en el quese guarda la fecha.
            $htmlFilter = $contenidoModulo= $oFilterForm->fetchForm("$local_templates_dir/form.tpl", "", 
                                                                        array( 
                                                                                'txt_fecha_init' => date("d M Y") ,
                                                                                'txt_fecha_end'  => date("d M Y") ,
                                                                        ) 
                                                                   );
        }
        // genero la url
        if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) and count($arrFilterExtraVars)>0) {
            // esta url contiene la informacion de lso campos del formulario pasados como GET,
            // a traves de un enlace.
            $url = array_merge($url, $arrFilterExtraVars);
        }
        
    } 
    $url = construirURL($url, array("nav", "start")); 


    // creo el objeto $oReportsBreak, que me ayudara a construir el reporte.
    $oReportsCalls  = new paloSantoReportsCalls($pDB);
    // valido la creacion del objeto
    if( !$oReportsCalls ) {
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", _tr("Error when creating object paloSantoReportsCalls"));
    }else {
        // creo un arreglo para el grid
        $arrGrid = array();
        // envio el arreglo por referencia a la funcion para que esta se encargue de defnirlo. La funcion 
        // me retorna un arreglo con la informacion del reporte y ha generado el grid, 
        // el cual es devuelto por referencia.
        $oGrid = new paloSantoGrid($smarty);
        $contenido = generarReporte($smarty,$fecha_actual_init,$fecha_actual_end,$oReportsCalls,$arrGrid,$bElastixNuevo,$bExportando,$oGrid,$url,$htmlFilter);
        // creo el objeto GRID
        // habilito la opcion Export con la que se procedera a exportar la data a un archivo.

        
        // evaluo si se ha dado click en el enlace Export para generar el archivo
        return $contenido;
        
    }
}
/*
    Esta funcion muestra un reporte de llamadas exitosas y abandonadas en un rango de fechas determinado,
    por defecto se toma la fecha actual del sistema.
*/
function generarReporte($smarty,$fecha_actual_init,$fecha_actual_end,$oReportsCalls,&$arrGrid=null,$bElastixNuevo,$bExportando,&$oGrid,$url,$htmlFilter) {
    $fecha_init = translateDate($fecha_actual_init) . " 00:00:00";
    $fecha_end  = translateDate($fecha_actual_end)  . " 23:59:59";

    $limit = 50;
    $offset = 0;

    // instancio una objeto de la clase paloSantoReports Calls
    $arrQueues = $oReportsCalls->getQueueCallEntry(null,$offset);
    $total = count($arrQueues);
    if($bElastixNuevo){
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);
        $offset = $oGrid->calculateOffset();
    } else{
        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="end") {
            $totalQueues  = count($arrQueues);
            // Mejorar el sgte. bloque.
            if(($totalQueues%$limit)==0) {
                $offset = $totalQueues - $limit;
            } else {
                $offset = $totalQueues - $totalQueues%$limit;
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
    // lamo al metodo que me genera el reporte
    $arrDatosReporte = $oReportsCalls->getCall($arrQueues,$fecha_init,$fecha_end);
    // obtengo la cantidad de registros que apareceran en el reporte
    $arrQueues = $oReportsCalls->getQueueCallEntry($limit,$offset);
    $end = count($arrQueues);
    //defino el esqueleto del reporte

    $indice = 0;
    // comienzo a llenar el arreglo $arrTmp con los datos que se mostraran en el reporte
    $arrData = array();
    foreach($arrQueues as $queue) {
        //
        $arrTmp[0] = $queue['queue'];
        $arrTmp[1] = $arrDatosReporte['Success'][$indice];
        $arrTmp[2] = $arrDatosReporte['Left'][$indice];
        $arrTmp[3] = $arrDatosReporte['WaitTime'][$indice];
        $arrTmp[4] = $arrTmp[1] + $arrTmp[2];
        $arrData[] = $arrTmp;
        $indice++;
    }
    $sumExitosa = $sumAbandonada = $sumTotalCall = 0;
    $sumWait = "00:00:00";
    for($i=0;$i<count($arrData);$i++){
        $sumExitosa = $sumExitosa + $arrData[$i][1];
        $sumAbandonada = $sumAbandonada + $arrData[$i][2];
        $sumWait = $oReportsCalls->getTotalWaitTime($sumWait,$arrData[$i][3]);
        $sumTotalCall = $sumTotalCall + $arrData[$i][4];
    }
    $sTagInicio = (!$bExportando) ? '<b>' : '';
    $sTagFinal = ($sTagInicio != '') ? '</b>' : '';
    $arrTmp[0] = $sTagInicio._tr("Total").$sTagFinal;
    $arrTmp[1] = $sTagInicio.$sumExitosa.$sTagFinal;
    $arrTmp[2] = $sTagInicio.$sumAbandonada.$sTagFinal;
    $arrTmp[3] = $sTagInicio.$sumWait.$sTagFinal;
    $arrTmp[4] = $sTagInicio.$sumTotalCall.$sTagFinal;
    $arrData[] = $arrTmp;
    $oGrid->enableExport();
    $oGrid->showFilter($htmlFilter);
    if($bElastixNuevo){
        $oGrid->setURL($url);
        $oGrid->setData($arrData);
        $arrColumnas = array(_tr("Queue"), _tr("Successful"), _tr("Left"), _tr("Time Hopes"),_tr("Total Calls"));
        $oGrid->setColumns($arrColumnas);
        $oGrid->setTitle(_tr("Ingoing Calls Success"));
        $oGrid->pagingShow(true); 
        $oGrid->setNameFile_Export(_tr("Ingoing Calls Success"));
     
        $smarty->assign("SHOW", _tr("Show"));
        return $oGrid->fetchGrid();
     } else {
        global $arrLang;
        $offset = 0;
        $total = count($arrQueues) + 1;
        $limit = $total;

        $arrGrid = array(
            "title"    =>  _tr("List Calls"),
            "url"      =>  $url,
            "icon"     => "images/list.png",
            "width"    => "99%",
            "start"    => ($total==0) ? 0 : $offset + 1,
            "end"      => ($offset+$limit)<=$total ? $offset+$limit : $total,
            "total"    => $total,
            "columns"  => array(
                                0 => array( 'name'      => _tr("Queue"),  // aqui se  crea la primera columna 
                                            'property1' => ''),             // del reporte, y asi con las demas
                                1 => array( 'name'      => _tr("Successful"),
                                            'property1' => ''),
                                2 => array( 'name'      => _tr("Left"),
                                            'property1' => ''),
                                3 => array( 'name'      => _tr("Time Hopes"),
                                            'property1' => ''),
                                4 => array( 'name'      => _tr("Total Calls"),
                                            'property1' => ''),
                                ),
        );
        if($bExportando){
            // ----------- cabeceras necesarias para enviar la data a un archivo.  ----------- //
            // para que los datos de un formulario no se pierdan
            header("Cache-Control: private");
            // obliga a la pagina a que sea cacheada
            header("Pragma: cache");
            // origina de un flujo de datos.
            header('Content-Type: application/octec-stream');
            // nombre del archivo del reporte a generarse, en base a la fecha seleccionada del reporte.
            $title = "\"".$fecha_actual_init." a ".$fecha_actual_end.".csv\"";
            // hace que la data se guarde en un archivo con el nombre especificado en el parametro filename
            header("Content-disposition: inline; filename={$title}");
            // fuerza a que el archivo sea download
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

function createFieldFilter()
{
    $arrFormElements = array
        (
            // en este caso se desea hacer un filtro para consultar los valores entre dos fechas en 
            // particular a laque llamaremos txt_fecha_init y txt_fecha_end.
            "txt_fecha_init"  => array
            (
                "LABEL"                     => _tr('Date Init'),
                "REQUIRED"                  => "yes",
                "INPUT_TYPE"                => "DATE",
                "INPUT_EXTRA_PARAM"         => "",
                "VALIDATION_TYPE"           => "ereg",
                "VALIDATION_EXTRA_PARAM"    => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"
            ),
            "txt_fecha_end"  => array
            (
                "LABEL"                     => _tr('Date End'),
                "REQUIRED"                  => "yes",
                "INPUT_TYPE"                => "DATE",
                "INPUT_EXTRA_PARAM"         => "",
                "VALIDATION_TYPE"           => "ereg",
                "VALIDATION_EXTRA_PARAM"    => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"
            ),
        );
    return $arrFormElements;
}

?>
