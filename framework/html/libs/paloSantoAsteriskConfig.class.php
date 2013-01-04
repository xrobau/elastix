<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version {ELASTIX_VERSION}                                               |
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
  $Id: paloSantoAsteriskConfig,v 1.1 05/11/2012 rocio mera rmera@palosanto.com Exp $ */

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
	require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

global $arrConf;

include_once "/var/www/html/libs/paloSantoConfig.class.php";
include_once "/var/www/html/libs/paloSantoPBX.class.php";
include_once "/var/www/html/libs/misc.lib.php";
include_once "/var/www/html/modules/features_code/libs/paloSantoFeaturesCode.class.php";
include_once "/var/www/html/modules/general_settings/libs/paloSantoGlobalsPBX.class.php";

class paloSantoASteriskConfig{
    public $errMsg;
	public $_DB; //conexion a la base elxpbx mysql
	public $_DBSQLite; //conexion a la base elastix.db de sqlite

	//recibe una conexion a la base de elxpbx de mysql
	function paloSantoASteriskConfig(&$pDB,&$pDBSQlite)
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

		// Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDBSQlite)) {
            $this->_DBSQLite =& $pDBSQlite;
            $this->errMsg = $this->_DBSQLite->errMsg." ".$this->errMsg;
        } else {
            $dsn = (string)$pDBSQlite;
            $this->_DBSQLite = new paloDB($dsn);

            if (!$this->_DBSQLite->connStatus) {
                $this->errMsg = $this->_DBSQLite->errMsg." ".$this->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
	}

	function getCodeByDomain($domain){
		$query="SELECT code from organization where domain=?";
		$result=$this->_DBSQLite->getFirstRowQuery($query,true,array($domain));
		if($result===false)
			$this->errMsg=$pDB->errMsg;
		elseif(count($result)==0)
			$this->errMsg=_tr("Organization doesn't exist");
		return $result;
	}
	

	private function createAsteriskDirectory($orgzDomain){
		$query="SELECT 1 from organization where domain=?";
		$result=$this->_DBSQLite->getFirstRowQuery($query, false, array($orgzDomain));
		if($result===false){
			$this->errMsg = $this->_DBSQLite->errMsg;
			return false;
		}elseif(count($result)==0){
			$this->errMsg = _tr("Organization doesn't exist");
			return false;
		}

		$sComando = '/usr/bin/elastix-helper asteriskconfig createDirOrganization '.$orgzDomain.'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
	}

	/**
        Funcion que se encarga de escribir el archivo extensions.conf
        Recibe como parametros un el dominino de una organizacion y una accion
        @param string $action -> pueden ser dos valores add or delete
        @param string $domain -> dominio de la organizacion con la que se quiere realizar la accion
    */
	function includeInExtensions_conf($action="add",$orgzDomain)
	{
		$query="SELECT 1 from organization where domain=?";
        $result=$this->_DBSQLite->getFirstRowQuery($query, false, array($orgzDomain));
        if($result===false){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }
        
        if($action=="add"){
            if(count($result)==0){
                $this->errMsg = _tr("Organization doesn't exist");
                return false;
            }
        }else{
            if(count($result)=="1"){
                $this->errMsg = _tr("Can't delete organization from extensions.conf");
                return false;
            }
        }
        
        $sComando = '/usr/bin/elastix-helper asteriskconfig createExtensionFile '."$action $orgzDomain".'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return true;
	}

	//borra el plan de marcado de una organizacion especifica
	//esto se hace cuando se elimina una organizacion del sistema
	//antes de llamar a esta funcion ya se debio haber eliminado
	//a la organizacion de la base sqlite elastix.db
	function delete_dialplanfiles($orgzDomain)
	{
		$path="/etc/asterisk/organizations/";
		$arrCredentiasls=getUserCredentials();
        $userLevel1=$arrCredentiasls["userlevel"];
        if($userLevel1!="superadmin"){
            $this->errMsg =_tr("You are no authorized to perform this action");
            return false;
        }
        
		//reescribimos los archivos extensions.conf, extensions_globals.conf y extensions_did.conf con las configuraciones correctas
		if($this->createExtensionsGlobals("delete",$orgzDomain)===false){
			$this->errMsg=_tr("Error when trying write asterisk config file").$this->errMsg;
			return false;
		}else{
			if($this->includeInExtensions_conf("delete",$orgzDomain)!==false){
                $sComando = '/usr/bin/elastix-helper asteriskconfig deleteFileOrgAst '.$orgzDomain.' 2>&1';
                $output = $ret = NULL;
                exec($sComando, $output, $ret);
                if ($ret != 0){
                    $this->errMsg = implode('', $output);
                    return false;
                }
                
				$sComando = '/usr/bin/elastix-helper asteriskconfig reload 2>&1';
				$output = $ret = NULL;
				exec($sComando, $output, $ret);
				if ($ret != 0){
                    $this->errMsg = implode('', $output);
                }
				return true;
			}else{
				$this->errMsg=_tr("Error when trying write asterisk config file").$this->errMsg;
				return false;
			}
		}
	}

	//Si se falla la momento de crear los archivos, ahi que deshacer los cambios desde donde se llame a esta funcion
	function createOrganizationAsterisk($domain,$country){
		//obtenemos el codigo de la organizacion y de esa manera validamos que la organizacion exista
		$query="SELECT 1 from organization where domain=?";
		$result=$this->_DBSQLite->getFirstRowQuery($query, false, array($domain));
		if($result===false){
			$this->errMsg = $this->_DBSQLite->errMsg;
			return false;
		}elseif(count($result)==0){
			$this->errMsg = _tr("Organization doesn't exist");
			return false;
		}
		$pFC=new paloFeatureCodePBX($this->_DB,$domain);

		// 1.-Seateamos las configuracions generales para la organizacion en la base de datos
		//	  (sip_general,iax_general,voicemail_general,globals,features_codes)
		// 2.-Creamos dentro de asterisk directorios que van a ser usados por la organizacion
		// 3.-Inclumos los archivos recien creados en con la sentencias include dentro del archivo
        //    extensions.conf y extensions_globals.conf
        // TODO: No se escriben los archivos de configuracion de la organizacion dentro del plan de marcado
        //       hasta que el superadmin cree al admin de la organizacion recien creada
		if($this->setGeneralSettingFirstTime($domain,$country)){
			if($pFC->insertPaloFeatureDB()){
				if($this->createAsteriskDirectory($domain)){
                    if($pFC->createFeatureFile()){
                        if($this->setReloadDialplan($domain)){
                            if($this->createExtensionsGlobals("add",$domain)!==false && $this->includeInExtensions_conf("add",$domain)!==false){
                                //recargamos la configuracion de asterisk
                               /* $sComando = '/usr/bin/elastix-helper asteriskconfig reload 2>&1';
                                $output = $ret = NULL;
                                exec($sComando, $output, $ret);
                                if ($ret != 0){
                                    $this->errMsg = implode('', $output);
                                }*/
                                return true;
                            }else{
                                $this->errMsg=_tr("Error trying created configuartion file in asterisk").$this->errMsg;}
                        }else{
                            $this->errMsg=_tr("Error trying created configuartion file in asterisk").$this->errMsg;}
                    }else{
                        $this->errMsg=_tr("Error trying created file features.conf ").$pFC->errMsg;}
				}else{
					$this->errMsg=_tr("Error trying created directories inside asterisk");}
			}else
				$this->errMsg=_tr("Error trying set Features Codes").$pFC->errMsg;
		}else{
			$this->errMsg=_tr("Error trying set general settings asterisk").$this->errMsg;}

		return false;
	}

	
	function deleteOrganizationAsterisk($domain,$code){
		// 2. Eliminar de la base de datos elxpbx todo lo que tenga que ver con la organizacion
	    //    Esto falta de ver cual es la mejor forma - en todas las tablas el campo que hace referencia a la organization
		//    se llama organization_domain
		// 3. Eliminar las entradas dentro de astDB que correspondan a la organizacion
		// 4. Eliminamos los archivos de configuracion dentro del directorio de asterisk que pertenezcan al dominio

		//arreglo que contiene las tablas dentro de elxpbx que no tienen el campo
        //organization_domian
		$arrNoOrgDomain=array("trunk_dialpatterns","trunk","outbound_route_dialpattern","outbound_route_trunkpriority","features_code_settings","globals_settings","iax_settings","sip_settings","voicemail_settings","queue_member","ivr_destination","did_details","did","tg_parameters");

		//obtenemos una lista de las tablas dentro de la base elxpbx
		$queryShow="show tables from elxpbx";
		$result=$this->_DB->fetchTable($queryShow);
		if($result===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}
		//TODO: Implementarlo en una funcion aparte
		foreach($result as $value){
			$queryDel="DELETE from ".$value[0]." where organization_domain=?";
			if(!in_array($value[0],$arrNoOrgDomain)){
                if($value[0]=="queue"){
                    $query="SELECT name from queue where organization_domain=?";
                    $result=$this->_DB->fetchTable($query, false, array($domain));
                    if($result===false){
                        $this->errMsg=$this->_DB->errMsg;
                        return false;
                    }else{
                        foreach($result as $valor){
                            $qDel="DELETE from queue_member where queue_name=?";
                            $result=$this->_DB->genQuery($qDel,array($valor[0]));
                            if($result==false){
                                $this->errMsg=$this->_DB->errMsg;
                                return false;
                            }
                        }
                    }
				}elseif($value[0]=="ivr"){
                    $query="SELECT id from ivr where organization_domain=?";
                    $result=$this->_DB->fetchTable($query, false, array($domain));
                    if($result===false){
                        $this->errMsg=$this->_DB->errMsg;
                        return false;
                    }else{
                        foreach($result as $valor){
                            $qDel="DELETE from ivr_destination where ivr_id=?";
                            $result=$this->_DB->genQuery($qDel,array($valor[0]));
                            if($result==false){
                                $this->errMsg=$this->_DB->errMsg;
                                return false;
                            }
                        }
                    }
				}elseif($value[0]=="outbound_route"){
                    $query="SELECT id from outbound_route where organization_domain=?";
                    $result=$this->_DB->fetchTable($query, false, array($domain));
                    if($result===false){
                        $this->errMsg=$this->_DB->errMsg;
                        return false;
                    }else{
                        foreach($result as $valor){
                            $qDel="DELETE from outbound_route_dialpattern where outbound_route_id=?";
                            $result=$this->_DB->genQuery($qDel,array($valor[0]));
                            if($result==false){
                                $this->errMsg=$this->_DB->errMsg;
                                return false;
                            }
                            
                            $qDel="DELETE from outbound_route_trunkpriority where outbound_route_id=?";
                            $result=$this->_DB->genQuery($qDel,array($valor[0]));
                            if($result==false){
                                $this->errMsg=$this->_DB->errMsg;
                                return false;
                            }
                        }
                    }
				}elseif($value[0]=="time_group"){
                    $query="SELECT id from time_group where organization_domain=?";
                    $result=$this->_DB->fetchTable($query, false, array($domain));
                    if($result===false){
                        $this->errMsg=$this->_DB->errMsg;
                        return false;
                    }else{
                        foreach($result as $valor){
                            $qDel="DELETE from tg_parameters where id_tg=?";
                            $result=$this->_DB->genQuery($qDel,array($valor[0]));
                            if($result==false){
                                $this->errMsg=$this->_DB->errMsg;
                                return false;
                            }
                        }
                    }
				}
				$result=$this->_DB->genQuery($queryDel,array($domain));
				if(!$result){
                    //print_r($queryDel);
					$this->errMsg=$this->_DB->errMsg;
					return false;
				}
			}
		}
		
		$queryd="UPDATE did set organization_domain=NULL where organization_domain=?";
        if($this->_DB->genQuery($queryd,array($domain))==false){
            $this->errMsg .=$this->_DB->errMsg;
            return false;
        }

		//borramos las entradas de la organizacion dentro de astDB
		$errorMng="";
		$astMang=AsteriskManagerConnect($errorMng);
		if($astMang==false){
			$this->errMsg=$errorMng;
			return false;
		}else{ 
			$result=$astMang->database_delTree("EXTUSER/".$code);
			$result=$astMang->database_delTree("DEVICE/".$code);
			$result=$astMang->database_delTree("DND/".$code);
			$result=$astMang->database_delTree("CALLTRACE/".$code);
			$result=$astMang->database_delTree("CFU/".$code);
			$result=$astMang->database_delTree("CFB/".$code);
			$result=$astMang->database_delTree("CF/".$code);
			$result=$astMang->database_delTree("CW/".$code);
			$result=$astMang->database_delTree("BLACKLIST/".$code);
			$result=$astMang->database_delTree("QPENALTY/".$code);
		}
		
		//reescribimos los arcgivos extensions_did.conf y chan_dahdi_additional.conf
		//por si la organizacion tenia asociado alguno
        $sComando = '/usr/bin/elastix-helper asteriskconfig createExtAddtionals '."$domain".' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = _tr("Error writitn did file").implode('', $output);
            return FALSE;
        }
        
		$exito=$this->delete_dialplanfiles($domain);
		if(!$exito){
			$this->errMsg=_tr("Error deleting dialplan files of organization")."$domain. ".$this->errMsg;
			//reescribimos los archivos extensions_did.conf y chan_dahdi_additional.conf
			$sComando = '/usr/bin/elastix-helper asteriskconfig createExtAddtionals  2>&1';
            $output = $ret = NULL;
            exec($sComando, $output, $ret);
            if ($ret != 0) {
                $this->errMsg = _tr("Error writing did file").implode('', $output);
                return FALSE;
            }
        }
        
		return $exito;
	}
	
	//se crean la varias globales del sistema, antes esto estaba dentro de extensions_additionals
    //ahora sera un archivo aparte
    //se sobreescribe este archivo cada vez que se crea una nueva organizacion
    private function createExtensionsGlobals($action="add", $orgzDomain){
        global $arrConf;
       
        $query="SELECT 1 from organization where domain=?";
        $result=$this->_DBSQLite->getFirstRowQuery($query, false, array($orgzDomain));
        if($result===false){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }
        
        if($action=="add"){
            if(count($result)==0){
                $this->errMsg = _tr("Organization doesn't exist");
                return false;
            }
        }else{
            if(count($result)=="1"){
                $this->errMsg = _tr("Can't delete organization from extensions_globals.conf");
                return false;
            }
        }
        
        $sComando = '/usr/bin/elastix-helper asteriskconfig createExtensionGlobals '."$action $orgzDomain".'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return true;
    }


    private function setGeneralSettingFirstTime($domain,$country)
	{
		global $arrConf;
		$source_file="/var/www/elastixdir/asteriskconf/globals.conf";
		//verificamos que exista el dominio
		$query="SELECT count(domain) from organization where domain=?";
		$result=$this->_DBSQLite->getFirstRowQuery($query, false, array($domain));
		if($result===false){
			$this->errMsg = $pDB->errMsg;
			return false;
		}elseif($result[0]==0){
			$this->errMsg = _tr("Organization dosen't exist");
			return false;
		}
		
		$pGlobals=new paloGlobalsPBX($this->_DB,$domain);
		$res=$pGlobals->insertDBGlobals($country,$this->_DBSQLite);
        if($res==false){
            $this->errMsg = $pGlobals->errMsg;
            return false;
        }
        
        $reslng=$pGlobals->getGlobalVar("LANGUAGE");
        if($reslng!=false){
            $language=$reslng;
        }
        
        
		$arrGeneral=array("sip","iax","voicemail");
		foreach($arrGeneral as $type){
            $queryg="Select * from ".$type."_settings";
            $arrConfig=$this->_DB->getFirstRowQuery($queryg,true);
            if($arrConfig===false){
                $this->errMsg=$this->_DB->errMsg;
                return false;
            }elseif($arrConfig==false){
                $this->errMsg=_tr("Don't exist default parameters ").$type."_settings";
                return false;
            }
                
			$questions="(?,";
			$prop="(organization_domain,";
			$arrValues=array($domain);
			$i=1;
			foreach($arrConfig as $key => $value){
                if($key=="language" && !empty($language)){
                    $value=$language;
                }
                if(isset($value) && $key!="id"){
                    $arrValues[$i]=$value;
                    $prop .="$key,";
                    $questions .="?,";
                    $i++;
                }
			}
			$questions=substr($questions,0,-1).")";
			$prop=substr($prop,0,-1).")";
			$query="INSERT INTO ".$type."_general $prop values $questions";
			if($this->_DB->genQuery($query,$arrValues)==false){
                $this->errMsg=$this->_DB->errMsg;
				return false;
			}
		}
		
		//settings de la organizacion que crearan cierto plan de marcado por default
		
		//una ruta de salida por default
		$query="insert into outbound_route (routename,outcid_mode,mohsilence,seq,organization_domain) VALUES (?,?,?,?,?)";
		if($this->_DB->genQuery($query,array("out_9","off","default","1",$domain))==false){
            $this->errMsg="Error creating outbound_route. ".$this->_DB->errMsg;
            return false;
        }
        //obtenemos el id de la ruta creada
        $result = $this->_DB->getFirstRowQuery("SELECT LAST_INSERT_ID()",false);
        if($result!=false){
            $outboundid=$result[0];
            $query="insert into outbound_route_dialpattern (outbound_route_id,prefix,match_pattern,seq) VALUES (?,?,?,?)";
            if($this->_DB->genQuery($query,array($outboundid,"9",".","1"))==false){
                $this->errMsg="Error creating outbound_route. ".$this->_DB->errMsg;
                return false;
            }
        }
        //TODO:falta asignarle una truncal de salida. Esto no se puede hacer porque aun no se le
        //ha permitido salida por ninguna truncal a la organizacion
        

        return true;
    }

    /**
        funcion que crear un registro en la tabla reloadDialplan
        esta tabla se utiliza para saber si es necesario mostrar un mensaje
        al adminitranor indicando que se debe reescribir el plan de marcado
        de la organizacion para que los cambios efectudos en la pbx tomen
        efecto dentro de asterisk
    */
    function setReloadDialplan($domain,$reload=false){
        //obtenemos el dominio de la organizacion para verificar que esta exista
        $query="SELECT id from organization where domain=?";
        $result=$this->_DBSQLite->getFirstRowQuery($query, false, array($domain));
        if($result===false){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }elseif(count($result)==0){
            $this->errMsg = _tr("Organization dosen't exist");
            return false;
        }

        //comprobamos que el usuario tiene acceso a modificar esta informacion
        $arrCredentials=getUserCredentials();
        if($arrCredentials["userlevel"]!="superadmin"){ //debemos comprobar el id de la organizacion
            if( ($result[0] != $arrCredentials["id_organization"]) || $arrCredentials["id_organization"]===false){
                $this->errMsg=_tr("Invalid organization");
                return false;
            }
        }


        $status=($reload==true)?"yes":"no";
        $query="SELECT show_msg from reload_dialplan where organization_domain=?";
        $estado=$this->_DB->getFirstRowQuery($query, false, array($domain));
        if($estado===false){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }else{
            if(is_array($estado) && count($estado)>0)
                $query="UPDATE reload_dialplan SET show_msg=? where organization_domain=?";
            else
                $query="Insert into reload_dialplan (show_msg,organization_domain) values(?,?)";
            $res=$this->_DB->genQuery($query,array($status,$domain));
            if($res==false)
                $this->errMsg = $this->_DB->errMsg;
            return $res;
        }
    }


    function getReloadDialplan($domain){
        //obtenemos el dominio de la organizacion
        $query="SELECT 1 from organization where domain=?";
        $result=$this->_DBSQLite->getFirstRowQuery($query, false, array($domain));
        if($result===false){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }elseif(count($result)==0){
            $this->errMsg = _tr("Organization dosen't exist");
            return false;
        }

        $query="SELECT show_msg from reload_dialplan where organization_domain=?";
        $estado=$this->_DB->getFirstRowQuery($query, false, array($domain));
        if($estado==false){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }else
            return $estado[0];
    }

    function generateDialplan($domain,$reload=false){
        //valido que exista el dominio y obtengo el code asociado a la extension
        //obtenemos el codigo de la organizacion
        $queryCode="SELECT code from organization where domain=?";
        $code=$this->_DBSQLite->getFirstRowQuery($queryCode, false, array($domain));
        if($code===false){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }elseif(count($code)==0){
            $this->errMsg = _tr("Organization dosen't exist");
            return false;
        }

        $sComando = "/usr/bin/elastix-helper asteriskconfig generateDialPlan $domain $reload  2>&1";
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }
}

