<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  $Id: index.php,v 1.1 2008/01/30 15:55:57 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoExtensionsBatch.class.php";
    
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
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP  = $pConfig->leer_configuracion(false);

    $dsnAsterisk = $arrAMP['AMPDBENGINE']['valor']."://".
                   $arrAMP['AMPDBUSER']['valor']. ":".
                   $arrAMP['AMPDBPASS']['valor']. "@".
                   $arrAMP['AMPDBHOST']['valor']. "/asterisk";

    $pDB = new paloDB($dsnAsterisk);
    if(!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", _tr('Error when connecting to database')."<br/>".$pDB->errMsg);
    }

    $pConfig = new paloConfig($arrAMP['ASTETCDIR']['valor'], "asterisk.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAST  = $pConfig->leer_configuracion(false);

    $content = "";
    $accion = getAction();

    //Sirve para todos los casos
    $smarty->assign("MODULE_NAME", $module_name);
    $smarty->assign("icon","modules/$module_name/images/pbx_batch_of_extensions.png");
    $smarty->assign("SAVE", _tr('Save'));
    $smarty->assign("DOWNLOAD", _tr("Download Extensions"));
    $smarty->assign("label_file", _tr("File"));
    $smarty->assign("title", _tr("Extensions Batch"));
    $smarty->assign("title_module", _tr("Batch of Extensions"));
    $smarty->assign("HeaderFile", _tr("Header File Extensions Batch"));
    $smarty->assign("AboutUpdate", _tr("About Update Extensions Batch"));
    $html_input = "<input class='button' type='submit' name='delete_all' value='"._tr('Delete All Extensions')."' onClick=\" return confirmSubmit('"._tr("Are you really sure you want to delete all the extensions in this server?")."');\" />";
    $smarty->assign("DELETE_ALL", $html_input);

    switch($accion)
    {
        case 'delete_all':
            delete_all_extention($smarty, $module_name, $local_templates_dir, $arrConf, $pDB, $arrAST, $arrAMP);
            $content = report_extension($smarty, $module_name, $local_templates_dir, $arrConf);
            break;
        case 'load_extension':
            $content = load_extension($smarty, $module_name, $local_templates_dir, $arrConf, $base_dir, $pDB, $arrAST, $arrAMP);
            break;
        case 'download_csv':
            download_extensions($pDB);
            break;
        default:
            $content = report_extension($smarty, $module_name, $local_templates_dir, $arrConf);
            break;
    }

    return $content;
}

function delete_all_extention(&$smarty, $module_name, $local_templates_dir, $arrConf, $pDB, $arrAST, $arrAMP)
{
    $message = "";
    $oPalo = new paloSantoLoadExtension($pDB);
    $arrSipExtension = array();

    $data_connection = array('host' => "127.0.0.1", 'user' => "admin", 'password' => obtenerClaveAMIAdmin());
    $arrData = $oPalo->getExtensions();
    foreach($arrData as $key => $value)
      $arrExtension[] = $value;
    if($oPalo->deleteTree($data_connection, $arrAST, $arrAMP, $arrExtension)){
        if($oPalo->deleteAllExtension())
        {
            if($oPalo->do_reloadAll($data_connection, $arrAST, $arrAMP))
            {
                $smarty->assign("mb_title", _tr('Message'));
                $smarty->assign("mb_message", _tr('All extensions deletes'));
            }else{
                $smarty->assign("mb_title", _tr('Error'));
                $smarty->assign("mb_message", _tr('Unable to reload the changes'));
            }
        }else{
            $smarty->assign("mb_title", _tr('Error'));
            $smarty->assign("mb_message", _tr('Could not delete the database'));
        }
    }else{
        $smarty->assign("mb_title", _tr('Message'));
        $smarty->assign("mb_message", _tr('Could not delete the ASTERISK database'));
    }

}

