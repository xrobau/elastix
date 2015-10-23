<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/
require_once 'libs/misc.lib.php';
require_once 'libs/paloSantoGrid.class.php';
require_once 'libs/paloSantoJSON.class.php';
require_once 'libs/paloSantoNetwork.class.php';
require_once 'libs/paloSantoValidar.class.php';

function _moduleContent(&$smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/paloSantoEndpoints.class.php";
    require_once "modules/$module_name/libs/paloInterfaceSSE.class.php";
    require_once "modules/$module_name/libs/paloServerSentEvents.class.php";
    
    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    // Valores estáticos comunes a todas las operaciones
    $smarty->assign('module_name', $module_name);
    $smarty->assign('LASTOP_ERROR_MESSAGE', 'null');

    // Inicialización del estado del módulo
    if (!isset($_SESSION[$module_name])) $_SESSION[$module_name] = array(
        'estadoCliente'     =>  NULL,
        'estadoClienteHash' =>  NULL,
    );
    
    // Construir lista de todos los diálogos conocidos
    $dlglist = array();
    foreach (scandir("modules/$module_name/dialogs/") as $dlgname) {
        if ($dlgname != '.' && $dlgname != '..' && is_dir("modules/$module_name/dialogs/$dlgname")) {
            $dlglist[] = $dlgname;
        }
    }

    // Carga de todas las funciones auxiliares de los diálogos
    foreach ($dlglist as $dlgname)
        if (file_exists("modules/$module_name/dialogs/$dlgname/index.php")) {
            if (file_exists("modules/$module_name/dialogs/$dlgname/lang/en.lang"))
                load_language_module("$module_name/dialogs/$dlgname");
            require_once "modules/$module_name/dialogs/$dlgname/index.php";
        }

    $h = 'handleHTML_mainReport';
    if (isset($_REQUEST['action'])) {
        $h = NULL;
        
        $regs = NULL;
        if (preg_match('/^(\w+)_(.*)$/', $_REQUEST['action'], $regs)) {
        	$classname = 'Dialog_'.ucfirst($regs[1]);
            $methodname = 'handleJSON_'.$regs[2];
            
            if (method_exists($classname, $methodname)) {
                $h = array($classname, $methodname);                
            }
        }
        if (is_null($h) && function_exists('handleJSON_'.$_REQUEST['action']))
            $h = 'handleJSON_'.$_REQUEST['action'];
        if (is_null($h))
            $h = 'handleJSON_unimplemented';
    }        
    return call_user_func($h, $smarty, $module_name, $local_templates_dir, $dlglist);
}

