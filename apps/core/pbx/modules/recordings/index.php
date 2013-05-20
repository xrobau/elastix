<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                                |
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
  $Id: default.conf.php,v 1.1 2008-06-12 09:06:35 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoPBX.class.php";
    
    //include elastix framework
    include_once "libs/paloSantoJSON.class.php";
    include_once("libs/paloSantoDB.class.php");
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoOrganization.class.php";
    include_once("libs/paloSantoACL.class.php");
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoRecordings.class.php";
    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $lang_file="modules/$module_name/lang/$lang.lang";
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    load_language_module($module_name);

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

    $pConfig = new paloConfig("/var/www/elastixdir/asteriskconf", "/elastix_pbx.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);
    
    $dsn_agi_manager['password'] = $arrConfig['MGPASSWORD']['valor'];
    $dsn_agi_manager['user'] = $arrConfig['MGUSER']['valor'];
    $dsn_agi_manager['host']=$arrConfig['DBHOST']['valor'];

    $pDBACL = new paloDB($arrConf['elastix_dsn']['acl']);
    
    $accion = getAction();
    
    //Comprobación de la credencial del usuario
    $arrCredentiasls=getUserCredentials();
    $userLevel1=$arrCredentiasls["userlevel"];
    $userAccount=$arrCredentiasls["userAccount"];
    $idOrganization=$arrCredentiasls["id_organization"];
  
    $pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
  
    $content = "";
    switch($accion){
    case "add":
        $content = form_Recordings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        break;
    case "record":
        $content = record($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
        break;
    case "hangup":
        $content = hangup($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
        break;
    case "save":
        $content = save_recording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        break;
    case "remove":
        $content = remove_recording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        break;
    case "check_call_status":
        $content = checkCallStatus("call_status", $smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
        break;
    case "checkName":
        $content = check_name($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
        break;
    case "download":
        $content = downloadFile($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
        break;
    default:
        $content = reportRecording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        break;
    }

    return $content;
}

function reportRecording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
        
    if ($userLevel1=="superadmin") {
        $domain = getParameter("organization");
        if (!empty($domain)) {
            $url = "?menu=$module_name&organization=$domain";
            if($domain=="src_custom")
                $domain_f = "all";
            else
                $domain_f = $domain;
          
            $pRecording = new paloSantoRecordings($pDB,$domain_f);
            if($domain_f!="all")
                $total=$pRecording->getNumRecording($domain);
            else
                $total=$pRecording->getNumRecording(); 
        } else {
            $domain="all";
            $url="?menu=$module_name";
            $pRecording = new paloSantoRecordings($pDB,$domain);
            $total=$pRecording->getNumRecording();
        }
    } else {
        $arrOrg=$pORGZ->getOrganizationById($idOrganization);
        $domain=$arrOrg["domain"];
        $url = "?menu=$module_name";
        $pRecording = new paloSantoRecordings($pDB,$domain);
        $total=$pRecording->getNumRecording($domain);
    }
        
    if ($total===false) {
        $error=$pRecording->errMsg;
        $total=0;
    }
    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
  
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    if(($userLevel1=="admin")||($userLevel1=="superadmin"))
        $check = "&nbsp;<input type='checkbox' name='checkall' class='checkall' id='checkall' onclick='jqCheckAll(this.id);' />";
    else
        $check = "";

    $arrGrid = array("title"    => _tr('Recordings List'),
            "url"      => $url,
            "width"    => "99%",
            "start"    => ($total==0) ? 0 : $offset + 1,
            "end"      => $end,
            "total"    => $total,
            'columns'   =>  array(
                array("name"      => $check ),
                array("name"      => _tr("Name"),),
                array("name"      => _tr("Source"),),
                array("name"      => _tr(""),),
                ),
            );

    $arrRecordings=array();
    $arrData = array();
    if($userLevel1=="admin"){
        $arrRecordings = $pRecording->getRecordings($domain,$limit,$offset);
    }elseif(($userLevel1=="superadmin")&&($domain=="all"))
        $arrRecordings=$pRecording->getRecordings(null,$limit,$offset);
    else
        $arrRecordings=$pRecording->getRecordings($domain);  
                   
    if($arrRecordings===false){
            $error=_tr("Error to obtain Recordings").$pRecording->errMsg;
    $arrRecordings=array();
    }
    $i=0;
       
    foreach($arrRecordings as $recording) {
        $ext = explode(".",$recording["name"]);
        if($userLevel1=="superadmin"){
            if($recording["source"]=="custom"){
                $arrTmp[0] = "&nbsp;<input type ='checkbox' class='delete' name='record_delete[]' value='".$recording['source'].",".$recording['name']."' />";
                $arrTmp[2] = $recording["source"];
            }else{
                $arrTmp[0] = "";
                $arrTmp[2] = $recording["organization_domain"];
            }
            $idfile = $recording['uniqueid'];
            if($ext[1]=="gsm"){
                $arrTmp[1] = "<span>".$recording['name']."</span>";
                $arrTmp[3] = "";
            }else{
                $arrTmp[1] = "<div class='single' id='$i'><span data-src='index.php?menu=$module_name&action=download&id=$idfile&rawmode=yes'><img style='cursor:pointer;' width='13px' src='/modules/recordings/images/sound.png'/>&nbsp;&nbsp;".$recording['name']."</span>";
                $arrTmp[3] = "<audio></audio>";
                $i++;
            }
        }elseif($userLevel1=="admin"){
            $arrTmp[0] = "&nbsp;<input type ='checkbox' class='delete' name='record_delete[]' value='".$recording['source'].",".$recording['name']."' />";
            $idfile = $recording['uniqueid'];
            $namefile = $recording['name'];
            if($ext[1]=="gsm"){
                $arrTmp[1] = "<span>".$recording['name']."</span>";
                $arrTmp[3] = "";
            }else{
                $arrTmp[1] = "<div class='single' id='$i'><span data-src='index.php?menu=$module_name&action=download&id=$idfile&rawmode=yes'><img style='cursor:pointer;' width='13px' src='/modules/recordings/images/sound.png'/>&nbsp;&nbsp;".$recording['name']."</span>";
                $arrTmp[3] = "<audio></audio>";
                $i++;
            }
            $arrTmp[2] = $recording["source"];
        }
        $arrData[] = $arrTmp;
    }
    
    if(($userLevel1 == "admin")||($userLevel1 == "superadmin")){
        $oGrid->addNew("add_recording",_tr("Add Recording"));
        $oGrid->deleteList(_tr("Are you sure you want to delete?"), "remove", _tr("Delete Selected"),false);
    }

    if($error!=""){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",$error);
    }

    if($userLevel1 == "superadmin"){
        $arrOrgz=array("all"=>"all","src_custom"=>_tr("custom"));
        foreach(($pORGZ->getOrganization()) as $value){
            if($value["id"]!=1)
                $arrOrgz[$value["domain"]]=$value["name"];
        }
        $arrFormElements = createFieldFilter($arrOrgz);
        $oFilterForm = new paloForm($smarty, $arrFormElements);
        $_POST["organization"]=$domain;
        $oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization")." = ".$arrOrgz[$domain], $_POST, array("organization" => "all"),true);
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
        $oGrid->showFilter(trim($htmlFilter));
    }

    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
    return $contenidoModulo;
}



function downloadFile($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{     
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    if($userLevel1=="superadmin"){
        $domain = "all";
    } elseif($userLevel1=="admin") {
        $arrOrg=$pORGZ->getOrganizationById($idOrganization);
        $domain=$arrOrg["domain"];
    }
    $fullPath=NULL;
    // $domain=$arrOrg["domain"];
    $id = getParameter("id");
    $pRecording = new paloSantoRecordings($pDB,$domain);
    $record = $pRecording->getRecordingById($id,$userLevel1);
    if ($record) {
        $fullPath = $record['filename'];
        $name = $record['name'];
    }
    // Must be fresh start 
    if(headers_sent()) 
        die('Headers Sent'); 

    // File Exists? 
    if(file_exists($fullPath)){ 
            
        // Parse Info / Get Extension 
        $fsize = filesize($fullPath); 

        $path_parts = pathinfo($fullPath); 
        $ext = strtolower($path_parts["extension"]); 
        
        // Determine Content Type 
        switch ($ext) { 
        case "wav": $ctype="audio/x-wav"; break;
        case "Wav": $ctype="audio/x-wav"; break;
        case "WAV": $ctype="audio/x-wav"; break;
        case "gsm": $ctype="audio/x-gsm"; break;
        case "GSM": $ctype="audio/x-gsm"; break;
        default: $ctype="application/force-download"; 
        } 
   
        header("Pragma: public"); // required 
        header("Expires: 0"); 
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
        header("Cache-Control: private",false); //required for certain browsers 
        header("Content-Type: $ctype"); 
       
        header("Content-Disposition: attachment; filename=\"".basename($fullPath)."\";" ); 
        header("Content-Transfer-Encoding: binary"); 
        header("Content-Length: ".$fsize); 
            
        if ($fileh = fopen($fullPath, 'rb')) {
            while(!feof($fileh) and (connection_status()==0)) {
                print(fread($fileh, 1024*12));//10kb de buffer stream
                flush();
            }
            
            fclose($fileh);
        }else die('File Not Found'); 
            
        return((connection_status()==0) and !connection_aborted());

    } else die('File Not Found'); 
          
 } 

function check_name($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    
    $arrOrg=$pORGZ->getOrganizationById($idOrganization);
    $domain=$arrOrg["domain"];
    
    $name = getParameter("recording_name");
    $pRecording = new paloSantoRecordings($pDB,$domain);
    if ($name!="") {
        $filename = "/var/lib/asterisk/sounds/".$domain."/system/".$name.".wav";
        $status = $pRecording->checkFilename($filename);
        if($userLevel1=="admin"){
            $recId = $pRecording->getId($name.".wav","system");
            $id  = $recId["uniqueid"];
        }
    } else
       $status = "empty";

    $jsonObject = new PaloSantoJSON();
    $msgResponse["name"] = $status;
    $msgResponse["id"] = $id;
    $jsonObject->set_status("OK");
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}

function record($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{
    session_commit();
    $status  = TRUE;
    $status = new_recording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
    $jsonObject = new PaloSantoJSON();
    $msgResponse["record"] = $status;
    $jsonObject->set_status("OK");
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}


function hangup($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{
   
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
  
    $arrOrg=$pORGZ->getOrganizationById($idOrganization);
    $domain=$arrOrg["domain"];
  
    $extension = getParameter("extension");
    $pRecording = new paloSantoRecordings($pDB,$domain);
    $result = $pRecording->Obtain_Protocol_Current_User($arrConf,$domain,$extension);
      
    if($result != FALSE)
       $result = $pRecording->hangupPhone($dsn_agi_manager, $result['device'], $result['dial'], $result['exten']);
   
    $msgResponse["record"] = $result;
    //$msgResponse["id"] = $id;
    
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status("OK");
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}


function remove_recording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $error = "";
    $success = false;
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
         
    if(($userLevel1!="admin")&&($userLevel1!="superadmin")) {
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportRecording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    } else {
        if ($userLevel1=="superadmin") {
            $domain = "all";
        } elseif($userLevel1=="admin") {
            $arrOrg=$pORGZ->getOrganizationById($idOrganization);
            $domain=$arrOrg["domain"];
        }

        $record=getParameter("record_delete");
        
        if (isset($record)&& count($record)>0) {
            $pRecording = new paloSantoRecordings($pDB,$domain);
            
            $pDB->beginTransaction();
            $success = $pRecording->deleteRecordings($record,$domain,$userLevel1);
            if($success)
                $pDB->commit();
            else
                $pDB->rollBack();
            $error .=$pRecording->errMsg;
            if($success){
                $smarty->assign("mb_title", _tr("MESSAGE"));
                $smarty->assign("mb_message",_tr("The Recordings were deleted successfully"));
            } else {
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message",$error);
            }
        } else {
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("You must select at least one record"));
        }
    }
    return reportRecording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function save_recording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $success= false;
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
  
    $arrOrg=$pORGZ->getOrganizationById($idOrganization);
    $domain=$arrOrg["domain"];
  
    $bExito = true;
    $pRecording = new paloSantoRecordings($pDB,$domain);
    $extension = $pRecording->Obtain_Extension_Current_User($arrConf);
    $error ="";
    //if(!$extension)
      // return form_Recordings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    
    if($userLevel1 == "admin") {
        $destiny_path = "/var/lib/asterisk/sounds/$domain/system";
        $source = "system";
    } elseif($userLevel1 == "superadmin"){
        $destiny_path = "/var/lib/asterisk/sounds/custom";
        $source = "custom";
    }

    if (isset($_FILES)) {
       
        if($_FILES['file_record']['name']!="") {
            $smarty->assign("file_record_name", $_FILES['file_record']['name']);
            if(!file_exists($destiny_path))
            {
                $bExito = mkdir($destiny_path, 0755, TRUE);
            }
          
            if((!preg_match("/^(\w|-|\.|\(|\)|\s)+\.(wav|WAV|Wav|gsm|GSM|Gsm|Wav49|wav49|WAV49|mp3|MP3|Mp3)$/",$_FILES['file_record']['name']))||(preg_match("/(\.php)/",$_FILES['file_record']['name']))){
             
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message",_tr("Invalid extension file ")." ".$_FILES["file_record"]["name"]);
                $bExito = false;
                return form_Recordings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
               
            }
            if($bExito)
            {
                $filenameTmp = $_FILES['file_record']['name'];
                $tmp_name = $_FILES['file_record']['tmp_name'];
                $filename = basename("$destiny_path/$filenameTmp");
                $info=pathinfo($filename);
                $file_sin_ext=$info["filename"];
                if (strlen($filenameTmp)>50) {
                    $smarty->assign("mb_title", _tr("ERROR"));
                    $smarty->assign("mb_message",_tr("Filename's length must be max 50 characters").": $filenameTmp");
                    $bExito = false;
                } elseif(($pRecording->checkFilename($destiny_path."/".$filenameTmp)!=true)||($pRecording->checkFilename($destiny_path."/".$file_sin_ext.".wav")!=true)) {
                    //Verificar que no existe otro archivo con el mismo nombre en la misma carpeta
                    $smarty->assign("mb_title", _tr("ERROR"));
                    $smarty->assign("mb_message",_tr("Already exists a file with same filename").": $filenameTmp");
                    $bExito = false;
                    return form_Recordings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
                } else {
                    // $filename = basename("$destiny_path/$filenameTmp");
                    $date=date("YMd_His");
                    $tmpFile=$date."_".$filename;
                    if (move_uploaded_file($tmp_name, "$destiny_path/$tmpFile")) {
                        $info=pathinfo($filename);
                        $file_sin_ext=$info["filename"];
                        $type=$pRecording->getTipeOfFile("$destiny_path/$tmpFile");
                        $continue=true;
                        
                        if($type==false){
                            $error .=$pRecording->errMsg;
                            $continue=false;
                        }

                        if($type=="audio/mpeg; charset=binary") {
                            if($pRecording->convertMP3toWAV($destiny_path,$tmpFile,$file_sin_ext,$date)==false){
                                $error .=$pRecording->errMsg;
                                $continue=false;
                                $bExito = false;
                            }else{
                                $filename=$file_sin_ext.".wav";
                                        
                            }
                        }
                        if($continue){
                            if($pRecording->resampleMoHFiles($destiny_path,$tmpFile,$filename)==false){
                                $error .=$pRecording->errMsg;
                                $bExito = false;
                            }
                        }
 
                    } else {
                        $smarty->assign("mb_title",_tr("ERROR").":");
                        $smarty->assign("mb_message", _tr("Possible file upload attack")." $filename");
                        $bExito = false;
                    }
                }
            } else {
                $smarty->assign("mb_title", _tr("ERROR").":");
                $smarty->assign("mb_message", _tr("Destiny directory couldn't be created"));
                $bExito = false;
            }
        }
        else{
            $smarty->assign("mb_title", _tr("ERROR").":");
            $smarty->assign("mb_message", _tr("Error copying the file"));
            $bExito = false;
        }
    }else{
        $smarty->assign("mb_title",  _tr("ERROR").":");
        $smarty->assign("mb_message", _tr("Error copying the file"));
        $bExito = false;
    }
 
    if($bExito) {
        $pRecording = new paloSantoRecordings($pDB,$domain);
        $name = "$destiny_path/$filename";
        $pDB->beginTransaction();
        $success=$pRecording->createNewRecording($filename,$name,$source,$domain);

        if($success)
            $pDB->commit();
        else
            $pDB->rollBack();
        $error .=$pRecording->errMsg;

        if($success==false){
            $smarty->assign("mb_title", _tr("ERROR").":");
            $smarty->assign("mb_message",  $error);   
        } else {
            $smarty->assign("mb_title", _tr("MESSAGE"));
            $smarty->assign("mb_message",_tr("Record Saved Successfully"));
        }
    } else {
        $smarty->assign("mb_title", _tr("ERROR").":");
        $smarty->assign("mb_message", _tr("ERROR Uploading File"));   
        return form_Recordings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    return reportRecording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function new_recording($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
   
    $arrOrg=$pORGZ->getOrganizationById($idOrganization);
    $domain=$arrOrg["domain"];
   
    $recording_name = getParameter("recording_name");
    if (basename($recording_name) != $recording_name) $recording_name = '';
    if (strpbrk($recording_name, "\r\n") !== FALSE) $recording_name = '';
  
    if($recording_name != '') {
        $extension = getParameter("extension");
        $pRecording = new paloSantoRecordings($pDB,$domain);
        $filename = "/var/lib/asterisk/sounds/".$domain."/system/".$recording_name.".wav";
        $checkRecordingName = $pRecording->checkFileName($filename);
        
        $result = $pRecording->Obtain_Protocol_Current_User($arrConf,$domain,$extension);
     
        if($result != FALSE) {
            $result = $pRecording->Call2Phone($dsn_agi_manager, $result['device'], $result['dial'], $result['exten'],$recording_name);
            if($result) {
                $result["filename"] = $recording_name;
                $result["msg"] = _tr("Recording...") ;
                $result["status"] = "ok";

                if ($checkRecordingName==TRUE) {
                    $pDB->beginTransaction();
                    $name = $recording_name.".wav";
                    $success=$pRecording->createNewRecording($name,$filename,"system",$domain);
                                      
                    if ($success) {
                        $pDB->commit();
                        $name =$recording_name.".wav";
                        $recId = $pRecording->getId($name,"system");
                        $id=$recId["uniqueid"];
                        $result["id"] = $id;
                    } else {
                        $pDB->rollBack();
                        $error =$pRecording->errMsg;
                        $result["msg"]=_tr("The record couldn't be saved ") ;
                        $result["status"] = "error";
                    }
                }
            } else {
               $result["msg"]=_tr("The record couldn't be realized ") ;
               $result["status"] = "error";
            }
        } else {
            $result["msg"]=_tr("The record couldn't be realized ") ;
            $result["status"] = "error";
        }
    } else {
        $result["msg"]=_tr("Insert the Recording Name.") ;
        $result["status"] = "error";
    }

    return $result;
}

function checkCallStatus($function, $smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{
    $executed_time = 2; //en segundos
    $max_time_wait = 30; //en segundos
    $event_flag    = false;
    $data          = null;

    $i = 1;
    while(($i*$executed_time) <= $max_time_wait){
        $return = $function($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
        $data   = $return['data'];
        if($return['there_was_change']){
            $event_flag = true;
            break;
        }
        $i++;
        sleep($executed_time); //cada $executed_time estoy revisando si hay algo nuevo....
    }
    $return = $function($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization);
    $data   = $return['data'];
    return $data;
}

function call_status($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $userLevel1, $userAccount, $idOrganization)
{
    session_commit();
    $status = TRUE;
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
   
    $arrOrg=$pORGZ->getOrganizationById($idOrganization);
    $domain=$arrOrg["domain"];
   
    $extension = getParameter("extension");
    $pRecording = new paloSantoRecordings($pDB,$domain);
    $result = $pRecording->Obtain_Protocol_Current_User($arrConf,$domain,$extension);
        
    $state = $pRecording->callStatus($result['dial']);
    $jsonObject = new PaloSantoJSON();
    if($state=="hangup")
        $status = FALSE;
   
    if($status){
        $msgResponse["status"] = $state;
        $jsonObject->set_status("RECORDING");
        $jsonObject->set_message($msgResponse);
        return array("there_was_change" => false,
                 "data" => $jsonObject->createJSON());
    }else{
        $jsonObject->set_status("HANGUP");
        return array("there_was_change" => true,
                 "data" => $jsonObject->createJSON());
    }
   
}

function form_Recordings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
         
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    $arrOrg=$pORGZ->getOrganizationById($idOrganization);
    $domain=$arrOrg["domain"];

    $pRecording = new paloSantoRecordings($pDB,$domain);
    $extension = $pRecording->Obtain_Extension_Current_User($arrConf);
   
    if(isset($_POST['option_record']) && $_POST['option_record']=='by_file')
        $smarty->assign("check_file", "checked");
    else
        $smarty->assign("check_record", "checked");
    
    $oForm = new paloForm($smarty,array());

    $smarty->assign("recording_name_Label", _tr("Record Name"));
    $smarty->assign("overwrite_record", _tr("This record Name already exists. Do you want to Overwrite it?"));
    $smarty->assign("record_Label",_tr("File Upload"));
    $smarty->assign("record_on_extension", _tr("Record On Extension"));
    $smarty->assign("Record", _tr("Record"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("INFO", _tr("Press REC and start your recording. Once you have finished recording you must press ·STOP or hangup the phone").".");
    $smarty->assign("NAME", _tr("You do not need to add an extension to the record name").".");
    $smarty->assign("icon", "/modules/$module_name/images/recording.png");
    $smarty->assign("module_name", $module_name);
    $smarty->assign("file_upload", _tr("File Upload"));
    $smarty->assign("record", _tr("Record"));
    $smarty->assign("ext", _tr("Extension"));
    $smarty->assign("system", _tr("System"));
    $smarty->assign("exten", _tr("Extension"));
    $smarty->assign("EXTENSION",$extension);
    $smarty->assign("checking", _tr("Checking Name..."));
    $smarty->assign("dialing", _tr("Dialing..."));
    $smarty->assign("domain", $domain);
    $smarty->assign("confirm_dialog", _tr("This Record Name already exists."));
    $smarty->assign("success_record", _tr("Record was saved succesfully."));
    $smarty->assign("cancel_record", _tr("Record was canceled."));
    $smarty->assign("hangup", _tr("Hang up."));
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($max_upload, $max_post, $memory_limit)*1048576;
    $smarty->assign("max_size", $upload_mb);
    $smarty->assign("alert_max_size", _tr("File size exceeds the limit. "));
    $smarty->assign("user_level", $userLevel1);
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr("Recordings"), $_POST);

    $contenidoModulo = "<form enctype='multipart/form-data' method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldFilter($arrOrgz)
{
    $arrFields = array(
                "organization"  => array("LABEL"               => _tr("Organization"),
                                      "REQUIRED"               => "no",
                                      "INPUT_TYPE"             => "SELECT",
                                      "INPUT_EXTRA_PARAM"      => $arrOrgz,
                                      "VALIDATION_TYPE"        => "domain",
                                      "VALIDATION_EXTRA_PARAM" => "",
                                      "ONCHANGE"               => "javascript:submit();"),
                );
    return $arrFields;
}


function getAction()
{
    if(getParameter("record"))
        return "record";
    else if(getParameter("save"))
        return "save";
    else if(getParameter("remove"))
        return "remove";
    else if(getParameter("add_recording"))
        return "add";
    elseif(getParameter("action")=="record")
        return "record";
    elseif(getParameter("action")=="hangup")
        return "hangup";
    elseif(getParameter("action")=="check_call_status")
        return "check_call_status";
    elseif(getParameter("action")=="checkName")
        return "checkName";
    elseif(getParameter("action")=="download")
        return "download";
    elseif(getParameter("action")=="remove")
        return "remove";
    else
        return "report";
}
?>