function report_extension($smarty, $module_name, $local_templates_dir, $arrConf){

    $oForm = new paloForm($smarty, array());
    $html = $oForm->fetchForm("$local_templates_dir/extension.tpl", _tr('Extensions Batch'), $_POST);

    $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$html."</form>";
    return $contenidoModulo;
}

function load_extension($smarty, $module_name, $local_templates_dir, $arrConf, $base_dir, $pDB, $arrAST, $arrAMP)
{
    $oForm = new paloForm($smarty, array());
    //$html = $oForm->fetchForm("$local_templates_dir/extension.tpl", _tr("Extensions Batch"), $_POST);

    $arrTmp=array();
    $bMostrarError = false;

    //valido el tipo de archivo
    if (!preg_match('/.csv$/', $_FILES['userfile']['name'])) {
        $smarty->assign("mb_title", _tr('Validation Error'));
        $smarty->assign("mb_message", _tr('Invalid file extension.- It must be csv'));
    }else {
        if(is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            $ruta_archivo = "/tmp/".$_FILES['userfile']['name'];
            copy($_FILES['userfile']['tmp_name'], $ruta_archivo);
            //Funcion para cargar las extensiones
            load_extension_from_csv($smarty, $ruta_archivo, $base_dir, $pDB, $arrAST, $arrAMP);
        }else {
            $smarty->assign("mb_title", _tr('Error'));
            $smarty->assign("mb_message", _tr('Possible file upload attack. Filename') ." :". $_FILES['userfile']['name']);
        }
    }
    $content = report_extension($smarty, $module_name, $local_templates_dir, $arrConf);
    return $content;
}

