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
  $Id: misc.lib.php,v 1.3 2007/08/10 01:32:51 gcarrillo Exp $ */


function recoger_valor($key, &$_GET, &$_POST, $default = NULL) {
    if (isset($_POST[$key])) return $_POST[$key];
    elseif (isset($_GET[$key])) return $_GET[$key];
    else return $default;
}

function obtener_info_de_sistema()
{
    $arrInfo=array();
    $arrExec=array();
    $arrParticiones=array();
    $varExec="";

    if($fh=fopen("/proc/meminfo", "r")) {
        while($linea=fgets($fh, "4048")) {
            // Aqui parseo algunos parametros
            if(preg_match("/^MemTotal:[[:space:]]+([[:digit:]]+) kB/", $linea, $arrReg)) {
                $arrInfo["MemTotal"]=trim($arrReg[1]);
            }
            if(preg_match("/^MemFree:[[:space:]]+([[:digit:]]+) kB/", $linea, $arrReg)) {
                $arrInfo["MemFree"]=trim($arrReg[1]);
            }
            if(preg_match("/^Buffers:[[:space:]]+([[:digit:]]+) kB/", $linea, $arrReg)) {
                $arrInfo["MemBuffers"]=trim($arrReg[1]);
            }
            if(preg_match("/^SwapTotal:[[:space:]]+([[:digit:]]+) kB/", $linea, $arrReg)) {
                $arrInfo["SwapTotal"]=trim($arrReg[1]);
            }
            if(preg_match("/^SwapFree:[[:space:]]+([[:digit:]]+) kB/", $linea, $arrReg)) {
                $arrInfo["SwapFree"]=trim($arrReg[1]);
            }
            if(preg_match("/^Cached:[[:space:]]+([[:digit:]]+) kB/", $linea, $arrReg)) {
                $arrInfo["Cached"]=trim($arrReg[1]);
            }
        }
        fclose($fh);
    }

    if($fh=fopen("/proc/cpuinfo", "r")) {
        while($linea=fgets($fh, "4048")) {
            // Aqui parseo algunos parametros
            if(preg_match("/^model name[[:space:]]+:[[:space:]]+(.*)$/", $linea, $arrReg)) {
                $arrInfo["CpuModel"]=trim($arrReg[1]);
            }
            if(preg_match("/^vendor_id[[:space:]]+:[[:space:]]+(.*)$/", $linea, $arrReg)) {
                $arrInfo["CpuVendor"]=trim($arrReg[1]);
            }
            if(preg_match("/^cpu MHz[[:space:]]+:[[:space:]]+(.*)$/", $linea, $arrReg)) {
                $arrInfo["CpuMHz"]=trim($arrReg[1]);
            }
        }
        fclose($fh);
    }


    if($fh=fopen("/proc/stat", "r")) {
        while($linea=fgets($fh, "4048")) {
            if(preg_match("/^cpu[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)" .
                    "[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)" .
                    "[[:space:]]+([[:digit:]]+)[[:space:]]?/", $linea, $arrReg)) {
                $cpuActivo=$arrReg[1]+$arrReg[2]+$arrReg[3]+$arrReg[5]+$arrReg[6]+$arrReg[7];
                $cpuTotal=$cpuActivo+$arrReg[4];
                if($cpuTotal>0 and $cpuActivo>=0) {
                    $arrInfo["CpuUsage"]=$cpuActivo/$cpuTotal;
                } else {
                    $arrInfo["CpuUsage"]="";
                }

            }
        }

        fclose($fh);
    }

    exec("/usr/bin/uptime", $arrExec, $varExec);

    if($varExec=="0") {
        //if(ereg(" up[[:space:]]+([[:digit:]]+ days,)?([[:space:]]+[[:digit:]]{2}:[[:digit:]]{2}), ", $arrExec[0], $arrReg)) {
        if(preg_match("/up[[:space:]]+([[:digit:]]+ days?,)?(([[:space:]]*[[:digit:]]{1,2}:[[:digit:]]{1,2}),?)?([[:space:]]*[[:digit:]]+ min)?/",
                $arrExec[0],$arrReg)) {
            if(!empty($arrReg[3]) and empty($arrReg[4])) {
                list($uptime_horas, $uptime_minutos) = explode(":", $arrReg[3]);
                $arrInfo["SysUptime"]=$arrReg[1] . " $uptime_horas hour(s), $uptime_minutos minute(s)";
            } else if (empty($arrReg[3]) and !empty($arrReg[4])) {
                // Esto lo dejo asi
                $arrInfo["SysUptime"]=$arrReg[1].$arrReg[3].$arrReg[4];
            } else {
                $arrInfo["SysUptime"]=$arrReg[1].$arrReg[3].$arrReg[4];
            }
        }
    }


    // Infomacion de particiones
    //- TODO: Aun no se soportan lineas quebradas como la siguiente:
    //-       /respaldos/INSTALADORES/fedora-1/disco1.iso
    //-                              644864    644864         0 100% /mnt/fc1/disc1

    exec("/bin/df -P /etc/fstab", $arrExec, $varExec);

    if($varExec=="0") {
        foreach($arrExec as $lineaParticion) {
            if(preg_match("/^([\/-_\.[:alnum:]|-]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)" .
                    "[[:space:]]+([[:digit:]]{1,3}%)[[:space:]]+([\/-_\.[:alnum:]]+)$/", $lineaParticion, $arrReg)) {
                $arrTmp="";
                $arrTmp["fichero"]=$arrReg[1];
                $arrTmp["num_bloques_total"]=$arrReg[2];
                $arrTmp["num_bloques_usados"]=$arrReg[3];
                $arrTmp["num_bloques_disponibles"]=$arrReg[4];
                $arrTmp["uso_porcentaje"]=$arrReg[5];
                $arrTmp["punto_montaje"]=$arrReg[6];
                $arrInfo["particiones"][]=$arrTmp;
            }
        }
    }
    return $arrInfo;
}

