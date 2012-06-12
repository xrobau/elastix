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
  $Id: new_campaign.php $ */

include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoFax.class.php";

define("MYSQL_ROOT_PASSWORD","eLaStIx.2oo7");
function _moduleContent(&$smarty, $module_name)
{

    require_once "libs/misc.lib.php";
    require_once "libs/paloSantoForm.class.php";
    include_once "libs/cyradm.php";
    include_once "configs/email.conf.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //global $cadena_dsn;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $contenidoModulo = "";
    $action="";
    if (isset($_POST["submit_borrar"])) $action = "submit_borrar";
    if (isset($_POST["submit_restore"]) || isset($_POST["process"])) $action = "submit_restore";
	
	//backup
    if (isset($_POST["backup"]) || isset($_POST["process_backup"])) $action = "backup";	
	

    // entra en este if solo si se hizo submit el borrar
    switch ($action) {
        case "backup":
            //Se realiza proceso de Backup

            $strErrorMsg = '';
            $bSaveBackup=false;
            $arrBackupOptions=array(
                              "elastix_db"=>array("desc"=>$arrLang["Elastix Database"],"check"=>"","msg"=>""),
                               "sounds"=> array("desc"=>$arrLang["Sounds"],"check"=>"","msg"=>""),
                            "config_files"=> array("desc"=>$arrLang["Configuration Files"],"check"=>"","msg"=>""),
                            "fax"=> array("desc"=>$arrLang["Fax"],"check"=>"","msg"=>""),
                            "voicemail"=> array("desc"=>$arrLang["Voicemails"],"check"=>"","msg"=>""),
                            "monitors"=> array("desc"=>$arrLang["Monitors"],"check"=>"","msg"=>""),
                            "tftp"=> array("desc"=>$arrLang["tFTP"],"check"=>"","msg"=>""),
                            "email"=> array("desc"=>$arrLang["Email Acccounts"],"check"=>"","msg"=>""),
                            );
            //obtener la version de mysql
               // define('MYSQL_INT_VERSION', " 5.0.22");
            //

            $smarty->assign("title", $arrLang["Backup"]);

            $smarty->assign("PROCESS_BACKUP",$arrLang["Process"]);
            $smarty->assign("LBL_TODOS", $arrLang["All options"]);
            $smarty->assign("BACK", "<< ".$arrLang["Backup List"]);
            $arrSelectedOptions=array();
              $backup_all=false;
            
              //print_r($_POST);
            if (isset($_POST["process_backup"]))
            {
                #realizar el respaldo de lo que está seleccionado
                if (isset($_POST["backup_total"]))
                {
                    $arrSelectedOptions=array_keys($arrBackupOptions);
                    foreach ($arrBackupOptions as $key=>$arrOption) $arrBackupOptions[$key]["check"]="checked";
                    $backup_all=true;
                }
                else
                {
                    #verificar sobre cuales hacer respaldo
                    foreach ($arrBackupOptions as $key=>$arrOption)
                    {
                        #verifica si ha seleccionado esa opcion
                        if (isset($_POST[$key]))
                        {
                            #le pongo checked
                            $arrBackupOptions[$key]["check"]="checked";
                            #lo agrego al arreglo
                            $arrSelectedOptions[]=$key;
                        }
                    }
                }

                #verifico que haya seleccionado al menos una opcion
                if (!count($arrSelectedOptions)>0)
                {
                   #no ha seleccionado opcion
                   $smarty->assign("ERROR_MSG", $arrLang["Choose an option to backup"]);
                }
                else
                {
        
                    #crear la carpeta donde se va a copiar el respaldo que se realice
                    $dir_respaldo = "backup";
                    //$timestamp=time();
                    $valor_unico = "-".date("YmdHis")."-".substr(session_id(), 0, 1).substr(session_id(), -1, 1);
                    $carpeta_respaldo = "backup";
                    $timestamp= $carpeta_respaldo.$valor_unico;
                    $ruta_respaldo="$dir_respaldo/$timestamp";
    
                    $ruta_respaldo_sin_valor_unico = "$dir_respaldo/$carpeta_respaldo";

                    //asegurarme que ya no exista la carpeta
                    //si ya existe BORRO contenido
                    if (file_exists($ruta_respaldo_sin_valor_unico)){
                        exec("rm -rf $ruta_respaldo_sin_valor_unico",$output,$retval);
                    }
                    mkdir($ruta_respaldo_sin_valor_unico); // ??
            
                    #hacer el respaldo de las opciones seleccionadas
                    #tengo que mostrar cuales de las opciones seleccionadas, se hizo el respaldo correctamente por eso envio $arrBackupOptions
                    process_backup($arrSelectedOptions,$ruta_respaldo_sin_valor_unico,$arrBackupOptions);
                    #en la carpeta backup ya deberia tener los respaldos
                    #comprimo la carpeta
                    #y la envio al navegador
                    exec("tar -C $dir_respaldo -cvzf $dir_respaldo/elastix$timestamp.tgz $carpeta_respaldo ",$output,$retval);
                    if ($retval<>0) //no se pudo generar el archivo comprimido
                        $errMsg= $arrLang["Could not generate backup file"]." : $dir_respaldo/elastix$timestamp.tgz\n";
                    else{
                        #mensaje que se ha completado el backup
                        $smarty->assign("ERROR_MSG", $arrLang["Backup Complete!"]." : $dir_respaldo/elastix$timestamp.tgz");
                         /*   #lo envio al browser
                        header("Cache-Control: private");
                        header("Pragma: cache");
                        header("Content-Type: application/octet-stream\n");
                        header("Content-Disposition: attachment; filename=elastixBackup.tgz"); 
                        header ("Content-Length: ".filesize("$dir_respaldo/elastixBackup.tgz"));
                        print file_get_contents("$dir_respaldo/elastixBackup.tgz");
                        $bSaveBackup=true;*/
                         //   exit();
                          #      print "Backup file location: $dir_respaldo/elastixBackup.tgz\n";
                    }
                    //borro la carpeta de backup
                    exec("rm $ruta_respaldo_sin_valor_unico -rf");
                     //   exec("rm $dir_respaldo/elastixBackup.tgz");
                    //    rmdir($ruta_respaldo_sin_valor_unico);
                }
            }    
            $all_checked=$backup_all?"checked":"";
               $smarty->assign("all_checked", $all_checked);
            $smarty->assign("backup_options", $arrBackupOptions);
            if ($bSaveBackup) exit();
            $contenidoModulo .= $smarty->fetch("$local_templates_dir/backup.tpl");
            break;
        case "submit_restore":

            $bSaveRestore=false;
            $arrRestoreOptions=array(
                                "elastix_db"=>array("desc"=>$arrLang["Elastix Database"],"check"=>"","msg"=>""),
                                "sounds"=> array("desc"=>$arrLang["Sounds"],"check"=>"","msg"=>""),
                                "config_files"=> array("desc"=>$arrLang["Configuration Files"],"check"=>"","msg"=>""),
                                "fax"=> array("desc"=>$arrLang["Fax"],"check"=>"","msg"=>""),
                                "voicemail"=> array("desc"=>$arrLang["Voicemails"],"check"=>"","msg"=>""),
                                "monitors"=> array("desc"=>$arrLang["Monitors"],"check"=>"","msg"=>""),
                                "tftp"=> array("desc"=>$arrLang["tFTP"],"check"=>"","msg"=>""),
                                "email"=> array("desc"=>$arrLang["Email Acccounts"],"check"=>"","msg"=>""),
                                );
        
            // se obtiene el nombre del archivo que se va a restaurar
            if (isset($_POST["submit_restore"])) {
                $arr = array_keys($_POST["submit_restore"]);
                $archivo_post = $arr[0];
            } else {
                $archivo_post = $_POST["backup_file"];
            } 
            $_ruta_archivo_ = "/var/www/html/".$arrConf["dir_backup"]."/$archivo_post";

            $smarty->assign("BACKUP_FILE", $_ruta_archivo_);
        
            $smarty->assign("title", $arrLang["Restore"].": ".$archivo_post);
        
            $smarty->assign("PROCESS_RESTORE", $arrLang["Process"]);
            $smarty->assign("LBL_BACKUP_FILE", $arrLang["Backup File Location"]);
            $smarty->assign("LBL_TODOS", $arrLang["All options"]);
            $smarty->assign("BACK", "<< ".$arrLang["Backup List"]);
        
        
            $arrSelectedOptions=array();
            $restore_all=false;

            if (isset($_POST["process"]))
            {
                #realizar el respaldo de lo que está seleccionado
                if (isset($_POST["restore_total"]))
                {
                    $arrSelectedOptions=array_keys($arrRestoreOptions);
                    foreach ($arrRestoreOptions as $key=>$arrOption) $arrRestoreOptions[$key]["check"]="checked";
                    $restore_all=true;
                }
                else
                {
                    #verificar sobre cuales hacer respaldo
                    foreach ($arrRestoreOptions as $key=>$arrOption)
                    {
                        #verifica si ha seleccionado esa opcion
                        if (isset($_POST[$key]))
                        {
                            #le pongo checked
                            $arrRestoreOptions[$key]["check"]="checked";
                            #lo agrego al arreglo
                            $arrSelectedOptions[]=$key;
                        }
                    }
                }
        
                #verifico que haya seleccionado al menos una opcion
                if (!count($arrSelectedOptions)>0)
                {
                #no ha seleccionado opcion
                    $smarty->assign("ERROR_MSG", $arrLang["Choose an option to restore"]);
                }
                else
                {
                    #verificar que existe el archivo de respaldo
                    $backup_file=$_POST['backup_file'];
                    if (empty($backup_file))
                        $smarty->assign("ERROR_MSG", $arrLang["Backup file path can't be empty"]);
                    else
                    {
                        #verificar que el archivo existe
                        if (!file_exists($backup_file))
                        {
                            $smarty->assign("ERROR_MSG", $arrLang["File doesn't exist"]);
                        }else
                        {
                        
                            #crear la carpeta donde se va a descomprimir el archivo de respaldo
                            $dir_respaldo = "/var/www/html/backup";
                            $timestamp=time();
                            $ruta_restaurar="/var/www/html/backup/restore";
                            $ruta_respaldo="$ruta_restaurar/backup";
                            if (!file_exists($ruta_restaurar)) mkdir($ruta_restaurar);
                            #descomprimir el archivo
                            $comando="tar -C $ruta_restaurar -xvzf $backup_file ";
                            exec($comando,$output,$retval);
                            if ($retval==0)
                            {
                                #pude descomprimirlo
                                #al descomprimir se debe crear una carpeta con nombre backup
                                #hacer el restore de lo elegido
                                process_restore($arrSelectedOptions,$ruta_respaldo,$ruta_restaurar,$arrRestoreOptions);
                                //borro la carpeta de restore
                            // exec("rm $ruta_restaurar -rf");
                                $smarty->assign("ERROR_MSG", $arrLang["Restore Complete!"]);
                            }
                        }
                    }
                }
            }
            $all_checked=$restore_all?"checked":"";
            $smarty->assign("all_checked", $all_checked);
            $smarty->assign("restore_options", $arrRestoreOptions);
            if ($bSaveRestore) exit();
            $contenidoModulo = $smarty->fetch("$local_templates_dir/restore.tpl");

        break;
        case "submit_borrar":
            // se obtiene en un arreglo el listado de archivos a borrar
            $archivos_borrar = isset($_POST["chk"])?$_POST["chk"]:array();
            if (count($archivos_borrar)>0){
                $msj = "";
                // se envía a la funcion borra_archivos el arreglo con todos los archivos y una variable msj que es por referencia
                if (!borra_archivos($archivos_borrar, $msj)) {
                    $smarty->assign("mb_message", $msj);
                }
            } else {
                $smarty->assign("mb_message", $arrLang["There are not backup file selected"]);
            }
        default:
            //CONSULTAR TODOS LOS ARCHIVOS DE BACKUP
            $contenidoModulo .= listadoBackup($smarty, $module_name, $local_templates_dir);
    } // fin del switch


    if(is_null($contenidoModulo))
        return "";
    else
        return $contenidoModulo;
}

