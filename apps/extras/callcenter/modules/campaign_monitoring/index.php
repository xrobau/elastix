<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.8                                                  |
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
  $Id: default.conf.php,v 1.1.1.1 2007/03/23 00:13:58 elandivar Exp $ */


include_once "libs/paloSantoConfig.class.php";

require_once "modules/agent_console/libs/elastix2.lib.php";
require_once "modules/agent_console/libs/JSON.php";
require_once "modules/agent_console/libs/paloSantoConsola.class.php";

function _moduleContent(&$smarty, $module_name)
{
    global $arrConf;
    global $arrLang;
    global $arrConfig;
  
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloMonitorCampania.class.php";

    load_language_module($module_name);    

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // Ember.js requiere jQuery 1.7.2 o superior.
    modificarReferenciasLibreriasJS($smarty);

    $sContenido = '';

    // Procesar los eventos AJAX.
    switch (getParameter('action')) {
    case 'getCampaigns':
        $sContenido = manejarMonitoreo_getCampaigns($module_name, $smarty, $local_templates_dir);
        break;
    case 'getCampaignDetail':
        $sContenido = manejarMonitoreo_getCampaignDetail($module_name, $smarty, $local_templates_dir);
        break;
    case 'checkStatus':
        $sContenido = manejarMonitoreo_checkStatus($module_name, $smarty, $local_templates_dir);
        break;
    default:
        // Página principal con plantilla
        $sContenido = manejarMonitoreo_HTML($module_name, $smarty, $local_templates_dir);
    }
    return $sContenido;
}

function manejarMonitoreo_HTML($module_name, $smarty, $sDirLocalPlantillas)
{
    $debug = "";
    $smarty->assign("MODULE_NAME", $module_name);
    $smarty->assign(array(
        'title'                         =>  _tr('Campaign Monitoring'),
        'icon'                          => '/images/list.png',
        'ETIQUETA_CAMPANIA'             =>  _tr('Campaign'),
        'ETIQUETA_FECHA_INICIO'         =>  _tr('Start date'),
        'ETIQUETA_FECHA_FINAL'          =>  _tr('End date'),
        'ETIQUETA_HORARIO'              =>  _tr('Schedule'),
        'ETIQUETA_COLA'                 =>  _tr('Queue'),
        'ETIQUETA_INTENTOS'             =>  _tr('Retries'),
        'ETIQUETA_TOTAL_LLAMADAS'       =>  _tr('Total calls'),
        'ETIQUETA_LLAMADAS_PENDIENTES'  =>  _tr('Pending calls'),
        'ETIQUETA_LLAMADAS_FALLIDAS'    =>  _tr('Failed calls'),
        'ETIQUETA_LLAMADAS_CORTAS'      =>  _tr('Short calls'),
        'ETIQUETA_LLAMADAS_EXITO'       =>  _tr('Connected calls'),
        'ETIQUETA_LLAMADAS_MARCANDO'    =>  _tr('Placing calls'),
        'ETIQUETA_LLAMADAS_COLA'        =>  _tr('Queued calls'),
        'ETIQUETA_LLAMADAS_TIMBRANDO'   =>  _tr('Ringing calls'),
        'ETIQUETA_LLAMADAS_ABANDONADAS' =>  _tr('Abandoned calls'),
        'ETIQUETA_LLAMADAS_NOCONTESTA'  =>  _tr('Unanswered calls'),
        'ETIQUETA_LLAMADAS_TERMINADAS'  =>  _tr('Finished calls'),
        'ETIQUETA_LLAMADAS_SINRASTRO'   =>  _tr('Lost track'),
        'ETIQUETA_AGENTES'              =>  _tr('Agents'),
        'ETIQUETA_NUMERO_TELEFONO'      =>  _tr('Phone Number'),
        'ETIQUETA_TRONCAL'              =>  _tr('Trunk'),
        'ETIQUETA_ESTADO'               =>  _tr('Status'),
        'ETIQUETA_DESDE'                =>  _tr('Since'),
        'ETIQUETA_AGENTE'               =>  _tr('Agent'),
        'ETIQUETA_REGISTRO'             =>  _tr('Campaign log'),
    ));
    $smarty->assign('INFO_DEBUG', $debug);
    return $smarty->fetch("file:$sDirLocalPlantillas/informacion_campania.tpl");
}