/**
 * Procedimiento para construir una cadena de parámetros GET a partir de un 
 * arreglo asociativo de variables. Opcionalmente se puede indicar un conjunto
 * de variables a excluir de la construcción. Si se ejecuta en contexto web y
 * se dispone del superglobal $_GET, sus variables se agregan también a la 
 * cadena, a menos que el nombre de la variable GET conste también en la lista
 * de variables indicada explícitamente.
 *
 * @param   array   $arrVars    Lista de variables a incluir en cadena URL
 * @param   array   $arrExcluir Lista de variables a excluir de cadena URL
 *
 * @return  string  Cadena URL con signo de interrogación enfrente, si hubo al
 *                  menos una variable a convertir, o cadena vacía si no hay
 *                  variable alguna a convertir
 */
function construirURL($arrVars=array(), $arrExcluir=array())
{
    $listaVars = array();   // Lista de variables inicial

    // Variables GET, si existen
    if (isset($_GET) && is_array($_GET))
        $listaVars = array_merge($listaVars, $_GET);

    // Variables explícitas, si existen
    if (is_array($arrVars))
        $listaVars = array_merge($listaVars, $arrVars);

    // Quitar variables excluídas
    foreach ($arrExcluir as $k) unset($listaVars[$k]);
    if (count($listaVars) <= 0) return '';

    $keyval = array();
    foreach ($listaVars as $k => $v) {
        $keyval[] = urlencode($k).'='.urlencode($v);
    }
    return '?'.implode('&amp;', $keyval);    
}

