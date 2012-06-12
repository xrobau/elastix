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
  */

require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "libs/xajax/xajax.inc.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
//     print_r($_POST);
    require_once "modules/$module_name/libs/PaloSantoHardwareDetection.class.php";
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    $xajax = new xajax();
    $xajax->registerFunction("hardwareDetect");
    $xajax->processRequests();
    
    $contenidoModulo  = $xajax->printJavascript("libs/xajax/");
    $contenidoModulo  .= listPorts($smarty, $module_name, $local_templates_dir);

    return $contenidoModulo;
}

function listPorts($smarty, $module_name, $local_templates_dir) {

    global $arrLang;
    $oPortsDetails = new PaloSantoHardwareDetection();
    $oGrid = new paloSantoGrid($smarty); 
    $contenidoModulo = "";

    $smarty->assign("HARDWARE_DETECT",$arrLang['Hardware Detect']);
    $smarty->assign("ZAPATA_REPLACE",$arrLang['Replace file zapata.conf']);
    $smarty->assign("MODULE_NAME",$module_name);
    $smarty->assign("detectandoHardware",$arrLang['Hardware Detecting']);
    $smarty->assign("CARD",$arrLang['Card']);
    $smarty->assign("CARD_NO_MOSTRAR",'ZTDUMMY/1');
    $smarty->assign("PORT_NOT_FOUND",$arrLang['Ports not Founds']);
    $smarty->assign("NO_PUERTO",$arrLang['No. Port']);
    $arrPortsDetails = $oPortsDetails->getPorts();

    if(!(is_array($arrPortsDetails) && count($arrPortsDetails) >0)){
        $smarty->assign("CARDS_NOT_FOUNDS",$oPortsDetails->errMsg);
    }
    $arrGrid = array("title"    => $arrLang['Hardware Detection'],
            "icon"     => "images/pci.png",
            "width"    => "100%"
            );
    $contenidoModulo .= llenarTpl($local_templates_dir,$smarty,$arrGrid, $arrPortsDetails);    
    return $contenidoModulo;
}

function llenarTpl($local_templates_dir,$smarty,$arrGrid, $arrData)
{
    $smarty->assign("title", $arrGrid['title']);
    $smarty->assign("icon",  $arrGrid['icon']);
    $smarty->assign("width", $arrGrid['width']);
    $smarty->assign("arrData", $arrData);
    return $smarty->fetch($local_templates_dir."/listPorts.tpl");
}

function hardwareDetect($chk_zapata_replace)
{
    global $arrLang;
    $respuesta = new xajaxResponse();
    $oHardwareDetect = new PaloSantoHardwareDetection();
    $resultado = $oHardwareDetect->hardwareDetection($chk_zapata_replace,"/etc/asterisk");
    $respuesta->addAlert($resultado);
    $respuesta->addAssign("relojArena","innerHTML","");
    $respuesta->addAssign("nombre_paquete","value","");
    $respuesta->addAssign("estaus_reloj","value","apagado");
    $respuesta->addScript("document.getElementById('form_dectect').submit();\n");
    return $respuesta;
}
?>
