<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                               |
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
  $Id: index.php,v 1.1 2008-08-25 05:08:01 jvega jvega@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoCurrency.class.php";

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
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);

    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "save":
            $content = saveCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        default:
            $content = formCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
    }
    return $content;
}

function formCurrency($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pCurrency = new paloSantoCurrency($pDB);
    $arrFormCurrency = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormCurrency);

    //CARGAR CURRENCY GUARDADO
    $curr = loadCurrentCurrency($pDB);

    if( $curr == false ) $curr = "$";

    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("icon", "modules/$module_name/images/system_preferences_currency.png");
    $_POST['currency'] = $curr;

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",$arrLang["Currency"], $_POST);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function saveCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang)
{
    $curr = getParameter("currency");
    $oPalo = new paloSantoCurrency($pDB);
    //print_r($curr);
    $bandera = $oPalo->SaveOrUpdateCurrency($curr);

    if($bandera == true ){
        $smarty->assign("mb_message", $arrLang["Successfully saved"]);
    }
    else{
        $smarty->assign("mb_title", $arrLang["Error"]);
        $smarty->assign("mb_message", $oPalo->errMsg);
    }

    return formCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
}

function createFieldForm($arrLang)
{
    $arrOptions = array('val1' => 'Value 1', 'val2' => 'Value 2', 'val3' => 'Value 3');

    $arrFields = array(
            "currency"   => array(  "LABEL"                  => $arrLang["Currency"],
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => getCurrencys($arrLang),
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "",
                                    "EDITABLE"               => "si",
                                            ),
            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if(getParameter("save"))
        return "save";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}

function loadCurrentCurrency($pDB)
{
    $oPalo = new paloSantoCurrency($pDB);
    return $oPalo->loadCurrency();
}

function getCurrencys($arrLang)
{
    return array(
            "AR$"   => "AR$ - ".$arrLang["Argentinian peso"],
            "฿"     => "฿ - ".$arrLang["Baht tailandés / balboa panameño"],
            "Bs"    => "Bs - ".$arrLang["Bolívar venezolano"],
            "Bs.F." => "Bs.F. - ".$arrLang["Bolívar fuerte venezolana"],
            "¢"     => "¢ - ".$arrLang["Colón costarricense"],
            "C$"    => "C$ - ".$arrLang["Córdoba nicaragüense/dólar canadiense"],
            "₫"     => "₫ - ".$arrLang["Dong vietnamita"],
            "EC$"   => "EC$ - ".$arrLang["Dólar del Caribe Oriental"],
            "Kr"    => "Kr - ".$arrLang["Corona danesa, corona sueca"],
            "£"     => "£ - ".$arrLang["Lira"],
            "L$"    => "L$ - ".$arrLang["Lempira hondureño"],
            "Q"     => "Q - ".$arrLang["Quetzal guatemalteco"],
            "€"     => "€ - ".$arrLang["Euro"],
            "£GBP"  => "£GBP - ".$arrLang["GB Sterling"],
            "R"     => "R - ".$arrLang["Rand sudafricano"],
            "Rp"    => "Rp - ".$arrLang["Rupia indonesia"],
            "Rs"    => "Rs - ".$arrLang["Rupia"],
            "R$"    => "R$ - ".$arrLang["Real brasileño"],
            "руб"   => "руб - ".$arrLang["Rublo ruso"],
            "A$"    => "A$ - ".$arrLang["Dólar australiano"],
            "$"     => "$ - ".$arrLang["Dólar/Peso"],
            "¥"     => "¥ - ".$arrLang["Yen"],
            "₪"     => "₪ - ".$arrLang["Sheqel israelí"],
            "¢"     => "¢ - ".$arrLang["Colón salvadoreño"],
            "元"    => "元 - ".$arrLang["Yuan chino"],
            "৳"     => "৳ - ".$arrLang["Rupia bengalí"],
            "S$"    => "S$ - ".$arrLang["Dólar de Singapur"],
	    "CHF"   => "CHF - ".$arrLang["Swiss Franc"],
    );
}
?>
