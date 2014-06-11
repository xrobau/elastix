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
  $Id: index.php,v 1.1 2008-08-03 11:08:42 Andres Flores aflores@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoJSON.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoNetwork.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoACL.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoPeersInformation.class.php";
    include_once "modules/general_information/libs/paloSantoGeneralInformation.class.php";

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
    $pDB = new paloDB($arrConf['dsn_conn_database']);


    //actions
    $accion = getAction();
    $content = "";

    /////////
    $pNet = new paloNetwork();
    $root_certicate = "/var/lib/asterisk/keys";
    $local_mac = "";
    $arrEths = $pNet->obtener_interfases_red_fisicas();
    $local_ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";//ip local

    foreach($arrEths as $idEth=>$arrEth){
        if($arrEth['Inet Addr'] == $local_ip);
            $local_mac = $arrEths['eth0']['HWaddr'];
    }
    $macCertificate ="CER".str_replace(":","",$local_mac);

    if(file_exists("$root_certicate/$macCertificate.pub"))
        $_SESSION['exitsKey'] = true;
    else
        $_SESSION['exitsKey'] = false;
    ////////

    ////// para uso de comandos en asterisk
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);
    $dsn_agi_manager['password'] = $arrConfig['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrConfig['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = 'admin';
    /////


    switch($accion){

        case "new_request":
            $content = formRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            break;
        case "request":
            $content = sendConnectionRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            break;
        case "view":
            $content = viewPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            break;
        case "delete_peer":
            $content = deletePeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager); 
            break;
        case "disconnect":
            $content = disconnectPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager); 
            break;
        case "connect":
            $content = connectPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager); 
            break;      
        case "accept_request":
            $content = AcceptPeerRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            $content = connectPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager); 
            break;
        case "reject_request":
            $content = rejectPeerRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            break;
        case "status":
            $content = status($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break; 
        case "disconnected":
            $content = disconnectPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager); 
            break;
        default:
            $content = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            break;

        
    }
    return $content;
}

function disconnectPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager){

   $pPeersInformation = new paloSantoPeersInformation($pDB);
    //$peerId  = $_POST['peerId'];
    $peerId  = getParameter('peerId');   
    $result = $pPeersInformation->StatusDisconnect($peerId);
  
    $local_ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";//ip local
    $action = 6;

    $dataPeer = $pPeersInformation->ObtainPeersDataById($peerId);
    $ip_ask = $dataPeer['host'];
    $mac_ask = $dataPeer['mac'];
    $connect = socketReject($ip_ask, $local_ip, $action);


   $contenidoModulo = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
   return $contenidoModulo;

}


function AcceptPeerRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager)
{
   $pPeersInformation = new paloSantoPeersInformation($pDB);
   $root_certicate = "/var/lib/asterisk/keys"; 
   //$peerId  = $_POST['peerId'];
   $peerId  = getParameter('peerId'); 
   $dataPeer = $pPeersInformation->ObtainPeersDataById($peerId);
   $ip_ask = $dataPeer['host'];
   $mac = $dataPeer['mac'];

   //$mac = $_POST['peerMac'];

 
   //  
  // $ip_ask = $_POST['ipAsk']; 
   
   $company = "";
   $comment = $arrLang["accepted connection"];
   $local_key = "";
   $peer_key = "";
   $local_mac = "";

   $pPeersInformation = new paloSantoPeersInformation($pDB);
   $pGeneralInformation = new paloSantoGeneralInformation($pDB);
   $pNet = new paloNetwork();

   $exist_information = $pGeneralInformation->getGeneralInformation();
   if(is_array($exist_information) && count($exist_information)){
      $company = $exist_information[0]['organization'];
      $result = $pPeersInformation->StatusDisconnect($peerId);
      $arrPeerInfo = $pPeersInformation->ObtainPeersInformation();
      if(is_array($arrPeerInfo) && count($arrPeerInfo)>0){
         foreach($arrPeerInfo as $key => $value)
         {
            if($value['mac'] == $mac)
               $peer_key = $value['key'];
         }
      }

      $pubKey_name = $pPeersInformation->createKeyPubServer($peer_key, $mac);
      $local_ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";
      $arrEths = $pNet->obtener_interfases_red_fisicas();
      foreach($arrEths as $idEth=>$arrEth)
      {
         if($arrEth['Inet Addr'] == $local_ip);
           $local_mac = $arrEths['eth0']['HWaddr'];
      }
      $macCertificate ="CER".str_replace(":","",$local_mac);
      if(file_exists("$root_certicate/$macCertificate.pub")){
         $lineas = file("$root_certicate/$macCertificate.pub");
         foreach ($lineas as $linea_num => $linea)
            $local_key.= htmlspecialchars("$linea");
      }
      $update = $pPeersInformation->UpdateOutKey($macCertificate, $peerId);//mac local
      $action = 2;
      $sent = socketConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $local_ip, $ip_ask , $local_mac, $local_key, $company, $comment, $action);

      if(preg_match( "/^BEGIN[[:space:]](.*)[[:space:]]END/", $sent, $regs ))
      {
          if($regs[1] == "accept"){
            $smarty->assign("mb_title", $arrLang["Message"]);
            $smarty->assign("mb_message", $arrLang["Your connection has been established"]);
            $idPeer = $pPeersInformation->getIdPeer($mac);
            $parameter = $pPeersInformation->createPeerParameter();
            $resultParameter = $pPeersInformation->addInformationParameter($parameter,$idPeer['id']);
          }
      }else{
            $smarty->assign("mb_title", $arrLang["Message"]);
            $smarty->assign("mb_message", $arrLang["Unable to establish connection with the host, it does not exist or is trying to connect himself"]);
          }
  }else{
       $smarty->assign("mb_title", $arrLang["Error"]);
       $smarty->assign("mb_message", $arrLang["You must fill your information General before accepting the request"]);
  }

  $contenidoModulo = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
  return $contenidoModulo;

}

function socketConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $local_ip, $ip_ask ,$local_mac, $local_key, $local_company, $comment, $action)
{
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $arrFormPeersInformation = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormPeersInformation);
    //$port_remote ="80";
    $port_remote ="443";
    $file_remote ="/elastixConnection/request.php";
    $respuesta = "";
    $key_answer = urlencode($local_key);

    $conexion = fsockopen("ssl://".$ip_ask,$port_remote);
    if ($conexion) {
       //echo "Conexion realizada con éxito <br />";
       $dataSend="company_answer=$local_company&comment_answer=$comment&ip_answer=$local_ip&ip_ask=$ip_ask&mac_answer=$local_mac&key_answer=$key_answer&action=$action";
       $dataLenght = strlen($dataSend);
       $headerRequest = createHeaderHttp($ip_ask,$file_remote,$dataLenght);
       fputs($conexion,$headerRequest.$dataSend);
       while(($r = fread($conexion,2048)) != ""){
            $respuesta .= $r;
       }
       $respuesta = strstr($respuesta,"BEGIN");
       fclose($conexion);
    }
  return $respuesta;
}

function createHeaderHttp($ip_remote, $file_remote, $dataLenght)
{
    $headerRequest  = "POST $file_remote HTTP/1.0\r\n";
    $headerRequest .= "Host: $ip_remote\r\n";
    $headerRequest .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $headerRequest .= "Content-Length: $dataLenght\r\n\r\n";
    return $headerRequest;
}

function connectPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager){

   $pPeersInformation = new paloSantoPeersInformation($pDB);
   $peerId  = getParameter('peerId');
   $result = $pPeersInformation->StatusConnect($peerId);

    $local_ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";//ip local
    $action = 5;

    $dataPeer = $pPeersInformation->ObtainPeersDataById($peerId);
    $ip_ask = $dataPeer['host'];
    $mac_ask = $dataPeer['mac'];
    $connect = socketReject($ip_ask, $local_ip, $action);

   $contenidoModulo = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
   return $contenidoModulo;

}

