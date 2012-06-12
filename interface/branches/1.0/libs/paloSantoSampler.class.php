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
  $Id: paloSantoSampler.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */
if (!extension_loaded('sqlite3')) dl('sqlite3.so');
class paloSampler {

    var $rutaDB;
    var $errMsg;
    var $_db;

    function paloSampler()
    {
        $this->rutaDB = "/var/www/db/samples.db";
        //instanciar clase paloDB
        $pDB = new paloDB("sqlite3:///".$this->rutaDB);
	    if(!empty($pDB->errMsg)) {
        	echo "$pDB->errMsg <br>";
		}else{
			$this->_db = $pDB;
		}
		
    }

    function insertSample($idLine, $timestamp, $value)
    {
        $this->errMsg='';
        $sqliteError = '';
        $query = "INSERT INTO samples (id_line, timestamp, value) values ($idLine, '$timestamp', '$value')";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
        	$this->errMsg = $this->_db->errMsg;
        }

    }

    function getSamplesByLineId($idLine) 
    {
        $this->errMsg='';
        $sqliteError='';
        $arrReturn = array();
        if ($db = sqlite3_open($this->rutaDB)) {
            $query = "SELECT timestamp, value FROM samples WHERE id_line='$idLine'";
            $result = @sqlite3_query($db, $query);
            while ($row = sqlite3_fetch_array($result)) {
                //$arrReturn[$row['timestamp']]=$row['value'];
                $arrReturn[]=$row;
            }

        } else {
            $this->errMsg = $sqliteError;
        }
        return $arrReturn;
    }

    function getGraphLinesById($idGraph)
    {
        $this->errMsg='';
        $arrReturn=array();
        $sqliteError='';
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "SELECT l.id as id, l.name as name, l.color as color, l.line_type as line_type ";
            $query .= " FROM graph_vs_line as gl, line as l WHERE gl.id_line=l.id AND gl.id_graph='$idGraph'";
            $result = @sqlite3_query($db, $query);
            if ($result!= FALSE){
                while ($row = sqlite3_fetch_array($result)) {
                    $arrReturn[]=$row;
                }
            }
            else //mostrar un mensaje descriptivo
                $this->errMsg = "It was not possible to obtain information about the graph";
        } else {
            $this->errMsg = $sqliteError;
        }
        return $arrReturn;
    }

    function getGraphById($idGraph)
    {
        $this->errMsg='';
        $sqliteError='';
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "SELECT name FROM graph WHERE id='$idGraph'";
            $result = @sqlite3_query($db, $query);
            if ($result!= FALSE){
                while ($row = sqlite3_fetch_array($result)) {
                    $arrReturn=$row;
                }
            }
            else
                $this->errMsg = "It was not possible to obtain information about the graph";
        } else {
            $this->errMsg = $sqliteError;
        }

        return $arrReturn;
    }

    function deleteDataBeforeThisTimestamp($timestamp)
    {
        $this->errMsg='';
        $sqliteError='';
        if(empty($timestamp)) return false;
        $query = "DELETE FROM samples WHERE timestamp<=$timestamp";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
        	$this->errMsg = $this->_db->errMsg;
        	return false;
        }
		return true;
    }
}
?>
