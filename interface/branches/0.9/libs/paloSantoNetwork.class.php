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
  $Id: paloSantoNetwork.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class paloNetwork
{
    var $errMsg;

    // Constructor
    function paloNetwork()
    {
        $this->errMsg = "";
    }

    function obtener_modelos_interfases_red()
    {
    
        $arrSalida=array();
        $str = shell_exec("/bin/dmesg");
    
        $arrLineasDmesg = split("\n", $str);
    
        foreach($arrLineasDmesg as $lineaDmesg) {
            //if(ereg("^(eth[[:digit:]]{1,3})", $lineaDmesg, $arrReg)) {
            //    echo $lineaDmesg;
            //}
            if(ereg("^(eth[[:digit:]]{1,3}):[[:space:]]+(.*)$", $lineaDmesg, $arrReg) and ereg(" at ", $lineaDmesg)) {
                $arrSalida[$arrReg[1]] = $arrReg[2];
            }
        }
    
        return $arrSalida;
    
    }
   
    function obtener_tipo_interfase($if)
    {
        $filePattern = "/etc/sysconfig/network-scripts/ifcfg-";
        $fileIf      = $filePattern . $if;
        $lineaIfcfg  = "";
        $type        = "static";

        if($fh = fopen($fileIf, "r")) {
            while(!feof($fh)) {
                $lineaIfcfg = fgets($fh, 4048);
                if(ereg("^BOOTPROTO[[:space:]]*=[[:space:]]*dhcp", $lineaIfcfg)) {
                    $type = "dhcp";
                }
            }
            fclose($fh);
        } else {
            // error
            $type = "";
        }

        return $type;
    }
 
    function obtener_interfases_red()
    {
    	$str = shell_exec("/sbin/ifconfig");
 
        $arrIfconfig = split("\n", $str);
    
        $arrModelosInterfasesRed = $this->obtener_modelos_interfases_red();
 
        foreach($arrIfconfig as $lineaIfconfig) {
    
            unset($arrReg);
    
            if(ereg("^eth(([[:digit:]]{1,3})(:([[:digit:]]{1,3}))?)[[:space:]]+", $lineaIfconfig, $arrReg)) {
                $interfaseActual = "eth" . $arrReg[1];
                $nombreInterfase = "Ethernet $arrReg[2]";
                if(!empty($arrReg[3])) {
                    $nombreInterfase .= " Alias $arrReg[4]";
                } else if(isset($arrModelosInterfasesRed[$interfaseActual])) {
                    $arrIf[$interfaseActual]["HW_info"] = $arrModelosInterfasesRed[$interfaseActual];        
                }
                $arrIf[$interfaseActual]["Name"] = $nombreInterfase;
                $arrIf[$interfaseActual]["Type"] = $this->obtener_tipo_interfase($interfaseActual);
            }
    
            if(ereg("^(lo)[[:space:]]+", $lineaIfconfig, $arrReg)) {
                    $interfaseActual = $arrReg[1];
                    $arrIf[$interfaseActual]["Name"] = "Loopback";
            }
    
            // debo tambien poder determinar cuando se termina una segmento de interfase
            // no solo cuando comienza como se hace en los dos parrafos anteriores
    	
            if(ereg("HWaddr ([ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:" .
                    "[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2})", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["HWaddr"] = $arrReg[1];
            }
    
            if(ereg("^[[:space:]]+inet addr:([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3})",
            $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["Inet Addr"] = $arrReg[1];
            }
    
            if(ereg("[[:space:]]+Mask:([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3})$",
            $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["Mask"] = $arrReg[1];
            }
    
            // TODO: El siguiente patron de matching es muy simple, cambiar
            if(ereg(" RUNNING ", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["Running"] = "Yes";
            }
    
            if(ereg("^[[:space:]]+RX packets:([[:digit:]]{1,20})", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["RX packets"] = $arrReg[1];
            }
    
            if(ereg("^[[:space:]]+RX bytes:([[:digit:]]{1,20})", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["RX bytes"] = $arrReg[1];
            }
    
            if(ereg("^[[:space:]]+TX packets:([[:digit:]]{1,20})", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["TX packets"] = $arrReg[1];
            }
    
            if(ereg("[[:space:]]+TX bytes:([[:digit:]]{1,20})", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["TX bytes"] = $arrReg[1];
            }
    
    	}
        
        return $arrIf;
    } 

    // Es decir que no se incluye "lo" ni interfases virtuales
    function obtener_interfases_red_fisicas()
    {
        $arrInterfasesRedPreliminar=array();
        $arrInterfasesRedPreliminar=$this->obtener_interfases_red();
        // TODO: Validar si $arrInterfasesRedPreliminar es un arreglo
    
        // Selecciono solo las interfases de red fisicas
        $arrInterfasesRed=array();
        foreach($arrInterfasesRedPreliminar as $nombreReal => $arrData) {
        if(ereg("^eth[[:digit:]]{1,3}$", $nombreReal)) {
                $arrInterfasesRed[$nombreReal]=$arrData;
            }
        }
    
        return $arrInterfasesRed;
    }

    function obtener_configuracion_red()
    {
        $archivoResolv = "/etc/resolv.conf";
        $arrResult = array();

        //- Obtengo los dnss
        if($fh=fopen($archivoResolv, "r")) {
            while(!feof($fh)) {
                $linea = fgets($fh, 4048); 
                if(ereg("^nameserver[[:space:]]+(.*)$", $linea, $arrReg)) {
                    $arrResult['dns'][] = $arrReg[1];
                }                
            } 

        } else {
            // Error?
        }

        //- Obtengo el hostname
        exec("/bin/hostname", $arrOutput);
        $arrResult['host'] = $arrOutput[0];

        //- Obtengo el Default Gateway
        exec("/sbin/route -n", $arrOutput);
        if(is_array($arrOutput)) {
            foreach($arrOutput as $linea) {
                if(ereg("^0.0.0.0[[:space:]]+(([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3}))", $linea, $arrReg)) {
                    $arrResult['gateway'] = $arrReg[1];
                }
            }
        }
        return $arrResult;
    }

    /*  Procedimiento para escribir la configuracin de red del sistema en los archivos de configuracin, a partir
        del arreglo indicado en el par�etro. El arreglo indicado en el par�etro debe de tener los siguientes
        elementos:
            $arreglo["host"]        Nombre simbolico del sistema
            $arreglo["dns_ip_1"]    DNS primario de la maquina
            $arreglo["dns_ip_2"]    DNS secundario de la maquina
            $arreglo["gateway_ip"]  IP del gateway asociado a la interfaz externa
        La funcin devuelve VERDADERO en caso de �ito, FALSO en caso de error.
    */
    function escribir_configuracion_red_sistema($config_red) 
    {     
        $this->errMsg = ""; 
        $bValido=TRUE;
        $msg="";
     
        include_once("paloSantoConfig.class.php");
       
        exec("sudo -u root hostname " . $config_red['host']);
 
        //para modificar el archivo /etc/sysconfig/network-----------------------------------------------------
        $hostname=$config_red['host']; 
        $arr_archivos[]=array("dir"=>"/etc/sysconfig","file"=>"network","separador"=>"=",
                              "regexp"=>"[[:blank:]]*=[[:blank:]]*","reemplazos"=>array("HOSTNAME"=>$hostname, "GATEWAY" => $config_red["gateway_ip"]));
          
        $this->establecerDefaultGateway($config_red["gateway_ip"]);
 
        //para setear los dns en /etc/resolv.conf--------------------------------------------------------------
        $dns_ip_1 =$config_red['dns_ip_1'];
        $dns_ip_2 =$config_red['dns_ip_2'];
        $arr_resolv[]="nameserver $dns_ip_1";
//        if($dns_ip_2!="" && !is_null($dns_ip_2) && ip_validation($dns_ip_2,$msg)){
        if($dns_ip_2!="" && !is_null($dns_ip_2)){
            $arr_resolv[]="nameserver $dns_ip_2";
        }
               
        $arr_archivos[]=array("dir"=>"/etc","file"=>"resolv.conf","separador"=>" ",
                              "regexp"=>"[[:blank:]]*","reemplazos"=>$arr_resolv,"overwrite"=>TRUE);
            
        foreach($arr_archivos as $archivo) {
            $overwrite=FALSE;  //Si esta true escribe las lineas de arr_reemplazos directamente en el archivo, sin buscar las claves
            $oConf = new paloConfig($archivo['dir'],$archivo['file'],$archivo['separador'],$archivo['regexp']);
                       
            if(array_key_exists("overwrite",$archivo) && $archivo['overwrite']==TRUE) {
                $overwrite=TRUE;
            }
            $bool = $oConf->escribir_configuracion($archivo['reemplazos'], $overwrite);
            $bValido*=$bool;
        
            if(!$bool){
                $this->errMsg = $oConf->errMsg;
                break;
            }
        }
                      
        if ($bValido) {
            $comando = "sudo -u root service networkt restart";
            $mensaje = `$comando 2>&1`;
        }
                 
        return $bValido;
    }

    function escribirConfiguracionInterfaseRed($dev, $tipo, $ip="", $mask="")
    {
        //para modificar el archivo /etc/sysconfig/network-scripts/ifcfg-eth?
        $archivoEth = "ifcfg-$dev";

        if($tipo=="dhcp") {
            $arrReemplazos=array("DEVICE"=>$dev, "BOOTPROTO"=>"dhcp", "ONBOOT"=>"yes", 
                                 "TYPE"=>"Ethernet", "IPADDR"=>"", "NETMASK"=>"", "NOZEROCONF"=>"yes");
        } else if($tipo=="static") {

            $broadcast = $this->construir_ip_broadcast($ip, $mask);
            $network   = $this->construir_ip_red($ip, $mask);
            $arrReemplazos=array("DEVICE"=>$dev, "BOOTPROTO"=>"static", "ONBOOT"=>"yes", "TYPE"=>"Ethernet", "IPADDR"=>$ip, 
                                  "NETMASK"=>$mask, "BROADCAST"=>$broadcast, "NETWORK"=>$network, "NOZEROCONF"=>"yes");            
        } else {
            // No hago nada?
        }

        include_once("paloSantoConfig.class.php");        
        $oConf = new paloConfig("/etc/sysconfig/network-scripts", $archivoEth, "=", "[[:blank:]]*=[[:blank:]]*");
        $oConf->escribir_configuracion($arrReemplazos, false);

        exec("sudo -u root service network restart");

        // Luego de escribir la configuracion hay que hacer un ifconfig?
        return true;
    }

    // Esta funcion establece un nuevo default gateway
    function establecerDefaultGateway($ipGateway)
    {

        $ipCurrentDefaultGateway = "";

        // Verificar que sea un IP el $ipGateway

        // Primero obtengo el default gateway
        exec("/sbin/route -n", $arrOutput);
        if(is_array($arrOutput)) {
            foreach($arrOutput as $linea) {
                if(ereg("^0.0.0.0[[:space:]]+(([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3}))", $linea, $arrReg)) {
                    $ipCurrentDefaultGateway = $arrReg[1];
                    break;
                }
            }
        }

        // TODO: Validar que tambien sea un IP
        if(!empty($ipCurrentDefaultGateway)) {
            // Elimino el default gateway actual
            exec("sudo -u root route del -net default gw $ipCurrentDefaultGateway", $arrOutput, $varOutput);
            if($varOutput!=0) {
                // No se pudo eliminar el default gateway actual
                // No impido que la rutina prosiga porque puede ser que no exista y esto
                // no impide que pueda agregar el nuevo
            }
        }

        exec("sudo -u root route add -net default gw $ipGateway", $arrOutput, $varOutput);

    }

    function construir_ip_broadcast($ip, $mascara) 
    {
        $ip = explode(".", $ip);
        $mascara = explode(".", $mascara);
        for ($i = 0; $i < 4; $i++) $ip[$i] = ((int)$ip[$i]) | (~((int)$mascara[$i])& 0xFF);
        return implode(".", $ip); 
    } 

    function construir_ip_red($ip, $mascara) 
    {    
        $ip = explode(".", $ip);
        $mascara = explode(".", $mascara);
        for ($i = 0; $i < 4; $i++) $ip[$i] = (int)$ip[$i] & (int)$mascara[$i];    
        return implode(".", $ip); 
    }
}
?>