function createFile($smarty, $arrLang, $pDB, $dsn_agi_manager)
{
   $peers = "";
   $pPeersInformation = new paloSantoPeersInformation($pDB);
   $arrDBPeers = $pPeersInformation->ObtainPeersInformation();//aqui barro la Tabla peer
   foreach($arrDBPeers as $infoPeers){
     if($infoPeers['status'] == "connected"){
        foreach($infoPeers as $key => $value){
           if($key == "mac")
              $peers.= "[$value]\n";
           else if($key != "mac" && $key != "id" && $key != "status" && $key !="key" && $key !="comment" && $key !="company")
              $peers.= "$key=$value\n";
           if($key == "id")
           $id = $value;
        }
        $arrInfoParameter = $pPeersInformation->ObtainPeersParametersById($id);
        foreach($arrInfoParameter as $infoParameter){
              $peers.= "{$infoParameter['name']}={$infoParameter['value']}\n";
        }
        $peers.= "\n\n";
     }
   }

   if($pPeersInformation->createFileDPCE($peers, $arrLang)){
      if($pPeersInformation->reloadAsterisk($dsn_agi_manager))
        return true;
   }else{
      if($pPeersInformation->reloadAsterisk($dsn_agi_manager))
        return false;
   }
 
}

function viewPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager)
{
    $pPeersInformation       = new paloSantoPeersInformation($pDB);
    $arrFormPeersInformation = createFieldForm($arrLang);
    $oForm   = new paloForm($smarty,$arrFormPeersInformation);
    $arrDataPeer = array();
    $arrData = array();
    $accept = "yes";
    $idPeer = $_GET['peerId'];
    $opcion = $_GET['opcion'];
    if($opcion == 1)
       $smarty->assign("MODE","view");
    else
       $smarty->assign("MODE","accept");
    $smarty->assign("ACEPT", $arrLang["Accept"]);
    $smarty->assign("REJECT", $arrLang["Reject"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DISCONNECT", $arrLang["Disconnect"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("peerId", $idPeer);
    $smarty->assign("BACK", _tr("Back"));

    $arrDataPeer = $pPeersInformation->ObtainPeersDataById($idPeer);
    $arrData['mac']     = $arrDataPeer['mac'];
    $arrData['model']   = $arrDataPeer['model'];
    $arrData['host']    = $arrDataPeer['host'];
    $arrData['inkey']   = $arrDataPeer['inkey'];
    $arrData['outkey']  = $arrDataPeer['outkey'];
    $arrData['comment'] = $arrDataPeer['comment'];
    $arrData['company'] = $arrDataPeer['company'];
    if($arrDataPeer['status'] == "Requesting connection" || $arrDataPeer['status'] == "disconnected" || $arrDataPeer['status'] == "request accepted")
       $accept = "no";

    $smarty->assign("OPCION",$accept);
    $smarty->assign("peerMac", $arrData['mac']);
    $smarty->assign("ipAsk", $arrData['host']);

    $oForm->setViewMode();
    $htmlForm = $oForm->fetchForm("$local_templates_dir/view_peer.tpl",_tr("View Remote Server"), $arrData);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;

}

function status($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $dataPeer = $pPeersInformation->ObtainPeersDataById(getParameter('conId'));
    //$ip_ask = $dataPeer['host'];
    $status = $dataPeer['status'];
    $his_status = $dataPeer['his_status'];
    $company = $dataPeer['company'];
    $cia="<strong>("._tr("Unknown").")</strong>";
    $color="black";
    $view="<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=1'>"._tr('View')."</a>&nbsp;&nbsp;";
    $con=0;
    if($status == "request reject"){
        $status=_tr("Request Connection has been Rejected");
        $color="red";
        $view="";
    }    

    if($status == "Requesting connection"){
        $status="<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=2'>"._tr("New Connection Request")."</a>";
        $color="blue";
        //$linkToConnect ="<a href='#' onclick='reject(".$dataPeer['id'].")'>"._tr('Reject')."</a>";        
        $linkToConnect ="<a href='?menu=$module_name&action=request_reject&peerId=".$dataPeer['id']."'>"._tr('Reject')."</a>";        
        //$view="<a href='?menu=$module_name&action=request_accept&peerId=".$dataPeer['id']."'>"._tr('Accept')."</a>&nbsp;&nbsp;".$linkToConnect;
        $view="<a href='#' onclick='accept(".$dataPeer['id'].")'>"._tr('Accept')."</a>&nbsp;&nbsp;".$linkToConnect;
   
    }
    
     
     if($status != "request accepted"&&$status != "waiting response"){
       $cia="<strong>".$company."</strong>";
    }
    if($status == "waiting response"){
        $status=_tr("Waiting Response...");
        $color="red";
        $view="";
        
    }   
    if($status == "request accepted"||$status=="connected"){
       $color="green";
    }

    if($status == "request accepted" || $status == "disconnected"){
        //$linkToConnect = "<a href='?menu=$module_name&action=connect&peerId=".$dataPeer['id']."'>"._tr('Connect')."</a>";
        $linkToConnect = "<a href='#' onclick='connect(".$dataPeer['id'].")'>"._tr('Connect')."</a>";        
        $view = "<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=1'>"._tr('View')."</a>&nbsp;&nbsp;".$linkToConnect;
    } 
    if($status == "connected" && $his_status=="disconnected"){   
        $linkToConnect = "<a href='#' onclick='disconnect(".$dataPeer['id'].")'>"._tr('Disconnect')."</a>";        
        //$linkToConnect = "<a href='?menu=$module_name&action=disconnected&peerId=".$dataPeer['id']."'>"._tr('Disconnect')."</a>";
        $view = "<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=1'>"._tr('View')."</a>&nbsp;&nbsp;".$linkToConnect;
        $status=_tr("Requesting Connection to Remote Server..."); 
        $color="blue";
    }
    elseif($status == "connected" && $his_status=="connected"){
        $linkToConnect = "<a href='#' onclick='disconnect(".$dataPeer['id'].")'>"._tr('Disconnect')."</a>";
        //$linkToConnect = "<a href='?menu=$module_name&action=disconnected&peerId=".$dataPeer['id']."'>"._tr('Disconnect')."</a>";
        $view = "<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=1'>"._tr('View')."</a>&nbsp;&nbsp;".$linkToConnect;
        $status=_tr("Connected");
        $color="green"; 
    }
    elseif($status == "disconnected" && $his_status=="connected"){
        $status=_tr("Remote Server is requesting Connection..."); 
        $color="red";
    }
    elseif($status == "disconnected" && $his_status=="disconnected"){
        $status=_tr("Disconnected"); 
        //$linkToConnect = "<a href='?menu=$module_name&action=connect&peerId=".$dataPeer['id']."'>"._tr('Connect')."</a>";
        $linkToConnect = "<a href='#' onclick='connect(".$dataPeer['id'].")'>"._tr('Connect')."</a>";
        $view = "<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=1'>"._tr('View')."</a>&nbsp;&nbsp;".$linkToConnect;
    }
    elseif($his_status=="connection deleted"){
        $view="";
        $status=_tr("Request Connection has been Deleted");
        $color="red"; 
    }
    elseif($status == "request accepted" && $his_status == "connected"){
        $con=1; // Si la solicitud es aceptada, se conecta automaticamente.
        $status=_tr("Connecting..."); 
        $color="blue";
    }
    elseif($status == "connected" || $his_status == "request accepted"){
        $status=_tr("Connecting...  "); 
        $view="<a href='?menu=$module_name&action=view&peerId=".$dataPeer['id']."&opcion=1'>"._tr('View')."</a>&nbsp;&nbsp;";
        $color="blue";
    }
    
    elseif($status == "request accepted" && $his_status == "disconnected"){
        $status=_tr("Request Connection has been Accepted"); 
        $color="blue";
    }
       
    $id = $dataPeer['id'];
    $oForm = new paloForm($smarty,array());
    $jsonObject = new PaloSantoJSON();
   
    $msgResponse['status'] = $status;
    $msgResponse['color'] = $color;
    $msgResponse['id'] = $id;
    $msgResponse['view'] = $view;
    $msgResponse['con'] = $con;
    $msgResponse['cia'] = $cia;
    $jsonObject->set_message($msgResponse);
    return $jsonObject->createJSON();

}

function reportPeersInformation($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang, $dsn_agi_manager)
{
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $field_pattern = getParameter("filter");
    $action = getParameter("nav");
    $start  = getParameter("start");

    
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $totalPeersInformation = $pPeersInformation->ObtainNumPeersInformation();
    $linkToConnect = "";
    $smarty->assign("NEW", $arrLang["New Request"]);
    $smarty->assign("New", _tr("New Request"));
    
    $limit  = 20;
    $total  = $totalPeersInformation;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

    $oGrid->calculatePagination($action,$start);
    $offset = $oGrid->getOffsetValue();
    $end    = $oGrid->getEnd();

    $url = array(
        'menu'    =>  $module_name,
        'filter'  =>  $field_pattern,
    );

    $arrData = null;
    $arrResult =$pPeersInformation->ObtainPeersInformation($limit, $offset, $field_pattern);

    if(is_array($arrResult) && $total>0){
        $status = "";
        $i=0;
        foreach($arrResult as $key => $value){
     
            if($value['status'] == "disconnected")
               $status = $arrLang["disconnected"];
            else if($value['status'] == "connected")
               $status = $arrLang["connected"];
            else if($value['status'] == "waiting response")
               $status = $arrLang["waiting response"];
            else if($value['status'] == "request reject")
               $status = $arrLang["request reject"];
            else if($value['status'] == "request delete")
               $status = $arrLang["request delete"];
            else if($value['status'] == "Requesting connection")
               $status = $arrLang["Requesting connection"];
            else if($value['status'] == "request accepted")
               $status = $arrLang["request accepted"];

            if($value['his_status'] == "disconnected")
               $his_status = $arrLang["disconnected"];
            else if($value['his_status'] == "connected")
               $his_status = $arrLang["connected"];
            else if($value['his_status'] == "waiting response")
               $his_status = $arrLang["waiting response"];
            else if($value['his_status'] == "request reject")
               $his_status = $arrLang["request reject"];
            else if($value['his_status'] == "request delete")
               $his_status = $arrLang["request delete"];
            else if($value['his_status'] == "Requesting connection")
               $his_status = $arrLang["Requesting connection"];
            else if($value['his_status'] == "request accepted")
               $his_status = $arrLang["request accepted"];
            else if($value['his_status'] == "connection deleted")
               $his_status = $arrLang["connection deleted"];
            else if($value['his_status'] == "connection rejected")
               $his_status = $arrLang["connection rejected"];

            $arrTmp[0] = "<div class='resp' id=".$value['id']."><input type='checkbox' name='peer_{$value['id']}'  /></div>";
            $arrTmp[1] = $value['host']."<div id='cia".$value['id']."'></div>";
            
            if($value['status'] == "disconnected" || $value['status'] == "request accepted")
                $linkToConnect = "<a href='?menu=$module_name&action=connect&peerId=".$value['id']."'>{$arrLang['Connect']}</a>";
            else
                 $linkToConnect = "";
            if($value['status'] == "Requesting connection")
                 $arrTmp[2] = "<div id='status".$value['id']."'><a href='?menu=$module_name&action=view&peerId=".$value['id']."&opcion=2'><img src='images/loading.gif' height='20px' /></a></div>";
            else if($value['status'] == "request accepted" || $value['status'] == "connect")
                        $arrTmp[2] = "<div id='status".$value['id']."' style='color:green; '><img src='images/loading.gif' height='20px' /></div>";
            else if($value['status'] == "waiting response" || $value['status'] == "request reject" || $value['status'] == "request delete")
                       $arrTmp[2] = "<div id='status".$value['id']."' style='color:red; '><img src='images/loading.gif' height='20px' /></div>";
            else
                $arrTmp[2] = "<div  id='status".$value['id']."'><img src='images/loading.gif' height='20px' /></div>";
            if($value['status'] =="waiting response" || $value['status'] == "request reject" || $value['status'] == "request delete")
                $arrTmp[3] = "<div id='option".$value['id']."' ></div>";
            else
                $arrTmp[3] = "<div id='option".$value['id']."'><a href='?menu=$module_name&action=view&peerId=".$value['id']."&opcion=1'>{$arrLang['View']}</a>&nbsp;&nbsp;".$linkToConnect."</div>";
           /* if($value['status'] == "request accepted")
                $arrTmp[3] = "<div id='his_status".$value['id']."' style='color:red;'>".$his_status."</div>";
            else{  
              if ($status == "disconnected" && $his_status == "disconnected")
                  $arrTmp[3] = "<div id='his_status".$value['id']."' style='color:red;'>".$his_status."</div>";
              else{ 
                if($status == "disconnected" && $his_status == "waiting response")
                   $arrTmp[3] = "<div id='his_status".$value['id']."' style='color:red;'>".$his_status."</div>";
                else
                   $arrTmp[3] = "<div id='his_status".$value['id']."' style='color:red;'>".$his_status."</div>";
              }
            }*/
            $arrData[] = $arrTmp;$i++;
        }

    }


    $arrGrid = array("title"    => _tr("Remote Servers"),
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array(
            0 => array("name"      => "<input type='checkbox' class='checkall'>",
                                                    "property1" => ""),
            1 => array("name"      =>_tr("Remote Server"),
                                                    "property1" => ""),
            2 => array("name"      =>_tr("Connection Status"),
                                                    "property1" => ""),
            3 => array("name"      => _tr("Actions"),
                                                    "property1" => ""),
                                        )
                    );


    //begin section filter
    $oFilterForm = new paloForm($smarty, array());
    $smarty->assign("SHOW", $arrLang["Show"]);
     
    //if there is not general information or public and private keys
    $band = $_SESSION['exitsKey'];
    if($band == true){
        $oGrid->deleteList($msg=_tr("Are you sure you wish to delete Connection (s)?"), $task="delete_peer", $alt=_tr("Delete Selected"),$asLink=false);
        $oGrid->addNew($task="new_request", $alt=_tr("New Request"));
    }
    else{
        $smarty->assign("mb_title", _tr('MESSAGE').":");
	$smarty->assign("mb_message",_tr("You should register the <a href='?menu=general_information'>General Information</a>."));
    }    
    
    //end section filter

    //$oGrid->showFilter(trim($htmlFilter));

    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    //end grid parameters

   if(!createFile($smarty, $arrLang, $pDB, $dsn_agi_manager))
        $pPeersInformation->errMsg = $arrLang["Error to create file"];

    return $contenidoModulo;
}


function rejectPeerRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager)
{
    //$idPeer = $_POST['peerId']; //id del peer de quien voy a eliminar
    $idPeer  = getParameter('peerId');    
   
   //$ip_ask = $_POST['ipAsk'];  //ip de quien voy a eliminar
    $local_ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";//ip local
    $action = 3; 
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $dataPeer = $pPeersInformation->ObtainPeersDataById($idPeer);
    $ip_ask = $dataPeer['host'];
    $mac_ask = $dataPeer['mac'];
    $reject = socketReject($ip_ask, $local_ip, $action);
    if(preg_match( "/^BEGIN[[:space:]](.*)[[:space:]]END/", $reject, $regs ))
      {
          if($regs[1] == "reject"){
             $result = $pPeersInformation->deleteInformationPeer($idPeer);
            if(!$result){
	       $smarty->assign("mb_title", $arrLang["Message"]);
	       $smarty->assign("mb_message", $arrLang["Error trying to do a reject to:"]." ".$ip_ask);
	       return rejectPeerRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
	    }
          }
      }
    $content = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
    return $content;
}

function socketReject($ip_ask, $local_ip, $action)
{

    //$port_remote ="80";
    $port_remote ="443";
    $file_remote ="/elastixConnection/request.php";
    $respuesta = "";

    $conexion = fsockopen("ssl://".$ip_ask,$port_remote);
    if ($conexion) {
       //echo "Conexion realizada con éxito <br />";
       $dataSend="ip_answer=$local_ip&action=$action";
       $dataLenght = strlen($dataSend);
       $headerRequest = createHeaderHttp($ip_ask,$file_remote,$dataLenght);
       fputs($conexion,$headerRequest.$dataSend);
       while(($r = fread($conexion,2048)) != ""){
            $respuesta .= $r;
       }
       $respuesta = strstr($respuesta,"BEGIN");
       fclose ($conexion);
    }
  return $respuesta;

}

function deletePeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager)
{
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $local_ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";//ip local
    $action = 4;

    foreach($_POST as $key => $values){
        if(substr($key,0,5) == "peer_")
        {
            $tmpID = substr($key, 5);
            $dataPeer = $pPeersInformation->ObtainPeersDataById($tmpID);
            $ip_ask = $dataPeer['host'];
            $mac_ask = $dataPeer['mac'];
            $delete = socketReject($ip_ask, $local_ip, $action);
	    if(preg_match( "/^BEGIN[[:space:]](.*)[[:space:]]END/", $delete, $regs ))
	    {
		if($regs[1] == "reject")
		{
		    $result = $pPeersInformation->deleteInformationPeer($tmpID);
		    if(!$result){
			$smarty->assign("mb_title", $arrLang["Message"]);
			$smarty->assign("mb_message", $arrLang["Error trying to delete peer:"]." ".$ip_ask);
			return reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
		    }
		    $resultParameter = $pPeersInformation->deleteInformationParameter($tmpID);
		    if(!$resultParameter){
			$smarty->assign("mb_title", $arrLang["Message"]);
			$smarty->assign("mb_message", $arrLang["Error trying to delete parameter in database:"]." ".$ip_ask);
			return reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
		    }
		}
	    }
        }
    }
    $content = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
    if(!createFile($smarty, $arrLang, $pDB, $dsn_agi_manager))
        $pPeersInformation->errMsg = $arrLang["Error to create file"];

    return $content;
}

function socketRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $ip_remote, $ip_request, $company_request, $comment_request, $mac_request, $certificate, $key, $action, $secret)
{

    $arrFormConnectionRequest = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormConnectionRequest);
    //$port_remote ="80";
    $port_remote ="443";
    $file_remote ="/elastixConnection/request.php";
    $respuesta = "";
    $key_request = urlencode($key);
    $conexion = fsockopen("ssl://".$ip_remote,$port_remote);
    if ($conexion) {
       //echo "Conexion realizada con éxito <br />";
       $dataSend="ip_remote=$ip_remote&ip_request=$ip_request&company_request=$company_request&comment_request=$comment_request&mac_request=$mac_request&certificate_request=$certificate&key_request=$key_request&secret=$secret&action=$action";
       $dataLenght = strlen($dataSend);
       $headerRequest = createHeaderHttp($ip_remote,$file_remote,$dataLenght);
       //echo "$headerRequest $dataSend";
       fputs($conexion,$headerRequest.$dataSend);
       while(($r = fread($conexion,2048)) != ""){
            $respuesta .= $r;
       }
       //echo $var;
       $respuesta = strstr($respuesta,"BEGIN");
       fclose ($conexion);
    }
  return $respuesta;
}

function sendConnectionRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager)
{

    $pGeneralInformation = new paloSantoGeneralInformation($pDB);
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $arrFormPeersInformation = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormPeersInformation);
    $pNet = new paloNetwork();
    $result = "";
    $companyKeyPub = "";
    $mac = "";
    $macCertificate = "";
    $key_answer = "";
    $company = "";
    $secret = "";
    $root_certicate = "/var/lib/asterisk/keys";

    $exist_information = $pGeneralInformation->getGeneralInformation();
    if(is_array($exist_information) && count($exist_information)){
        $company = $exist_information[0]['organization'];
        $ip_whosent = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";
        $arrEths = $pNet->obtener_interfases_red_fisicas();
        foreach($arrEths as $idEth=>$arrEth)
        {
            if($arrEth['Inet Addr'] == $ip_whosent);
                $mac = $arrEths['eth0']['HWaddr'];
        }

        $macCertificate .="CER".str_replace(":","",$mac);
        if(file_exists("$root_certicate/$macCertificate.pub")){
            $lineas = file("$root_certicate/$macCertificate.pub");
            foreach ($lineas as $linea_num => $linea)
                    $key_answer.= htmlspecialchars("$linea"); 
        }

        $smarty->assign("Request", $arrLang["Request"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $smarty->assign("icon", "images/list.png");

        if(!$oForm->validateForm($_POST)){
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";

            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $contenidoModulo = formRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
            return $contenidoModulo;
        }else{
            $ip_remote = isset($_POST['ip'])?$_POST['ip']:"";//Del servidor a conectarse
            $secret = isset($_POST['secret'])?$_POST['secret']:"";
            $ip_request = $ip_whosent;//quien lo solicita
            $company_request = isset($company)?$company:"";
            $comment_request = isset($_POST['comment_request'])?$_POST['comment_request']:"";
            $mac_request = $mac;
            $action = 1;
            //Verifica si al host que deseamos conectarnos ya existe como peer en nuestra base
            $existHost =  $pPeersInformation->getHostStatus($ip_remote);
            if(!$existHost)
            {
                $result = socketRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $ip_remote, $ip_request, $company_request, $comment_request,$mac_request, $macCertificate, $key_answer, $action, $secret);

                if(preg_match( "/^BEGIN[[:space:]](.*)[[:space:]]END/", $result, $regs ))
                {
                    if($regs[1] == "request"){
                        $smarty->assign("mb_title", $arrLang["Message sent"]);
                        $smarty->assign("mb_message", $arrLang["Your connection request has been received"]);
                        $save = addPeerRequest($smarty, $pDB, $arrLang, $macCertificate);
                    }else if($regs[1] == "exist"){
                        $smarty->assign("mb_title", $arrLang["Alert"]);
                        $smarty->assign("mb_message", $arrLang["Currently there is a connection request"]);
                    }
                    else if($regs[1] == "nosecret"){
                        $smarty->assign("mb_title", $arrLang["Error"]);
                        $smarty->assign("mb_message", $arrLang["Secret incorrect"]);
                    }
                }else{
                    $smarty->assign("mb_title", $arrLang["Message"]);
                    $smarty->assign("mb_message", $arrLang["Unable to establish connection with the host, it does not exist or is trying to connect himself"]);
                }
            }else{
                $smarty->assign("mb_title", $arrLang["Message"]);
                $smarty->assign("mb_message", $arrLang["You had a request which was rejected earlier, your request has been sent again"]);
            }
        }
    }else{
         $smarty->assign("mb_title", $arrLang["Error"]);
         $smarty->assign("mb_message", $arrLang["You must fill your information General before making the request"]);
    }

    $contenidoModulo = reportPeersInformation($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager);
    return $contenidoModulo;
}

