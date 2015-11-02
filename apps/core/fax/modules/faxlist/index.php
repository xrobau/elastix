<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

include_once "libs/paloSantoFax.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoJSON.class.php";

function _moduleContent($smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $accion = getAction();
    switch($accion){
        case "checkFaxStatus":
            $contenidoModulo = checkFaxStatus("faxListStatus",$smarty, $module_name, $local_templates_dir, $arrConf);
            break;
        default:
            $contenidoModulo = listFax($smarty, $module_name, $local_templates_dir);
            break;
    }
    return $contenidoModulo;
}

function listFax($smarty, $module_name, $local_templates_dir)
{
    $limit = 30;
    $oFax  = new paloFax();
    $total = $oFax->getTotalFax();

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->pagingShow(true);
    $oGrid->setURL("?menu=faxlist");
    $oGrid->setTitle(_tr("Virtual Fax List"));
    $oGrid->setIcon("/modules/$module_name/images/fax_virtual_fax_list.png");

    $arrColumns = array(
        _tr("Virtual Fax Name"),
        _tr("Fax Extension"),
        _tr("Secret"),
        _tr("Destination Email"),
        _tr("Caller ID Name"),
        _tr("Caller ID Number"),
        _tr("Status"));
    $oGrid->setColumns($arrColumns);
    $offset = $oGrid->calculateOffset();

    $arrFax       = $oFax->getFaxList($offset,$limit);
    $arrFaxStatus = $oFax->getFaxStatus();

    $arrData = array();
    foreach($arrFax as $fax) {
        $arrTmp    = array();
        $arrTmp[0] = "&nbsp;<a href='?menu=faxnew&action=view&id=".$fax['id']."'>".$fax['name']."</a>";
        $arrTmp[1] = $fax['extension'];
        $arrTmp[2] = $fax['secret'];
        $arrTmp[3] = $fax['email'];
        $arrTmp[4] = $fax['clid_name'] . "&nbsp;";
        $arrTmp[5] = $fax['clid_number'] . "&nbsp;";
        $arrTmp[6] = msgStatusFaxDevice($arrFaxStatus, $fax['dev_id']);
        $arrData[] = $arrTmp;
    }

    $session = getSession();
    $session['faxlist']['faxListStatus'] = $arrData;
    putSession($session);

    $oGrid->setData($arrData);
    return $oGrid->fetchGrid();
}

function checkFaxStatus($function, $smarty, $module_name, $local_templates_dir, $arrConf)
{
    $executed_time = 1; //en segundos
    $max_time_wait = 30; //en segundos
    $event_flag    = false;
    $data          = null;

    $i = 1;
    while(($i*$executed_time) <= $max_time_wait){
        $return = $function($smarty, $module_name, $local_templates_dir, $arrConf);
        $data   = $return['data'];
        if($return['there_was_change']){
            $event_flag = true;
            break;
        }
        $i++;
        sleep($executed_time); //cada $executed_time estoy revisando si hay algo nuevo....
    }
   return $data;
}

function faxListStatus($smarty, $module_name, $local_templates_dir, $arrConf)
{
    $oFax    = new paloFax();
    $arrFax  = $oFax->getFaxList();
    $status  = TRUE;
    $end = count($arrFax);
    $arrFaxStatus = $oFax->getFaxStatus();
    $arrData    = array();
    foreach($arrFax as $fax) {
        $arrData[$fax['extension']] = msgStatusFaxDevice($arrFaxStatus, $fax['dev_id']);
    }

    $statusArr    = thereChanges($arrData);
    if(empty($statusArr))
        $status = FALSE;
    $jsonObject = new PaloSantoJSON();
    if($status){ //este status es true solo cuando el tecnico acepto al customer (al hacer click)
        //sleep(2); //por si acaso se desincroniza en la tabla customer el campo attended y llenarse los datos de id_chat y id_chat_time
        $msgResponse["faxes"] = $statusArr;
        $jsonObject->set_status("CHANGED");
        $jsonObject->set_message($msgResponse);
    }else{
        $jsonObject->set_status("NOCHANGED");
    }

    return array("there_was_change" => $status,
                 "data" => $jsonObject->createJSON());
}

function msgStatusFaxDevice(&$arrFaxStatus, $fax_dev_id)
{
    if (isset($arrFaxStatus['modems']['ttyIAX'.$fax_dev_id])) {
        return $arrFaxStatus['modems']['ttyIAX'.$fax_dev_id].' on ttyIAX'.$fax_dev_id;
    } else {
        return _tr('(internal error) Fax device does not exist').': ttyIAX'.$fax_dev_id;
    }
}

function thereChanges($data){
    $session = getSession();
    $arrData = array();
    if (isset($session['faxlist']['faxListStatus']) &&
        is_array($session['faxlist']['faxListStatus']))
        $arrData = $session['faxlist']['faxListStatus'];
    $arraResult = array();
    foreach($arrData as $key => $value){
        $fax = $value[1];
        $status = $value[6];
        if(isset($data[$fax]) && $data[$fax] != $status){
            $arraResult[$fax] = $data[$fax];
            $arrData[$key][6] = $data[$fax];
        }
    }
    $session['faxlist']['faxListStatus'] = $arrData;
    putSession($session);
    return $arraResult;
}

function getSession()
{
    session_commit();
    ini_set("session.use_cookies","0");
    if(session_start()){
        $tmp = $_SESSION;
        session_commit();
    }
    return $tmp;
}

function putSession($data)//data es un arreglo
{
    session_commit();
    ini_set("session.use_cookies","0");
    if(session_start()){
        $_SESSION = $data;
        session_commit();
    }
}

function getAction()
{
    if(getParameter("action")=="checkFaxStatus")
        return "checkFaxStatus";
    else
        return "default";
}
?>
