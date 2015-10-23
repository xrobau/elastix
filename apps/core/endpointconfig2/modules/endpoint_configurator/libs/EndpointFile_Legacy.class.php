<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
  | http://www.elastix.com                                               |
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
  | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

class EndpointFile_Legacy
{
    var $errMsg = NULL;
    
    function generarDescargaEndpoints($oEndpoints, &$listaEndpoints)
    {
        Header('Content-Type: text/csv;charset=utf-8');
        Header('Content-Disposition: attachment; filename=endpoints.csv');
        
        // Imprimir cabeceras
        $fp = fopen('php://output', 'w');
        $keyOrder = array(
            'Vendor',
            'Model',
            'MAC',
            'Ext',
            'IP',
            'Mask',
            'GW',
            'DNS1',
            'Bridge',
            'Time_Zone',
            'DNS2',
        );
        fputcsv($fp, $keyOrder);
        
        foreach ($listaEndpoints as $endpoint) {
            $t = array(
                $endpoint['name_manufacturer'],
                $endpoint['name_model'],
                $endpoint['mac_address'],
    
                /* El formato viejo de descarga sólo puede representar una cuenta, y 
                 * no puede representar qué tecnología corresponde a la cuenta. */
                (count($endpoint['accounts']) > 0) ? $endpoint['accounts'][0]['account'] : '',
                
                '',   // IP
                '',   // máscara
                '',   // gateway
                '',   // DNS 1
                '',   // bridge?
                '',   // zona horaria
                '',   // DNS 2
            );
            
            /* Las características de red no son la IP actual, sino la IP que se 
             * desea que tenga el endpoint. Si el equipo se mantiene como DHCP, 
             * entonces todas las características de red quedan en blanco. */
            $offsets = array(
                'bridge'        => 8, // Se asume que es 1 o 0
                'timezone'      => 9, // TODO: formato unificado para todos los modelos
            );
            if (isset($endpoint['properties']['dhcp']) && !$endpoint['properties']['dhcp']) {
                $offsets = array_merge($offsets, array(
                    'static_ip'     => 4,
                    'static_mask'   => 5,
                    'static_gw'     => 6,
                    'static_dns1'   => 7,
                    'static_dns2'   => 10,
                ));
            }
            foreach ($offsets as $k => $v) {
                if (isset($endpoint['properties'][$k])) $t[$v] = $endpoint['properties'][$k];
            }
            
            // No hay manera de representar propiedades de la cuenta misma 
            
            fputcsv($fp, $t);
        }
    }

    function detectarFormato($path)
    {
        $fp = fopen($path, 'r');
        $s = fgets($fp);
        fclose($fp);
        
        return (strpos($s, 'Vendor') !== FALSE) 
            && (strpos($s, 'Model') !== FALSE)
            && (strpos($s, 'MAC') !== FALSE);
    }
    
