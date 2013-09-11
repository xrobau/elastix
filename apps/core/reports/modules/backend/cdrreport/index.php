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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:21 gcarrillo Exp $ 
  $Id: index.php,v 2.0.0.0 2012/12/26 21:31:21 rmera Exp $ */


include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoCDR.class.php";
require_once "libs/misc.lib.php";

function _moduleContent(&$smarty, $module_name)
{
    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    $local_templates_dir = getWebDirModule($module_name);

    //comprobacion de la credencial del usuario, el usuario superadmin es el unica capaz de crear
    //y borrar usuarios de todas las organizaciones
    //los usuarios de tipo administrador estan en la capacidad crear usuarios solo de sus organizaciones
    $arrCredentiasls=getUserCredentials();
    $userLevel1=$arrCredentiasls["userlevel"];
    $userAccount=$arrCredentiasls["userAccount"];
    $idOrganization=$arrCredentiasls["id_organization"];
    $domain=$arrCredentiasls["domain"];
    
    // DSN para consulta de cdrs
    $dsn = generarDSNSistema('asteriskuser', 'asteriskcdrdb');
    $pDB     = new paloDB($dsn);
    
    $action = getAction();
    $content = "";
       
    switch($action){
        default: // report
            $content = reportCDR($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $domain);
            break;
    }
    return $content;
}

