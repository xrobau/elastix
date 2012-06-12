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
    global $arrConf;
    global $arrLang;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP  = $pConfig->leer_configuracion(false);

    $dsnAsterisk = $arrAMP['AMPDBENGINE']['valor']."://".
                   $arrAMP['AMPDBUSER']['valor']. ":".
                   $arrAMP['AMPDBPASS']['valor']. "@".
                   $arrAMP['AMPDBHOST']['valor']. "/asterisk";

    $pDB = new paloDB($dsnAsterisk);
    if(!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", $arrLang["Error when connecting to database"]."<br/>".$pDB->errMsg);
    }

    $pConfig = new paloConfig($arrAMP['ASTETCDIR']['valor'], "asterisk.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAST  = $pConfig->leer_configuracion(false);

    $content = "";
    $accion = getAction();

    //Sirve para todos los casos
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("DOWNLOAD", $arrLang["Download Extensions"]);
    $smarty->assign("label_file", $arrLang["File"]);
    $smarty->assign("title_module", $arrLang["Extensions Batch"]);
    $smarty->assign("HeaderFile", $arrLang["Header File Extensions Batch"]);
    $smarty->assign("AboutUpdate", $arrLang["About Update Extensions Batch"]);
    $smarty->assign("LINK", "modules/$module_name/libs/download_csv.php");
    $smarty->assign("DELETE_ALL", $arrLang["Delete all extension"]);

    switch($accion)
    {
        case 'delete_all':
            delete_all_extention($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig, $pDB, $arrAST, $arrAMP);
            $content = report_extension($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig);
            break;
        case 'load_extension':
            $content = load_extension($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig, $base_dir, $pDB, $arrAST, $arrAMP);
            break;
        default:
            $content = report_extension($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig);
            break;
    }

    return $content;
}

function delete_all_extention(&$smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig, $pDB, $arrAST, $arrAMP)
{
    $message = "";
    $oPalo = new paloSantoLoadExtension($pDB);
    $arrSipExtension = array();

    $data_connection = array('host' => "127.0.0.1", 'user' => "admin", 'password' => "elastix456");
    $arrData = $oPalo->getExtensionSip();
    foreach($arrData as $key => $value)
      $arrSipExtension[] = $value;
    if($oPalo->deleteTreeSip($data_connection, $arrAST, $arrAMP, $arrSipExtension)){
		if($oPalo->deleteAllExtension())
        {
			if($oPalo->do_reloadAll($data_connection, $arrAST, $arrAMP))
            {
            	$smarty->assign("mb_title", $arrLang["Message"]);
                $smarty->assign("mb_message", $arrLang["All extensions deletes"]);
            }else{
                $smarty->assign("mb_title", $arrLang["Error"]);
                $smarty->assign("mb_message", $arrLang["Unable to reload the changes"]);
            }
        }else{
			$smarty->assign("mb_title", $arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["Could not delete the database"]);
        }
    }else{
        $smarty->assign("mb_title", $arrLang["Message"]);
        $smarty->assign("mb_message", $arrLang["Could not delete the ASTERISK database"]);
    }

}

function report_extension($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig){

    $oForm = new paloForm($smarty, array());
    $html = $oForm->fetchForm("$local_templates_dir/extension.tpl", $arrLang["Extensions Batch"], $_POST);

    $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$html."</form>";
    return $contenidoModulo;
}

function load_extension($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig, $base_dir, $pDB, $arrAST, $arrAMP)
{
    $oForm = new paloForm($smarty, array());
    $html = $oForm->fetchForm("$local_templates_dir/extension.tpl", $arrLang["Extensions Batch"], $_POST);

    $arrTmp=array();
    $bMostrarError = false;

    //valido el tipo de archivo
    if (!eregi('.csv$', $_FILES['userfile']['name'])) {
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang["Invalid file extension.- It must be csv"]);
    }else {
        if(is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            $ruta_archivo = "/tmp/".$_FILES['userfile']['name'];
            copy($_FILES['userfile']['tmp_name'], $ruta_archivo);
            //Funcion para cargar las extensiones
            load_extension_from_csv($smarty, $arrLang, $ruta_archivo, $base_dir, $pDB, $arrAST, $arrAMP);
        }else {
            $smarty->assign("mb_title", $arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." :". $_FILES['userfile']['name']);
        }
    }
    $content = report_extension($smarty, $module_name, $local_templates_dir, $arrLang, $arrConfig);
    return $content;
}