// Translate a date in format 9 Dec 2006
function translateDate($dateOrig)
{
    if(preg_match("/([[:digit:]]{1,2})[[:space:]]+([[:alnum:]]{3})[[:space:]]+([[:digit:]]{4})/", $dateOrig, $arrReg)) {
        if($arrReg[2]=="Jan")      $numMonth = "01";
        else if($arrReg[2]=="Feb") $numMonth = "02";
        else if($arrReg[2]=="Mar") $numMonth = "03";
        else if($arrReg[2]=="Apr") $numMonth = "04";
        else if($arrReg[2]=="May") $numMonth = "05";
        else if($arrReg[2]=="Jun") $numMonth = "06";
        else if($arrReg[2]=="Jul") $numMonth = "07";
        else if($arrReg[2]=="Aug") $numMonth = "08";
        else if($arrReg[2]=="Sep") $numMonth = "09";
        else if($arrReg[2]=="Oct") $numMonth = "10";
        else if($arrReg[2]=="Nov") $numMonth = "11";
        else if($arrReg[2]=="Dec") $numMonth = "12";
        return $arrReg[3] . "-" . $numMonth . "-" . $arrReg[1]; 
    } else {
        return false;
    }
}
function get_key_settings($pDB,$key){
    $value='';
    $sQuery="SELECT value FROM settings WHERE key='$key'";
    //$oResult=$pDB->conn->getOne($sQuery,array($key));
    $oResult=$pDB->getFirstRowQuery($sQuery, FALSE);
    if($oResult && count($oResult)>0)
        $value=$oResult[0];
    return $value;
}
function set_key_settings($pDB,$key,$value){
    $bExito=FALSE;
    //tengo que verificar si existe el valor de configuracion 
    $sQuery="SELECT count(*) FROM settings WHERE key='$key'";
    $oResult=$pDB->getFirstRowQuery($sQuery, FALSE);
    if ($oResult){
        if($oResult[0]>0){
            $sQuery="UPDATE settings SET value ='$value' WHERE key='$key'";
            $oResult=$pDB->genQuery($sQuery);
            if ($oResult) $bExito=TRUE;
        }else{
            $sQuery="INSERT INTO settings (key,value) VALUES ( '$key', '$value' )";
            $oResult=$pDB->genQuery($sQuery);
            if ($oResult) $bExito=TRUE;
        }
    }
    return $bExito;
}

function load_version_elastix($ruta_base='')
{
    require_once $ruta_base."configs/default.conf.php";
    global $arrConf;
    include_once $ruta_base."libs/paloSantoDB.class.php";

    //conectarse a la base de settings para obtener la version y release del sistema elastix
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
    if(empty($pDB->errMsg)) {
        $theme=get_key_settings($pDB,'elastix_version_release');
    }
//si no se encuentra setear solo ?
    if (empty($theme)){
        set_key_settings($pDB,'elastix_version_release','?');
        return "?";
    }
    else return $theme;
}

function load_theme($ruta_base='')
{
    require_once $ruta_base."configs/default.conf.php";
    global $arrConf;
    include_once $ruta_base."libs/paloSantoDB.class.php";

    //conectarse a la base de settings para obtener el thema actual
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
    if(empty($pDB->errMsg)) {
        $theme=get_key_settings($pDB,'theme');
    }
    //si no se encuentra setear el tema por default
    if (empty($theme)){
        set_key_settings($pDB,'theme','default');
        return "default";
    }
    else return $theme;
}

function load_language($ruta_base='')
{
    $lang = get_language($ruta_base);

    include_once $ruta_base."lang/en.lang";
    $lang_file = $ruta_base."lang/$lang.lang";

    if ($lang != 'en' && file_exists("$lang_file")) {
        $arrLangEN = $arrLang;
        include_once "$lang_file";
        $arrLang = array_merge($arrLangEN, $arrLang);
    }
}

function load_language_module($module_id, $ruta_base='')
{
    $lang = get_language($ruta_base);
    include_once $ruta_base."modules/$module_id/lang/en.lang";
    $lang_file_module = $ruta_base."modules/$module_id/lang/$lang.lang";
    if ($lang != 'en' && file_exists("$lang_file_module")) {
        $arrLangEN = $arrLangModule;
        include_once "$lang_file_module";
        $arrLangModule = array_merge($arrLangEN, $arrLangModule);
    }

    global $arrLang;
    global $arrLangModule;
    $arrLang = array_merge($arrLang,$arrLangModule);
}

function _tr($s)
{
    global $arrLang;
    return isset($arrLang[$s]) ? $arrLang[$s] : $s;
}

