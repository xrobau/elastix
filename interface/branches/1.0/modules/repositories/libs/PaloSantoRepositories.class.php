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
  $Id: PaloSantoRepositories.php $ */

include_once("libs/paloSantoDB.class.php");

class PaloSantoRepositories
{
    var $errMsg;

    function PaloSantoRepositories()
    {

    }

     /**
     * Procedimiento para obtener el listado de los repositorios 
     *
     * @return array    Listado de los repositorios 
     */
    function getRepositorios($ruta)
    {
        $arrArchivosRepo = $this->getArchivosRepo($ruta);
        $repositorios = array();
        foreach($arrArchivosRepo as $key => $archivoRepo){
            $auxRepo      = $this->scanFileRepo($ruta,$archivoRepo);
            $repositorios = array_merge($repositorios,$auxRepo); 
        }
        return $repositorios;
    }

    function setRepositorios($ruta,$arrReposActivos)
    {
        $arrArchivosRepo = $this->getArchivosRepo($ruta);
        $repositorios = array();
        foreach($arrArchivosRepo as $key => $archivoRepo){
            $this->replaceFileRepo($ruta,$archivoRepo,$arrReposActivos);
        }
    }

    function getArchivosRepo($dir='/etc/yum.repos.d/')
    {
        global $arrLang;
        $arr_repositorios  = scandir($dir);
        $arr_respuesta = array();
        
        if (is_array($arr_repositorios) && count($arr_repositorios) > 0) {
            foreach($arr_repositorios as $key => $repositorio){ 
                if(!is_dir($dir.$repositorio) && $repositorio!="." && $repositorio!=".." && strstr($repositorio,".repo")) //que se un archivo y que el archivo tenga extension .repo
                    $arr_respuesta[$repositorio] = $repositorio;
            }
        } 
        else 
            $this->errMsg = $arrLang["Repositor not Found"];
        return $arr_respuesta;
    }


    function scanFileRepo($ruta,$file)
    {
        $repositorios = array();
        $indice = -1;
        if($report_handle = fopen($ruta.$file, "r")){
            $bandera = 'nofoundRepo';

            while(!feof($report_handle)){
                $linea = trim(fgets($report_handle,1024)); 
                if(substr($linea,0,1)!='#'){ //para ignorar los comentarios
                    if(ereg("^\[?(.+)\]",$linea,$reg1)){//se busca [repo] 
                        $indice++;
                        $bandera = 'foundRepo'; //sirve para indicar que encontre un repositorio, y en la proxima iteracion esta el nombre completo del repositorio, esto se hace en el proximo if(...)
                    }
                    else if($bandera=='foundRepo'){ 
                        if(ereg("^name=",$linea,$reg2)){
                            $name = substr($linea,5);
                            $repositorios[$indice] = array('id' => $reg1[1],'name' => $name, 'file' => $file, 'activo' => '1'); //activo esta setedo temporalmente para que despues sea seteado
                        }
                        else if(ereg("^enabled=([[:digit:]]{1,})",$linea,$reg3)){
                            if($repositorios[$indice]['id']==$reg1[1]){ //aseguro que es el repositorio
                                $repositorios[$indice]['activo']=$reg3[1]; //cambio su estatus
                                $bandera = 'nofoundRepo';
                            }
                        }
                    }
                }
            }
        }
        fclose($report_handle);
        return $repositorios;
    } 

    function replaceFileRepo($ruta,$file,$arrReposActivos=array("elastix","base","elastix-beta"))
    {
        $indice = -1;
        unset($arrLine);

        if($report_handle = fopen($ruta.$file, "r")){
            while(!feof($report_handle))
                $arrLine[] = rtrim(fgets($report_handle,2048));
 
        }

        $keyGpgcheck = -1;
        $repoTmp = $repo = "";
        $encontradoEnabled = false; 
        if(is_array($arrLine) && count($arrLine) > 0){
            foreach($arrLine as $key => $line){
                if(substr($line,0,1)!='#' && $line!=""){ //para ignorar los comentarios
                    if(ereg("^\[?(.+)\]",$line,$repo)){//lo encontre
                        //$repo != $repoTmp... significa q canbio de repositorio
                        if($repo[1] != $repoTmp && !$encontradoEnabled && $keyGpgcheck!=-1){
                            $arrLine[$keyGpgcheck] = $arrLine[$keyGpgcheck]."\nenabled=".$this->activarRepo($arrReposActivos,$repoTmp);
                            $keyGpgcheck = -1;
                        }
                        $encontradoEnabled = false;
                        $repoTmp = $repo[1]; 
                    }
                    else if(ereg("^gpgcheck=[[:digit:]]{1,}",$line)){ //aparentemente esta linea siempre estara en cada repositorio
                        $keyGpgcheck = $key;
                    }
                    else if(ereg("^enabled=[[:digit:]]{1,}",$line)){
                        $encontradoEnabled = true;
                        $keyGpgcheck = -1;
                        $arrLine[$key]="enabled=".$this->activarRepo($arrReposActivos,$repoTmp);
                    } 
                }
            }
        }

        //quitando las ultimas lineas en blanco
        for($i=count($arrLine)-1;$i>=0;$i--){
            if($arrLine[$i]=="")
                array_pop($arrLine);
            else
                break;
        }

        //Update del archivo
        exec("sudo -u root chmod 777 ".$ruta.$file);
        if($report_handle = fopen($ruta.$file, "w")){
            if(is_array($arrLine) && count($arrLine) > 0){
                foreach($arrLine as $key => $line){
                    fputs($report_handle,$line."\n");
                }
            }
        }
        fclose($report_handle);
        exec("sudo -u root chmod 644 ".$ruta.$file);
    } 

    function activarRepo($arrReposActivos,$repo)
    {
        foreach($arrReposActivos as $key => $value){//si esta para ser modificado, lo modifico su enabled si no se entiende que quiere q se desactive
            if($value==$repo)
                return 1;
        }
        return 0;
    }

    function obtenerVersionDistro()
    {
        exec("rpm -q --queryformat '%{VERSION}' centos-release",$arrSalida,$flag);
        if($flag==0)
            return $arrSalida[0];
        else
            return '?';
    }
}
?>