function reportCDR($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $org_domain){
    $oCDR    = new paloSantoCDR($pDB);
    $pDBACL = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $oGrid = new paloSantoGrid($smarty);
    
    
    if($userLevel1=="superadmin"){
        $domain=getParameter("organization");
        if(!empty($domain)){
            if($domain=='all')
                $domain = NULL;
        }else{
            $domain = NULL;
        }
    }else{
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/",$org_domain)){
            $smarty->assign('mb_message', "<b>"._tr("Invalid User")."</b>");
            return "";
        }
        $domain = $org_domain;
    }
    
    // Parámetros base y validación de parámetros
    $url = array('menu' => $module_name);
    $paramFiltroBase = $paramFiltro = array(
        'date_start'    => date("d M Y"), 
        'date_end'      => date("d M Y"),
        'field_name'    => 'dst',
        'field_pattern' => '',
        'status'        => 'ALL',
        'ringgroup'     =>  '',
        'calltype'      => '',
    );
    
    foreach (array_keys($paramFiltro) as $k) {
        if (!is_null(getParameter($k))){
            $paramFiltro[$k] = getParameter($k);
        }
    }
    
    //filtrado por el dominio de la organizacion
    $paramFiltro["organization"]=$domain;
    
    // Para usuarios que no son administradores, se restringe a los CDR de la
    // propia extensión
    if($userLevel1=="other"){
        //obtenemos la extension del usuario
        $arrExten = $pACL->getExtUser($pACL->getIdUser($userAccount));
        if($arrExten==false){
            if(!$isAdministrator){
                $smarty->assign('mb_message', "<b>"._tr("contact_admin")."</b>");
                return "";
            }
        }else{
            $paramFiltro["extension"]=$arrExten["exten"];
            $paramFiltro["device_dial"]=$arrExten["dial"];
        }
    }
    
    //filtro option
    $arrOrgz=array();
    if($userLevel1=="superadmin"){
        $arrOrgz=array("all"=>"all");
        $pORGZ = new paloSantoOrganization($pDBACL);
        foreach(($pORGZ->getOrganization()) as $value){
            if($value["id"]!=1)
                $arrOrgz[$value["domain"]]=$value["name"];
        }
        if(is_null($domain)){
            $fil_domain="all";
        }else
            $fil_domain=$domain;
        $oGrid->addComboAction($name_select="organization",_tr("Organization"), $arrOrgz, $fil_domain, $task="report", 'javascript:submit();');
    }
    
    $arrFormElements = createFieldFilter($arrOrgz,$domain);
    $oFilterForm = new paloForm($smarty, $arrFormElements);

    $valueFieldName = $arrFormElements['field_name']["INPUT_EXTRA_PARAM"][$paramFiltro['field_name']];
    $valueStatus = $arrFormElements['status']["INPUT_EXTRA_PARAM"][$paramFiltro['status']];
    $valueRingGRoup = $arrFormElements['ringgroup']["INPUT_EXTRA_PARAM"][$paramFiltro['ringgroup']];

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Start Date")." = ".$paramFiltro['date_start'].", "._tr("End Date")." = ".
    $paramFiltro['date_end'], $paramFiltro, array('date_start' => date("d M Y"),'date_end' => date("d M Y")),true);

    $oGrid->addFilterControl(_tr("Filter applied: ").$valueFieldName." = ".$paramFiltro['field_pattern'],$paramFiltro, array('field_name' => "dst",'field_pattern' => ""));

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Status")." = ".$valueStatus,$paramFiltro, array('status' => 'ALL'),true);

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Ring Group")." = ".$valueRingGRoup,$paramFiltro, array('ringgroup' => ''));
    
    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Call Type")." = ".$paramFiltro['calltype'],$paramFiltro, array('calltype' => ''));
    
    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("userLevel", $userLevel1);
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $paramFiltro);
    $oGrid->showFilter($htmlFilter);
    //fin filtro options
    
    
    // Tradudir fechas a formato ISO para comparación y para API de CDRs.
    $url = array_merge($url, $paramFiltro);
    $paramFiltro['date_start'] = translateDate($paramFiltro['date_start']).' 00:00:00';
    $paramFiltro['date_end'] = translateDate($paramFiltro['date_end']).' 23:59:59';
    
    // esto es necesario hacer debido a los filtros aplicados. 
    $paramFiltro["organization"]=$domain;
        
    // data
    $total=$oCDR->contarCDRs($paramFiltro);
    if(is_null($total)){
        $smarty->assign('mb_message', $oCDR->errMsg);
        return "";
    }
    
    $oGrid->setTitle(_tr("CDR Report"));
    $oGrid->pagingShow(true);
    $oGrid->enableExport();   // enable export.
    $oGrid->setNameFile_Export(_tr("CDRReport"));
    $oGrid->setURL($url);
    if($userLevel1=="superadmin"){
        $arrColumns[]=_tr("Domain");
    }
    $arrColumns[]=_tr("Date");
    $arrColumns[]=_tr("Source");
    if($userLevel1!="superadmin")
        $arrColumns[]=_tr("Ring Group");
    $arrColumns[]=_tr("Destination");
    $arrColumns[]=_tr("Src. Channel");
    $arrColumns[]=_tr("Account Code");
    $arrColumns[]=_tr("Dst. Channel");
    $arrColumns[]=_tr("Call Type");
    $arrColumns[]=_tr("Status");
    $arrColumns[]=_tr("Duration");
    $oGrid->setColumns($arrColumns);
   
    $arrData = null;

    if($oGrid->isExportAction()){
        $arrResult = $oCDR->listarCDRs($paramFiltro);
        if(is_array($arrResult['cdrs']) && $total>0){
            foreach($arrResult['cdrs'] as $key => $value){
                $arrTmp=array();
                if($userLevel1=="superadmin")
                    $arrTmp[] = $value[11];
                $arrTmp[] = $value[0]; //calldate
                $arrTmp[] = $value[1]; //src
                if($userLevel1!="superadmin")
                    $arrTmp[] = $value[10]; //rg_name
                $arrTmp[] = $value[2]; //dst
                $arrTmp[] = $value[3]; //channel
                $arrTmp[] = $value[9]; //accountcode
                $arrTmp[] = $value[4]; //dst_channel
                if($value[12]=="1" || $value[13]=="1"){ //call_type
                    $arrTmp[] = ($value[12]=="1")?"outgoing":"incoming";
                }else
                    $arrTmp[] = "";
                $arrTmp[] = $value[5]; //disposition
                $iDuracion = $value[8]; //billsec
                $iSec = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iSec) / 60);
                $iMin = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iMin) / 60);
                $sTiempo = "{$value[8]}s";
                if ($value[8] >= 60) {
                      if ($iDuracion > 0) $sTiempo .= " ({$iDuracion}h {$iMin}m {$iSec}s)";
                      elseif ($iMin > 0)  $sTiempo .= " ({$iMin}m {$iSec}s)";
                }
                $arrTmp[]=$sTiempo;
                $arrData[] = $arrTmp;
            }
        }
        if (!is_array($arrResult)) {
            $smarty->assign(array(
                'mb_title'      =>  _tr('ERROR'),
                'mb_message'    =>  $oCDR->errMsg,
            ));
        }
    }else {
        $limit = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);
        $offset = $oGrid->calculateOffset();
        $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
        $oGrid->setWidth("99%");
        $oGrid->setStart(($total==0) ? 0 : $offset + 1);
        $oGrid->setEnd($end);
        $oGrid->setTotal($total);
        
        $arrResult = $oCDR->listarCDRs($paramFiltro, $limit, $offset);

        if(is_array($arrResult['cdrs']) && $total>0){
            foreach($arrResult['cdrs'] as $key => $value){
                $arrTmp=array();
                if($userLevel1=="superadmin")
                    $arrTmp[] = $value[11];
                $arrTmp[] = $value[0]; //calldate
                $arrTmp[] = $value[1]; //src
                if($userLevel1!="superadmin")
                    $arrTmp[] = $value[10]; //rg_name
                $arrTmp[] = $value[2]; //dst
                $arrTmp[] = $value[3]; //channel
                $arrTmp[] = $value[9]; //accountcode
                $arrTmp[] = $value[4]; //dst_channel
                if($value[12]=="1" || $value[13]=="1"){ //call_type
                    $arrTmp[] = ($value[12]=="1")?"outgoing":"incoming";
                }else
                    $arrTmp[] = "";
                $arrTmp[] = $value[5]; //disposition
                $iDuracion = $value[8]; //billsec
                $iSec = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iSec) / 60);
                $iMin = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iMin) / 60);
                $sTiempo = "{$value[8]}s";
                if ($value[8] >= 60) {
                      if ($iDuracion > 0) $sTiempo .= " ({$iDuracion}h {$iMin}m {$iSec}s)";
                      elseif ($iMin > 0)  $sTiempo .= " ({$iMin}m {$iSec}s)";
                }
                $arrTmp[]=$sTiempo;
                $arrData[] = $arrTmp;
            }
        }
        if (!is_array($arrResult)) {
            $smarty->assign(array(
                'mb_title'      =>  _tr('ERROR'),
                'mb_message'    =>  $oCDR->errMsg,
            ));
        }
    }

    $oGrid->setData($arrData);
    $content = $oGrid->fetchGrid();
    return $content;
}


