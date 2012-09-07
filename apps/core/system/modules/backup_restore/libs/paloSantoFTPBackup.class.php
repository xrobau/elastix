<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-1                                               |
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
  $Id: paloSantoFTPBackup.class.php,v 1.1 2009-09-07 10:09:02 Eduardo Cueva ecueva@palosanto.com Exp $ */
class paloSantoFTPBackup {
    var $_DB;
    var $errMsg;

    function paloSantoFTPBackup(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    /*HERE YOUR FUNCTIONS*/

    function connectFTP($user, $password, $host, $port){
    //Permite conectarse al Servidor FTP
        $id_ftp=ftp_connect($host,$port); //Obtiene un manejador del Servidor FTP
        if (!$id_ftp) {
            return 1;
        }else{
            $login = ftp_login($id_ftp,$user,$password); //Se loguea al Servidor FTP
            if (!$login) {
                return 2;
            }else
                ftp_pasv($id_ftp,true); //Establece el modo de conexión true modo pasivo
        }
        return $id_ftp; //Devuelve el manejador a la función
    }

    function uploadFile($local_file,$remote_file,$user, $password, $host, $port, $path){
        //Sube archivo de la maquina Cliente al Servidor (Comando PUT)
        $id_ftp=$this->connectFTP($user, $password, $host, $port); //Obtiene un manejador y se conecta al Servidor FTP 
        if($id_ftp == 1 || $id_ftp == 2)    return $id_ftp;
        $local_file = '/var/www/backup/'.$remote_file;
        ftp_chdir($id_ftp, $path);  // se cambia de directorio
        $val = ftp_put($id_ftp,$remote_file,$local_file,FTP_BINARY);
        //Sube un archivo al Servidor FTP en modo Binario
        ftp_quit($id_ftp); //Cierra la conexion FTP
        return $val;
    }

    function downloadFile($local_file,$remote_file,$user, $password, $host, $port, $path){
        $id_ftp=$this->connectFTP($user, $password, $host, $port);
        if($id_ftp == 1 || $id_ftp == 2)    return $id_ftp;
        $local_file = '/var/www/backup/'.$local_file;
        $handle = fopen($local_file, 'w');
        ftp_chdir($id_ftp, $path);
        $val = ftp_fget($id_ftp,$handle,$remote_file,FTP_BINARY,0);
        fclose($handle);
        ftp_quit($id_ftp);
        return $val;
    }

    function getExternalNames($user, $password, $host, $port, $path){
        // permite conectarse y obtener los nombres de los archivos externos
        // get contents of the current directory
        $id_ftp=$this->connectFTP($user, $password, $host, $port);
        if($id_ftp == 1 || $id_ftp == 2)    return $id_ftp;
        ftp_chdir($id_ftp, $path);
        $contents = ftp_nlist($id_ftp, "-la .");
        ftp_quit($id_ftp);
        $new_contents = $this->getOnlyTar($contents);
        if(!$new_contents) return 'empty';
        return $new_contents;
    }

    function getOnlyTar($contents){
        $new_list ="";
        $j = 0;
        for($i=0 ; $i<count($contents); $i++){
            $band=strpos($contents[$i],".tar");
            $content = explode(" ",$contents[$i]);
            $size = count($content);
            $file = "";
            if($size > 1 && is_array($content)){
                $file = $content[$size-1];
            }else{
                $file = $content[$size];
            }
            if($band != false){
                if($file=="")
                    $new_list[$j] = $contents[$i];
                else
                    $new_list[$j] = $file;
                $j++;
            }
        }
        return $new_list;
    }

    function ObtainLink($user, $password, $host, $port){
        //Obriene ruta del directorio del Servidor FTP (Comando PWD)
        $id_ftp=$this->connectFTP($user, $password, $host, $port); //Obtiene un manejador y se conecta al Servidor FTP 
        if($id_ftp == 1 || $id_ftp == 2)    return $id_ftp;
        $Directorio=ftp_pwd($id_ftp); //Devuelve ruta actual p.e. "/home/willy"
        ftp_quit($id_ftp); //Cierra la conexion FTP
        return $Directorio; //Devuelve la ruta a la función
    }

    //obtiene lo asrchivos locales
    function obtainFiles($dir){
        $files =  glob($dir."/{*.tar}",GLOB_BRACE);
        $array = array();
        $names ="";
        foreach ($files as $ima)
            $names[]=array_pop(explode("/",$ima));
        if(!$names) return $array;
        return $names;
    }

    function updateData($server, $port, $user, $password, $path)
    {
        $query = "UPDATE serverFTP 
                  SET server='$server', 
                      port=$port, 
                      user='$user', 
                      password='$password',
                      pathServer='$path'
                  WHERE id = 1";
        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function insertData($server, $port, $user, $password, $path)
    {
        $query = "INSERT INTO serverFTP(server,port,user,password,pathServer) VALUES('$server',$port,'$user','$password','$path');";
        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    ///////////////////////////////


    function getNumFTPBackup($filter_field, $filter_value)
    {
        $where = "";
        if(isset($filter_field) & $filter_field !="")
            $where = "where $filter_field like '$filter_value%'";

        $query   = "SELECT COUNT(*) FROM table $where";

        $result=$this->_DB->getFirstRowQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getFTPBackup($limit, $offset, $filter_field, $filter_value)
    {
        $where = "";
        if(isset($filter_field) & $filter_field !="")
            $where = "where $filter_field like '$filter_value%'";

        $query   = "SELECT * FROM table $where LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getFTPBackupById($id)
    {
        $query = "SELECT * FROM serverFTP WHERE id=$id";

        $result=$this->_DB->getFirstRowQuery($query,true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function getStatusAutomaticBackupById()
    {
        $query = "SELECT status FROM automatic_backup WHERE id=1";

        $result=$this->_DB->getFirstRowQuery($query,true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function updateStatus($status)
    {
        $query = "UPDATE automatic_backup 
                  SET status='$status'
                  WHERE id = 1";
        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function insertStatus($status)
    {
        $query = "INSERT INTO automatic_backup(status) VALUES('$status');";
        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }
    
    function createCronFile($time)
    {
        $time = strtolower($time);
        if (!in_array($time, array('daily', 'monthly', 'weekly'))) $time = 'off';
        $sComando = '/usr/bin/elastix-helper backupengine --autobackup '.$time;
        $output = $retval = NULL;
        exec($sComando, $output, $retval);
        if ($retval != 0) {
        	$this->errMsg = _tr('Unabled write file').' - '.implode("\n", $output);
            return FALSE;
        }
        return TRUE;
    }
}
?>