function load_extension_from_csv($smarty, $ruta_archivo, $base_dir, $pDB, $arrAST, $arrAMP){
    $Messages = "";
    $arrayColumnas = array();
    $data_connection = array('host' => "127.0.0.1", 'user' => "admin", 'password' => obtenerClaveAMIAdmin());

    $result = isValidCSV($pDB, $ruta_archivo, $arrayColumnas);
    if($result != "valided"){
        $smarty->assign("mb_message", $result);
        return;
    }

    $hArchivo = fopen($ruta_archivo, 'r+');
    $cont = 0;
    $pLoadExtension = new paloSantoLoadExtension($pDB);

    if($hArchivo) {
        //Linea 1 header ignorada
        $tupla = fgetcsv($hArchivo, 4096, ",");
        $prueba = count ($tupla);
       
        //Desde linea 2 son datos
        while ($tupla = fgetcsv($hArchivo, 4096, ",")) {
            if(is_array($tupla) && count($tupla)>=3)
            {
                
                $Name               = $tupla[$arrayColumnas[0]];
                $Ext                = $tupla[$arrayColumnas[1]];
                $Direct_DID         = isset($arrayColumnas[2]) ?$tupla[$arrayColumnas[2]]:"";
                $Outbound_CID       = isset($arrayColumnas[3]) ?$tupla[$arrayColumnas[3]]:"";
                $Call_Waiting       = isset($arrayColumnas[4]) ?$tupla[$arrayColumnas[4]]:"";
                $Secret             = $tupla[$arrayColumnas[5]];
                $VoiceMail          = isset($arrayColumnas[6]) ?$tupla[$arrayColumnas[6]]:"";
                $VoiceMail_PW       = isset($arrayColumnas[7]) ?$tupla[$arrayColumnas[7]]:"";
                $VM_Email_Address   = isset($arrayColumnas[8]) ?$tupla[$arrayColumnas[8]]:"";
                $VM_Pager_Email_Addr= isset($arrayColumnas[9]) ?$tupla[$arrayColumnas[9]]:"";
                $VM_Options         = isset($arrayColumnas[10])?$tupla[$arrayColumnas[10]]:"";
                $VM_EmailAttachment = isset($arrayColumnas[11])?$tupla[$arrayColumnas[11]]:"";
                $VM_Play_CID        = isset($arrayColumnas[12])?$tupla[$arrayColumnas[12]]:"";
                $VM_Play_Envelope   = isset($arrayColumnas[13])?$tupla[$arrayColumnas[13]]:"";
                $VM_Delete_Vmail    = isset($arrayColumnas[14])?$tupla[$arrayColumnas[14]]:"";
                $Context            = isset($arrayColumnas[15])?$tupla[$arrayColumnas[15]]:"from-internal";
                if (trim($Context) == "") $Context = "from-internal";
                $Tech               = strtolower($tupla[$arrayColumnas[16]]);
                $Callgroup          = isset($arrayColumnas[17])?$tupla[$arrayColumnas[17]]:"";
                $Pickupgroup        = isset($arrayColumnas[18])?$tupla[$arrayColumnas[18]]:"";
                $Disallow           = isset($arrayColumnas[19])?$tupla[$arrayColumnas[19]]:"";
                $Allow              = isset($arrayColumnas[20])?$tupla[$arrayColumnas[20]]:"";
                $Deny               = isset($arrayColumnas[21])?$tupla[$arrayColumnas[21]]:"";
                $Permit             = isset($arrayColumnas[22])?$tupla[$arrayColumnas[22]]:"";
                $Record_Incoming    = isset($arrayColumnas[23])?$tupla[$arrayColumnas[23]]:"";
                $Record_Outgoing    = isset($arrayColumnas[24])?$tupla[$arrayColumnas[24]]:"";
                
//////////////////////////////////////////////////////////////////////////////////

                $Record_Incoming = strtolower($Record_Incoming);
                $Record_Outgoing = strtolower($Record_Outgoing);
        
                if(preg_match("/^(on demand|adhoc)/",$Record_Incoming)){
                    $Record_Incoming = "Adhoc";
                }elseif(preg_match("/^always/",$Record_Incoming)){
                    $Record_Incoming = "always";
                }elseif(preg_match("/^never/",$Record_Incoming)){
                    $Record_Incoming = "never";
                }

                if(preg_match("/(on demand|adhoc)/",$Record_Outgoing)){
                    $Record_Outgoing = "Adhoc";
                }elseif(preg_match("/^always/",$Record_Outgoing)){
                    $Record_Outgoing = "always";
                }elseif(preg_match("/^never/",$Record_Outgoing)){
                    $Record_Outgoing = "never";
                }
                    
//////////////////////////////////////////////////////////////////////////////////
                // validando para que coja las comillas
                $Outbound_CID = preg_replace('/“/', "\"", $Outbound_CID);
                $Outbound_CID = preg_replace('/”/', "\"", $Outbound_CID);

//////////////////////////////////////////////////////////////////////////////////
                //Paso 1: creando en la tabla sip - iax 
                if(!$pLoadExtension->createTechDevices($Ext,$Secret,$VoiceMail,$Context,$Tech, $Disallow, $Allow, $Deny, $Permit, $Callgroup, $Pickupgroup, $Record_Incoming, $Record_Outgoing))
                {
                    $Messages .= "Ext: $Ext - ". _tr('Error updating Tech').": ".$pLoadExtension->errMsg."<br />";
                }else{
                    
                    //Paso 2: creando en la tabla users
                    if(!$pLoadExtension->createUsers($Ext,$Name,$VoiceMail,$Direct_DID,$Outbound_CID, $Record_Incoming, $Record_Outgoing))
                        $Messages .= "Ext: $Ext - ". _tr('Error updating Users').": ".$pLoadExtension->errMsg."<br />";
                    
                    //Paso 3: creando en la tabla devices
                    if(!$pLoadExtension->createDevices($Ext,$Tech,$Name))
                        $Messages .= "Ext: $Ext - ". _tr('Error updating Devices').": ".$pLoadExtension->errMsg."<br />";
                    
                    //Paso 4: creando en el archivo /etc/asterisk/voicemail.conf los voicemails
                    if(!$pLoadExtension->writeFileVoiceMail(
                        $Ext,$Name,$VoiceMail,$VoiceMail_PW,$VM_Email_Address,
                        $VM_Pager_Email_Addr,$VM_Options,$VM_EmailAttachment,$VM_Play_CID,
                        $VM_Play_Envelope, $VM_Delete_Vmail)
                      )
                        $Messages .= "Ext: $Ext - ". _tr('Error updating Voicemail')."<br />";
                    
                    //Paso 5: Configurando el call waiting
                    if(!$pLoadExtension->processCallWaiting($Call_Waiting,$Ext))
                        $Messages .= "Ext: $Ext - ". _tr('Error processing CallWaiting')."<br />";

                    $outboundcid = preg_replace("/\"/", "'", $Outbound_CID);
                    $outboundcid = preg_replace("/\"/", "'", $outboundcid);
                    $outboundcid = preg_replace("/ /", "", $outboundcid);
                    if(!$pLoadExtension->putDataBaseFamily($data_connection, $Ext, $Tech, $Name, $VoiceMail, $outboundcid , $Record_Incoming, $Record_Outgoing))
                        $Messages .= "Ext: $Ext - ". _tr('Error processing Database Family')."<br />";
                    $cont++;
                }
                    
                ////////////////////////////////////////////////////////////////////////
                //Paso 7: Escribiendo en tabla incoming
        if($Direct_DID !== ""){
            if(!$pLoadExtension->createDirect_DID($Ext,$Direct_DID))
            $Messages .= "Ext: $Ext - ". _tr('Error to insert or update Direct DID')."<br />";
        }
                /////////////////////////////////////////////////////////////////////////
            }
        }
        //Paso 6: Realizo reload
        if(!$pLoadExtension->do_reloadAll($data_connection, $arrAST, $arrAMP))
            $Messages .= $pLoadExtension->errMsg;
        $Messages .= _tr('Total extension updated').": $cont<br />";
        $smarty->assign("mb_message", $Messages);
    }

    unlink($ruta_archivo);
}