function handleHTML_mainReport($smarty, $module_name, $local_templates_dir, $dlglist)
{
    // Ember.js requiere jQuery 1.7.2 o superior.
    modificarReferenciasLibreriasJS($smarty, $module_name, $dlglist);

    $json = new Services_JSON();
    $smarty->assign(array(
        'title'                     =>  _tr('Endpoint Configurator'),
        'icon'                      =>  'modules/'.$module_name.'/images/pbx_endpoint_configurator.png',
        'showing'                   =>  _tr('Showing'),
        'of'                        =>  _tr('of'),
        
        'LBL_CANCEL'                =>  _tr('Cancel'),
        'LBL_SCAN'                  =>  _tr('Scan network for endpoints'),
        'LBL_STEP'                  =>  _tr('Step'),
        'LBL_CONFIGURE'             =>  _tr('Configure'),
        'LBL_FORGET'                =>  _tr('Remove configuration for selected endpoints'),
        'LBL_DOWNLOAD'              =>  _tr('Download list of configurable endpoints'),
        'LBL_CSV_LEGACY'            =>  _tr('CSV (Legacy)'),
        'LBL_XML'                   =>  _tr('XML'),
        'LBL_CSV_NESTED'            =>  _tr('CSV (nested)'),
        'LBL_UPLOAD'                =>  _tr('Upload list of endpoint configuration'),
        'LBL_VIEW_LOG'              =>  _tr('View log of last configuration'),
        'LBL_STATUS'                =>  _tr('Status'),
        'LBL_MAC_ADDRESS'           =>  _tr('MAC Address'),
        'LBL_CURRENT_IP'            =>  _tr('Current IP'),
        'LBL_MANUFACTURER'          =>  _tr('Manufacturer'),
        'LBL_MODEL'                 =>  _tr('Model'),
        'LBL_OPTIONS'               =>  _tr('Options'),
        'TOOLTIP_CONFIGURE'         =>  _tr('Apply configuration to all selected endpoints'),
        'TOOLTIP_FROM_BATCH'        =>  _tr('This endpoint has been loaded from a file'),
        'TOOLTIP_MODIFIED'          =>  _tr('Changes to this endpoint have not yet been applied'),
        'TOOLTIP_HAS_EXTENSIONS'    =>  _tr('This endpoint has at least one account assigned'),
        'TOOLTIP_MISSING'           =>  _tr('This endpoint was previously detected but is missing from the latest scan'),
        'LBL_UNKNOWN'               =>  _tr('(unknown)'),
        'MSG_NO_ENDPOINTS'          =>  _tr('No endpoints have been discovered or loaded.'),
        'ARRLANG_MAIN'              =>  $json->encode(array(
            'SCANCONFIG_INPROGRESS' =>  _tr('Cannot scan while scanning/configuration is in progress.'),
            'SCANCONFIG_INPROGRESS2'=>  _tr('Cannot configure while scanning/configuration is in progress.'),
            'INVALID_SCANMASK'      =>  _tr('Invalid endpoint scan mask.'),
            'CONFIRM_FORGET'        =>  _tr('Please confirm in order to remove tracking of selected endpoints. All configuration files for selections will be removed but no accounts will be removed from the phone or this system.'),
            'TITLE_ENDPOINT_CONFIG' =>  _tr('Endpoint configuration for'),
            'TITLE_LASTLOG'         =>  _tr('Log of last configuration'),
            'CONFIGURATION_SUCCESS' =>  _tr('All endpoints were successfully configured'),
            'LBL_APPLY'             =>  _tr('Apply'),
            'LBL_DISMISS'           =>  _tr('Dismiss'),
        )),
    ));
    $c = $smarty->fetch($local_templates_dir.'/reporte_endpoints.tpl');
    
    // Se invoca la preparación de las plantillas Ember.js de cada diálogo
    foreach ($dlglist as $dlgname) {
        // No hay soporte de namespace en PHP 5.1, se simula con una clase
        $classname = 'Dialog_'.ucfirst($dlgname);
        if (class_exists($classname))
            $c .= call_user_func(array($classname, 'templateContent'), $smarty, $module_name, $local_templates_dir);
    }
    return $c;
}

function handleJSON_unimplemented($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode(array(
        'status'    =>  'error',
        'message'   =>  _tr('Unimplemented method'),
    ));
}

