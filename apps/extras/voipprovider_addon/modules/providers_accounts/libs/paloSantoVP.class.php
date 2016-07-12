<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4                                               |
  | http://www.elastix.com                                               |
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
  $Id: paloSantoVP.class.php,v 1.2 2010-11-29 15:09:50 Alberto Santos asantos@palosanto.com Exp $ */

require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
class paloSantoVP {
    var $_DB;
    var $errMsg;

    function paloSantoVP(&$pDB)
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

    function saveTrunk($data)
    {
        $id = $this->getIdNextTrunk();
        if($data[17] == "SIP"){
            $tech = "sip";
            $register = "$data[1]:$data[2]@$data[7]/$data[1]";
        }
        else{
            $tech = "iax";
            $register = "$data[1]:$data[2]@$data[7]";
        }

        $arrParam = array($id,$data[0],$tech,$data[0],$data[3]);
        $query = "insert into trunks (trunkid,name,tech,keepcid,channelid,disabled,usercontext,provider,outcid) values (?,?,?,'off',?,'off','','',?)";
        $result = $this->_DB->genQuery($query, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        $arrDataTech = $this->getDataTech($data);
        $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,?)";
        foreach($arrDataTech as $key => $value){
            $arrParam = array("tr-peer-$id",$key,$value['data'],$value['flag']);
            $result = $this->_DB->genQuery($query, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,0)";
        $arrParam = array("tr-reg-$id","register",$register);
        $result = $this->_DB->genQuery($query, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function getIdNextTrunk()
    {
        $query = "select max(trunkid) as id from trunks";
        $result=$this->_DB->getFirstRowQuery($query,true);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return 1 + $result['id'];
    }

    function updateTrunk($data,$id_trunk)
    {
        $oldTech = $this->getTechbyId($id_trunk);
        if($data[17] == "SIP"){
            $tech = "sip";
            $register = "$data[1]:$data[2]@$data[7]/$data[1]";
        }
        else{
            $tech = "iax";
            $register = "$data[1]:$data[2]@$data[7]";
        }

        $arrParam = array($data[0],$tech,$data[0],$data[3],$id_trunk);
        $query = "update trunks set name = ?, tech = ?, channelid = ?, outcid = ? where trunkid = ?";
        $result = $this->_DB->genQuery($query, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        $arrDataTech = $this->getDataTech($data);

        //si no se ha cambiado la tecnología de sip a iax o viceversa, entonces hay que hacer los update o insert o delete dependiendo del caso
        if($oldTech == $tech){
            $query = "select keyword from $tech where id=?";
            $arrParam = array("tr-peer-$id_trunk");
            $result=$this->_DB->fetchTable($query, true, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }

            //Lazo para determinar si el keyword del arreglo de datos existe en la base, de ser asi hace un update y si no está en la base
            //lo inserta
            foreach($arrDataTech as $key => $value){
                $flag = 0;
                foreach($result as $key2 => $value2){
                    if($value2['keyword'] == $key){
                        $query = "update $tech set data=? where id=? and keyword=?";
                        $arrParam = array($value['data'],"tr-peer-$id_trunk",$key);
                        $result2 = $this->_DB->genQuery($query, $arrParam);
                        if($result2==FALSE){
                            $this->errMsg = $this->_DB->errMsg;
                            return false;
                        }
                        $flag = 1;
                        break;
                    }
                }
                if($flag == 0){
                    $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,?)";
                    $arrParam = array("tr-peer-$id_trunk",$key,$value['data'],$value['flag']);
                    $result2 = $this->_DB->genQuery($query, $arrParam);
                    if($result2==FALSE){
                        $this->errMsg = $this->_DB->errMsg;
                        return false;
                    }
                }
            }
    
            //Lazo para determinar si el keyword en la base existe en el arreglo de datos, de no ser asi, lo elimina de la base
            foreach($result as $key => $value){
                $flag = 0;
                foreach($arrDataTech as $key2 => $value2){
                    if($value['keyword'] == $key2){
                        $flag = 1;
                        break;
                    }
                }
                if($flag == 0){
                    $query = "delete from $tech where id=? and keyword=?";
                    $arrParam = array("tr-peer-$id_trunk",$value['keyword']);
                    $result2 = $this->_DB->genQuery($query, $arrParam);
                    if($result2==FALSE){
                        $this->errMsg = $this->_DB->errMsg;
                        return false;
                    }
                }
            }
    
            $query = "update $tech set data=? where id=?";
            $arrParam = array($register,"tr-reg-$id_trunk");
            $result = $this->_DB->genQuery($query, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        //si cambio la tecnología entonces hay que borrar la información que había en esa troncal en la tabla de la tecnología anterior y hacer los inserts en la tabla de la nueva tecnología
        else{
            $query = "delete from $oldTech where id like ?";
            $arrParam = array("tr-%-$id_trunk");
            $result = $this->_DB->genQuery($query, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }

            $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,?)";
            foreach($arrDataTech as $key => $value){
                $arrParam = array("tr-peer-$id_trunk",$key,$value['data'],$value['flag']);
                $result = $this->_DB->genQuery($query, $arrParam);
                if($result==FALSE){
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
            }
            $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,0)";
            $arrParam = array("tr-reg-$id_trunk","register",$register);
            $result = $this->_DB->genQuery($query, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        return true;
    }

    function disableTrunk($id_trunk,$disable)
    {
        $query = "update trunks set disabled = ? where trunkid = ?";
        $arrParam = array($disable,$id_trunk);
        $result = $this->_DB->genQuery($query, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }

    function trunkExists($id_trunk)
    {
	$arrParam = array($id_trunk);
	$query = "select * from trunks where trunkid = ?";
	$result = $this->_DB->fetchTable($query, true, $arrParam);
	if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
	if(count($result)==0)
	    return false;
	return true;
    }

    function deleteTrunk($id_trunk)
    {
        $tech = $this->getTechbyId($id_trunk);
        if($tech == "iax2")
            $tech = "iax";
        $query = "delete from trunks where trunkid = ?";
        $arrParam = array($id_trunk);
        $result = $this->_DB->genQuery($query, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($tech){
            $query = "delete from $tech where id like ?";
            $arrParam = array("tr-%-$id_trunk");
            $result = $this->_DB->genQuery($query, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        return true;
    }

    function getTechbyId($id_trunk)
    {
        $query = "select tech from trunks where trunkid = ?";
        $arrParam = array($id_trunk);
        $result = $this->_DB->getFirstRowQuery($query, true, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result['tech'];
    }

    function do_reloadAll($data_connection, $arrAST, $arrAMP) 
    {
        $bandera = true;

        if (isset($arrAMP["PRE_RELOAD"]['valor']) && !empty($arrAMP['PRE_RELOAD']['valor'])){
            exec( $arrAMP["PRE_RELOAD"]['valor']);
        }

        //para crear los archivos de configuracion en /etc/asterisk
        $retrieve = $arrAMP['AMPBIN']['valor'].'/retrieve_conf';
        exec($retrieve);

        //reload MOH to get around 'reload' not actually doing that, reload asterisk
        $command_data = array("moh reload", "reload");
        $arrResult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);

        if (isset($arrAMP['FOPRUN']['valor'])) {
            //bounce op_server.pl
            $wOpBounce = $arrAMP['AMPBIN']['valor'].'/bounce_op.sh';
            exec($wOpBounce.' &>'.$arrAST['astlogdir']['valor'].'/freepbx-bounce_op.log');
        }

        //store asterisk reloaded status
        $sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'";
        if(!$this->_DB->genQuery($sql))
        {
            $this->errMsg = $this->_DB->errMsg;
            $bandera = false;
        }

        if (isset($arrAMP["POST_RELOAD"]['valor']) && !empty($arrAMP['POST_RELOAD']['valor']))  {
            exec( $arrAMP["POST_RELOAD"]['valor']);
        }

        if(!$bandera) return false;
        else return true;
    }

    function AsteriskManager_Command($host, $user, $password, $command_data) 
    {
        $salida = array();
        $astman = new AGI_AsteriskManager();
        //$salida = array();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = _tr("Error when connecting to Asterisk Manager");
        } else{
            foreach($command_data as $key => $valor)
                $salida = $astman->send_request('Command', array('Command'=>"$valor"));

            $astman->disconnect();
            $salida["Response"] = isset($salida["Response"])?$salida["Response"]:"";
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

    function getDataTech($data)
    {
        $dataTech['account']['data'] = $data[0];
        $dataTech['account']['flag'] = 2;
        $dataTech['host']['data'] = $data[7];
        $dataTech['host']['flag'] = 3;
        $dataTech['username']['data'] = $data[1];
        $dataTech['username']['flag'] = 4;
        $dataTech['secret']['data'] = $data[2];
        $dataTech['secret']['flag'] = 5;
        $dataTech['type']['data'] = $data[4];
        $dataTech['type']['flag'] = 6;
        $dataTech['qualify']['data'] = $data[5];
        $dataTech['qualify']['flag'] = 7;
        $dataTech['insecure']['data'] = $data[6];
        $dataTech['insecure']['flag'] = 8;
        if($data[8] != ""){
            $dataTech['fromuser']['data'] = $data[8];
            $dataTech['fromuser']['flag'] = 9;
        }
        if($data[9] != ""){
            $dataTech['fromdomain']['data'] = $data[9];
            $dataTech['fromdomain']['flag'] = 10;
        }
        $dataTech['dtmfmode']['data'] = $data[10];
        $dataTech['dtmfmode']['flag'] = 11;
        if($data[11] != ""){
            $dataTech['disallow']['data'] = $data[11];
            $dataTech['disallow']['flag'] = 12;
        }
        if($data[12] != ""){
            $dataTech['context']['data'] = $data[12];
            $dataTech['context']['flag'] = 13;
        }
        if($data[13] != ""){
            $dataTech['allow']['data'] = $data[13];
            $dataTech['allow']['flag'] = 14;
        }
        $dataTech['trustrpid']['data'] = $data[14];
        $dataTech['trustrpid']['flag'] = 15;
        $dataTech['sendrpid']['data'] = $data[15];
        $dataTech['sendrpid']['flag'] = 16;
        $dataTech['canreinvite']['data'] = $data[16];
        $dataTech['canreinvite']['flag'] = 17;
        return $dataTech;
    }
}

?>