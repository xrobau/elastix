<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                                |
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
  $Id: default.conf.php,v 1.1 2008-06-21 09:06:53 Jonathan Exp $
  $Id: default.conf.php,v 1.1 2008-06-30 11:37:40 afigueroa Exp $
*/

include_once "libs/xajax/xajax.inc.php";

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoFoneBridge.class.php";

    //include lang local module
    $lang=get_language();
    $lang_file="modules/$module_name/lang/$lang.lang";
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    if (file_exists("$base_dir/$lang_file"))
        include_once($lang_file);
    else
        include_once("modules/$module_name/lang/en.lang");

    global $arrConf;
    global $arrLang;
    global $arrLangModule;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $accion = getAction();

    $content = "";

    $xajax = new xajax();
    $xajax->registerFunction("span_configuration_update");
    $xajax->processRequests();

    $content = $xajax->printJavascript("libs/xajax/");

    $arrFormConference = createFieldForm($arrLangModule);
    $oForm = new paloForm($smarty,$arrFormConference);
    switch($accion)
    {
        case "configure":
            $content .= configure_FoneBridge($smarty, $module_name, $local_templates_dir, $arrLang, $oForm, $arrLangModule);
            break;
        default:
            $content .= form_FoneBridge($smarty, $module_name, $local_templates_dir, $arrLang, $oForm, $arrLangModule);
            break;
    }

    return $content;
}

function span_configuration_update($id_select_type, $val_select_type, $id_select_framing, $val_select_framing, $id_select_encoding, $val_select_encoding)
{
    $arrOptions_type        = array('E1' => 'E1', 'T1' => 'T1');
    $arrOptions_framing_e1  = array('ccs' => 'ccs', 'cas' => 'cas');
    $arrOptions_framing_t1  = array('esf' => 'esf', 'sf' => 'sf'/*, 'd4' => 'd4'*/);
    $arrOptions_encoding_e1 = array('hdb3' => 'hdb3', 'ami' => 'ami');
    $arrOptions_encoding_t1 = array('b8zs' => 'b8zs', 'ami' => 'ami');
    $arrOptions_extra       = array('crc4' => 'crc4', 'loopback' => 'loopback', 'rbs' => 'rbs');

    $respuesta = new xajaxResponse();

    if($val_select_type == "T1"){
        $arrOptions_framing = $arrOptions_framing_t1;
        $arrOptions_encoding = $arrOptions_encoding_t1;
    }
    else{
        $arrOptions_framing = $arrOptions_framing_e1;
        $arrOptions_encoding = $arrOptions_encoding_e1;
    }

    /*SPAN TYPE*/
    $options_type = combo($arrOptions_type, $val_select_type);
    $respuesta->addAssign($id_select_type, "innerHTML", $options_type);

    /*SPAN FRAMING*/
    $options_framing = combo($arrOptions_framing, $val_select_framing);
    $respuesta->addAssign($id_select_framing, "innerHTML", $options_framing);

    /*SPAN ENCODING*/
    $options_encoding = combo($arrOptions_encoding, $val_select_encoding);
    $respuesta->addAssign($id_select_encoding, "innerHTML", $options_encoding);

    return $respuesta;
}

function configure_FoneBridge($smarty, $module_name, $local_templates_dir, $arrLang, $oForm, $arrLangModule)
{
    $error = true;
    if(!$oForm->validateForm($_POST)){
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores=$oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
        foreach($arrErrores as $k=>$v){
            $strErrorMsg .= "$k, ";
        }
        $strErrorMsg .= "";
        $smarty->assign("mb_message", $strErrorMsg);
    }
    else{
        if($_POST['span1_extra']=="unselected") $_POST['span1_extra']='';
        if($_POST['span2_extra']=="unselected") $_POST['span2_extra']='';
        if($_POST['span3_extra']=="unselected") $_POST['span3_extra']='';
        if($_POST['span4_extra']=="unselected") $_POST['span4_extra']='';

        $paloFoneBridge = new paloSantoFoneBridge("/etc/redfone.conf");
        if( !$paloFoneBridge->saveFileConfFoneBridge($_POST) ){
            $smarty->assign("mb_title",$arrLangModule["Configure Error"].":");
            $smarty->assign("mb_message",$paloFoneBridge->errMsg);
        }
        else{
            if(!$paloFoneBridge->executeFonulator($path = '/etc/redfone.conf')){
                $smarty->assign("mb_title",$paloFoneBridge->errMsg['head'].":");
                $smarty->assign("mb_message",$paloFoneBridge->errMsg['body']);
                $paloFoneBridge->setStatusFoneBridge($module_name,"no");
            }
            else{
                $smarty->assign("mb_title",$arrLang["Info"].":");
                $smarty->assign("mb_message",$arrLangModule["Configuration updated successfully"]);
                $paloFoneBridge->setStatusFoneBridge($module_name, "yes");
                $error = false;
            }
        }
    }

    if($error){
        Assign_Values($_POST, $smarty);
    }

    return form_FoneBridge($smarty, $module_name, $local_templates_dir, $arrLang, $oForm,$arrLangModule);
}

