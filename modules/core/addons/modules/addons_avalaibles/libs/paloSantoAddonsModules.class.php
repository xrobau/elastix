<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-15                                               |
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
  $Id: paloSantoAddonsModules.class.php,v 1.1 2010-03-06 11:03:53 Eduardo Cueva ecueva@palosanto.com Exp $ */
class paloSantoAddonsModules {
    var $_DB;
    var $errMsg;

    function paloSantoAddonsModules(&$pDB)
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

    function getNumAddonsInstalled()
    {
        $query   = "SELECT COUNT(*) FROM addons";

        $result=$this->_DB->getFirstRowQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    // Procedimiento privado para refrescar la versión instalada del módulo
    private function _refrescarVersionInstaladaAddons()
    {
        $result = $this->_DB->fetchTable('SELECT name_rpm, version, release FROM addons', TRUE);
        if (is_array($result)) {
            foreach ($result as $tupla) {
                $salida = $result = NULL;
                exec("rpm -qi {$tupla['name_rpm']}", $salida, $exitcode);
                if ($exitcode == 0) {
                    // Se busca la versión y release de la instalación
                    $sVersion = $sRelease = NULL;
                    foreach ($salida as $sLinea) {
                        $regs = NULL;
                        if (preg_match('/^Version\s+:\s+(\S+)/', $sLinea, $regs)) {
                            $sVersion = $regs[1];
                        }
                        if (preg_match('/^Release\s+:\s+(\S+)/', $sLinea, $regs)) {
                            $sRelease = $regs[1];
                        }
                    }
                    if ($sVersion != $tupla['version'] || $sRelease != $tupla['release']) {
                        $this->_DB->genQuery('UPDATE addons SET version = ?, release = ? WHERE name_rpm = ?',
                            array($sVersion, $sRelease, $tupla['name_rpm']));
                    }
                } else {
                    // Probablemente el paquete no está instalado ya, se borra...
                    $this->_DB->genQuery('DELETE FROM addons WHERE name_rpm = ?', array($tupla['name_rpm']));
                }
            }
        }
    }

    function getAddonsInstalled($limit, $offset)
    {
        $this->_refrescarVersionInstaladaAddons();
        $query   = "SELECT * FROM addons LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getAddonsInstalledALL()
    {
        $this->_refrescarVersionInstaladaAddons();
        $query   = "SELECT * FROM addons";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getStatus($arrConf)
    {
        $salida = NULL;
        $status = $this->statusAddon($arrConf);
        if($status!=null){
            $arrStatus = explode("\n",$status);
            $porcent_total_all = 0;
            $porcent_downl_all = 0;
            $salida = array(
                'errmsg' => array(),
                'warnmsg' => array(),
            );
            foreach($arrStatus as $k => $line){
                $arrLine = explode(" ",$line);
                if ($arrLine[0] == 'errmsg') {
                    array_shift($arrLine);
                    $salida['errmsg'][] = implode(' ', $arrLine);
                }
                else if ($arrLine[0] == 'warnmsg') {
                    array_shift($arrLine);
                    $salida['warnmsg'][] = implode(' ', $arrLine);
                }
                else if($arrLine[0]=="status") $salida['status'] = $arrLine[1];

                else if($arrLine[0]=="action")  $salida['action'] = $arrLine[1];

                else if(isset($salida['action']) &&  ($salida['action']  == "confirm" || $salida['action']  == "downloading" || $salida['action']  == "applying")){
                    if($arrLine[0]=="package"){
                        if($arrLine[1] == "install" || $arrLine[1] == "update"){
                            $porcent_downl_all += $arrLine[4];
                            $porcent_total_all += $arrLine[3];
                            $salida['package'][] = array(
                                "action"    => $arrLine[1], //if is install or update
                                "name"      => $arrLine[2], //name's package
                                "lon_total" => $arrLine[3], //size in bytes
                                "lon_downl" => $arrLine[4], //size download in bytes
                                "status_pa" => $arrLine[5], //status package
                                "porcent_ins" => number_format($arrLine[4]*100/$arrLine[3],0), //percent
                            );
                        }
                        else if($arrLine[1] == "remove"){
                            $salida['package'][] = array(
                                "action"    => "remove", //if is install or update
                                "name"      => $arrLine[2], //name's package
                                "version"   => $arrLine[3],
                                "status_pa" => $arrLine[5], //status package
                            );
                        }
                    }
                }
                else if(isset($salida['action']) && ($salida['action']  == "checkinstalled")){
                    if($arrLine[0]=="installed"){
                        $salida['installed'][] = array(
                            "name"    => $arrLine[1], //name's package
                            "arch"    => $arrLine[2],
                            "epoch"   => $arrLine[3],
                            "version" => $arrLine[4],
                            "release" => $arrLine[5],
                        );
                    }
                }
            }

            if(isset($salida['action']) && ($salida['action']  == "confirm" || $salida['action']  == "downloading" || $salida['action']  == "applying")){
                if($porcent_total_all!=0){
                    $totalShow =  number_format(($porcent_downl_all*100/$porcent_total_all),0);
                    $salida['porcent_total_ins'] = $totalShow;
                }
                else
                    $salida['porcent_total_ins'] = 0;
            }
        }
        return $salida;
    }

    function addAddon($arrConf, $addAddons)
    {
        return $this->commandAddons($arrConf, "add", $addAddons);
    }

    function updateAddon($arrConf, $updateAddons)
    {
        return $this->commandAddons($arrConf, "update", $updateAddons);
    }

    function removeAddon($arrConf, $removeAddons)
    {
        return $this->commandAddons($arrConf, "remove", $removeAddons);
    }

    function checkAddon($arrConf, $checkAddons)
    {
        return $this->commandAddons($arrConf, "check", $checkAddons);
    }

    function confirmAddon($arrConf)
    {
        return $this->commandAddons($arrConf, "confirm");
    }

    function clearAddon($arrConf)
    {
        return $this->commandAddons($arrConf, "clear");
    }

    function statusAddon($arrConf)
    {
        return $this->commandAddons($arrConf, "status");
    }

    function testAddAddon($arrConf, $addAddons)
    {
        return $this->commandAddons($arrConf, "testadd $addAddons");
    }

    private function commandAddons($arrConf, $cmd, $parameters = '')
    {
        $errno = $errstr = NULL;
        $hConn = @fsockopen($arrConf['socket_conn_ip'],$arrConf['socket_conn_port'],$errno,$errstr,$timeout=30);
        if (!$hConn) {
            $this->errMsg = "(internal) Cannot connect to updater daemon - ($errno) $errstr";
            return NULL;
        }
        $sSalida = $this->_consumirStatus($hConn);
        if ($cmd != 'status') {
            fputs($hConn, "$cmd $parameters\n");
            $sSalida = fgets($hConn);
        }
        fputs($hConn, "exit\n");
        fclose($hConn);
        return $sSalida;
    }

    private function _consumirStatus($hConn)
    {
        $sStatus = $sLinea = '';
        do {
            $sLinea = fgets($hConn);
            $sStatus .= $sLinea;
        } while ($sLinea != "end status\n");
        return $sStatus;
    }

    function insertAddons($name,$name_rpm,$version,$release)
    {
        $query = "INSERT INTO addons(name,name_rpm,version,release) VALUES('$name','$name_rpm','$version','$release')";
        $result = $this->_DB->genQuery($query);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }
    
    function updateAddons($name,$name_rpm,$version,$release, $id)
    {
    	$data = array($name,$name_rpm,$version,$release, $id);
        $query = "UPDATE addons set name = ? ,name_rpm = ?,version = ?,release = ? WHERE id = ?"; 
        $result = $this->_DB->genQuery($query, $data);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }

    /**
     * Procedimiento que verifica si un RPM de addon, descrito por $arrAddon,
     * está instalado en el sistema. Además, este método actualiza el caché de
     * los addons, agregando o quitando los registros del caché de addons.
     * 
     * @param array $arrAddon   Arreglo de información de addon que tiene los
     *                          siguientes elementos esperados:
     *                          name_rpm    Nombre formal del RPM
     *                          name        Nombre descriptivo del addon
     *                          version     Versión del addon (2.0.0)
     *                          release     Revisión del addon
     * @return bool VERDADERO si el addon existe, FALSO si no existe
     */
    function exitAddons($arrAddon)
    {

        $rpm = $this->rpms_Installed($arrAddon['name_rpm']);
		$version = $arrAddon['version']."-".$arrAddon['release'];

        // EXISTE EN EL SISTEMA
        if($rpm[$arrAddon['name_rpm']]){ //esta instalado en el sistema
            $query  = "SELECT id, version, release FROM addons WHERE name_rpm='$arrAddon[name_rpm]'";
            $result = $this->_DB->getFirstRowQuery($query,true);
            $versionInstalled = $result['version']."-".$result['release'];
            if(is_array($result) && count($result) > 0){//existe, en la base
                if($version===$versionInstalled)
                	return true; // esta instalado ...
                else{
            		if($this->updateAddons($arrAddon['name'],$arrAddon['name_rpm'],$arrAddon['version'],$arrAddon['release'],$result['id']))
                    	return true;
                	else{
                    	$this->errMsg = "Error esta actualizando en el sistema, pero no esta en la base, hay que actualizar en registro en la base";
                    	return true;
                	}
                }
            }else{ //no existe en la base, hay que insertarlo, xq si esta instalado en el sistema
                if($this->insertAddons($arrAddon['name'],$arrAddon['name_rpm'],$arrAddon['version'],$arrAddon['release']))
                    return true;
                else{
                    $this->errMsg = "Error esta instalado en el sistema, pero no esta en la base, hay que insertar en registro en la base";
                    return true;
                }
            }
        }
        // NO EXISTE EN EL SISTEMA
        else{
            $query  = "SELECT id, version, release FROM addons WHERE name_rpm='$arrAddon[name_rpm]'";
            $result = $this->_DB->getFirstRowQuery($query,true);
            if(is_array($result) && count($result) > 0){//existe, en la base
                $query  = "DELETE FROM addons WHERE name_rpm='$arrAddon[name_rpm]'";
                if($this->_DB->genQuery($query)) 
                    return false;
                else{
                    $this->errMsg = "Error no existe en el sistema, pero si en la base, hay que eliminarlo de la base.";
                    return true; // esto es un error
                }
            }
            return false; // no existe;
        }
    }

    function getCheckAddonsInstalled()
    {
        $arrResult = $this->getAddonsInstalledALL();
        $sal = "";
        if(isset($arrResult) && $arrResult!=""){
            foreach($arrResult as $key => $value){
               $valor0 = $value['name_rpm'];
               $valor1 = $value['version'];
               $valor2 = $value['release'];
               $sal .= $valor0."|".$valor1."|".$valor2.",";
            }
        }
        return $sal;
    }
    
    function idUpgraded($arrAddon)
    {
    	$rpm = $this->rpms_Installed($arrAddon['name_rpm']);
		$version = $arrAddon['name_rpm']."-".$arrAddon['version']."-".$arrAddon['release'];
		$result = array();
		if($rpm[$arrAddon['name_rpm']]){
			$exito = "";
			$name_rpm = $arrAddon['name_rpm'];
			$arrConsole = array();
			exec("rpm -q $name_rpm ",$arrConsole,$exito);
            $versionInstalled = trim($arrConsole[0]);
            exec("echo '$version===$versionInstalled' > /tmp/diff");
			if($version===$versionInstalled){
				$result['status'] = false;
				$result['old_version'] = $versionInstalled;
				$result['new_version'] = $version;
    			return $result;
			}else{
				$result['status'] = true;
				$result['old_version'] = $versionInstalled;
				$result['new_version'] = $version;
    			return $result;
			}
    	}else{
    		$result['status'] = false;
    		return $result;
    	}
    }

    function updateInDB($arrAddons)
    {
        $this->_DB->genQuery('UPDATE addons SET update_st = 0');
        foreach($arrAddons as $k => $name_rpm){
            $query = "update addons set update_st=1 where name_rpm='$name_rpm'";
            $result = $this->_DB->genQuery($query);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        return true; 
    }

    function rpms_Installed($addons)
    {
        $rpms = null;

        if(!empty($addons)){
            $arrAddons = explode(" ",$addons);
            foreach($arrAddons as $rpm){
                exec("rpm -q $rpm",$arrConsole,$exito);
                $rpms[$rpm] = $exito?"0":"1";
            }
        }
        return $rpms;
    }

    // This function fill tte data cache in database
    function fillDataCache($arr_packages, $arr_RPMsInfo)
    {
        // all information is deleted to fill th new data cache
        $query = "delete from addons_cache";
        $result = $this->_DB->genQuery($query);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
        }

        // the database is filled again with a new data cache
        for($i=0; $i<count($arr_packages)-1; $i++){
            $package = $arr_packages[$i];
            $status = $arr_RPMsInfo[$package]["status"];
            $observation = $arr_RPMsInfo[$package]["observation"];
            if($status == "OK")
                $status = 1;
            else
                $status = 0;
            $query = "insert into addons_cache(name_rpm, status, observation) values('$package', $status, '$observation')";
            $result = $this->_DB->genQuery($query);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
            }
        }
    }

    //  This function get all data cache from addons_cache
    function getDataCache()
    {
        $query   = "SELECT * FROM addons_cache";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function setActionTMP($name_rpm, $action_rpm, $data_exp)
    {
        $user  = $_SESSION['elastix_user'];
        $query = "INSERT INTO action_tmp (name_rpm,action_rpm,data_exp,user) values ('$name_rpm','$action_rpm','$data_exp','$user');";

        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

	function updateTimeActionTMP($init_time)
    {
        $user  = $_SESSION['elastix_user'];
        $query = "UPDATE action_tmp SET init_time='$init_time';";

        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function getActionTMP()
    {
        $query = "SELECT * FROM action_tmp;";

        $result=$this->_DB->getFirstRowQuery($query,true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function clearActionTMP()
    {
        $query = "DELETE FROM action_tmp;";

        $result=$this->_DB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function existsActionTMP()
    {
        $result=$this->getActionTMP();

        if(is_array($result) && count($result)>0){
            return true;
        }
        return false;
    }

    function getSID()
    {
		if(file_exists("/etc/elastix.key")){
			$key = file_get_contents("/etc/elastix.key");
			$key = trim($key);
			return empty($key)?null:$key;
		}
		return null;
    }
    
    function getAddonByName($rpm_name)
    {
    	$data = array($rpm_name);
        $query = "SELECT * FROM addons WHERE name_rpm = ?";

        $result=$this->_DB->getFirstRowQuery($query,true, $data);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }
}
?>
