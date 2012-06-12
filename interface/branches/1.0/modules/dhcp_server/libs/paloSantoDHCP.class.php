<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  $Id: paloSantoDHCP.class.php,v 1.1 2008/01/04 10:39:57 bmacias Exp $ */

include_once("libs/paloSantoDB.class.php");

/* Clase que implementa DHCP Server */
class PaloSantoDHCP
{
    var $_DB; // instancia de la clase paloDB
    var $errMsg;
    var $pathFileConfDHCP;
    function PaloSantoDHCP(&$pDB)
    {
        $this->pathFileConfDHCP = "/etc/dhcpd.conf";
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

    function getConfigurationDHCP() 
    {
        global $arrLang;

        // Trato de abrir el archivo de configuracion de dhcp
        if($fh = @fopen("/etc/dhcpd.conf", "r")) {
            $arrDirectivasEncontradas = array();
            $arrConfigurationDHCP = array();
    
            while($linea_archivo = fgets($fh, 4096)) {
                // RANGO DE IPS
                $patron = "^[[:space:]]*range dynamic-bootp[[:space:]]+([[:digit:]]{1,3})\.([[:digit:]]{1,3})\." .
                        "([[:digit:]]{1,3})\.([[:digit:]]{1,3})[[:space:]]+([[:digit:]]{1,3})\." .
                        "([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})[[:space:]]?;";
                if(ereg($patron, $linea_archivo, $arrReg)) {
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_ini_1"] = $arrReg[1]; 
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_ini_2"] = $arrReg[2];
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_ini_3"] = $arrReg[3]; 
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_ini_4"] = $arrReg[4];
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_fin_1"] = $arrReg[5]; 
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_fin_2"] = $arrReg[6];
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_fin_3"] = $arrReg[7]; 
                    $arrConfigurationDHCP["IPS_RANGE"]["in_ip_fin_4"] = $arrReg[8];
                } 
    
                // LEASE TIME
                $patron = "^[[:space:]]*default-lease-time[[:space:]]([[:digit:]]{1,8})[[:space:]]?;";
                if(ereg($patron, $linea_archivo, $arrReg)) {
                    $arrConfigurationDHCP["LEASE_TIME"]["in_lease_time"] = $arrReg[1];
                } 
    
                // GATEWAY
                $patron = "^[[:space:]]*option routers[[:space:]]+([[:digit:]]{1,3})\.([[:digit:]]{1,3})\." .
                        "([[:digit:]]{1,3})\.([[:digit:]]{1,3})[[:space:]]?";
                if(ereg($patron, $linea_archivo, $arrReg)) {
                    $arrConfigurationDHCP["GATEWAY"]["in_gw_1"] = $arrReg[1]; 
                    $arrConfigurationDHCP["GATEWAY"]["in_gw_2"] = $arrReg[2];
                    $arrConfigurationDHCP["GATEWAY"]["in_gw_3"] = $arrReg[3]; 
                    $arrConfigurationDHCP["GATEWAY"]["in_gw_4"] = $arrReg[4]; 
                } 
    
                // GATEWAY NETMASK
                $patron = "^[[:space:]]*option subnet-mask[[:space:]]+([[:digit:]]{1,3})\.([[:digit:]]{1,3})\." .
                        "([[:digit:]]{1,3})\.([[:digit:]]{1,3})[[:space:]]?";
                if(ereg($patron, $linea_archivo, $arrReg)) {
                    $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_1"] = $arrReg[1]; 
                    $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_2"] = $arrReg[2];
                    $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_3"] = $arrReg[3]; 
                    $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_4"] = $arrReg[4]; 
                } 
    
                // WINS
                $patron = "^[[:space:]]*option netbios-name-servers[[:space:]]+([[:digit:]]{1,3})\.([[:digit:]]{1,3})\." .
                        "([[:digit:]]{1,3})\.([[:digit:]]{1,3})[[:space:]]?";
                if(ereg($patron, $linea_archivo, $arrReg)) {
                    $arrConfigurationDHCP["WINS"]["in_wins_1"] = $arrReg[1]; 
                    $arrConfigurationDHCP["WINS"]["in_wins_2"] = $arrReg[2];
                    $arrConfigurationDHCP["WINS"]["in_wins_3"] = $arrReg[3]; 
                    $arrConfigurationDHCP["WINS"]["in_wins_4"] = $arrReg[4]; 
                }
    
                // DNSs
                $patron = "^[[:space:]]*option domain-name-servers[[:space:]]+([[:digit:]]{1,3})\.([[:digit:]]{1,3})\." .
                        "([[:digit:]]{1,3})\.([[:digit:]]{1,3})[[:space:]]?";
                if(ereg($patron, $linea_archivo, $arrReg)) {
                    if(!isset($arrConfigurationDHCP["DNS1"])) {
                        $arrConfigurationDHCP["DNS1"]["in_dns1_1"] = $arrReg[1]; 
                        $arrConfigurationDHCP["DNS1"]["in_dns1_2"] = $arrReg[2];
                        $arrConfigurationDHCP["DNS1"]["in_dns1_3"] = $arrReg[3]; 
                        $arrConfigurationDHCP["DNS1"]["in_dns1_4"] = $arrReg[4]; 
                    } else if (!isset($arrConfigurationDHCP["DNS2"])){
                        $arrConfigurationDHCP["DNS2"]["in_dns2_1"] = $arrReg[1]; 
                        $arrConfigurationDHCP["DNS2"]["in_dns2_2"] = $arrReg[2];
                        $arrConfigurationDHCP["DNS2"]["in_dns2_3"] = $arrReg[3]; 
                        $arrConfigurationDHCP["DNS2"]["in_dns2_4"] = $arrReg[4];
                    } 
                }
            } //end while
    
            if(!isset($arrConfigurationDHCP["IPS_RANGE"])){
                // Error, no se encontro el rango de IPs, la directiva mas importante...
                $this->errMsg = $arrLang["Could not find IP range"];
            }

            //Lleno de vacio los q no se encontraron... Para tener q mostrar .. esto solo por presentacion.
            if(!isset($arrConfigurationDHCP["DNS2"])){
                $arrConfigurationDHCP["DNS2"]["in_dns2_1"] = ""; 
                $arrConfigurationDHCP["DNS2"]["in_dns2_2"] = "";
                $arrConfigurationDHCP["DNS2"]["in_dns2_3"] = ""; 
                $arrConfigurationDHCP["DNS2"]["in_dns2_4"] = "";
            }
            if(!isset($arrConfigurationDHCP["WINS"])){
                $arrConfigurationDHCP["WINS"]["in_wins_1"] = ""; 
                $arrConfigurationDHCP["WINS"]["in_wins_2"] = "";
                $arrConfigurationDHCP["WINS"]["in_wins_3"] = ""; 
                $arrConfigurationDHCP["WINS"]["in_wins_4"] = "";
            }
            if(!isset($arrConfigurationDHCP["GATEWAY_NETMASK"])){
                $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_1"] = ""; 
                $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_2"] = "";
                $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_3"] = ""; 
                $arrConfigurationDHCP["GATEWAY_NETMASK"]["in_gwm_4"] = ""; 
            }
        }
        else{
            // Error al abrir el archivo
            $this->errMsg = $arrLang["DHCP configuration reading error: Verify that the DHCP service is installed and try again."];
        }
        return $arrConfigurationDHCP;
    }

    function getStatusServiceDHCP()
    {
        //VERIFICACION DE QUE EL PROCESO dhcpd ESTA EJECUTANDOSE
        //Por ahora voy a basarme en si el archivo /var/run/dhcpd.pid existe
        //y voy a suponer que si EXISTE el servicio esta corriendo, caso contrario NO.

        //vemos si se esta ejecutando el servicio dhcp
        $out=`sudo /sbin/service dhcpd status`;

        if(file_exists("/var/run/dhcpd.pid") && eregi("pid",$out))
            return "active"; 
        else
            return "desactive";
    }

    function startServiceDHCP()
    {
        $flag = false;
        if(!file_exists("/var/run/dhcpd.pid")) {
            $out = `sudo /sbin/service dhcpd start`;
            if(eregi("OK",$out)){
                exec("sudo -u root chkconfig --level 235 dhcpd on",$arrConsole,$flagStatus);
                $flag = ($flagStatus)?false:true;
            }
        }
        return $flag;
    }

    function stopServiceDHCP()
    {
        $flag = false;
        // Intentar terminar el servicio
        if(file_exists("/var/run/dhcpd.pid")) {
            $out = `sudo /sbin/service dhcpd stop`;
            if(eregi("OK",$out)){
                exec("sudo -u root chkconfig --level 235 dhcpd off",$arrConsole,$flagStatus);
                $flag = ($flagStatus)?false:true;
            }
        }
        return $flag;
    }

    function calcularIpSubred($ipCualquiera, $mascaraRed)
    {
        if(empty($ipCualquiera) or empty($mascaraRed)) {
            return false;
        }

        $arrLanIp   = explode(".", $ipCualquiera);
        $arrLanMask = explode(".", $mascaraRed);

        $IPSubnetOct1 = (int)$arrLanIp[0]&(int)$arrLanMask[0];
        $IPSubnetOct2 = (int)$arrLanIp[1]&(int)$arrLanMask[1];
        $IPSubnetOct3 = (int)$arrLanIp[2]&(int)$arrLanMask[2];
        $IPSubnetOct4 = (int)$arrLanIp[3]&(int)$arrLanMask[3];
        $strResultado = $IPSubnetOct1 . "." . $IPSubnetOct2 . "." . $IPSubnetOct3 . "." . $IPSubnetOct4;
        return $strResultado;
    }

    function updateFileConfDHCP(
                $ip_gw, 
                $ip_gw_nm, 
                $ip_wins, 
                $ip_dns1, 
                $ip_dns2, 
                $IPSubnet, 
                $conf_red_actual,
                $ip_ini,
                $ip_fin,
                $in_lease_time)
    {
        //PASO 1: PREPARO EL ARREGLOS DE ATRIBUTOS PARA CREAR EL CONTENIDO DEL ARCHIVO.
        $arrAttributes['ip_gw']         = $ip_gw;
        $arrAttributes['ip_gw_nm']      = $ip_gw_nm;
        $arrAttributes['ip_wins']       = $ip_wins;
        $arrAttributes['ip_dns1']       = $ip_dns1;
        $arrAttributes['ip_dns2']       = $ip_dns2;
        $arrAttributes['IPSubnet']      = $IPSubnet; 
        $arrAttributes['lan_mask']      = $conf_red_actual['lan_mask']; 
        $arrAttributes['lan_ip']        = $conf_red_actual['lan_ip']; 
        $arrAttributes['ip_ini']        = $ip_ini;
        $arrAttributes['ip_fin']        = $ip_fin;
        $arrAttributes['in_lease_time'] = $in_lease_time;

        //PASO 2: CREO EL CONTENIDO DEL ARCHIVO
        $contentFileDHCP = $this->createContentConfDHCP($arrAttributes);
        exec("sudo -u root chown asterisk:asterisk ".$this->pathFileConfDHCP,$arrConsole,$flagStatus1);
        exec("sudo -u root chmod 666 ".$this->pathFileConfDHCP,$arrConsole,$flagStatus2);

        //PASO 3: SOBREESCRIBO EL ARCHIVO DE CONFIGURACION
        if($flagStatus1==0 && $flagStatus2==0){
            if($fh_dhcpd = @fopen($this->pathFileConfDHCP, "w")) {
                fwrite($fh_dhcpd, $contentFileDHCP);
                fclose($fh_dhcpd);
            }
            else{
                $this->errMsg = $arrLang["Failed to update the file configuration"].": ".$this->pathFileConfDHCP; 
                return false;
            }
        } 
        else{ 
            $this->errMsg = $arrLang["Failed to update the file configuration"].": ".$this->pathFileConfDHCP; 
            return false;
        }
        exec("sudo -u root chown root:root ".$this->pathFileConfDHCP,$arrConsole,$flagStatus1);
        exec("sudo -u root chmod 644 ".$this->pathFileConfDHCP,$arrConsole,$flagStatus2);
        if($flagStatus1==0 && $flagStatus2==0) 
            return true;
        else{ 
            $this->errMsg = $arrLang['The update was successful, but was unable to reestablish the permissions of the file dhcp.conf']; 
            return false;
        }
    }

    function createContentConfDHCP($arrAttributes)
    {
        $tpl = $this->getTemplateFileConfDHCP();
        $tpl = str_replace("{CONF_GATEWAY}",        $arrAttributes['ip_gw'],   $tpl);
        $tpl = str_replace("{CONF_GATEWAY_NETMASK}",$arrAttributes['ip_gw_nm'],$tpl);

        if($arrAttributes['ip_wins']!="...") $tpl = str_replace("{CONF_WINS}","\toption netbios-name-servers\t{$arrAttributes['ip_wins']};\n",$tpl);
        else $tpl = str_replace("{CONF_WINS}","",$tpl);

        $tpl = str_replace("{IP_SUBNET_LAN}",   $arrAttributes['IPSubnet'],$tpl); // El $IPSubnet lo obtuve mas arriba
        $tpl = str_replace("{MASK_SUBNET_LAN}", $arrAttributes['lan_mask'],$tpl);
        // Fin del calculo de la subnet lan

        $lineas_dns  = "\toption domain-name-servers\t{$arrAttributes['ip_dns1']};\n";
        if($arrAttributes['ip_dns2']!="...") $lineas_dns .= "\toption domain-name-servers\t{$arrAttributes['ip_dns2']};\n";

        $tpl = str_replace("{CONF_DOMAIN_NAME_SERVER}",$lineas_dns,                    $tpl);
        $tpl = str_replace("{CONF_NTP_SERVERS}",       $arrAttributes['lan_ip'],       $tpl);
        $tpl = str_replace("{CONF_TFTP_SERVER_NAME}",  $arrAttributes['lan_ip'],       $tpl);
        $tpl = str_replace("{CONF_IP_INICIO}",         $arrAttributes['ip_ini'],       $tpl);
        $tpl = str_replace("{CONF_IP_FIN}",            $arrAttributes['ip_fin'],       $tpl);
        $tpl = str_replace("{CONF_LEASE_TIME}",        $arrAttributes['in_lease_time'],$tpl);
        return $tpl;
    }

    function getTemplateFileConfDHCP()
    {
        $template = "ddns-update-style interim;\n".
                    "ignore client-updates;\n\n".

                    "subnet {IP_SUBNET_LAN} netmask {MASK_SUBNET_LAN} {\n".

	            "\toption routers\t\t\t{CONF_GATEWAY};\n\n".
                    "\toption subnet-mask\t\t{MASK_SUBNET_LAN};\n".
                    "\toption nis-domain\t\t\"asterisk.local\";\n".
                    "\toption domain-name\t\t\"asterisk.local\";\n".
                    "{CONF_DOMAIN_NAME_SERVER}\n".
                    "\toption time-offset\t\t-18000; # Eastern Standard Time\n".
                    "\toption ntp-servers\t\t{CONF_NTP_SERVERS};\n".
                    "\toption tftp-server-name\t\t\"tftp://{CONF_TFTP_SERVER_NAME}\";\n".
                    "{CONF_WINS}\n\n".

	            "\trange dynamic-bootp {CONF_IP_INICIO} {CONF_IP_FIN};\n".
	            "\tdefault-lease-time {CONF_LEASE_TIME};\n".
	            "\tmax-lease-time 50000;\n".
                    "}";
        return $template;
    }

    function restartServiceDHCP()
    { 
        // Reinicio el servicio, si es que esto aplica
        // Hay 3 casos
        $dhcp_status = $this->getStatusServiceDHCP();
        if(file_exists("/var/run/dhcpd.pid") and $dhcp_status=='active') {
            exec("/sg/bin/sudo -u root service dhcpd restart",$arrConsole,$flagReturn1);
            exec("/sg/bin/sudo -u root chkconfig --level 235 dhcpd on",$arrConsole,$flagReturn2);
            return (($flagReturn1)?false:true) and (($flagReturn2)?false:true);
        } 
        else if (file_exists("/var/run/dhcpd.pid") and $dhcp_status=='desactive') {
            exec("/sg/bin/sudo -u root service dhcpd stop",$arrConsole,$flagReturn1);
            exec("/sg/bin/sudo -u root chkconfig --level 235 dhcpd off",$arrConsole,$flagReturn2);
            return (($flagReturn1)?false:true) and (($flagReturn2)?false:true);
        } 
        else if (!file_exists("/var/run/dhcpd.pid") and $dhcp_status=='active') {
            exec("/sg/bin/sudo -u root service dhcpd start",$arrConsole,$flagReturn1);
            exec("/sg/bin/sudo -u root chkconfig --level 235 dhcpd on",$arrConsole,$flagReturn2);
            return (($flagReturn1)?false:true) and (($flagReturn2)?false:true);
        } 
        else {
            exec("/sg/bin/sudo -u root chkconfig --level 235 dhcpd off",$arrConsole,$flagReturn2);
            return ($flagReturn2)?false:true;
        }
    }
}
?>