function manejarMonitoreo_getCampaigns($module_name, $smarty, $sDirLocalPlantillas)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );
	$oPaloConsola = new PaloSantoConsola();
    $listaCampanias = $oPaloConsola->leerListaCampanias();
    if (!is_array($listaCampanias)) {
    	$respuesta['status'] = 'error';
        $respuesta['message'] = $oPaloConsola->errMsg;
    } else {
        /* Para la visualización se requiere que primero se muestren las campañas 
         * activas, con el ID mayor primero (probablemente la campaña más reciente)
         * seguido de las campañas inactivas, y luego las terminadas */
        if (!function_exists('manejarMonitoreo_getCampaigns_sort')) {
            function manejarMonitoreo_getCampaigns_sort($a, $b)
            {
            	if ($a['status'] != $b['status'])
                    return strcmp($a['status'], $b['status']);
                return $b['id'] - $a['id'];
            }
        }
        usort($listaCampanias, 'manejarMonitoreo_getCampaigns_sort');
        $respuesta['campaigns'] = array();
        foreach ($listaCampanias as $c) /*if ($c['status'] != 'inactive')*/ { 
            $respuesta['campaigns'][] = array(
                'id_campaign'   => $c['id'],
                'desc_campaign' => '('.$c['type'].') '.$c['name'],
                'type'          =>  $c['type'],
                'status'        =>  $c['status'],
            );
        }
    }
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function manejarMonitoreo_getCampaignDetail($module_name, $smarty, $sDirLocalPlantillas)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );
    $estadoCliente = array();
    
    $sTipoCampania = getParameter('campaigntype');
    $sIdCampania = getParameter('campaignid');
    if (is_null($sTipoCampania) || !in_array($sTipoCampania, array('incoming', 'outgoing'))) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('Invalid campaign type');
    } elseif (is_null($sIdCampania) || !ctype_digit($sIdCampania)) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('Invalid campaign ID');
    } else {
        $oPaloConsola = new PaloSantoConsola();
        if ($respuesta['status'] == 'success') {
        	$infoCampania = $oPaloConsola->leerInfoCampania($sTipoCampania, $sIdCampania);
            if (!is_array($infoCampania)) {
            	$respuesta['status'] = 'error';
                $respuesta['message'] = $oPaloConsola->errMsg;
            }
        }
        if ($respuesta['status'] == 'success') {
            $estadoCampania = $oPaloConsola->leerEstadoCampania($sTipoCampania, $sIdCampania);
            if (!is_array($estadoCampania)) {
                $respuesta['status'] = 'error';
                $respuesta['message'] = $oPaloConsola->errMsg;
            }
        }
    }
    if ($respuesta['status'] == 'success') {
    	$respuesta['campaigndata'] = array(
            'startdate'                 =>  $infoCampania['startdate'],
            'enddate'                   =>  $infoCampania['enddate'],
            'working_time_starttime'    =>  $infoCampania['working_time_starttime'],
            'working_time_endtime'      =>  $infoCampania['working_time_endtime'],
            'queue'                     =>  $infoCampania['queue'],
            'retries'                   =>  (int)$infoCampania['retries'],
        );
        
        $respuesta['update'] = array(
            'statuscount'   =>  $estadoCampania['statuscount'],
        );
        $estadoCliente = array(
            'campaignid'    =>  $sIdCampania,
            'campaigntype'  =>  $sTipoCampania,
            'statuscount'   =>  $estadoCampania['statuscount'],
        );
        
        // TODO: llamadas en curso y log de campaña        
        // TODO: lista de agentes que pueden atender la llamada
        
        $respuesta['estadoClienteHash'] = generarEstadoHash($module_name, $estadoCliente);
    }
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function manejarMonitoreo_checkStatus($module_name, $smarty, $sDirLocalPlantillas)
{
    $oPaloConsola = new PaloSantoConsola();
	//
}

