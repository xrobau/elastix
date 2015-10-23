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

require_once 'vendor/Grandstream.class.php';

// An Elastix LXP200 phone is a rebranded Grandstream 140x.
class Elastix extends Grandstream
{
    function handle($id_endpoint, $pathList)
    {
        if (count($pathList) <= 0) {
            header('HTTP/1.1 404 Not Found');
            print 'No '.get_class($this).' resource specified';
            return;
        }
        
        // Handle LXPx50 as special cases, delegate anything else
        $tupla = $this->leerInfoEndpoint($id_endpoint);
        if ($tupla['manufacturer_name'] == 'Elastix'&&
            in_array($tupla['model_name'], array('LXP150', 'LXP250'))) {
            $service = array_shift($pathList);
            switch ($service) {
                case 'int.xml':
                    $this->_handle_phonebook($id_endpoint, 'internal');
                    break;
                case 'ext.xml':
                    $this->_handle_phonebook($id_endpoint, 'external');
                    break;
                default:
                    header('HTTP/1.1 404 Not Found');
                    print 'Unknown '.get_class($this).' resource specified';
                    break;
            }
            return;
        }
        return parent::handle($id_endpoint, $pathList);
    }
    
    private function _handle_phonebook($id_endpoint, $addressBookType)
    {
        $userdata = $this->obtenerUsuarioElastix($id_endpoint);
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><Directory/>');

        $result = $this->listarAgendaElastix(is_null($userdata) ? NULL : $userdata['id_user'], $addressBookType);
        if (!is_array($result['contacts'])) {
            Header(($result["fc"] == "DBERROR")
            ? 'HTTP/1.1 500 Internal Server Error'
                : 'HTTP/1.1 400 Bad Request');
                print $result['fm'].' - '.$result['fd'];
                return;
        }
        
        $labels = array(
            'internal'  =>  array('Internal', 'Elastix Phonebook - Internal'),
            'external'  =>  array('External', 'Elastix Phonebook - External'),
        );

        $xml->addAttribute('Name', $labels[$addressBookType][1]);
        foreach ($result['contacts'] as $idx => $contact) {
            $xml_contact = $xml->addChild('Contact');
            $xml_contact->addAttribute('Id', $idx + 1);

            $xml_contact->addAttribute('Name', isset($contact['last_name'])
                ? str_replace('&', '&amp;', $contact['name']).' '.str_replace('&', '&amp;', $contact['last_name'])
                : str_replace('&', '&amp;', $contact['name']));
            
            $numlabels = array(
                'work_phone'    =>  'Office',
                'cell_phone'    =>  'Mobile',
                'home_phone'    =>  'Other');
            foreach ($numlabels as $k => $label) {
                if (!empty($contact[$k]))
                    $xml_contact->addAttribute($label, str_replace('&', '&amp;', $contact[$k]));
            }
        }
        
        header('Content-Type: text/xml');
        print $xml->asXML();
    }
}
?>