function load_extension_from_csv($smarty, $arrLang, $ruta_archivo, $base_dir, $pDB, $arrAST, $arrAMP){
    $Messages = "";
    $arrayColumnas = array();
    $data_connection = array('host' => "127.0.0.1", 'user' => "admin", 'password' => "elastix456");

    $result = isValidCSV($arrLang, $ruta_archivo, $arrayColumnas);
    if($result != true){
        $smarty->assign("mb_message", $result);
        return;
    }

    $hArchivo = fopen($ruta_archivo, 'rt');
    $cont = 0;
    $pLoadExtension = new paloSantoLoadExtension($pDB);

    if($hArchivo) {
        //Linea 1 header ignorada
        $tupla = fgetcsv($hArchivo, 4096, ",");
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
                $VM_Options         = isset($arrayColumnas[10]) ?$tupla[$arrayColumnas[10]]:"";
                $VM_EmailAttachment = isset($arrayColumnas[11])?$tupla[$arrayColumnas[11]]:"";
                $VM_Play_CID        = isset($arrayColumnas[12])?$tupla[$arrayColumnas[12]]:"";
                $VM_Play_Envelope   = isset($arrayColumnas[13])?$tupla[$arrayColumnas[13]]:"";
                $VM_Delete_Vmail    = isset($arrayColumnas[14])?$tupla[$arrayColumnas[14]]:"";
                $Context            = isset($arrayColumnas[15])?$tupla[$arrayColumnas[15]]:"from-internal";

                //Paso 1: creando en la tabla sip
                if(!$pLoadExtension->createSipDevices($Ext,$Secret,$VoiceMail,$Context))
                {
                    $Messages .= "Ext: $Ext - ". $arrLang["Error updating Sip"].": ".$pLoadExtension->errMsg."<br />";
                }else{
                    
                    //Paso 2: creando en la tabla users
                    if(!$pLoadExtension->createUsers($Ext,$Name,$VoiceMail,$Direct_DID,$Outbound_CID))
                        $Messages .= "Ext: $Ext - ". $arrLang["Error updating Users"].": ".$pLoadExtension->errMsg."<br />";
                    
                    //Paso 3: creando en la tabla devices
                    if(!$pLoadExtension->createDevices($Ext,"sip",$Name))
                        $Messages .= "Ext: $Ext - ". $arrLang["Error updating Devices"].": ".$pLoadExtension->errMsg."<br />";
                    
                    //Paso 4: creando en el archivo /etc/asterisk/voicemail.conf los voicemails
                    if(!$pLoadExtension->writeFileVoiceMail(
                        $Ext,$Name,$VoiceMail,$VoiceMail_PW,$VM_Email_Address,
                        $VM_Pager_Email_Addr,$VM_Options,$VM_EmailAttachment,$VM_Play_CID,
                        $VM_Play_Envelope, $VM_Delete_Vmail)
                      )
                        $Messages .= "Ext: $Ext - ". $arrLang["Error updating Voicemail"]."<br />";

                    //Paso 5: Configurando el call waiting
                    if(!$pLoadExtension->processCallWaiting($Call_Waiting,$Ext))
                        $Messages .= "Ext: $Ext - ". $arrLang["Error processing CallWaiting"]."<br />";

                    if(!$pLoadExtension->putDataBaseFamily($data_connection, $Ext, "sip", $Name, $VoiceMail))
                        $Messages .= "Ext: $Ext - ". $arrLang["Error processing Database Family"]."<br />";
                    $cont++;
                }
            }
        }
        //Paso 6: Realizo reload
        if(!$pLoadExtension->do_reloadAll($data_connection, $arrAST, $arrAMP))
            $Messages .= $pLoadExtension->errMsg;

        $Messages .= $arrLang["Total extension updated"].": $cont<br />";
        $smarty->assign("mb_message", $Messages);
    }

    unlink($ruta_archivo);
}