function modificarReferenciasLibreriasJS($smarty)
{
    $listaLibsJS_framework = explode("\n", $smarty->get_template_vars('HEADER_LIBS_JQUERY'));
    $listaLibsJS_modulo = explode("\n", $smarty->get_template_vars('HEADER_MODULES'));

    /* Se busca la referencia a jQuery (se asume que sólo hay una biblioteca que
     * empieza con "jquery-") y se la quita. Las referencias a Ember.js y 
     * Handlebars se reordenan para que Handlebars aparezca antes que Ember.js 
     */ 
    $sEmberRef = $sHandleBarsRef = $sjQueryRef = NULL;
    foreach (array_keys($listaLibsJS_modulo) as $k) {
    	if (strpos($listaLibsJS_modulo[$k], 'themes/default/js/jquery-') !== FALSE) {
    		$sjQueryRef = $listaLibsJS_modulo[$k];
            unset($listaLibsJS_modulo[$k]);
    	} elseif (strpos($listaLibsJS_modulo[$k], 'themes/default/js/handlebars-') !== FALSE) {
            $sHandleBarsRef = $listaLibsJS_modulo[$k];
            unset($listaLibsJS_modulo[$k]);
        } elseif (strpos($listaLibsJS_modulo[$k], 'themes/default/js/ember-') !== FALSE) {
            $sEmberRef = $listaLibsJS_modulo[$k];
            unset($listaLibsJS_modulo[$k]);
        }
    }
    array_unshift($listaLibsJS_modulo, $sEmberRef);
    array_unshift($listaLibsJS_modulo, $sHandleBarsRef);
    $smarty->assign('HEADER_MODULES', implode("\n", $listaLibsJS_modulo));

    /* Se busca la referencia original al jQuery del framework, y se reemplaza
     * si es más vieja que el jQuery del módulo */
    $sRegexp = '/jquery-(\d.+?)(\.min)?\.js/'; $regs = NULL;
    preg_match($sRegexp, $sjQueryRef, $regs);
    $sVersionModulo = $regs[1];
    $sVersionFramework = NULL;
    foreach (array_keys($listaLibsJS_framework) as $k) {
    	if (preg_match($sRegexp, $listaLibsJS_framework[$k], $regs)) {
    		$sVersionFramework = $regs[1];
            
            // Se asume que la versión sólo consiste de números y puntos
            $verFramework = explode('.', $sVersionFramework);
            $verModulo = explode('.', $sVersionModulo);
            while (count($verFramework) < count($verModulo)) $verFramework[] = "0";
            while (count($verFramework) > count($verModulo)) $verModulo[] = "0";
            if ($verModulo > $verFramework) $listaLibsJS_framework[$k] = $sjQueryRef;
    	}
    }
    $smarty->assign('HEADER_LIBS_JQUERY', implode("\n", $listaLibsJS_framework));
}

function jsonflush($bSSE, $respuesta)
{
    $json = new Services_JSON();
    $r = $json->encode($respuesta);
    if ($bSSE)
        printflush("data: $r\n\n");
    else printflush($r);
}

function printflush($s)
{
    print $s;
    ob_flush();
    flush();
}

function generarEstadoHash($module_name, $estadoCliente)
{
    $estadoHash = md5(serialize($estadoCliente));
    $_SESSION[$module_name]['estadoCliente'] = $estadoCliente;
    $_SESSION[$module_name]['estadoClienteHash'] = $estadoHash;

    return $estadoHash;
}


