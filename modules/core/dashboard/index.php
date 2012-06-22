<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0                                                  |
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

// require_once files framework elastix.
require_once "libs/paloSantoForm.class.php";
require_once "libs/xajax/xajax.inc.php";
require_once "libs/paloSantoDB.class.php";
require_once "libs/paloSantoACL.class.php";

function _moduleContent(&$smarty,$module_name){
    // require_once files this module
    require_once "modules/$module_name/libs/paloSantoDashboard.class.php";
    require_once "modules/$module_name/configs/default.conf.php";

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
    $local_templates_dir  = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //start ajax resource
    //$contenido = startXajaxRefresh($local_templates_dir,$module_name);
    $contenido = getDashboard($local_templates_dir,$module_name);
    return $contenido;
}

/** Start Implementation ajax*/
function startXajaxRefresh($local_templates_dir,$module_name)
{
    $xajax = new xajax();
    $xajax->registerFunction("refreshDashboard");
    $xajax->processRequests();

    $id_xajax_content = 
    "<div id='xajax_content'> </div>
     <script type='text/javascript'> 
        xajax_refreshDashboard('$local_templates_dir','$module_name');
        /*function ejecutarAjax()
        {
            xajax_refreshDashboard('$local_templates_dir','$module_name');
            setTimeout(ejecutarAjax(),10000);
        }*/
     </script>";
    $contenido = $xajax->printJavascript("libs/xajax/");
    return $contenido.$id_xajax_content;
}

function refreshDashboard($local_templates_dir,$module_name)
{
    $respuesta = new xajaxResponse();
    $contenido = getDashboard($local_templates_dir,$module_name);
    $respuesta->addAssign("xajax_content","innerHTML",$contenido);
    return $respuesta;
}
/** End Implementation ajax*/

function getDashboard($local_templates_dir,$module_name)
{
    global $arrConf;
    global $arrLang;
    global $smarty;

    $callsRows   =$arrLang["Error at read yours calls."];
    $faxRows     =$arrLang["Error at read yours faxes."];
    $voiceMails  =$arrLang["Error at read yours voicemails."];
    $mails       =$arrLang["Error at read yours mails."];
    $systemStatus=$arrLang["Error at read status system."];
    $eventsRows  =$arrLang["Error at read your calendar."];

    $pDB = conectionAsteriskCDR();
    if($pDB){
        $objUserInfo = new paloSantoDashboard($pDB);
        $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);

        if(is_array($arrData) && count($arrData)>0){
            $extension = isset($arrData['extension'])?$arrData['extension']:"";
            $email     = "{$arrData['login']}.{$arrData['domain']}";
            $passw     = isset($arrData['password'])?$arrData['password']:"";
            $numRegs   = 5;

            $callsRows   = $objUserInfo->getLastCalls($extension,$numRegs);
            $faxRows     = $objUserInfo->getLastFaxes($extension,$numRegs);
            $voiceMails  = $objUserInfo->getVoiceMails($extension,$numRegs);
            $mails       = $objUserInfo->getMails($email,$passw,$numRegs);
            $systemStatus= $objUserInfo->getSystemStatus($email,$passw);
            $eventsRows  = $objUserInfo->getEventsCalendar($arrData['id'], $numRegs);
        }
    }

    $smarty->assign("userInf",$arrLang["Dashboard"]);
    $smarty->assign("calls",$arrLang["Calls"]);
    $smarty->assign("emails",$arrLang["Em@ils"]);
    $smarty->assign("faxes",$arrLang["Faxes"]);
    $smarty->assign("voicemails",$arrLang["Voicem@ils"]);
    $smarty->assign("calendar",$arrLang["Calendar"]);
    $smarty->assign("system",$arrLang["System"]);
    $smarty->assign("callsRows",$callsRows);
    $smarty->assign("faxRows",$faxRows);
    $smarty->assign("voiceMails",$voiceMails);
    $smarty->assign("mails",$mails);
    $smarty->assign("systemStatus",$systemStatus);
    $smarty->assign("calendarEvents",$eventsRows);

    $oForm = new paloForm($smarty,array());
    $contenido = $oForm->fetchForm($local_templates_dir."/dashboard.tpl",$arrLang["Dashboard"]);
    return $contenido;
}

function conectionAsteriskCDR()
{
    include_once "libs/paloSantoConfig.class.php";
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);
    $dsnAsteriskCDR = $arrConfig['AMPDBENGINE']['valor']."://".
                      $arrConfig['AMPDBUSER']['valor']. ":".
                      $arrConfig['AMPDBPASS']['valor']. "@".
                      $arrConfig['AMPDBHOST']['valor']."/asteriskcdrdb";
    $pDB = new paloDB($dsnAsteriskCDR);

    if(!empty($pDB->errMsg)) 
        return false;
    else
        return $pDB;
}
?>