function listadoBackup($smarty, $module_name, $local_templates_dir) {

    global $arrLang;
    global $arrConf;

    $nombre_archivos = array();

    // INICIO: se obtiene el listado de archivos de backup que hay en la carpeta backup

    // se carga en dir el directorio de backups
    $dir=dir($arrConf["dir_backup"]);
    if (file_exists($arrConf["dir_backup"])) {
        // se obtienen todos los archivos del directorio si son archivos tgz
        while ($archivo = $dir->read()) {
            if ($archivo != "." && $archivo != ".." && eregi('\.(tgz)$',$archivo))
                $nombre_archivos[] = $archivo;
        }
    } else {
        $smarty->assign("mb_message", $arrConf["Folder backup doesn't exist"]);
    }
    // se ordenan por fecha los archivos
    rsort($nombre_archivos);

    // se crea la data que va en cada linea (delete, name backup, date, accion)
    foreach($nombre_archivos as $key=>$nombre_archivo) {
        $arrTmp[0] = "<input type='checkbox' name='chk[".$nombre_archivo."]' id='chk[".$nombre_archivo."]'>";
        $arrTmp[1] = $nombre_archivo;
        $fecha="";
        // se parsea el archivo para obtener la fecha
        if(ereg("[[:alnum:]]-([[:digit:]]{4})([[:digit:]]{2})([[:digit:]]{2})([[:digit:]]{2})([[:digit:]]{2})([[:digit:]]{2})-([[:alnum:]]{2}).[[:alnum:]]", $nombre_archivo, $data)) {
            $fecha = "$data[3]/$data[2]/$data[1] $data[4]:$data[5]:$data[6]";
            $id= "$data[1]$data[2]$data[3]$data[4]$data[5]$data[6]-$data[7]";
        }
        $arrTmp[2] = $fecha;
        //$arrTmp[3] = "<a href='?menu=restore&id=$id'>".$arrLang["Restore"]."</a>";
        $arrTmp[3] = "<input type='submit' name='submit_restore[".$nombre_archivo."]' value='{$arrLang['Restore']}' class='button'>";
        $arrData[] = $arrTmp;
    }
    // se obtiene la cantidad de archivos
    $end = count($arrData);
    // FIN: se obtiene el listado de archivos de backup que hay en la carpeta backup

    // se crean las cabeceras
    $arrGrid = array("title"    => $arrLang["Backup List"],
        "icon"     => "images/list.png",
        "width"    => "99%",
        "start"    => ($end==0) ? 0 : 1,
        "end"      => $end,
        "total"    => $end,
        "columns"  => array(0 => array("name"      => "<input type='submit' name='submit_borrar' value='{$arrLang['Delete']}' class='button'>",
                                       "property1" => "width='5%'"),
                            1 => array("name"      => $arrLang["Name Backup"],
                                       "property1" => "width='80%'"),
                            2 => array("name"      => $arrLang["Date"],
                                       "property1" => "width='5%'"),
                            3 => array("name"      => $arrLang["Action"],
                                       "property1" => "width='5%'"),
                            ));

    $oGrid = new paloSantoGrid($smarty);
	
	//Button Backup
	$button = "<input class=\"button\" type=\"submit\" name=\"backup\" value=\"". $arrLang["Backup"]."\">";
	
    $oGrid->showFilter(
              "<form style='margin-bottom:0;' method='POST' action='?menu=$module_name'>" .$button);
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";
    return $contenidoModulo;
}