function isValidCSV($pDB, $sFilePath, &$arrayColumnas){
    $pLoadExtension = new paloSantoLoadExtension($pDB);
    $hArchivo = fopen($sFilePath, 'r+');
    $cont = 0;
    $ColName = -1;

    //Paso 1: Obtener Cabeceras (Minimas las cabeceras: Display Name, User Extension, Secret)
    if ($hArchivo) {
        $tupla = fgetcsv($hArchivo, 4096, ",");
        
        if(count($tupla)>=4)
        {
            for($i=0; $i<count($tupla); $i++)
            {
                if($tupla[$i] == 'Display Name')                $arrayColumnas[0] = $i;
                else if($tupla[$i] == 'User Extension')         $arrayColumnas[1] = $i;
                else if($tupla[$i] == 'Direct DID')             $arrayColumnas[2] = $i;
                else if($tupla[$i] == 'Outbound CID')           $arrayColumnas[3] = $i;
                else if($tupla[$i] == 'Call Waiting')           $arrayColumnas[4] = $i;
                else if($tupla[$i] == 'Secret')                 $arrayColumnas[5] = $i;
                else if($tupla[$i] == 'Voicemail Status')       $arrayColumnas[6] = $i;
                else if($tupla[$i] == 'Voicemail Password')     $arrayColumnas[7] = $i;
                else if($tupla[$i] == 'VM Email Address')       $arrayColumnas[8] = $i;
                else if($tupla[$i] == 'VM Pager Email Address') $arrayColumnas[9] = $i;
                else if($tupla[$i] == 'VM Options')             $arrayColumnas[10] = $i;
                else if($tupla[$i] == 'VM Email Attachment')    $arrayColumnas[11] = $i;
                else if($tupla[$i] == 'VM Play CID')            $arrayColumnas[12] = $i;
                else if($tupla[$i] == 'VM Play Envelope')       $arrayColumnas[13] = $i;
                else if($tupla[$i] == 'VM Delete Vmail')        $arrayColumnas[14] = $i;
                else if($tupla[$i] == 'Context')                $arrayColumnas[15] = $i;
                else if($tupla[$i] == 'Tech')                   $arrayColumnas[16] = $i;
                else if($tupla[$i] == 'Callgroup')              $arrayColumnas[17] = $i;
                else if($tupla[$i] == 'Pickupgroup')            $arrayColumnas[18] = $i;
                else if($tupla[$i] == 'Disallow')               $arrayColumnas[19] = $i;
                else if($tupla[$i] == 'Allow')                  $arrayColumnas[20] = $i;
                else if($tupla[$i] == 'Deny')                   $arrayColumnas[21] = $i;
                else if($tupla[$i] == 'Permit')                 $arrayColumnas[22] = $i;
                else if($tupla[$i] == 'Record Incoming')        $arrayColumnas[23] = $i;
                else if($tupla[$i] == 'Record Outgoing')        $arrayColumnas[24] = $i;
                
            }  
            if(isset($arrayColumnas[0]) && isset($arrayColumnas[1]) && isset($arrayColumnas[5]) && isset($arrayColumnas[16]))
            {
                //Paso 2: Obtener Datos (Validacion que esten llenos los mismos de las cabeceras)
                $count = 2;
                while ($tupla = fgetcsv($hArchivo, 4096,",")) {
                    if(is_array($tupla) && count($tupla)>=3)
                    {
                        $Ext = $tupla[$arrayColumnas[1]];
                        if($Ext != '')
                            $arrExt[] = array("ext" => $Ext);
                        else return _tr("Can't exist a extension empty. Line").": $count. - ". _tr("Please read the lines in the footer");

                        $Display = $tupla[$arrayColumnas[0]];
                        if($Display == '')
                            return _tr("Can't exist a display name empty. Line").": $count. - ". _tr("Please read the lines in the footer");

                        $Secret = $tupla[$arrayColumnas[5]];
                        if(!$pLoadExtension->valida_password($Secret))
                            return _tr("Secret weak. Line").": $count. - ". _tr("The secret must be minimum");

                        $Tech = $tupla[$arrayColumnas[16]];
                        if($Tech == '')
                            return _tr("Can't exist a technology empty. Line").": $count. - ". _tr("Please read the lines in the footer");
                        else
                            $arrTech[] = strtolower($Tech);
                    }
                    $count++;
                }

                //Paso 3: Validacion extensiones repetidas
                if(is_array($arrExt) && count($arrExt) > 0){
                    foreach($arrExt as $key1 => $values1){
                        foreach($arrExt as $key2 => $values2){
                            if( ($values1['ext']==$values2['ext'])  &&  ($key1!=$key2) ){
                                return _tr("Error, extension")." ".$values1['ext']." "._tr("repeat in lines")." ".($key1 + 2)." "._tr("with")." ".($key2 + 2);
                            }
                        }
                        if( $arrTech[$key1]!="sip" && $arrTech[$key1]!="iax" && $arrTech[$key1]!="iax2" ){
                            return _tr("Error, extension")." ".$values1['ext']." "._tr("has a wrong tech in line")." ".($key1 + 2)." "._tr("Tech must be sip or iax");
                        }
                    }
                    return "valided";
                }
            }else return _tr("Verify the header") ." - ". _tr("At minimum there must be the columns").": \"Display Name\", \"User Extension\", \"Secret\", \"Tech\"";
        }
        else return _tr("Verify the header") ." - ". _tr("Incomplete Columns");
    }else return _tr("The file is incorrect or empty") .": $sFilePath";
}