class paloContexto{
	public $name; //nombre de contexto sin el code de la organizacion a la que pertences
	public $arrExtensions; //arreglo de extensiones que pertenecen al contexto
	public $arrInclude; //include tipo de extension especial, arreglo que ocntige extensiones de este tipo
	public $switch; //swtich tipo de extension especial, arreglo que ocntige extensiones de este tipo
	public $code; //code de la organizacion a la que pertence el contexto
	public $errMsg;

	function paloContexto($code,$name){
		global $arrConf;
		//valido que el codigo exista
		$pDB=new paloDB($arrConf['elastix_dsn']['elastix']);
		$queryCode="SELECT count(code) from organization where code=?";
		$recode=$pDB->getFirstRowQuery($queryCode, false, array($code));
		if($recode===false){
			$this->errMsg = $pDB->errMsg;
			return false;
		}elseif(count($recode)==0){
			$this->errMsg = _tr("Organization doesn't exist");
			return false;
		}

		$this->code=$code;

		if(preg_match("/^[A-Za-z0-9\-_]+$/",$name) || strlen($name)>62){
			if(substr($name,0,6)=="macro-")
				$this->name="[macro-".$this->code."-".substr($name,6)."]";
			else
				$this->name="[".$this->code."-".$name."]";
		}else{
			$this->errMsg=_tr("Context names cannot contain special characters and have a maximum length of 62 characters");
			return false;
		}
	}
	

