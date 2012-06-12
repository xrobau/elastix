<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                 |
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
  $Id: paloSantoFileEndPoint.class.php,v 1.1 2008/01/22 15:05:57 afigueroa Exp $ */

class PaloSantoFileEndPoint
{
    var $directory;
    var $errMsg;
    var $ipAdressServer;

    function PaloSantoFileEndPoint($dir){
        $this->directory = $dir;
        $this->ipAdressServer = $_SERVER['SERVER_ADDR'];
    }

    /*
        La funcion createFiles nos permite crear los archivos de configuracion de un EndPoint
        Para ello recibimos un arreglo con los datos necesarios para crear estos archivos,
        Entre los datos tenemos el nombre del vendor, nombre de archivo, mas address.
     */
    function createFiles($ArrayData)
    {
        include_once "vendors/{$ArrayData['vendor']}.cfg.php";
        switch($ArrayData['vendor'])
        {
            case 'Polycom':
                //Header Polycom
                $contentHeader = HeaderFilePolycom($ArrayData['data']['filename']);

                if($this->createFileConf($this->directory, $ArrayData['data']['filename'].".cfg", $contentHeader))
                {
                    //Archivo Principal
                    $contentFilePolycom = PrincipalFilePolycom($ArrayData['data']['DisplayName'], $ArrayData['data']['id_device'], $ArrayData['data']['secret']);

                    if($this->createFileConf($this->directory, $ArrayData['data']['filename']."reg.cfg", $contentFilePolycom))
                        return true;
                    else return false;
                }else return false;

                break;

            case 'Linksys':
                $contentFileLinksys =PrincipalFileLinksys($ArrayData['data']['DisplayName'], $ArrayData['data']['id_device'], $ArrayData['data']['secret'],$this->ipAdressServer);
                if($this->createFileConf($this->directory, "spa".$ArrayData['data']['filename'].".cfg", $contentFileLinksys))
                {
                    if(conexionHTTP($ArrayData['data']['ip_endpoint'], $this->ipAdressServer, $ArrayData['data']['filename']))
                        return true;
                    else return false;
                }
                else return false;

                break;

            case 'Aastra':
                $contentFileAastra =PrincipalFileAastra($ArrayData['data']['DisplayName'], $ArrayData['data']['id_device'], $ArrayData['data']['secret'],$this->ipAdressServer);
                if($this->createFileConf($this->directory, $ArrayData['data']['filename'].".cfg", $contentFileAastra))
                    return true;
                else return false;

                break;

            case 'Cisco':
                $contentFileCisco =PrincipalFileCisco($ArrayData['data']['DisplayName'], $ArrayData['data']['id_device'], $ArrayData['data']['secret'],$this->ipAdressServer);
                if($this->createFileConf($this->directory, $ArrayData['data']['filename'].".cnf", $contentFileCisco))
                    return true;
                else return false;

                break;

            case 'Atcom':
                if($ArrayData['data']['model'] == "AT 320")
                {
                    $contentFileAtcom = PrincipalFileAtcom320($ArrayData['data']['DisplayName'], $ArrayData['data']['id_device'], $ArrayData['data']['secret'],$this->ipAdressServer,$ArrayData['data']['filename']);
                    $result = $this->telnet($ArrayData['data']['ip_endpoint'], "", "12345678", $contentFileAtcom);
                    if($result) return true;
                    else return false;
                }else if($ArrayData['data']['model'] == "AT 530"){
                    $contentFileAtcom = PrincipalFileAtcom530($ArrayData['data']['DisplayName'], $ArrayData['data']['id_device'], $ArrayData['data']['secret'],$this->ipAdressServer,$ArrayData['data']['filename'], $ArrayData['data']['arrParameters']['versionCfg']);
                    if($this->createFileConf($this->directory,"atc".$ArrayData['data']['filename'].".cfg", $contentFileAtcom))
                    {
                        $arrComandos = arrAtcom530($this->ipAdressServer, $ArrayData['data']['filename']);
                        $result = $this->telnet($ArrayData['data']['ip_endpoint'], "admin", "admin", $arrComandos);
                        if($result) return true;
                        else return false;
                    }else return false;
                }

                break;

            case 'Snom':
                break;

            case 'Grandstream':
                break;
        }
    }

    /*
        Esta funcion nos permite crear un archivo de configuracion
        Recibe el directorio, nombre de archivo, contenido del archivo.
     */
    function createFileConf($tftpBootPath, $nameFileConf, $contentConf)
    {
        global $arrLang;

        $nameFileConf = strtolower($nameFileConf);
        $fd = fopen ($tftpBootPath.$nameFileConf, "w");
        if ($fd){
            fputs($fd,$contentConf,strlen($contentConf)); // write config file
        fclose ($fd);
            return true;
        }
        $this->errMsg = $arrLang['Unable write the file'].": $nameFileConf";
        return false;
    }


