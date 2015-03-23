<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: Llamada.class.php,v 1.48 2009/03/26 13:46:58 alex Exp $ */

class ListaLlamadas implements IteratorAggregate
{
    private $_tuberia;
    private $_log;

    private $_llamadas = array();
    private $_indices = array(
        'id_llamada_saliente'   =>  array(),
        'id_llamada_entrante'   =>  array(),
        'dialstring'            =>  array(),
        'channel'               =>  array(),
        'actualchannel'         =>  array(),
        'uniqueid'              =>  array(),
        'actionid'              =>  array(),
        'auxchannel'            =>  array(),
    );

    function __construct($tuberia, $log)
    {
    	$this->_tuberia = $tuberia;
        $this->_log = $log;
    }
    
    function numLlamadas() { return count($this->_llamadas); }
    
    function nuevaLlamada($tipo_llamada)
    {
        if (!in_array($tipo_llamada, array('incoming', 'outgoing')))
            die(__METHOD__.' - tipo de llamada no implementado: '.$tipo_llamada);
    	$o = new Llamada($this, $tipo_llamada, $this->_tuberia, $this->_log);
        $this->_llamadas[] = $o;
        return $o;
    }
    
    function getIterator() {
        return new ArrayIterator($this->_llamadas);
    }
    
    function agregarIndice($sIndice, $key, Llamada $obj)
    {
        if (!isset($this->_indices[$sIndice]))
            die(__METHOD__.' - índice no implementado: '.$sIndice);
    	$this->_indices[$sIndice][$key] = $obj;
    }
    
    function removerIndice($sIndice, $key)
    {
        if (!isset($this->_indices[$sIndice]))
            die(__METHOD__.' - índice no implementado: '.$sIndice);
    	unset($this->_indices[$sIndice][$key]);
    }

    function buscar($sIndice, $key)
    {
        if (!isset($this->_indices[$sIndice]))
            die(__METHOD__.' - índice no implementado: '.$sIndice);
        return isset($this->_indices[$sIndice][$key]) 
            ? $this->_indices[$sIndice][$key] : NULL;
    }
    
    function remover(Llamada $obj)
    {
    	foreach (array_keys($this->_llamadas) as $k) {
    		if ($this->_llamadas[$k] === $obj) {
                unset($this->_llamadas[$k]);
                if (isset($this->_indices['id_llamada_saliente'][$obj->id_llamada]))
                    unset($this->_indices['id_llamada_saliente'][$obj->id_llamada]);
                if (isset($this->_indices['id_llamada_entrante'][$obj->id_llamada]))
                    unset($this->_indices['id_llamada_entrante'][$obj->id_llamada]);
                if (isset($this->_indices['dialstring'][$obj->dialstring]))
                    unset($this->_indices['dialstring'][$obj->dialstring]);
                if (isset($this->_indices['channel'][$obj->channel]))
                    unset($this->_indices['channel'][$obj->channel]);
                if (isset($this->_indices['actualchannel'][$obj->actualchannel]))
                    unset($this->_indices['actualchannel'][$obj->actualchannel]);
                if (isset($this->_indices['uniqueid'][$obj->uniqueid]))
                    unset($this->_indices['uniqueid'][$obj->uniqueid]);
                if (isset($this->_indices['actionid'][$obj->actionid]))
                    unset($this->_indices['actionid'][$obj->actionid]);
                    
                foreach (array_keys($obj->AuxChannels) as $k)
                    unset($this->_indices['auxchannel'][$k]);
    			break;
    		}
    	}
    }
    
    function dump($log)
    {
        foreach ($this->_llamadas as &$llamada) $llamada->dump($log);
        
    }
}
?>