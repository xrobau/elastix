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


function recoger_valor($key, &$get, &$post, $default = NULL) {
    if (isset($post[$key])) return $post[$key];
    elseif (isset($get[$key])) return $get[$key];
    else return $default;
}

function obtener_muestra_actividad_cpu()
{
    if (!function_exists('_info_sistema_linea_cpu')) {
        function _info_sistema_linea_cpu($s) { return (strpos($s, 'cpu ') === 0); }
    }
    $muestra = preg_split('/\s+/', array_shift(array_filter(file('/proc/stat', FILE_IGNORE_NEW_LINES), '_info_sistema_linea_cpu')));
    array_shift($muestra);
    return $muestra;
}

function calcular_carga_cpu_intervalo($m1, $m2)
{
    if (!function_exists('_info_sistema_diff_stat')) {
        function _info_sistema_diff_stat($a, $b)
        {
            $aa = str_split($a);
            $bb = str_split($b);
            while (count($aa) < count($bb)) array_unshift($aa, '0');
            while (count($aa) > count($bb)) array_unshift($bb, '0');
            while (count($aa) > 0 && $aa[0] == $bb[0]) {
                array_shift($aa);
                array_shift($bb);
            }
            if (count($aa) <= 0) return 0;
            $a = implode('', $aa); $b = implode('', $bb);
            return (int)$b - (int)$a;
        }
    }
    $diffmuestra = array_map('_info_sistema_diff_stat', $m1, $m2);
    $cpuActivo = $diffmuestra[0] + $diffmuestra[1] + $diffmuestra[2] + $diffmuestra[4] + $diffmuestra[5] + $diffmuestra[6];
    $cpuTotal = $cpuActivo + $diffmuestra[3];
    return ($cpuTotal > 0) ? $cpuActivo / $cpuTotal : 0;
}

