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

include_once $arrConf['basePath']."/libs/paloSantoConfig.class.php";
include_once $arrConf['basePath']."/libs/paloSantoPBX.class.php";
include_once $arrConf['basePath']."/libs/misc.lib.php";
include_once $arrConf['basePath']."/modules/features_code/libs/paloSantoFeaturesCode.class.php";
include_once $arrConf['basePath']."/modules/general_settings/libs/paloSantoGlobalsPBX.class.php";

class paloSantoASteriskConfig{
    public $errMsg;
	public $_DB; //conexion a la base elx_pbx mysql
	public $_DBSQLite; //conexion a la base elastix.db de sqlite

	//recibe una conexion a la base de elx_pbx de mysql
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

	/**
	*Funcion que escribe el archivo extensions_domain.conf de cada organizacion
	*a partir de un archivo generico
	*/
	private function writeExtensionsDomain_conf($orgzDomain)
	{
		global $arrConf;
		global $arrConfModule;
		$EXITO=false;

		$result=$this->getCodeByDomain($orgzDomain);
		if($result==false)
			return false;
		else
			$orgzCode=$result["code"];
			
		$fsource="/var/www/elastixdir/asteriskconf/generic_extensions.conf";
		$extFile="/etc/asterisk/organizations/extensions_$orgzDomain.conf";
		$extAddFile="/etc/asterisk/organizations/extensions_additionals_$orgzDomain.conf";
		$extCusFile="/etc/asterisk/organizations/extensions_custom_$orgzDomain.conf";
		if(is_file($fsource)){
			if($handler=fopen($fsource,'r')){
				$content = fread($handler, filesize($fsource));
				$content = str_replace("{CODE}", "$orgzCode", $content);
				$content = str_replace("{DOMAIN}", "$orgzDomain", $content);
				fclose($handler);
				
				foreach(array($extFile,$extAddFile,$extCusFile) as $file){
                    if(file_put_contents($file, $content)!==false){
                        $sComando = '/usr/bin/elastix-helper asteriskconfig changePermission '."$file".' 2>&1';
                        $output = $ret = NULL;
                        exec($sComando, $output, $ret);
                        if ($ret != 0) {
                            $this->errMsg = implode('', $output);
                            return false;
                        }
                        $content="";
                    }else{
                        $this->errMsg=_tr("File")." $file "._tr("couldn't be written");
                        return false;
                    }    
				}
                
               /* $sComando = '/usr/bin/elastix-helper asteriskconfig create_file '."$extCusFile $content".'  2>&1';
                $output = $ret = NULL;
                exec($sComando, $output, $ret);
                if ($ret != 0) {
                    $this->errMsg = implode('', $output);
                    return false;
                }*/
				$EXITO=true;
			}else{
				$this->errMsg=_tr("Couldn't be opened for reading file")." $fsource";
			}
		}else{
			$this->errMsg=_tr("File extensions_generic.conf doesn't exist, new fiel couldn't be created");
		}
		return $EXITO;
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

		$pConfig = new paloConfig("/var/www/elastixdir/asteriskconf", "elastix_pbx.conf", "=", "[[:space:]]*=[[:space:]]*");
		$arrConfig = $pConfig->leer_configuracion(false);

		//sacar la variable de globals
		//TODO
		$astmonitor = $arrConfig['MIXMON_DIR']['valor'];
		$astlibsound = $arrConfig['ASTVARLIBDIR']['valor']."/sounds";
		$astspooltmp= $arrConfig['ASTSPOOLDIR']['valor']."/tmp";

		$arrDir=array($astmonitor,$astlibsound,$astspooltmp);
		$exito=true;

		
		foreach($arrDir as $value){
			if($exito){
				if(!is_dir("$value/$orgzDomain")){
					$exito=mkdir("$value/$orgzDomain");
				}

				if(!$exito || !chown("$value/$orgzDomain","asterisk"))
					$exito=false;

				if(!$exito || !chgrp("$value/$orgzDomain","asterisk"))
					$exito=false;

			}else
				break;
		}

		//si no se logra hacer el proceso revertimos los cambios
		if(!$exito){
			if(is_dir("$astmonitor/$orgzDomain"))
				rmdir("$astmonitor/$orgzDomain");
			if(is_dir("$astlibsound/$orgzDomain"))
				rmdir("$astlibsound/$orgzDomain");
			if(is_dir("$astspooltmp/$orgzDomain"))
				rmdir("$astspooltmp/$orgzDomain");
			return false;
		}else
			return true;
	}

