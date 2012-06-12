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
  $Id: paloSantoEndPoint.class.php,v 1.1 2008/01/15 10:39:57 bmacias Exp $ */

include_once("libs/paloSantoDB.class.php");

/* Clase que implementa EndPoint Configuracion */
class paloSantoEndPoint
{
    var $_dsnAsterisk;
    var $_dsnSqlite;
    var $errMsg;
    function paloSantoEndPoint($dsnAsterisk, $dsnSqlite)
    {
        $this->_dsnAsterisk = $dsnAsterisk;
        $this->_dsnSqlite = $dsnSqlite;
    }

    function connectDataBase($engineBase, $nameBase)
    {
        if($engineBase=="mysql")
            $stringConnect = $this->_dsnAsterisk . "/$nameBase";
        else if($engineBase=="sqlite")
            $stringConnect = $this->_dsnSqlite . "/$nameBase.db";
        else{
            $this->errMsg = "Error: String of connection not support.";
            return false;
        }
        $pDB = new paloDB($stringConnect);

        if ($pDB->connStatus) {
            $this->errMsg = $pDB->errMsg;
            // debo llenar alguna variable de error
            return false;
        }
        return $pDB;
    }

    function listEndpointConf() {
        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;
	$sqlPeticion = "select e.id,e.desc_device,e.account,e.mac_adress,e.id_model from endpoint e;";
        $result = $pDB->fetchTable($sqlPeticion,true); //se consulta a la base endpoints
        $pDB->disconnect();
	return $result;
    }

    function listVendor() {
        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;
	$sqlPeticion = "select v.id,v.name vendor ,m.value mac from vendor v inner join mac m on v.id = m.id_vendor;";
        $result = $pDB->fetchTable($sqlPeticion,true); //se consulta a la base endpoints
        $pDB->disconnect(); 
	return $result;
    }

     function deleteEndpointsConf($Mac) {
        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;
        $ok1 = true;
        $ok2 = true;

        //First delete the parameters endpoint
        $sqlPeticion = "delete from parameter where id_endpoint = (select id from endpoint where mac_adress='$Mac');";
        $ok1 = $pDB->genQuery($sqlPeticion); 
        //Second delete the endpoint
        $sqlPeticion = "delete from endpoint  where mac_adress='$Mac';";
        $ok2 = $pDB->genQuery($sqlPeticion);

        $pDB->disconnect(); 
        return ($ok1 && $ok2); //no es tan buena la validacion, ver si se puede mejorar, probabilidad de q ocurra este error es muy baja
    }