    /*
        La funcion deleteFiles nos permite eliminar los archivos de configuracion de un
        EndPoint. Para ello recibimos un arreglo con los datos necesarios para eliminar
        estos archivos. Los datos recibidos son el nombre del vendor, nombre de archivo.
     */
    function deleteFiles($ArrayData)
    {
        switch($ArrayData['vendor'])
        {
            case 'Polycom':
                if($this->deleteFileConf($this->directory, $ArrayData['data']['filename']."reg.cfg")){
                    return $this->deleteFileConf($this->directory, $ArrayData['data']['filename'].".cfg");
                } else false;
                break;

            case 'Linksys':
                return $this->deleteFileConf($this->directory, "spa".$ArrayData['data']['filename'].".cfg");
                break;

            case 'Aastra':
                return $this->deleteFileConf($this->directory, $ArrayData['data']['filename'].".cfg");
                break;

            case 'Cisco':
                return $this->deleteFileConf($this->directory, $ArrayData['data']['filename'].".cnf");
                break;

            case 'Atcom':
                return $this->deleteFileConf($this->directory, "atc".$ArrayData['data']['filename'].".cfg");
                break;

            case 'Snom':
                break;

            case 'Grandstream':
                break;
        }
    }

    /*
        Esta funcion nos permite eliminar un archivo de configuracion
        Recibe el directorio, nombre de archivo.
     */
    function deleteFileConf($tftpBootPath, $nameFileConf)
    {
        global $arrLang;

        $nameFileConf = strtolower($nameFileConf);
        if (file_exists($tftpBootPath.$nameFileConf)) {
            if(!unlink($tftpBootPath.$nameFileConf)){
                $this->errMsg = $arrLang['Unable delete the file'].": $nameFileConf";
                return false;
            }
            return true;
        }
    }

    function createFilesGlobal($vendor)
    {
        include_once "vendors/{$vendor}.cfg.php";

        switch($vendor){
            case 'Polycom':
                //PASO 1: Creo los directorios Polycom.
                if(mkdirFilePolycom($this->directory)){
                    $contentFilePolycom = serverFilePolycom($this->ipAdressServer);

                    //PASO 2: Creo el archivo server.cfg
                    if($this->createFileConf($this->directory, "server.cfg", $contentFilePolycom)){
                        $contentFilePolycom = sipFilePolycom($this->ipAdressServer);

                        //PASO 3: Creo el archivo sip.cfg
                        return $this->createFileConf($this->directory, "sip.cfg", $contentFilePolycom);
                    } else return false;
                } else return false;

                break;

            case 'Linksys':
                //Creando archivos de ejemplo.
                $contentFileLinksys = templatesFileLinksys($this->ipAdressServer);
                $this->createFileConf($this->directory, "spaxxxxxxxxxxxx.template.cfg", $contentFileLinksys);
                return true; //no es tan importante la necesidad de estos archivos solo son de ejemplo.
                break;

            case 'Aastra':
                //Creando archivos de ejemplo.
                $contentFileAatra = templatesFileAastra($this->ipAdressServer);
                $this->createFileConf($this->directory, "aasxxxxxxxxxxxx.template.cfg", $contentFileAatra);
                return true; //no es tan importante la necesidad de estos archivos solo son de ejemplo.
                break;

            case 'Cisco':
                break;

            case 'Atcom':
                //Creando archivos de ejemplo.
                $contentFileAtcom = templatesFileAtcom($this->ipAdressServer);
                $this->createFileConf($this->directory, "atcxxxxxxxxxxxx.template.cfg", $contentFileAtcom);
                return true; //no es tan importante la necesidad de estos archivos solo son de ejemplo.
                break;

            case 'Snom':
                break;

            case 'Grandstream':
                break;
        }
    }

    function telnet($ip, $user, $password, $arrComandos){
        if ($fsock = fsockopen($ip, 23, $errno, $errstr, 30))
        {
            if(is_array($arrComandos) && count($arrComandos)>0)
            {
                if($user!="" && $user!=null){
                    fputs($fsock, "$user\r");
                    fread($fsock,1024);
                }
                if($password!="" && $password!=null){
                    fputs($fsock, "$password\r");
                    fread($fsock,1024);
                }
                foreach($arrComandos as $comando => $valor)
                {
                    $line = $comando;
                    if($valor!="")
                        $line = "$comando $valor";

                    fputs($fsock, "$line\r");
                    fread($fsock,1024);
                }
            }
            fclose($fsock);
            return true;
        }else return false;
    }

    function updateArrParameters($vendor, $model, $arrParametersOld)
    {
        switch($vendor)
        {
            case 'Polycom':
                break;

            case 'Linksys':
                break;

            case 'Aastra':
                break;

            case 'Cisco':
                break;

            case 'Atcom':
                if($model == 'AT 530')
                {
                    if(isset($arrParametersOld['versionCfg']))
                        $arrParametersOld['versionCfg'] = $arrParametersOld['versionCfg'] + 0.0001;
                    else
                        $arrParametersOld['versionCfg'] = '2.0005';
                }
                break;

            case 'Snom':
                break;

            case 'Grandstream':
                break;
        }

        return $arrParametersOld;
    }
}
?>