function form_FoneBridge($smarty, $module_name, $local_templates_dir, $arrLang, $oForm, $arrLangModule)
{
    $smarty->assign("CONFIGURE", $arrLangModule["Configure"]);
    $smarty->assign("icon", "modules/$module_name/themes/default/fonebridge.png");
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("general_settings", $arrLangModule["General Settings"]);
    $smarty->assign("status_label", $arrLang["Status"]);
    $smarty->assign("span_config", $arrLangModule["Span Configuration"]);
    $smarty->assign("span_type", $arrLang["Type"]);
    $smarty->assign("span_framing", $arrLangModule["Framing"]);
    $smarty->assign("span_encoding", $arrLangModule["Encoding"]);
    $smarty->assign("span_timing", $arrLangModule["Timing Priority"]);
    $smarty->assign("by_spans", $arrLangModule["By Spans"]);
    $smarty->assign("internal", $arrLangModule["Internal"]);
    $smarty->assign("spans", $arrLangModule["Spans Order"]);
    $smarty->assign("span_extra", $arrLangModule["Extra"]);
    $smarty->assign("span_1", $arrLangModule["Span 1"]);
    $smarty->assign("span_2", $arrLangModule["Span 2"]);
    $smarty->assign("span_3", $arrLangModule["Span 3"]);
    $smarty->assign("span_4", $arrLangModule["Span 4"]);

    $paloFoneBridge = new paloSantoFoneBridge("/etc/redfone.conf");

    $arrValores = array();
    if(!$paloFoneBridge->fileRedFoneExists())
        $paloFoneBridge->setStatusFoneBridge($module_name,"no");
    else
        $arrValores = $paloFoneBridge->getConfigurationFoneBridge();

    $smarty->assign("status_info",$paloFoneBridge->getStatusFoneBridge($module_name));

    //if(count($arrValores)>0){
    if(count($_POST) == 0){
        Assign_Values($arrValores, $smarty); 
        $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", $arrLangModule["FoneBridge"], $arrValores);
    }
    else{
        Assign_Values($_POST, $smarty);
        $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", $arrLangModule["FoneBridge"], $_POST);
    }

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldForm($arrLangModule)
{
    $unselected = "--".$arrLangModule['None']."--";
    $arrOptions_timing = array(/*'unselected' => $unselected,*/ '0' => 'Sp1', '1' => 'Sp2', '2' => 'Sp3', '3' => 'Sp4');
    $arrOptions_extra  = array('unselected' => $unselected, 'crc4' => 'crc4', 'loopback' => 'loopback', 'rbs' => 'rbs');

    $arrFields = array(
            "phone_bridge_ip"   => array(   "LABEL"                  => $arrLangModule["Phone Bridge IP"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "ip",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                ),
            "server_mac"        => array(   "LABEL"                  => $arrLangModule["Server MAC"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => "[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}"
                                ),
            "port_for_TDMoE"    => array(   "LABEL"                  => $arrLangModule["Port for TDMoE"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => array('1' => '1', '2' => '2'),
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "priority1"         => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_timing,
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "priority2"         => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_timing,
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "priority3"         => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_timing,
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "priority4"         => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_timing,
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "span1_extra"       => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_extra,
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "span2_extra"       => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_extra,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "span3_extra"       => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_extra,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "span4_extra"       => array(   "LABEL"                  => "",
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions_extra,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                )
            );
    return $arrFields;
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
    else if(getParameter("update"))
        return "update";
    else if(getParameter("configure"))
        return "configure";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}

function Assign_Values(&$arrValues, $smarty)
{
    $arrOptions_type        = array('E1' => 'E1', 'T1' => 'T1');
    $arrOptions_framing_e1  = array('ccs' => 'ccs', 'cas' => 'cas');
    $arrOptions_framing_t1  = array('esf' => 'esf', 'sf' => 'sf'/*, 'd4' => 'd4'*/);
    $arrOptions_encoding_e1 = array('hdb3' => 'hdb3', 'ami' => 'ami');
    $arrOptions_encoding_t1 = array('b8zs' => 'b8zs', 'ami' => 'ami');
    $arrOptions_extra       = array('crc4' => 'crc4', 'loopback' => 'loopback', 'rbs' => 'rbs');

    $arrValues['phone_bridge_ip'] = isset($arrValues['phone_bridge_ip'])?$arrValues['phone_bridge_ip']:"";
    $arrValues['port_for_TDMoE']  = isset($arrValues['port_for_TDMoE'])?$arrValues['port_for_TDMoE']:"";
    $arrValues['server_mac']      = isset($arrValues['server_mac'])?$arrValues['server_mac']:"";
    $arrValues['span1_type']      = isset($arrValues['span1_type'])?$arrValues['span1_type']:"";
    $arrValues['span2_type']      = isset($arrValues['span2_type'])?$arrValues['span2_type']:"";
    $arrValues['span3_type']      = isset($arrValues['span3_type'])?$arrValues['span3_type']:"";
    $arrValues['span4_type']      = isset($arrValues['span4_type'])?$arrValues['span4_type']:"";
    $arrValues['span1_framing']   = isset($arrValues['span1_framing'])?$arrValues['span1_framing']:"";
    $arrValues['span2_framing']   = isset($arrValues['span2_framing'])?$arrValues['span2_framing']:"";
    $arrValues['span3_framing']   = isset($arrValues['span3_framing'])?$arrValues['span3_framing']:"";
    $arrValues['span4_framing']   = isset($arrValues['span4_framing'])?$arrValues['span4_framing']:"";
    $arrValues['span1_encoding']  = isset($arrValues['span1_encoding'])?$arrValues['span1_encoding']:"";
    $arrValues['span2_encoding']  = isset($arrValues['span2_encoding'])?$arrValues['span2_encoding']:"";
    $arrValues['span3_encoding']  = isset($arrValues['span3_encoding'])?$arrValues['span3_encoding']:"";
    $arrValues['span4_encoding']  = isset($arrValues['span4_encoding'])?$arrValues['span4_encoding']:"";
    $arrValues['timing_priority'] = isset($arrValues['timing_priority'])?$arrValues['timing_priority']:"by_spans";
    $arrValues['priority1']       = isset($arrValues['priority1'])?$arrValues['priority1']:"0";
    $arrValues['priority2']       = isset($arrValues['priority2'])?$arrValues['priority2']:"1";
    $arrValues['priority3']       = isset($arrValues['priority3'])?$arrValues['priority3']:"2";
    $arrValues['priority4']       = isset($arrValues['priority4'])?$arrValues['priority4']:"3";

    if($arrValues['span1_type'] == "T1")
    {
        $arrOptions_framing_1 = $arrOptions_framing_t1;
        $arrOptions_encoding_1 = $arrOptions_encoding_t1;
    }
    else{
        $arrOptions_framing_1 = $arrOptions_framing_e1;
        $arrOptions_encoding_1 = $arrOptions_encoding_e1;
    }
    if($arrValues['span2_type'] == "T1")
    {
        $arrOptions_framing_2 = $arrOptions_framing_t1;
        $arrOptions_encoding_2 = $arrOptions_encoding_t1;
    }
    else{
        $arrOptions_framing_2 = $arrOptions_framing_e1;
        $arrOptions_encoding_2 = $arrOptions_encoding_e1;
    }
    if($arrValues['span3_type'] == "T1")
    {
        $arrOptions_framing_3 = $arrOptions_framing_t1;
        $arrOptions_encoding_3 = $arrOptions_encoding_t1;
    }
    else{
        $arrOptions_framing_3 = $arrOptions_framing_e1;
        $arrOptions_encoding_3 = $arrOptions_encoding_e1;
    }
    if($arrValues['span4_type'] == "T1")
    {
        $arrOptions_framing_4 = $arrOptions_framing_t1;
        $arrOptions_encoding_4 = $arrOptions_encoding_t1;
    }
    else{
        $arrOptions_framing_4 = $arrOptions_framing_e1;
        $arrOptions_encoding_4 = $arrOptions_encoding_e1;
    }

    if($arrValues['timing_priority'] == "internal")
        $smarty->assign("internal_checked", "checked='checked'");
    else 
        $smarty->assign("by_spans_checked", "checked='checked'");

    /*SPAN TYPE*/
    $options_type = combo($arrOptions_type, trim($arrValues['span1_type']));
    $smarty->assign("select_span1_type", $options_type);

    $options_type = combo($arrOptions_type, trim($arrValues['span2_type']));
    $smarty->assign("select_span2_type", $options_type);

    $options_type = combo($arrOptions_type, trim($arrValues['span3_type']));
    $smarty->assign("select_span3_type", $options_type);

    $options_type = combo($arrOptions_type, trim($arrValues['span4_type']));
    $smarty->assign("select_span4_type", $options_type);

    /*SPAN FRAMING*/
    $options_framing = combo($arrOptions_framing_1, trim($arrValues['span1_framing']));
    $smarty->assign("select_span1_framing", $options_framing);

    $options_framing = combo($arrOptions_framing_2, trim($arrValues['span2_framing']));
    $smarty->assign("select_span2_framing", $options_framing);

    $options_framing = combo($arrOptions_framing_3, trim($arrValues['span3_framing']));
    $smarty->assign("select_span3_framing", $options_framing);

    $options_framing = combo($arrOptions_framing_4, trim($arrValues['span4_framing']));
    $smarty->assign("select_span4_framing", $options_framing);

    /*SPAN ENCODING*/
    $options_encoding = combo($arrOptions_encoding_1, trim($arrValues['span1_encoding']));
    $smarty->assign("select_span1_encoding", $options_encoding);

    $options_encoding = combo($arrOptions_encoding_2, trim($arrValues['span2_encoding']));
    $smarty->assign("select_span2_encoding", $options_encoding);

    $options_encoding = combo($arrOptions_encoding_3, trim($arrValues['span3_encoding']));
    $smarty->assign("select_span3_encoding", $options_encoding);

    $options_encoding = combo($arrOptions_encoding_4, trim($arrValues['span4_encoding']));
    $smarty->assign("select_span4_encoding", $options_encoding);
}
?>
