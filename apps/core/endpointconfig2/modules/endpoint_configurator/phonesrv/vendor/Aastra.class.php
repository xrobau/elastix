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

class Aastra extends BaseVendorResource
{
    function handle($id_endpoint, $pathList)
    {
        if (count($pathList) <= 0) {
            header('HTTP/1.1 404 Not Found');
            print 'No '.get_class($this).' resource specified';
            return;
        }
        $service = (count($pathList) <= 0) ? 'index' : array_shift($pathList);
        if ($service == '') $service = 'index';
        switch ($service) {
        /*
        case 'logo.bmp':
            header('Content-Type: image/bmp');
            readfile(ELASTIX_BASE.'images/elastix.bmp');
            break;
        */
        default:
            $method = '_handle_'.$service;
            if (method_exists($this, $method)) {
                $this->$method($id_endpoint, $pathList);
            } else {
                header('HTTP/1.1 404 Not Found');
                print 'Unknown '.get_class($this).' resource specified';
            }
            break;
        }
    }
    
    private function _handle_index($id_endpoint, $pathList)
    {
    	$xml = new SimpleXMLElement('<?xml version="1.0" ?><AastraIPPhoneTextMenu/>');
        $xml->addChild('Title',  str_replace('&', '&amp;', _tr('Elastix services for Aastra')));
        
        foreach (array(
            'directory' => _tr('Phone directory'),
            'rssfeeds'  => _tr('RSS Feeds'),
            'help'      => _tr('Help')
            )
            as $k => $v) {
            $this->_aastraMenuItem($xml, $v, $k);
        }
        
        header('Content-Type: text/xml');
        print $xml->asXML();
    }

    private function _handle_help($id_endpoint, $pathList)
    {
    	$r = $this->listarCodigosFuncionalidades();
        if (!is_array($r)) {
        	Header('HTTP/1.1 501 Internal Server Error');
            print "Unable to read feature codes!";
            return;
        }
        
        $xml = new SimpleXMLElement('<?xml version="1.0" ?><AastraIPPhoneTextMenu/>');
        $xml->addChild('Title', str_replace('&', '&amp;', _tr('Help')));
        foreach ($r as $tupla) {
        	$this->_aastraMenuItem($xml, ($tupla['code']).' '.$tupla['description'], NULL, $tupla['code']);
        }
        
        header('Content-Type: text/xml');
        print $xml->asXML();
    }

    private function _handle_directory($id_endpoint, $pathList)
    {
        if (is_null($id_endpoint)) {
            header('HTTP/1.1 403 Forbidden');
            print 'Unauthorized for phonebook!';
            return;
        } 

        $typemap = array(
            'internal' => _tr('Internal'),
            'external' => _tr('External'));
        
        $xml = new SimpleXMLElement('<?xml version="1.0" ?><AastraIPPhoneTextMenu/>');

        if (count($pathList) <= 0) {
            // Listado de lo que hay disponible
            $xml->addChild('Title', str_replace('&', '&amp;', _tr('Phone Directory')));
            $this->_aastraMenuItem($xml, _tr('Internal'), 'directory/internal');
            $this->_aastraMenuItem($xml, _tr('External'), 'directory/external');
            $this->_aastraMenuItem($xml, _tr('Search - Internal'), 'directorysearch/internal');
            $this->_aastraMenuItem($xml, _tr('Search - External'), 'directorysearch/external');
        } else {
            $addressBookType = array_shift($pathList);
            if (!isset($typemap[$addressBookType])) {
                header('Location: '.$this->_baseurl.'/directory');
                return;
            }

            $userdata = $this->obtenerUsuarioElastix($id_endpoint);
            if (isset($_REQUEST['search']) && empty($_REQUEST['search'])) unset($_REQUEST['search']);
        
            $result = $this->listarAgendaElastix(
                is_null($userdata) ? NULL : $userdata['id_user'],
                $addressBookType,
                (isset($_REQUEST['search']) && trim($_REQUEST['search']) != '') ? trim($_REQUEST['search']) : NULL);
            if (!is_array($result['contacts'])) {
                Header(($result["fc"] == "DBERROR") 
                    ? 'HTTP/1.1 500 Internal Server Error' 
                    : 'HTTP/1.1 400 Bad Request');
                print $result['fm'].' - '.$result['fd'];
                return;
            }

            $xml->addChild('Title', str_replace('&', '&amp;', _tr('Phone Directory').' - '.$typemap[$addressBookType]));
            foreach ($result['contacts'] as $contact) {
                $nombre = $contact['name'];
                if (isset($contact['last_name'])) $nombre .= ' '.$contact['last_name'];
                $this->_aastraMenuItem($xml, $nombre, NULL, str_replace('&', '&amp;', $contact['work_phone']));
            }
        }

        header('Content-Type: text/xml');
        print $xml->asXML();
    }

