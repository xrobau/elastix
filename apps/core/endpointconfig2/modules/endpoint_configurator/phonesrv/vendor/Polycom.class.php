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

class Polycom extends BaseVendorResource
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
        case 'logo.bmp':
            header('Content-Type: image/bmp');
            readfile(ELASTIX_BASE.'images/elastix.bmp');
            break;
        default:
            $method = '_handle_'.$service;
            if (method_exists($this, $method)) {
                $smarty = getSmarty('default');
                $smarty->template_dir = dirname($_SERVER['SCRIPT_FILENAME']).'/tpl';
                $smarty->assign('baseurl', $this->_baseurl);
                $this->$method($id_endpoint, $pathList, $smarty);
            } else {
                header('HTTP/1.1 404 Not Found');
                print 'Unknown '.get_class($this).' resource specified';
            }
            break;
        }
    }
    
    private function _handle_idle($id_endpoint, $pathList, $smarty)
    {
        // TODO: esta pantalla podría mostrar mucha más información sobre usuario
        $smarty->assign(array(
            'title' =>  _tr('Polycom for Elastix'),
        ));
        $smarty->display('Polycom_idle.tpl');
    }

    private function _handle_index($id_endpoint, $pathList, $smarty)
    {
        $smarty->assign(array(
            'title'             =>  _tr('Elastix services for Polycom'),
            'tag_directory'     =>  _tr('Phone directory'),
            'tag_rss'           =>  _tr('RSS Feeds'),
        ));
        $smarty->display('Polycom_index.tpl');
    }

    private function _handle_directory($id_endpoint, $pathList, $smarty)
    {
        if (is_null($id_endpoint)) {
            header('HTTP/1.1 403 Forbidden');
            print 'Unauthorized for phonebook!';
            return;
        } 

        $typemap = array(
            'internal' => _tr('Internal'),
            'external' => _tr('External'));
        
        if (count($pathList) <= 0) {
            // Listado de lo que hay disponible
            $smarty->assign(array(
                'title'             =>  _tr('Phone Directory'),
                'tag_internal'      =>  _tr('Internal'),
                'tag_external'      =>  _tr('External'),
                'search_internal'   =>  _tr('Search - Internal'),
                'search_external'   =>  _tr('Search - External'),
            ));
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
            $smarty->assign(array(
                'title'     =>  _tr('Phone Directory').' - '.$typemap[$addressBookType],
                'contacts'  =>  $result['contacts'],
            ));
        }
        $smarty->display('Polycom_directory.tpl');
    }

    private function _handle_directorysearch($id_endpoint, $pathList, $smarty)
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
        $smarty->assign(array(
            'title'         =>  _tr('Search Phone Directory').' - '.$typemap[$addressBookType],
            'directorytype' =>  $addressBookType,
            'tag_submit'    =>  _tr('Search'),
        ));
        $smarty->display('Polycom_directorysearch.tpl');        
    }

    private function _handle_rssfeeds($id_endpoint, $pathList, $smarty)
    {
        if (is_null($id_endpoint)) {
            header('HTTP/1.1 403 Forbidden');
            print 'Unauthorized for RSS feeds!';
            return;
        } 

        $rssfeeds = $this->listarCanalesRSS();
        if (count($pathList) <= 0) {
            // Listar los RSS disponibles
            $smarty->assign(array(
                'title'     =>  _tr('RSS Feeds'),
                'rssfeeds'  =>  $rssfeeds,
            ));
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
            } elseif (!is_object($infoRSS)) {
                header('HTTP/1.1 503 Internal Server Error');
                print $sMensaje;
                return;
            } else {
                $smarty->assign(array(
                    'title'     =>  $rssfeeds[$chosenfeed][0],
                    'rsstitle'  =>  $infoRSS->channel['title'],
                    'rsslink'   =>  $infoRSS->channel['link'],
                    'rssitems'  =>  $infoRSS->items,
                ));
            }
        }
        $smarty->display('Polycom_rssfeeds.tpl');
    }
}
?>