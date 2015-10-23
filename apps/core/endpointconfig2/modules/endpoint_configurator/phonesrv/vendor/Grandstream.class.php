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

require_once 'vendor/BaseVendorResource.class.php';

class Grandstream extends BaseVendorResource
{
    function handle($id_endpoint, $pathList)
    {
        if (count($pathList) <= 0) {
            header('HTTP/1.1 404 Not Found');
            print 'No '.get_class($this).' resource specified';
            return;
        }
        $service = array_shift($pathList);
        switch ($service) {
        case 'gs_phonebook.xml':
        case 'phonebook.xml':
            $this->_handle_phonebook($id_endpoint, $pathList);
            break;
        default:
            header('HTTP/1.1 404 Not Found');
            print 'Unknown '.get_class($this).' resource specified';
            break;
        }
    }

    // Fuente: http://www.grandstream.com/products/gxp_series/general/documents/gxp_wp_xml_phonebook.pdf
    private function _handle_phonebook($id_endpoint)
    {
        if (is_null($id_endpoint)) {
            header('HTTP/1.1 403 Forbidden');
            print 'Unauthorized for phonebook!';
            return;
        } 

        $typemap = array('internal', 'external');
        $userdata = $this->obtenerUsuarioElastix($id_endpoint);
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><AddressBook/>');
        
        // GXV3140 aparentemente requiere esto
        $xml->addChild('version', 1);

        foreach ($typemap as $addressBookType) {
            $result = $this->listarAgendaElastix(is_null($userdata) ? NULL : $userdata['id_user'], $addressBookType);
            if (!is_array($result['contacts'])) {
                Header(($result["fc"] == "DBERROR") 
                    ? 'HTTP/1.1 500 Internal Server Error' 
                    : 'HTTP/1.1 400 Bad Request');
                print $result['fm'].' - '.$result['fd'];
                return;
            }
            
            foreach ($result['contacts'] as $contact) {
                $xml_contact = $xml->addChild('Contact');
                // LastName y FirstName deben estar presentes, incluso si vacíos
                if (isset($contact['last_name'])) {
                    $xml_contact->addChild('FirstName', str_replace('&', '&amp;', $contact['name']));
                    $xml_contact->addChild('LastName', str_replace('&', '&amp;', $contact['last_name']));
                } else {
                    $xml_contact->addChild('FirstName');
                	$xml_contact->addChild('LastName', str_replace('&', '&amp;', $contact['name']));
                }
                
                $i = 0;
                foreach (array('work_phone', 'cell_phone', 'home_phone') as $k) {
                    if (!empty($contact[$k])) {
                        $xml_phone = $xml_contact->addChild('Phone');
                        $xml_phone->addChild('phonenumber', str_replace('&', '&amp;', $contact[$k]));
                        $xml_phone->addChild('accountindex', $i);
                        $i++;
                    }
                }
                
                // GXV3140: No hay todavía de dónde sacar grupos
                //$xml_contact->addChild('Group', 0);
            }
            
            // TODO: GXP2200 tiene más campos
            // http://www.grandstream.com/products/gxp_series/gxp2200/documents/gxp2200_xml_phonebook_guide.pdf
        }
    
        header('Content-Type: text/xml');
        print $xml->asXML();
    }
}
?>