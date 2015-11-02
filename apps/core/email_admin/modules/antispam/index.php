<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-01 05:09:56 Bruno Macias <bmacias@palosanto.com> Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "configs/email.conf.php";
    include_once "libs/cyradm.php";
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAntispam.class.php";

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfModule['templates_dir']))?$arrConfModule['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $accion = getAction();

    $content = "";
    switch($accion)
    {

        case "update":
            $content = updateAntispam($smarty, $module_name, $local_templates_dir, $arrConf, $arrConfModule);
            break;

        default:
            $content = formAntispam($smarty, $module_name, $local_templates_dir, $arrConf, $arrConfModule);
            break;
    }

    return $content;
}

function updateAntispam($smarty, $module_name, $local_templates_dir, $arrConf, $arrConfModule)
{
    $status    = getParameter("status");
    $level     = getParameter("levelnum");
    $header    = getParameter("header");
    $time_spam = getParameter("time_spam");
    $politica  = getParameter("politica");

    $objAntispam = new paloSantoAntispam(
        $arrConfModule['path_postfix'],
        $arrConfModule['path_spamassassin'],
        $arrConfModule['file_master_cf'],
        $arrConfModule['file_local_cf']);
    $isOk = $objAntispam->changeFileLocal($level,$header);
    if($isOk === false){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", $objAntispam->errMsg);
    }

    if($status == "on"){
        $isOk = $objAntispam->activateSpamFilter(($politica == 'capturar_spam') ? $time_spam : NULL);

        if($isOk === false){
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", $objAntispam->errMsg);
        }else{
            $smarty->assign("mb_title", _tr("Message"));
            $smarty->assign("mb_message", _tr("Successfully Activated Service Antispam"));
        }
    }else if($status == "off"){
        $isOk = $objAntispam->disactivateSpamFilter();

        if($isOk === false){
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", $objAntispam->errMsg);
        }else{
            $smarty->assign("mb_title", _tr("Message"));
            $smarty->assign("mb_message", _tr("Successfully Desactivated Service Antispam"));
        }
    }

    return formAntispam($smarty, $module_name, $local_templates_dir, $arrConf, $arrConfModule);
}

function formAntispam($smarty, $module_name, $local_templates_dir, $arrConf, $arrConfModule)
{
    $arrFormConference = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("LEGEND", _tr("Legend"));
    $smarty->assign("UPDATE", _tr("Save"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "modules/$module_name/images/email_antispam.png");


    $objAntispam = new paloSantoAntispam($arrConfModule['path_postfix'], $arrConfModule['path_spamassassin'],$arrConfModule['file_master_cf'], $arrConfModule['file_local_cf']);
    $activated = $objAntispam->isActiveSpamFilter();
    if($activated){
        $arrData['status'] = "on";
		$smarty->assign("statusSpam", "active");
    }else{
        $arrData['status'] = "off";
		$smarty->assign("statusSpam", "desactive");
	}

    $val = $objAntispam->getTimeDeleteSpam();
    if($val != '') $arrData['time_spam'] = $val;
    $statusSieve = ($activated && $val != '') ? 'on' : 'off';
    $arrData['politica'] = ($statusSieve == 'on') ? 'capturar_spam' : 'marcar_asusto';

    $smarty->assign("statusSieve", $statusSieve);
    $valueRequiredHits = $objAntispam->getValueRequiredHits();
    $arrData['levelNUM'] = $valueRequiredHits['level'];
    $arrData['header'] = $valueRequiredHits['header'];
    $smarty->assign("levelNUM", $arrData['levelNUM']);
    $smarty->assign("level", _tr('Level'));
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr("Antispam"), $arrData);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldForm()
{

    $arrPolitics    = array('marcar_asusto' => _tr('Mark Subject')."...", 'capturar_spam' => _tr('Spam Capture'));
    $arrSpamFolders = array("one_week"=>_tr("Delete Spam for more than one week"), "two_week"=>_tr("Delete Spam for more than two week"), "one_month"=>_tr("Delete Spam for more than one month"));

    $arrFields = array(
            "status"            => array(   "LABEL"                  => _tr("Status"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "header"            => array(   "LABEL"                  => _tr("Rewrite Header"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "politica"          => array(   "LABEL"                  => _tr("Politics"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrPolitics,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                ),
            "time_spam"          => array(   "LABEL"                  => _tr("Empty Spam Folders"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSpamFolders,
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
    if(getParameter("update"))
        return "update";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}?>
