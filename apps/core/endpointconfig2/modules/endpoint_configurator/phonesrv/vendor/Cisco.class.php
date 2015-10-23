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

define ('CISCO_MAX_ENTRADAS_DIR', 32);

class Cisco extends BaseVendorResource
{
    function handle($id_endpoint, $pathList)
    {
    	if (count($pathList) <= 0) {
            header('HTTP/1.1 404 Not Found');
            print 'No Cisco resource specified';
    		return;
    	}
        $service = array_shift($pathList);
        switch ($service) {
        case 'logo.bmp':
            header('Content-Type: image/bmp');
            readfile(ELASTIX_BASE.'images/elastix.bmp');
            break;
        default:
            if (!$this->_checkSEP($id_endpoint)) {
                header('HTTP/1.1 403 Forbidden');
                print 'Invalid SEP';
                return;
            }

            $method = '_handle_'.$service;
            if (method_exists($this, $method)) {
            	$this->$method($id_endpoint, $pathList);
            } else {
                header('HTTP/1.1 404 Not Found');
                print 'Unknown Cisco resource specified';
            }
            break;
        }
    }
    
    private function _checkSEP(&$id_endpoint)
    {
        if (!isset($_GET['name'])) return FALSE;
        $regs = NULL;
        if (!preg_match('/^SEP(\w+)$/', $_GET['name'], $regs)) return FALSE;
        $mac_address = implode(':', str_split($regs[1], 2));
        $tupla = $this->_db->getFirstRowQuery(
            'SELECT id FROM endpoint WHERE mac_address = ?',
            TRUE, array($mac_address));
        if (!is_array($tupla) || count($tupla) <= 0) return FALSE;
        if (is_null($id_endpoint)) $id_endpoint = $tupla['id'];
        return ($id_endpoint == $tupla['id']);
    }
    
    private function _handle_services($id_endpoint, $pathList)
    {
        $xml = new CiscoIPPhoneMenu;
        $xml->setTitle(_tr('Elastix services for Cisco'))
            ->setPrompt(_tr('Please select one'));
        foreach (array(
            'directory' => _tr('Phone directory'),
            'rssfeeds'  => _tr('RSS Feeds'),
            'help'      => _tr('Help'))
            as $k => $v) {
            $xml->addMenuItem($v, $this->_baseurl.'/'.$k.'?name='.$_GET['name']);
        }
        $xml->output();
    }
    
    private function _handle_help($id_endpoint, $pathList)
    {
        $r = $this->listarCodigosFuncionalidades();
        if (!is_array($r)) {
            Header('HTTP/1.1 501 Internal Server Error');
            print "Unable to read feature codes!";
            return;
        }
        
        $numpaginas = (int)((count($r) + CISCO_MAX_ENTRADAS_DIR - 1) / CISCO_MAX_ENTRADAS_DIR);
        if (isset($_GET['page'])) {
        	if (!ctype_digit($_GET['page']) || $_GET['page'] >= $numpaginas)
                unset($_GET['page']);
        }
        
        if (!isset($_GET['page'])) {
            // Se elaboran tantos menús como sea requerido para cubrir todas las páginas
            $xml = new CiscoIPPhoneMenu;
            for ($i = 0; $i < $numpaginas; $i++) {
                $url = $this->_baseurl.'/help?name='.$_GET['name'].'&page='.$i;
                $xml->addMenuItem(_tr('Help').' - '._tr('Page').' '.($i + 1), $url);
            }
        } else {
            $xml = new CiscoIPPhoneDirectory;
            $xml->setTitle(_tr('Help'))
                ->setPrompt(_tr('Please select one'));
            $r = array_slice($r, $_GET['page'] * CISCO_MAX_ENTRADAS_DIR, CISCO_MAX_ENTRADAS_DIR);
            foreach ($r as $tupla) {
                $xml->addDirectoryEntry($tupla['description'], $tupla['code']);
            }
        }

        $xml->output();
    }