	function includeInExtensions_conf()
	{
		$file="/etc/asterisk/extensions.conf";
		
		$query= "SELECT domain from organization";
		$result=$this->_DBSQLite->fetchTable($query, false);
        if($result===FALSE){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }
        
		$includes="; BEGIN ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";
		$includes .="#include extensions_globals.conf\n";
		foreach($result as $domain)
		{
			if(isset($domain[0]) && $domain[0]!="")
				$includes .="#include organizations/extensions_".$domain[0].".conf\n";
		}
		$includes .="; END ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";

		$lineas=array();
		
		foreach (file($file) as $sLinea) {
			// Remover todos los include conrrespondientes a los archivos extensiones de las organizacion
			if (preg_match('/; BEGIN ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE/', $sLinea)){
				$lineas[] = $includes;
				break;
			} else {
				$lineas[] = $sLinea;
			}
		}
		if(count($lineas)==0)
			$lineas[]=$includes;
			
		if(file_put_contents($file, $lineas)===false)
            return false;	
        /*$sComando = '/usr/bin/elastix-helper asteriskconfig create_file extensions.conf '.$lineas.'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return false;
        }*/
		return true;
	}

	//borra el plan de marcado de una organizacion especifica
	//esto se hace cuando se elimina una organizacion del sistema
	//antes de llamar a esta funcion ya se debio haber eliminado
	//a la organizacion de la base sqlite acl.db
	//TODO: solo el usuario superadmin debe ser capaz de realizar esa accion
	function delete_dialplanfiles($orgzDomain)
	{
		$path="/etc/asterisk/organizations/";
		$arrCredentiasls=getUserCredentials();
        $userLevel1=$arrCredentiasls["userlevel"];
        if($userLevel1!="superadmin"){
            $this->errMsg =_tr("You are no authorized to perform this action");
            return false;
        }
        
		//reescribimos los archivos extensions.conf y extensions_globals.conf con las configuraciones correctas
		if($this->createExtensionsGlobals()===false){
			$this->errMsg=_tr("Error when trying write asterisk config file").$this->errMsg;
			return false;
		}else{
			if($this->includeInExtensions_conf()!==false){
				$arrayFile=array("extensions_$orgzDomain.conf","extensions_additionals_$orgzDomain.conf","extensions_custom_$orgzDomain.conf","extensions_globals_$orgzDomain.conf");
				foreach($arrayFile as $file)
					unlink($path.$file);
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
		// 3.-Creamos los archivos de configuracion de asterisk para dicha organizacion
		//	  (extensions_dominio.conf,extensions_additionals_dominio.conf,extensions_custom_dominio.conf,extensions_globals_dominio.conf)
		// 4.-Inclumos los archivos recien creados en con la sentencias include dentro del archivo
        //    extensions.conf y extensions_globals.conf
		if($this->setGeneralSettingFirstTime($domain,$country)){
			if($pFC->insertPaloFeatureDB()){
				if($this->createAsteriskDirectory($domain)){
                    if($pFC->createFeatureFile()){
                        if($this->createExtensionGlobalsDomain($domain) &&  $this->writeExtensionsDomain_conf($domain) && $this->setReloadDialplan($domain)){
                            if($this->createExtensionsGlobals()!==false && $this->includeInExtensions_conf()!==false){
                                $sComando = '/usr/bin/elastix-helper asteriskconfig reload 2>&1';
                                $output = $ret = NULL;
                                exec($sComando, $output, $ret);
                                if ($ret != 0){
                                    $this->errMsg = implode('', $output);
                                }
                                return true;
                            }else{
                                $this->errMsg=_tr("Error trying set general settings asterisk").$this->errMsg;}
                        }else{
                            $this->errMsg=_tr("Error trying set general settings asterisk").$this->errMsg;}
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
		// 2. Eliminar de la base de datos ast_realtime todo lo que tenga que ver con la organizacion
	    //    Esto falta de ver cual es la mejor forma - en todas las tablas el campo que hace referencia a la organization
		//    se llama organization_domain
		// 3. Eliminar las entradas dentro de astDB que correspondan a la organizacion
		// 4. Eliminamos los archivos de configuracion dentro del directorio de asterisk que pertenezcan al dominio

		//arreglo que contiene las tablas dentro de ast_realtime que no tienen el campo
        //organization_domian
		$arrNoOrgDomain=array("trunk_dialpatterns","features_code_settings","globals_settings","iax_settings","sip_settings",
		"voicemail_settings");

		//obtenemos una lista de las tablas dentro de la base elx_pbx
		$queryShow="show tables from elx_pbx";
		$result=$this->_DB->fetchTable($queryShow);
		if($result===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}
		//TODO: Implementarlo en una funcion aparte
		foreach($result as $value){
			$queryDel="DELETE from ".$value[0]." where organization_domain=?";
			if(!in_array($value[0],$arrNoOrgDomain)){
				if($value[0]=="trunks"){
					$queryTrunkId="SELECT trunkid from trunks where organization_domain=?";
					$result=$this->_DB->fetchTable($queryTrunkId, false, array($domain));
					if($result===false){
						$this->errMsg=$this->_DB->errMsg;
						return false;
					}else{
						foreach($result as $valor){
							$qDelTrunkD="DELETE from trunk_dialpatterns where trunkid=?";
							$result=$this->_DB->genQuery($qDelTrunkD,array($valor[0],$domain));
							if($result==false){
								$this->errMsg=$this->_DB->errMsg;
								return false;
							}
						}
					}
				}
				$result=$this->_DB->genQuery($queryDel,array($domain));
				if(!$result){
					$this->errMsg=$this->_DB->errMsg;
					return false;
				}
			}
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
		}
	
		$exito=$this->delete_dialplanfiles($domain);
		if(!$exito)
			$this->errMsg=_tr("Error deleting dialplan files of organization")."$domain. ".$this->errMsg;

		return $exito;
	}
	
	//se crean la varias globales del sistema, antes esto estaba dentro de extensions_additionals
    //ahora sera un archivo aparte
    //se sobreescribe este archivo cada vez que se crea una nueva organizacion
    private function createExtensionsGlobals(){
        global $arrConf;
        $arrCredentiasls=getUserCredentials();
        $userLevel1=$arrCredentiasls["userlevel"];
        if($userLevel1!="superadmin"){
            $this->errMsg =_tr("You are no authorized to perform this action");
            return false;
        }
        
        $file="/etc/asterisk/extensions_globals.conf";
        $source_file="/var/www/elastixdir/asteriskconf/elastix_pbx.conf";
        $content ="[globals]\n";
        if(is_file($source_file)){
            if($handler=fopen($source_file,'r')){
                $content .= fread($handler, filesize($source_file));
            } 
        }else{
            $this->errMsg=_tr("File /var/www/elastixdir/asteriskconf/elastix_pbx.conf dosen't exist");
            $content ="\n; BEGIN ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";
            $content .="; END ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";
            file_put_contents($file, $content);
            return false;
        }
        
        //incluimos los archivos que tienen las configuraciones globales de cada organizacion
        $query= "SELECT domain from organization";
        $result=$this->_DBSQLite->fetchTable($query, false);
        if($result===FALSE){
            $this->errMsg = $pDB->errMsg;
            return false;
        }

        if(count($result)!=0){
            $content .="\n; BEGIN ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";
            foreach($result as $domain)
            {
                if(isset($domain[0]) && $domain[0]!=""){
                    //antes de incluir el archivo validamos que el mismo exista
                    //ya que si no existe y lo incluimos esto provocara que asterisk crash
                    //en caso de no existir el archivo se lo inteta crear
                    if(!is_file("/etc/asterisk/organizations/extensions_globals_".$domain[0].".conf")){
                        if($this->createExtensionGlobalsDomain($domain[0]))
                            $content .="#include organizations/extensions_globals_".$domain[0].".conf\n";
                    }else
                        $content .="#include organizations/extensions_globals_".$domain[0].".conf\n";
                }
            }
            $content .="; END ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";  
        }else{
            $content .="\n; BEGIN ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";
            $content .="; END ELASTIX INCLUDE FILE DO NOT REMOVE THIS LINE\n";
        }
        
       /* $sComando = '/usr/bin/elastix-helper asteriskconfig create_file extensions_globals.con'." $content ".'2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return false;
        }else*/
        if(file_put_contents($file, $content)==false)
            return false;
        return true;
    }

	//se lee las variables globales desde la base de datos y se las escribe en el archivo de
	//configuracion
	private function createExtensionGlobalsDomain($domain){
		global $arrConf;
		$file="/etc/asterisk/organizations/extensions_globals_$domain.conf";
		$contenido="";

		//obtenemos el codigo de la organizacion
		$queryCode="SELECT code from organization where domain=?";
		$code=$this->_DBSQLite->getFirstRowQuery($queryCode, false, array($domain));
		if($code===false){
			$this->errMsg = $this->_DBSQLite->errMsg;
			return false;
		}elseif(count($code)==0){
			$this->errMsg = _tr("Organization doesn't exist");
			return false;
		}
			
		//leemos todas las variables globales de la organizacion desde la tabla globals
		$query="SELECT variable,value from globals where organization_domain=?";
		$result=$this->_DB->fetchTable($query,false,array($domain));
		if($result===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}elseif(count($result)!=0){
			$contenido = "[globals](+)\n"; //no quitar (+), esto permite escribir un mismo contexto en distintos archivos
			foreach($result as $arrtemp){
				$contenido .="$code[0]_$arrtemp[0]=$arrtemp[1]\n";
			}
		}

		//las globales correspondientes a las truncales de la organizacion
		$queryTrunk="SELECT * from trunks where organization_domain=?";
		$trunks=$this->_DB->fetchTable($queryTrunk,true,array($domain));
		if($trunks===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}elseif(count($trunks)!=0){
			foreach($trunks as $arrtemp){
				$trunkid=$arrtemp["trunkid"];
				$tech=$arrtemp["tech"];
				$channelId=$arrtemp["channelid"];
				$outcid=isset($arrtemp["outcid"])?$arrtemp["outcid"]:"";
				$maxchans=isset($arrtemp["maxchans"])?$arrtemp["maxchans"]:"";
				$outprefix=isset($arrtemp["dialoutprefix"])?$arrtemp["dialoutprefix"]:"";
				$outFail=isset($arrtemp["failscript"])?$arrtemp["failscript"]:"";
				$disabled=isset($arrtemp["disabled"])?$arrtemp["disabled"]:"off";
				$keepCid=isset($arrtemp["keepcid"])?$arrtemp["keepcid"]:"off";
				$force=($keepCid=="all")?"1":"";

				$contenido .="OUT_$trunkid = $tech/$channelId\n";
				$contenido .="OUTCID_$trunkid = $outcid\n";
				$contenido .="OUTMAXCHANS_$trunkid = $maxchans\n";
				$contenido .="OUTPREFIX_$trunkid = $outprefix\n";
				$contenido .="OUTDISABLE_$trunkid = $disabled\n";
				$contenido .="OUTKEEPCID_$trunkid = $keepCid\n";
				$contenido .="FORCEDOUTCID_$trunkid = $force\n";

				$qPrefix="SELECT count(trunkid) from trunk_dialpatterns where trunkid=?";
				$trunkPrefix=$this->_DB->fetchTable($qPrefix,true,array($trunkid));
				if(count($trunkPrefix)>0)
					$contenido .="PREFIX_TRUNK_$trunkid = 1\n";
				else
					$contenido .="PREFIX_TRUNK_$trunkid = \n";
			}
		}
		
		if(file_put_contents($file, $contenido)===false)
            return false;
		/*$sComando = '/usr/bin/elastix-helper asteriskconfig create_file extensions_globals_'."$domain.conf $contenido".'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return false;
        }else*/
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
            $language=$reslng[0];
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

	function generateDialplan($domain){
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

		$file="organizations/extensions_additionals_$domain.conf";

		$arrContext=array();
		$arrFromInt=array();

		//genero el plan de marcado relacionado con las extension internas
		$pDevice=new paloDevice($domain,"sip",$this->_DB);
		$arrContextExtLocal=$pDevice->createDialPlanLocalExtension($arrFromInt);
		if($arrContextExtLocal===false || !is_array($arrContextExtLocal))
			$this->errMsg .=$pDevice->errMsg;
		else
			$arrContext=array_merge($arrContext,$arrContextExtLocal);

		//genero el plan de marcado de los faxes
		$arrContextExtFax=$pDevice->createDialPlanFaxExtension($arrFromInt);
		if($arrContextExtFax===false || !is_array($arrContextExtFax))
			$this->errMsg .=$pDevice->errMsg;
		else
			$arrContext=array_merge($arrContext,$arrContextExtFax);

		//genero el plan marcado relacionado con los features codes
		$pFC=new paloFeatureCodePBX($this->_DB,$domain);
		$arrContextFeaturesCode=$pFC->createDialPlanFeaturesCode($arrFromInt);
		if($arrContextFeaturesCode===false || !is_array($arrContextFeaturesCode))
			$this->errMsg .=$pFC->errMsg;
		else
			$arrContext=array_merge($arrContext,$arrContextFeaturesCode);
		//genero plan de marcado relacionado con los irvs

		//genero plan de marcado relacionado con la ringgroups

		//genero plan de marcado relacionado con las truncales

		//genero plan de marcado relacionado con las colas

		//genero plan de marcado relacionado con las aplicacion (features code)

		//incluimos los contestos dentro de from-internal-additional
		$fromInternal=new paloContexto($code[0],"from-internal-additional");
		$fromInternal->arrInclude=$arrFromInt;
		$fromInternal->arrExtensions=array(new paloExtensions("h",new ext_hangup(),"1"));
		$arrContext[]=$fromInternal;

		$contenido="";

       // print_r($arrContext);
		
		foreach($arrContext as $value){
			if(isset($value)){
				if(empty($value->errMsg) && is_object($value)){
					$contenido .=$value->stringContexto($value->arrInclude,$value->arrExtensions);
				}else{
					$this->errMsg .=$value->errMsg."\n";
				}
			}
		}
        
		/*$sComando ='/usr/bin/elastix-helper asteriskconfig create_file '.$file." '".$contenido."' 2>&1";
        $output = $ret = NULL;
        exec($sComando, $output, $ret);*/
        $ret=0;
        if(file_put_contents("/etc/asterisk/$file", $contenido)==false)
            $ret=1;
        if ($ret != 0) {
            $this->errMsg = _tr("Couldn't be written file")." /etc/asterisk/$file ".implode('', $output);
            return false;
        }else{
            $sComando = '/usr/bin/elastix-helper asteriskconfig dialplan-reload 2>&1';
            $output = $ret = NULL;
            exec($sComando, $output, $ret);
            if ($ret != 0){
                $this->errMsg = implode('', $output);
            }
            return true;
        }
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
				if(preg_match("/^[A-Za-z0-9\-_]+$/",$value) || strlen($value)>62){
					if(substr($this->name,0,6)=="macro-")
						$contexto .="include =>macro-".$this->code."-".substr($value,6)."\n";
					else
						$contexto .="include =>".$this->code."-".$value."\n";
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