function handleJSON_loadModels($smarty, $module_name, $local_templates_dir, $dlglist)
{
    session_commit();
    
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );
    
    $oEndpoints = new paloSantoEndpoints();
    $listaModelos = $oEndpoints->leerModelos();
    if (!is_array($listaModelos)) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = $oEndpoints->getErrMsg();
    } else {
        $respuesta['models'] = $listaModelos;
    }
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_loadStatus($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );
    
	$respuesta['scanMask'] = network();
    $respuesta['scanInProgress'] = (
        !is_null($_SESSION[$module_name]['estadoCliente']) &&
        !is_null($_SESSION[$module_name]['estadoCliente']['scanSocket']));
    $respuesta['configInProgress'] = (
        !is_null($_SESSION[$module_name]['estadoCliente']) &&
        !is_null($_SESSION[$module_name]['estadoCliente']['configLog']));
    $respuesta['estadoClienteHash'] = ($respuesta['scanInProgress'] || $respuesta['configInProgress']) ?
        $_SESSION[$module_name]['estadoClienteHash'] : NULL; 
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_loadEndpoints($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );
	
    if (isset($_SESSION[$module_name]['estadoCliente']['endpoints'])) {
    	$respuesta['endpoints'] = $_SESSION[$module_name]['estadoCliente']['endpoints'];
    } else {
        $oEndpoints = new paloSantoEndpoints();
        $listaEndpoints = $oEndpoints->leerEndpoints();
        if (!is_array($listaEndpoints)) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = $oEndpoints->getErrMsg();
        } else {
            $respuesta['endpoints'] = $listaEndpoints;
        }
    }
    
    paloServerSentEvents::generarEstadoHash($module_name, array(
        'endpoints'     =>  isset($_SESSION[$module_name]['estadoCliente']['endpoints']) 
            ? $_SESSION[$module_name]['estadoCliente']['endpoints'] : NULL,
        'scanSocket'    =>  isset($_SESSION[$module_name]['estadoCliente']['scanSocket']) 
            ? $_SESSION[$module_name]['estadoCliente']['scanSocket'] : NULL,
        'configLog'     =>  isset($_SESSION[$module_name]['estadoCliente']['configLog'])
            ? $_SESSION[$module_name]['estadoCliente']['configLog'] : NULL,
        'logOffset'     =>  0,
    ));

    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_scanStart($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );

    if (isset($_SESSION[$module_name]['estadoCliente']['scanSocket'])) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('There is a network scan in progress');
    } elseif (isset($_SESSION[$module_name]['estadoCliente']['configLog'])) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('There is an endpoint configuration in progress');
    } else {
        // Validar la máscara de red para escaneo de red
        $scanMask = isset($_REQUEST['scanMask']) ? $_REQUEST['scanMask'] : '';
        $pValidator = new PaloValidar();
        if (!$pValidator->validar('scanMask', $scanMask, 'ip/mask')){
            $strErrorMsg = "";
            if(is_array($pValidator->arrErrores) && count($pValidator->arrErrores) > 0){
                foreach($pValidator->arrErrores as $k=>$v) {
                    $strErrorMsg .= "$k, ";
                }
            }
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Invalid Format in Parameter').': '.$strErrorMsg;
        } else {
            // Cargar los endpoints desde la base de datos
            $oEndpoints = new paloSantoEndpoints();
            $listaEndpoints = $oEndpoints->leerEndpoints();
            if (!is_array($listaEndpoints)) {
                $respuesta['status'] = 'error';
                $respuesta['message'] = $oEndpoints->getErrMsg();
            } else {
                // Iniciar el escaneo de la red usando el script privilegiado
                $sockFile = $oEndpoints->iniciarScanRed($scanMask);
                if (is_null($sockFile)) {
                    $respuesta['status'] = 'error';
                    $respuesta['message'] = $oEndpoints->getErrMsg();
                }
                $respuesta['estadoClienteHash'] = paloServerSentEvents::generarEstadoHash($module_name, array(
                    'endpoints'     =>  $listaEndpoints,
                    'scanSocket'    =>  $sockFile,
                ));
            }

        }
    }
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_scanCancel($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );

    if (is_null($_SESSION[$module_name]['estadoCliente']['scanSocket'])) {
    	$respuesta['status'] = 'error';
        $respuesta['message'] = _tr('There is no network scan in progress.');
    } else {
        $oEndpoints = new paloSantoEndpoints();
        if (!$oEndpoints->cancelarScanRed($_SESSION[$module_name]['estadoCliente']['scanSocket'])) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = $oEndpoints->getErrMsg();
        }
    }
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_scanStatus($smarty, $module_name, $local_templates_dir, $dlglist)
{
    require_once "modules/$module_name/libs/paloEndpointScanStatus.class.php";

	$paloSSE = new paloServerSentEvents($module_name, 'paloEndpointScanStatus');
    $paloSSE->handle();
    return '';
}