	//retorna el contexto como un string para se añadido
	//al plan de marcado, esto es de una contexto especifico
	function stringContexto($arrInclude,$arrExtensions){
		$contexto="\n".$this->name."\n";
		//incluimos los contextos personalizados , TODO: falta preguntar si se los quiere o no incluir
		$contexto .="include =>".substr($this->name,1,-1)."-custom\n";
		if(isset($arrInclude)){
			foreach($arrInclude as $value){
				if(preg_match("/^[A-Za-z0-9\-_]+$/",$value["name"]) || strlen($value["name"])>62){
					if(substr($this->name,0,6)=="macro-")
						$contexto .="include =>macro-".$this->code."-".substr($value["name"],6);
					else
						$contexto .="include =>".$this->code."-".$value["name"];
                    
                    if(isset($value["extra"])){
                        $contexto .=$value["extra"];
                    }
                    $contexto .="\n";
				}else{
					$this->errMsg=_tr("Context names cannot contain special characters and have a maximum length of 62 characters");
					return "";
				}
			}
		}

		if(is_array($arrExtensions)){
			foreach($arrExtensions as $extension){
				if(!is_null($extension) && is_object($extension))
					$contexto .=$extension->data."\n";
			}
		}
		return $contexto;
	}
}

class paloExtensions{
	public $extension;
	public $priority;
	public $label;
	public $application;
	public $data;