function cargar_menu($db)
{
   //leer el contenido de la tabla menu y devolver un arreglo con la estructura
    $menu = array ();
    $query="Select m1.*, (Select count(*) from menu m2 where m2.IdParent=m1.id) as HasChild from menu m1 order by order_no asc;";
    $oRecordset=$db->fetchTable($query, true);
    if ($oRecordset){
        foreach($oRecordset as $key => $value)
        {
            if($value['HasChild']>0)
                $value['HasChild'] = true;
            else $value['HasChild'] = false;
            $menu[$value['id']]= $value;
        }
    }
    return $menu;
}

function get_language($ruta_base='')
{
    require_once $ruta_base."configs/default.conf.php";
    include $ruta_base."configs/languages.conf.php";

    global $arrConf;
    $lang="";

    //conectarse a la base de settings para obtener el idioma actual
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
    if(empty($pDB->errMsg)) {
        $lang=get_key_settings($pDB,'language');
    }
    //si no se encuentra tomar del archivo de configuracion
    if (empty($lang)) $lang=isset($arrConf['language'])?$arrConf['language']:"en";

    //verificar que exista en el arreglo de idiomas, sino por defecto en
    if (!array_key_exists($lang,$languages)) $lang="en";
    return $lang;
}


#funciones para menu


/**
* Genera la lista de opciones para el tag SELECT_INPUT
* @generic
*/
function combo($arreglo_valores, $selected) {
    $cadena = '';
    if(!is_array($arreglo_valores) or empty($arreglo_valores)) return '';

    foreach($arreglo_valores as $key => $value) if ($selected == $key)
        $cadena .= "<option value='$key' selected>$value</option>\n"; else $cadena .= "<option value='$key'>$value</option>\n";
    return $cadena;
}

/**
* Funcion que sirve para obtener informacion de un checkbox si esta o no seteado.
* Habia un problema q cunado un checkbox no era seleccionado, este no devolvia nada por POST
* Esta funcion garantiza que siempre q defina un checkbox voy a tener un 'false' si no esta
* seteado y un 'true' si lo esta.
*
* Ejemplo: $html = checkbox("chk_01",'on','off'); //define un checkbox y esta seteado.
           $smarty("eje",$html); //lo paso a las plantilla.
           ......... por POST lo recibo ......
*          $check = $_POST['chk_01'] //recibo 'on' or 'off' segun el caso de q este seteado o  no.
*/
function checkbox($id_name, $checked='off', $disable='off')
{
    $check = $disab = "";
    $id_name_fixed  = str_replace("-","_",$id_name);

    if(!($checked=='off'))
        $check = "checked=\"checked\"";
    if(!($disable=='off'))
        $disab = "disabled=\"disabled\"";

    $checkbox  = "<input type=\"checkbox\" name=\"chkold{$id_name}\" $check $disab onclick=\"javascript:{$id_name_fixed}check();\" /> 
                  <input type=\"hidden\"   name=\"{$id_name}\" id=\"{$id_name}\"   value=\"{$checked}\" />
                  <script type=\"text/javascript\">
                    function {$id_name_fixed}check(){
                        var node = document.getElementById('$id_name');
                        if(node.value == 'on')
                            node.value = 'off';
                        else node.value = 'on';
                    }
                  </script>";
    return $checkbox;
}

/**
* Funcion que sirve para obtener los valores de los parametros de los campos en los
* formularios, Esta funcion verifiva si el parametro viene por POST y si no lo encuentra
* trata de buscar por GET para poder retornar algun valor, si el parametro ha consultar no
* no esta en request retorna null.
*
* Ejemplo: $nombre = getParameter('nombre');
*/
function getParameter($parameter)
{
    if(isset($_POST[$parameter]))
        return $_POST[$parameter];
    else if(isset($_GET[$parameter]))
        return $_GET[$parameter];
    else
        return null;
}

/**
 * Función para obtener la clave del Cyrus Admin de Elastix.
 * La clave es obtenida de /etc/elastix.conf
 *
 * @param   string  $ruta_base          Ruta base para inclusión de librerías
 *
 * @return  mixed   NULL si no se reconoce usuario, o la clave en plaintext
 */
