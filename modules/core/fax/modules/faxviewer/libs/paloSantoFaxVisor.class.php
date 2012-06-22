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
  $Id: paloSantoFaxVisor.class.php,v 1.1.1.1 2008/12/09 18:00:00 aflores Exp $ */

/*-
CREATE TABLE info_fax_recvq
(
    id           INTEGER  PRIMARY KEY,
    pdf_file    varchar(255)   NOT NULL DEFAULT '',
    modemdev     varchar(255)   NOT NULL DEFAULT '',
    status       varchar(255)   NOT NULL DEFAULT '',
    commID       varchar(255)   NOT NULL DEFAULT '',
    errormsg     varchar(255)   NOT NULL DEFAULT '',
    company_name varchar(255)   NOT NULL DEFAULT '',
    company_fax  varchar(255)   NOT NULL DEFAULT '',
    fax_destiny_id       INTEGER NOT NULL DEFAULT 0,
    date     timestamp  NOT NULL ,
    FOREIGN KEY (fax_destiny_id)   REFERENCES fax(id)
);
*/

class paloFaxVisor {

    var $dirIaxmodemConf;
    var $dirHylafaxConf;
    var $rutaDB;
    var $firstPort;
    var $rutaFaxDispatch;
    var $rutaInittab;
    var $usuarioWeb;
    var $grupoWeb;
    var $_db;
    var $errMsg;

    function paloFaxVisor()
    {
        global $arrConf;
        
        $this->dirIaxmodemConf = "/etc/iaxmodem";
        $this->dirHylafaxConf  = "/var/spool/hylafax/etc";
        $this->rutaDB = "$arrConf[elastix_dbdir]/fax.db";
        $this->firstPort=40000;
        $this->rutaFaxDispatch = "/var/spool/hylafax/etc/FaxDispatch";
        $this->rutaInittab = "/etc/inittab";
        $this->usuarioWeb = "asterisk";
        $this->grupoWeb   = "asterisk";
        //instanciar clase paloDB
        $pDB = new paloDB("sqlite3:///".$this->rutaDB);
    	if(!empty($pDB->errMsg)) {
            echo "$pDB->errMsg <br>";
    	}else{
       		$this->_db = $pDB;
    	}
    }

    function obtener_faxes($company_name,$company_fax,$fecha_fax,$offset,$cantidad,$type)
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn=array();
        //if ($db = sqlite3_open($this->rutaDB)) {

        $str = " ";
        if( $type == 'in' || $type == 'IN' || $type == 'In' ) $str = " AND type='in'";
        else if( $type == 'out' || $type == 'OUT' || $type == 'Out' ) $str = " AND type='out'";

        $query = "SELECT r.id,r.pdf_file,r.modemdev,r.commID,r.errormsg,r.company_name,r.company_fax,r.fax_destiny_id,r.date,r.type,r.faxpath, f.name destiny_name,f.extension destiny_fax ".
                 "FROM info_fax_recvq r inner join fax f on f.id = r.fax_destiny_id ".
                 "WHERE company_name like '%$company_name%' and company_fax like '%$company_fax%' and date like '%$fecha_fax%' $str ".
                 "order by r.id desc ".
                 "limit $cantidad offset $offset";

        $arrReturn = $this->_db->fetchTable($query, true);
        if($arrReturn==FALSE)
        {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
/*
            $result = @sqlite3_query($db, $query);
            if(count($result)>0){
                while ($row = @sqlite3_fetch_array($result)) {
                    $arrReturn[]=$row;
                }
            }
        } 
        else 
        {
            $errMsg = $sqliteError;
         }*/

        return $arrReturn;
    }

    function obtener_fax($idFax)
    {
        $arrReturn=array();
        $query = "SELECT * FROM info_fax_recvq WHERE id=$idFax";

        $arrReturn = $this->_db->getFirstRowQuery($query, true);
        if($arrReturn==FALSE){
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
    }

    function obtener_cantidad_faxes($company_name,$company_fax,$fecha_fax,$type)
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn = -1;
        //if ($db = sqlite3_open($this->rutaDB)) {

        $str = " ";
        if( $type == 'in' || $type == 'IN' || $type == 'In' ) $str = " AND type='in'";
        else if( $type == 'out' || $type == 'OUT' || $type == 'Out' ) $str = " AND type='out'";

        $query  = "SELECT count(*) cantidad ".
                  "FROM (SELECT pdf_file,modemdev,commID,errormsg,company_name,company_fax,fax_destiny_id,date ".
                        "FROM info_fax_recvq ".
                        "WHERE company_name like '%$company_name%' and company_fax like '%$company_fax%' and date like '%$fecha_fax%' $str)";

        $arrReturn = $this->_db->getFirstRowQuery($query, true);
        if($arrReturn==FALSE)
        {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
/*
            $result = @sqlite3_query($db, $query);
            if(count($result)>0){
                while ($row = @sqlite3_fetch_array($result)) {
                    $arrReturn=$row['cantidad'];
                }
            }
        } 
        else 
        {
            $errMsg = $sqliteError;
        }
*/
        return $arrReturn['cantidad'];
    }

    function deleteInfoFaxFromDB($pdfFileInfoFax) {
        $query  = "DELETE FROM info_fax_recvq WHERE pdf_file='$pdfFileInfoFax'";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function updateInfoFaxFromDB($idFax, $company_name, $company_fax) {
        $query  = "UPDATE info_fax_recvq set
                    company_name='$company_name',
                    company_fax='$company_fax'
                   WHERE id=$idFax";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function updateFileFaxSend($oldfile, $newfile)
    {
        $query  = "UPDATE info_fax_recvq set
                   pdf_file='$newfile' WHERE pdf_file='$oldfile'";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;

    }

    function testFile($file)
    {
        $temp_file = "";
        $return = "";
        exec("sudo -u root chmod 777 /var/spool/hylafax/docq/$file",$arrConsole,$flagStatus);
        if($flagStatus==0){
			exec("ls /var/spool/hylafax/docq/$file",$arrConsole2,$flagStatus);
            if($flagStatus == 0){ //existe por lo tanto ya esta completo
                $temp_file = basename($arrConsole2[0],".ps");
                if($this->updateFileFaxSend($file, $temp_file.".pdf"))
                    $return = $temp_file.".pdf";
                else
                    $return = "";
            }
        }
        exec("sudo -u root chmod 740 /var/spool/hylafax/docq/$file",$arrConsole,$flagStatus);
        return $return;
    }

    function deleteInfoFaxFromPathFile($path_file){
       $path = "/var/www/faxes";
       $file = "$path/$path_file/fax.pdf";

       if(file_exists($file)){
	    unlink($file);
	    return true;
       }		
       return false;
    }

    function getPathByPdfFile($pdfFile){
        $arrReturn= "";
        $query = "SELECT faxpath FROM info_fax_recvq WHERE pdf_file='$pdfFile'";
        $arrReturn = $this->_db->getFirstRowQuery($query, true);
        if($arrReturn['faxpath']==""){
            $this->errMsg = $this->_db->errMsg;
            return "";
        }else
        	return $arrReturn['faxpath'];
    }

	//function createFolder()
	

}
?>