    function parsearEndpoints($sTmpFile)
    {
        $fp = @fopen($sTmpFile, 'r');
        if (!$fp) {
            $this->errMsg = _tr('Failed to open file').': '.$sTmpFile;
        	return NULL;
        }
        
    	// Leer las columnas conocidas y registrar su posición en el archivo
        $keyCSV = array(
            'Vendor',
            'Model',
            'MAC',
            'Ext',
            'IP',
            'Mask',
            'GW',
            'DNS1',
            'Bridge',
            'Time_Zone',
            'DNS2',
        );
        $t = fgetcsv($fp);
        if (!is_array($t)) {
            $this->errMsg = _tr('Failed to read header');
            fclose($fp);
        	return NULL;
        }
        $cp = array();  // Posición de columna, dada la clave
        foreach ($t as $i => $k) {
        	if (in_array($k, $keyCSV)) $cp[$k] = $i;
        }
        if (!(isset($cp['Vendor']) && isset($cp['Model']) && isset($cp['MAC']) && isset($cp['Ext']))) {
            $this->errMsg = _tr('Invalid header - the following columns are required').' - Vendor Model MAC Ext';
        	fclose($fp);
            return NULL;
        }
        
        // Leer el resto de las filas
        $endpoints = array(); $line = 2;
        $valid = TRUE;
        while ($valid && ($t = fgetcsv($fp))) {
            // Ignorar líneas vacías o que empiezan con punto y coma
            if ((count($t) == 1 && trim($t[0]) == 0) || ($t[0][0] == ';')) {
            	$line++;
                continue;
            }
            
            // Los campos vacíos deben considerarse no seteados
            for ($i = 0; $i < count($t); $i++) {
            	if (trim($t[$i]) == '') $t[$i] = NULL;
            }
            
        	/*
            $endpoint = array(
                'mac_address'       =>  $t[$cp['MAC']],
                'name_manufacturer' =>  $t[$cp['Vendor']],
                'name_model'        =>  $t[$cp['Model']],
                'accounts'          =>  array(
                    array(
                        'account'   =>  $t[$cp['Ext']],
                        'tech'      =>  NULL,   // Este formato no graba la tecnología
                        'priority'  =>  1,
                        'properties'=>  array(),
                    ),
                ),
                'properties'        =>  array(
                    'static_ip'     => $t[$cp['IP']],
                    'static_mask'   => $t[$cp['Mask']],
                    'static_gw'     => $t[$cp['GW']],
                    'static_dns1'   => $t[$cp['DNS1']],
                    'static_dns2'   => $t[$cp['DNS2']],
                    'bridge'        => $t[$cp['Bridge']], // Se asume que es 1 o 0
                    'timezone'      => $t[$cp['Time_Zone']], // TODO: formato unificado para todos los modelos
                ),
            );
            */
            $endpoint = array(
                'source'    =>  _tr('Line')." $line",
            );
            if ($valid) {
                $propmap = array(
                    'MAC'   => 'mac_address',
                    'Vendor'=> 'name_manufacturer',
                    'Model' => 'name_model'
                );
                foreach ($propmap as $k1 => $k2) {
                    if (!isset($t[$cp[$k1]])) {
                        $this->errMsg = _tr('Line')." $line : "._tr('missing required field')." $k1";
                        $valid = FALSE;
                        break;
                    }
                    $endpoint[$k2] = $t[$cp[$k1]];
                }
            }
            
            // Lista de cuentas, sólo hay una. La cuenta es obligatoria sólo porque
            // la implementación anterior requería esta cuenta.
            if ($valid) {
            	if (!isset($t[$cp['Ext']])) {
                    $this->errMsg = _tr('Line')." $line : "._tr('missing required field')." Ext";
                    $valid = FALSE;
            	} else {
            		$endpoint['accounts'] = array(
                        array(
                            'account'   =>  $t[$cp['Ext']],
                            'tech'      =>  NULL,   // Este formato no graba la tecnología
                            'priority'  =>  1,
                            'properties'=>  array(),    // Este formato no representa propiedades
                        ),
                    );
            	}
            }

            // Lista de propiedades
            if ($valid) {
                $endpoint['properties'] = array();
                $propmap = array(
                    'IP'        =>  'static_ip',
                    'Mask'      =>  'static_mask',
                    'GW'        =>  'static_gw',
                    'DNS1'      =>  'static_dns1',
                    'DNS2'      =>  'static_dns2',
                    'Bridge'    =>  'bridge',
                    'Time_Zone' =>  'timezone',
                );
            	foreach ($propmap as $k1 => $k2) {
                    if (isset($cp[$k1]) && isset($t[$cp[$k1]])) {
                    	$endpoint['properties'][$k2] = $t[$cp[$k1]];
                    }
                }
                if (isset($endpoint['properties']['static_ip']))
                    $endpoint['properties']['dhcp'] = '0';
                
                $endpoints[] = $endpoint;
            }
            
            if (!$valid) $endpoints = NULL;
            $line++;
        }
        
        fclose($fp);
        return $endpoints;
    }
}
?>
