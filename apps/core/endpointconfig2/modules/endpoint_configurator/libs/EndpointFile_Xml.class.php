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

class EndpointFile_Xml
{
    var $errMsg = NULL;
    
    function generarDescargaEndpoints($oEndpoints, $listaEndpoints)
    {
        Header('Content-Type: text/xml');
        Header('Content-Disposition: attachment; filename=endpoints.xml');
        
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(TRUE);
        $xml->setIndentString("\t");
        $xml->startDocument();
        $xml->startElement('endpoints');
        foreach ($listaEndpoints as $endpoint) {
            $xml->startElement('endpoint');
            
            $xml->writeElement('mac_address', $endpoint['mac_address']);
            $xml->writeElement('name_manufacturer', $endpoint['name_manufacturer']);
            $xml->writeElement('name_model', $endpoint['name_model']);
    
            $xml->startElement('endpoint_properties');
            foreach ($endpoint['properties'] as $k => $v) {
                $xml->startElement('property');
                $xml->writeElement('key', $k);
                $xml->writeElement('value', $v);
                $xml->endElement();
            }
            $xml->endElement(); // endpoint_properties
            
            $xml->startElement('accounts');
            foreach ($endpoint['accounts'] as $account) {
                $xml->startElement('account');
                $xml->writeElement('tech', $account['tech']);
                $xml->writeElement('accountname', $account['account']);
                $xml->writeElement('priority', $account['priority']);
                
                $xml->startElement('account_properties');
                foreach ($account['properties'] as $k => $v) {
                    $xml->startElement('property');
                    $xml->writeElement('key', $k);
                    $xml->writeElement('value', $v);
                    $xml->endElement();
                }
                $xml->endElement(); // account_properties
                
                $xml->endElement(); // account
            }
            $xml->endElement(); // accounts
            
            $xml->endElement(); // endpoint
            print $xml->flush();
        }
        $xml->endElement(); // endpoints
        $xml->endDocument();
        print $xml->flush();
    }

    function detectarFormato($path)
    {
        $fp = fopen($path, 'r');
        $s = fgets($fp);
        fclose($fp);
        
        return (strpos($s, '<?xml') === 0);
    }
    
    function parsearEndpoints($sTmpFile)
    {
        // Cargar el archivo y reportar los errores
        $prevval = libxml_use_internal_errors(true);
    	$xml = simplexml_load_file($sTmpFile);
        if ($xml === FALSE) {
            $s = "Malformed XML:\n";
            $levelmap = array(
                LIBXML_ERR_WARNING  =>  _tr('Warning'),
                LIBXML_ERR_ERROR    =>  _tr('Error'),
                LIBXML_ERR_FATAL    =>  _tr('Fatal error'),
            );
            foreach (libxml_get_errors() as $error) {
            	$s .= $levelmap[$error->level].' '.$error->code.': '.
                    _tr('Line').' '.$error->line.' '.
                    _tr('Column').' '.$error->column.': '.
                    trim($error->message)."\n";
            }
            $this->errMsg = $s;
            libxml_clear_errors();
            libxml_use_internal_errors($prevval);
        	return NULL;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prevval);
        
        // Validación de etiquetas
        if ($xml->getName() != 'endpoints') {
            $this->errMsg = _tr('Incorrect XML document type');
        	return NULL;
        }
        
        $endpoints = array();
        foreach ($xml->children() as $xml_endpoint) {
            $endpoint = array();
        	if ($xml_endpoint->getName() != 'endpoint') {
                $this->errMsg = _tr('Unrecognized element instead of endpoint').': '.$xml_endpoint->getName();
        		return NULL;
        	}
            
            // Elementos requeridos para todos los endpoints
            foreach (array('mac_address', 'name_manufacturer', 'name_model') as $k) {
            	if (!isset($xml_endpoint->$k)) {
                    $this->errMsg = _tr('Missing required element in endpoint').": $k";
            		return NULL;
            	}
                $endpoint[$k] = (string)($xml_endpoint->$k);                
            }
            $endpoint['source'] = _tr('Endpoint').' '.$endpoint['mac_address'];
            
            // Propiedades asignadas a este endpoint
            $endpoint['properties'] = array();
            if (isset($xml_endpoint->endpoint_properties)) {
            	foreach ($xml_endpoint->endpoint_properties->children() as $xml_property) {
            		if ($xml_property->getName() != 'property') {
                        $this->errMsg = _tr('Unrecognized element instead of property').': '.$xml_property->getName();
            			return NULL;
            		}
                    if (isset($xml_property->key) && isset($xml_property->value)) {
                    	$endpoint['properties'][(string)$xml_property->key] = (string)$xml_property->value;
                    }
            	}
            }

            // Cuentas asignadas a este endpoint
            $endpoint['accounts'] = array();
            if (isset($xml_endpoint->accounts)) {
            	foreach ($xml_endpoint->accounts->children() as $xml_account) {
                    if ($xml_account->getName() != 'account') {
                        $this->errMsg = _tr('Unrecognized element instead of account').': '.$xml_account->getName();
                        return NULL;
                    }
                    
                    $account = array('priority' => NULL, 'tech' => NULL);
                    if (!isset($xml_account->accountname)) {
                        $this->errMsg = _tr('Missing required element in account').": accountname";
                        return NULL;
                    }
                    foreach (array(
                        'accountname' => 'account',
                        'tech' => 'tech',
                        'priority' => 'priority') as $k1 => $k2) {
                        $account[$k2] = (string)($xml_account->$k1);                
                    }
                    
                    $account['properties'] = array();
                    if (isset($xml_account->account_properties)) {
                    	foreach ($xml_account->account_properties->children() as $xml_property) {
                            if ($xml_property->getName() != 'property') {
                                $this->errMsg = _tr('Unrecognized element instead of property').': '.$xml_property->getName();
                                return NULL;
                            }
                            if (isset($xml_property->key) && isset($xml_property->value)) {
                                $account['properties'][(string)$xml_property->key] = (string)$xml_property->value;
                            }
                    	}
                    }
                    
                    $endpoint['accounts'][] = $account;
            	}
            }

            $endpoints[] = $endpoint;
        }
        
        return $endpoints;
    }
}
?>