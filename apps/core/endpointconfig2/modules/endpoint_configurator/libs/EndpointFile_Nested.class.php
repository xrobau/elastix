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

class EndpointFile_Nested
{
    var $errMsg = NULL;
    
    function generarDescargaEndpoints($oEndpoints, &$listaEndpoints)
    {
        Header('Content-Type: text/csv;charset=utf-8');
        Header('Content-Disposition: attachment; filename=endpoints.csv');
        
        $fp = fopen('php://output', 'w');

        foreach ($listaEndpoints as $endpoint) {
            fputcsv($fp, array(
                $endpoint['mac_address'],
                $endpoint['name_manufacturer'],
                $endpoint['name_model']
            ));

            foreach ($endpoint['properties'] as $k => $v) {
                fputcsv($fp, array('', $k, $v));
            }
            if (count($endpoint['accounts']) > 0) {
                foreach ($endpoint['accounts'] as $account) {
                    fputcsv($fp, array('', '', $account['tech'], $account['account'], $account['priority']));
                    foreach ($account['properties'] as $k => $v) {
                        fputcsv($fp, array('', '', '', $k, $v));
                    }
                }
            }
        }
    }
    
    function detectarFormato($path)
    {
    	$fp = fopen($path, 'r');
        $s = fgets($fp);
        fclose($fp);
        
        return (preg_match('/^([[:xdigit:]]{2}:){5}[[:xdigit:]]{2},/', $s));
    }


    function parsearEndpoints($sTmpFile)
    {
        $fp = @fopen($sTmpFile, 'r');
        if (!$fp) {
            $this->errMsg = _tr('Failed to open file').': '.$sTmpFile;
            return NULL;
        }

        $endpoints = array(); $line = 1;        
        $valid = TRUE;
        $idxEndpoint = -1;
        $idxAccount = -1;
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
            
            /* Para este formato de archivo, si la primera columna está seteada,
             * se inicia el parseo de un nuevo endpoint. Si la primera columna
             * está vacía pero la segunda está seteada, es una definición de
             * propiedad para el endpoint actual. Si se setea desde la tercera
             * columna, es una definición de cuenta para el endpoint actual. Si
             * se setea desde la cuarta columna, es una propiedad de la cuenta
             * actual del endpoint actual. */
            if (!is_null($t[0])) {
            	// Inicia el parseo de un nuevo endpoint
                $idxEndpoint++;
                $idxAccount = -1;
                
                $endpoints[$idxEndpoint] = array(
                    'mac_address'   =>  trim($t[0]),
                    'properties'    =>  array(),
                    'accounts'      =>  array(),
                    'source'        =>  _tr('Line')." $line",
                );
                if (is_null($t[1])) {
                    $this->errMsg = _tr('Line')." $line : "._tr('missing required field')." Vendor";
                    $valid = FALSE;
                } elseif (is_null($t[2])) {
                    $this->errMsg = _tr('Line')." $line : "._tr('missing required field')." Model";
                    $valid = FALSE;
                } else {
                	$endpoints[$idxEndpoint]['name_manufacturer'] = trim($t[1]);
                    $endpoints[$idxEndpoint]['name_model'] = trim($t[2]);
                }
            } elseif (!is_null($t[1])) {
            	// Inicia el parseo de una nueva propiedad
                if ($idxEndpoint < 0) {
                    $this->errMsg = _tr('Line')." $line : "._tr('property without endpoint');
                	$valid = FALSE;
                } elseif (!is_null($t[2])) {
                    $endpoints[$idxEndpoint]['properties'][trim($t[1])] = trim($t[2]);
                }
            } elseif (!is_null($t[2])) {
            	// Inicia el parseo de una nueva cuenta
                if ($idxEndpoint < 0) {
                    $this->errMsg = _tr('Line')." $line : "._tr('account without endpoint');
                    $valid = FALSE;
                } elseif (is_null($t[3])) {
                    $this->errMsg = _tr('Line')." $line : "._tr('missing required field')." Account";
                    $valid = FALSE;
                } elseif (is_null($t[4])) {
                    $this->errMsg = _tr('Line')." $line : "._tr('missing required field')." Priority";
                    $valid = FALSE;
                } else {
                	$idxAccount++;
                    $endpoints[$idxEndpoint]['accounts'][$idxAccount] = array(
                        'tech'          =>  trim($t[2]),
                        'account'       =>  trim($t[3]),
                        'priority'      =>  trim($t[4]),
                        'properties'    =>  array(),
                    );
                }
            } elseif (!is_null($t[3])) {
            	// Inicia el parseo de una propiedad de la cuenta
                if ($idxEndpoint < 0) {
                    $this->errMsg = _tr('Line')." $line : "._tr('account property without endpoint');
                    $valid = FALSE;
                } elseif ($idxAccount < 0) {
                    $this->errMsg = _tr('Line')." $line : "._tr('account property without account');
                    $valid = FALSE;
                } elseif (!is_null($t[4])) {
                    $endpoints[$idxEndpoint]['accounts'][$idxAccount]['properties'][trim($t[4])] = trim($t[5]);
                }
            } else {
                $this->errMsg = _tr('Line')." $line : "._tr('invalid indentation');
            	$valid = FALSE;
            }
            
            if (!$valid) $endpoints = NULL;
            $line++;
        }
        
        fclose($fp);
        return $endpoints;
    }
}
?>