function obtenerClaveCyrusAdmin($ruta_base='')
{
    require_once $ruta_base.'libs/paloSantoConfig.class.php';

	$pConfig = new paloConfig("/etc", "elastix.conf", "=", "[[:space:]]*=[[:space:]]*");
	$listaParam = $pConfig->leer_configuracion(FALSE);
	if (isset($listaParam['cyrususerpwd'])) 
		return $listaParam['cyrususerpwd']['valor'];
	else return 'palosanto'; // Compatibility for updates where /etc/elastix.conf is not available
}

/**
 * Función para obtener la clave MySQL de usuarios bien conocidos de Elastix.
 * Los usuarios conocidos hasta ahora son 'root' (sacada de /etc/elastix.conf)
 * y 'asteriskuser' (sacada de /etc/amportal.conf)
 *
 * @param   string  $sNombreUsuario     Nombre de usuario para interrogar
 * @param   string  $ruta_base          Ruta base para inclusión de librerías
 *
 * @return  mixed   NULL si no se reconoce usuario, o la clave en plaintext
 */
function obtenerClaveConocidaMySQL($sNombreUsuario, $ruta_base='')
{
    require_once $ruta_base.'libs/paloSantoConfig.class.php';
    switch ($sNombreUsuario) {
    case 'root':
        $pConfig = new paloConfig("/etc", "elastix.conf", "=", "[[:space:]]*=[[:space:]]*");
        $listaParam = $pConfig->leer_configuracion(FALSE);
        if (isset($listaParam['mysqlrootpwd'])) 
            return $listaParam['mysqlrootpwd']['valor'];
        else return 'eLaStIx.2oo7'; // Compatibility for updates where /etc/elastix.conf is not available
        break;
    case 'asteriskuser':
        $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
        $listaParam = $pConfig->leer_configuracion(FALSE);
        if (isset($listaParam['AMPDBPASS']))
            return $listaParam['AMPDBPASS'];
        break;
    }
    return NULL;
};

/**
 * Función para obtener la clave AMI del usuario admin, obtenida del archivo /etc/elastix.conf
 *
 * @param   string  $ruta_base          Ruta base para inclusión de librerías
 *
 * @return  string   clave en plaintext de AMI del usuario admin
 */

function obtenerClaveAMIAdmin($ruta_base='')
{
    require_once $ruta_base.'libs/paloSantoConfig.class.php';
    $pConfig = new paloConfig("/etc", "elastix.conf", "=", "[[:space:]]*=[[:space:]]*");
    $listaParam = $pConfig->leer_configuracion(FALSE);
    if(isset($listaParam["amiadminpwd"]))
        return $listaParam["amiadminpwd"]['valor'];
    else
        return "elastix456";
}

/**
 * Función para construir un DSN para conectarse a varias bases de datos 
 * frecuentemente utilizadas en Elastix. Para cada base de datos reconocida, se
 * busca la clave en /etc/elastix.conf o en /etc/amportal.conf según corresponda.
 *
 * @param   string  $sNombreUsuario     Nombre de usuario para interrogar
 * @param   string  $sNombreDB          Nombre de base de datos para DNS
 * @param   string  $ruta_base          Ruta base para inclusión de librerías
 *
 * @return  mixed   NULL si no se reconoce usuario, o el DNS con clave resuelta
 */
function generarDSNSistema($sNombreUsuario, $sNombreDB, $ruta_base='')
{
    require_once $ruta_base.'libs/paloSantoConfig.class.php';
    switch ($sNombreUsuario) {
    case 'root':
        $sClave = obtenerClaveConocidaMySQL($sNombreUsuario, $ruta_base);
        if (is_null($sClave)) return NULL;
        return 'mysql://root:'.$sClave.'@localhost/'.$sNombreDB;
    case 'asteriskuser':
        $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
        $listaParam = $pConfig->leer_configuracion(FALSE);
        return $listaParam['AMPDBENGINE']['valor']."://".
               $listaParam['AMPDBUSER']['valor']. ":".
               $listaParam['AMPDBPASS']['valor']. "@".
               $listaParam['AMPDBHOST']['valor']. "/".$sNombreDB;
    }
    return NULL;
}