function isValidCSV($arrLang, $sFilePath, &$arrayColumnas){
    $hArchivo = fopen($sFilePath, 'rt');
    $cont = 0;
    $ColName = -1;

    //Paso 1: Obtener Cabeceras (Minimas las cabeceras: Display Name, User Extension, Secret)
    if ($hArchivo) {
        $tupla = fgetcsv($hArchivo, 4096, ",");
        if(count($tupla)>=3)
        {
            for($i=0; $i<count($tupla); $i++)
            {
                if($tupla[$i] == 'Display Name')            $arrayColumnas[0] = $i;
                else if($tupla[$i] == 'User Extension')     $arrayColumnas[1] = $i;
                else if($tupla[$i] == 'Direct DID')         $arrayColumnas[2] = $i;
                else if($tupla[$i] == 'Outbound CID')       $arrayColumnas[3] = $i;
                else if($tupla[$i] == 'Call Waiting')       $arrayColumnas[4] = $i;
                else if($tupla[$i] == 'Secret')             $arrayColumnas[5] = $i;
                else if($tupla[$i] == 'Voicemail Status')   $arrayColumnas[6] = $i;
                else if($tupla[$i] == 'Voicemail Password') $arrayColumnas[7] = $i;
                else if($tupla[$i] == 'VM Email Address')   $arrayColumnas[8] = $i;
                else if($tupla[$i] == 'VM Pager Email Address') $arrayColumnas[9] = $i;
                else if($tupla[$i] == 'VM Options')         $arrayColumnas[10] = $i;
                else if($tupla[$i] == 'VM Email Attachment')$arrayColumnas[11] = $i;
                else if($tupla[$i] == 'VM Play CID')        $arrayColumnas[12] = $i;
                else if($tupla[$i] == 'VM Play Envelope')   $arrayColumnas[13] = $i;
                else if($tupla[$i] == 'VM Delete Vmail')    $arrayColumnas[14] = $i;
                else if($tupla[$i] == 'Context')            $arrayColumnas[15] = $i;
            }
            if(isset($arrayColumnas[0]) && isset($arrayColumnas[1]) && isset($arrayColumnas[5]))
            {
                //Paso 2: Obtener Datos (Validacion que esten llenos los mismos de las cabeceras)
                $count = 2;
                while ($tupla = fgetcsv($hArchivo, 4096,",")) {
                    if(is_array($tupla) && count($tupla)>=3)
                    {
                        $Ext = $tupla[$arrayColumnas[1]];
                        if($Ext != '')
                            $arrExt[] = array("ext" => $Ext);
                        else return $arrLang["Can't exist a extension empty. Line"].": $count. - ". $arrLang["Please read the lines in the footer"];

                        $Secret       = $tupla[$arrayColumnas[5]];
                        if($Secret == '')
                            return $arrLang["Can't exist a secret empty. Line"].": $count. - ". $arrLang["Please read the lines in the footer"];

                        $Display      = $tupla[$arrayColumnas[0]];
                        if($Display == '')
                            return $arrLang["Can't exist a display name empty. Line"].": $count. - ". $arrLang["Please read the lines in the footer"];
                    }
                    $count++;
                }

                //Paso 3: Validacion extensiones repetidas
                if(is_array($arrExt) && count($arrExt) > 0){
                    foreach($arrExt as $key1 => $values1){
                        foreach($arrExt as $key2 => $values2){
                            if( ($values1['ext']==$values2['ext'])  &&  ($key1!=$key2) ){
                                return "{$arrLang["Error, extension"]} ".$values1['ext']." {$arrLang["repeat in lines"]} ".($key1 + 2)." {$arrLang["with"]} ".($key2 + 2);
                            }
                        }
                    }
                    return true;
                }
            }else return $arrLang["Verify the header"] ." - ". $arrLang["At minimum there must be the columns"].": \"Display Name\", \"User Extension\", \"Secret\"";
        }
        else return $arrLang["Verify the header"] ." - ". $arrLang["Incomplete Columns"];
    }else return $arrLang["The file is incorrect or empty"] .": $sFilePath";
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
    else
        return "report_extension";
}
?>
