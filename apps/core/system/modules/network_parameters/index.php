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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoNetwork.class.php";
    include_once "libs/paloSantoGrid.class.php";
    include_once "modules/$module_name/configs/default.conf.php";

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
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $arrFormNetwork  = array("host"         => array("LABEL"                  => "{$arrLang['Host']} (Ex. host.example.com)",
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "domain",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dns1"         => array("LABEL"                  => $arrLang["Primary DNS"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dns2"         => array("LABEL"                  => $arrLang["Secondary DNS"],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "gateway"      => array("LABEL"                  => $arrLang["Default Gateway"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""));

    $arrFormInterfase = array("ip"          => array("LABEL"                  => $arrLang["IP Address"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "mask"         => array("LABEL"                  => $arrLang["Network Mask"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "mask",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "type"         => array("LABEL"                  => $arrLang["Interface Type"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "RADIO",
                                                     "INPUT_EXTRA_PARAM"      => array("static" => "Static", "dhcp" => "DHCP"),
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dev_id"       => array("LABEL"                  => $arrLang["Device"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "HIDDEN",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ereg",
                                                     "VALIDATION_EXTRA_PARAM" => "^eth[[:digit:]]{1,2}$"));

    $strReturn ="";

    $pNet = new paloNetwork();

    // MANEJO DE ACCIONES

    if(isset($_POST['edit'])) {

        $arrNetwork = $pNet->obtener_configuracion_red();

        if(is_array($arrNetwork)) {
            $arrNetworkData['dns1'] = isset($arrNetwork['dns'][0])?$arrNetwork['dns'][0]:'';
            $arrNetworkData['dns2'] = isset($arrNetwork['dns'][1])?$arrNetwork['dns'][1]:'';
            $arrNetworkData['host'] = isset($arrNetwork['host'])?$arrNetwork['host']:'';
            $arrNetworkData['gateway'] = isset($arrNetwork['gateway'])?$arrNetwork['gateway']:'';
        }

        $oForm = new paloForm($smarty, $arrFormNetwork);
        $smarty->assign("ETHERNET_INTERFASES_LIST", "");
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("SAVE", $arrLang["Save"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
	$smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
        $strReturn = $oForm->fetchForm("$local_templates_dir/network.tpl", $arrLang["Network Parameters"], $arrNetworkData);

    } else if(isset($_POST['save_network_changes'])) {

        $oForm = new paloForm($smarty, $arrFormNetwork);

        if($oForm->validateForm($_POST)) {
            $arrNetConf['host'] = $_POST['host']; 
            $arrNetConf['dns_ip_1'] = $_POST['dns1'];
            $arrNetConf['dns_ip_2'] = $_POST['dns2'];
            $arrNetConf['gateway_ip'] = $_POST['gateway'];
            $pNet->escribir_configuracion_red_sistema($arrNetConf);
            if(!empty($pNet->errMsg)) {
                $smarty->assign("mb_message", $pNet->errMsg);
            } else {
                header("Location: index.php?menu=network");
            }
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $smarty->assign("CANCEL", $arrLang["Cancel"]);
            $smarty->assign("SAVE", $arrLang["Save"]);
            $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);    
            $strReturn=$oForm->fetchForm("$local_templates_dir/network.tpl", $arrLang["Network Parameters"], $_POST);
        }
	$smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
        // Se aplasto el boton de grabar los cambios en la red

    } else if(isset($_POST['cancel_interfase_edit'])) {

        header("Location: index.php?menu=network");

    } else if(isset($_POST['save_interfase_changes'])) {

        $oForm = new paloForm($smarty, $arrFormInterfase);

        if($oForm->validateForm($_POST)) {
	    $smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
            if($pNet->escribirConfiguracionInterfaseRed($_POST['dev_id'], $_POST['type'], $_POST['ip'], $_POST['mask'])) {
                header("Location: index.php?menu=network");
            } else {
                $smarty->assign("mb_message", $pNet->errMsg);
            }
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $smarty->assign("CANCEL", $arrLang["Cancel"]);
            $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
            $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
            $smarty->assign("EDIT_PARAMETERS", $arrLang["Edit Network Parameters"]);
	    $smarty->assign("icon","/modules/$module_name/images/system_hardware_detector.png");
            $smarty->assign("CONFIRM_EDIT", $arrLang["Are you sure you want to edit network parameters?"]);
            $strReturn=$oForm->fetchForm("$local_templates_dir/network_edit_interfase.tpl", "{$arrLang['Edit Interface']} \"Ethernet ??\"", $_POST);
        }
    } else if(isset($_GET['action']) && $_GET['action'] == "editInterfase") {

        // TODO: Revisar si el $_GET['id'] contiene un id valido
        $arrEths = $pNet->obtener_interfases_red_fisicas();
        $arrEth = $arrEths[$_GET['id']];

        if(is_array($arrEth)) {
            $arrInterfaseData['ip'] = $arrEth['Inet Addr'];
            $arrInterfaseData['mask'] = $arrEth['Mask'];
            $arrInterfaseData['type'] = $arrEth['Type'];
            $arrInterfaseData['dev_id'] = $_GET['id'];
        }

        $oForm = new paloForm($smarty, $arrFormInterfase);
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $smarty->assign("EDIT_PARAMETERS", $arrLang["Edit Network Parameters"]);
	$smarty->assign("icon","/modules/$module_name/images/system_hardware_detector.png");
        $smarty->assign("CONFIRM_EDIT", $arrLang["Are you sure you want to edit network parameters?"]);
        $strReturn = $oForm->fetchForm("$local_templates_dir/network_edit_interfase.tpl", "{$arrLang['Edit Interface']} \"" . $arrEth['Name'] . "\"", $arrInterfaseData);
    } else {
        // SECCION NETWORK PARAMETERS
        $arrNetwork = $pNet->obtener_configuracion_red();

        if(is_array($arrNetwork)) {
            $arrNetworkData['dns1'] = isset($arrNetwork['dns'][0])?$arrNetwork['dns'][0]:'';
            $arrNetworkData['dns2'] = isset($arrNetwork['dns'][1])?$arrNetwork['dns'][1]:'';
            $arrNetworkData['host'] = isset($arrNetwork['host'])?$arrNetwork['host']:'';
            $arrNetworkData['gateway'] = isset($arrNetwork['gateway'])?$arrNetwork['gateway']:'';
        }

        $oForm = new paloForm($smarty, $arrFormNetwork);
        $oForm->setViewMode();

        // SECCION ETHERNET LIST
        $arrData = array();
        $arrEths = $pNet->obtener_interfases_red_fisicas();
        $end = count($arrEths);

        foreach($arrEths as $idEth=>$arrEth) {
            $arrTmp    = array();
            $arrTmp[0] = "&nbsp;<a href='?menu=network&action=editInterfase&id=$idEth'>" . $arrEth['Name'] . "</a>";
            $arrTmp[1] = strtoupper($arrEth['Type']);
            $arrTmp[2] = $arrEth['Inet Addr'];
            $arrTmp[3] = $arrEth['Mask'];
            $arrTmp[4] = $arrEth['HWaddr'];
            $arrTmp[5] = isset($arrEth['HW_info'])?$arrEth['HW_info']:''; //- Deberia acotar este campo pues puede ser muy largo
            $arrTmp[6] = ($arrEth['Running']=="Yes" ? "<font color=green>{$arrLang["Connected"]}</font>" : "<font color=red>{$arrLang["Not Connected"]}</font>");
            $arrData[] = $arrTmp;
        }

        $oGrid = new paloSantoGrid($smarty);
        $oGrid->pagingShow(false);

        $arrGrid = array("title"    => $arrLang["Ethernet Interfaces List"],
                         "icon"     => "/modules/$module_name/images/system_hardware_detector.png",
                         "width"    => "99%",
                         "start"    => "1",
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => $arrLang["Device"],
                                                        "property1" => ""),
                                             1 => array("name"      => $arrLang["Type"],
                                                        "property1" => ""),
                                             2 => array("name"      => $arrLang["IP"],
                                                        "property1" => ""),
                                             3 => array("name"      => $arrLang["Mask"],
                                                        "property1" => ""),
                                             4 => array("name"      => $arrLang["MAC Address"],
                                                        "property1" => ""),
                                             5 => array("name"      => $arrLang["HW Info"],
                                                        "property1" => ""),
                                             6 => array("name"      => $arrLang["Status"],
                                                        "property1" => "")
                                            ));

        $htmlGrid = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
        $smarty->assign("ETHERNET_INTERFASES_LIST", $htmlGrid);
        $smarty->assign("EDIT_PARAMETERS", $arrLang["Edit Network Parameters"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
	$smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
        // DISPLAY
        $strReturn = $oForm->fetchForm("$local_templates_dir/network.tpl", $arrLang["Network Parameters"], $arrNetworkData);
    }

    return $strReturn;
}
?>
