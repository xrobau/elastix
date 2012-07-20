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

class EndpointSnom extends Endpoint
{
    static protected $_global_serverip = NULL;

    private $_http;

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Snom', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
        if (is_null(self::$_global_serverip)) {
            self::$_global_serverip = $sServerIP;
        } elseif (self::$_global_serverip != $sServerIP) {
            $this->_log->output('WARN: global server IP is '.self::$_global_serverip.
                ' but endpoint '.$sIP.' requires server IP '.$sServerIP.
                ' - this endpoint might not work correctly.');
        }
    }
    
    /**
     * Configuration for Snom endpoints (global):
     * SIP global definition goes in /tftpboot/snom{300|320|360}.htm
     */
    public static function updateGlobalConfig()
    {
        // Fetch file contents, which will be repeated in three files
        $sOutput = self::_fetchSmartyTemplate(
            'Snom_global_3xx.tpl',
            array(
                'SERVER_IP'         => self::$_global_serverip,
            ));
        foreach (array('snom300.htm', 'snom320.htm', 'snom360.htm') as $sFileName) {
        	if (FALSE === file_put_contents(TFTP_DIR.'/'.$sFileName, $sOutput)) {
                $this->_log->output('ERR: failed to write '.$sFileName);
                return FALSE;
        	}
        }
        
        return TRUE;
    }

    /**
     * Configuration for Snom endpoints (local):
     * 
     * The file snomMMM-XXXXXXXXXXXX.htm contains the SIP configuration. Here 
     * XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone, and
     * MMM is replaced by the specific model of the phone.
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * sip notify reboot-snom {$EXTENSION}. Alternatively the phone can be 
     * rebooted by requesting the URL 
     * "http://{$this->_ip}/advanced_network.htm?reboot=Reboot".
     * Verified with Snom 300.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate UPPERCASE version of MAC address without colons
            $sUpperMAC = strtoupper(str_replace(':', '', $this->_mac));
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Snom_local_3xx.tpl',
                array(
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/snom{$this->_model}-{$sUpperMAC}.htm");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/snom{$this->_model}-{$sUpperMAC}.htm");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state
            if (!is_null($this->registered)) {
                $this->_amireboot('reboot-snom');
            } else {
                // reboot-snom does not work if phone is not registered.
                // Use HTTP reboot instead.
                $sURL = "http://{$this->_ip}/advanced_network.htm?reboot=Reboot";
                $this->_http = new AsyncHTTPClient($this->multiplex, $this);
                $st = $this->_http->GET($sURL);
                if ($st === TRUE) {
                    //$this->_log->output("DEBUG: requested $sURL, waiting for response...");
                    $this->_step = 'HTTP_REBOOT';
                } else {
                    $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            }
            break;
        case 'HTTP_REBOOT':
            if ($oConn !== $this->_http) break;

            if ($this->_http->getResponseComplete() || 
                ($this->_http->getResponseCode() >= 301 && $this->_http->getResponseCode() <= 399)) {                
                if ($this->_http->getResponseCode() == '302') {
                    // Sets either success, or AMI_UNREGISTER state
                    $this->_unregister();
                } else {
                    $this->_log->output(
                        'ERR: failed to reboot via HTTP - '.
                        'got response code '.$this->_http->getResponseCode());
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
                $this->_http->finalizarConexion();
                $this->_http = NULL;
            } elseif ($bClosing) {
                $this->_log->output(
                    'ERR: failed to reboot via HTTP - '.
                    'failed to get full response');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                $this->_http->finalizarConexion();
                $this->_http = NULL;
            }
            break;
        case 'AMI_REBOOT':
            if (is_array($oConn)) {
                // Sets either success, or AMI_UNREGISTER state
                $this->_unregister();
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