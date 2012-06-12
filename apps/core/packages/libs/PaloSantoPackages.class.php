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
  $Id: PaloSantoPackages.php $ */

include_once("libs/paloSantoDB.class.php");

class PaloSantoPackages
{
    var $errMsg;

    function PaloSantoPackages()
    {

    }

    /**
     * Procedimiento para obtener el listado de los paquetes instaldos en el sistema. 
     * @param  string   $filtro    Si !="" buscar los paquetes con el nombre que se pasa
     *
     * @return array    Listado de paquetes, o FALSE en caso de error:
     */
    function getPackagesInstalados($ruta,$filtro="", $offset, $limit, $total)
    {
        global $arrLang;
        unset($respuesta);
        $paquetes = array(); 
        if($filtro!="")
            $filtro = " | grep $filtro";
        $offset_inv = $total-$offset;
        $comando = "rpm -qa --queryformat '%{NAME}|%{SUMMARY}|%{VERSION}|%{RELEASE}\n' $filtro | tail -n $offset_inv | head -n $limit";
        exec($comando,$respuesta,$retorno);

         if($retorno==0 && $respuesta!=null && count($respuesta) > 0 && is_array($respuesta)){
            foreach($respuesta as $key => $paqueteLinea){
                $paquete = explode("|",$paqueteLinea);
                $repositorio = $this->buscarRepositorioDelPaquete($ruta,$paquete[0],$paquete[2],$paquete[3], $offset, $limit);
                $paquetes[] = array("name" =>$paquete[0],"summary" =>$paquete[1],"version" =>$paquete[2],"release" =>$paquete[3],'repositorio' => $repositorio);
            }
         }
         else 
             $this->errMsg = $arrLang["Packages not Founds"];
        return($paquetes);
    }

    function getAllPackages($ruta,$filtro="")
    {
        if($filtro!="")
            $filtro = " where name like '%$filtro%'";

        $arr_repositorios = $this->getRepositorios($ruta); 
        $arrRepositoriosPaquetes = array(); 
        if (is_array($arr_repositorios) && count($arr_repositorios) > 0) {
            foreach($arr_repositorios as $key => $repositorio){
                $arr_paquetes = $this->getPaquetesDelRepositorio($ruta,$repositorio,$filtro);
                //$arrRepositoriosPaquetes[$repositorio] = $arr_paquetes;
                if(is_array($arr_paquetes) && count($arr_paquetes) > 0)
                     $arrRepositoriosPaquetes = array_merge($arrRepositoriosPaquetes,$arr_paquetes);
            }
        }
        return $arrRepositoriosPaquetes;
    }

    /**
     * Procedimiento para obtener el listado de los repositorios 
     *
     * @return array    Listado de los repositorios 
     */
    function getRepositorios($dir='/var/cache/yum/')
    {
        global $arrLang;

        $arr_repositorios  = scandir($dir);
        $arr_respuesta = array();

        if (is_array($arr_repositorios) && count($arr_repositorios) > 0) {
            foreach($arr_repositorios as $key => $repositorio){ 
                if(is_dir($dir.$repositorio) && $repositorio!="." && $repositorio!="..")
                    $arr_respuesta[$repositorio] = $repositorio;
            }
        } 
        else 
            $this->errMsg = $arrLang["Repositor not Found"];
        return $arr_respuesta;
    }

    function getPaquetesDelRepositorio($ruta,$repositorio,$filtro, $contar=false)
    {
        if(file_exists($ruta.$repositorio."/primary.xml.gz.sqlite")){
            $cadena_dsn = "sqlite3:///$ruta"."$repositorio"."/primary.xml.gz.sqlite";

            // se conecta a la base
            $pDB = new paloDB($cadena_dsn);

            if(!empty($pDB->errMsg)) {
                $this->errMsg = $arrLang["Error when connecting to database"]."<br/>".$pDB->errMsg;
                return array();
            }

            if($contar)
                $sQuery  = "select count(*) as total from packages $filtro";
            else
                $sQuery  = "select name,summary,version,release,'$repositorio' repositorio from packages $filtro";
            $arr_paquetes = $pDB->fetchTable($sQuery,true);

            $pDB->disconnect();
            if (is_array($arr_paquetes) && count($arr_paquetes) > 0) {
                return $arr_paquetes;
            }
            else return array();
        }
        else return array();
    }

    function estaPaqueteInstalado($paquete)
    {
        global $arrLang;
        exec("rpm -q $paquete",$respuesta,$retorno);
        if($retorno == 0)
            return true;
        else return false;
    }

    function buscarRepositorioDelPaquete($ruta,$paquete,$version,$release, $offset, $limit)
    {
        global $arrLang;
        $filtro = " where name = '$paquete' and version = '$version' and release = '$release' ";
        $filtro .= " LIMIT $limit OFFSET $offset";

        $arr_repositorios = $this->getRepositorios($ruta);
        if(is_array($arr_repositorios) && count($arr_repositorios) > 0) {
             foreach($arr_repositorios as $key => $repositorio){
                $arr_paquetes = $this->getPaquetesDelRepositorio($ruta,$repositorio,$filtro);
                if(is_array($arr_paquetes) && count($arr_paquetes) > 0){
                    return $repositorio;
                }
            }
            return "No info yet";
        }
        else{ 
            $this->errMsg = $arrLang["Repositor not Found"];
            return "No info yet";
        }
    } 