function formRequest($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $dsn_agi_manager)
{
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $arrFormPeersInformation = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormPeersInformation);

    $smarty->assign("Request", $arrLang["Request"]);
    $smarty->assign("Cancel", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/request.tpl",$arrLang["Connection Request"], $_POST);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function addPeerRequest($smarty, $pDB, $arrLang, $macCertificate)
{
    $pPeersInformation = new paloSantoPeersInformation($pDB);
    $ip_remote = isset($_POST['ip'])?$_POST['ip']:"";
    $mac_remote = "";
    $status = "waiting response";
    $result = $pPeersInformation->AddServerRequest($ip_remote, $mac_remote, $status, $macCertificate);
    if(!$result)
      return false;
    else
      return true;
}



function createFieldForm($arrLang)
{
    
    
    $arrFields = array(
                                        
             "mac"   => array(      "LABEL"                  => $arrLang["MAC"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => "([ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:"."[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2})"
                                            ),
            "model"   => array(      "LABEL"                  => $arrLang["Model"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "host"   => array(      "LABEL"                  => $arrLang["Host"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "ip",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "inkey"   => array(      "LABEL"                  => $arrLang["Inkey"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "outkey"   => array(      "LABEL"                  => $arrLang["Outkey"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "comment"  => array(      "LABEL"                  => $arrLang["Comment"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXTAREA",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "EDITABLE"               => "no",
                                            "COLS"                   => "30",
                                            "ROWS"                   => "4",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "company"   => array(      "LABEL"                  => $arrLang["Company"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "ip",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "ip"   => array(      "LABEL"                  => $arrLang["Host Remote"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "ip",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "comment_request"   => array(      "LABEL"                  => $arrLang["Comment"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXTAREA",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "EDITABLE"               => "si",
                                            "COLS"                   => "50",
                                            "ROWS"                   => "4",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "secret"   => array(      "LABEL"                  => $arrLang["Secret"],
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
    else if(getParameter("new_request"))
        return "new_request";
    else if(getParameter("request"))
        return "request";
    else if(getParameter("delete_peer"))
        return "delete_peer";
    else if(getParameter("disconnect")){
        return "disconnect";}
    else if(getParameter("accept_request"))
        return "accept_request";
    else if(getParameter("reject_request"))
        return "reject_request";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else if(getParameter("action")=="view")
        return "view";
    else if(getParameter("action")=="connect")
        return "connect";
    else if(getParameter("action")=="status")
        return "status";
    else if(getParameter("action")=="disconnected")
        return "disconnected";
    else if(getParameter("action")=="request_accept")
        return "accept_request";
    else if(getParameter("action")=="request_reject")
        return "reject_request";
    else
        return "report";
}
?>
