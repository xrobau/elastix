<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0                                                  |
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
  $Id: dhcpconfig.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/


class EndpointLinksys extends Endpoint
{
    private $_http;
    
    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Linksys', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    /**
     * Configuration for Linksys endpoints (local):
     * 
     * A file called spaXXXXXXXXXXXX.cfg should be created with the XML 
     * configuration for the endpoint. The XXXXXXXXXXXX should be replaced with
     * the lowercase version of the MAC address of the endpoint. To reboot and
     * refresh the configuration, an HTTP GET request is performed to the URL:
     * http://{$IP}/admin/resync?{$SERVER_IP}/spaXXXXXXXXXXXX.cfg, again 
     * replacing XXXXXXXXXXXX with the MAC address.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Linksys_local_spa.tpl',
                array(
                    'SERVER_IP'         =>  $this->_serverip,
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                    'MAC_ADDRESS'       =>  $sLowerMAC,
                ),
                TFTP_DIR."/spa{$sLowerMAC}.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/spa{$sLowerMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Open connection to execute resync.
            // TODO: what, no authentication?
            $sURL = "http://{$this->_ip}/admin/resync?{$this->_serverip}/spa{$sLowerMAC}.cfg";
            $this->_log->output("DEBUG: connecting to $sURL ...");
            $this->_http = new AsyncHTTPClient($this->multiplex, $this);
            $st = $this->_http->GET($sURL);
            if ($st === TRUE) {
                $this->_log->output("DEBUG: requested $sURL, waiting for response...");
                $this->_step = 'HTTP_REBOOT';
            } else {
                $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'HTTP_REBOOT':
            if ($this->_http->getResponseComplete()) {
                if ($this->_http->getResponseCode() == '200') {
                    // Sets either success, or AMI_UNREGISTER state
                    $this->_unregister();
                } else {
                    $this->_log->output(
                        'ERR: failed to reboot phone - got response code '.
                        $this->_http->getResponseCode());
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
                $this->_http->finalizarConexion();
            } elseif ($bClosing) {
                $this->_log->output(
                    'ERR: failed to reboot phone - failed to get full response');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'AMI_UNREGISTER':
            if (is_array($oConn)) {
                $this->_localConfigStatus = isset($oConn['data'])
                    ? ENDPOINT_SUCCESS
                    : ENDPOINT_FAILURE; 
            }
            break;
        }        
    }
}
?>