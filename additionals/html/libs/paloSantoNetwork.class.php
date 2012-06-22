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
    
        $arrLineasDmesg = explode("\n", $str);
    
        foreach($arrLineasDmesg as $lineaDmesg) {
            //if(ereg("^(eth[[:digit:]]{1,3})", $lineaDmesg, $arrReg)) {
            //    echo $lineaDmesg;
            //}
            if(preg_match("/^(eth[[:digit:]]{1,3}):[[:space:]]+(.*)$/", $lineaDmesg, $arrReg) and preg_match("/ at /", $lineaDmesg)) {
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

        if(file_exists($fileIf))
        {
            if($fh = fopen($fileIf, "r")) {
                while(!feof($fh)) {
                    $lineaIfcfg = fgets($fh, 4048);
                    if(preg_match("/^BOOTPROTO[[:space:]]*=[[:space:]]*dhcp/", $lineaIfcfg)) {
                        $type = "dhcp";
                    }
                }
                fclose($fh);
            } else {
                // error
                $type = "";
            }
        }else $type = ""; //error

        return $type;
    }
 
    function obtener_interfases_red()
    {
    	$str = shell_exec("/sbin/ifconfig");
 
        $arrIfconfig = explode("\n", $str);
    
        $arrModelosInterfasesRed = $this->obtener_modelos_interfases_red();
 
        foreach($arrIfconfig as $lineaIfconfig) {
    
            unset($arrReg);
    
            if(preg_match("/^eth(([[:digit:]]{1,3})(:([[:digit:]]{1,3}))?)[[:space:]]+/", $lineaIfconfig, $arrReg)) {
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
    
            if(preg_match("/^(lo)[[:space:]]+/", $lineaIfconfig, $arrReg)) {
                    $interfaseActual = $arrReg[1];
                    $arrIf[$interfaseActual]["Name"] = "Loopback";
            }
    
            // debo tambien poder determinar cuando se termina una segmento de interfase
            // no solo cuando comienza como se hace en los dos parrafos anteriores
    	
            if(preg_match("/HWaddr ([ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:" .
                    "[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2}:[ABCDEF[:digit:]]{2})/", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["HWaddr"] = $arrReg[1];
            }
    
            if(preg_match("/^[[:space:]]+inet addr:([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3})/",
            $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["Inet Addr"] = $arrReg[1];
            }
    
            if(preg_match("/[[:space:]]+Mask:([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3})$/",
            $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["Mask"] = $arrReg[1];
            }
    
            // TODO: El siguiente patron de matching es muy simple, cambiar
            if(preg_match("/ RUNNING /", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["Running"] = "Yes";
            }
    
            if(preg_match("/^[[:space:]]+RX packets:([[:digit:]]{1,20})/", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["RX packets"] = $arrReg[1];
            }
    
            if(preg_match("/^[[:space:]]+RX bytes:([[:digit:]]{1,20})/", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["RX bytes"] = $arrReg[1];
            }
    
            if(preg_match("/^[[:space:]]+TX packets:([[:digit:]]{1,20})/", $lineaIfconfig, $arrReg)) {
                    $arrIf[$interfaseActual]["TX packets"] = $arrReg[1];
            }
    
            if(preg_match("/[[:space:]]+TX bytes:([[:digit:]]{1,20})/", $lineaIfconfig, $arrReg)) {
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
        if(preg_match("/^eth[[:digit:]]{1,3}$/", $nombreReal)) {
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
                if(preg_match("/^nameserver[[:space:]]+(.*)$/", $linea, $arrReg)) {
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
                if(preg_match("/^0.0.0.0[[:space:]]+(([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3}))/", $linea, $arrReg)) {
                    $arrResult['gateway'] = $arrReg[1];
                }
            }
        }
        return $arrResult;
    }

    /**
     * Procedimiento para escribir la configuracin de red del sistema en los 
     * archivos de configuración, a partir del arreglo indicado en el parámetro.
     * El arreglo indicado en el parámetro debe de tener los siguientes
     * elementos:
     *      $arreglo["host"]        Nombre simbolico del sistema
     *      $arreglo["dns_ip_1"]    DNS primario de la maquina
     *      $arreglo["dns_ip_2"]    DNS secundario de la maquina
     *      $arreglo["gateway_ip"]  IP del gateway asociado a la interfaz externa
     *  La función devuelve VERDADERO en caso de éxito, FALSO en caso de error.
     * 
     * @param   mixed   $config_red Nueva configuración deseada de la red
     * 
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function escribir_configuracion_red_sistema($config_red)
    {
        $this->errMsg = '';
    	$sComando = '/usr/bin/elastix-helper netconfig --genconf'.
            ' --host '.escapeshellarg($config_red['host']).
            ' --gateway '.escapeshellarg($config_red['gateway_ip']).
            ' --dns1 '.escapeshellarg($config_red['dns_ip_1']).
            ($config_red['dns_ip_2'] == '' ? '' : ' --dns2 '.escapeshellarg($config_red['dns_ip_2'])).
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
        	return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para escribir la configuración de red de una interfaz
     * Ethernet específica.
     * 
     * @param   string  $dev    Dispositivo de red a modificar: eth0
     * @param   string  $tipo   Una de las cadenas: static dhcp
     * @param   string  $ip     (opcional)  IP a asignar en caso static
     * @param   string  $mask   (opcional)  Máscara a asignar en caso static
     * 
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function escribirConfiguracionInterfaseRed($dev, $tipo, $ip="", $mask="")
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper netconfig --ifconf'.
            ' --device '.escapeshellarg($dev).
            ' --bootproto '.escapeshellarg($tipo).
            (($ip == '') ? '' : ' --ipaddr '.escapeshellarg($ip)).
            (($mask == '') ? '' : ' --netmask '.escapeshellarg($mask)).
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }
}
?>