    function checkUpdate()
    {
        global $arrLang;
        exec("sudo yum check-update",$respuesta,$retorno);

        if(is_array($respuesta)){
            foreach($respuesta as $key => $linea){
                //Es algo no muy concreto si hay alguna manera de saber las posibles salidas hay que cambiar esta condicion para buscar el error
                if(ereg("(\[Errno [[:digit:]]{1,}\])",$linea,$reg))
                    return $linea;
            }
            if($retorno==1) //Error debido a los repositorios de elastix
                return $arrLang["ERROR"].": url don't open.";
            else if($retorno==100 || $retorno == 0) //codigo 100 de q hay paquetes para actualizar y 0 que no hay. (ver man yum )
                return $arrLang["Satisfactory Update"];
            else //por si acaso se presenta algo desconocido
                return "";
        } 
    }

    function installPackage($package)
    {
        global $arrLang;
        exec("sudo yum install -y $package",$respuesta,$retorno);
        $indiceInicial = $indiceFinal = 0;
        $terminado = array();
        $paquetesIntall = false;
        $paquetesIntallDependen = false;
        $paquetesUpdateDependen = false;
         if(is_array($respuesta)){ 
            foreach($respuesta as $key => $linea){
                if(!ereg("[[:space:]]{1,}",$linea)){
                    $paquetesIntall = false;
                    $paquetesIntallDependen = false;
                    $paquetesUpdateDependen = false;
                }
                // 1 paquetes a instalar
                if(ereg("^Installing:",$linea)){
                    $paquetesIntall = true;
                }
                //2 paquetes a instalar por dependencias
                else if(ereg("^Installing for dependencies:",$linea)){
                    $paquetesIntallDependen = true;
                    $paquetesIntall = false;
                }
                //3 paquetes a actualizar por dependencias
                else if(ereg("^Updating for dependencies:",$linea)){
                    $paquetesUpdateDependen = true;
                    $paquetesIntallDependen = false;
                }
                //Llenado de datos
                else if($paquetesIntall){
                    $terminado['Installing'][] = $linea; 
                }
                else if($paquetesIntallDependen){
                    $terminado['Installing for dependencies'][] = $linea;
                } 
                else if($paquetesUpdateDependen){
                    $terminado['Updating for dependencies'][] = $linea;
                } 
                //4 fin
                else if(ereg("^Transaction Summary",$linea)){
                    // Procesamiento de los datos recolectados
                    return $this->procesarDatos($terminado);
                }
            }return $arrLang['ERROR']; //error
        } 
    }

    function procesarDatos($datos)
    {
        global $arrLang;
        $respuesta = "";
        $total = 0;
        if(isset($datos['Installing'])){
            $total = $total + count($datos['Installing']);
            $respuesta .= $arrLang['Installing']."\n";
            for($i=0; $i<count($datos['Installing']); $i++){
                $linea = trim($datos['Installing'][$i]);
                if(ereg("^([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([\.[:digit:]]+[[:space:]]+[[:alpha:]]{1})", $linea, $arrReg)) {
                    $respuesta .= ($i+1)." .- ".trim($arrReg[1])." -- ".trim($arrReg[3])."\n";
                }
            }
        }
        $respuesta .= "\n";
        if(isset($datos['Installing for dependencies'])){
            $total = $total + count($datos['Installing for dependencies']);
            $respuesta .= $arrLang['Installing for dependencies']."\n";
            for($i=0; $i<count($datos['Installing for dependencies']); $i++){
                $linea = trim($datos['Installing for dependencies'][$i]);
                if(ereg("^([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([\.[:digit:]]+[[:space:]]+[[:alpha:]]{1})", $linea, $arrReg)) {
                    $respuesta .= ($i+1)." .- ".trim($arrReg[1])." -- ".trim($arrReg[3])."\n";
                }
            }
        }
        $respuesta .= "\n";
        if(isset($datos['Updating for dependencies'])){
            $total = $total + count($datos['Updating for dependencies']);
            $respuesta .= $arrLang['Updating for dependencies']."\n";
            for($i=0; $i<count($datos['Updating for dependencies']); $i++){
                $linea = trim($datos['Updating for dependencies'][$i]);
                if(ereg("^([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([\.[:digit:]]+[[:space:]]+[[:alpha:]]{1})", $linea, $arrReg)) {
                    $respuesta .= ($i+1)." .- ".trim($arrReg[1])." -- ".trim($arrReg[3])."\n";
                }
            }
        }
        $respuesta .= $arrLang['Total Packages']." = $total";
        return $respuesta;
    }

    function ObtenerTotalPaquetes($submitInstalado, $ruta, $filtro)
    {
        if($submitInstalado == "all")
        {
	    if($filtro != "")
	      $filtro = " where name like '%$filtro%'";
            $total = 0;
            $arr_repositorios = $this->getRepositorios($ruta);
            if (is_array($arr_repositorios) && count($arr_repositorios) > 0) {
                foreach($arr_repositorios as $key => $repositorio){
                    $arr_paquetes = $this->getPaquetesDelRepositorio($ruta,$repositorio,$filtro,true);
                    if(isset($arr_paquetes[0]['total']))
			$total += $arr_paquetes[0]['total'];
                }
            }
            return $total;
        }
        else{
	    if($filtro != "")
	      $comando="rpm -qa | grep $filtro | grep -c .";
	    else
	      $comando="rpm -qa | grep -c .";
            exec($comando,$output,$retval);
            if ($retval!=0) return 0;
            return $output[0];
        }
    }

    function getDataPagination($arrData,$limit,$offset)
    {
	$arrResult = array();
	$limitInferior = $offset;
	$limitSuperior = $offset + $limit - 1;
	foreach($arrData as $key => $value){
	    if($key > $limitSuperior)
		break;
	    if($key >= $limitInferior && $key <= $limitSuperior){
		$arrResult[]=$arrData[$key];
	    }
	}
	return $arrResult;
    }
}
?>