function obtener_info_de_sistema()
{
    $muestracpu = array();
    $muestracpu[0] = obtener_muestra_actividad_cpu();

    $arrInfo=array(
        'MemTotal'      =>  0,
        'MemFree'       =>  0,
        'MemBuffers'    =>  0,
        'SwapTotal'     =>  0,
        'SwapFree'      =>  0,
        'Cached'        =>  0,
        'CpuModel'      =>  '(unknown)',
        'CpuVendor'     =>  '(unknown)',
        'CpuMHz'        =>  0.0,
    );
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
            if (preg_match("/^Processor[[:space:]]+:[[:space:]]+(.*)$/", $linea, $arrReg)) {
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

    exec("/usr/bin/uptime", $arrExec, $varExec);

    if($varExec=="0") {
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

    usleep(250000);
    $muestracpu[1] = obtener_muestra_actividad_cpu();
    $arrInfo['CpuUsage'] = calcular_carga_cpu_intervalo($muestracpu[0], $muestracpu[1]);

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
function get_key_settings($pDB,$key)
{
    $r = $pDB->getFirstRowQuery(
        'SELECT value FROM settings WHERE key = ?',
        FALSE, array($key));
    return ($r && count($r) > 0) ? $r[0] : '';
}
function set_key_settings($pDB,$key,$value)
{
    // Verificar si existe el valor de configuración
    $r = $pDB->getFirstRowQuery(
        'SELECT COUNT(*) FROM settings WHERE key = ?',
        FALSE, array($key));
    if (!$r) return FALSE;
    $r = $pDB->genQuery(
        (($r[0] > 0) 
            ? 'UPDATE settings SET value = ? WHERE key = ?' 
            : 'INSERT INTO settings (value, key) VALUES (?, ?)'),
        array($value, $key));
    return $r ? TRUE : FALSE;    
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
    $name_delete_filters = null;
    if(isset($_POST['name_delete_filters']) && !empty($_POST['name_delete_filters']))
        $name_delete_filters = $_POST['name_delete_filters'];
    else if(isset($_GET['name_delete_filters']) && !empty($_GET['name_delete_filters']))
        $name_delete_filters = $_GET['name_delete_filters'];

    if($name_delete_filters){
        $arrFilters = explode(",",$name_delete_filters);
        if(in_array($parameter,$arrFilters))
            return null;
    }
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
            return $listaParam['AMPDBPASS']['valor'];
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
			if(count($tabla) > 0)
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

function putMenuAsHistory($menu)
{
	include_once "libs/paloSantoACL.class.php";
	$success = false;
	if($menu != ""){
		$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
		global $arrConf;
		$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
		$pACL = new paloACL($pdbACL);
		$uid = $pACL->getIdUser($user);
		if($uid!==FALSE){
			//verificar de que ya no este en la base de datos
			$id_resource = $pACL->getResourceId($menu);
			$exist = false;
			$history = "SELECT aus.id AS id, ar.id AS id_menu, ar.name AS name, ar.description AS description FROM acl_user_shortcut aus, acl_resource ar WHERE id_user = ? AND type = 'history' AND ar.id = aus.id_resource ORDER BY aus.id DESC";
			
			$arr_result1 = $pdbACL->fetchTable($history, TRUE, array($uid));
			if($arr_result1 !== FALSE){
				// verificar si ya existe menu en tabla acl_user_shortcut con ese usuario
				$i = 0;
				$arrIDS = array();
				foreach($arr_result1 as $key => $value){
					$arrNew[] = $value;
					$arrIDS[] = $value['id'];
					if($value['name'] == $menu){
						$exist = true;
						if($i==0) return true;
					}
					$i++;
				}
				if(!$exist && count($arr_result1) <= 4){
					$pdbACL->beginTransaction();
					$query = "INSERT INTO acl_user_shortcut(id_user, id_resource, type) VALUES(?, ?, ?)";
					$r = $pdbACL->genQuery($query, array($uid, $id_resource, "history"));
					if(!$r){
						$pdbACL->rollBack();
						return false;
					}else{
						$pdbACL->commit();
						return true;
					}
				}else{
					$pdbACL->beginTransaction();
					$success = true;
					$tmp = "";
					$query = "UPDATE acl_user_shortcut SET id_resource = ? WHERE id_user = ? AND id = ? AND type = ?";
					for($i=0; $i<count($arrIDS); $i++){
						$id = $arrIDS[$i];
						$id_menu = $arrNew[$i]["id_menu"];
						
						$r = true;
						if($i==0){
							$tmp = $id_menu;
							$r = $pdbACL->genQuery($query, array($id_resource, $uid, $id, "history"));
						}else{
							if($id_menu != $id_resource){
								if($tmp != $id_resource && $tmp != ""){
									$r = $pdbACL->genQuery($query, array($tmp, $uid, $id, "history"));
									$tmp = $id_menu;
								}else
									$tmp = "";
							}else{
								$r = $pdbACL->genQuery($query, array($tmp, $uid, $id, "history"));
								$tmp = $id_menu;
							}
						}
						if(!$r)
							$success = false;
					}
					if($success)
						$pdbACL->commit();
					else
						$pdbACL->rollBack();
				}
			}
		}
	}
	return $success;
}

function putMenuAsBookmark($menu)
{
	include_once "libs/paloSantoACL.class.php";
	$arrResult['status'] = FALSE;
	$arrResult['data'] = array("action" => "none", "menu" => "$menu");
	$arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
	if($menu != ""){
		$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
		global $arrConf;
		$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
		$pACL = new paloACL($pdbACL);
		$uid = $pACL->getIdUser($user);
		if($uid!==FALSE){
			$id_resource = $pACL->getResourceId($menu);
			$resource = $pACL->getResources($id_resource);
			$exist = false;
			$bookmarks = "SELECT aus.id AS id, ar.id AS id_menu, ar.name AS name, ar.description AS description FROM acl_user_shortcut aus, acl_resource ar WHERE id_user = ? AND type = 'bookmark' AND ar.id = aus.id_resource ORDER BY aus.id DESC";
			$arr_result1 = $pdbACL->fetchTable($bookmarks, TRUE, array($uid));
			if($arr_result1 !== FALSE){
				$i = 0;
				$arrIDS = array();
				foreach($arr_result1 as $key => $value){
					if($value['id_menu'] == $id_resource)
						$exist = true;
				}
				if($exist){
					$pdbACL->beginTransaction();
					$query = "DELETE FROM acl_user_shortcut WHERE id_user = ? AND id_resource = ? AND type = ?";
					$r = $pdbACL->genQuery($query, array($uid, $id_resource, "bookmark"));
					if(!$r){
						$pdbACL->rollBack();
						$arrResult['status'] = FALSE;
						$arrResult['data'] = array("action" => "delete", "menu" => _tr($resource[0][2]), "idmenu" => $id_resource, "menu_session" => $menu);
						$arrResult['msg'] = _tr("Bookmark cannot be removed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
						return $arrResult;
					}else{
						$pdbACL->commit();
						$arrResult['status'] = TRUE;
						$arrResult['data'] = array("action" => "delete", "menu" => _tr($resource[0][2]), "idmenu" => $id_resource,  "menu_session" => $menu);
						$arrResult['msg'] = _tr("Bookmark has been removed.");
						return $arrResult;
					}
				}

				if(count($arr_result1) > 4){
					$arrResult['msg'] = _tr("The bookmark maximum is 5. Please uncheck one in order to add this bookmark");
				}else{
					$pdbACL->beginTransaction();
					$query = "INSERT INTO acl_user_shortcut(id_user, id_resource, type) VALUES(?, ?, ?)";
					$r = $pdbACL->genQuery($query, array($uid, $id_resource, "bookmark"));
					if(!$r){
						$pdbACL->rollBack();
						$arrResult['status'] = FALSE;
						$arrResult['data'] = array("action" => "add", "menu" => _tr($resource[0][2]), "idmenu" => $id_resource,  "menu_session" => $menu );
						$arrResult['msg'] = _tr("Bookmark cannot be added. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
					}else{
						$pdbACL->commit();
						$arrResult['status'] = TRUE;
					    $arrResult['data'] = array("action" => "add", "menu" => _tr($resource[0][2]), "idmenu" => $id_resource,  "menu_session" => $menu );
						$arrResult['msg'] = _tr("Bookmark has been added.");
						return $arrResult;
					}
				}
			}
		}
	}
	return $arrResult;
}

function menuIsBookmark($menu)
{
	include_once "libs/paloSantoACL.class.php";
	if($menu != ""){
		$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
		global $arrConf;
		$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
		$pACL = new paloACL($pdbACL);
		$uid = $pACL->getIdUser($user);
		if($uid!==FALSE){
			$id_resource = $pACL->getResourceId($menu);
			$bookmarks = "SELECT id FROM acl_user_shortcut WHERE id_user = ? AND id_resource = ? AND type = ?";
			$arr_result1 = $pdbACL->fetchTable($bookmarks, TRUE, array($uid,$id_resource,"bookmark"));
			if($arr_result1 !== FALSE){
				if(count($arr_result1) > 0)
					return true;
				else
					return false;
			}else
				return false;
		}
	}
	return false;
}

function saveNeoToggleTabByUser($menu, $action_status)
{
	include_once "libs/paloSantoACL.class.php";
	$arrResult['status'] = FALSE;
	$arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
	if($menu != ""){
		$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
		global $arrConf;
		$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
		$pACL = new paloACL($pdbACL);
		$uid = $pACL->getIdUser($user);
		if($uid!==FALSE){
			$exist = false;
			$togglesTabs = "SELECT * FROM acl_user_shortcut WHERE id_user = ? AND type = 'NeoToggleTab'";
			$arr_result1 = $pdbACL->getFirstRowQuery($togglesTabs, TRUE, array($uid));
			if($arr_result1 !== FALSE && count($arr_result1) > 0)
				$exist = true;

			if($exist){
				$pdbACL->beginTransaction();
				$query = "UPDATE acl_user_shortcut SET description = ? WHERE id_user = ? AND type = ?";
				$r = $pdbACL->genQuery($query, array($action_status, $uid, "NeoToggleTab"));
				if(!$r){
					$pdbACL->rollBack();
					$arrResult['status'] = FALSE;
					$arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
					return $arrResult;
				}else{
					$pdbACL->commit();
					$arrResult['status'] = TRUE;
					$arrResult['msg'] = _tr("Request has been sent.");
					return $arrResult;
				}
			}else{
				$pdbACL->beginTransaction();
				$query = "INSERT INTO acl_user_shortcut(id_user, id_resource, type, description) VALUES(?, ?, ?, ?)";
				$r = $pdbACL->genQuery($query, array($uid, $uid, "NeoToggleTab", $action_status));
				if(!$r){
					$pdbACL->rollBack();
					$arrResult['status'] = FALSE;
					$arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
					return $arrResult;
				}else{
					$pdbACL->commit();
					$arrResult['status'] = TRUE;
					$arrResult['msg'] = _tr("Request has been sent.");
					return $arrResult;
				}
			}
		}
	}
	return $arrResult;
}

function getStatusNeoTabToggle()
{
	include_once "libs/paloSantoACL.class.php";
	$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
	global $arrConf;
	$exist = false;
	$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
	$pACL = new paloACL($pdbACL);
	$uid = $pACL->getIdUser($user);
	$togglesTabs = "SELECT * FROM acl_user_shortcut WHERE id_user = ? AND type = 'NeoToggleTab'";
	$arr_result1 = $pdbACL->getFirstRowQuery($togglesTabs, TRUE, array($uid));
	if($arr_result1 !== FALSE && count($arr_result1) > 0)
		$exist = true;
	if($exist){
		return $arr_result1['description'];
	}else{
	  return "none";
	}
}

/**
 * Funcion que se encarga obtener un sticky note.
 *
 * @return array con la informacion como mensaje y estado de resultado
 * @param string $menu nombre del menu al cual se le va a agregar la nota
 *
 * @author Eduardo Cueva
 * @author ecueva@palosanto.com
 */
function getStickyNote($menu)
{
	include_once "libs/paloSantoACL.class.php";
	$arrResult['status'] = FALSE;
	$arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
	if($menu != ""){
		$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
		global $arrConf;
		$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
		$pACL = new paloACL($pdbACL);
		$id_resource = $pACL->getResourceId($menu);
		$uid = $pACL->getIdUser($user);
		$date_edit = date("Y-m-d h:i:s");
		if($uid!==FALSE){
			$exist = false;
			$query = "SELECT * FROM sticky_note WHERE id_user = ? AND id_resource = ?";
			$arr_result1 = $pdbACL->getFirstRowQuery($query, TRUE, array($uid, $id_resource));
			if($arr_result1 !== FALSE && count($arr_result1) > 0)
				$exist = true;

			if($exist){
				$arrResult['status'] = TRUE;
				$arrResult['msg'] = "";
				$arrResult['data'] = $arr_result1['description'];
                $arrResult['popup'] = $arr_result1['auto_popup'];
				return $arrResult;
			}else{
				$arrResult['status'] = FALSE;
				$arrResult['msg'] = "no_data";
				$arrResult['data'] = _tr("Click here to leave a note.");
				return $arrResult;
			}
		}
	}
	return $arrResult;
}

/**
 * Funcion que se encarga de guardar o editar una nota de tipo sticky note.
 *
 * @return array con la informacion como mensaje y estado de resultado
 * @param string $menu nombre del menu al cual se le va a agregar la nota
 * @param string $description contenido de la nota que se desea agregar o editar
 *
 * @author Eduardo Cueva
 * @author ecueva@palosanto.com
 */
function saveStickyNote($menu, $description, $popup)
{
	include_once "libs/paloSantoACL.class.php";
	$arrResult['status'] = FALSE;
	$arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
	if($menu != ""){
		$user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
		global $arrConf;
		$pdbACL = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
		$pACL = new paloACL($pdbACL);
		$id_resource = $pACL->getResourceId($menu);
		$uid = $pACL->getIdUser($user);
		$date_edit = date("Y-m-d h:i:s");
		if($uid!==FALSE){
			$exist = false;
			$query = "SELECT * FROM sticky_note WHERE id_user = ? AND id_resource = ?";
			$arr_result1 = $pdbACL->getFirstRowQuery($query, TRUE, array($uid, $id_resource));
			if($arr_result1 !== FALSE && count($arr_result1) > 0)
				$exist = true;

			if($exist){
				$pdbACL->beginTransaction();
				$query = "UPDATE sticky_note SET description = ?, date_edit = ?, auto_popup = ? WHERE id_user = ? AND id_resource = ?";
				$r = $pdbACL->genQuery($query, array($description, $date_edit, $popup, $uid, $id_resource));
				if(!$r){
					$pdbACL->rollBack();
					$arrResult['status'] = FALSE;
					$arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
					return $arrResult;
				}else{
					$pdbACL->commit();
					$arrResult['status'] = TRUE;
					$arrResult['msg'] = "";
					return $arrResult;
				}
			}else{
				$pdbACL->beginTransaction();
				$query = "INSERT INTO sticky_note(id_user, id_resource, date_edit, description, auto_popup) VALUES(?, ?, ?, ?, ?)";
				$r = $pdbACL->genQuery($query, array($uid, $id_resource, $date_edit, $description, $popup));
				if(!$r){
					$pdbACL->rollBack();
					$arrResult['status'] = FALSE;
					$arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
					return $arrResult;
				}else{
					$pdbACL->commit();
					$arrResult['status'] = TRUE;
					$arrResult['msg'] = "";
					return $arrResult;
				}
			}
		}
	}
	return $arrResult;
}

// Set default timezone from /etc/sysconfig/clock for PHP 5.3+ compatibility
function load_default_timezone()
{
    $sDefaultTimezone = @date_default_timezone_get();
    if ($sDefaultTimezone == 'UTC') {
        $sDefaultTimezone = 'America/New_York';
        if (file_exists('/etc/sysconfig/clock')) {
            foreach (file('/etc/sysconfig/clock') as $s) {
                $regs = NULL;
                if (preg_match('/^ZONE\s*=\s*"(.+)"/', $s, $regs)) {
                    $sDefaultTimezone = $regs[1];
                }
            }
        }
    }
    date_default_timezone_set($sDefaultTimezone);
}

// Create a new Smarty object and initialize template directories
function getSmarty($mainTheme, $basedir = '/var/www/html')
{
    require_once("$basedir/libs/smarty/libs/Smarty.class.php");
    $smarty = new Smarty();
    
    $smarty->template_dir = "$basedir/themes/$mainTheme";
    $smarty->config_dir =   "$basedir/configs/";
    $smarty->compile_dir =  "$basedir/var/templates_c/";
    $smarty->cache_dir =    "$basedir/var/cache/";

    return $smarty;
}

?>