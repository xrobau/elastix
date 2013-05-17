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
    var $fn = '/tmp/list-update-packages.txt';
 
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
        $filtroGrep = "";
	    if($filtro!="")
	        $filtroGrep = "| grep ".escapeshellarg($filtro);
        $comando = "rpm -qa --queryformat '%{NAME}|%{SUMMARY}|%{VERSION}|%{RELEASE}\n' $filtroGrep";
        exec($comando,$respuesta,$retorno);

        if($retorno==0 && $respuesta!=null && count($respuesta) > 0 && is_array($respuesta)){
            foreach($respuesta as $key => $paqueteLinea){
                $paquete = explode("|",$paqueteLinea);
		if(preg_match("/$filtro/",$paquete[0]) || $filtro == ""){
		    $repositorio = $this->buscarRepositorioDelPaquete($ruta,$paquete[0],$paquete[2],$paquete[3], $offset, $limit);
		    $paquetes[] = array("name" =>$paquete[0],"summary" =>$paquete[1],"version" =>$paquete[2],"release" =>$paquete[3],'repositorio' => $repositorio);
		}
            }
         }
         else
             $this->errMsg = $arrLang["Packages not Founds"];
        //return $this->getDataPagination($paquetes,$limit,$offset);
		return $paquetes;
    }


    function getAllPackages($ruta,$filtro="", $offset, $limit, $total, &$actualizar)
    {
        $valorfiltro = array();
        if ($filtro != "") $valorfiltro['namefilter'] = $filtro;

        $arr_repositorios = $this->getRepositorios($ruta);
        $arrRepositoriosPaquetes = array();
        if (is_array($arr_repositorios) && count($arr_repositorios) > 0) {
            foreach($arr_repositorios as $key => $repositorio){
                $arr_paquetes = $this->getPaquetesDelRepositorio($ruta,$repositorio,$valorfiltro);
                //$arrRepositoriosPaquetes[$repositorio] = $arr_paquetes;
                if(is_array($arr_paquetes) && count($arr_paquetes) > 0)
                     $arrRepositoriosPaquetes = array_merge($arrRepositoriosPaquetes,$arr_paquetes);
            }
        } else {
			$actualizar = true;
			$arrRepositoriosPaquetes = $this->getPackagesInstalados($ruta,$filtro, $offset, $limit, $total);
        }
        return $arrRepositoriosPaquetes;
    }

    /**
     * Procedimiento para obtener el listado de los repositorios
     *
     * @return array    Listado de los repositorios
     */
    private function getRepositorios($dir='/var/cache/yum/')
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

    private function getPaquetesDelRepositorio($ruta,$repositorio,/*$filtro*/ $param, $contar=false)
    {
	$cadena_dsn = null;
        if(file_exists($ruta.$repositorio."/primary.xml.gz.sqlite")){
            $cadena_dsn = "sqlite3:///$ruta"."$repositorio"."/primary.xml.gz.sqlite";
	}
	elseif($repositorio == "epel"){
	    $database = glob($ruta."epel/*-primary.sqlite");
	    if(isset($database[0]))
		$cadena_dsn = "sqlite3:///$database[0]";
	}
	elseif($repositorio == "extras"){
	    if(file_exists($ruta.$repositorio."/primary.sqlite"))
		$cadena_dsn = "sqlite3:///$ruta"."$repositorio"."/primary.sqlite";
	}
	if(isset($cadena_dsn)){
            // se conecta a la base
            $pDB = new paloDB($cadena_dsn);

            if(!empty($pDB->errMsg)) {
                $this->errMsg = "Error when connecting to database"."<br/>".$pDB->errMsg;
                return array();
            }

            $sql = 'SELECT '.
                ($contar ? 'COUNT(*)' : "name, summary, version, release, '$repositorio' repositorio").
                ' FROM packages';
            $paramSQL = array();
            $condWhere = array();
            if (isset($param['name'])) {
                $condWhere[] = 'name = ?';
            	$paramSQL[] = $param['name'];
            }
            if (isset($param['version'])) {
                $condWhere[] = 'version = ?';
                $paramSQL[] = $param['version'];
            }
            if (isset($param['release'])) {
                $condWhere[] = 'release = ?';
                $paramSQL[] = $param['release'];
            }
            if (isset($param['namefilter'])) {
                $condWhere[] = 'name LIKE ?';
                $paramSQL[] = '%'.$param['namefilter'].'%';
            }
            if (count($condWhere) > 0) $sql .= ' WHERE '.implode(' AND ', $condWhere);
            if (isset($param['limit']) && isset($param['offset'])) {
            	$sql .= ' LIMIT ? OFFSET ?';
                $paramSQL[] = $param['limit'];
                $paramSQL[] = $param['offset'];
            }
            $arr_paquetes = $pDB->fetchTable($sql, true, $paramSQL);

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
        exec("rpm -q ".escapeshellarg($paquete),$respuesta,$retorno);
        if($retorno == 0)
            return true;
        else return false;
    }

    private function buscarRepositorioDelPaquete($ruta,$paquete,$version,$release, $offset, $limit)
    {
        global $arrLang;
        $filtro = array(
            'name'      =>  $paquete,
            'version'   =>  $version,
            'release'   =>  $release,
            'limit'     =>  $limit,
            'offset'    =>  $offset,
        );

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
        $respuesta = $retorno = NULL;
        exec('/usr/bin/elastix-helper ryum check-update ', $respuesta, $retorno);
        $tmp = array();
        if(is_array($respuesta)){
            foreach($respuesta as $key => $linea){
		//Es algo no muy concreto si hay alguna manera de saber las posibles salidas hay que cambiar esta condicion para buscar el error
                if(preg_match("/(\[Errno [[:digit:]]{1,}\])/",$linea,$reg))
                    return false;
		elseif((!preg_match("/^Excluding/",$linea,$reg))&&(!preg_match("/^Finished/",$linea,$reg))&&(!preg_match("/^Loaded/",$linea,$reg))&&(!preg_match("/^\ /",$linea,$reg))&&(!preg_match("/^Loading/",$linea,$reg))&&($linea!=""))
		    {     
			  $var = explode(".",$linea);
			  $tmp[] = $var[0];
		    }
		   
            }
	    if($retorno==1) //Error debido a los repositorios de elastix
                return $arrLang["ERROR"].": url don't open.";
            else if($retorno==100 || $retorno == 0){ //codigo 100 de q hay paquetes para actualizar y 0 que no hay. (ver man yum )
                 if($this->writeTempFile($tmp))
		    return $arrLang["Satisfactory Update"];
	         else
		    return "";
	    }
            else //por si acaso se presenta algo desconocido
                return "";
        }
	
    }

    private function writeTempFile($arr){
      if ($f = fopen ($this->fn, 'w+'))
      {
	 foreach($arr as $key => $value){
	    fwrite($f,$value);
	    fwrite($f,"\n");
	 }
	    fclose($f);
	    return true;  
      }else return false;
	
    }

    function readTempFile(){
    global $arrLang;
      $fn = $this->fn;
      if (file_exists($fn)){ 
	  if($fh = fopen($fn,"r")){ 
	      while (!feof($fh)){ 
		$arr[] = trim(fgets($fh)); 
	      } 
	  fclose($fh); 
	  return $arr;
	  }else
	      return false;
      }else{
	    if($this->checkUpdate()==$arrLang["Satisfactory Update"]){
	       $this->readTempFile();
	       return true;
	    }
	    else
	        return false;
      }
    }
    
    function installPackage($package,$val)
    {
        global $arrLang;
        $respuesta = $retorno = NULL;
	if($val==0)
	  exec('/usr/bin/elastix-helper ryum install '.escapeshellarg($package), $respuesta, $retorno);
        else
	  exec('/usr/bin/elastix-helper ryum update '.escapeshellarg($package), $respuesta, $retorno);
   	
	$indiceInicial = $indiceFinal = 0;
        $terminado = array();
        $paquetesIntall = false;
        $paquetesIntallDependen = false;
        $paquetesUpdateDependen = false;
         if(is_array($respuesta)){
            foreach($respuesta as $key => $linea){
                if(!preg_match("/[[:space:]]{1,}/",$linea)){
                    $paquetesIntall = false;
                    $paquetesIntallDependen = false;
                    $paquetesUpdateDependen = false;
                }
                // 1 paquetes a instalar
                if((preg_match("/^Installing:/",$linea))||(preg_match("/^Updating:/",$linea))){
                    $paquetesIntall = true;
                }
		//2 paquetes a instalar por dependencias
                else if(preg_match("/^Installing for dependencies:/",$linea)){
                    $paquetesIntallDependen = true;
                    $paquetesIntall = false;
                }
                //3 paquetes a actualizar por dependencias
                else if(preg_match("/^Updating for dependencies:/",$linea)){
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
                else if(preg_match("/^Transaction Summary/",$linea)){
                    // Procesamiento de los datos recolectados
                    return $this->procesarDatos($terminado,$val);
                }
            }
	    return $arrLang['ERROR']; //error
        }
    }

    private function procesarDatos($datos,$val)
    {
        global $arrLang;
        $respuesta = "";
        $total = 0;
        if(isset($datos['Installing'])){
            $total = $total + count($datos['Installing']);
	    if($val==0)  
	      $respuesta .= $arrLang['Installing']."\n";
            else
	      $respuesta .= _tr("Updating")."\n";
	    for($i=0; $i<count($datos['Installing']); $i++){
                $linea = trim($datos['Installing'][$i]);
                if(preg_match("/^([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([\.[:digit:]]+[[:space:]]+[[:alpha:]]{1})/", $linea, $arrReg)) {
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
                if(preg_match("/^([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([\.[:digit:]]+[[:space:]]+[[:alpha:]]{1})/", $linea, $arrReg)) {
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
                if(preg_match("/^([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([-\+\.\:[:alnum:]]+)[[:space:]]+([\.[:digit:]]+[[:space:]]+[[:alpha:]]{1})/", $linea, $arrReg)) {
                    $respuesta .= ($i+1)." .- ".trim($arrReg[1])." -- ".trim($arrReg[3])."\n";
                }
            }
        }
        $respuesta .= $arrLang['Total Packages']." = $total";
	if($val==1) 
	   $this->checkUpdate();

	return $respuesta;
    }

function uninstallPackage($package)
{
        global $arrLang;
        $respuesta = $retorno = NULL;
        exec('/usr/bin/elastix-helper ryum remove '.escapeshellarg($package), $respuesta, $retorno);
        $indiceInicial = $indiceFinal = 0;
        $terminado = array();
        $paquetesUnintall = false;
        $paquetesIntallDependen = false;
        $paquetesUpdateDependen = false;
        $valor ="";
	$total=0;
        if(is_array($respuesta)){
         $valor .= _tr("Package(s) Uninstalled").":\n\n"; 
            foreach($respuesta as $key => $linea){
                if(!preg_match("/[[:space:]]{1,}/",$linea)){
                    $paquetesIntall = false;
                    $paquetesIntallDependen = false;
                    $paquetesUpdateDependen = false;
                }
                // 1 paquetes a instalar
                if(preg_match("/^Complete!/",$linea)){
                    $paquetesUnintall = true;
		    $valor .= "\nTotal: ".$total." "._tr("Packages uninstalled");
                    $valor .= "\n\n". _tr("Completed!");
		    return $valor;
                }
                if(preg_match("/Erasing/",$linea)){
	            $paquetesUnintall = true;
                    $rep =  preg_split("/[\s]*[ ][\s]*/", $linea);
                    
                    $valor .= $rep[4]." ".$rep[3]."\n";
                    
	            $total++;
                    
         	}
                //2 paquetes a instalar por dependencias
            }
            $valor = _tr("Error");
            return $valor;
         }

}

    function ObtenerTotalPaquetes($submitInstalado, $ruta, $filtro)
    {
		$total = 0;
        if($submitInstalado == "all")
        {
            $valorfiltro = array();
            if ($filtro != "") $valorfiltro['namefilter'] = $filtro;
            $total = 0;
            $arr_repositorios = $this->getRepositorios($ruta);
            if (is_array($arr_repositorios) && count($arr_repositorios) > 0) {
                foreach($arr_repositorios as $key => $repositorio){
                    $arr_paquetes = $this->getPaquetesDelRepositorio($ruta,$repositorio,$valorfiltro,true);
                    if(isset($arr_paquetes[0]['total']))
			$total += $arr_paquetes[0]['total'];
                }
            }
        }
		if($total==0){
			if($filtro != "")
			$comando="rpm -qa --queryformat '%{NAME}\n' | grep ".
                escapeshellarg($filtro)." | grep -c .";
			else
			$comando="rpm -qa | grep -c .";
				exec($comando,$output,$retval);
				if ($retval!=0) return 0;
				$total = $output[0];
        }
		return $total;
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