function borra_archivos($archivos_borrar, &$msj) {
    global $arrLang;
    global $arrConf;

    $error = false;
    if (is_array($archivos_borrar)) {
        //cambiar de usuario a la carpeta y a los archivos de backup para poder borrarlos
        //no dejo los permisos como estaban porque estos archivos deben pertenecer al usuario
        //asterisk, y si no fuera asi es incorrecto.
        $comando="sudo -u root /bin/chown asterisk:asterisk $arrConf[dir_backup] -R";
        exec($comando,$output,$retval);
        if ($retval==0) {
            // se hace un ciclo para borrar todos los archivos del respaldo checados
            foreach($archivos_borrar as $archivo=>$estatus) {
                $ruta_archivo = $arrConf["dir_backup"]."/".$archivo;
                // se verifica que el archivo exista para evitar errores en el unlink
                if (file_exists($ruta_archivo)) {
                    // unlink borra el archivo
                    if (!unlink($ruta_archivo)) {
                        $error = true;
                        break;
                    }
                }
            }
        } else {
            $error = true;
        }
    }
    if ($error) {
        $msj = $arrLang["Error when deleting backup file"];
    }

    return (!$error);
}



/* ------------------------------------------------------------------------------- */
/* FUNCIONS PARA EL RESTORE*/
/* ------------------------------------------------------------------------------- */
function process_restore($arrSelectedOptions,$ruta_respaldo,$ruta_restaurar,&$arrRestoreOptions)
{
    foreach ($arrSelectedOptions as $option)
    {
        $bExito=true;
        #hago case de option
        switch ($option){
        case "elastix_db":
            restaurar_base_elastix($ruta_restaurar,$ruta_respaldo);
        break;
        case "sounds":
            $error="";
            $arrInfoRestaurar['folder_path']="/var/lib/asterisk";
            $arrInfoRestaurar['folder_name']="sounds";
            $arrInfoRestaurar['nombre_archivo_respaldo']="var.lib.asterisk.sounds.tgz";
            $bExito=restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error);
        break;
        case "config_files":
            $error="";
            $arrInfoRestaurar['folder_path']="/etc";
            $arrInfoRestaurar['folder_name']="asterisk";
            $arrInfoRestaurar['nombre_archivo_respaldo']="etc.asterisk.tgz";
            $bExito=restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error);
        break;
        case "fax":
            #verifico si esta el respaldo de la tabla de fax
            #si no ha seleccionado respaldar base de datos respaldo el archivo

            if (file_exists("$ruta_respaldo/fax.tgz"))
            {
                #busco el respaldo de fax.db: fax.tgz
                #descomprimo el archivo
                $comando="tar -C $ruta_restaurar -xvzf $ruta_respaldo/fax.tgz";
                exec($comando,$output,$retval);
                if ($retval<>0) $bExito=false;
                else{
                #me crea una carpeta fax con un archivo fax.sql
                    $script_fax="$ruta_restaurar/fax/fax.db.sql";
                    #creo un nuevo archivo db con la informacion de respaldo
                    $base_fax_respaldo="$ruta_restaurar/fax_respaldo.db";
                    #pongo el script en el archivo fax.db
                    $comando="cat $script_fax | sqlite3 /var/www/db/fax.db";
                    exec($comando,$output,$retval);

                    $comando="cat $script_fax | sqlite3 $base_fax_respaldo";
                    exec($comando,$output,$retval);
                    if ($retval==0){
                        #consultar en la base para crear en el sistema
                            crear_cuentas_fax($base_fax_respaldo);
                    }
                }
            }
        break;
        case "voicemail":
            $error="";
            $arrInfoRestaurar['folder_path']="/var/spool/asterisk";
            $arrInfoRestaurar['folder_name']="voicemail";
            $arrInfoRestaurar['nombre_archivo_respaldo']="var.spool.asterisk.voicemail.tgz";
            $bExito=restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error);
        break;
        case "monitors":
            $error="";
            $arrInfoRestaurar['folder_path']="/var/spool/asterisk";
            $arrInfoRestaurar['folder_name']="monitor";
            $arrInfoRestaurar['nombre_archivo_respaldo']="var.spool.asterisk.monitor.tgz";
            $bExito=restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error);
        break;
        case "tftp":
            $error="";
            $bExito=false;
            $arrInfoRestaurar['folder_path']="/";
            $arrInfoRestaurar['folder_name']="tftpboot";
            $arrInfoRestaurar['nombre_archivo_respaldo']="tftpboot.tgz";
            #tengo que cambiarle los permisos a la carpeta (con sudo) por que sino no voy a poder hacerle el restore
            $comando="sudo -u root /bin/chown asterisk:asterisk /tftpboot -R";
            exec($comando,$output,$retval);
            if ($retval==0){
                $bExito=restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error);
                #cambio de nuevo a root
                $comando="sudo -u root /bin/chown root:root /tftpboot -R";
                exec($comando,$output,$retval);
            }
        break;
        case "email":
            #verifico si esta el respaldo de la tabla de email
              if (file_exists("$ruta_respaldo/email.tgz"))
            {
                #busco el respaldo de email.db: email.db.sql
                #descomprimo el archivo
                $comando="tar -C $ruta_restaurar -xvzf $ruta_respaldo/email.tgz";
                exec($comando,$output,$retval);
                if ($retval<>0) {
                    $bExito=false;
                }
                else{
                #me crea una carpeta email con un archivo email.db.sql, roundcoubedb.sql, var.spool.imap.tgz

                    $script_email="$ruta_restaurar/email/email.db.sql";
                    #creo un nuevo archivo db con la informacion de respaldo
                    $base_email_respaldo="$ruta_restaurar/email_respaldo.db";
                    #pongo el script en el archivo email.db
                    $comando="cat $script_email | sqlite3 /var/www/db/email.db";
                    exec($comando,$output,$retval); 

                    if ($retval==0){
                            #consultar en la base para crear en el sistema
                            $bExito=crear_cuentas_email("/var/www/db/email.db");
                    }
                    #restaurar los  mailboxes ruta /var/spool/imap
                    #primero cambiar los permisos a la carpeta
                    $comando="sudo -u root /bin/chown asterisk:asterisk /var/spool/imap -R";
                    exec($comando,$output,$retval);
                    if ($retval==0){
                        $arrInfoRestaurar['folder_path']="/var/spool";
                        $arrInfoRestaurar['folder_name']="imap";
                        $arrInfoRestaurar['nombre_archivo_respaldo']="var.spool.imap.tgz";
                        $bExito=restaurar_carpeta($arrInfoRestaurar,"$ruta_restaurar/email",$error);
    
                        #cambio de nuevo a cyrus
                        $comando="sudo -u root /bin/chown cyrus:mail /var/spool/imap -R";
                        exec($comando,$output,$retval);
                    }
                    #restaurar lo del webmail
                    $comando="mysql --password=".MYSQL_ROOT_PASSWORD." --user=root roundcubedb < $ruta_restaurar/email/roundcubedb.sql";
                    exec($comando,$output,$retval);

                }
            }
        break;


        }
        if ($bExito) $msge="[ OK ]";
        else $msge="[ FAILED ]";
        $arrRestoreOptions[$option]["msg"]=$msge;
    }
}

function restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,&$error)
{
    $bExito=true;

    $comando="tar -C ".$arrInfoRestaurar['folder_path'] .
             " -xvzf $ruta_respaldo/$arrInfoRestaurar[nombre_archivo_respaldo]";

    exec($comando,$output,$retval);
    if ($retval<>0) $bExito=false;

    return $bExito;
}

function crear_cuentas_fax($ruta_base_fax_respaldo)
{
    $result=array();
    $pDB = new paloDB("sqlite3:///$ruta_base_fax_respaldo");
    if (!empty($pDB->errMsg)) {
        echo "DB ERROR: $pDB->errMsg \n";
    }else
    {
            #borrar las cuentas de fax de /var/www/db
            $pDBorig = new paloDB("sqlite3:////var/www/db/fax.db");
            if (!empty($pDBorig->errMsg)) {
                echo "DB ERROR: $pDBorig->errMsg \n";
            }else{
                #TODO:
                #antes de borrar de la base de datos deberia seleccionar cada una e ir borrando del equipo
                $query1="DELETE FROM fax";
                $pDBorig->genQuery($query1);

                $query="SELECT * FROM fax";
                $result=$pDB->fetchTable($query,true);

            }
    }

    if (is_array($result)){
        foreach ($result as $arrFax)
        {
            $oFax = new paloFax();
            $oFax->createFaxExtension($arrFax['name'], $arrFax['extension'], $arrFax['secret'], $arrFax['email'],$arrFax['clid_name'], $arrFax['clid_number']);
    
        }
    }


}


