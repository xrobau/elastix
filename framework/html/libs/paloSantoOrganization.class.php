<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-29                                               |
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
  $Id: paloSantoEntity.class.php,v 1.1 2012-02-07 11:02:13 Rocio Mera rmera@palosanto.com Exp $ */
    include_once "libs/paloSantoEmail.class.php";
    include_once "libs/paloSantoACL.class.php";
    include_once "libs/paloSantoFax.class.php";
	include_once "libs/paloSantoAsteriskConfig.class.php";
	include_once "libs/paloSantoPBX.class.php";
	
class paloSantoOrganization{
    var $_DB;
    var $errMsg;

    function paloSantoOrganization(&$pDB)
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

    function getNumOrganization($filter_field = null, $filter_value = null)
    {
        $where    = "";
        $arrParam = null;
        if(isset($filter_field) & $filter_field !=""){
            $where    = "where $filter_field like ?";
            $arrParam = array("$filter_value%");
        }

        $query   = "SELECT COUNT(id) FROM organization $where;";

        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getOrganization($limit="", $offset="", $filter_field="", $filter_value="")
    {
        $where    = "";
        $limite = "";
        $offsets = "";
        $arrParam = null;
        if(isset($filter_field) & $filter_field !=""){
            $where    = "where $filter_field like ?";
            $arrParam = array("$filter_value");
        }

        if(isset($limite) & $limite !=""){
            $limite = "LIMIT $limit";
        }

        if(isset($offset) & $offset !=""){
            $offsets = "OFFSET $offset";
        }

        $query   = "SELECT * FROM organization $where $limite $offsets";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result===false){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getNumUserByOrganization($id)
    {
        $query   = "SELECT COUNT(u.id) FROM acl_user u inner join acl_group g on u.id_group = g.id where g.id_organization=?;";
        $result=$this->_DB->getFirstRowQuery($query, false, array($id));

        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getUsersByOrganization($id)
    {
		if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = "Organization ID is not numeric";
			return false;
        }

        $query   = "SELECT u.id, u.username, u.name, u.md5_password, u.id_group, u.extension, u.fax_extension, u.estado, u.picture FROM acl_user u inner join acl_group g on u.id_group = g.id where g.id_organization=?;";
        $result=$this->_DB->fetchTable($query, true, array($id));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }

        return $result;
    }

    function getOrganizationById($id)
    {
		if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = "Organization ID is not numeric";
			return false;
        }

        $query = "SELECT * FROM organization WHERE id=?;";

        $result=$this->_DB->getFirstRowQuery($query, true, array($id));

        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result;
    }

    function getOrganizationByName($name)
    {
        $query = "SELECT * FROM organization WHERE name=?;";
        $result=$this->_DB->getFirstRowQuery($query, true, array($name));
        //triple igual problema de conneccion o de sintaxis, falso booleano
        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result;
    }

    function getOrganizationByDomain_Name($domain_name)
    {
        $query = "SELECT * FROM organization WHERE domain=?;";
        $result=$this->_DB->getFirstRowQuery($query, true, array($domain_name));
        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result;
    }

    //recibe como parametros el id de la organizacion y el nombre de la propiedad que se desea obtener
    function getOrganizationProp($id,$key)
    {
        $query = "SELECT value FROM organization_properties WHERE id_organization=? and key=?;";
        $result=$this->_DB->getFirstRowQuery($query, false, array($id,$key));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result[0];
    }

	function getOrganizationPropByCategory($id,$category)
    {
        $query = "SELECT key,value FROM organization_properties WHERE id_organization=? and category=?;";
        $result=$this->_DB->fetchTable($query, true, array($id,$category));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result;
    }

    /**
      *  Procedimiento para setear una propiedad de una organizacion, dado el id de la organizacion,
      *  el nombre de la propiedad y el valor de la propiedad
      *  Si la propiedad ya existe actualiza el valor, caso contrario crea el nuevo registro
      *  @param integer $id de la organizacion a la que se le quiere setear la propiedad
      *  @param string $key nombre de la propiedad
      *  @param string $value valor que tomarà la propiedad
      *  @return boolean verdadera si se ejecuta con existo la accion, falso caso contrario
    */
    function setOrganizationProp($id,$key,$value,$category=""){
        $bQuery = "select 1 from organization_properties where id_organization=? and key=?";
        $bResult=$this->_DB->getFirstRowQuery($bQuery,false, array($id,$key));
        if($bResult===false){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }else{
            if(count($bResult)==0){
                $query="INSERT INTO organization_properties values (?,?,?,?)";
                $arrParams=array($id,$key,$value,$category);
            }else{
                if($bResult[0]=="1"){
                $query="UPDATE organization_properties SET value=? where id_organization=? and key=?";
                $arrParams=array($value,$id,$key);}
            }
            $result=$this->_DB->genQuery($query, $arrParams);
            if($result==false){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }else
                return true;
        }
    }

	//esta funcion se usa para setear las propiedades de una organizacion que pertenecen a la categoria system
	//al momento de crear una nueva organizacion
	//se usa como valor de cada una de la propiedades los valores respectivos que tiene seteados la organizacion
	//principal
	function setOrganizationPropSys($idOrganization){
		$Exito=false;
		if (is_null($idOrganization) || !preg_match('/^[[:digit:]]+$/', "$idOrganization")) {
            $this->errMsg = "Invalid ID Organization";
		}
		$result=$this->getOrganizationPropByCategory(1,"system");
		if($result!=false){
			foreach($result as $tmp){
				$Exito=$this->setOrganizationProp($idOrganization,$tmp["key"],$tmp["value"],"system");
				if(!$Exito){
					$this->errMsg = $this->_DB->errMsg;
					return false;
				}
			}
		}
		return $Exito;
	}

    private function getNewCode($domain)
    {
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg=_tr("Invalid domain format");
            return false;
        }
        