    private function _handle_directory($id_endpoint, $pathList)
    {
        $typemap = array(
            'internal' => _tr('Internal'),
            'external' => _tr('External'));
        
        $userdata = $this->obtenerUsuarioElastix($id_endpoint);

        if (isset($_GET['search']) && empty($_GET['search'])) unset($_GET['search']);
        
        if (count($pathList) <= 0) {
    		// Se elaboran tantos menús como sea requerido para cubrir todas las páginas
            $xml = new CiscoIPPhoneMenu;

            if (!isset($_GET['search'])) {
                $xml->setTitle(_tr('Phone Directory'))
                    ->addMenuItem(_tr('Search'), $this->_baseurl.'/directorysearch/?name='.$_GET['name']);
            } else {
                $xml->setTitle(_tr('Search Results'));
            }
            $xml->setPrompt(_tr('Please select one'));
            
            foreach ($typemap as $addressBookType => $v) {
                $result = $this->listarAgendaElastix(
                    is_null($userdata) ? NULL : $userdata['id_user'],
                    $addressBookType,
                    (isset($_GET['search']) && trim($_GET['search']) != '') ? trim($_GET['search']) : NULL);
                if (!is_array($result['contacts'])) {
                    Header(($result["fc"] == "DBERROR") 
                        ? 'HTTP/1.1 500 Internal Server Error' 
                        : 'HTTP/1.1 400 Bad Request');
                    print $result['fm'].' - '.$result['fd'];
                    return;
                }
                $total = count($result['contacts']);
                
                for ($offset = 0, $page = 1; $offset < $total; $offset += CISCO_MAX_ENTRADAS_DIR, $page++) {
                    $url = $this->_baseurl.'/directory/'.$addressBookType.'?name='.$_GET['name'].'&offset='.$offset;
                    if (isset($_GET['search'])) $url .= '&search='.urlencode($_GET['search']);
                    $xml->addMenuItem("$v - "._tr('Page')." $page", $url);
                }
            }
    	} else {
    		$addressBookType = array_shift($pathList);
            $offset = (isset($_GET['offset']) && ctype_digit($_GET['offset'])) ? (int)$_GET['offset'] : 0;
            $page = (int)($offset / CISCO_MAX_ENTRADAS_DIR) + 1;
            
            if (!isset($typemap[$addressBookType])) {
                header('Location: '.$this->_baseurl.'/directory?name='.$_GET['name']);
            	return;
            }
            $result = $this->listarAgendaElastix(
                is_null($userdata) ? NULL : $userdata['id_user'],
                $addressBookType,
                (isset($_GET['search']) && trim($_GET['search']) != '') ? trim($_GET['search']) : NULL);
            if (!is_array($result['contacts'])) {
                Header(($result["fc"] == "DBERROR") 
                    ? 'HTTP/1.1 500 Internal Server Error' 
                    : 'HTTP/1.1 400 Bad Request');
                print $result['fm'].' - '.$result['fd'];
                return;
            }

            // Listar resumen de contactos
            $xml = new CiscoIPPhoneDirectory;
            $xml->setTitle(_tr('Phone Directory').' - '.$typemap[$addressBookType].' - '._tr('Page')." $page")
                ->setPrompt(_tr('Please select one'));
            foreach ($result['contacts'] as $contact) {
                $xml->addDirectoryEntry(
                    $contact['name'].(isset($contact['last_name']) ? ' '.$contact['last_name'] : ''),
                    $contact['work_phone']);
            }
                
    	}

        $xml->output();
    }

    private function _filter_direntry_name(&$contact, $name)
    {
        $fullname = $contact['name'];
        if (isset($contact['last_name'])) {
            $fullname .= ' '.$contact['last_name'];
        }

        return (stripos($fullname, $name) !== FALSE);
    }
    

    private function _handle_directorysearch($id_endpoint, $pathList)
    {
        $xml = new CiscoIPPhoneInput;
        $xml->setTitle(_tr('Search Phone Directory'))
            ->setPrompt(_tr('Enter text to search'))
            ->setURL($this->_baseurl.'/directory?name='.$_GET['name'])
            ->addInputItem(_tr('Text'), 'search')
            ->output();
    }
    
    private function _ciscoMenuItem($xml, $name, $url)
    {
    	$xml_menuitem = $xml->addChild('MenuItem');
        $xml_menuitem->addChild('Name', str_replace('&', '&amp;', $name));
        $xml_menuitem->addChild('URL', str_replace('&', '&amp;', $url));
    }