function handleJSON_setEndpointModel($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'        =>  'success',
        'message'       =>  '(no message)',
        'last_modified' =>  NULL,
    );

    $oEndpoints = new paloSantoEndpoints();
	if (!isset($_REQUEST['id_endpoint']) || !ctype_digit($_REQUEST['id_endpoint'])) {
		$respuesta['status'] = 'error';
        $respuesta['message'] = _tr('Invalid endpoint ID');
	} elseif (!isset($_REQUEST['id_model']) || !(ctype_digit($_REQUEST['id_model']) || $_REQUEST['id_model'] == 'unknown')) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('Invalid model ID');
    } else {
    	$r = $oEndpoints->asignarModeloEndpoint(
            (int)$_REQUEST['id_endpoint'],
            (($_REQUEST['id_model'] == 'unknown') ? NULL : (int)$_REQUEST['id_model']));
        if (is_null($r)) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = $oEndpoints->getErrMsg();
        } elseif ($r != 'unchanged') {
        	$respuesta['last_modified'] = $r;
        }
    }
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_loadUnassignedAccounts($smarty, $module_name, $local_templates_dir, $dlglist)
{
    session_commit();
    
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );

    $oEndpoints = new paloSantoEndpoints();
    $listaCuentas = $oEndpoints->leerCuentasNoAsignadas();
    if (!is_array($listaCuentas)) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = $oEndpoints->getErrMsg();
    } else {
        $respuesta['accounts'] = $listaCuentas;
    }
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_forgetSelected($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );

    if (!isset($_REQUEST['selection']) || !is_array($_REQUEST['selection']) || count($_REQUEST['selection']) <= 0) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('Invalid or empty selection');
    } else {
        $oEndpoints = new paloSantoEndpoints();
        if (!$oEndpoints->olvidarSeleccionEndpoints($_REQUEST['selection'])) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = $oEndpoints->getErrMsg();
        }
    }

    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_configStart($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );

	if (!isset($_REQUEST['selection']) || !is_array($_REQUEST['selection']) || count($_REQUEST['selection']) <= 0) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('Invalid or empty selection');
    } elseif (isset($_SESSION[$module_name]['estadoCliente']['scanSocket'])) {
		$respuesta['status'] = 'error';
        $respuesta['message'] = _tr('There is a network scan in progress');
	} elseif (isset($_SESSION[$module_name]['estadoCliente']['configLog'])) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = _tr('There is an endpoint configuration in progress');
    } else {
        // Cargar los endpoints desde la base de datos
        $oEndpoints = new paloSantoEndpoints();
        $listaEndpoints = $oEndpoints->leerEndpoints();
        if (!is_array($listaEndpoints)) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = $oEndpoints->getErrMsg();
        } else {
            $logfile = $oEndpoints->iniciarConfiguracionEndpoints($_REQUEST['selection']);
            if (is_null($logfile)) {
                $respuesta['status'] = 'error';
                $respuesta['message'] = $oEndpoints->getErrMsg();
            } else {
                $respuesta['estadoClienteHash'] = paloServerSentEvents::generarEstadoHash($module_name, array(
                    'endpoints' =>  $listaEndpoints,
                    'configLog' =>  $logfile,
                    'logOffset' =>  0,
                ));
            }
        }

	}
    
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

function handleJSON_configStatus($smarty, $module_name, $local_templates_dir, $dlglist)
{
    require_once "modules/$module_name/libs/paloEndpointConfigStatus.class.php";

    $paloSSE = new paloServerSentEvents($module_name, 'paloEndpointConfigStatus');
    $paloSSE->handle();
    return '';
}

