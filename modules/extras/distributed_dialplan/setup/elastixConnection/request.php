<?php
$documentRoot = "/var/www/html";
include_once ("$documentRoot/libs/misc.lib.php");
include_once ("$documentRoot/libs/paloSantoNetwork.class.php");
include_once ("$documentRoot/configs/default.conf.php");
include_once "$documentRoot/modules/peers_information/libs/paloSantoPeersInformation.class.php";
//include_once "$documentRoot/modules/connection_request/libs/paloSantoConnectionRequest.class.php";

require_once("$documentRoot/libs/smarty/libs/Smarty.class.php");

session_name("elastixSession");
session_start();
/*load_language($documentRoot);*/

$pDB = "sqlite3:////var/www/db/elastixconnection.db";

$ip_request      = isset($_POST['ip_request'])?$_POST['ip_request']:"";
$company_request = isset($_POST['company_request'])?$_POST['company_request']:"";
$comment_request = isset($_POST['comment_request'])?$_POST['comment_request']:"";
$mac_request = isset($_POST['mac_request'])?$_POST['mac_request']:"";
$key_request = isset($_POST['key_request'])?$_POST['key_request']:"";
$certificate_request = isset($_POST['certificate_request'])?$_POST['certificate_request']:"";
$action = isset($_POST['action'])?$_POST['action']:0;
$secret = isset($_POST['secret'])?$_POST['secret']:"";

$ip_ask = isset($_POST['ip_ask'])?$_POST['ip_ask']:"";
$ip_answer = isset($_POST['ip_answer'])?$_POST['ip_answer']:"";
$mac_answer = isset($_POST['mac_answer'])?$_POST['mac_answer']:"";
$key_answer = isset($_POST['key_answer'])?$_POST['key_answer']:"";
$company_answer = isset($_POST['company_answer'])?$_POST['company_answer']:"";
$comment_answer = isset($_POST['comment_answer'])?$_POST['comment_answer']:"";

$pRequest = new paloSantoPeersInformation($pDB);

if($ip_request == "" && $company_request == "" && $comment_request == "" && $secret == "" && $action == 0)//no hay solicitudes
{
   echo "BEGIN norequest END";//(server) localmente cuando ingresa al modulo peerInforamtion consulta esto,
                              //al no encontrar requerimientos regresara el comando "norequest"
}else if($action == 1){// se recibe la peticion la cual se deber verificar la clave si es valida
     $pNetwork = new paloNetwork();
     $localmac = "";
     $localhost = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"";
     $localSecret = $pRequest->getSecret();
     if($localSecret == $secret){
        $arrEths = $pNetwork->obtener_interfases_red_fisicas();
        foreach($arrEths as $idEth=>$arrEth)
        {
            if($arrEth['Inet Addr'] == $localhost)
                $localmac = $arrEths['eth0']['HWaddr'];
        }
        if(!$pRequest->hostExist($mac_request))
        {
            $result = $pRequest->addInfoRequest($mac_request, $ip_request, $company_request, $comment_request,$certificate_request,$key_request);
            echo "BEGIN request END";
        }else
            echo "BEGIN exist END";
     }else
        echo "BEGIN nosecret END";
}else if($action == 2){
     $id = $pRequest->getIdPeerbyRemoteHost($ip_answer);
     if(isset($id['id']) && $id['id'] != ""){
        $result = $pRequest->UpdateInfoRequest($ip_answer, $mac_answer, $key_answer, $company_answer, $comment_answer);
        $keyPubServer =  $pRequest->createKeyPubServer($key_answer, $mac_answer);
        $parameter = $pRequest->createPeerParameter();
        $resultParameter = $pRequest->addInformationParameter($parameter,$id['id']);
        echo "BEGIN accept END";
     }
     else
        echo "BEGIN norequest END";
}else if($action == 3 || $action == 4){
     $id = $pRequest->getIdPeerbyRemoteHost($ip_answer);
     if(isset($id['id']) && $id['id'] != ""){
        $result = $pRequest->UpdateInfoForReject($ip_answer, $action);
     }
     echo "BEGIN reject END";
}else if($action == 5 || $action == 6){
     $id = $pRequest->getIdPeerbyRemoteHost($ip_answer);
     if(isset($id['id']) && $id['id'] != ""){
        $result = $pRequest->hisStatusConnect($ip_answer, $action);
        echo "BEGIN connected END";
     }else
        echo "BEGIN norequest END";
}
?>