    private function _handle_directorysearch($id_endpoint, $pathList)
    {
        if (is_null($id_endpoint)) {
            header('HTTP/1.1 403 Forbidden');
            print 'Unauthorized for phonebook!';
            return;
        } 

        $typemap = array(
            'internal' => _tr('Internal'),
            'external' => _tr('External'));
        if (count($pathList) <= 0)
            $addressBookType = NULL;
        else $addressBookType = array_shift($pathList);
        if (!isset($typemap[$addressBookType])) {
            header('Location: '.$this->_baseurl.'/directory');
            return;
        }
        
        $xml = new SimpleXMLElement('<?xml version="1.0" ?><AastraIPPhoneInputScreen/>');
        $xml->addAttribute('type', 'string');
        $xml->addChild('Title', str_replace('&', '&amp;', _tr('Search Phone Directory').' - '.$typemap[$addressBookType]));
        $xml->addChild('Prompt', str_replace('&', '&amp;', _tr('Enter text to search')));
        $xml->addChild('URL', str_replace('&', '&amp;', $this->_baseurl.'/directory/'.$addressBookType));
        $xml->addChild('Parameter', 'search');

        header('Content-Type: text/xml');
        print $xml->asXML();
    }

    private function _handle_rssfeeds($id_endpoint, $pathList)
    {
        $rssfeeds = $this->listarCanalesRSS();
        if (count($pathList) <= 0) {
            // Listar los RSS disponibles
            $xml = new SimpleXMLElement('<?xml version="1.0" ?><AastraIPPhoneTextMenu/>');
            $xml->addChild('Title', str_replace('&', '&amp;', _tr('RSS Feeds')));
            foreach ($rssfeeds as $k => $rssfeed) {
                $this->_aastraMenuItem($xml, $rssfeed[0], 'rssfeeds/'.$k);
            }
        } else {
            // Mostrar el contenido del RSS elegido
            $chosenfeed = array_shift($pathList);
            if (!isset($rssfeeds[$chosenfeed])) {
                header('Location: '.$this->_baseurl.'/rssfeeds');
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
                $xml = new SimpleXMLElement('<?xml version="1.0" ?><AastraIPPhoneFormattedTextScreen/>');
                $xml_header = $xml->addChild('Line', str_replace('&', '&amp;', $rssfeeds[$chosenfeed][0]));
                $xml_header->addAttribute('Size', 'double');
                $xml_header->addAttribute('Color', 'lightcyan');

                $xml_header = $xml->addChild('Line', str_replace('&', '&amp;', $infoRSS->channel['title'].' - '.$infoRSS->channel['link']));
                $xml_header->addAttribute('Size', 'small');
                $xml_header->addAttribute('Color', 'lightcyan');
                
                $xml_scroll = $xml->addChild('Scroll');
                for ($i = 0; $i < count($infoRSS->items); $i++) {
                    $rsstext = wordwrap(str_replace('&', '&amp;', date('Y.m.d', $infoRSS->items[$i]['date_timestamp']).' - '.$infoRSS->items[$i]['title']), 45);
                    $rsslines = explode("\n", $rsstext);
                    foreach ($rsslines as $s) {
                        $xml_line = $xml_scroll->addChild('Line', $s);
                        $xml_line->addAttribute('Size', 'normal');
                        $xml_line->addAttribute('Color', 'lightblue');
                    }
                    
                    $rsstext = wordwrap(str_replace('&amp;', '&', strip_tags($infoRSS->items[$i]['summary'])), 70);
                    $rsslines = explode("\n", $rsstext);
                    foreach ($rsslines as $s) {
                        $xml_line = $xml_scroll->addChild('Line', str_replace('&', '&amp;', $s));
                        $xml_line->addAttribute('Size', 'small');
                        $xml_line->addAttribute('Color', 'white');
                    }
                }
            }
        }

        header('Content-Type: text/xml');
        print $xml->asXML();
    }

    private function _aastraMenuItem($xml, $name, $url, $dial = NULL)
    {
        $xml_menuitem = $xml->addChild('MenuItem');
        $xml_menuitem->addChild('Prompt', str_replace('&', '&amp;', $name));
        if (!is_null($url)) {
            $xml_menuitem->addAttribute('base', $this->_baseurl.'/');
            $xml_menuitem->addChild('URI', str_replace('&', '&amp;', $url));	
        }
        if (!is_null($dial)) $xml_menuitem->addChild('Dial', str_replace('&', '&amp;', $dial));
    }
}
?>