        //el code esta fromado por el dominio de la prganizacion sin caracteres especiales 
        //y debe tener una longitud de 15 caracteres. En caso de que el dominio tenga menos de 
        //15 caracteres a este se le agrega un codigo para completar dicha longitud
        $chars = "abcdefghijkmnpqrstuvwxyz23456789";
        $existCode=false;
        $inicode=str_replace(array("-","."),"",$domain);
        $len=strlen($inicode);
        do{
            srand((double)microtime()*1000000);
            //la primera vez esto es falso. Si llega a ser verdad 
            //la variable code estaria seteada y tendria 15 caracteres
            if($existCode){
                $code=substr($code, 0, 10);
                $len=10;
            }else{
                $code=$inicode;
            }
            
            if($len>=15){
                $code=substr($inicode, 0, 15);
            }else{        
                // Genero los caracteres faltantes
                while (strlen($code) < 15) {
                        $num = rand() % 33;
                        $tmp = substr($chars, $num, 1);
                        $code .= $tmp;
                }
            }
            $existCode = $this->existCode($code);
        }while ($existCode);

        return $code;
    }
    
    private function existCode($org_code){
        $query="select 1 from organizacion where code=?";
        $result=$this->_DB->fetchTable($query, false);
        if($result==false){
            return false;
        }else{
            return true;
        }
    }


    function getOrganizationCode($domain)
    {
        $query="select code from organization where domain=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($domain));
        if($result==FALSE)
            $this->errMsg = $this->_DB->errMsg;
        return $result;
    }

    /**
      *  Procedimiento para crear una entidad
      *  @param string $dominio nuevo dominio al que perteneceràn todos los mienbros de una misma entidad
                       y sera creado un nuevo dominio a nivel de mails
      *  @param string $name nombre de la organizacion o empresa que crea la entidad
      *  @param string $country pais donde reside la empresa dueña de la entidad
      *  @param string $state estado o ciudad donde reside la empresa dueña de la entidad
    */
    function createOrganization($name,$dominio,$country,$state,$address,$country_code,$area_code,$quota, $email_contact, &$error)
    {
        $flag=false;
            $arr_result = $this->getOrganizationByName($name);
			if ($arr_result!==FALSE){ //SIGNIFICA QUE NO HAY PROBLEMAS DE CONEXION
				if (is_array($arr_result) && count($arr_result)>0 ) {
					$this->errMsg = _tr("Organization name already exists");
				}elseif($this->insertOrganizationDB($name,$dominio,$country,$state,$address,$country_code,$area_code, $quota, $email_contact, $error)){
					$flag=true;
				}
			}
        return $flag;
    }


	private function assignResource($idOrganization){
		$rInsert=true;
		$recurso=array();
		$arrResource=array("usermgr","organization","userlist","grouplist","group_permission","preferences","language","themes_system","myex_config","webmail","cdrreport","billing","billing_rates","billing_report","dest_distribution","billing_setup","graphic_report","summary_by_extension","missed_calls","openfire","downloads","sphones","faxutils","instantmessaging","calendar","address_book","sec_accessaudit","sec_weak_keys","email_accounts","email_list","email_stats","vacations","virtual_fax","faxlist","sendfax","faxviewer","sysdash","dashboard","applet_admin","backup_restore","currency","pbxadmin","control_panel","voicemail","monitoring","endpoint_configurator","conference","extensions_batch","tools","asterisk_cli","file_editor","text_to_wav",'extensions','queues','trunks','ivr','features_code','general_settings','inbound_route','outbound_route');
		//1) Asignamos los recursos a la organizacion
		//   estos recursos son sacados en base a los recursos por default a los que tiene acceso
        //   el adminitrador de cada entidad
		//   En caso de que no exista el group 1, que es el grupo administrador por default se asignan los recursos
		//   que existan en la tabla acl_resource y se encuentre dentro del arreglo $arrResource
		$query1="select o.id_resource from organization_resource o join group_resource g on o.id=g.id_org_resource where id_group=1";
		$recursos=$this->_DB->fetchTable($query1, true);
		
		if($recursos===false){
			//ocurrio un error con la conexion
			$this->errMsg = "An error has occurred when were assigned resources to the organization. ".$this->_DB->errMsg;
			return false;
		}elseif(count($recursos)>0){
			foreach($recursos as $value){
				$recurso[]=$value["id_resource"];
			}
		}else{
			$recurso=$arrResource;
		}

		$tmp=0;
		//con los recursos por default verificamos que estos existan en la tabla acl_resource
		//y de ahi le asignamos los recursos a la organizazion
		$query2="SELECT id FROM acl_resource WHERE Type!=''";
		$result=$this->_DB->fetchTable($query2, true);
		foreach($result as $value){
			$result2[]=$value["id"];
		}
		if($result===false){
			$this->errMsg = _tr("An error has occurred when trying get resources of the system. ").$this->_DB->errMsg;
			return false;
		}else{
			$qInsert="INSERT INTO organization_resource (id_organization, id_resource) VALUES(?,?)";
			foreach($recurso as $value){
				if(in_array($value,$result2)){
					//creamos una entrada en la tabla organization_resource para esa recurso
					$rInsert=$this->_DB->genQuery($qInsert,array($idOrganization,$value));
					if($rInsert==false){
						$this->errMsg = _tr("An error has occurred when trying get resources of the system. ").$this->_DB->errMsg;
						return false;
					}
				}
			}
		}
			
		if($rInsert){
			if($this->createAllGroupOrganization($idOrganization)){
				return true;
			}else
				return false;
		}
		
	}

    private function createAllGroupOrganization($idOrganization){
        $gExito = false;
        $pACL = new paloACL($this->_DB);

        $arrayGroups= array("administrator"=>"total access","operator"=>"operator","extension"=>"extension user");
        foreach($arrayGroups as $key => $value){
            $gExito=$pACL->createGroup($key, $value, $idOrganization);
            if($gExito==false){
				$this->errMsg = $pACL->errMsg;
                return false;
            }
        }

		//obtenemos los grupos recien insertados a la organizacion
		$grpOrga=$pACL->getGroups(null,$idOrganization);

		// se asume que la organizacion por default , la 1 tiene los tres grupos de elastix creadsos
        // los modulos a los que estos grupos tinen acceso se toman como refencia para asignar los recursos
		// a los grupos recien creados
		//          id_grupo 1=administrator
		//          id_group 2=operator
		//          id_group 3=extension
		$query = "Insert into group_resource (id_org_resource,id_group) select id, ? from organization_resource where id_organization=? and id_resource in (select o.id_resource from organization_resource o join group_resource g on o.id=g.id_org_resource where id_group=?)";
		if($gExito){
			foreach($grpOrga as $value){
				switch($value[1]){
					case "administrator":
						$id_group=1;
						break;
					case "operator":
						$id_group=2;
						break;
					default:
						$id_group=3;
					}
				$result=$this->_DB->genQuery($query,array($value[0],$idOrganization,$id_group));
                if($result==false){
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
			}
		}
        return true;
    }

    private function insertOrganizationDB($name,$domain,$country,$city,$address,$country_code,$area_code, $quota, $email_contact, &$error)
    {
        global $arrConf;
        $flag=false;
        $error_domain="";
        $address=isset($address)? $address : "";
        $pEmail = new paloEmail($this->_DB);
        //contrumios la nueva entidad
        //antes que todo debemos validar que no exista el dominio que queremos crear en el sistema
        $resOrgz=$this->getOrganizationByDomain_Name($domain);
        if(array($resOrgz) && count($resOrgz)==0){
            $this->_DB->beginTransaction();
            //obtenemos el codigo de la organizacion
            //se valida que el dominio de la organizacion tenga un formato valido
            $org_code=$this->getNewCode($domain);
            if($org_code==false)
                return false;
            $query="INSERT INTO organization (name,domain,code,country,city,address,email_contact) values(?,?,?,?,?,?,?);";
            $arr_params = array($name,$domain,$org_code,$country,$city,$address,$email_contact);
            $result=$this->_DB->genQuery($query,$arr_params);
            if($result==FALSE){
                $this->_DB->rollBAck();
                $this->errMsg=$this->_DB->errMsg;    
            }else{
                //obtenemos la organizacion recien creada
                $resultOrgz=$this->getOrganizationByDomain_Name($domain);
                //seteamos los valores de organization_properties correspondientes a la categoria system
                $proExito=$this->setOrganizationPropSys($resultOrgz['id']);
                //seteamos las demas propiedades de la organization
                $cExito=$this->setOrganizationProp($resultOrgz['id'],"country_code",$country_code,"fax");
                $aExito=$this->setOrganizationProp($resultOrgz['id'],"area_code",$area_code,"fax");
                $eExito=$this->setOrganizationProp($resultOrgz['id'],"email_quota",$quota,"email");

                if($proExito && $cExito && $aExito && $eExito){
                    //se asignan los recursos a la organizacion
                    //se crean los grupos
                    //se asignan los recursos a los grupos
                    $gExito=$this->assignResource($resultOrgz['id']);
                    if($gExito==false){
                        $error = _tr("Error trying create organization groups.");
                        $this->_DB->rollBAck();
                    }else{
                        //procedo a crear el plan de marcado para la organizacion
                        $pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
                        $pDB->beginTransaction();
                        $pAstConf=new paloSantoASteriskConfig($pDB,$this->_DB);
                        //procedo a setear las configuaraciones generales del plan de marcado por cada organizacion
                        if($pAstConf->createOrganizationAsterisk($domain,$country)){
                            //procedo a crear el nuevo dominio
                            if(!($pEmail->createDomain($domain,$error_domain))){
                                //no se puede crear el dominio
                                $this->errMsg=$error_domain;
                                $this->_DB->rollBAck();
                                $pAstConf->_DB->rollBAck();
                                $pAstConf->delete_dialplanfiles($domain);
                            }else{
                                if(!$this->createFolderFaxOrg($domain)){
                                    $this->_DB->rollBAck();
                                    $pAstConf->_DB->rollBAck();
                                    $pAstConf->delete_dialplanfiles($domain);
                                }else{
                                    $flag=true;
                                    $this->_DB->commit();
                                    $pAstConf->_DB->commit();
                                }
                            }
                        }else{
                            $error=_tr("Errors trying created dialplan for new organization").$pAstConf->errMsg;
                            $this->_DB->rollBAck();
                            $pAstConf->_DB->rollBAck();
                            $pAstConf->delete_dialplanfiles($domain);
                        }
                    }
                }else{
                    $error=_tr("Errors trying set organization properties");
                    $this->_DB->rollBAck();
                }
            }
        }else{
            $error=_tr("Already exist other organization with the same domain");
        }

        return $flag;
    }


	private function createFolderFaxOrg($domain){
        $sComando = '/usr/bin/elastix-helper faxconfig createDirFax '.$domain.'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
	}

    //a una entidad no se le puede editar el dominio
    function setOrganization($id,$name,$country,$city,$address,$country_code,$area_code,$quota,$email_contact)
    {
		if (!preg_match('/^[[:digit:]]+$/', "$id") || $id=="1") {
            $this->errMsg = "Invalid ID Organizaion";
			return false;
		}

        $flag=false;$cExito=false;$aExito=false;$qExito=false;
        $address=isset($address)? $address : "";
        $query="UPDATE organization set name=?,country=?,city=?,address=?,email_contact=? where id=?;";
        $arr_params=array($name,$country,$city,$address,$email_contact,$id);

		$this->_DB->beginTransaction();
        $result=$this->_DB->genQuery($query,$arr_params);
        if($result==FALSE){
            $this->errMsg=$this->_DB->errMsg;
            $this->_DB->rollBack();
        }else{
			$this->setOrganizationPropSys($id);
            $cExito=$this->setOrganizationProp($id,"country_code",$country_code,"fax");
            $aExito=$this->setOrganizationProp($id,"area_code",$area_code,"fax");
            $qExito=$this->setOrganizationProp($id,"email_quota",$quota,"email");
            if($cExito!=false && $aExito!=false && $qExito!=false){
                $flag=true;
                $this->_DB->commit();
            }else{
                $this->_DB->rollBack();
            }
        }
        return $flag;
    }

    function deleteOrganizationProp($id)
    {
        $flag=false;
        $error="";
        $query="delete from organization_properties where id_organization=?;";
        $result=$this->_DB->genQuery($query, array($id));
        if($result==true){
               $flag = true;
        }else{
            $this->errMsg=$this->_DB->errMsg;
        }
        return $flag;
    }

    function deleteOrganization($id){
        include_once "libs/cyradm.php";
        include_once "configs/email.conf.php";

        $flag=false;
        $dEmail=true;
		$dGroup=true;
        $numUsers=$this->getNumUserByOrganization($id);
        $arrOrgz=$this->getOrganizationById($id);
		$pACL=new paloACL($this->_DB);

        if(is_array($arrOrgz) && count($arrOrgz)>0){
            $name=$arrOrgz['name'];
            $domain=$arrOrgz['domain'];
			$code=$arrOrgz['code'];
            if($numUsers===false){ //ahi un error en la conexion
                $this->errMsg = $this->_DB->errMsg;
				return false;
            }else{
                if($numUsers!=0){ //no deben existir usuarios para poder eliminar una organizacion
                    $this->errMsg = _tr("Please before to delete a organization delete all users asociated to").$name;
					return false;
                }else{
                    $this->_DB->beginTransaction();
					if(!$this->deleteOrganizationProp($id)){//no se pueden eliminar los registros de la tabla organization_properties
						$this->errMsg=$this->_DB->errMsg;
						$this->_DB->rollBack();
						return false;
					}
					//procedemos a borrar los grupos pertenecientes a la entidad
					$queryGroup="Select id from acl_group where id_organization=?";
					$arrGroup=$this->_DB->fetchTable($queryGroup, true, array($id));
					foreach($arrGroup as $value){
						if(!$pACL->deleteGroup($value["id"])){
							$this->errMsg=$pACL->errMsg;
							$this->_DB->rollBack();
							return false;
						}
					}
					//borramos los permisos de la tabla organization_resource
					$qDelOrgResource="DELETE FROM organization_resource where id_organization=?";
					$rDelOrgResource=$this->_DB->genQuery($qDelOrgResource,array($id));
					if($rDelOrgResource==false){
						$this->errMsg=$this->_DB->errMsg;
						$this->_DB->rollBack();
						return false;
					}
					//borramos la organization
					$query="DELETE FROM organization WHERE id = ?;";
					$result=$this->_DB->genQuery($query,array($id));
					if($result==FALSE){ //no se puede eliminar la organizacion
						$this->errMsg=$this->_DB->errMsg;
						$this->_DB->rollBack();
						return false;
					}
					//borramos el dominio que le pertenece a la organizacion
					$pEmail = new paloEmail($this->_DB);
					$bExito = $pEmail->eliminarDominio($id,$domain,$this->errMsg, FALSE);
					if (!$bExito){
						$this->_DB->rollBack();
						return false;
					}
					//borramos la organizacion de asterisk
					$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
					$pAstConf=new paloSantoASteriskConfig($pDB,$this->_DB);
					$pAstConf->_DB->beginTransaction();
					//ANTES DE BORRAR LA ORGANIZACION SE DEBE HACER UN BACKUP DE LOS ARCHIVOS DE
					//CONFIGURACION DE LA ORGANIZACION Y DE LA BASE AST_DB de la base cosa
					//QUE SI ALGO SALE MAL PUEDA VOLVER AL SISTEMA COMO ESTABA ANTES DE TRATAR DE ELIMINAR
					if($pAstConf->deleteOrganizationAsterisk($domain,$code)){
						$pAstConf->_DB->commit();
						$this->_DB->commit();
						return true;
					}else{
						$this->errMsg=$pAstConf->errMsg." ".$this->errMsg;
						//TODO:volver a restaurar la organizacion dentro de Asterisk -- NO SE COMO?
						$pAstConf->_DB->rollBack();
						$this->_DB->rollBack();
						//volvemos a crear el dominio en el sistema
						$pEmail->createDomain($domain, $errMsg);
						return false;
					}
				}
			}
		}else{
			$this->errMsg=_tr("Organization doesn't exist");
			return false;
		}
    }


	function setParameterUserExtension($domain,$type,$exten,$secret,$fullname,$email,&$pDB2)
	{
		$pDevice=new paloDevice($domain,$type,$pDB2);
		if($pDevice->errMsg!=""){
			$this->errMsg=_tr("Error getting settings from extension user").$pDevice->errMsg;
			return false;
		}
        $pGPBX = new paloGlobalsPBX($pDB2,$domain);
		
		$arrProp=array();
		$arrProp["fullname"]=$fullname;
		$arrProp["name"]=$exten;
		$arrProp['secret']= $secret;
		$arrProp["vmpassword"]= $exten;
		$arrProp["vmemail"]=$email;
		$arrProp["record_in"]="on_demand";
		$arrProp["record_out"]="on_demand";
		$arrProp["callwaiting"]="no";
		$arrProp["rt"]=$pGPBX->getGlobalVar("RINGTIMER");
		$arrProp["create_vm"]=$pGPBX->getGlobalVar("CREATE_VM");
		
		$result=$pDevice->tecnologia->getDefaultSettings($domain);
		$arrOpt=array_merge($result,$arrProp);
		return $arrOpt;
	}

	function setParameterFaxExtension($domain,$type,$exten,$secret,$clid_name,$clid_number,$port=null,&$pDB2)
	{
		$pDevice=new paloDevice($domain,$type,$pDB2);
		if($pDevice->errMsg!=""){
			$this->errMsg=_tr("Error getting settings from fax extension user").$pDevice->errMsg;
			return false;
		}

		$pGPBX = new paloGlobalsPBX($pDB,$domain);
		
		$arrProp=array();
		$arrProp["name"]=$exten;
		$arrProp["defaultip"]="127.0.0.1";
		$arrProp['secret']= $secret;
		$arrProp["create_vm"]= "no";
		$arrProp["fullname"]=$clid_name;
		$arrProp["cid_number"]=$clid_number;
		if(!is_null($port))
			$arrProp["port"]=$port;
        $arrProp["record_in"]="on_demand";
        $arrProp["record_out"]="on_demand";
        $arrProp["callwaiting"]="no";
        $arrProp["rt"]=$pGPBX->getGlobalVar("RINGTIMER");

		$result=$pDevice->tecnologia->getDefaultSettings($domain);
		$arrOpt=array_merge($result,$arrProp);
		return $arrOpt;
	}

    /**
        Este procedimiento se encarga de crear un usuario que pertenece a una organizacion,
		al usuario se le crea una cuenta de correo dentro de la organizacion
		una extension telefonica dentro de asterisk
		un fax con hylafax y la extension para el fax dentro de asterisk
    */
    function createUserOrganization($idOrganization, $username, $name, $md5password, $password, $idGroup, $extension, $fax_extension,
	$countryCode, $areaCode, $clidNumber, $cldiName, $quota, &$lastId)
    {
        include_once "libs/cyradm.php";
        include_once "configs/email.conf.php";
		$pACL=new paloACL($this->_DB);
		$pEmail = new paloEmail($this->_DB);
		$pFax = new paloFax($this->_DB);
		$continuar=true;
		$Exito = false;
		$error="";
		$pDB2=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));

		// 1) valido que la organizacion exista
		// 2) trato de crea el usuario en la base -- aqui se hacen validaciones con respecto al usuario
		//		--Se valida que no exista otro usuario con el mismo username
		//		--Se valida que no exista otro usuario dentro de la misma organizacion con la misma sip_extension
		//		--Se valida que no exista otro usuario dentro de la misma organizacion con la misma fax_extension
		// 3) creo la cuenta de fax
		// 4) creo la cuenta de mail
		// 5) se crea la extension dentro del plan de marcado para el usuario

		if($name=="")
			$name=$username;

		$arrOrgz=$this->getOrganizationById($idOrganization);
        if(is_array($arrOrgz) && count($arrOrgz)>0){ // 1)
			$this->_DB->beginTransaction();
			$username = $username."@".$arrOrgz["domain"];
			$org_extension=$arrOrgz["code"]."_".$extension;
			$org_fax_extension=$arrOrgz["code"]."_".$fax_extension;
			//validamos que no exista otro usuario con la misma sip_extension
			//validamos que no exista otro usuario con la misma fax_extension
			//TODO: en un futuro las extensiones podran ser sip o iax, eso lo define el administrador entre las
			//opciones generales y habra que preguntar que tipo de extension se va a crear
			if($fax_extension==$extension){
				$this->errMsg=_tr("Extension and Fax Extension can not be equal");
				return false;
			}

			$pDevice=new paloDevice($arrOrgz["domain"],"sip",$pDB2);
			if($pDevice->existExtension($extension,"sip")==true){
				$this->errMsg=$pDevice->errMsg;
				return false;
			}

			//las extensiones usadas para el fax siempre son de tipo iax
			if($pDevice->existExtension($fax_extension,"iax2")==true){
				$this->errMsg=$pDevice->errMsg;
				return false;
			}

			if(($pACL->createUser($username, $name, $md5password, $idGroup,$extension,$fax_extension, $idOrganization))){//creamos usuario
				//seteamos los registros en la tabla user_properties
				if($countryCode=="" || $countryCode==null) $countryCode= $this->getOrganizationProp($idOrganization,"country_code");
				if($areaCode=="" || $areaCode==null) $areaCode= $this->getOrganizationProp($idOrganization,"area_code");
				if($clidNumber=="" || $clidNumber==null) $clidNumber = $fax_extension;
				if($cldiName=="" || $cldiName==null) $cldiName = $name;
				$fax_subject = "Fax attached (ID: {NAME_PDF})";
				$fax_content = "Fax sent from '{COMPANY_NAME_FROM}'. The phone number is {COMPANY_NUMBER_FROM}. \n This email has a fax attached with ID {NAME_PDF}.";
				$faxProperties=array("country_code"=>$countryCode,"area_code"=>$areaCode,"clid_number"=>$clidNumber,"clid_name"=>$cldiName,"fax_subject"=>$fax_subject,"fax_content"=>$fax_content);

				//obtenemos el id del usuario que acabmos de crear
				$idUser =$pACL->getIdUser($username);
				$lastId=$idUser;

				foreach($faxProperties as $key => $value){
					if($value===false){
						$error="Property $key is not set. ".$this->errMsg;
						$this->_DB->rollBack();
						$continuar=false;
						break;
					}else{
						if(!$pACL->setUserProp($idUser,$key,$value,"fax")){
							$error= _tr("Error setting parameters faxs").$pACL->errMsg;
							$this->_DB->rollBack();
							$continuar=false;
							break;
						}
					}
				}

				if($quota=="" || $quota==null) $quota = $this->getOrganizationProp($idOrganization,"email_quota");
				//seteamos la quota
				if($quota!==false && $continuar){
					if(!$pACL->setUserProp($idUser,"email_quota",$quota,"email")){
						$error= _tr("Error setting quota").$pACL->errMsg;
						$this->_DB->rollBack();
						$continuar=false;
					}
				}else{
					$error= _tr("Property quota is not set").$this->errMsg;
					$continuar=false;
				}

				$arrSysProp = $this->getOrganizationPropByCategory($idOrganization,"system");
				if(is_array($arrSysProp) && $continuar){
					foreach($arrSysProp as $tmp){
						if(!$pACL->setUserProp($idUser,$tmp["key"],$tmp["value"],"system")){
							$error= _tr("Error setting user properties").$pACL->errMsg;
							$this->_DB->rollBack();
							$continuar=false;
							break;
						}
					}
				}

				//encontrar el puerto que va a ser usado por el iaxmodem
				//se lo hace aqui para poder setear 'port' en el peer iax
				//para que al momento de reiniciar el servicio iaxmodem el iaxmodem creado sea capaz
				//de registrarse dentro de asterisk
				$nextPort=$pFax->getNextAvailablePort();
				if($nextPort==false){
					$error=$pFax->errMsg;
					$this->_DB->rollBack();
					$continuar=false;
				}

				$pDB2->beginTransaction();
				//creamos la extension iax para el fax del usuario
				if($continuar){
					$arrPropFax=$this->setParameterFaxExtension($arrOrgz["domain"],"iax2",$fax_extension,$password,$cldiName,$clidNumber,$nextPort,$pDB2);
					if($arrPropFax==false){
						$error=$this->errMsg;
						$this->_DB->rollBack();
						$continuar=false;
					}else{
						if($pDevice->createFaxExtension($arrPropFax,"iax2")==false){
							$error=$pDevice->errMsg;
							$pDB2->rollBack();
							$this->_DB->rollBack();
							$continuar=false;
						}
					}
				}

				if($continuar){
					//creamos la extension del usuario
					$arrProp=$this->setParameterUserExtension($arrOrgz["domain"],"sip",$extension,$password,$name,$username,$pDB2);
					if($arrProp==false){
						$error=$this->errMsg;
						$pDB2->rollBack();
						$this->_DB->rollBack();
						$continuar=false;
					}else{
						if($pDevice->createNewDevice($arrProp,"sip")==false){
							$error=$pDevice->errMsg;
							$pDB2->rollBack();
							$this->_DB->rollBack();
							$pDevice->deleteAstDBExt($extension,$org_extension,"sip");
							$continuar=false;
						}
					}
				}

				//una vez setado todos los parametros en la table user_properties creamos el fax y el email del usuario
				if($continuar){
					if($pFax->createFax($idUser,$countryCode,$areaCode,$cldiName,$clidNumber,$org_fax_extension,$md5password,$username,$nextPort)){//si se crea exitosamente el fax creamos el email
						if($pEmail->create_email_account($username,$password,$idOrganization,$quota*1024,true)){
							$Exito=true;
							$this->_DB->commit();
							$pDB2->commit();
							$pFax->restartService();
						}else{
							$error=_tr("Error trying create email_account").$pEmail->errMsg;
							$devId=$pACL->getUserProp($idUser,"dev_id");
							$this->_DB->rollBack();
							$pDevice->deleteAstDBExt($extension,$org_extension,"sip");
							$pDB2->rollBack();
							$pFax->deleteFax($devId);
						}
					}else{
						$error=_tr("Error trying create new fax").$pFax->errMsg;
						$pDevice->deleteAstDBExt($extension,$org_extension,"sip");
						$pDB2->rollBack();
						$this->_DB->rollBack();
					}
				}
			}else{
				$error=_tr("User couldn't be created").$pACL->errMsg;
				$this->_DB->rollBack();
			}
		}else{
			$error=_tr("Invalid Organization").$this->errMsg;
		}
		$this->errMsg=$error;
		return $Exito;
    }

	function updateUserSuperAdmin($idUser, $name, $md5password, $password1, $email_contact, $userLevel1){
		$pACL=new paloACL($this->_DB);
		$arrUser=$pACL->getUsers($idUser);
		if($arrUser===false || count($arrUser)==0 || !isset($idUser)){
			$this->errMsg=_tr("User dosen't exist");
			return false;
		}

		if($userLevel1!="superadmin"){
			$this->errMsg=_tr("You aren't authorized to perform this action");
			return false;
		}

		$this->_DB->beginTransaction();
		//actualizamos la informacion de usuario que esta en la tabla acl_user
		if($pACL->updateUserName($idUser, $name)){
			if($pACL->setUserProp($idUser,"email_contact",$email_contact,"email")){
				//actualizamos el password del usuario
				if($password1!==""){
					if($pACL->changePassword($idUser,$md5password)){
						$this->_DB->commit();
						return true;
					}else{
						$error=_tr("Password couldn't be updated")." ".$pACL->errMsg;
						$this->_DB->rollBack();
						return false;
					}
				}else{
					$this->_DB->commit();
					return true;
				}
			}else{
				$error=_tr("Can't set email contact.")." ".$pACL->errMsg;
				$this->_DB->rollBack();
				return false;
			}
		}else{
			$error=_tr("User couldn't be update.")." ".$pACL->errMsg;
			$this->_DB->rollBack();
			return false;
		}
	}

	function updateUserOrganization($idUser, $name, $md5password, $password1, $extension, $fax_extension,$countryCode, $areaCode, $clidNumber, $cldiName, $idGrupo, $quota, $userLevel1,&$reAsterisk){
		include_once "libs/cyradm.php";
        include_once "configs/email.conf.php";
		$pACL=new paloACL($this->_DB);
		$pEmail = new paloEmail($this->_DB);
		$pFax = new paloFax($this->_DB);
		$continuar=true;
		$Exito = false;
		$error="";
		$cExten=false;
		$cFExten=false;
		$pDB2=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
		$arrBackup=array();
		$editFax=false;
		$faxProperties=array();
		
		$arrUser=$pACL->getUsers($idUser);
		if($arrUser===false || count($arrUser)==0 || !isset($idUser)){
			$this->errMsg=_tr("User dosen't exist");
			return false;
		}

		if($pACL->isUserSuperAdmin($arrUser[0][1])){
			$this->errMsg=_tr("Invalid Action");
			return false;
		}

		$arrOrgz=$this->getOrganizationById($arrUser[0][4]);
	
		$username=$arrUser[0][1];
		$oldExten=$arrUser[0][5];
        $oldFaxExten=$arrUser[0][6];
        
		$pDevice=new paloDevice($arrOrgz["domain"],"sip",$pDB2);
        $arrExtUser=$pDevice->getExtension($oldExten);
        $arrFaxExtUser=$pDevice->getFaxExtension($oldFaxExten);
		
		if($name=="")
			$name=$username;

		$pDB2->beginTransaction();

		if($userLevel1=="other"){
			$extension=$arrUser[0][5];
			$fax_extension=$arrUser[0][6];
			$quota=$pACL->getUserProp($idUser,"email_quota");
			$idGrupo=$arrUser[0][7];
			$modificarExts=false;
		}else{
			//verificar si el usuario cambio de extension y si es asi que no este siendo usado por otro usuario
			if($extension!=$oldExten){
				if($pDevice->existExtension($extension,$arrExtUser["tech"])==true){
					$this->errMsg=$pDevice->errMsg;
					return false;
				}else
					$cExten=true;
			}

			//verificar si el usuario cambio de fax extension
			if($fax_extension!=$oldFaxExten){
				if($pDevice->existExtension($fax_extension,"iax2")==true){
					$this->errMsg=$pDevice->errMsg;
					return false;
				}else
					$cFExten=true;
			}

			//para cambiar al usuario de extension o faxextension es necesario que se haya llenado el campo password para
			//poder crear las extensiones con la clave correcta
			if($cExten || $cFExten){
				if(is_null($md5password) || $md5password=="" || is_null($password1) || $password1==""){
					$this->errMsg=_tr("Need fill field password");
					return false;
				}
			}
		}
		
		$org_areaCode=$pACL->getUserProp($idUser,"area_code");
        $org_clidNumber=$pACL->getUserProp($idUser,"clid_number");
        $org_countryCode=$pACL->getUserProp($idUser,"country_code");
        $org_cldiName=$pACL->getUserProp($idUser,"clid_name");
        
		if(isset($clidNumber) && $clidNumber!=""){
            $faxProperties["clid_number"] = $clidNumber;
        }else{
            $faxProperties["clid_number"] = (isset($org_clidNumber) && $org_clidNumber!="")?$org_clidNumber:$fax_extension;
            $clidNumber=$faxProperties["clid_number"];
        }
        
        if(isset($cldiName) && $cldiName!=""){
            $faxProperties["clid_name"] =$cldiName;
        }else{
            $faxProperties["clid_name"] = (isset($org_cldiName) && $org_cldiName!="")?$org_cldiName:$name;
            $cldiName=$faxProperties["clid_name"];
        }
		
		$this->_DB->beginTransaction();
		//actualizamos la informacion de usuario que esta en la tabla acl_user
		if($pACL->updateUser($idUser, $name, $extension, $fax_extension)){
			//actualizamos el grupo al que pertennece el usuario
			if($pACL->addToGroup($idUser, $idGrupo)){

				//seteamos los registros en la tabla user_properties orrespondientes a la categoria fax
				$faxProperties["country_code"] =$countryCode;
				$faxProperties["area_code"] =$areaCode;
				
				foreach($faxProperties as $key => $value){
					if(!$pACL->setUserProp($idUser,$key,$value,"fax")){
						$error= "Error setting $key in table user_properties. ".$pACL->errMsg;
						$continuar=false;
						break;
					}
				}

				$old_quota=$pACL->getUserProp($idUser,"email_quota");
				//actualizamos la cuota de correo
				if(isset($quota) && $quota!="" && $continuar){
					if($pEmail->updateQuota($old_quota,$quota,$username)){
						if(!$pACL->setUserProp($idUser,"email_quota",$quota,"email")){
							$error= _tr("Error setting email quota").$pACL->errMsg;
							$pEmail->updateQuota($quota,$old_quota);
							$this->_DB->rollBack();
							$continuar=false;
						}
					}else{
						$error= _tr("Error setting email quota").$pEmail->errMsg;
						$continuar=false;
					}
				}

				if($continuar && $userLevel1!="other"){
					$port=$pACL->getUserProp($idUser,"port");
					$modificarExts=$this->modificarExtensionsUsuario($cExten,$cFExten,$pDB2,$arrOrgz["domain"],$oldExten,$oldFaxExten,$extension,$fax_extension,$password1,$md5password,$name,$username,$cldiName,$clidNumber,$port,$arrBackup);

					if($modificarExts==false){
						$error=_tr("Couldn't updated user extensions").$this->errMsg;
						$continuar=false;
					}
				}
				
				if(!$cFExten && $continuar){
                    if(!$pDevice->editFaxDevice(array("name"=>$fax_extension,"fullname"=>$cldiName,"cid_number"=>$clidNumber))){
                        $this->errMsg=_tr("Fax Extension couldn't be updated").$pDevice->errMsg;
                        $continuar=false;
                    }
                }

				//actualizamos el password del usuario
				if($password1!=="" && $continuar){
					if($pACL->changePassword($idUser,$md5password)){
						//en caso que no se hayan modificado las extensiones del usuario entonces es necesario actualizar los password de los canales sip, iax y del voicemail
						if(!$cExten){
							if(!$pDevice->changePasswordExtension($password1,$extension)){
								$this->errMsg=_tr("Extension password couldn't be updated").$pDevice->errMsg;
								$continuar=false;
							}
						}
						
						if(!$cFExten && $continuar){
							if(!$pDevice->changePasswordFaxExtension($password1,$fax_extension)){
								$this->errMsg=_tr("Fax Extension password couldn't be updated").$pDevice->errMsg;
								$continuar=false;
							}
						}

						if($continuar){
							if(!$pFax->editFax($idUser,$countryCode,$areaCode,$cldiName,$clidNumber,$arrOrgz["code"]."_".$fax_extension,$md5password,$username)){
								$continuar=false;
								$error=_tr("Error Updating fax configuration").$pFax->errMsg;
							}else{
								if(!$pEmail->updatePassword($username,$password1)){
									$continuar=false;
									$error=_tr("Password couldn't be updated")." ".$pEmail->errMsg;
									$editFax=true;
								}
							}
						}
					}else{
						$error=_tr("Password couldn't be updated")." ".$pACL->errMsg;
						$continuar=false;
					}
				}else{
					if($continuar){
						if(!$pFax->editFax($idUser,$countryCode,$areaCode,$cldiName,$clidNumber,$arrOrgz["code"]."_".$fax_extension,$arrUser[0][3],$username)){
							$continuar=false;
							$error=_tr("Error Updating fax configuration").$pFax->errMsg;
						}
					}
				}

				if($continuar){
					$Exito=true;
					$this->_DB->commit();
					$pDB2->commit();
					//recargamos la configuracion en realtime de los dispositivos para que tomen efectos los cambios
					if($cExten){
                        //se cambio la extension del usuario hay que eliminar de cache la anterior
                        $pDevice->tecnologia->prunePeer($arrExtUser["device"],$arrExtUser["tech"]);
					}else{
                        $pDevice->tecnologia->prunePeer($arrExtUser["device"],$arrExtUser["tech"]);
                        $pDevice->tecnologia->loadPeer($arrExtUser["device"],$arrExtUser["tech"]);
					}
					
					if($cFExten){
                        //se cambio la faxextension del usuario hay que eliminar de cache la anterior
                        $pDevice->tecnologia->prunePeer($arrFaxExtUser["device"],$arrFaxExtUser["tech"]);
                    }else{
                        //se recarga la faxextension del usuario por los cambios que pudo haber
                        $pDevice->tecnologia->prunePeer($arrFaxExtUser["device"],$arrFaxExtUser["tech"]);
                        $pDevice->tecnologia->loadPeer($arrFaxExtUser["device"],$arrFaxExtUser["tech"]);
                    }
                    
					$pFax->restartService();
				}else{
					$this->_DB->rollBack();
					$pDB2->rollBack();
					if($editFax==true){
						$pFax->editFax($idUser,$org_countryCode,$org_areaCode,$org_cldiName,$org_clidNumber,$arrFaxExtUser["device"],$arrUser[0][3],$username);
					}
					if($cExten==true){
						$pDevice->deleteAstDBExt($extension,$arrOrgz["code"]."_".$extension);
						$pDevice->restoreBackupAstDBEXT($arrBackup);
					}
				}
			}else{
				$error=_tr("Failed Updated Group")." ".$pACL->errMsg;
				$this->_DB->rollBack();
				$pDB2->rollBack();
			}
		}else{
			$error=_tr("User couldn't be update")." ".$pACL->errMsg;
			$this->_DB->rollBack();
			$pDB2->rollBack();
		}

		if($cExten || $cFExten)
			$reAsterisk=true;

		$this->errMsg=$error." ".$this->errMsg;
		return $Exito;
	}

	private function modificarExtensionsUsuario(&$EXTEN,&$FEXTEN,&$pDB2,$domain,$oldExten,$oldFaxExten,$extension,$fax_extension,$password,$md5password,$name,$username,$cldiName,$clidNumber,$port,&$arrBackup){
		$continuar=true;
		$pDevice=new paloDevice($domain,"sip",$pDB2);
		$error="";

		//1.- verificar si el usuario cambio de extension y si es asi que no este siendo usado por otro usuario
		//2.- Tomar un backup de las entradas en la base astDB para dicha extension
		//3.- Eliminar la extension anterior
		//4.- Crear la nueva extension


		//creamos la extension iax para el fax del usuario
		if($FEXTEN){
			if(!$pDevice->deleteFaxExtension($oldFaxExten)){
				$this->errMsg=_tr("Old Fax extension can't be deleted").$pDevice->errMsg;
				return false;
			}
			//borramos el channel del fax anterior anterior
			$arrPropFax=$this->setParameterFaxExtension($domain,"iax2",$fax_extension,$password,$cldiName,$clidNumber,$port,$pDB2);
			if($arrPropFax==false){
				$error=$this->errMsg;
				return false;
			}else{
				if($pDevice->createFaxExtension($arrPropFax,"iax2")==false){
					$error=$pDevice->errMsg;
					return false;
				}
			}
		}

		//creamos la extension del usuario
		if($EXTEN){
			$arrBackup=$pDevice->backupAstDBEXT($oldExten);
			//borramos la extension anterior
			if(!$pDevice->deleteExtension($oldExten)){
				$this->errMsg=_tr("Old extension can't be deleted").$pDevice->errMsg;
				return false;
			}

			//creamos una nueva para el usuario
			$arrProp=$this->setParameterUserExtension($domain,"sip",$extension,$password,$name,$username,$pDB2);
			if($arrProp==false){
				$error=$this->errMsg;
				$continuar=false;
			}else{
				if($pDevice->createNewDevice($arrProp,"sip")==false){
					//si no se pude crear la extension anterior se restaura los valores anteriores en la base astDB
					$pDevice->restoreBackupAstDBEXT($arrBackup);
					$error=$pDevice->errMsg;
					$continuar=false;
				}
			}
		}

		$this->errMsg=$error;
		return $continuar;
	}


    function deleteUserOrganization($idUser){
        include_once "libs/cyradm.php";
        include_once "configs/email.conf.php";
		$pACL=new paloACL($this->_DB);
		$pEmail = new paloEmail($this->_DB);
		$pFax = new paloFax($this->_DB);
		$Exito=false;

		//1)se comprueba de que el ID de USUARIO se un numero
		//2)se verifica que exista dicho usuario
		//3)se recompila los datos del usuario de las tablas acl_user y user_properties
		//4)se elimina al usuario de la base
		//5)se elimina la extension de uso del usuario y la extension de fax
		//6)se trata de eliminar la cuenta de fax
		//7)se elimina el buzon de correo
		if (!preg_match('/^[[:digit:]]+$/', "$idUser")) {
            $this->errMsg = _tr("User ID is not numeric");
			return false;
        }else{
			$arrUser=$pACL->getUsers($idUser);
			if($arrUser===false || count($arrUser)==0){
				$this->errMsg=_tr("User dosen't exist");
				return false;
			}
		}

		$devId=$pACL->getUserProp($idUser,"dev_id");
		$port=$pACL->getUserProp($idUser,"port");
		$countryCode=$pACL->getUserProp($idUser,"country_code");
		$areaCode=$pACL->getUserProp($idUser,"area_code");
		$cldiName=$pACL->getUserProp($idUser,"clid_name");
		$clidNumber=$pACL->getUserProp($idUser,"clid_number");
		$picture=$pACL->getUserPicture($idUser);

		$pDB2=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));

		$idDomain=$arrUser[0][4];
		$query="Select domain from organization where id=?";
		$getDomain=$this->_DB->getFirstRowQuery($query, false, array($idDomain));
		if($getDomain==false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}
		
		$pDevice=new paloDevice($getDomain[0],"sip",$pDB2);
        $arrExten=$pDevice->getExtension($arrUser[0][5]);
        $arrFaxExten=$pDevice->getFaxExtension($arrUser[0][6]);
        
		$ruta_destino="/var/www/elastixdir/users_images";
		$this->_DB->beginTransaction();
		$pDB2->beginTransaction();
		//tomamos un backup de las extensiones que se van a eliminar de la base astDB por si algo sale mal
		//y ahi que restaurar la extension
		$arrExt=$pDevice->backupAstDBEXT($arrUser[0][5]);
		if($pACL->deleteUser($idUser)){
			if($pDevice->deleteExtension($arrUser[0][5]) && $pDevice->deleteFaxExtension($arrUser[0][6])){
				if($pFax->deleteFax($devId)){
					if($pEmail->eliminar_cuenta($arrUser[0][1])){
						if(isset($picture[0]))
							unlink($ruta_destino."/".$picture[0]);
						$Exito=true;
						$pDB2->commit();
						$this->_DB->commit();
						$pDevice->tecnologia->prunePeer($arrExten["device"],$arrExten["tech"]);
						$pDevice->tecnologia->prunePeer($arrFaxExten["device"],$arrFaxExten["tech"]);
						$pFax->restartService();
					}else{
						$pDevice->restoreBackupAstDBEXT($arrExt);
						$pDB2->rollBack();
						$this->_DB->rollBack();
						$pFax->createFax($idUser,$countryCode,$areaCode,$cldiName,$clidNumber,$arrUser[0][6],$arrUser[0][3],$arrUser[0][1],$port,$devId);
						$this->errMsg=_tr("Email Account cannot be deleted").$pEmail->errMsg;
					}
				}else{
					$this->errMsg=_tr("Fax cannot be deleted").$pFax->errMsg;
					$pDevice->restoreBackupAstDBEXT($arrExt);
					$pDB2->rollBack();
					$this->_DB->rollBack();
				}
			}else{
				$this->errMsg=_tr("User Extension can't be deleted").$pDevice->errMsg;
				$pDevice->restoreBackupAstDBEXT($arrExt);
				$pDB2->rollBack();
				$this->_DB->rollBack();
			}
		}else{
			$this->errMsg=$pACL->errMsg;
			$this->_DB->rollBack();
		}
		return $Exito;
    }
}
?>