function createFieldFilter($arrOrgz,$domain){

    // DSN para consulta de ringgroups
    $dsn_asterisk = generarDSNSistema('asteriskuser', 'elxpbx');
    $pDB=new paloDB($dsn_asterisk);
    $query="SELECT id,rg_name,rg_number from ring_group";
    $rgparam=array();
    if(isset($domain)){
        $query .=" where organization_domain=?";
        $rgparam = array($domain);
    }
    $result=$pDB->fetchTable($query,true,$rgparam);
    foreach($result as $value){
        $dataRG[$value["rg_number"]]=$value["rg_number"]." (".$value["rg_name"].")";
    }
    $dataRG[''] = _tr('(Any ringgroup)');
    
    $arrFormElements = array(
        "date_start"  => array("LABEL"                  => _tr("Start Date"),
                            "REQUIRED"               => "yes",
                            "INPUT_TYPE"             => "DATE",
                            "INPUT_EXTRA_PARAM"      => "",
                            "VALIDATION_TYPE"        => "ereg",
                            "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
        "date_end"    => array("LABEL"                  => _tr("End Date"),
                            "REQUIRED"               => "yes",
                            "INPUT_TYPE"             => "DATE",
                            "INPUT_EXTRA_PARAM"      => "",
                            "VALIDATION_TYPE"        => "ereg",
                            "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
        "field_name"  => array("LABEL"                  => _tr("Field Name"),
                            "REQUIRED"               => "no",
                            "INPUT_TYPE"             => "SELECT",
                            "INPUT_EXTRA_PARAM"      => array( "dst"         => _tr("Destination"),
                                                               "src"         => _tr("Source"),
                                                               "channel"     => _tr("Src. Channel"),
                                                               "accountcode" => _tr("Account Code"),
                                                               "dstchannel"  => _tr("Dst. Channel")),
                            "VALIDATION_TYPE"        => "ereg",
                            "VALIDATION_EXTRA_PARAM" => "^(dst|src|channel|dstchannel|accountcode)$"),
        "field_pattern" => array("LABEL"                  => _tr("Field"),
                            "REQUIRED"               => "no",
                            "INPUT_TYPE"             => "TEXT",
                            "INPUT_EXTRA_PARAM"      => "",
                            "VALIDATION_TYPE"        => "ereg",
                            "VALIDATION_EXTRA_PARAM" => "^[\*|[:alnum:]@_\.,/\-]+$"),
        "status"  => array("LABEL"                  => _tr("Status"),
                            "REQUIRED"               => "no",
                            "INPUT_TYPE"             => "SELECT",
                            "INPUT_EXTRA_PARAM"      => array(
                                                        "ALL"         => _tr("ALL"),
                                                        "ANSWERED"    => _tr("ANSWERED"),
                                                        "BUSY"        => _tr("BUSY"),
                                                        "FAILED"      => _tr("FAILED"),
                                                        "NO ANSWER "  => _tr("NO ANSWER")),
                            "VALIDATION_TYPE"        => "text",
                            "VALIDATION_EXTRA_PARAM" => ""),
        "ringgroup"  => array("LABEL"                  => _tr("Ring Group"),
                            "REQUIRED"               => "no",
                            "INPUT_TYPE"             => "SELECT",
                            "INPUT_EXTRA_PARAM"      => $dataRG ,
                            "VALIDATION_TYPE"        => "text",
                            "VALIDATION_EXTRA_PARAM" => ""),
        "calltype"  => array("LABEL"                  => _tr("Call Type"),
                            "REQUIRED"               => "no",
                            "INPUT_TYPE"             => "SELECT",
                            "INPUT_EXTRA_PARAM"      => array(""=>"all","incoming"=>"incoming","outgoing"=>"outgoing") ,
                            "VALIDATION_TYPE"        => "text",
                            "VALIDATION_EXTRA_PARAM" => ""),
        "organization"  => array("LABEL"                  => _tr("Organization"),
                      "REQUIRED"               => "no",
                      "INPUT_TYPE"             => "SELECT",
                      "INPUT_EXTRA_PARAM"      => $arrOrgz,
                      "VALIDATION_TYPE"        => "domain",
                      "VALIDATION_EXTRA_PARAM" => "",
                      "ONCHANGE"           => "javascript:submit();"),
        );
        
    return $arrFormElements;
}

function getAction(){
    return "report"; //cancel
}
?>