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

class EndpointAastra extends Endpoint
{
    private $_http;
    private $_http_username = NULL;
    private $_http_password = NULL;
    
    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Aastra', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
        $this->_http_username = 'admin';
        $this->_http_password = '22222';
    }

    /**
     * Configuration for Aastra endpoints (global):
     * SIP global definition goes in /tftpboot/aastra.cfg. Even though its 
     * contents are very similar to the per-phone config, and it also defines
     * a SIP server, this file must exist and have a "valid" (even if redundant)
     * configuration, or the phone will refuse to boot.
     */
    public static function updateGlobalConfig()
    {
        // Write out aastra.cfg to tftp directory
        if (FALSE === self::_writeSmartyTemplate(
            'Aastra_global_cfg.tpl',
            array(
                'SERVER_IP'         => '0.0.0.0', //self::$_global_serverip,
            ), 
            TFTP_DIR.'/aastra.cfg')) {
            $this->_log->output('ERR: failed to write aastra.cfg');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Configuration for Aastra endpoints (local):
     * 
     * The file XXXXXXXXXXXX.cfg contains the plaintext SIP configuration. Here
     *  XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone.
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * sip notify aastra-check-cfg {$IP}. Verified with Aastra 57i and 6757i.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate UPPERCASE version of MAC address without colons
            $sUpperMAC = strtoupper(str_replace(':', '', $this->_mac));
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Aastra_local_cfg.tpl',
                array(
                    'SERVER_IP'         =>  $this->_serverip,
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/{$sUpperMAC}.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$sUpperMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state
            $this->_amireboot('aastra-check-cfg');
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