function getAction()
{
    //if(isset($_POST["update"]) && $_POST["update"]=='on') $accion = "update_extension";
    if(isset($_POST["save"]))
        return "load_extension";
    else if(isset($_POST["backup"]))
        return "backup_extension";
    else if(isset($_POST["delete_all"]))
        return "delete_all";
    else if(isset($_GET["accion"]) && $_GET["accion"]=="backup_extension")
        return "backup_extension";
    else if (isset($_GET['accion']) && $_GET['accion'] == 'download_csv')
        return 'download_csv';
    else
        return "report_extension";
}

function download_extensions($pDB)
{
    header("Cache-Control: private");
    header("Pragma: cache");
    header('Content-Type: text/csv; charset=iso-8859-1; header=present');
    header("Content-disposition: attachment; filename=extensions.csv");
    echo backup_extensions($pDB);
}

function backup_extensions($pDB)
{
    $csv = "";
    $pLoadExtension = new paloSantoLoadExtension($pDB);
    $r = $pLoadExtension->queryExtensions();
    
    if (!is_array($r)) {
        print $pLoadExtension->errMsg;
        return;
    }
    
    $keyOrder = array(
        'name'                  =>  'Display Name',
        'extension'             =>  'User Extension',
        'directdid'             =>  'Direct DID',
        'outboundcid'           =>  'Outbound CID',
        'callwaiting'           =>  'Call Waiting',
        'secret'                =>  'Secret',
        'voicemail'             =>  'Voicemail Status',
        'vm_secret'             =>  'Voicemail Password',
        'email_address'         =>  'VM Email Address',
        'pager_email_address'   =>  'VM Pager Email Address',
        'vm_options'            =>  'VM Options',
        'email_attachment'      =>  'VM Email Attachment',
        'play_cid'              =>  'VM Play CID',
        'play_envelope'         =>  'VM Play Envelope',
        'delete_vmail'          =>  'VM Delete Vmail',
        'context'               =>  'Context',
        'tech'                  =>  'Tech',
        'callgroup'             =>  'Callgroup',
        'pickupgroup'           =>  'Pickupgroup',
        'disallow'              =>  'Disallow',
        'allow'                 =>  'Allow',
        'deny'                  =>  'Deny',
        'permit'                =>  'Permit',
        'record_in'             =>  'Record Incoming',
        'record_out'            =>  'Record Outgoing',
        );
    print '"'.implode('","', $keyOrder)."\"\n";
    
    
    foreach ($r as $tupla) {
    
        $t = array();
        foreach (array_keys($keyOrder) as $k) switch ($k) {
        
            case 'name':                    $t[] = $tupla['name']; break;
            case 'extension':               $t[] = $tupla['extension']; break;
            case 'directdid':               $t[] = $tupla['directdid']; break;
            case 'outboundcid':             $t[] = $tupla['outboundcid']; break;
            case 'callwaiting':             $t[] = $tupla['callwaiting']; break;
            case 'voicemail':               $t[] = $tupla['voicemail']; break;
            case 'vm_secret':               $t[] = $tupla['vm_secret']; break;
            case 'email_address':           $t[] = $tupla['email_address']; break;
            case 'pager_email_address':     $t[] = $tupla['pager_email_address']; break;
            case 'vm_options':              $t[] = $tupla['vm_options']; break;
            case 'email_attachment':        $t[] = $tupla['email_attachment']; break;
            case 'play_cid':                $t[] = $tupla['play_cid']; break;
            case 'play_envelope':           $t[] = $tupla['play_envelope']; break;
            case 'delete_vmail':            $t[] = $tupla['delete_vmail']; break;
            case 'tech':                    $t[] = $tupla['tech']; break;
            
            default:
            if (isset($tupla['parameters'][$k])){                             
                if ($tupla['parameters'][$k] == "Adhoc"){
                    $tupla['parameters'][$k] = "On Demand";
                    $t[] = $tupla['parameters'][$k];
                }
                else
                    $t[] = $tupla['parameters'][$k];
            }else
                $t[] = '';
            
        }
        
        print '"'.implode('","', $t)."\"\n";
    }
    return $csv;
}

?>