function handleJSON_getConfigLog($smarty, $module_name, $local_templates_dir, $dlglist)
{
    $respuesta = array(
        'status'    =>  'success',
        'message'   =>  '(no message)',
    );

    $oEndpoints = new paloSantoEndpoints();
    $log = $oEndpoints->leerLogConfiguracion();
    if (is_null($log)) {
        $respuesta['status'] = 'error';
        $respuesta['message'] = $oEndpoints->getErrMsg();
    } else {
    	$respuesta['log'] = $log;
    }

    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($respuesta);
}

// A pesar del nombre, este método no devuelve JSON sino CSV o XML
function handleJSON_download($smarty, $module_name, $local_templates_dir, $dlglist)
{
    Header('Cache-Control: private');
    Header('Pragma: cache');

    $oEndpoints = new paloSantoEndpoints();
    $listaEndpoints = $oEndpoints->leerEndpointsDescarga();
    if (!is_array($listaEndpoints)) {
        return $oEndpoints->getErrMsg();
    }
    $format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'legacy';
    if (!preg_match('/^\w+$/', $format)) $format = 'legacy';

    // Identificar la clase que maneja el formato 
    $sClassName = 'EndpointFile_'.ucfirst($format);
    $sClassPath = "modules/$module_name/libs/{$sClassName}.class.php";
    if (!file_exists($sClassPath)) {
    	return _tr('Invalid endpoint file format');
    }
    require_once $sClassPath;
    
    $formato = new $sClassName;
    return $formato->generarDescargaEndpoints($oEndpoints, $listaEndpoints);
}

function handleJSON_upload($smarty, $module_name, $local_templates_dir, $dlglist)
{
	$sMensajeError = NULL;
    
    // ¿Cuál es el formato del archivo que se ha subido?
    $loader = NULL;
    if (!isset($_FILES['endpointfile']) || $_FILES['endpointfile']['size'][0] <= 0) {
    	$sMensajeError = _tr('No file uploaded');
    } elseif ($_FILES['endpointfile']['error'][0] != UPLOAD_ERR_OK) {
    	$sMensajeError = _tr('Failed to upload file');
    } else {
        $sTmpFile = $_FILES['endpointfile']['tmp_name'][0];
        foreach (glob("modules/{$module_name}/libs/EndpointFile_*.class.php") as $classfile) {
        	require_once $classfile;
            $classname = basename($classfile, '.class.php');
            $loader = new $classname;
            if ($loader->detectarFormato($sTmpFile)) break;
            
            $loader = NULL;
        }
        if (is_null($loader)) {
        	$sMensajeError = _tr('Failed to detect endpoint file format');
        }
    }
    
    $listaEndpoints = NULL;
    if (!is_null($loader)) {
    	$listaEndpoints = $loader->parsearEndpoints($sTmpFile);
        if (is_null($listaEndpoints)) {
        	$sMensajeError = _tr('Failed to parse file').': '.$loader->errMsg;
        } 
        $loader = NULL;
    }
    
    if (!is_null($listaEndpoints)) {
        $oEndpoints = new paloSantoEndpoints();
        $endpointChanges = $oEndpoints->ingresarEndpoints($module_name, $listaEndpoints);
        if (is_null($endpointChanges)) {
        	$sMensajeError = _tr('Failed to save endpoints').': '.$oEndpoints->getErrMsg();
        }
    }
    
    /* Este método puede invocarse de dos maneras. Se puede invocar vía JSON en
     * navegadores compatibles com HTML5. También puede invocarse como una
     * petición POST de formulario ordinaria con Internet Explorer */
    $json = new Services_JSON();
    if (isset($_REQUEST['legacyupload'])) {
    	// Petición ordinaria con Internet Explorer
        if (!is_null($sMensajeError)) {
        	$smarty->assign('LASTOP_ERROR_MESSAGE', $json->encode($sMensajeError));
        }
        return handleHTML_mainReport($smarty, $module_name, $local_templates_dir, $dlglist);
    } else {
    	// Petición AJAX de HTML5
        $respuesta = array(
            'status'    =>  'success',
            'message'   =>  '(no message)',
        );
        if (!is_null($sMensajeError)) {
        	$respuesta['status'] = 'error';
            $respuesta['message'] = $sMensajeError;
        } else {
            $respuesta['endpointchanges'] = $endpointChanges;
        }

        Header('Content-Type: application/json');
        return $json->encode($respuesta);
    }
}

