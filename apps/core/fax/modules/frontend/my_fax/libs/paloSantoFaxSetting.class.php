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
  $Id: paloSantoForm.class.php,v 1.4 2007/05/09 01:07:03 gcarrillo Exp $ */
global $arrConf;
 
class paloMyFax{

    public $_DB;
    private $errMsg;
    private $idUser;

    public function paloMyFax(&$pDB,$idUser){
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

        $this->idUser=$idUser;
    }


    function getErrorMsg(){
        return $this->errMsg;
    }

    /**
     * Returns fax extension user data
     * It returns also voicemail user data If user have an active voicemail
     */
    function getMyFaxExtension(){
        $myFax=array();
        $arrProp=array();
        //1 obtener el fax extension del mismo de la tabla acl_user
        //2 si no tiene  fax extension retornamos false
        //3 obtenemos los datos de la organization
        //4 leer los datos de la tabla fax 
        //5 leer los datos de la tabla user_properties (tabla clave valor)

        $faxExten=$this->getFaxExtensionUser();
        if($faxExten===false){
            return false;
        }

        $orgInfo=$this->userOrganizationInfo();
        if($orgInfo===false){
            return false;
        }
        
        //$arrProp['id']= $this->idUser;
        $arrProp['exten']= $faxExten;
        $arrProp['organization_domain']= $orgInfo['domain'];       
 
        $pMyFax = new paloFax($this->_DB);
        $faxSettings = $pMyFax->getFaxList($arrProp);
        
        $myFax['FAX_EXTEN']=$faxExten;      
        $myFax['DEVICE']=$faxSettings[0]['device'];
        $myFax['CID_NAME']=$faxSettings[0]['clid_name'];
        $myFax['CID_NUMBER']=$faxSettings[0]['clid_number'];
        $myFax['COUNTRY_CODE']=$faxSettings[0]['country_code'];
        $myFax['AREA_CODE']=$faxSettings[0]['area_code'];
        $myFax['FAX_SUBJECT']=$faxSettings[0]['fax_subject'];
        $myFax['FAX_CONTENT']=$faxSettings[0]['fax_content'];
        
        $devID=$faxSettings[0]['dev_id'];
        $myFax['MODEM']='ttyIAX'.$devID;
        
        $faxStatus = $pMyFax->getFaxStatusByModem($devID);
        $myFax['STATUS'] = $faxStatus["modems"]["ttyIAX".$devID];

        return $myFax;
    }

    private function getFaxExtensionUser(){
        $query="SELECT fax_extension FROM acl_user WHERE id=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($this->idUser));
        if($result===false){
            $this->errMsg=_tr("DATABASE ERROR").' '.$this->_DB->errMsg;
            return false;
        }elseif(count($result)==0){
            $this->errMsg=_tr("User does not exist").' '.$this->_DB->errMsg;
            return false;
        }else{
            if($result['fax_extension']=='' || is_null($result['fax_extension'])){
                $this->errMsg=_tr("User does not have an fax extension");
                return false;                
            }else{
                return $result['fax_extension'];
            }            
        }    
    }

    //obtenemos el dominio y el codigo de la organizacion a la que pertenece 
    //el usuario
    private function userOrganizationInfo(){
        $query="select org.id, org.domain, org.code from acl_user acu ".
                    "join acl_group acg on acu.id_group = acg.id ".
                    "join organization org on acg.id_organization = org.id ".
                        "where acu.id=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($this->idUser));
        if($result===false){
            $this->errMsg=_tr("DATABASE ERROR").' '.$this->_DB->errMsg;
            return false;
        }elseif(count($result)==0){
            $this->errMsg=_tr("User does not exist").' '.$this->_DB->errMsg;
            return false;
        }else{
            return $result;   
        }
    }

    function editFaxExten($arrProp){
        require_once("libs/paloSantoPBX.class.php");
        $errorData=array();
        $errorBoolean= false;

        if($arrProp['clid_name']==''){
            $errorData['field'][] = "CID_NAME";
            $errorBoolean= true;
        }

        if($arrProp['clid_number']==''){
            $errorData['field'][] = "CID_NUMBER";
            $errorBoolean= true;
        }elseif(!preg_match('/^[0-9]+$/', $arrProp['clid_number'])){
            $errorData['field'][] = "CID_NUMBER";
            $errorBoolean= true;
        }

        if($arrProp['country_code']==''){
            $errorData['field'][] = "COUNTRY_CODE";
            $errorBoolean= true;
        }elseif(!preg_match('/^[0-9]+$/', $arrProp['country_code'])){
            $errorData['field'][] = "COUNTRY_CODE";
            $errorBoolean= true;
        }

        if($arrProp['area_code']==''){
            $errorData['field'][] = "AREA_CODE";
            $errorBoolean= true;
        }elseif(!preg_match('/^[0-9]+$/', $arrProp['area_code'])){
            $errorData['field'][] = "AREA_CODE";
            $errorBoolean= true;
        }

        if($arrProp['fax_subject']==''){
            $errorData['field'][] = "FAX_SUBJECT";
            $errorBoolean= true;
        }

        if($arrProp['fax_content']==''){
            $errorData['field'][] = "FAX_CONTENT";
            $errorBoolean= true;
        }

        if($errorBoolean){
            $errorData['stringError'] = "Some fields are wrong";
            $this->errMsg = $errorData;
            return false;
        }
              
        $pMyFax = new paloFax($this->_DB);
        if(!$pMyFax->editFaxToUser($arrProp)){
            return false;
        }

        if(!$pMyFax->restartService()){
            return false;
        }

        return true;
    }
}
?>
