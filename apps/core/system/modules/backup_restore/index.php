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
  $Id: index.php,v 1.1 2008/01/30 15:55:57 afigueroa Exp $ */

include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoFax.class.php";
include_once "libs/paloSantoEmail.class.php";

include_once "libs/cyradm.php";
include_once "configs/email.conf.php";

function _moduleContent(&$smarty, $module_name)
{
//include elastix framework
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "modules/$module_name/libs/paloSantoFTPBackup.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    //include_once "modules/$module_name/libs/paloSantoConference.php";

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

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $dir_backup = $arrConf["dir"];

    $accion = getAction();
    $content = "";
    switch($accion)
    {
        case 'delete_backup': //BOTON DE BORRAR BACKUP "ELIMINAR"
            $content = delete_backup($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, $pDB);
            break;
        case 'backup': //BOTON "RESPALDAR"
            $content = backup_form($smarty, $local_templates_dir, $arrLang, $module_name);
            break;
        case 'submit_restore': //BOTON DE RSTAURAR, lleva a la ventana de seleccion para restaurar
            $content = restore_form($smarty, $local_templates_dir, $arrLang, $dir_backup, $module_name);
            break;
        case 'process_backup':
            $content = process_backup($smarty, $local_templates_dir, $arrLang, $module_name);
            break;
        case 'process_restore':
            $content = process_restore($smarty, $local_templates_dir, $arrLang, $dir_backup, $module_name);
            break;
        case 'download_file':
            $content = downloadBackup($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup);
            break;

/******************************* PARA FTP BACKUP ***************************************/
        case "save_new_FTP":
            $content = saveNewFTPBackup($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case "view_form_FTP":
            $content = viewFormFTPBackup($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case 'uploadFTPServer':
            $content = file_upload_FTPServer($module_name, $arrLang, $arrConf, $pDB);
            break;
        case 'downloadFTPServer':
            $content = file_download_FTPServer($module_name, $arrLang, $arrConf, $pDB);
            break;
/***************************************************************************************/
          case "detail":
            $content = viewDetail($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup);
            break;
/******************************* PARA BACKUP AUTOMATICO ********************************/
        case "automatic":
            $content = automatic_backup($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup,$pDB);
            break;
/***************************************************************************************/
        default:
            $content = report_backup_restore($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, $pDB);
            break;
    }

    return $content;
}

function report_backup_restore($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, &$pDB)
{
    $nombre_archivos = array();
    $num_backups = Obtener_Total_Backups($dir_backup);
    $pFTPBackup = new paloSantoFTPBackup($pDB);
    //Paginacion
    $limit  = 10;
    $total  = $num_backups;

// obtencion de parametros desde la base
        $_DATA = $pFTPBackup->getStatusAutomaticBackupById(1);
        if(!(is_array($_DATA) & count($_DATA)>0)){
            $_DATA['status'] = "DISABLED";
        }

    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $nombre_archivos = Obtener_Backups($dir_backup, $total-$offset, $limit);

    //Fin Paginacion

    $arrData = null;
    if(is_array($nombre_archivos) && $total>0){
        foreach($nombre_archivos as $key => $nombre_archivo){
            $arrTmp[0] = "<input type='checkbox' name='chk[".$nombre_archivo."]' id='chk[".$nombre_archivo."]'>";
            $arrTmp[1] = "<a href='?menu=$module_name&action=download_file&file_name=$nombre_archivo&rawmode=yes'>$nombre_archivo</a>";
            $fecha="";
            // se parsea el archivo para obtener la fecha
            if(preg_match("/\w*-\d{4}\d{2}\d{2}\d{2}\d{2}\d{2}-\w{2}\.\w*/",$nombre_archivo)){ //elastixbackup-20110720122759-p7.tar
                $arrMatchFile = preg_split("/-/",$nombre_archivo);
                $data  = $arrMatchFile[1];
                $fecha = substr($data,-8,2)."/".substr($data,-10,2)."/".substr($data,0,4)." ".substr($data,-6,2).":".substr($data,-4,2 ).":".substr($data,-2,2);
                $id    = $arrMatchFile[1]."-".$arrMatchFile[2];
            }

            $arrTmp[2] = $fecha;
            $arrTmp[3] = "<input type='submit' name='submit_restore[".$nombre_archivo."]' value='{$arrLang['Restore']}' class='button'>";
            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array("title"    => $arrLang["Backup List"],
                     "url"      => array('menu' => $module_name),
                     "icon"     => "/modules/$module_name/images/system_backup_restore.png",
                     "width"    => "99%",
                     "start"    => ($total==0) ? 0 : $offset + 1,
                     "end"      => $end,
                     "total"    => $total,
                     "columns"  => array(0 => array("name"      => "",
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Name Backup"],
                                                    "property1" => ""),
                                         2 => array("name"      => $arrLang["Date"],
                                                    "property1" => ""),
                                         3 => array("name"      => $arrLang["Action"],
                                                    "property1" => ""),
                                    )
                    );
    $time = $_DATA['status'];

    $smarty->assign("FILE_UPLOAD", $arrLang["File Upload"]);
    $smarty->assign("AUTOMATIC", $arrLang["AUTOMATIC"]);
   // $smarty->assign("BACKUP", $arrLang["Backup"]);
    $smarty->assign("UPLOAD", $arrLang["Upload"]);
    $smarty->assign("FTP_BACKUP", $arrLang["FTP Backup"]);
    $oGrid->addNew("backup",_tr("Backup"));
    $oGrid->deleteList(_tr("Are you sure you wish to delete backup (s)?"),'delete_backup',_tr("Delete"));
    $oGrid->customAction("view_form_FTP",_tr("FTP Backup"));

    $backupIntervals = array(
        'DISABLED'  =>  _tr('DISABLED'),
        'DAILY'     =>  _tr('DAILY'),
        'MONTHLY'   =>  _tr('MONTHLY'),
        'WEEKLY'    =>  _tr('WEEKLY'),
    );

    $oGrid->addComboAction("time",_tr("AUTOMATIC"),$backupIntervals,$time,'automatic');
    

    //$htmlFilter = $smarty->fetch("$local_templates_dir/filter.tpl");
    //$oGrid->showFilter(trim($htmlFilter),true);
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);

    return $contenidoModulo;
}

function automatic_backup($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, $pDB)
{
    $nombre_archivos = array();
    $num_backups = Obtener_Total_Backups($dir_backup);
    $pFTPBackup = new paloSantoFTPBackup($pDB);
    //Paginacion
    $limit  = 5;
    $total  = $num_backups;

    $time = getParameter("time");

    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $nombre_archivos = Obtener_Backups($dir_backup, $total-$offset, $limit);

    //Fin Paginacion

    $arrData = null;
    if(is_array($nombre_archivos) && $total>0){
        foreach($nombre_archivos as $key => $nombre_archivo){
            $arrTmp[0] = "<input type='checkbox' name='chk[".$nombre_archivo."]' id='chk[".$nombre_archivo."]'>";
            $arrTmp[1] = "<a href='?menu=$module_name&action=download_file&file_name=$nombre_archivo'>$nombre_archivo</a>";
            $fecha="";
            // se parsea el archivo para obtener la fecha
            if(preg_match("/\w*-\d{4}\d{2}\d{2}\d{2}\d{2}\d{2}-\w{2}\.\w*/",$nombre_archivo)){ //elastixbackup-20110720122759-p7.tar
                $arrMatchFile = preg_split("/-/",$nombre_archivo);
                $data  = $arrMatchFile[1];
                $fecha = substr($data,-8,2)."/".substr($data,-10,2)."/".substr($data,0,4)." ".substr($data,-6,2).":".substr($data,-4,2 ).":".substr($data,-2,2);
                $id    = $arrMatchFile[1]."-".$arrMatchFile[2];
            }
            $arrTmp[2] = $fecha;
            $arrTmp[3] = "<input type='submit' name='submit_restore[".$nombre_archivo."]' value='{$arrLang['Restore']}' class='button'>";
            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array("title"    => $arrLang["Backup List"],
                     "url"      => array('menu' => $module_name),
                     "icon"     => "/modules/$module_name/images/system_backup_restore.png",
                     "width"    => "99%",
                     "start"    => ($total==0) ? 0 : $offset + 1,
                     "end"      => $end,
                     "total"    => $total,
                     "columns"  => array(0 => array("name"      => "",
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Name Backup"],
                                                    "property1" => ""),
                                         2 => array("name"      => $arrLang["Date"],
                                                    "property1" => ""),
                                         3 => array("name"      => $arrLang["Action"],
                                                    "property1" => ""),
                                    )
                    );


    $smarty->assign("FILE_UPLOAD", $arrLang["File Upload"]);
    $smarty->assign("AUTOMATIC", $arrLang["AUTOMATIC"]);

   // $smarty->assign("BACKUP", $arrLang["Backup"]);
    $smarty->assign("UPLOAD", $arrLang["Upload"]);
    $smarty->assign("FTP_BACKUP", $arrLang["FTP Backup"]);
    $oGrid->addNew("backup",_tr("Backup"));
    $oGrid->deleteList(_tr("Are you sure you wish to delete backup (s)?"),'delete_backup',_tr("Delete"));
    $oGrid->customAction("view_form_FTP",_tr("FTP Backup"));
    $backupIntervals = array(
        'DISABLED'  =>  _tr('DISABLED'),
        'DAILY'     =>  _tr('DAILY'),
        'MONTHLY'   =>  _tr('MONTHLY'),
        'WEEKLY'    =>  _tr('WEEKLY'),
    );

    $oGrid->addComboAction("time",_tr("AUTOMATIC"),$backupIntervals,$time,'automatic');

    //if there is data in database
    $result = $pFTPBackup->getStatusAutomaticBackupById();
    if(isset($result) && $result != "")
        $pFTPBackup->updateStatus($time);
    else
        $pFTPBackup->insertStatus($time);
    $smarty->assign("mb_message", $arrLang["SUCCESSFUL"]);
    $pFTPBackup->createCronFile($time);
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);

    return $contenidoModulo;
}


function downloadBackup($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, &$pDB)
{
    $bArchivoValido = TRUE;

    $file_name = getParameter("file_name");
    if (basename($file_name) != $file_name) {
        $bArchivoValido = FALSE;
    } elseif (!preg_match('/^elastixbackup-\d{14}-\w{2}\.tar$/', $file_name)) {
        $bArchivoValido = FALSE;
    }

    if ($bArchivoValido) {
        if (file_exists("$dir_backup/$file_name")) {
            header("Cache-Control: private");
            header("Pragma: cache");
            header('Content-Type: application/octet-stream');
            header("Content-Length: ".filesize("$dir_backup/$file_name"));
            header("Content-Disposition: attachment; filename=$file_name");

            readfile("$dir_backup/$file_name");
        } else {
            header("HTTP/1.1 404 Not Found");
            print "File not found";
        }
    } else {
        header("HTTP/1.1 403 Forbidden");
        print "Invalid file";
    }
}


function Obtener_Total_Backups($dir_backup)
{
    $comando="ls $dir_backup/*.tar | grep -c .";
    exec($comando,$output,$retval);
    if ($retval!=0) return 0;
    return $output[0];
}

function Obtener_Backups($dir_backup, $offset_inv, $limit)
{
    $comando="ls $dir_backup/*.tar -t | tail -n $offset_inv | head -n $limit | xargs -n 1 basename";
    exec($comando,$output,$retval);
    if ($retval!=0) return array();
    return $output;
}

function delete_backup($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, &$pDB)
{
    // se obtiene en un arreglo el listado de archivos a borrar
    $archivos_borrar = isset($_POST["chk"])?$_POST["chk"]:array();
    if (count($archivos_borrar)>0){
        $error = false;
        if (is_array($archivos_borrar)) {
            //cambiar de usuario a la carpeta y a los archivos de backup para poder borrarlos
            //no dejo los permisos como estaban porque estos archivos deben pertenecer al usuario
            //asterisk, y si no fuera asi es incorrecto.
            $comando="sudo -u root /bin/chown asterisk:asterisk $dir_backup -R";
            exec($comando,$output,$retval);
            if ($retval==0) {
                // se hace un ciclo para borrar todos los archivos del respaldo checados
                foreach($archivos_borrar as $archivo=>$estatus) {
                    $ruta_archivo = $dir_backup."/".$archivo;
                    // se verifica que el archivo exista para evitar errores en el unlink
                    if (file_exists($ruta_archivo)) {
                        // unlink borra el archivo
                        if (!unlink($ruta_archivo)) {
                            $error = true;
                            break;
                        }
                    }
                }
            }else{
                $error = true;
            }
        }

        if ($error) {
            $smarty->assign("mb_message", $arrLang["Error when deleting backup file"]);
        }
    } else {
        $smarty->assign("mb_message", $arrLang["There are not backup file selected"]);
    }
    return report_backup_restore($smarty, $module_name, $local_templates_dir, $arrLang, $dir_backup, $pDB);
}

function form_general($smarty, $local_templates_dir, $arrLang, $arrBackupOptions, $module_name)
{
    $smarty->assign("PROCESS",$arrLang["Process"]);
    $smarty->assign("LBL_TODOS", $arrLang["Select All options"]);
    $smarty->assign("TODO_FAX", $arrLang["Select all in this section"]);
    $smarty->assign("TODO_EMAIL", $arrLang["Select all in this section"]);
    $smarty->assign("TODO_ENDPOINT", $arrLang["Select all in this section"]);
    $smarty->assign("TODO_ASTERISK", $arrLang["Select all in this section"]);
    $smarty->assign("TODO_OTROS", $arrLang["Select all in this section"]);
    $smarty->assign("TODO_OTROS_NEW", $arrLang["Select all in this section"]);
    $smarty->assign("BACK", $arrLang["Cancel"]);
    $smarty->assign("WARNING", $arrLang["This process could take several minutes"]);

    /*****************/
    $smarty->assign("FAX", $arrLang["Fax"]);
    $smarty->assign("EMAIL", $arrLang["Email"]);
    $smarty->assign("ENDPOINT", $arrLang["Endpoint"]);
    $smarty->assign("ASTERISK", $arrLang["Asterisk"]);
    $smarty->assign("OTROS", $arrLang["Others"]);
    $smarty->assign("OTROS_NEW", $arrLang["Others"]);
    /*****************/

    $smarty->assign("backup_fax", $arrBackupOptions['fax']);
    $smarty->assign("backup_email", $arrBackupOptions['email']);
    $smarty->assign("backup_endpoint", $arrBackupOptions['endpoint']);
    $smarty->assign("backup_asterisk", $arrBackupOptions['asterisk']);
    $smarty->assign("backup_otros", $arrBackupOptions['otros']);
    $smarty->assign("backup_otros_new", $arrBackupOptions['otros_new']);

    $smarty->assign("module", $module_name);
    return $smarty->fetch("$local_templates_dir/backup.tpl");
}

function backup_form($smarty, $local_templates_dir, $arrLang, $module_name)
{
    $arrBackupOptions = Array_Options($arrLang, "");

    $smarty->assign("title", $arrLang["Backup"]);
    $smarty->assign("OPTION_URL", "backup");

    return form_general($smarty, $local_templates_dir, $arrLang, $arrBackupOptions, $module_name);
}

function restore_form($smarty, $local_templates_dir, $arrLang, $path_backup, $module_name)
{
    $arrBackupOptions = Array_Options($arrLang, "disabled='disabled'");
    if(isset($_POST["submit_restore"]))
    {
        $arr = array_keys($_POST["submit_restore"]);
        $archivo_post = $arr[0];
    }else $archivo_post = isset($_POST["backup_file"])?$_POST["backup_file"]:"";

    $dir_respaldo = "$path_backup";
    $comando="cd $dir_respaldo; tar xvf $dir_respaldo/$archivo_post backup/a_options.xml";
    exec($comando,$output,$retval);
    if ($retval==0)
    {
        $xmlDoc = new DOMDocument();
        $xmlDoc->load("$dir_respaldo/backup/a_options.xml");

        //copio el archivo en memoria
        $root = $xmlDoc->documentElement;//apunto a el tag raiz

        $optionsList = $root->getElementsByTagName("options");
        foreach($optionsList as $optionGeneral) {
            $attributeID = $optionGeneral->getAttribute("id");
            $option = $optionGeneral->getElementsByTagName("option");
            foreach($option as $value) {
                $arrBackupOptions[$attributeID][$value->nodeValue]["disable"] = "";
            }
        }
    }

    //$_ruta_archivo_ = $archivo_post;
    $smarty->assign("BACKUP_FILE", $archivo_post);
    $smarty->assign("title", $arrLang["Restore"]. ": $archivo_post");
    $smarty->assign("OPTION_URL", "restore");
    $parameter = 0;
    showAlert($path_backup, $smarty, $arrLang, $archivo_post, $module_name, $parameter);

    return form_general($smarty, $local_templates_dir, $arrLang, $arrBackupOptions, $module_name);
}

function process_backup($smarty, $local_templates_dir, $arrLang, $module_name)
{
    $arrBackupOptions = Array_Options($arrLang);
    $arrSelectedOptions=array();

    $xml_backup = "<raiz>\n";
    foreach ($arrBackupOptions as $key_general=>$arrOptionGeneral)
    {
        $xml_backup .= "\t<options id=\"$key_general\">\n";
        foreach($arrOptionGeneral as $key=>$arrOption)
        {
            //verifica si ha seleccionado esa opcion
            if (isset($_POST[$key]))
            {
                //le pongo checked
                $arrBackupOptions[$key]["check"]="checked";
                $xml_backup .= "\t\t<option>$key</option>\n";
                //lo agrego al arreglo
                $arrSelectedOptions[]=$key;
            }
        }
        $xml_backup .= "\t</options>\n";
    }
    $xml_backup .= "</raiz>";

    //verifico que haya seleccionado al menos una opcion
    if (!count($arrSelectedOptions)>0)
    {
        //no ha seleccionado opcion
        $smarty->assign("ERROR_MSG", $arrLang["Choose an option to backup"]);
    }
    else
    {
        //crear la carpeta donde se va a copiar el respaldo que se realice
        $dir_respaldo = "/var/www/backup";
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

        //Guardar xml para saber que opciones fueron respaldadas y obviar las que no en el momento de hacer el restore
        $gestor = fopen($ruta_respaldo_sin_valor_unico."/a_options.xml", "w");
        fwrite($gestor, $xml_backup);
        fclose($gestor);

        //Escribo el archivo xml de versions para validaciones en el restore
        createVersionPrograms_XML($dir_respaldo);

        //hacer el respaldo de las opciones seleccionadas
        //tengo que mostrar cuales de las opciones seleccionadas, se hizo el respaldo correctamente por eso envio $arrBackupOptions
        $status = process_each_backup($arrSelectedOptions,$ruta_respaldo_sin_valor_unico,$arrBackupOptions);
        /*if(!$status['status']){
            exec("rm $ruta_respaldo_sin_valor_unico -rf");
            $smarty->assign("ERROR_MSG", $arrLang["Backup Complete!"].": elastix$timestamp.tar");
            return backup_form($smarty, $local_templates_dir, $arrLang, $module_name);
        }*/
        //en la carpeta backup ya deberia tener los respaldos
        //comprimo la carpeta
        //y la envio al navegador
        exec("tar -C $dir_respaldo -cvf $dir_respaldo/elastix$timestamp.tar $carpeta_respaldo ",$output,$retval);
        if ($retval<>0) //no se pudo generar el archivo comprimido
            $errMsg= $arrLang["Could not generate backup file"]." : $dir_respaldo/elastix$timestamp.tar\n";
        else{
            //mensaje que se ha completado el backup
            $smarty->assign("ERROR_MSG", $arrLang["Backup Complete!"].": elastix$timestamp.tar");
            #print "Backup file location: $dir_respaldo/elastixBackup.tgz\n";
        }
        //borro la carpeta de backup
        exec("rm $ruta_respaldo_sin_valor_unico -rf");
    }

    return backup_form($smarty, $local_templates_dir, $arrLang, $module_name);
}

function process_restore($smarty, $local_templates_dir, $arrLang, $path_backup, $module_name)
{
    $arrRestoreOptions = Array_Options($arrLang);
    $arrSelectedOptions=array();

    foreach ($arrRestoreOptions as $key_general=>$arrOptionGeneral)
    {
        foreach($arrOptionGeneral as $key=>$arrOption)
        {
            //verifica si ha seleccionado esa opcion
            if (isset($_POST[$key]))
            {
                //le pongo checked
                $arrRestoreOptions[$key]["check"]="checked";
                //lo agrego al arreglo
                $arrSelectedOptions[]=$key;
            }
        }
    }
    $smarty->assign("module",$module_name);
    //verifico que haya seleccionado al menos una opcion
    if (!count($arrSelectedOptions)>0)
    {
        //no ha seleccionado opcion
        $smarty->assign("ERROR_MSG", $arrLang["Choose an option to restore"]);
    }
    else
    {
        //verificar que existe el archivo de respaldo
        $backup_file=$_POST['backup_file'];
        if (empty($backup_file))
            $smarty->assign("ERROR_MSG", $arrLang["Backup file path can't be empty"]);
        else
        {
            $path_file_backup = "$path_backup/$backup_file";
            //verificar que el archivo existe
            if (!file_exists($path_file_backup))
            {
                $smarty->assign("ERROR_MSG", $arrLang["File doesn't exist"]);
            }else
            {
                //crear la carpeta donde se va a descomprimir el archivo de respaldo
                $dir_respaldo = "$path_backup";
                $timestamp=time();
                $ruta_restaurar="$path_backup/restore";
                $ruta_respaldo="$ruta_restaurar/backup";
                if (!file_exists($ruta_restaurar)) mkdir($ruta_restaurar);
                //descomprimir el archivo
                $comando="tar -C $ruta_restaurar -pxvf $path_file_backup ";
                exec($comando,$output,$retval);
                if ($retval==0)
                {
                    //se descomprimio
                    //al descomprimir se debe crear una carpeta con nombre backup
                    //hacer el restore de lo elegido
                    process_each_restore($arrSelectedOptions,$ruta_respaldo,$ruta_restaurar,$arrRestoreOptions);
                    //borro la carpeta de restore
                    exec("rm $ruta_restaurar -rf");
                    $smarty->assign("ERROR_MSG", $arrLang["Restore Complete!"]);
                }
            }
        }
    }
    return restore_form($smarty, $local_templates_dir, $arrLang, $path_backup, $module_name);
}

function Array_Options($arrLang, $disabled="")
{
    $arrBackupOptions = array(
            "asterisk"      =>  array(
                                    "as_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "as_config_files"   =>  array("desc"=>$arrLang["Configuration Files"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "as_monitor"        =>  array("desc"=>$arrLang["Monitors"]."  ".$arrLang["(Heavy Content)"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "as_voicemail"      =>  array("desc"=>$arrLang["Voicemails"]."  ".$arrLang["(Heavy Content)"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "as_sounds"         =>  array("desc"=>$arrLang["Sounds"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "as_mohmp3"            =>
                                    array("desc"=>$arrLang["MOH"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "as_dahdi"         =>  array("desc"=>$arrLang["DAHDI Configuration"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                ),
            "fax"           =>  array(
                                    "fx_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "fx_pdf"            =>  array("desc"=>$arrLang["PDF"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                ),
            "email"         =>  array(
                                    "em_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "em_mailbox"        =>  array("desc"=>$arrLang["Mailbox"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                ),
            "endpoint"      =>  array(
                                    "ep_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "ep_config_files"   =>  array("desc"=>$arrLang["Configuration Files"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                ),
            "otros"         =>  array(
                                    "sugar_db"          =>  array("desc"=>$arrLang["SugarCRM Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "vtiger_db"         =>  array("desc"=>$arrLang["VtigerCRM Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "a2billing_db"      =>  array("desc"=>$arrLang["A2billing Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "mysql_db"          =>  array("desc"=>$arrLang["Mysql Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "menus_permissions" =>  array("desc"=>$arrLang["Menus and Permissions"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "fop_config"        =>  array("desc"=>$arrLang["Flash Operator Panel Config Files"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                ),
           "otros_new"      =>  array(
                                    "calendar_db"          =>  array("desc"=>$arrLang["Calendar  Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "address_db"          =>  array("desc"=>$arrLang["Address Book Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "conference_db"          =>  array("desc"=>$arrLang["Conference  Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    "eop_db"          =>  array("desc"=>$arrLang["EOP"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                ),
    );
    return $arrBackupOptions;
}

/* ------------------------------------------------------------------------------- */
/* FUNCIONS PARA EL BACKUP*/
/* ------------------------------------------------------------------------------- */

function process_each_backup($arrSelectedOptions,$ruta_respaldo,&$arrBackupOptions)
{
    global $arrConf;
    $arrResult = array();
    $arrExito = array();
    $arrExitoFolder = array();
    foreach ($arrSelectedOptions as $option)
    {
        $bExito=true;
        $error="";
        switch ($option){
        case "as_db":
            $dir_resp_db="$ruta_respaldo/mysqldb_asterisk";
            mkdir($dir_resp_db);

            //Hacer mysqldump de cada base de asterisk
            $arrMysqlAsterisk = respaldar_base_mysql($dir_resp_db, "asterisk");
            if(!$arrMysqlAsterisk['status']){
                $arrMysqlAsterisk['db'] = "asterisk";
                $arrExito[] = $arrMysqlAsterisk;
                $bExito = false;
            }
            $arrMysqlAsteriskCdr = respaldar_base_mysql($dir_resp_db, "asteriskcdrdb");
            if(!$arrMysqlAsteriskCdr['status']){
                $arrMysqlAsteriskCdr['db'] = "asteriskcdrdb";
                $arrExito[] = $arrMysqlAsteriskCdr;
                $bExito = false;
            }
            $arrMysqlAsteriskReal = respaldar_base_mysql($dir_resp_db, "asteriskrealtime");
            if(!$arrMysqlAsteriskReal['status']){
                $arrMysqlAsteriskReal['db'] = "asteriskrealtime";
                $arrExito[] = $arrMysqlAsteriskReal;
                $bExito = false;
            }

            //Respaldar carpeta con las bases
            $arrInfoRespaldo = array(   'folder_path'               =>  $ruta_respaldo,
                                        'folder_name'               =>  "mysqldb_asterisk",
                                        'nombre_archivo_respaldo'   =>  "mysqldb_asterisk.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "$ruta_respaldo/mysqldb_asterisk";
            }

            //Se respalda la base asterisk en /var/lib/asterisk/astdb
            if(!copy("/var/lib/asterisk/astdb", $ruta_respaldo."/astdb")) $bExito = false;

            //Se respalda la carpeta admin de FreePBX

            $arrInfoRespaldo2 = array(  'folder_path'               =>  "/var/www/html",
                                        'folder_name'               =>  "admin",
                                        'nombre_archivo_respaldo'   =>  "var.www.html.admin.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo2,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/www/html/admin";
            }

            //borrar la carpeta de respaldo mysqldb
            exec("rm $ruta_respaldo/mysqldb_asterisk -rf");
            break;

        case "as_config_files":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/etc",
                                        'folder_name'               =>  "asterisk",
                                        'nombre_archivo_respaldo'   =>  "etc.asterisk.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/etc/asterisk";
            }
            break;

        case "as_monitor":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/var/spool/asterisk",
                                        'folder_name'               =>  "monitor",
                                        'nombre_archivo_respaldo'   =>  "var.spool.asterisk.monitor.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/spool/asterisk/monitor";
            }
            break;

        case "as_voicemail":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/var/spool/asterisk",
                                        'folder_name'               =>  "voicemail",
                                        'nombre_archivo_respaldo'   =>  "var.spool.asterisk.voicemail.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/spool/asterisk/voicemail";
            }
            break;

        case "as_sounds":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/var/lib/asterisk/sounds",
                                        'folder_name'               =>  "custom",
                                        'nombre_archivo_respaldo'   =>  "var.lib.asterisk.sounds.custom.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/lib/asterisk/sounds/custom";
            }
            break;

        case "as_mohmp3":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/var/lib/asterisk",
                                        'folder_name'               =>  "mohmp3",
                                        'nombre_archivo_respaldo'   =>  "var.lib.asterisk.mohmp3.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/lib/asterisk/mohmp3";
            }

            $arrInfoRespaldo2 = array( 'folder_path'               =>  "/var/lib/asterisk",
                                        'folder_name'               =>  "moh",
                                        'nombre_archivo_respaldo'   =>  "var.lib.asterisk.moh.tgz"
                                );

            if(!respaldar_carpeta($arrInfoRespaldo2,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/lib/asterisk/moh";
            }
            break;

        case "as_dahdi":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/etc",
                                        'folder_name'               =>  "dahdi",
                                        'nombre_archivo_respaldo'   =>  "etc.dahdi.tgz"
                                    );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/etc/dahdi";
            }
            break;

        case "fx_db":
            if(!copy("$arrConf[elastix_dbdir]/fax.db", $ruta_respaldo."/fax.db")) $bExito = false;
            break;

        case "fx_pdf":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/var/www",
                                        'folder_name'               =>  "faxes",
                                        'nombre_archivo_respaldo'   =>  "var.www.faxes.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/www/faxes";
            }
            break;

        case "em_db":
            $arrMysqlRoundcube = respaldar_base_mysql($ruta_respaldo, "roundcubedb");
            if(!$arrMysqlRoundcube){
                $arrMysqlRoundcube['db'] = "roundcubedb";
                $arrExito[] = $arrMysqlRoundcube;
                $bExito = false;
            }

            if (file_exists("$ruta_respaldo/roundcubedb.sql")){
                $comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/roundcubedb_mysql.tgz roundcubedb.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

                $comando="rm -f $ruta_respaldo/roundcubedb.sql";
                exec($comando,$output,$retval);
            }else if (file_exists("$ruta_respaldo/roundcubedb2.sql")){
                //Si existe este archivo es porq la base esta vacia o no existe
                $comando="rm -f $ruta_respaldo/roundcubedb2.sql";
                exec($comando,$output,$retval);
            }else $bExito = false;

            if(!copy("$arrConf[elastix_dbdir]/email.db", "$ruta_respaldo/email.db")) $bExito = false;

            break;

        case "em_mailbox":
            //respaldar los  mailboxes ruta /var/spool/imap
            //primero cambiar los permisos a la carpeta
            $comando="sudo -u root /bin/chown asterisk:asterisk /var/spool/imap -R";
            exec($comando,$output,$retval);
            if ($retval!=0) $bExito = false;
            else{
                $arrInfoRespaldo = array(   'folder_path'               =>  "/var/spool",
                                            'folder_name'               =>  "imap",
                                            'nombre_archivo_respaldo'   =>  "var.spool.imap.tgz"
                                );
                if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                    $bExito = false;
                    $arrExitoFolder[]['status'] = false;
                    $arrExitoFolder[]['folder'] = "/var/spool/imap";
                }
            }
            $comando="sudo -u root /bin/chown cyrus:mail /var/spool/imap -R";
            exec($comando,$output,$retval);
            break;

        case "ep_db":
            if(!copy("$arrConf[elastix_dbdir]/endpoint.db", "$ruta_respaldo/endpoint.db")) $bExito = false;
            break;

        case "ep_config_files":
            $arrInfoRespaldo = array(   'folder_path'               =>  "/",
                                        'folder_name'               =>  "tftpboot",
                                        'nombre_archivo_respaldo'   =>  "tftpboot.tgz"
                                );
            //Cambiar permisos a la carpeta (con sudo), sino no se puede hacer backup
            $comando="sudo -u root /bin/chown asterisk:asterisk /tftpboot -R";
            exec($comando,$output,$retval);
            if ($retval!=0) $bExito = false;
            else{
                if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                    $bExito = false;
                    $arrExitoFolder[]['status'] = false;
                    $arrExitoFolder[]['folder'] = "/tftpboot";
                }
            }
            //cambio de nuevo a root
            $comando="sudo -u root /bin/chown root:root /tftpboot";
            exec($comando,$output,$retval);
            break;

        case "sugar_db":
            $arrMysqlSugarCrm = respaldar_base_mysql($ruta_respaldo, "sugarcrm");
            if(!$arrMysqlSugarCrm){
                $arrMysqlSugarCrm['db'] = "sugarcrm";
                $arrExito[] = $arrMysqlSugarCrm;
                $bExito = false;
            }

            if (file_exists("$ruta_respaldo/sugarcrm.sql"))
            {
                $comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/sugarcrm_mysql.tgz sugarcrm.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

                $comando="rm -f $ruta_respaldo/sugarcrm.sql";
                exec($comando,$output,$retval);
            }else if (file_exists("$ruta_respaldo/sugarcrm2.sql"))
            {
                //Si existe este archivo es porq la base esta vacia o no existe
                $comando="rm -f $ruta_respaldo/sugarcrm2.sql";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "vtiger_db":
            $arrMysqlVtigerCrm = respaldar_base_mysql($ruta_respaldo, "vtigercrm510");
            if(!$arrMysqlVtigerCrm){
                $arrMysqlVtigerCrm['db'] = "vtigercrm510";
                $arrExito[] = $arrMysqlVtigerCrm;
                $bExito = false;
            }

            if (file_exists("$ruta_respaldo/vtigercrm510.sql"))
            {
                $comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/vtigercrm510_mysql.tgz vtigercrm510.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

                $comando="rm -f $ruta_respaldo/vtigercrm510.sql";
                exec($comando,$output,$retval);
            }else if (file_exists("$ruta_respaldo/vtigercrm5102.sql"))
            {
                //Si existe este archivo es porq la base esta vacia o no existe
                $comando="rm -f $ruta_respaldo/vtigercrm5102.sql";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "a2billing_db":
            $arrMysqlA2billing = respaldar_base_mysql($ruta_respaldo, "mya2billing");
            if(!$arrMysqlA2billing){
                $arrMysqlA2billing['db'] = "mya2billing";
                $arrExito[] = $arrMysqlA2billing;
                $bExito = false;
            }

            if (file_exists("$ruta_respaldo/mya2billing.sql"))
            {
                $comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/mya2billing_mysql.tgz mya2billing.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

                $comando="rm -f $ruta_respaldo/mya2billing.sql";
                exec($comando,$output,$retval);
            }else if (file_exists("$ruta_respaldo/mya2billing2.sql"))
            {
                //Si existe este archivo es porq la base esta vacia o no existe
                $comando="rm -f $ruta_respaldo/mya2billing2.sql";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "mysql_db":
            $arrMysqlDb = respaldar_base_mysql($ruta_respaldo, "mysql");
            if(!$arrMysqlDb){
                $arrMysqlDb['db'] = "mysql";
                $arrExito[] = $arrMysqlDb;
                $bExito = false;
            }

            if (file_exists("$ruta_respaldo/mysql.sql"))
            {
                $comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/mysql_mysql.tgz mysql.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

                $comando="rm -f $ruta_respaldo/mysql.sql";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "menus_permissions":
            if(!copy("$arrConf[elastix_dbdir]/menu.db", "$ruta_respaldo/menu.db")) $bExito = false;
            if(!copy("$arrConf[elastix_dbdir]/acl.db", "$ruta_respaldo/acl.db")) $bExito = false;

            break;

        case "fop_config":
            //FLASH
            $arrInfoRespaldo = array('folder_path'            =>"/var/www/html/",
                                     'folder_name'            =>"panel/*.cfg panel/*.txt",
                                     'nombre_archivo_respaldo'=>"var.www.html.panel.tgz"
                                );
            if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
                $bExito = false;
                $arrExitoFolder[]['status'] = false;
                $arrExitoFolder[]['folder'] = "/var/www/html/panel/*.cfg panel/*.txt";
            }
            $comando1="rpm -q --queryformat '%{version}' freePBX";
            $output1 = `$comando1`;
            if(isset($output1))
                $version = explode(".",$output1);
                if($version[0] >=2 && $version[1] <= 6)
                    if(!copy("/var/lib/asterisk/bin/retrieve_op_conf_from_mysql.pl", "$ruta_respaldo/retrieve_op_conf_from_mysql.pl")) $bExito = false;

            break;

        case "calendar_db":
            if(!copy("$arrConf[elastix_dbdir]/calendar.db", "$ruta_respaldo/calendar.db")) $bExito = false;
            break;

        case "address_db":
            if(!copy("$arrConf[elastix_dbdir]/address_book.db", "$ruta_respaldo/address_book.db")) $bExito = false;
            break;

        case "conference_db":
            $arrMysqlMeetme = respaldar_base_mysql($ruta_respaldo, "meetme");
            if(!$arrMysqlMeetme){
                $arrMysqlMeetme['db'] = "meetme";
                $arrExito[] = $arrMysqlMeetme;
                $bExito = false;
            }

            if (file_exists("$ruta_respaldo/meetme.sql"))
            {
                $comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/meetme_mysql.tgz meetme.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

                $comando="rm -f $ruta_respaldo/meetme.sql";
                exec($comando,$output,$retval);
            }else if (file_exists("$ruta_respaldo/meetme.sql"))
            {
                //Si existe este archivo es porq la base esta vacia o no existe
                $comando="rm -f $ruta_respaldo/meetme.sql";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "eop_db":
            if(!copy("$arrConf[elastix_dbdir]/control_panel_design.db", "$ruta_respaldo/control_panel_design.db")) $bExito = false;
            break;
        }

        if ($bExito){
            $msge = "[ OK ]";

        }else{
            $msge = "[ FAILED ]";
        }
        $arrBackupOptions[][$option]["msg"]=$msge;
    }
}

function respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,&$error)
{
    $bExito=true;
    $comando="tar -C ".$arrInfoRespaldo['folder_path'] .
             " -cvzf $ruta_respaldo/{$arrInfoRespaldo['nombre_archivo_respaldo']} ".
             $arrInfoRespaldo['folder_name'];
    exec($comando,$output,$retval);
    if ($retval<>0) $bExito=false;

    return $bExito;
}

function respaldar_base_mysql($dir_resp_db,$base)
{
    $respaldo ="";
    $estructura="";
    $arrResult = array();
    $bContinuar = FALSE;
    $host="localhost";
    $user="root";
    $pass=obtenerClaveConocidaMySQL('root');
    $dsn     = "mysql://$user:$pass@$host/$base";
    $db = new paloDB($dsn);
    if($db->errMsg != ""){
        $arrResult['status'] = false;
        $arrResult['msg'] = $db->errMsg;
    }

    if(databaseExist($base, $pass, $host, $user))
        //obtiene el dump de los inserts
        system("mysqldump -h $host -u $user -p$pass  $base -t -c > $dir_resp_db/{$base}2.sql",$retorno);
    else
        $retorno = 1;

    if ($retorno==0) $bContinuar = TRUE;

    if ($bContinuar){
        // se obtiene la estructura de la base de datos es decir vacia.
        system("mysqldump -h $host -u $user -p$pass  $base --no-data  > $dir_resp_db/{$base}.sql",$retorno);
        if ($retorno==0){
            $estructura=file_get_contents("$dir_resp_db/{$base}.sql");
            $estructura=str_replace("CREATE TABLE","CREATE TABLE IF NOT EXISTS",$estructura);

            if (strlen(trim($estructura))>0){
                $respaldo=$estructura.$respaldo;
                $bContinuar=TRUE;
            }else{//si no hay estructura no se puede continuar con los datos
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
        rewind($open); //Establece el indicador de posición de archivo de handle al principio del flujo del archivo.
        rewind($openSQL); //Establece el indicador de posición de archivo de handle al principio del flujo del archivo.
        $tamanio_linea=4096;
        $escribir = fwrite ($openSQL,$respaldo."\n");
        while ($linea = fgets($open,$tamanio_linea))  // [0]
        {
           $escribir = fwrite ($openSQL,$linea);
        }
        fclose($open);
        fclose($openSQL);
        unlink("$dir_resp_db/{$base}2.sql");
        $arrResult['status'] = true;
        $arrResult['msg'] = "";
    }

    //return $bContinuar?0:($retorno>0?1:$retorno);
    return $arrResult;
}

/* ------------------------------------------------------------------------------- */
/* FUNCIONS PARA EL RESTORE*/
/* ------------------------------------------------------------------------------- */
function process_each_restore($arrSelectedOptions,$ruta_respaldo,$ruta_restaurar,&$arrRestoreOptions)
{
    global $arrConf;
    $error="";
    $root_password = obtenerClaveConocidaMySQL('root');

    foreach ($arrSelectedOptions as $option)
    {
        $bExito=true;
        switch ($option){
        case "as_db":
            $ruta_respaldo_db = $ruta_restaurar."/mysqldb_asterisk";
            if (file_exists("$ruta_respaldo/mysqldb_asterisk.tgz"))
            {
                $comando="tar -C $ruta_restaurar -pxvzf $ruta_respaldo/mysqldb_asterisk.tgz";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;
                else{
                    $directorio=dir($ruta_respaldo_db);
                    $arrArchivos = array();
                    while ($archivo = $directorio->read()) {
                        if ($archivo!="." && $archivo!=".." && preg_match("/^.*\.sql$/",$archivo,$regs))
                        {
                            $baseSQL = explode(".",$regs[0]);
                            $base = $baseSQL[0];
                            $fileSQL = $archivo;
                            $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo_db/$fileSQL";
                            exec($comando,$output,$retval);
                            if ($retval!=0) $bExito = false;
                        }
                    }
                    //Recargar FOP
                    include_once "modules/extensions_batch/libs/paloSantoExtensionsBatch.class.php";
                    include_once "libs/paloSantoConfig.class.php";
                    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
                    $arrAMP  = $pConfig->leer_configuracion(false);

                    $dsnAsterisk = $arrAMP['AMPDBENGINE']['valor'].":/".
                                $arrAMP['AMPDBUSER']['valor']. ":".
                                $arrAMP['AMPDBPASS']['valor']. "@".
                                $arrAMP['AMPDBHOST']['valor']. "/asterisk";
                    $pDB = new paloDB($dsnAsterisk);

                    $pConfig = new paloConfig($arrAMP['ASTETCDIR']['valor'], "asterisk.conf", "=", "[[:space:]]*=[[:space:]]*");
                    $arrAST  = $pConfig->leer_configuracion(false);

                    $pEX = new paloSantoLoadExtension($pDB);
                    $data_connection = array('host' => "127.0.0.1", 'user' => "admin", 'password' => obtenerClaveAMIAdmin());
                    $pEX->do_reloadAll($data_connection, $arrAST, $arrAMP);
                }
            }
            // se restaura la base de asterisk
            if (file_exists("$ruta_respaldo/astdb"))
            {
                $base_address_respaldo = "$ruta_respaldo/astdb";
                $base_address = "/var/lib/asterisk/astdb";


                if(!rename($base_address_respaldo, $base_address))  $bExito = false;

                $comando="sudo -u root /bin/chmod 777 $base_address";
                exec($comando,$output,$retval);
            }else $bExito = false;

            //Respaldo carpeta /var/www/html/admin en un tgz
            $comando="tar -cvzf /var/www/html/admin.tgz /var/www/html/admin/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/www/html/admin/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/var/www/html",
                                            'folder_name'               =>  "admin",
                                            'nombre_archivo_respaldo'   =>  "var.www.html.admin.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;

                $comando="amportal restart";
                exec($comando, $output, $retval);
            }

            break;

        case "as_config_files":
            //Respaldo carpeta /etc/asterisk en un tgz
            $comando="sudo -u root touch /etc/asterisk.tgz";
            exec($comando, $output, $retval);

            $comando="sudo -u root chmod 777 /etc/asterisk.tgz";
            exec($comando, $output, $retval);

            $comando="tar -cvzf /etc/asterisk.tgz /etc/asterisk/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /etc/asterisk/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/etc",
                                            'folder_name'               =>  "asterisk",
                                            'nombre_archivo_respaldo'   =>  "etc.asterisk.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;
                else {
            /* Elastix bug 164: se requiere revisar la ruta correcta a los módulos
                        de Asterisk, para lidiar con el caso de respaldo de 32 bits restaurado
                        en 64 bits o viceversa */
            // Determinar si existe la ruta de 64 bits
                        $sRutaModulos = '/usr/lib/asterisk/modules';
                        if (is_dir('/usr/lib64/asterisk/modules')) {
                            $sRutaModulos = '/usr/lib64/asterisk/modules';
                        }
                        $arrRutas = array("/usr/lib/asterisk/modules","/usr/lib64/asterisk/modules");
                        foreach (array(
                            '/etc/asterisk/asterisk.conf',
                            '/etc/asterisk/extensions_additional.conf') as $sArchivo) {

                            // Dar permiso de lectura y escritura total para proceso
                            exec("sudo -u root chmod 666 $sArchivo ", $output, $retval);

                            // Leer archivo entero para procesar
                            $contenido = file($sArchivo);
                            for ($i = 0; $i < count($contenido); $i++) {
                                $contenido[$i] = str_replace($arrRutas,$sRutaModulos,$contenido[$i]);
                            }

                            // Escribir contenido resultante
                            $hArchivo = fopen($sArchivo, 'w');
                            for ($i = 0; $i < count($contenido); $i++) {
                                fputs($hArchivo, $contenido[$i]);
                            }
                            fclose($hArchivo);

                            // Restaurar permisos del archivo
                            exec("sudo -u root chmod 664 $sArchivo ", $output, $retval);
                        }
                    }
            }
            break;

        case "as_monitor":
            //Respaldo carpeta /var/spool/asterisk/monitor en un tgz
            $comando="tar -cvzf /var/spool/asterisk/monitor.tgz /var/spool/asterisk/monitor/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/spool/asterisk/monitor/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/var/spool/asterisk",
                                            'folder_name'               =>  "monitor",
                                            'nombre_archivo_respaldo'   =>  "var.spool.asterisk.monitor.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;
            }
            break;

        case "as_voicemail":
            //Respaldo carpeta /var/spool/asterisk/voicemail en un tgz
            $comando="tar -cvzf /var/spool/asterisk/voicemail.tgz /var/spool/asterisk/voicemail/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/spool/asterisk/voicemail/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/var/spool/asterisk",
                                            'folder_name'               =>  "voicemail",
                                            'nombre_archivo_respaldo'   =>  "var.spool.asterisk.voicemail.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;
            }
            break;

        case "as_sounds":
            //Respaldo carpeta /var/spool/asterisk/sounds en un tgz
            $comando="tar -cvzf /var/lib/asterisk/sounds/custom.tgz /var/lib/asterisk/sounds/custom/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/lib/asterisk/sounds/custom/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/var/lib/asterisk/sounds",
                                            'folder_name'               =>  "custom",
                                            'nombre_archivo_respaldo'   =>  "var.lib.asterisk.sounds.custom.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;
            }
            break;

        case "as_mohmp3":
            //Respaldo carpeta /var/spool/asterisk/sounds en un tgz
            $comando="tar -cvzf /var/lib/asterisk/mohmp3.tgz /var/lib/asterisk/mohmp3/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/lib/asterisk/mohmp3/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/var/lib/asterisk",
                                            'folder_name'               =>  "mohmp3",
                                            'nombre_archivo_respaldo'   =>  "var.lib.asterisk.mohmp3.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;

                $arrInfoRestaurar2 = array( 'folder_path'               =>  "/var/lib/asterisk",
                                            'folder_name'               =>  "moh",
                                            'nombre_archivo_respaldo'   =>  "var.lib.asterisk.moh.tgz"
                                    );

                if(!restaurar_carpeta($arrInfoRestaurar2,$ruta_respaldo,$error))
                    $bExito = false;
            }
            break;

        case "as_dahdi":
            //Respaldo carpeta /etc/dahdi en un tgz
            $comando="sudo -u root touch /etc/dahdi.tgz";
            exec($comando, $output, $retval);

            $comando="sudo -u root chmod 777 /etc/dahdi.tgz";
            exec($comando, $output, $retval);

            $comando="tar -cvzf /etc/dahdi.tgz /etc/dahdi/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="sudo -u root chown -R asterisk.asterisk /etc/dahdi";
                exec($comando, $output, $retval);
                    $comando="rm -rf /etc/dahdi/*";
                    exec($comando, $output, $retval);

                    $arrInfoRestaurar = array(  'folder_path'               =>  "/etc",
                                                'folder_name'               =>  "dahdi",
                                                'nombre_archivo_respaldo'   =>  "etc.dahdi.tgz"
                                        );
                    if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                        $bExito = false;
                    else{
                        $comando="sudo -u root chown -R root.root /etc/dahdi";
                        exec($comando, $output, $retval);
                        $bExito = true;
                }
            }
            break;

        case "fx_db":
            if (file_exists("$ruta_respaldo/fax.db"))
            {
                $base_fax_respaldo = "$ruta_respaldo/fax.db";
                $base_fax= "$arrConf[elastix_dbdir]/fax.db";

                if (!rename($base_fax_respaldo, $base_fax)) {
                        $bExito = false;
                } else {

                    $comando="sudo -u root /bin/chmod 644 $base_fax";
                    exec($comando,$output,$retval);

                    //consultar en la base para crear en el sistema
                    $oFax = new paloFax();
                    $oFax->refreshFaxConfiguration();
                }
            } else $bExito = false;
            break;

        case "fx_pdf":
            //Respaldo carpeta /etc/asterisk en un tgz
            $comando="sudo -u root touch /var/www/faxes.tgz";
            exec($comando, $output, $retval);

            $comando="sudo -u root chmod 777 /var/www/faxes.tgz";
            exec($comando, $output, $retval);

            $comando="tar -cvzf /var/www/faxes.tgz /var/www/faxes/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/www/faxes/*";
                exec($comando, $output, $retval);

                $arrInfoRestaurar = array(  'folder_path'               =>  "/var/www",
                                            'folder_name'               =>  "faxes",
                                            'nombre_archivo_respaldo'   =>  "var.www.faxes.tgz"
                                    );
                if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                    $bExito = false;
            }
            break;

        case "em_db":
            //Primero eliminar todos los dominios existentes
            $pDB = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/email.db");
            $pEmail = new paloEmail($pDB);
            $virtual = FALSE;
            if(!empty($pDB->errMsg)) {
                echo "ERROR DE DB: $pDB->errMsg <br>";
            }
            $pEmail = new paloEmail($pDB);
            $arrDomain = $pEmail->getDomains();
            foreach($arrDomain as $key => $valor)
            {
                $arrTmp['domain_name']  = $valor[1];
                $arrTmp['id_domain']    = $valor[0];
                $bExito = $pEmail->eliminar_dominio($pDB,$arrTmp,$errMsg,$virtual);
            }

            $archivo = "roundcubedb";
            if (file_exists("$ruta_respaldo/{$archivo}_mysql.tgz"))
            {
                $comando="tar -C $ruta_respaldo -xvzf $ruta_respaldo/{$archivo}_mysql.tgz";
                exec($comando,$output,$retval);

                $base = $archivo;
                $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo/$archivo.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

            }else $bExito = false;

            if (file_exists("$ruta_respaldo/email.db"))
            {
                $base_email_respaldo = "$ruta_respaldo/email.db";
                $base_email = "$arrConf[elastix_dbdir]/email.db";

                //consultar en la base para crear en el sistema
                if(!crear_cuentas_email($base_email_respaldo, $base_email))
                    $bExito = false;
                if(!rename($base_email_respaldo, $base_email))  $bExito = false;

                $comando="sudo -u root /bin/chmod 777 $base_email";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "em_mailbox":
            //Respaldo carpeta /var/spool/imap/ en un tgz
            $comando="sudo -u root touch /var/spool/imap.tgz";
            exec($comando, $output, $retval);

            $comando="sudo -u root chmod 777 /var/spool/imap.tgz";
            exec($comando, $output, $retval);

            $comando="sudo -u root /bin/chown asterisk:asterisk /var/spool/imap -R";
            exec($comando, $output, $retval);

            $comando="tar -cvzf /var/spool/imap.tgz /var/spool/imap/";
            exec($comando, $output, $retval);

            if ($retval!=0) $bExito = false;
            else{
                $comando="rm -rf /var/spool/imap/*";
                exec($comando, $output, $retval);
                if ($retval!=0) $bExito = false;
                else{
                    $arrInfoRestaurar = array(  'folder_path'               =>  "/var/spool",
                                                'folder_name'               =>  "imap",
                                                'nombre_archivo_respaldo'   =>  "var.spool.imap.tgz"
                                        );
                    if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                        $bExito = false;

                    //cambio de nuevo los permisos
                    $comando="sudo -u root /bin/chown cyrus:mail /var/spool/imap -R";
                    exec($comando,$output,$retval);
                }
            }
            break;

        case "ep_db":
            if(!copy("$ruta_respaldo/endpoint.db", "$arrConf[elastix_dbdir]/endpoint.db"))  $bExito = false;

            break;

        case "ep_config_files":
            //Respaldo carpeta /var/spool/imap/ en un tgz
            $comando="sudo -u root touch /tftpboot.tgz";
            exec($comando, $output, $retval);

            $comando="sudo -u root chmod 777 /tftpboot.tgz";
            exec($comando, $output, $retval);

            $comando="tar -cvzf /tftpboot.tgz /tftpboot/";
            exec($comando, $output, $retval);
            if ($retval!=0) $bExito = false;
            else{
                //tengo que cambiarle los permisos a la carpeta (con sudo) por que sino no voy a poder hacerle el restore
                $comando="sudo -u root /bin/chown asterisk:asterisk /tftpboot -R";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;
                else{
                    $comando="rm -rf /tftpboot/*";
                    exec($comando, $output, $retval);

                    $arrInfoRestaurar = array(  'folder_path'               =>  "/",
                                                'folder_name'               =>  "tftpboot",
                                                'nombre_archivo_respaldo'   =>  "tftpboot.tgz"
                                        );
                    if(!restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,$error))
                        $bExito = false;

                    //cambio de nuevo a root
                    $comando="sudo -u root /bin/chown root:root /tftpboot";
                    exec($comando,$output,$retval);
                }
            }
            break;

        case "sugar_db":
            $archivo = "sugarcrm";
            if (file_exists("$ruta_respaldo/{$archivo}_mysql.tgz"))
            {
                $comando="tar -C $ruta_respaldo -xvzf $ruta_respaldo/{$archivo}_mysql.tgz";
                exec($comando,$output,$retval);

                $base = $archivo;
                $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo/$archivo.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

            }
            break;

        case "vtiger_db":
            $archivo = "vtigercrm510";
            if (file_exists("$ruta_respaldo/{$archivo}_mysql.tgz"))
            {
                $comando="tar -C $ruta_respaldo -xvzf $ruta_respaldo/{$archivo}_mysql.tgz";
                exec($comando,$output,$retval);

                $base = $archivo;
                $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo/$archivo.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

            }
            break;

        case "a2billing_db":
            $archivo = "mya2billing";
            if (file_exists("$ruta_respaldo/{$archivo}_mysql.tgz"))
            {
                $comando="tar -C $ruta_respaldo -xvzf $ruta_respaldo/{$archivo}_mysql.tgz";
                exec($comando,$output,$retval);

                $base = $archivo;
                $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo/$archivo.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

            }
            break;

        case "mysql_db":
            $archivo = "mysql";
            if (file_exists("$ruta_respaldo/{$archivo}_mysql.tgz"))
            {
                $comando="tar -C $ruta_respaldo -xvzf $ruta_respaldo/{$archivo}_mysql.tgz";
                exec($comando,$output,$retval);

                $base = $archivo;
                $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo/$archivo.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;

            }
            break;

        case "menus_permissions":
            if(!copy("$ruta_respaldo/menu.db", "$arrConf[elastix_dbdir]/menu.db"))  $bExito = false;
            if(!copy("$ruta_respaldo/acl.db", "$arrConf[elastix_dbdir]/acl.db"))   $bExito = false;

            break;

        case "fop_config":
            //FLASH
            $arrInfoRespaldo = array(  'folder_path'              =>  "/var/www/html/",
                                       'folder_name'              =>  "panel/*.cfg panel/*.txt",
                                       'nombre_archivo_respaldo'  =>  "var.www.html.panel.tgz"
                                );

            if(!restaurar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error))
                $bExito = false;

            $comando1="rpm -q --queryformat '%{version}' freePBX";
            $output1 = `$comando1`;
            if(isset($output1))
                $version = explode(".",$output1);
                if($version[0] >=2 && $version[1] <= 6)
                if(!copy("$ruta_respaldo/retrieve_op_conf_from_mysql.pl", "/var/lib/asterisk/bin/retrieve_op_conf_from_mysql.pl"))  $bExito = false;

            break;

        case "calendar_db":

            if (file_exists("$ruta_respaldo/calendar.db"))
            {
                $base_calendar_respaldo = "$ruta_respaldo/calendar.db";
                $base_calendar = "$arrConf[elastix_dbdir]/calendar.db";

                if(!rename($base_calendar_respaldo, $base_calendar))  $bExito = false;

                $comando="sudo -u root /bin/chmod 777 $base_calendar";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "address_db":

            if (file_exists("$ruta_respaldo/address_book.db"))
            {
                $base_address_respaldo = "$ruta_respaldo/address_book.db";
                $base_address = "$arrConf[elastix_dbdir]/address_book.db";

                if(!rename($base_address_respaldo, $base_address))  $bExito = false;

                $comando="sudo -u root /bin/chmod 777 $base_address";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        case "conference_db":
            $archivo = "meetme";
            if (file_exists("$ruta_respaldo/{$archivo}_mysql.tgz"))
            {
                $comando="tar -C $ruta_respaldo -xvzf $ruta_respaldo/{$archivo}_mysql.tgz";
                exec($comando,$output,$retval);

                $base = $archivo;
                $comando="mysql --password=".$root_password." --user=root $base < $ruta_respaldo/$archivo.sql";
                exec($comando,$output,$retval);
                if ($retval!=0) $bExito = false;
            }
            break;

        case "eop_db":

            if (file_exists("$ruta_respaldo/control_panel_design.db"))
            {
                $base_address_respaldo = "$ruta_respaldo/control_panel_design.db";
                $base_address = "$arrConf[elastix_dbdir]/control_panel_design.db";

                if(!rename($base_address_respaldo, $base_address))  $bExito = false;

                $comando="sudo -u root /bin/chmod 777 $base_address";
                exec($comando,$output,$retval);
            }else $bExito = false;
            break;

        }

        if ($bExito) $msge="[ OK ]";
        else $msge="[ FAILED ]";
        $arrRestoreOptions[$option]["msg"]=$msge;
    }
}

function compareArrays($path_backup, $arr_XML){
   $errors = "";
    foreach($path_backup as $key => $value) {
       if($arr_XML[$key]['version'] != $path_backup[$key]['version'] ||  $arr_XML[$key]['release'] != $path_backup[$key]['release']){
             $errors[$key] = $path_backup[$key]['version']. "-" .$path_backup[$key]['release'];
        }
   }
    return $errors;
}

function showAlert($path_backup, $smarty, $arrLang, $backup_file, $module_name, $parameter){
    //verificar que existe el archivo de respaldo

    if (empty($backup_file))
        $smarty->assign("ERROR_MSG", $arrLang["Backup file path can't be empty"]);
    else
    {
        $path_file_backup = "$path_backup/$backup_file";
        //verificar que el archivo existe
        if (!file_exists($path_file_backup))
        {
                $smarty->assign("ERROR_MSG", $arrLang["File doesn't exist"]);
        }else
        {
            //crear la carpeta donde se va a descomprimir el archivo de respaldo
            $dir_respaldo = "$path_backup";
            $timestamp=time();
            $ruta_restaurar="$path_backup/restore";
            $ruta_respaldo="$ruta_restaurar/backup";
            if (!file_exists($ruta_restaurar)) mkdir($ruta_restaurar);
            //descomprimir el archivo
            $comando="tar -C $ruta_restaurar -pxvf $path_file_backup ";
            exec($comando,$output,$retval);
            if ($retval==0)
            {
                $path_backup_XML = getVersionPrograms_SYSTEM();
                $arr_XML = getVersionPrograms_XML($ruta_restaurar);
                if($arr_XML==null){
                    $smarty->assign("mb_message", $arrLang["no_file_xml"]);
                    exec("rm $ruta_restaurar -rf");
                    return ;
                }
                $compare = compareArrays($path_backup_XML, $arr_XML);

                if($parameter == 0){
                    if(count($compare)>=1 && $compare!=null){
                        $warning = $arrLang["Warning"];
                        $outMessage = $warning." <a href='?menu=$module_name&action=detail&rawmode=yes&file_name=$backup_file' rel='facebox'>".$arrLang["details"]."</a>";
                        $smarty->assign("mb_message", $outMessage);
                    }
                    exec("rm $ruta_restaurar -rf");
                    return ;
                }else{
                    exec("rm $ruta_restaurar -rf");
                    $version = boxAlert($module_name, $arrLang,$path_backup_XML,$arr_XML,$compare);
                    return $version;
                }
            }
        }
    }
}

function boxAlert($module_name, $arrLang, $programs, $external, $compare){
    $html = "<table id='version' width='750px' align='center'>".
             "<tr class='moduleTitle'><td class='moduleTitle' align='center' valign='middle' colspan=5>".$arrLang['warning_details']."</td></tr>
             <tr class='tabForm'>
                <td class='tabForm'><b>".$arrLang['programs']."</b></td>
                <td class='tabForm'><b>".$arrLang['Package']."</b></td>
                <td class='tabForm' colspan='2'>
                    <table>
                        <tr align='center'><td width='130px' colspan='2' style='border-bottom: solid 1px #AAAAAA; font-weight: bold; color: #333333; font-family: verdana,arial,helvetica,sans-serif;'>"._tr("Version")."</td></tr>
                        <tr align='center'>
                            <td style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'><b>".$arrLang['local_version']."</b></td>
                            <td style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'><b>".$arrLang['external_version']."</b></td>
                        </tr>
                    </table>
                </td>
                <td class='tabForm'>
                    <table align='center'>
                        <tr align='center'><td colspan='6' style='border-bottom: solid 1px #AAAAAA; font-weight: bold; color: #333333; font-family: verdana,arial,helvetica,sans-serif;'>"._tr("Options Backup")."</td></tr>
                        <tr align='center'>
                            <td width='60px' style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'>&nbsp;"._tr("Endpoint")."&nbsp;</td>
                            <td width='60px' style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'>&nbsp;"._tr("Fax")."&nbsp;</td>
                            <td width='60px' style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'>&nbsp;"._tr("Email")."&nbsp;</td>
                            <td width='60px' style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'>&nbsp;"._tr("Asterisk")."&nbsp;</td>
                            <td width='60px' style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'>&nbsp;"._tr("Others")."&nbsp;</td>
                            <td width='60px' style='color: #333333; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;'>&nbsp;"._tr("Others new")."&nbsp;</td>
                        </tr>
                    </table>
                </td>
             </tr>";
    foreach($compare as $key => $value){
        $externalValues = $external[$key]['version']."-".$external[$key]['release'];
        $programsValues = $programs[$key]['version']."-".$programs[$key]['release'];

        if($external[$key]['version'] == $external[$key]['release']){
            $externalValues = "<span style='font-style: italic; color: red;'>"._tr("Package not installed")."</span>";
        }

        if($programs[$key]['version'] == $programs[$key]['release']){
            $programsValues = "<span style='font-style: italic; color: red;'>"._tr("Package not installed")."</span>";
        }

        $arrVal = getValueofBackupOption($key);

        $html .="<tr onmouseout=\"this.style.backgroundColor='#ffffff';\" onmouseover=\"this.style.backgroundColor='#f2f2f2';\">
                        <td class='tdStyle'>"._tr($key)."</td>
                        <td class='tdStyle'>$key</td>
                        <td class='tdStyle'>$programsValues</td>
                        <td class='tdStyle'>$externalValues</td>
                        <td class='tdStyle'>
                            <table>
                                <tr align='center'>
                                    <td width='60px' class='tdStyle'>".$arrVal['endpoint']."</td>
                                    <td width='60px' class='tdStyle'>".$arrVal['fax']."</td>
                                    <td width='60px' class='tdStyle'>".$arrVal['email']."</td>
                                    <td width='60px' class='tdStyle'>".$arrVal['asterisk']."</td>
                                    <td width='60px' class='tdStyle'>".$arrVal['otros']."</td>
                                    <td width='60px' class='tdStyle'>".$arrVal['otros_new']."</td>
                                </tr>
                            </table>
                        <td>
                    </tr>";
    }
    $html .="</table>";
    return $html;
}

function getValueofBackupOption($valueOp)
{
    $arrayOptions = array(
        "endpoint" => array("elastix-pbx"),
        "fax" => array("elastix-fax"),
        "email" => array("elastix","elastix-email_admin"),
        "asterisk" => array("asterisk","dahdi","wanpipe-util","freepbx","elastix"),
        "otros" => array("elastix-vtigercrm","elastix-a2billing","elastix","elastix-pbx","elastix-sugarcrm-addon"),
        "otros_new" => array("elastix-pbx","elastix-agenda")
    );
    $arrayResult = array();
    foreach($arrayOptions as $key => $value)
    {
        for($i=0; $i<count($value); $i++){
            $package = $value[$i];
            if($valueOp == $package)
                $arrayResult[$key] = "x";
            $arrayResult[$key] = isset($arrayResult[$key])?$arrayResult[$key]:"";
        }
    }
    return $arrayResult;
}

function showMessageAlert($arr){
    $version = "";
    foreach($arr as $key => $value){
        $version .= "$key => version ".$arr[$key]['version']." release ".$arr[$key]['release']."\n";
    }
    return $version;
}

function viewDetail($smarty, $module_name, $local_templates_dir, $arrLang, $path_backup){
    $parameter = 1;
    $backup_file = getParameter("file_name");
   $htmlForm = showAlert($path_backup, $smarty, $arrLang, $backup_file, $module_name, $parameter);
    $content  = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;
}

function getVersionPrograms_SYSTEM(){
     //obteniendo version de asterisk, dahdi, Sangoma, freePBX, elastix
     // asterisk
     $comando1="rpm -q --queryformat '%{version}' asterisk";
     $comando2="rpm -q --queryformat '%{release}' asterisk";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['asterisk'] = array("version" => "$output1", "release" => "$output2");

     // dahdi
     $comando1="rpm -q --queryformat '%{version}' dahdi";
     $comando2="rpm -q --queryformat '%{release}' dahdi";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['dahdi'] = array("version" => "$output1", "release" => "$output2");

     // Sangoma
     $comando1="rpm -q --queryformat '%{version}' wanpipe-util";
     $comando2="rpm -q --queryformat '%{release}' wanpipe-util";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['wanpipe-util'] = array("version" => "$output1", "release" => "$output2");

     // freePBX
     $comando1="rpm -q --queryformat '%{version}' freePBX";
     $comando2="rpm -q --queryformat '%{release}' freePBX";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['freepbx']  = array("version" => "$output1", "release" => "$output2");

     // elastix
     $comando1="rpm -q --queryformat '%{version}' elastix";
     $comando2="rpm -q --queryformat '%{release}' elastix";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix']  = array("version" => "$output1", "release" => "$output2");
/***************************** added ******************************************************/
     // elastix-pbx
     $comando1="rpm -q --queryformat '%{version}' elastix-pbx";
     $comando2="rpm -q --queryformat '%{release}' elastix-pbx";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-pbx']  = array("version" => "$output1", "release" => "$output2");

     // elastix-email
     $comando1="rpm -q --queryformat '%{version}' elastix-email_admin";
     $comando2="rpm -q --queryformat '%{release}' elastix-email_admin";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-email_admin']  = array("version" => "$output1", "release" => "$output2");

     // elastix-agenda
     $comando1="rpm -q --queryformat '%{version}' elastix-agenda";
     $comando2="rpm -q --queryformat '%{release}' elastix-agenda";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-agenda']  = array("version" => "$output1", "release" => "$output2");

     // elastix-fax
     $comando1="rpm -q --queryformat '%{version}' elastix-fax";
     $comando2="rpm -q --queryformat '%{release}' elastix-fax";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-fax']  = array("version" => "$output1", "release" => "$output2");

     // elastix-vtigercrm
     $comando1="rpm -q --queryformat '%{version}' elastix-vtigercrm";
     $comando2="rpm -q --queryformat '%{release}' elastix-vtigercrm";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-vtigercrm']  = array("version" => "$output1", "release" => "$output2");

     // elastix-a2billing
     $comando1="rpm -q --queryformat '%{version}' elastix-a2billing";
     $comando2="rpm -q --queryformat '%{release}' elastix-a2billing";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-a2billing']  = array("version" => "$output1", "release" => "$output2");

     // elastix-sugarcrm-addon
     $comando1="rpm -q --queryformat '%{version}' elastix-sugarcrm-addon";
     $comando2="rpm -q --queryformat '%{release}' elastix-sugarcrm-addon";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     if(strlen($output2)>3){
        $output1 = _tr("Package not installed");
        $output2 = _tr("Package not installed");
     }
     $arrPro['elastix-sugarcrm-addon']  = array("version" => "$output1", "release" => "$output2");
/***************************** end added ***************************************************/
     return $arrPro;
}

function getVersionPrograms_XML($path_backup)
{
    $dir_respaldo = "$path_backup";
     if(!file_exists("$dir_respaldo/backup/versions.xml"))
        return null;
    $xmlDoc = new DOMDocument();
    $xmlDoc->load("$dir_respaldo/backup/versions.xml");
    $arrPrograms = null;

    //copio el archivo en memoria
    $root = $xmlDoc->documentElement;//apunto a el tag versions
    $optionsList = $root->getElementsByTagName("program");

    foreach($optionsList as $optionGeneral) {
        $arrPrograms[$optionGeneral->getAttribute("id")] = array(
            "version" => $optionGeneral->getAttribute("ver"),
            "release" => $optionGeneral->getAttribute("rel"));
    }
    return $arrPrograms;
}

function createVersionPrograms_XML($dir_respaldo)
{
    $arrSelectedOptions=array();
    $arrPrograms = getVersionPrograms_SYSTEM();

    $xml_versions = "<versions>\n";
    foreach ($arrPrograms as $id => $program)
        $xml_versions .= "\t<program id=\"$id\" ver=\"$program[version]\" rel=\"$program[release]\" />\n";
    $xml_versions .= "</versions>";

    //crear la carpeta donde se va a copiar el respaldo que se realice
    $dir_respaldo = "/var/www/backup";
    $carpeta_respaldo = "backup";
    $ruta_respaldo_sin_valor_unico = "$dir_respaldo/$carpeta_respaldo";

    //Guardar xml
    $gestor = fopen($ruta_respaldo_sin_valor_unico."/versions.xml", "w");
    fwrite($gestor, $xml_versions);
    fclose($gestor);
}

function restaurar_carpeta($arrInfoRestaurar,$ruta_respaldo,&$error)
{
    $bExito=true;
    $comando="tar -C ".$arrInfoRestaurar['folder_path'] .
             " -pxvzf $ruta_respaldo/{$arrInfoRestaurar['nombre_archivo_respaldo']}";

    exec($comando,$output,$retval);
    if ($retval<>0) $bExito=false;

    return $bExito;
}

function crear_cuentas_email($ruta_base_email_respaldo,$base_email)
{
    $bExito=true;
    $virtual = FALSE;
    $result=array();
    $pDB = new paloDB("sqlite3:///$ruta_base_email_respaldo");
    if (!empty($pDB->errMsg)) {
        echo "DB ERROR: $pDB->errMsg \n";
    }
    else{
        #borrar las cuentas de dominos y el domino $arrConf[elastix_dbdir]
        $pDBorig = new paloDB("sqlite3:///$base_email");
        $pEmail  = new paloEmail($pDBorig);

        $separador = "";
        $comando1="rpm -q --queryformat '%{version}' elastix";
        $output1 = `$comando1`;
        if(isset($output1))
            $version = explode(".",$output1);
                if($version[0] == 2)
                    $separador = "@"; //si es un elastix 2.0
                else
                    $separador = "\."; //si es un elastix 1.6

        if (!empty($pDBorig->errMsg)) {
            echo "DB ERROR: $pDBorig->errMsg \n";
        }
        else{

            // ya no se debe hacer ya que se hizo antes de llamar a esta funcion
            $query="SELECT * FROM domain";
            $result=$pDBorig->fetchTable($query,true);
            if(is_array($result) && count($result) > 0){
                foreach($result as $key => $value){
                    $arrTmp['id_domain']= $value['id'];
                    $arrTmp['domain_name']= $value['domain_name'];
                    $bExito = $pEmail->eliminar_dominio($pDBorig,$arrTmp,$errMsg,$virtual);
                }
            }
            // ya no se debe hacer ya que se hizo antes de llamar a esta funcion

            if($bExito){
                #crear los dominios
                #seleccionar los dominios
                $sQuery="SELECT * from domain";
                $result=$pDB->fetchTable($sQuery,true);
                if(is_array($result) && count($result)>0)
                {
                    foreach ($result as $infoDominio)
                    {
                        $pEmail->guardar_dominio_sistema($infoDominio['domain_name'],$errMsg);
                    }
                    #crear las cuentas
                    $sQuery="SELECT a.*,d.domain_name from accountuser as a,domain as d where d.id=a.id_domain";
                    $result=$pDB->fetchTable($sQuery,true);
                    if (is_array($result)){
                        foreach ($result as $infoCuenta)
                        {
                            $username = $infoCuenta['username'];
                            $quota    = $infoCuenta['quota'];
                            $domain   = $infoCuenta['domain_name'];
                            #armo el email
                            $arrMatchAccount = preg_split("/$separador/",$username);
                            if (count($arrMatchAccount) > 0)
                            {
                                $email=$arrMatchAccount[0].'@'.$infoCuenta['domain_name'];
                                $password=$infoCuenta['password'];
                                $bExito = $pEmail->crear_usuario_correo_sistema($email,$username,$password,$errMsg,$virtual);
                                if ($bExito){
                                    //crear el mailbox para la nueva cuenta
                                    $bReturn=crear_mailbox_usuario($email,$username,$quota,$errMsg);
                                    if(!$bReturn)
                                        $bReturn = $pEmail->eliminar_usuario_correo_sistema($username,$email,$errMsg);
                                }else{
                                    //tengo que borrar el usuario creado en el sistema
                                    $bReturn = $pEmail->eliminar_usuario_correo_sistema($username,$email,$errMsg);
                                }
                            }
                        }
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
        $seperator  = '/';
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

function databaseExist($base, $pass, $host, $user)
{
    $pathDBs = "/var/lib/mysql/".$base;
    if(is_dir($pathDBs)){
        //Ejecutando el siguiente comando en la base schema de mysql.
        $pDB = new paloDB("mysql://$user:$pass@$host/information_schema");
        $pFTPBackup = new paloSantoFTPBackup($pDB);
        $result = $pFTPBackup->existSchemaDB($base, $pDB);
        return $result;
    }else{
        return false;
    }
}

/************************  FUNCIONES PARA FTP BACKUP ***********************************/
function viewFormFTPBackup($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pFTPBackup = new paloSantoFTPBackup($pDB);
    $arrFormFTPBackup = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormFTPBackup);
    global $arrConfModule;
    $band = 0;
    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    if(!$action){
        $_DATA = $pFTPBackup->getFTPBackupById(1);
        $band = 0;
    }

    if(!(isset($_DATA) & $_DATA!="" & count($_DATA)>0)){
        $_DATA = $pFTPBackup->getFTPBackupById(1);
        if(!(is_array($_DATA) & count($_DATA)>0)){
            $_DATA['server'] = "";
            $_DATA['port'] = "21";
            $_DATA['user'] = "";
            $_DATA['password'] = "";
            $_DATA['pathServer'] = "/";
            $band = 1;
        }
    }

    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("UPLOAD", $arrLang["Upload"]);
    $smarty->assign("DOWNLOAD", $arrLang["Download"]);
    $smarty->assign("TITLE", $arrLang["TITLE"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("icon", "modules/$module_name/images/system_backup_restore.png");

    $dir = $arrConf['dir'];
    $array_new = $pFTPBackup->obtainFiles($dir);
    $content_remote = "";
    $content_local = "";
    $files_names = "";
    for($i=0; $i<count($array_new); $i++)
        $content_local .= "<li class='ui-state-default' id="."'inn_"."$array_new[$i]'><b class='item'>{$array_new[$i]}</b></li>";

    if($band == 0){
        $files_names = $pFTPBackup->getExternalNames($_DATA['user'], $_DATA['password'], $_DATA['server'], $_DATA['port'], $_DATA['pathServer'], $smarty);
    }else{
        $files_names = 'empty';
    }
    if($files_names == 1)
        $smarty->assign("mb_message", $arrLang["Error Connection"]);
    else if($files_names == 2)
        $smarty->assign("mb_message", $arrLang["Error user_password"]);
    else if($files_names == 'empty')
        $content_remote = "";
    else
        for($i=0; $i<count($files_names); $i++)
            $content_remote .= "<li class='ui-state-highlight' id="."'out_"."$files_names[$i]'><b class='item'>{$files_names[$i]}</b></li>";

    $smarty->assign("LOCAL_LI",$content_local);
    $smarty->assign("REMOTE_LI", $content_remote);
    $smarty->assign("module_name",$module_name);

    $htmlForm = $oForm->fetchForm("$local_templates_dir/formFTP.tpl",$arrLang["FTP Backup"], $_DATA);
    $content  = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;
}

function saveNewFTPBackup($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pFTPBackup = new paloSantoFTPBackup($pDB);
    $arrFormFTPBackup = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormFTPBackup);
    $_DATA  = $_POST;
    $server = getParameter("server");
    $port = getParameter("port");
    $user = getParameter("user");
    $password = getParameter("password");
    $path = getParameter("pathServer");
    if(!$oForm->validateForm($_POST)){
        // Validation basic, not empty and VALIDATION_TYPE
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";
        }
        $smarty->assign("mb_message", $strErrorMsg);
    }
    else{
        if($server &&  $port &&  $user  &&  $password &&  $path){ //deben estar llenos todos los campos
            $result = $pFTPBackup->getFTPBackupById(1);
            if($result)
                $pFTPBackup->updateData($server, $port, $user, $password, $path);
            else
                $pFTPBackup->insertData($server, $port, $user, $password, $path);
        }else
            $smarty->assign("mb_message", $arrLang["Error to save"]);
        $content = viewFormFTPBackup($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
    }
    return $content;
}
/*****************************************************************************************/
/*************** FUNCIONES PARA HACER UN BACKUP/RESTORE A UN SERVIDOR FTP ****************/

function file_upload_FTPServer($module_name, $arrLang, $arrConf, &$pDB)
{
    $dir = $arrConf['dir'];
    $file    = getParameter('file');
    $lista   = getParameter('lista'); //identifica en que lista se hace el drop

    $array = obtainList($file);
    $pFTPBackup = new paloSantoFTPBackup($pDB);
    $info = $pFTPBackup->getFTPBackupById(1);
    $user = $info['user'];
    $password = $info['password'];
    $host = $info['server'];
    $port = $info['port'];
    $path = $info['pathServer'];

    $files_names = $pFTPBackup->getExternalNames($user, $password, $host, $port, $path);
     if($lista == 'droptrue2' & $array[0] == 'out')
        echo $arrLang["Error Drag Drop"];
     else{
        if(!$files_names)
            echo $arrLang["Error to request"];
        else{
            if(!$array[1])
                echo $arrLang["Error Drag Drop"];
            else{
                if($files_names == 'empty'){
                    $local_file = $array[1];
                    $remote_file = $array[1];
                    $val = $pFTPBackup->uploadFile($local_file,$remote_file,$user, $password, $host, $port, $path);
                    if ($val) {
                        echo $arrLang["Successfully uploaded"]." $local_file\n";
                    } else {
                        echo $arrLang["Problem uploading"]." $local_file\n";
                    }
                }
                else{
                    $local_file = $array[1];
                    $remote_file = $array[1];
                    $val = $pFTPBackup->uploadFile($local_file,$remote_file,$user, $password, $host, $port, $path);
                    if ($val) {
                        echo $arrLang["Successfully uploaded"]." $local_file\n";
                    } else {
                        echo $arrLang["Problem uploading"]." $local_file\n";
                    }
                }
            }
        }
    }
}

function file_download_FTPServer($module_name, $arrLang, $arrConf, &$pDB)
{
    $dir = $arrConf['dir'];
    $file    = getParameter('file');
    $lista   = getParameter('lista'); //identifica en que lista se hace el drop

    $array = obtainList($file);
    $pFTPBackup = new paloSantoFTPBackup($pDB);
    $info = $pFTPBackup->getFTPBackupById(1);
    $user = $info['user'];
    $password = $info['password'];
    $host = $info['server'];
    $port = $info['port'];
    $path = $info['pathServer'];

    $local_files = $pFTPBackup->obtainFiles($dir);

    if($lista == 'droptrue' & $array[0] == 'inn')
        echo $arrLang["Error Drag Drop"];
    else{
        if(!$local_files)
            echo $arrLang["Error to request"];
        else{
            if(!$array[1])
                echo $arrLang["Error Drag Drop"];
            else{
                if($local_files == 'empty'){
                    $local_file = $array[1];
                    $remote_file = $array[1];
                    $val = $pFTPBackup->downloadFile($local_file,$remote_file,$user, $password, $host, $port, $path);
                    if ($val) {
                        echo $arrLang["Successfully written"]." to $local_file\n";
                    } else {
                        echo $arrLang["Problem downloading"]." $local_file\n";
                    }
                }
                else{
                    $local_file = $array[1];
                    $remote_file = $array[1];
                    $val = $pFTPBackup->downloadFile($local_file,$remote_file,$user, $password, $host, $port, $path);
                    if ($val) {
                        echo $arrLang["Successfully written"]." to $local_file\n";
                    } else {
                        echo $arrLang["Problem downloading"]." $remote_file\n";
                    }
                }
            }
        }
    }
}

function getListUp($fileUP, $fileRemote){// fileUp toda la sita que se envia
    $up = "";
    $i = 0;
    $k = 0;
    $repetidos = "";
    $fileUP = array_unique($fileUP);
    for($j=0; $j<count($fileUP); $j++){
        if(!in_array($fileUP[$j],$fileRemote)){
            $up[$i] = $fileUP[$j];
            $i++;
        }else {
            if(filesRepeted($fileUP[$j],$fileRemote) > 0){
                $repetidos[$k] = $fileUP[$j];
                $k++;
            }
        }
    }
    $sal[0] = $i;
    $sal[1] = $up;
    $sal[2] = $repetidos;
    return $sal;
}

function filesRepeted($filename,$fileRemote){
    $i=0;
    $j=0;
    for($i=0; $i<count($fileRemote); $i++){
        if(in_array($filename,$fileRemote))
            $j++;
    }
    return $j;
}

function obtainList($fileString)
{
    $token = strtok($fileString, "_");
    $out = "";
    $i = 0;
    while ($token != false)
    {
        $out[$i] = $token;
        $token = strtok(";");
        $i++;
    }
    return $out;
}
/******************************************************************************************/

function createFieldForm($arrLang)
{
    $arrOptions = array('val1' => 'Value 1', 'val2' => 'Value 2', 'val3' => 'Value 3');

    $arrFields = array(
            "server"   => array(      "LABEL"                  => $arrLang["Server FTP"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "port"   => array(      "LABEL"                  => $arrLang["Port"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "user"   => array(      "LABEL"                  => $arrLang["User"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "password"   => array(      "LABEL"                  => $arrLang["Password"],
                                            "REQUIRED"               => "si",
                                            "INPUT_TYPE"             => "PASSWORD",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "local"   => array(      "LABEL"                  => $arrLang["Local"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "server_ftp"   => array(      "LABEL"                  => $arrLang["Server FTP"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "pathServer"   => array(     "LABEL"                  => $arrLang["Path Server FTP"],
                                            "REQUIRED"               => "no",
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
    if      (isset($_POST["delete_backup"])) return "delete_backup";
    else if (isset($_POST["backup"])) return "backup";
    else if (isset($_POST["submit_restore"])) return "submit_restore";
    else if (isset($_POST["process"]) && $_POST["option_url"]=="backup")  return  "process_backup";
    else if (isset($_POST["process"]) && $_POST["option_url"]=="restore") return  "process_restore";

/******************************* PARA FTP BACKUP *************************************/
    else if (isset($_POST["upload"])) return "upload";
    else if (getParameter("action")=="download_file") return "download_file";
    else if (isset($_POST["ftp_backup"])) return "ftp_backup";
    else if (isset($_POST["save_new_FTP"])) return "save_new_FTP";
    else if (isset($_POST["view_form_FTP"])) return "view_form_FTP";
/************************* POPUP DE DETALES DE FTP_BACKUP ****************************/
    else if (getParameter("action")=="detail") return "detail";
/****************************** PARA BACKUP AUTOMATICO ********************************/
    else if (isset($_POST["automatic"])) return "automatic";
/**************************************************************************************/
/****************************** PARA EL CONTROL AJAX **********************************/
    else if (getParameter("action") == "uploadFTPServer") return "uploadFTPServer";
    else if (getParameter("action") == "downloadFTPServer") return "downloadFTPServer";
/**************************************************************************************/
    else return "report_backup_restore";

}
?>