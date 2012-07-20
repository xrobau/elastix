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

class EndpointPolycom extends Endpoint
{
    static protected $_global_serverip = NULL;

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Polycom', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
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
     * Configuration for Polycom endpoints (global):
     *  Server definition goes in /tftpboot/server.cfg
     *  Sip global definition goes in /tftpboot/sip.cfg
     *  Requires directories /tftpboot/polycom/{logs,overrides,contacts}
     */
    public static function updateGlobalConfig()
    {
        // Ensure the required directory structure exists
        foreach (array(
            TFTP_DIR.'/polycom/logs',
            TFTP_DIR.'/polycom/overrides',
            TFTP_DIR.'/polycom/contacts',
        ) as $sDir) {
        	if (!is_dir($sDir)) {
        		if (!mkdir($sDir, 0777, TRUE)) {
                    $this->_log->output('ERR: failed to create directory: '.$sDir);
        			return FALSE;
        		}
        	}
        }
        
        // Write out server.cfg to tftp directory
        if (FALSE === self::_writeSmartyTemplate(
            'Polycom_global_server.tpl',
            array(
                'SERVER_IP' => self::$_global_serverip,
            ), 
            TFTP_DIR.'/server.cfg')) {
            $this->_log->output('ERR: failed to write server.cfg');
            return FALSE;
        }

        // Write out sip.cfg to tftp directory
        if (FALSE === self::_writeSmartyTemplate(
            'Polycom_global_sip.tpl',
            array(
                'SERVER_IP' => self::$_global_serverip,
            ), 
            TFTP_DIR.'/sip.cfg')) {
            $this->_log->output('ERR: failed to write sip.cfg');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Configuration for Polycom endpoints (local):
     * 
     * Two files should be created for each endpoint. The file XXXXXXXXXXXX.cfg
     * references server.cfg, sip.cfg, and the detailed configuration, which is
     * written to XXXXXXXXXXXXreg.cfg. Here XXXXXXXXXXXX is replaced by the
     * lowercase MAC address of the phone.
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * sip notify polycom-check-cfg {$IP}. Verified with IP 330.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Polycom_local_header.tpl',
                array(
                    'CONFIG_FILENAME'   =>  "{$sLowerMAC}reg.cfg",
                ),
                TFTP_DIR."/{$sLowerMAC}.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$sLowerMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Polycom_local_reg.tpl',
                array(
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/{$sLowerMAC}reg.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$sLowerMAC}reg.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state
            $this->_amireboot('polycom-check-cfg');
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