    private function _handle_rssfeeds($id_endpoint, $pathList)
    {
        $rssfeeds = $this->listarCanalesRSS();
    	if (count($pathList) <= 0) {
    		// Listar los RSS disponibles
            $xml = new CiscoIPPhoneMenu;
            $xml->setTitle(_tr('RSS Feeds'))
                ->setPrompt(_tr('Please select an RSS Feed'));
            foreach ($rssfeeds as $k => $rssfeed) {
                $xml->addMenuItem($rssfeed[0], $this->_baseurl.'/rssfeeds/'.$k.'?name='.$_GET['name']);
            }
    	} else {
    		// Mostrar el contenido del RSS elegido
            $chosenfeed = array_shift($pathList);
            if (!isset($rssfeeds[$chosenfeed])) {
                header('Location: '.$this->_baseurl.'/rssfeeds?name='.$_GET['name']);
                return;
            }
            
            $sMensaje = NULL;
            $infoRSS = $this->leerCanalRSS($rssfeeds[$chosenfeed][1], $sMensaje);
            
            if (strpos($sMensaje, 'HTTP Error: connection failed') !== FALSE) {
                header("HTTP/1.1 500 Internal Server Error");
                print _tr('Could not get web server information. You may not have internet access or the web server is down');
                return;
            } elseif (strpos($sMensaje, '404 Not Found') !== FALSE) {
                header('HTTP/1.1 404 Not Found');
                print $sMensaje;
                return;
            } elseif (!is_object($infoRSS)) {
                header('HTTP/1.1 503 Internal Server Error');
                print $sMensaje;
                return;
            } else {
                $rsstext = $infoRSS->channel['title'].' - '.$infoRSS->channel['link']."\n-----------------------------\n";
                for ($i = 0; $i < count($infoRSS->items); $i++) {
                    $newitem = date('Y.m.d', $infoRSS->items[$i]['date_timestamp']).' - '.$infoRSS->items[$i]['title']."\n\n";
                    $newitem .= strip_tags($infoRSS->items[$i]['summary']);
                    $newitem .= "\n-----------------------------\n";
                    
                    // El Cisco 7960 se ahoga con un texto muy largo
                    if (strlen($rsstext) + strlen($newitem) > 14000) break;
                    $rsstext .= $newitem;
                }
                $rsstext = str_replace('&amp;', '&', $rsstext);
                $rsstext = str_replace("\r", '', $rsstext);

                $xml = new CiscoIPPhoneText;
                $xml->setTitle($rssfeeds[$chosenfeed][0])
                    ->setPrompt(_tr('RSS Feed'))
                    ->setText($rsstext);
            }
    	}

        $xml->output();
    }
}

class CiscoIPPhoneObject
{
	protected $_xml;

    function __construct($basetag)
    {
    	$this->_xml = new SimpleXMLElement('<?xml version="1.0" encoding="iso-8859-1" ?><'.$basetag.'/>');
    }
    
    protected function _addTextChild($xml, $t, $s)
    {
    	return $xml->addChild($t, str_replace('&', '&amp;',
            iconv('ISO-8859-1', 'UTF-8', iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s))));
    }
    
    function setTitle($s)
    {
        $this->_addTextChild($this->_xml, 'Title', $s);
        return $this;
    }
    
    function setPrompt($s)
    {
        $this->_addTextChild($this->_xml, 'Prompt', $s);
        return $this;
    }
    
    function output()
    {
        header('Content-Type: text/xml');
        print $this->_xml->asXML();
    }
}

class CiscoIPPhoneText extends CiscoIPPhoneObject
{
	function __construct()
    {
    	parent::__construct('CiscoIPPhoneText');
    }
    
    function setText($s)
    {
        $this->_addTextChild($this->_xml, 'Text', $s);
        return $this;
    }
}

class CiscoIPPhoneMenu extends CiscoIPPhoneObject
{
    function __construct()
    {
        parent::__construct('CiscoIPPhoneMenu');
    }
    
	function addMenuItem($name, $url)
    {
        $xml_menuitem = $this->_xml->addChild('MenuItem');
        $this->_addTextChild($xml_menuitem, 'Name', $name);
        $this->_addTextChild($xml_menuitem, 'URL', $url);
        return $this;
    }
}

class CiscoIPPhoneInput extends CiscoIPPhoneObject
{
    function __construct()
    {
        parent::__construct('CiscoIPPhoneInput');
    }

    function setURL($s)
    {
        $this->_addTextChild($this->_xml, 'URL', $s);
        return $this;
    }
    
    function addInputItem($displayname, $queryparam, $inputflags = NULL, $defaultvalue = NULL)
    {
        $xml_inputitem = $this->_xml->addChild('InputItem');
        $this->_addTextChild($xml_inputitem, 'DisplayName', $displayname);
        $this->_addTextChild($xml_inputitem, 'QueryStringParam', $queryparam);
        if (!is_null($inputflags))
            $this->_addTextChild($xml_inputitem, 'InputFlags', $inputflags);
        else $xml_inputitem->addChild('InputFlags');
        if (!is_null($defaultvalue))
            $this->_addTextChild($xml_inputitem, 'DefaultValue', $defaultvalue);
        else $xml_inputitem->addChild('DefaultValue');
        return $this;
    }
}

class CiscoIPPhoneDirectory extends CiscoIPPhoneObject
{
    function __construct()
    {
        parent::__construct('CiscoIPPhoneDirectory');
    }

    function addDirectoryEntry($name, $telephone)
    {
        $xml_direntry = $this->_xml->addChild('DirectoryEntry');
        $this->_addTextChild($xml_direntry, 'Name', $name);
        $this->_addTextChild($xml_direntry, 'Telephone', $telephone);
        return $this;
    }
}
?>