/**
 * Función para obtener un detalle de los rpms que se encuentran instalados en el sistema.
 *
 *
 * @return  mixed   NULL si no se reconoce usuario, o el DNS con clave resuelta
 */
function obtenerDetallesRPMS(){
    $comando1  = "/bin/bash /usr/bin/versionPaquetes.sh";

    $arrPro = "";
    ///// elastix web interface
    exec($comando1,$output1,$retval);
    if ($retval<>0){
        $arrPro['Elastix'] = array("name" => "no", "version" => "no", "release" => "no");
    }
    else{ // se ejecuto de manera correcta
        $arrmin = "";
        for($i = 0; $i < count($output1); $i++){
            $lim = substr($output1[$i], 0, 3);
            if($lim == "RPM"){
                $j = 0;
                $label = substr($output1[$i], 4);
            }else{
                $arrPro[$label][$j] =  explode(" ",$output1[$i]);
                $j++;
            }
        }
    }
    return $arrPro;
}

function isPostfixToElastix2(){
    $pathImap    = "/etc/imapd.conf";
    $vitualDomain = "virtdomains: yes";
    $band = TRUE;
    $handle = fopen($pathImap, "r");
    $contents = fread($handle, filesize($pathImap));
    fclose($handle);
    if(strstr($contents,$vitualDomain)){
        $band = TRUE; // if the conf postfix is for Elastix 2.0
    }
    else{
        $band = FALSE;// if the conf postfix is for Elastix 1.6
    } 
    return $band;
}

// Esta función revisa las bases de datos del framework (acl.db, menu.db, register.db, settings.db, samples.db) en caso de que no existan y se encuentre su equivalente pero con extensión .rpmsave entonces se las renombra.
// Esto se lo hace exclusivamente debido a la migración de las bases de datos .db del framework a archivos .sql ya que el último rpm generado que contenía las bases como .db las renombra a .rpmsave
function checkFrameworkDatabases($dbdir)
{
    $arrFrameWorkDatabases = array("acl.db","menu.db","register.db","samples.db","settings.db");
    foreach($arrFrameWorkDatabases as $database){
        if(!file_exists("$dbdir/$database") || filesize("$dbdir/$database")==0){
            if(file_exists("$dbdir/$database.rpmsave"))
                 rename("$dbdir/$database.rpmsave","$dbdir/$database");
        }
    }
}

function writeLOG($logFILE, $log)
{
    $logPATH = "/var/log/elastix"; 
    $path_of_file = "$logPATH/".$logFILE;

    $fp = fopen($path_of_file, 'a+');
    if ($fp) {
        fwrite($fp,date("[M d H:i:s]")." $log\n");
        fclose($fp);
    }
    else
        echo "The file $logFILE couldn't be opened";
}

function verifyTemplate_vm_email()
{
   $ip = $_SERVER['SERVER_ADDR'];
   $login = "?login=\${VM_MAILBOX}";
   $file = "/etc/asterisk/vm_email.inc";
   //http://AMPWEBADDRESS/recordings/index.php?login=${VM_MAILBOX}
   $file_string = file_get_contents($file);
   if($file_string){
      $file_string_new = str_replace("*98","*97", $file_string);

      if(preg_match("/https?:\/\/(.*)\/recordings\/index\.php/",$file_string_new,$arrVar)){
         if(is_array($arrVar) && count($arrVar) > 1){
             $ip_old = $arrVar[1];
             if($ip_old != $ip)
                $file_string_new = str_replace($ip_old, $ip, $file_string_new);
         }
      }

      if(preg_match("/https?:\/\/.*\/recordings\/index\.php(\s|\?login=\$\{VM_MAILBOX\})/",$file_string_new,$arrVar)){
         if(is_array($arrVar) && count($arrVar) > 1){
             $login_old  = $arrVar[1];
             if($login_old != $login)
                $file_string_new = str_replace("index.php","index.php$login", $file_string_new);
         }
      }

      if($file_string != $file_string_new)
         file_put_contents($file, $file_string_new);
   }
}


