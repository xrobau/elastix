<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                               |
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
  $Id: paloSantoGeneralInformation.class.php,v 1.1 2008-12-20 04:12:14 Andres Flores aflores@palosanto.com Exp $ */
class paloSantoGeneralInformation {
    var $_DB;
    var $errMsg;

    function paloSantoGeneralInformation(&$pDB)
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

    function addInformation($data)
    {
        $arrTmp = array();
        foreach($data as $key => $value)
           $arrTmp[$key] = $this->_DB->DBCAMPO($value);

        $queryInsert = $this->_DB->construirInsert('general', $arrTmp);
        $result = $this->_DB->genQuery($queryInsert);
        return $result;
    }

    function uploadInformation($sTabla, $data)
    {

        $arrTmp = array();
        foreach($data as $key => $value)
           $arrTmp[$key] = $this->_DB->DBCAMPO($value);

        $queryInsert = $this->_DB->construirUpdate($sTabla, $arrTmp);
        $result = $this->_DB->genQuery($queryInsert);
        return $result;
    }

    function updateCertificate($certificate)
    {
      $tmpCertificate = $this->_DB->DBCAMPO($certificate);
      $query = "UPDATE general SET certificate=$tmpCertificate";
      $result = $this->_DB->genQuery($query);
      if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return false;
      }else
          return true;

    }

    function getGeneralInformation()
    {
      $query = "SELECT * FROM general ";
      $result=$this->_DB->fetchTable($query, true);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

     //Funcion que crea el archivo dundi_mappings_custom_elastix.conf
    function createFileDMCE($ipServer)
    {
       $arrData = array();
       $arrData['dundi']['canonical']['name'] = "dundi-priv-canonical";
       $arrData['dundi']['canonical']['number'] = 0;
       $arrData['dundi']['customers']['name'] = "dundi-priv-customers";
       $arrData['dundi']['customers']['number'] = 100;
       $arrData['dundi']['pstn']['name'] = "dundi-priv-via-pstn";
       $arrData['dundi']['pstn']['number'] = 400;

       $dundi_file = "/etc/asterisk/dundi_mappings_custom_elastix.conf";
       $fh = fopen($dundi_file, "w+");
       if($fh){
        foreach($arrData as $dundi)
        {
            foreach($dundi as $key)
            {
              if(fwrite($fh,"priv => {$key['name']},{$key['number']},IAX2,dundi:\${SECRET}@$ipServer/\${NUMBER},nopartial"."\n") == false)
              {
                $this->errMsg = $arrLang["Unabled write file"];
                fclose($fh);
                return false;
              }
            }
         }
         fclose($fh);
        }
        else{
            $this->errMsg = $arrLang["Unabled open file"];
            return false;
        }
        return true;
    }

    //Funcion que crea el archivo dundi_general_custom_elastix.conf
    function createFileDGCE($arrInfoGeneral, $mac)
    {
       $arrTmp = "";
       $dundi_file = "/etc/asterisk/dundi_general_custom_elastix.conf";
       $fh = fopen($dundi_file, "w+");
       if($fh){
         foreach($arrInfoGeneral as $key => $value){
            if(fwrite($fh, "$key=$value\n") == false){
                $this->errMsg = $arrLang["Unabled write file"];
                fclose($fh);
                return false;
            }
         }
         if(fwrite($fh, "entityid=$mac") == false){
                $this->errMsg = $arrLang["Unabled write file"];
                fclose($fh);
                return false;
         }
         fclose($fh);
        }
        else{
            $this->errMsg = $arrLang["Unabled open file"];
            return false;
        }
       return true;
    }

    function genRandomPassword($length = 32, $certificate)
    {
        $salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $len = strlen($salt);
        $makepass = '';
        mt_srand(10000000 * (double) microtime());

        for ($i = 0; $i < $length; $i ++) {
            $makepass .= $salt[mt_rand(0, $len -1)];
        }
        $makepass .= $certificate;
        $result = hash('whirlpool', $makepass);
        return $result;
    }

}

    
?>