function crear_cuentas_email($ruta_base_email)
{
    $bExito=true;
    $result=array();
    $pDB = new paloDB("sqlite3:///$ruta_base_email");
    if (!empty($pDB->errMsg)) {
        echo "DB ERROR: $pDB->errMsg \n";
    }else
    {
        #crear los dominios
        #seleccionar los dominios
        $sQuery="SELECT * from domain";
        $result=$pDB->fetchTable($sQuery,true);
        foreach ($result as $infoDominio)
        {
            guardar_dominio_sistema($infoDominio['domain_name'],$errMsg);
        }
        #crear las cuentas
        $sQuery="SELECT a.*,d.domain_name from accountuser as a,domain as d where d.id=a.id_domain";
        $result=$pDB->fetchTable($sQuery,true);
        if (is_array($result)){
            foreach ($result as $infoCuenta)
            {
                $username=$infoCuenta['username'];
                $quota=$infoCuenta['quota'];
                #armo el email
                if (ereg("(.*)\.($infoCuenta[domain_name])",$username,$regs))
                {
                    $email=$regs[1].'@'.$infoCuenta['domain_name'];
                    $password=$infoCuenta['password'];
                    $bExito=crear_usuario_correo_sistema($email,$username,$password,$errMsg);
                    if ($bExito){
                        //crear el mailbox para la nueva cuenta
                        $bReturn=crear_mailbox_usuario($email,$username,$quota,$errMsg);
                        #reemplazo el mailbox
                    }else{
                        //tengo que borrar el usuario creado en el sistema
                        $bReturn=eliminar_usuario_correo_sistema($username,$email,$errMsg);
    
                    }
    
                }
            }
        }
    }

    return $bExito;

}

function crear_mailbox_usuario($email,$username,$quota,&$error_msg){
    global $CYRUS;
    global $arrLang;
    $cyr_conn = new cyradm;
    $error=$cyr_conn->imap_login();
    if ($error===FALSE){
        $error_msg.="IMAP login error: $error <br>";
    }
    else{
        $seperator	= '/';
        $bValido=$cyr_conn->createmb("user" . $seperator . $username);
        if(!$bValido)
            $error_msg.="Error creating user:".$cyr_conn->getMessage()."<br>";
        else{
            $bValido=$cyr_conn->setacl("user" . $seperator . $username, $CYRUS['ADMIN'], "lrswipcda");
            if(!$bValido)
                $error_msg.="error:".$cyr_conn->getMessage()."<br>";
            else{
                $bValido = $cyr_conn->setmbquota("user" . $seperator . $username, $quota);
                if(!$bValido)
                    $error_msg.="error".$cyr_conn->getMessage()."<br>";
            }
        }

    }

    return TRUE;         
}