    /*Nota: 
      No hice de esta funcion en tres funciones ya que se podia dividir, 
      porque ahorraba procesamiento al no recorrer de nuevo el arrego de 
      endpoints en cada funcion nueva.
    */
    /**
        input:
            $network          : the red where go configure the endpoints
            $arrVendor        : array of all the phones (vendor) than are suport for configuration
            $arrEndpointsConf : array of the endpoints than are configuraded in the system.
        output:
            $map              : array with endpoints configurated and the that can be configure.
    **/
    function endpointMap($network,$arrVendor,$arrEndpointsConf)
    {
        $map=array();
        //PASO 0: VALIDACIONES DE LOS ARREGLOS
        if(!is_array($arrVendor) || count($arrVendor) <= 0 || !is_array($arrEndpointsConf)){
            $this->errMsg = "Function - endpointMap: Empty parameters.";
            return $map;
        }

        // PASO 1: OBTENGO TODOS LOS ENDPOINTS DE LA RED
        exec('sudo nmap -sP -n '.$network, $arrConsole,$flagStatus);
        if($flagStatus == 0){
            $ok1 = false;
            $ok2 = false;
            foreach ($arrConsole as $key => $lineConsole) {
                //PASO 2: PARSEO CADA LINEA Y DESCUBRO CADA ENDPOINT

                //Host 192.168.1.232 appears to be up.
                if (eregi("^Host ([\.|[:digit:]]*) ",$lineConsole,$arrReg)){
                    $ipAdress   = $arrReg[1];
                    $ok1 = true;
                }
                //MAC Address: 00:04:F2:12:CA:8A (Polycom)
                if (eregi("^MAC Address: ([:|[:alnum:]]*) \((.*)\)$",$lineConsole,$arrReg)){
                    $macAddress = $arrReg[1];
                    $descVendor = $arrReg[2];
                    $macVendor  = substr($macAddress,0,8);
                    $ok2 = true;
                }

                //PASO 3: CONSTRUNCCION DEL ARREGLO MAP
                if($ok1 && $ok2){// correcto se encontro un endpoint con sus datos completos
                    if(is_array($arrVendor) && count($arrVendor)>0){ //filtro para solo devolver los map de los endpoints cuyos vendor (fabricantes) puede reconocer elastix.
                        foreach($arrVendor as $key => $vendor){
                            //PASO 4: FILTRO SOLO LOS ENDPOINT CON VENDOR CONOCIDOS
                            if($vendor['mac']==$macVendor){
                                $tmpMap["ip_adress"]   = $ipAdress;
                                $tmpMap["mac_adress"]  = $macAddress;
                                $tmpMap["desc_vendor"] = $descVendor;
                                $tmpMap["mac_vendor"]  = $macVendor;
                                $tmpMap["name_vendor"] = $vendor['vendor'];
                                $tmpMap["id_vendor"] = $vendor['id'];

                                //PASO 5: LLENO LOS DATOS ADICIONALES SI ESTE ENDPOINT HA ESTADO CONFIGURADO SI NO LLENO DE VACIO
                                $isConfigurated = false;
                                if(is_array($arrEndpointsConf) && count($arrEndpointsConf)>0){
                                    $isConfigurated = false;
                                    foreach($arrEndpointsConf as $key => $endpointConf){
                                        if($endpointConf['mac_adress'] == $macAddress){ //si el actual endpoint su mac_adress esta en la lista de configurados
                                            $tmpMap["id"]          = $endpointConf['id'];
                                            $tmpMap["desc_device"] = $endpointConf['desc_device'];
                                            $tmpMap["account"]     = $endpointConf['account'];
                                            $tmpMap["model_no"]    = $endpointConf['id_model'];
                                            $tmpMap["configurated"]= true;
                                            $isConfigurated = true;
                                            break; //si ya lo encontre rompo el lazo
                                        }
                                    }
                                }
                                if(!$isConfigurated){ //si no esta configurado aun el endpoint lo lleno de vacio
                                    $tmpMap["id"]   = "";
                                    $tmpMap["desc_device"] = "";
                                    $tmpMap["account"]     = "";
                                    $tmpMap["model_no"]    = "";
                                    $tmpMap["configurated"]= false;
                                }
                                $map[] = $tmpMap;
                                break; //si ya lo encontre rompo el lazo
                            }
                        }
                    }
                    $ok1 = false;
                    $ok2 = false;
                }
            }//end foreach main
        }
        return $map;
    }

    function getDeviceFreePBX()
    {
        global $arrLang;

        $pDB = $this->connectDataBase("mysql","asterisk");
        if($pDB==false)
            return false;
        $sqlPeticion = "select id, concat(description,' <',user,'>') label FROM devices WHERE tech = 'sip' ORDER BY id ASC;";
        $result = $pDB->fetchTable($sqlPeticion,true); //se consulta a la base asterisk
        $pDB->disconnect(); 
        $arrDevices = array();
        if(is_array($result) && count($result)>0){
                $arrDevices['unselected'] = "-- {$arrLang['Unselected']} --";
            foreach($result as $key => $device){
                $arrDevices[$device['id']] = $device['label'];
            }
        }
        else{
            $arrDevices['no_device'] = "-- {$arrLang['No Extensions']} --";
        }
	return $arrDevices;
    }

    function getAllModelsVendor($nameVendor)
    {
        global $arrLang;

        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;
        $sqlPeticion = "select m.id,m.name from vendor v inner join model m on v.id=m.id_vendor where v.name ='$nameVendor' order by m.name;";
        $result = $pDB->fetchTable($sqlPeticion,true); //se consulta a la base endpoints
        $arrModels = array();
        if(is_array($result) && count($result)>0){
            $arrModels['unselected'] = "-- {$arrLang['Unselected']} --";
            foreach($result as $key => $model)
                $arrModels[$model['id']] = $model['name'];
        }
        else{
            $arrModels['no_model'] = "-- {$arrLang["No Models"]} --";
        }
        $pDB->disconnect(); 
	return $arrModels;
    }