function network()
{
    /* OJO: paloNetwork::getNetAdress() ha sido reescrito y es ahora una función
     * estática. Si PHP se queja de que la función no puede llamarse en contexto
     * estático, NO PARCHE AQUí. En su lugar, actualice a 
     * elastix-system-2.3.0-10 o superior. El spec de elastix-pbx ya tiene este
     * requerimiento mínimo. */
    $ip = $_SERVER['SERVER_ADDR'];
    $total = subMask($ip);
    return paloNetwork::getNetAdress($ip, $total)."/".$total;    
}

function subMask($ip)
{
    $output = NULL;
    exec('/sbin/ip addr', $output);
    /*
    [root@picosam ~]# ip addr show
    1: lo: <LOOPBACK,UP,LOWER_UP> mtu 16436 qdisc noqueue state UNKNOWN 
        link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00
        inet 127.0.0.1/8 scope host lo
        inet6 ::1/128 scope host 
           valid_lft forever preferred_lft forever
    2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc pfifo_fast state UNKNOWN qlen 1000
        link/ether 7a:35:22:cd:57:98 brd ff:ff:ff:ff:ff:ff
        inet 192.168.5.130/16 brd 192.168.255.255 scope global eth0
        inet6 fe80::7835:22ff:fecd:5798/64 scope link 
           valid_lft forever preferred_lft forever
     */
    foreach ($output as $s) {
        $regs = NULL;
        if (preg_match('|inet (\d+.\d+.\d+.\d+)/(\d+)|', $s, $regs)) {
            if ($regs[1] == $ip) return (int)$regs[2];
        }
    }
    return 32;  // No se pudo encontrar máscara de la red
}

function modificarReferenciasLibreriasJS($smarty, $module_name, $dlglist)
{
    $listaLibsJS_modulo = explode("\n", $smarty->get_template_vars('HEADER_MODULES'));

    /* Se busca la referencia a jQuery (se asume que sólo hay una biblioteca que
     * empieza con "jquery-") y se la quita. Las referencias a Ember.js y 
     * Handlebars se reordenan para que Handlebars aparezca antes que Ember.js 
     */ 
    $sEmberRef = $sHandleBarsRef = NULL;
    foreach (array_keys($listaLibsJS_modulo) as $k) {
        if (strpos($listaLibsJS_modulo[$k], 'themes/default/js/handlebars-') !== FALSE) {
            $sHandleBarsRef = $listaLibsJS_modulo[$k];
            unset($listaLibsJS_modulo[$k]);
        } elseif (strpos($listaLibsJS_modulo[$k], 'themes/default/js/ember-') !== FALSE) {
            $sEmberRef = $listaLibsJS_modulo[$k];
            unset($listaLibsJS_modulo[$k]);
        }
    }
    array_unshift($listaLibsJS_modulo, $sEmberRef);
    array_unshift($listaLibsJS_modulo, $sHandleBarsRef);
    
    // Se incluyen las funciones javascript de cada diálogo
    foreach ($dlglist as $dlgname) {
    	foreach (scandir("modules/$module_name/dialogs/$dlgname/js") as $jslib) 
        if ($jslib != '.' && $jslib != '..') {
    		array_push($listaLibsJS_modulo, "<script type='text/javascript' src='modules/$module_name/dialogs/$dlgname/js/$jslib'></script>");
    	}
    }    
    $smarty->assign('HEADER_MODULES', implode("\n", $listaLibsJS_modulo));
}

?>