function setUserPassword()
{
	include_once "libs/paloSantoACL.class.php";

	$old_pass   = getParameter("oldPassword");
	$new_pass   = getParameter("newPassword");
	$new_repass = getParameter("newRePassword");
	$arrResult  = array();
	$arrResult['status'] = FALSE;
	if($old_pass == ""){
	  $arrResult['msg'] = _tr("Please write your current password.");
	  return $arrResult;
	}
	if($new_pass == "" || $new_repass == ""){
	  $arrResult['msg'] = _tr("Please write the new password and confirm the new password.");
	  return $arrResult;
	}
	if($new_pass != $new_repass){
	  $arrResult['msg'] = _tr("The new password doesn't match with retype new password.");
	  return $arrResult;
	}

	$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    global $arrConf;
    $pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);
	if($uid===FALSE)
        $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
	else{
		// verificando la clave vieja
		$val = $pACL->authenticateUser ($user, md5($old_pass));
		if($val === TRUE){
			$status = $pACL->changePassword($uid, md5($new_pass));
			if($status){
				$arrResult['status'] = TRUE;
				$arrResult['msg'] = _tr("Elastix password has been changed.");
				$_SESSION['elastix_pass'] = md5($new_pass);
			}else{
				$arrResult['msg'] = _tr("Impossible to change your Elastix password.");
			}
		}else{
			$arrResult['msg'] = _tr("Impossible to change your Elastix password. User does not exist or password is wrong");
		}
	}
	return $arrResult;
}

function searchModulesByName()
{
	include_once "libs/JSON.php";
	include_once "modules/group_permission/libs/paloSantoGroupPermission.class.php";
	$json = new Services_JSON();

	$pGroupPermission = new paloSantoGroupPermission();
	$name = getParameter("name_module_search");
	$arrSessionPermissions = $_SESSION['elastix_user_permission'];
	$result = array();
	$arrIdMenues = array();
	$lang=get_language();
    global $arrLang;

	// obteniendo los id de los menus permitidos
	$arrIdMenues = array();
	foreach($arrSessionPermissions as $key => $value){
		$arrIdMenues[] = $value['id']; // id, IdParent, Link, Name, Type, order_no, HasChild
	}

	$parameter_to_find = array(); // arreglo con los valores del name dada la busqueda
	// el metodo de busqueda de por nombre sera buscando en el arreglo de lenguajes y obteniendo su $key para luego buscarlo en la base de
	// datos menu.db
    if($lang != "en"){ // entonces se adjunta la busqueda con el arreglo de lenguajes en ingles
	    foreach($arrLang as $key=>$value){
            $langValue    = strtolower(trim($value));
            $filter_value = strtolower(trim($name));
            if($filter_value!=""){
                if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
                    if(preg_match("/$filter_value/",$langValue))
			            $parameter_to_find[] = $key;
            }
	    }
    }
	$parameter_to_find[] = $name;

	// buscando en la base de datos acl.db tabla acl_resource con el campo description
	if(empty($parameter_to_find))
		$arrResult = $pGroupPermission->ObtainResources(25, 0, $name);
	else
    	$arrResult = $pGroupPermission->ObtainResources(25, 0, $parameter_to_find);

	foreach($arrResult as $key2 => $value2){
		// leyendo el resultado del query
		if(in_array($value2["name"], $arrIdMenues)){
			$arrMenu['caption'] = _tr($value2["description"]);
			$arrMenu['value']   = $value2["name"];
			$result[] = $arrMenu;
		}
	}

	header('Content-Type: application/json');
	return $json->encode($result);

}