	function paloExtensions($extension,$application,$priority="",$label=""){
		$this->extension=$this->validateExtension($extension);
		$this->priority=$this->validatePriority($priority);
		$this->label=$this->validateLabel($label);
		$this->application=$this->validateApplication($application);
		if($this->extension===false || $this->priority===false || $this->label===false || $this->application===false)
			return false;
		else{
			$this->data="exten => ".$this->extension.",".$this->priority.$this->label.",".$this->application;
			return true;
		}
	}

	function validateExtension($extension){
		if(!isset($extension) || $extension=="")
			return false;
		//if(preg_match("/^[A-Za-z0-9#\*]+$/",$extension) || preg_match("/^_[A-Za-z0-9#\*\.\[\]]+$/",$extension))
			return $extension;
		/*else
			return false;*/
	}
	
	function validatePriority($prioridad){
		if(!isset($prioridad) || $prioridad=="" ||$prioridad=="n")
			return "n";
		elseif(strtolower($prioridad)==("hint"))
			return strtolower($prioridad);
		elseif(preg_match("/[[:digit:]]+/",$prioridad))
			return $prioridad;
		else
			return false;
	}

	function validateLabel($label){
		if(is_null($label) || $label=="")
			return "";
		elseif(preg_match("/^\+[[:digit:]]+$/",$label))
			return $label;
		else
			return '('.$label.')';
	}

	//recibe un objeto de tipo extension
	function validateApplication($application){
		if(!is_object($application))
			return false;
		else{
			if($application->output()=="")
				return false;
			else
				return $application->output();
		}
	}
}
?>