function restaurar_base_elastix($ruta_restaurar,$ruta_respaldo)
{
    $arrDatabasesMySQL=array(
                    "asterisk",
                    "asteriskcdrdb",
                    "asteriskrealtime",
                    "endpoints",
                    "mya2billing",
                    "mysql",
                    "roundcubedb",
                    "sugarcrm",
                    "vtigercrm503",
            );

    $error="";
///-----------------  MYSQL
    $ruta_respaldo_db = $ruta_restaurar."/mysqldb";
        
    if (file_exists("$ruta_respaldo/mysqldb.tgz")) {
        $comando="tar -C $ruta_restaurar -xvzf $ruta_respaldo/mysqldb.tgz";
        exec($comando,$output,$retval);
        if ($retval==0){
            $directorio=dir($ruta_respaldo_db);
            $arrArchivos = array();
            while ($archivo = $directorio->read()) {
                if ($archivo!="." && $archivo!=".." && ereg("(.*)\.sql$",$archivo,$regs)) {
                    if (in_array($regs[1],$arrDatabasesMySQL))
                        $arrArchivos[$regs[1]] = $archivo;
                }
            }
        
            foreach ($arrArchivos as $base => $fileSQL){
                $comando="mysql --password=".MYSQL_ROOT_PASSWORD." --user=root $base < $ruta_respaldo_db/$fileSQL";
                exec($comando,$output,$retval);
        
            }
        }
    } 

///-----------------  SQLITE

    $ruta_base_sqlite = "/var/www/db";
    $ruta_respaldo_db = $ruta_restaurar."/db";

    if (file_exists("$ruta_respaldo/var.www.db.tgz")) {
        $comando="tar -C $ruta_restaurar -xvzf $ruta_respaldo/var.www.db.tgz";
        exec($comando,$output,$retval);
        if ($retval==0){
            $directorio=dir($ruta_respaldo_db);
            $arrArchivos = array();
            while ($archivo = $directorio->read()) {
                if ($archivo!="." && $archivo!=".." && ereg("(.*)\.sql$",$archivo,$regs)) {
                    $arrArchivos[$regs[1]] = $archivo;
                }
            }
        
            foreach ($arrArchivos as $base => $fileSQL){
                $comando="cat $ruta_respaldo_db/$fileSQL | sqlite3 $ruta_base_sqlite/$base";
                exec($comando,$output,$retval);
            }
        }

    } 

///-----------------

}


/* ------------------------------------------------------------------------------- */
/* FUNCIONS PARA EL BACKUP*/
/* ------------------------------------------------------------------------------- */

function process_backup($arrSelectedOptions,$ruta_respaldo,&$arrBackupOptions)
{
    foreach ($arrSelectedOptions as $option)
    {
        $bExito=true;
        #hago case de option
        switch ($option){
        case "elastix_db":
            #voy a hacer mysqldump de cada una de las bases de datos
            respaldar_mysql_db($ruta_respaldo);
            $error="";
            $arrInfoRespaldo['folder_path']=$ruta_respaldo;
            $arrInfoRespaldo['folder_name']="mysqldb";
            $arrInfoRespaldo['nombre_archivo_respaldo']="mysqldb.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
            #voy a borrar la carpeta de respaldo mysqldb
            exec("rm $ruta_respaldo/mysqldb -rf");
            #para las bases de sqlite genero el schema y los insert
            #respaldar bases sqlite
            respaldar_sqlite_db($ruta_respaldo);
            $error="";
            $arrInfoRespaldo['folder_path']=$ruta_respaldo;
            $arrInfoRespaldo['folder_name']="db";
            $arrInfoRespaldo['nombre_archivo_respaldo']="var.www.db.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
            #voy a borrar la carpeta de respaldo db
            exec("rm $ruta_respaldo/db -rf");
        break;
        case "sounds":
            $error="";
            $arrInfoRespaldo['folder_path']="/var/lib/asterisk";
            $arrInfoRespaldo['folder_name']="sounds";
            $arrInfoRespaldo['nombre_archivo_respaldo']="var.lib.asterisk.sounds.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
        break;
        case "config_files":
            $error="";
            $arrInfoRespaldo['folder_path']="/etc";
            $arrInfoRespaldo['folder_name']="asterisk";
            $arrInfoRespaldo['nombre_archivo_respaldo']="etc.asterisk.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
        break;
        case "fax":
            #por ahora se respaldara la base de datos, fax.db
            #si no ha seleccionado respaldar base de datos respaldo el archivo
            $dir_resp_fax="$ruta_respaldo/fax";
            mkdir($dir_resp_fax);

                #copio el archivo fax.db
            respaldar_base_sqlite($dir_resp_fax,"/var/www/db","fax.db");
            $arrInfoRespaldo['folder_path']="$ruta_respaldo";
            $arrInfoRespaldo['folder_name']="fax";
            $arrInfoRespaldo['nombre_archivo_respaldo']="fax.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
            exec("rm $dir_resp_fax -rf");

        break;
        case "voicemail":
            $error="";
            $arrInfoRespaldo['folder_path']="/var/spool/asterisk";
            $arrInfoRespaldo['folder_name']="voicemail";
            $arrInfoRespaldo['nombre_archivo_respaldo']="var.spool.asterisk.voicemail.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
        break;
        case "monitors":
            $error="";
            $arrInfoRespaldo['folder_path']="/var/spool/asterisk";
            $arrInfoRespaldo['folder_name']="monitor";
            $arrInfoRespaldo['nombre_archivo_respaldo']="var.spool.asterisk.monitor.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
        break;
        case "tftp":
            $error="";
            $bExito=false;
            $arrInfoRespaldo['folder_path']="/";
            $arrInfoRespaldo['folder_name']="tftpboot";
            $arrInfoRespaldo['nombre_archivo_respaldo']="tftpboot.tgz";
            #tengo que cambiarle los permisos a la carpeta (con sudo) por que sino no voy a poder hacerle backup
            $comando="sudo -u root /bin/chown asterisk:asterisk /tftpboot -R";
            exec($comando,$output,$retval);
            if ($retval==0){
                $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
                #cambio de nuevo a root
                $comando="sudo -u root /bin/chown root:root /tftpboot -R";
                exec($comando,$output,$retval);
            }
        break;
        case "email":
            #por ahora se respaldara la base de datos, email.db
            #si no ha seleccionado respaldar base de datos respaldo el archivo sqlite email.db y la base de mysql de roundcube (webmail)
            $dir_resp_email="$ruta_respaldo/email";
            mkdir($dir_resp_email);
            if (!in_array("elastix_db",$arrSelectedOptions))
            {
                //voy a crear una carpeta email para copiar los archivos a respaldar
                respaldar_base_sqlite($dir_resp_email,"/var/www/db","email.db");
                /*$comando="tar -C /var/www/db -cvzf $ruta_respaldo/email.tgz email.db ";
                exec($comando,$output,$retval);
                if ($retval<>0) $bExito=false;*/
                #hago dump de la base de mysql
                respaldar_base_mysql($dir_resp_email,"roundcubedb");
            }
            #respaldar los  mailboxes ruta /var/spool/imap
            #primero cambiar los permisos a la carpeta
            $comando="sudo -u root /bin/chown asterisk:asterisk /var/spool/imap -R";
            exec($comando,$output,$retval);
            if ($retval==0){
            $arrInfoRespaldo['folder_path']="/var/spool";
            $arrInfoRespaldo['folder_name']="imap";
            $arrInfoRespaldo['nombre_archivo_respaldo']="var.spool.imap.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,"$ruta_respaldo/email",$error);
            #cambio de nuevo a cyrus
            $comando="sudo -u root /bin/chown cyrus:mail /var/spool/imap -R";
            exec($comando,$output,$retval);
            }


            $arrInfoRespaldo['folder_path']="$ruta_respaldo";
            $arrInfoRespaldo['folder_name']="email";
            $arrInfoRespaldo['nombre_archivo_respaldo']="email.tgz";
            $bExito=respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error);
            exec("rm $dir_resp_email -rf");
        break;


        }
        if ($bExito) $msge="[ OK ]";
        else $msge="[ FAILED ]";
        $arrBackupOptions[$option]["msg"]=$msge;
    }
}

function respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,&$error)
{
    $bExito=true;
    $comando="tar -C ".$arrInfoRespaldo['folder_path'] .
             " -cvzf $ruta_respaldo/$arrInfoRespaldo[nombre_archivo_respaldo] ".
             $arrInfoRespaldo['folder_name'];
    exec($comando,$output,$retval);
    if ($retval<>0) $bExito=false;

    return $bExito;
}



function respaldar_sqlite_db($ruta_respaldo){
    $bExito=true;
    $arrArchivos = array();
    $ruta_db="/var/www/db";
   // $noBackTables=array('acl_group','acl_action','acl_group_permission',
    //                    'acl_membership','acl_resource','acl_user_permission');
    $noBackTables=array();

    //voy a crear una carpeta db
    mkdir("$ruta_respaldo/db");
    $dir_resp_db="$ruta_respaldo/db";
    //obtener listado de todas los archivos de ese directorio .db
    if (file_exists($ruta_db)){
        //existe el archivo, así es que leo el contenido
        $directorio=dir($ruta_db);
        while ($archivo = $directorio->read()) {
            if ($archivo!="." && $archivo!=".." && ereg("(.*)\.db$",$archivo)) $arrArchivos[] = $archivo;
        }
    }
    foreach ($arrArchivos as $archivoDB)
    {
        //por cada archivo db, voy a crear un archivo de texto que va a tener el script de la base, creación de tablas e inserts de datos
        respaldar_base_sqlite($dir_resp_db,$ruta_db,$archivoDB);
    }

    return $bExito;
}

function armar_queries_insert($pDB, $table_name, $schema)
{
//con el esquema obtener los campos para formar el query
    $descTabla=array();
    $campos = array();
    $arrQuerys=array();

    if (eregi("CREATE TABLE $table_name([[:space:]]\n*)\((.*)\)",$schema,$regs))
    {
        $arrCampos = split(",",$regs[2]);
        foreach ($arrCampos as $campo)
        {
            $descCampos = split(" ",trim($campo));
            //omitir el campo num_digits
            if (trim($descCampos[0])!="num_digits"){
                $arrCampo['Field']=trim($descCampos[0]);
                $campos[]=trim($descCampos[0]);
                $arrCampo['Type']=trim($descCampos[1]);
                $descTabla[]=$arrCampo;
            }
        }
    //    print_r($descTabla);
    }
    if (count($campos)>0){
        $strCampos=implode(",",$campos);
        $queryInsert = "INSERT INTO $table_name ($strCampos) VALUES ";
        $values='';
        //obtener los registros
        $sQuery="SELECT $strCampos FROM $table_name ";
        $result=$pDB->fetchTable($sQuery);
        if (is_array($result) && count($result)>0){
            foreach ($result as $columnas){
                //escapar los caracteres en los valores de la columna
                $columnValues='';
                foreach ($columnas as $columna){
                    $valor_columna=paloDB::DBCAMPO($columna);
                    $columnValues.=empty($columnValues)?$valor_columna:",$valor_columna";

                }
                $values="($columnValues)";
            //agrego el query a la lista
                $arrQuerys[]="$queryInsert $values";
            }
        }
      //  print_r($arrQuerys);
    }
    return $arrQuerys;
}

function respaldar_mysql_db($ruta_respaldo)
{
    //voy a crear una carpeta mysqldb
    mkdir("$ruta_respaldo/mysqldb");
    $dir_resp_db="$ruta_respaldo/mysqldb";

    $arrDatabasesMySQL=array(
                    "asterisk",
                    "asteriskcdrdb",
                    "asteriskrealtime",
                    "endpoints",
                    "mya2billing",
                    "mysql",
                    "roundcubedb",
                    "sugarcrm",
                    "vtigercrm503",
            );

            #TODO: asegurar si se va a exportar la estructura tambien
    foreach ($arrDatabasesMySQL as $base){
        #hago mysqldump a cada base de datos
        # -t :no-create-info
        #voy a omitir el esquema de las tablas
        $retorno=respaldar_base_mysql($dir_resp_db,$base);
        
        if ($retorno==0){#todo bien
        }
    }
}


