<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
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
  | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

require_once 'vendor/BaseVendorResource.class.php';
require_once ELASTIX_BASE.'modules/address_book/libs/core.class.php';

class Yealink extends BaseVendorResource
{
    function handle($id_endpoint, $pathList)
    {
        if (count($pathList) <= 0) {
            header('HTTP/1.1 404 Not Found');
            print 'No '.get_class($this).' resource specified';
            return;
        }
        $service = (count($pathList) <= 0) ? '' : array_shift($pathList);
        switch ($service) {
        case 'internal':
        case 'external':
            $this->_handle_phonebook($id_endpoint, $service, $pathList);
            break;
        case '':
            $this->_handle_index($id_endpoint, $pathList);
            break;
        default:
            header('HTTP/1.1 404 Not Found');
            print 'Unknown '.get_class($this).' resource specified';
            break;
        }
    }

    private function _handle_index($id_endpoint, $pathList)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><ElastixIPPhoneDirectory/>');
        $xml->addChild('Title', str_replace('&', '&amp;', _tr('Phone Directory')));
        $xml->addChild('Prompt', str_replace('&', '&amp;', _tr('Please select one')));

        foreach (array('1' => 'internal', '2' => 'external') as $softkey => $addressBookType) {
        	$xml_keyitem = $xml->addChild('SoftKeyItem');
            $xml_keyitem->addChild('Name', $softkey);
            $xml_keyitem->addChild('URL', $this->_baseurl.'/'.$addressBookType.construirURL());
        }
    
        header('Content-Type: text/xml');
        print $xml->asXML();
    }
    
    private function _handle_phonebook($id_endpoint, $addressBookType, $pathList)
    {
        $typemap = array(
            'internal' => _tr('Internal'),
            'external' => _tr('External'));

        $userdata = $this->obtenerUsuarioElastix($id_endpoint);
        if (is_null($userdata)) {
            header('HTTP/1.1 403 Forbidden');
            print 'Unauthorized for phonebook!';
            return;
        } 
        else $_SERVER['PHP_AUTH_USER'] = $userdata['name_user'];
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><ElastixIPPhoneDirectory/>');
        $xml->addChild('Title', str_replace('&', '&amp;', _tr('Phone Directory').' - '.$typemap[$addressBookType]));
        $xml->addChild('Prompt', str_replace('&', '&amp;', _tr('Please select one')));
    	
        $pCore_AddressBook = new core_AddressBook();
        $result = $pCore_AddressBook->listAddressBook($addressBookType, NULL, NULL, NULL);
        if (!is_array($result)) {
            $error = $pCore_AddressBook->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            print $error['fm'].' - '.$error['fd'];
            return;
        }
        
        foreach ($result['extension'] as $contact) {
            $fullname = $contact['name'];
            if (isset($contact['last_name'])) {
                $fullname .= ' '.$contact['last_name'];
            }

            if (!isset($_GET['name']) || trim($_GET['name']) == '' || stripos($fullname, $_GET['name']) !== FALSE) {
                $xml_contact = $xml->addChild('DirectoryEntry');
                $xml_contact->addChild('Name', str_replace('&', '&amp;', $fullname));
                
                foreach (array('work_phone', 'cell_phone', 'home_phone') as $k) {
                    if (!empty($contact[$k])) {
                        $xml_phone = $xml_contact->addChild('Telephone', str_replace('&', '&amp;', $contact[$k]));
                    }
                }
            }
        }
    
        header('Content-Type: text/xml');
        print $xml->asXML();
    }
}
?>