    function getModelById($id_model)
    {
        global $arrLang;

        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;
        $sqlPeticion = "select m.name from model m where m.id ='$id_model';";
        $result = $pDB->getFirstRowQuery($sqlPeticion,true); //se consulta a la base endpoints

        if(is_array($result) && count($result)>0)
            return $result['name'];
        else return false;
        $pDB->disconnect();
    }

    function getDeviceFreePBXParameters($id_device) {
        $pDB = $this->connectDataBase("mysql","asterisk");
        $parameters = array();

        if($pDB==false)
            return false;
        $sqlPeticion = "select 
                            d.id, 
                            d.description,
                            s.data 
                        from 
                            devices d 
                                inner 
                            join sip s on d.id = s.id 
                        where 
                            s.keyword = 'secret' and 
                            d.id = '$id_device';";
        $result = $pDB->getFirstRowQuery($sqlPeticion,true); //se consulta a la base endpoints

	if(is_array($result) && count($result)>0){
            $parameters['id_device']     = $result['id'];
            $parameters['desc_device']   = $result['description'];
            $parameters['account_device']   = $result['id'];//aparentemente siempre son iguales
            $parameters['secret_device'] = $result['data'];
	}
        $pDB->disconnect(); 
	return $parameters;
    }

    function createEndpointDB($endpointVars)
    {
        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;
        $sqlPeticion = "select count(*) existe from endpoint where mac_adress ='{$endpointVars['mac_adress']}';";
        $result = $pDB->getFirstRowQuery($sqlPeticion,true); //se consulta a la base endpoints

        if(is_array($result) && count($result)>0 && $result['existe']==1){//Si existe entonces actualizo
           $sqlPeticion = "update endpoint set 
                            id_device   = '{$endpointVars['id_device']}',
                            desc_device = '{$endpointVars['desc_device']}',
                            account     = '{$endpointVars['account']}',
                            secret      = '{$endpointVars['secret']}',
                            id_model    = {$endpointVars['id_model']},
                            mac_adress  = '{$endpointVars['mac_adress']}',
                            id_vendor   = {$endpointVars['id_vendor']},
                            edit_date   = datetime('now','localtime'),
                            comment     = '{$endpointVars['comment']}'
                          where mac_adress = '{$endpointVars['mac_adress']}';";
        }
        else{ // Si no existe entonces lo inserto
            $sqlPeticion = "insert into endpoint(id_device,desc_device,account,secret,id_model,
                            mac_adress,id_vendor,edit_date,comment)
                            values ('{$endpointVars['id_device']}',
                                    '{$endpointVars['desc_device']}',
                                    '{$endpointVars['account']}',
                                    '{$endpointVars['secret']}',
                                    {$endpointVars['id_model']},
                                    '{$endpointVars['mac_adress']}',
                                    {$endpointVars['id_vendor']},
                                    datetime('now','localtime'),
                                    '{$endpointVars['comment']}');";
        }