function respaldar_base_mysql($dir_resp_db,$base)
{
    $respaldo ="";
    $bContinuar = FALSE;
    $host="localhost";
    $user="root";
    $pass=MYSQL_ROOT_PASSWORD;
    $dsn     = "mysql://$user:$pass@$host/$base";
    $db=new paloDB($dsn);
#mysqldump solo para la estructura
    system("mysqldump -h $host -u $user -p$pass  $base -t -c  > $dir_resp_db/{$base}2.sql",$retorno);


    if ($retorno==0) $bContinuar = TRUE;
    
    if ($bContinuar){
        $sQuery="SHOW TABLES";
        $tablas=$db->fetchTable($sQuery);

        $num_tables=count($tablas);
        $i=0;
        $error="";
        while ($i < $num_tables) {
            $table = $tablas[$i][0];
            $respaldo.= "--\n-- Delete Rows for Table $table\n--\nDELETE FROM `$table`;\n\n";
            $i++;
        }
        if (!empty($error)){   
            $bContinuar = FALSE;
            //$sContenido.=$tpl->crearAlerta("error","Error",$error);
        } else {
            $bContinuar=TRUE;            
        }     
    }

    if ($bContinuar){
        system("mysqldump -h $host -u $user -p$pass  $base --skip-add-drop-table --no-data  > $dir_resp_db/{$base}.sql",$retorno);
        if ($retorno==0){

            $estructura="";
            //no hubo inconvenientes, se guardo la estructura
            //se carga el contenido del archivo            
            $estructura=file_get_contents("$dir_resp_db/{$base}.sql");            
            $estructura=str_replace("CREATE TABLE","CREATE TABLE IF NOT EXISTS",$estructura);    
            
            //borrar el archivo

            if (strlen(trim($estructura))>0){
                $respaldo=$estructura.$respaldo;
                $bContinuar=TRUE;
            }
            else{
                //si no hay estructura no se puede continuar con los datos
                $bContinuar=FALSE;
            }

        }else{
            $bContinuar = FALSE;
        }
    }

    if ($bContinuar){
       // file_put_contents("$dir_resp_db/{$base}.sql", $respaldo, FILE_APPEND);
        $open = fopen ("$dir_resp_db/{$base}2.sql","a+");
        $openSQL = fopen ("$dir_resp_db/{$base}.sql","w+");
        rewind($open);rewind($openSQL);
        $tamanio_linea=4096;
        $escribir = fwrite ($openSQL,$respaldo."\n");
        while ($linea = fgets($open,$tamanio_linea))  // [0]
        {        
	       $escribir = fwrite ($openSQL,$linea);
        }
        fclose($open);        fclose($openSQL);
        unlink("$dir_resp_db/{$base}2.sql");
    }

    return $bContinuar?0:($retorno>0?1:$retorno);
}

function respaldar_base_sqlite($dir_resp_db,$ruta_db,$archivoDB,$noBackTables=array())
{
    //abrir conexion paloDB
    $pDB = new paloDB("sqlite3:///$ruta_db/".trim($archivoDB));
    if (!empty($pDB->errMsg)) {
        echo "DB ERROR: $pDB->errMsg \n";
    }else
    {
        //obtener el esquema de la base para obtener las tablas
        $sQuery="SELECT name, sql FROM sqlite_master ".
                "WHERE type='table' ".
                "ORDER BY name";
        $result=$pDB->fetchTable($sQuery);
        if (is_array($result) && count($result)>0){
            $pathSQLdb="$dir_resp_db/$archivoDB.sql";
            if (file_exists($pathSQLdb)) unlink($pathSQLdb);
            $archivos_db[]=$pathSQLdb;
            foreach ($result as $tableDesc){
                $table_name=trim($tableDesc[0]);
                $table_schema=trim($tableDesc[1]);
            // print "$table_name - $table_schema\n";
                //obtener los datos de esa tabla
                //para la base acl solo obtengo la tabla acl_user
                if (!in_array($table_name,$noBackTables)){
                    //escribir DELETE de la tabla
                    $queryDelete="DELETE FROM $table_name;\n";
                    $arrQueryInsert=armar_queries_insert($pDB, $table_name, $table_schema);
                }
                //ya tengo el listado de querys 
                //guardo en el archivo .sql
                //solo necesito los datos porque las tablas ya estan creadas en la nueva version
                if (count($arrQueryInsert>0)){
                    $strQuerys=implode(";\n",$arrQueryInsert);
                    $strQuerys.=(empty($strQuerys))?'':";\n";
                    //REEMPLAZAR CREATE TABLE POR CREATE TABLE IF NOT EXISTS
                    $table_schema=str_replace("CREATE TABLE","CREATE TABLE IF NOT EXISTS",$table_schema);
                    $qTable="$table_schema;\n";
                    $qTable.=$queryDelete;
                    $qTable.=$strQuerys;
                    if (!$handle = fopen($pathSQLdb, 'a')) {
                        echo "Cannot open file ($pathSQLdb)";
                        return false;
                    }
                    if (fwrite($handle, $qTable) === FALSE) {
                        echo "Cannot write to file ($pathSQLdb)";
                        return false;
                    }
                    fclose($handle);
                }
            }
        }

    }
}

?>