/*
    // Conexión a la base de datos CallCenter
    $pDB = new paloDB($arrConf['cadena_dsn']);
    if ($pDB->connStatus) die("ERR: ".$pDB->errMsg);

    // Si se llega hasta aquí, se genera el contenido original del monitor
    $oMonitor = new paloMonitorCampania($pDB);

    // Listar las campañas disponibles y verificar cuál está elegida
    $idCampania = NULL;
    $listaCampanias = $oMonitor->listarCampanias();
    $smartyListaCampanias = array();
    if (count($listaCampanias) > 0) $idCampania = $listaCampanias[0]['id'];
    foreach ($listaCampanias as $tuplaCampania) {
        $smartyListaCampanias[$tuplaCampania['id']] = $tuplaCampania['name'];
        if (isset($_POST['id_campaign']) && $_POST['id_campaign'] == $tuplaCampania['id'])
            $idCampania = $tuplaCampania['id'];
    }
    $smarty->assign('curr_id_campaign', $idCampania);
    $smarty->assign('lista_campaign', $smartyListaCampanias);

    // Leer la información de resumen de la campaña elegida
    if (!is_null($idCampania)) {
        $infoCampania = $oMonitor->leerResumenCampania($idCampania);
        $iTotalLlamadas = array_sum($infoCampania['status']);
        
        $smarty->assign(array(
            'FECHA_INICIO_CAMPANIA'     =>  $infoCampania['datetime_init'],
            'FECHA_FINAL_CAMPANIA'      =>  $infoCampania['datetime_end'],
            'HORARIO_INICIO_CAMPANIA'   =>  $infoCampania['daytime_init'],
            'HORARIO_FINAL_CAMPANIA'    =>  $infoCampania['daytime_end'],
            'COLA_CAMPANIA'             =>  $infoCampania['queue'],
            'MAX_INTENTOS_CAMPANIA'     =>  $infoCampania['retries'],
            'CAMPANIA_LLAMADAS_TOTAL'   =>  $iTotalLlamadas,
            'CAMPANIA_LLAMADAS_PENDIENTES' =>  $infoCampania['status']['Pending'],
            'CAMPANIA_LLAMADAS_FALLIDAS' =>  $infoCampania['status']['Failure'] + $infoCampania['status']['NoAnswer'] + $infoCampania['status']['Abandoned'],
            'CAMPANIA_LLAMADAS_CORTAS' =>  $infoCampania['status']['ShortCall'],
            'CAMPANIA_LLAMADAS_EXITO' =>  $infoCampania['status']['Success'] + $infoCampania['status']['OnHold'],
            'CAMPANIA_LLAMADAS_MARCANDO' =>  $infoCampania['status']['Placing'] + $infoCampania['status']['Ringing'],
            'CAMPANIA_LLAMADAS_COLA' =>  $infoCampania['status']['OnQueue'],
        ));
    }

    // Leer la configuración de Asterisk Manager para consultar el AMI
    $estadoCola = $oMonitor->leerEstadoCola($infoCampania['queue']);
    ksort($estadoCola['members']);
    //$debug .= print_r($estadoCola, 1);
    $listaAgentes = array();
    foreach ($estadoCola['members'] as $sNumAgente => $infoAgente) {
        $tuplaAgente = array(
            'id'        =>  $sNumAgente,    // El ID del agente
            'estado'    =>  _tr('Not Logged In'),    // No logon, Ocupado, Break/Pausa, Libre
            'numero'    =>  '--',           // El número con el que está hablando
            'troncal'   =>  '--',           // Troncal a través de la que salió la llamada
            'desde'     =>  '--',           // Instante desde el que empezó a hablar
        );
        if ($infoAgente['status'] == 'canBeCalled')
            $tuplaAgente['estado'] = _tr('Free');
        if ($infoAgente['status'] == 'inUse') {
            $tuplaAgente['estado'] = _tr('Busy');
            // TODO: obtener número, troncal, tiempo de hablado
            $tuplaAgente['numero'] = $infoAgente['dialnumber'];
            $tuplaAgente['troncal'] = $infoAgente['clientchannel'];
            $tuplaAgente['desde'] = str_replace(date('Y-m-d '), '', $infoAgente['datetime_init']);
        }
        if (in_array('paused', $infoAgente['attributes'])) {
            $tuplaAgente['estado'] = _tr('Break').': '.$infoAgente['break_name'];
            $tuplaAgente['desde'] = str_replace(date('Y-m-d '), '', $infoAgente['datetime_init']);
        }
        $listaAgentes[] = $tuplaAgente;
    }
    $smarty->assign('agentes_cola', $listaAgentes);
*/
?>