        //Realizo el query.
        if(!$pDB->genQuery($sqlPeticion)){
            $this->errMsg = $pDB->errMsg;
            $pDB->disconnect();
            return false;
        }else{
            if(isset($endpointVars['arrParameters']) && is_array($endpointVars['arrParameters']) && count($endpointVars['arrParameters'])>0)
                $result = $this->setParameters($endpointVars['arrParameters'], $endpointVars['mac_adress'], $pDB);
        }
        $pDB->disconnect();
        if(isset($result) && !$result) return false;
        return true;
    }

    function setParameters($arrParameters, $mac_adress, $pDB)
    {
        foreach($arrParameters as $key => $value)
        {
            $sqlPeticion = " select count(*) as exist from parameter where name='$key' and id_endpoint = (select id from endpoint where mac_adress = '$mac_adress');";
            $result = $pDB->getFirstRowQuery($sqlPeticion,true); //se consulta a la base
            if(is_array($result) && count($result)>0 && $result['exist']==1)
            {
                $sqlPeticion = "update parameter set 
                                  value   = '$value'
                                  where name='$key' and id_endpoint = (select id from endpoint where mac_adress = '$mac_adress');";
            }else{
                $sqlPeticion = "insert into parameter (name, value, id_endpoint) values 
                                  ('$key', '$value', (select id from endpoint where mac_adress = '$mac_adress'));";
            }

            //Realizo el query.
            if(!$pDB->genQuery($sqlPeticion)){
                $this->errMsg = $pDB->errMsg;
                $pDB->disconnect();
                return false;
            }

            return true;
        }
    }

    /** Funcion que compara si hay incongruencia de informacion entre las bases asterisk(mysql) y endpoint(sqlite)
        Diferencia entre la descripcion y secret de los devices y tambien si en alguna base no existe el device.
    **/
    function compareDevicesAsteriskSqlite($device)
    {
        global $arrLang;
        $report = "";

        //comprobar si existe en base asterisk
        $deviceParametersFreePBX = $this->getDeviceFreePBXParameters($device);
        if($deviceParametersFreePBX===false)
            return false;
        else if(is_array($deviceParametersFreePBX) && empty($deviceParametersFreePBX))
            return $arrLang["Don't exist in FreePBX extension"]." $device";
        else if(is_array($deviceParametersFreePBX) && count($deviceParametersFreePBX) == 4){
            //ok tengo datos del freePBX acerca del device.entonces continuo ahora con endpoint
            $pDB_sqlite = $this->connectDataBase("sqlite","endpoint");
            if($pDB_sqlite==false)
                    return false;

            $sqlPeticion = "select desc_device,secret from endpoint where id_device ='$device';";
            $result = $pDB_sqlite->getFirstRowQuery($sqlPeticion,true);//se consulta a la base asterisk

            if(is_array($result) && count($result) == 0){//no existe en sqlite
                $pDB_sqlite->disconnect();
                return $arrLang["Don't exist in Endpoint extension"]." $device";
            }
            else if(is_array($result) && count($result) == 2){//si existe en sqlite
                $desc_asterisk   = $deviceParametersFreePBX['desc_device'];
                $secret_asterisk = $deviceParametersFreePBX['secret_device'];
                $desc_endpoint   = $result['desc_device'];
                $secret_endpoint = $result['secret'];

                if($desc_asterisk!=$desc_endpoint)
                    $report .= $arrLang["User Name in Endpoint is"]." $desc_endpoint.";
                if($secret_asterisk!=$secret_endpoint)
                    $report .= "<br />".$arrLang['And secrets no equals in FreePBX and Endpoint'].".";
                $pDB_sqlite->disconnect();
            }
        }
        if($report=="") return false;
        else return $report;
    }

    function getParameters($mac_adress)
    {
        $pDB = $this->connectDataBase("sqlite","endpoint");
        if($pDB==false)
            return false;

        $arrParameters = array();
        $sqlPeticion = " select name, value from parameter where id_endpoint = (select id from endpoint where mac_adress = '$mac_adress');";
        $result = $pDB->fetchTable($sqlPeticion,true); //se consulta a la base
        if(is_array($result) && count($result)>0)
        {
            foreach($result as $key => $value)
            {
                $arrParameters["{$value['name']}"] = $value['value'];
            }
        }

        $pDB->disconnect();
        return $arrParameters;
    }
}

/**
create table vendor(
    id          integer         primary key,
    name        varchar(255)    not null default '',
    description varchar(255)    not null default '',
    script      text
);

create table model(
    id          integer         primary key,
    name        varchar(255)    not null default '',
    description varchar(255)    not null default '',
    id_vendor   integer         not null,
    foreign key (id_vendor)     references vendor(id)
);

create table mac(
    id          integer         primary key,
    id_vendor   integer         not null,
    value       varchar(8)      not null default '--:--:--',
    description varchar(255)    not null default '',
    foreign key (id_vendor)     references vendor(id)
);

create table endpoint(
    id          integer         primary key,
    id_device   varchar(255)    not null default '',
    desc_device varchar(255)    not null default '',
    account     varchar(255)    not null default '',
    secret      varchar(255)    not null default '',
    id_model    integer         not null,
    mac_adress  varchar(17)     not null default '--:--:--:--:--:--',
    id_vendor   integer         not null,
    edit_date   timestamp       not null,
    comment     varchar(255),
    foreign key (id_model)      references model(id), 
    foreign key (id_vendor)     references vendor(id) 
);

create table parameter(
    id          integer         primary key,
    id_endpoint integer         not null,
    name        varchar(255)    not null default '',
    value       varchar(255)    not null default '',
    foreign key (id_endpoint)   references endpoint(id)
);
**/
?>