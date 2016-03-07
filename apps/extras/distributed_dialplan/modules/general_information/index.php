<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                               |
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
  $Id: index.php,v 1.1 2008-12-20 04:12:14 Andres Flores aflores@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoNetwork.class.php";


function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoGeneralInformation.class.php";
    include_once "modules/peers_information/libs/paloSantoPeersInformation.class.php";

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
    $pDB = new paloDB($arrConfModule['dsn_conn_database_1']);


    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "upload":
            $content = uploadGeneralInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        default:
            $content = formGeneralInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
    }
    return $content;
}

function uploadGeneralInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang)
{
   $pElastixConnection = new paloSantoGeneralInformation($pDB);
   $pPeersInformation = new paloSantoPeersInformation($pDB);
   $arrForm = createFieldForm($arrLang);
   $oForm = new paloForm($smarty, $arrForm);
   $pNet = new paloNetwork();
   $mac = "";
   $data = array();
   $writting = false;
   $macCertificate = "";
   $root_certicate = "/var/lib/asterisk/keys";
   if(!$oForm->validateForm($_POST)){
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores=$oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
        foreach($arrErrores as $k=>$v) {
           $strErrorMsg .= "$k, ";
        }
        $strErrorMsg .= "";
        $smarty->assign("mb_message", $strErrorMsg);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Company Information"), $_POST);

   }else{
        $upload = $_POST['command'];
        $data['organization'] = $_POST['organization'];
        $data['locality']     = $_POST['locality'];
        $data['stateprov']    = $_POST['state'];
        $data['country']      = $_POST['country'];
        $data['email']        = $_POST['email'];
        $data['phone']        = $_POST['phone'];
        $data['department']   = $_POST['department'];
        $data['secret']       = $pElastixConnection->genRandomPassword(32, "");

       if($upload == "1"){
            $result = $pElastixConnection->uploadInformation('general', $data);
            if(!$result){
                $smarty->assign("mb_title", _tr('ERROR').":");
                $smarty->assign("mb_message", _tr("Error. Please try again."));
		return formGeneralInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            }else{
                $smarty->assign("mb_title", _tr('MESSAGE').":");
		        $smarty->assign("mb_message", _tr("Information has been saved."));}
       }else{
            $result = $pElastixConnection->addInformation($data);
            if(!$result){
                $smarty->assign("mb_title", _tr('ERROR').":");
		        $smarty->assign("mb_message", _tr("Error. Please try again."));
                return formGeneralInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
	        }else{
                $smarty->assign("mb_title", _tr('MESSAGE').":");
		        $smarty->assign("mb_message", _tr("Information has been saved."));}
       }
       $serverIp = $_SERVER['SERVER_ADDR'];
       $arrEths = $pNet->obtener_interfases_red_fisicas();
       foreach($arrEths as $idEth=>$arrEth)
       {
          if($arrEth['Inet Addr'] == $serverIp);
            $mac = $arrEths['eth0']['HWaddr'];
       }
       $writting =  $pElastixConnection->createFileDGCE($data, $mac);
       $writting =  $pElastixConnection->createFileDMCE($serverIp) ;
       $macCertificate .="CER".str_replace(":","",$mac);
       if(!file_exists("$root_certicate/$macCertificate.pub")){
           if($pElastixConnection->updateCertificate($macCertificate))
                $genCertificate = $pPeersInformation->GenKeyPub($macCertificate);
       }
   }
   $contenidoModulo = formGeneralInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
   return $contenidoModulo;

}


function formGeneralInformation($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pGeneralInformation = new paloSantoGeneralInformation($pDB);
    $arrFormGeneralInformation = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormGeneralInformation);

    $smarty->assign("UPLOAD", $arrLang["Save"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("icon", "images/list.png");
    $command = "0";
    $arrData = array();//array que se envia al $htmlForm para que muestre siempre los datos
    $datos = array();//array que usuamos para definir si le enviamos $_POST o $arrData a $htmlForm
    $arrDataInfo = $pGeneralInformation->getGeneralInformation();
    if(is_array($arrDataInfo) && count($arrDataInfo)>0)
    {
        foreach($arrDataInfo as $key => $datos){
           $arrData['organization'] = $datos['organization'];
           $arrData['department'] = $datos['department'];
           $arrData['locality'] = $datos['locality'];
           $arrData['state'] = $datos['stateprov'];
           $arrData['country'] = $datos['country'];
           $arrData['email'] = $datos['email'];
           $arrData['phone'] = $datos['phone'];
           $arrData['id'] = $datos['id'];
        }
        $datos = $arrData;
        $command = "1";
    }else
        $datos = $_POST;

    $smarty->assign("command", $command);
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Company Information"), $datos);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldForm($arrLang)
{
    $arrOptions = array('val1' => 'Value 1', 'val2' => 'Value 2', 'val3' => 'Value 3');

    $arrFields = array(
            "organization"   => array(      "LABEL"                  => $arrLang["Organization"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "department"   => array(      "LABEL"                  => $arrLang["Department"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "locality"   => array(      "LABEL"                  => _tr("City"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "state"   => array(      "LABEL"                  => _tr("State/Province"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "country"   => array(      "LABEL"                  => $arrLang["Country"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "email"   => array(      "LABEL"                  => $arrLang["Email"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "email",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone"   => array(      "LABEL"                  => $arrLang["Phone"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),

            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if(getParameter("upload"))
        return "upload";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}
?>
