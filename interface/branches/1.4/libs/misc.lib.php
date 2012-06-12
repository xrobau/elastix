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
            if(ereg("^MemTotal:[[:space:]]+([[:digit:]]+) kB", $linea, $arrReg)) {
                $arrInfo["MemTotal"]=trim($arrReg[1]);
            }
            if(ereg("^MemFree:[[:space:]]+([[:digit:]]+) kB", $linea, $arrReg)) {
                $arrInfo["MemFree"]=trim($arrReg[1]);
            }
            if(ereg("^Buffers:[[:space:]]+([[:digit:]]+) kB", $linea, $arrReg)) {
                $arrInfo["MemBuffers"]=trim($arrReg[1]);
            }
            if(ereg("^SwapTotal:[[:space:]]+([[:digit:]]+) kB", $linea, $arrReg)) {
                $arrInfo["SwapTotal"]=trim($arrReg[1]);
            }
            if(ereg("^SwapFree:[[:space:]]+([[:digit:]]+) kB", $linea, $arrReg)) {
                $arrInfo["SwapFree"]=trim($arrReg[1]);
            }
            if(ereg("^Cached:[[:space:]]+([[:digit:]]+) kB", $linea, $arrReg)) {
                $arrInfo["Cached"]=trim($arrReg[1]);
            }
        }
        fclose($fh);
    }

    if($fh=fopen("/proc/cpuinfo", "r")) {
        while($linea=fgets($fh, "4048")) {
            // Aqui parseo algunos parametros
            if(ereg("^model name[[:space:]]+:[[:space:]]+(.*)$", $linea, $arrReg)) {
                $arrInfo["CpuModel"]=trim($arrReg[1]);
            }
            if(ereg("^vendor_id[[:space:]]+:[[:space:]]+(.*)$", $linea, $arrReg)) {
                $arrInfo["CpuVendor"]=trim($arrReg[1]);
            }
            if(ereg("^cpu MHz[[:space:]]+:[[:space:]]+(.*)$", $linea, $arrReg)) {
                $arrInfo["CpuMHz"]=trim($arrReg[1]);
            }
        }
        fclose($fh);
    }


    if($fh=fopen("/proc/stat", "r")) {
        while($linea=fgets($fh, "4048")) {
            if(ereg("^cpu[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)" .
                    "[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)" .
                    "[[:space:]]+([[:digit:]]+)[[:space:]]?", $linea, $arrReg)) {
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
        if(ereg("up[[:space:]]+([[:digit:]]+ days?,)?(([[:space:]]*[[:digit:]]{1,2}:[[:digit:]]{1,2}),?)?([[:space:]]*[[:digit:]]+ min)?",
                $arrExec[0],$arrReg)) {
            if(!empty($arrReg[3]) and empty($arrReg[4])) {
                list($uptime_horas, $uptime_minutos) = split(":", $arrReg[3]);
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
            if(ereg("^([/-_\.[:alnum:]|-]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+([[:digit:]]+)" .
                    "[[:space:]]+([[:digit:]]{1,2}%)[[:space:]]+([/-_\.[:alnum:]]+)$", $lineaParticion, $arrReg)) {
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

function construirURL($arrVars=array(), $arrExcluir=array())
{
    $strURL = "?";

    // Incluyo en el string las variables arbitrarias
    if(is_array($arrVars)) {
        foreach($arrVars as $varName => $value) {
            // Excluyo las variables que se deben excluir
            if(!in_array($varName, $arrExcluir)) {
                $strURL .="$varName=" . urlencode($value) . "&";
            }
        }
    }

    // Incluyo en el string las variables GET
    if(is_array($_GET)) {
        foreach($_GET as $varName => $value) {
            if(!array_key_exists($varName, $arrVars)) {
                // Excluyo las variables que se deben excluir
                if(!in_array($varName, $arrExcluir)) {
                    $strURL .= "$varName=$value&";
                }
            }
        }
    }

    // Elimino el ultimo caracter pues es un ? o un &
    $strURL = substr($strURL, 0, strlen($strURL)-1);

    return $strURL;
}

// Translate a date in format 9 Dec 2006
function translateDate($dateOrig)
{
    if(ereg("([[:digit:]]{1,2})[[:space:]]+([[:alnum:]]{3})[[:space:]]+([[:digit:]]{4})", $dateOrig, $arrReg)) {
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
    require_once $ruta_base."configs/default.conf.php";
    global $arrConf;
    include_once $ruta_base."libs/paloSantoDB.class.php";
    include $ruta_base."configs/default.conf.php";
    include_once $ruta_base."configs/languages.conf.php";
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

    include_once $ruta_base."lang/".$lang.".lang";
}

function cargar_menu($db)
{
   //leer el contenido de la tabla menu y devolver un arreglo con la estructura
    $menu = array ();
    $query="Select m1.*, (Select count(*) from menu m2 where m2.IdParent=m1.id) as HasChild from menu m1;";
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
    global $arrConf;
    $lang="";
    //conectarse a la base de settings para obtener el idioma actual
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
    if(empty($pDB->errMsg)) {
        $lang=get_key_settings($pDB,'language');
    }
    return $lang;
}



#funciones para menu

function guardar_dominio_sistema($domain_name,&$errMsg)
{
    $continuar=FALSE;
    global $arrLang;
     //Se debe modificar el archivo /etc/postfix/main.cf para agregar el dominio a la variable
     //mydomain2
    $conf_file=new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
    $contenido=$conf_file->leer_configuracion();
    $valor_anterior=$conf_file->privado_get_valor($contenido,"mydomain2");
    $valor_nuevo=construir_valor_nuevo_postfix($valor_anterior,$domain_name);
    $arr_reemplazos=array('mydomain2'=>$valor_nuevo);
    $bValido=$conf_file->escribir_configuracion($arr_reemplazos);
    if($bValido){
        //Se deben recargar la configuracion de postfix
        $retval=$output="";
        exec("sudo -u root postfix reload",$output,$retval);
        if($retval==0)
            $continuar=TRUE;
        else
            $errMsg=$arrLang["main.cf file was updated successfully but when restarting the mail service failed"];
  
    }
    return $continuar;
}


function construir_valor_nuevo_postfix($valor_anterior,$dominio,$eliminar_dominio=FALSE){
    $valor_nuevo=$valor_anterior;

    if(is_null($valor_anterior)){
        $elemento=(!$eliminar_dominio)?"$dominio":"";
        $valor_nuevo="$elemento";
    }
    else{
        if(ereg("^(.*)$",$valor_anterior,$regs)){
            $arr_valores=explode(',',$regs[1]);
            if(!$eliminar_dominio)
                $arr_valores[]="$dominio";

            $valor_nuevo="";
            for($i=0;$i<count($arr_valores);$i++){
                $valor_nuevo.=$arr_valores[$i];
                if($i<(count($arr_valores)-1))
                    $valor_nuevo.=","; 
            }

            if($eliminar_dominio==TRUE){
                $valor_nuevo=str_replace(",$dominio","",$valor_nuevo);
            }
        }
    }
    return $valor_nuevo;
}

function eliminar_dominio($db,$arrDominio,&$errMsg)
{ 
    $pEmail = new paloEmail($db);
    $total_cuentas=0;
    $output="";

    global $CYRUS;
    global $arrLang;
    $cyr_conn = new cyradm;
    $continuar=$cyr_conn -> imap_login();

      # First Delete all stuff related to the domain from the database
    if ($continuar){
        $query1 = "SELECT * FROM accountuser WHERE id_domain='$arrDominio[id_domain]' order by username";
        $result=$db->fetchTable($query1,TRUE);

        if(is_array($result) && count($result)>0){
            foreach ($result as $fila){
                $username = $fila['username'];
                $bExito=eliminar_cuenta($db,$username,$errMsg);

                if (!$bExito) $output = $errMsg;
                /*$cyr_conn->deletemb("user/".$username)."<br>";
                exec("sudo -u root saslpasswd2 -d $username@".SASL_DOMAIN);

                if($cyr_conn->error_msg!="" && (strpos($cyr_conn->error_msg, "Mailbox does not exist")===false))
                    $output.=$cyr_conn->error_msg;

                $bValido5=$pEmail->deleteAliasesFromAccount($username);
                //borrar de virtual
                $bool=eliminar_virtual_sistema($email,$error);*/
            }
        }

        if($output!=""){
            $errMsg=$arrLang["Error deleting user accounts from system"].": $output";
            return FALSE;
        }

        //uso la clase Email

        $bExito=$pEmail->deleteAccountsFromDomain($arrDominio['id_domain']);
        if (!$bExito){
            $errMsg=$arrLang["Error deleting user accounts"].' :'.((isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg);
            return FALSE;
        }
        $bExito=$pEmail->deleteDomain($arrDominio['id_domain']);
        if (!$bExito){
            $errMsg=$arrLang["Error deleting record from table domain"].' :'.((isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg);
            return FALSE;
        }



//Se elimina el dominio del archivo main.cf y se recarga la configuracion
        $continuar=FALSE;
       //Se debe modificar el archivo /etc/postfix/main.cf para borrar el dominio a la variable
       //mydomain2
        $conf_file=new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
        $contenido=$conf_file->leer_configuracion();
        $valor_anterior=$conf_file->privado_get_valor($contenido,"mydomain2");
        $valor_nuevo=construir_valor_nuevo_postfix($valor_anterior,$arrDominio['domain_name'],TRUE);
        $arr_reemplazos=array('mydomain2'=>$valor_nuevo);
        $bValido=$conf_file->escribir_configuracion($arr_reemplazos);

        if($bValido){
           //Se deben recargar la configuracion de postfix
            $retval=$output="";
            exec("sudo -u root postfix reload",$output,$retval);
            if($retval==0)
                $continuar=TRUE;
            else
                $errMsg=$arrLang["main.cf file was updated successfully but when restarting the mail service failed"]." : $retval";

       }

    }
    return $continuar;

}
function eliminar_usuario_correo_sistema($username,$email,&$error){
    $output=array();
    exec("sudo -u root /usr/sbin/saslpasswd2 -d $username@".SASL_DOMAIN,$output);
    if(is_array($output) && count($output)>0){
        foreach($output as $linea)
            $error.=$linea."<br>";
    }

    if($error!="")
        return FALSE;

    $bool=eliminar_virtual_sistema($email,$error);

    return $bool;
}

function eliminar_virtual_sistema($email,&$error){
    $config=new paloConfig("/etc/postfix","virtual","\t","[[:space:]?\t[:space:]?]");     
    $arr_direcciones=$config->leer_configuracion();


    $eliminado=FALSE;
    foreach($arr_direcciones as $key=>$fila){
        if(isset($fila['clave']) && $fila['clave']==$email){
             unset($arr_direcciones[$key]);
             $eliminado=TRUE;
        }
        elseif(ereg("^$email",$fila)){
             unset($arr_direcciones[$key]);
             $eliminado=TRUE;
        }
    }
    if($eliminado){
        $bool=$config->escribir_configuracion($arr_direcciones,true);
        if($bool){
            exec("sudo -u root postmap /etc/postfix/virtual",$output);
            if(is_array($output) && count($output)>0)
                foreach($output as $linea)
                    $error.=$linea."<br>";
        }
        else{
            $error.=$config->getMessage();
            return FALSE;
        }
    }

    return TRUE;
}






function crear_usuario_correo_sistema($email,$username,$clave,&$error,$virtual=TRUE){
    $output=array();

    exec("echo \"$clave\" | sudo -u root /usr/sbin/saslpasswd2 -c $username -u ".SASL_DOMAIN,$output);


    if(is_array($output) && count($output)>0){
        foreach($output as $linea_salida)
            $error.=$linea_salida."<br>";
    }
    if($error!="") 
        return FALSE;
    elseif($virtual){
        $bool=crear_virtual_sistema($email,$username,$error);
        if(!$bool)
            return FALSE;
        else
            return TRUE;
    }
    else
        return TRUE;

}


function crear_virtual_sistema($email,$username,&$error){
    $output=array();

    exec("sudo -u root chown asterisk /etc/postfix/virtual");
    $username.='@'.SASL_DOMAIN;
    exec("echo \"$email \t $username\" >> /etc/postfix/virtual",$output);

    if(is_array($output) && count($output)>0){
        foreach($output as $linea)
            $error.=$linea."<br>";
    }   
    exec("sudo -u root chown root /etc/postfix/virtual");

    exec("sudo -u root postmap /etc/postfix/virtual",$output);
    if(is_array($output) && count($output)>0){
         foreach($output as $linea)
            $error.=$linea."<br>";
    }
    if($error!="")
        return FALSE;
    else
        return TRUE;
}
function eliminar_cuenta($db,$username,$errMsg){
    global $CYRUS;
    $arr_alias=array();
    $pEmail = new paloEmail($db);
    //primero se obtienen las direcciones de mail del usuario (virtuales)
    $arrAlias=$pEmail->getAliasAccount($username);
    if (is_array($arrAlias)){
        foreach ($arrAlias as $fila)
            $arr_alias[]=$fila[1];
    }
    //servira hacerlo como transaccion??????
    $pEmail->deleteAliasesFromAccount($username);
    $bExito=$pEmail->deleteAccount($username);
    if ($bExito){
        $cyr_conn = new cyradm;
        $bValido = $cyr_conn->imap_login();

        if ($bValido ===FALSE){
            $errMsg=$cyr_conn->getMessage();
            return FALSE;
        }

        $bValido=$cyr_conn->deletemb("user/".$username);
        if($bValido===FALSE){
            $errMsg=$cyr_conn->getMessage();
            return FALSE;
        }
        $cyr_conn->deletemb("user/".$username)."<br>";

        foreach($arr_alias as $alias){
            if(!eliminar_usuario_correo_sistema($username,$alias,$errMsg)){
                return FALSE;
            }
        }
        return TRUE;
    }
    return $bExito;
}

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

    if(!($checked=='off'))
        $check = "checked=\"checked\"";
    if(!($disable=='off'))
        $disab = "disabled=\"disabled\"";

    $checkbox  = "<input type=\"checkbox\" name=\"chkold{$id_name}\" $check $disab onclick=\"javascript:{$id_name}check();\" /> 
                  <input type=\"hidden\"   name=\"{$id_name}\" id=\"{$id_name}\"   value=\"{$checked}\" />
                  <script type=\"text/javascript\">
                    function {$id_name}check(){
                        var node = document.getElementById('$id_name');
                        if(node.value == 'on')
                            node.value = 'off';
                        else node.value = 'on';
                    }
                  </script>";
    return $checkbox;
}
?>