function getMenuColorByMenu()
{
	include_once "libs/paloSantoACL.class.php";
	global $arrConf;
    $pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
    $pACL = new paloACL($pdbACL);
	$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $uid = $pACL->getIdUser($user);
	$color = "#454545";
	$id_profile = "";
	$sPeticionID = "SELECT id_profile FROM acl_user_profile WHERE id_user = ?";
	$tupla = $pdbACL->getFirstRowQuery($sPeticionID, FALSE, array($uid));
	if ($tupla === FALSE) {
		$arrResult['msg'] = _tr("ERROR DB: ").$pdbACL->errMsg;
	} elseif (count($tupla) == 0) {
		$id_profile = NULL;
	} else {
		$id_profile = $tupla[0];
	}
	if(isset($id_profile) && $id_profile != ""){
		$sPeticionPropiedades = "SELECT property, value FROM acl_profile_properties WHERE id_profile = ? AND property = ?";
		$tabla = $pdbACL->getFirstRowQuery($sPeticionPropiedades, FALSE, array($id_profile,"menuColor"));
		if ($tabla === FALSE) {
		  $arrResult['msg'] = _tr("ERROR DB: ").$pdbACL->errMsg;
		} else {
			if($tabla[0] == "menuColor")
				$color = $tabla[1];
		}
	}
	return $color;
}

function changeMenuColorByUser()
{
	include_once "libs/paloSantoACL.class.php";

	$color = getParameter("menuColor");
	$arrResult  = array();
	$arrResult['status'] = FALSE;

	if($color == ""){
	   $color = "#454545";
	}

	$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    global $arrConf;
    $pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);

	if($uid===FALSE)
        $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
	else{
		//si el usuario no tiene un color establecido entonces se crea el nuevo registro caso contrario se lo inserta
		//obteniendo el id profile del usuario

		$id_profile = "";
		$sPeticionID = "SELECT id_profile FROM acl_user_profile WHERE id_user = ?";
		$tupla = $pdbACL->getFirstRowQuery($sPeticionID, FALSE, array($uid));
		if ($tupla === FALSE) {
			$arrResult['msg'] = _tr("ERROR DB: ").$pdbACL->errMsg;
			return $arrResult;
		} elseif (count($tupla) == 0) {
			$id_profile = NULL;
		} else {
			$id_profile = $tupla[0];
		}

		if (is_null($id_profile) || $id_profile == "") {
			// Crear el nuevo perfil para el usuario indicado...
			$sPeticionNuevoPerfil = 'INSERT INTO acl_user_profile (id_user, id_resource, profile) VALUES (?, ?, ?)';
			$r = $pdbACL->genQuery($sPeticionNuevoPerfil, array($uid, "19", "default"));
			if (!$r) {
				$arrResult['msg'] = _tr("ERROR DE DB: ").$pDB->errMsg;
			}
			$id_profile = $pdbACL->getLastInsertId();
		}
		if(isset($id_profile) && $id_profile != ""){
		  $sPeticionPropiedades = "SELECT property, value FROM acl_profile_properties WHERE id_profile = ?";
		  $existColor = false;
		  $tabla = $pdbACL->fetchTable($sPeticionPropiedades, FALSE, array($id_profile));
		  if ($tabla === FALSE) {
			$arrResult['msg'] = _tr("ERROR DB: ").$pdbACL->errMsg;
		  } else {
			foreach ($tabla as $tupla) {
				if($tupla[0] == "menuColor")
				  $existColor = true;
			}
			if ($existColor) {
				$sPeticionSQL = 'UPDATE acl_profile_properties SET value = ? WHERE id_profile = ? AND property = ?';
				$params = array($color, $id_profile, "menuColor");
			} else {
				$sPeticionSQL = 'INSERT INTO acl_profile_properties (id_profile, property, value) VALUES (?, ?, ?)';
				$params = array($id_profile, "menuColor", $color);
			}
			$r = $pdbACL->genQuery($sPeticionSQL, $params);
			if (!$r) {
				$arrResult['msg'] = _tr("ERROR DB: ").$pdbACL->errMsg;
			}else{
				$arrResult['status'] = TRUE;
				$arrResult['msg'] = _tr("OK");
			}
		  }
		}
	}
	return $arrResult;
}

?>
