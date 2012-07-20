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

class EndpointCisco extends Endpoint
{
    static protected $_global_serverip = NULL;

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Cisco', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
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
     * Configuration for Cisco endpoints (global):
     * SIP global definition goes in /tftpboot/SIPDefault.cnf and has a 
     * reference to a firmware file P0S*.sb2. If there are several files, the
     * higher version is selected. 
     */
    public static function updateGlobalConfig()
    {
    	// Choose the firmware version to reference
        $sFirmwareVersion = NULL;
        foreach (glob(TFTP_DIR.'/P0S*.sb2') as $sPathName) {
        	$sVersion = basename($sPathName, '.sb2');
            if (is_null($sFirmwareVersion) || strcmp($sFirmwareVersion, $sVersion) < 0) { 
                $sFirmwareVersion = $sVersion;
            }
        }
        if (is_null($sFirmwareVersion)) {
            $this->_log->output('ERR: failed to find firmware file P0S*.sb2 in '.TFTP_DIR);
        	return FALSE;
        }

        // Write out SIPDefault.cnf to tftp directory
        if (FALSE === self::_writeSmartyTemplate(
            'Cisco_global_SIPDefault.tpl',
            array(
                'SERVER_IP'         => self::$_global_serverip,
                'FIRMWARE_VERSION'  =>  $sFirmwareVersion,
            ), 
            TFTP_DIR.'/SIPDefault.cnf')) {
            $this->_log->output('ERR: failed to write SIPDefault.cnf');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Configuration for Cisco endpoints (local):
     * 
     * The file SIPXXXXXXXXXXXX.cnf contains the SIP configuration. Here 
     * XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone.
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * sip notify cisco-check-cfg {$EXTENSION}. Verified with Cisco 7960.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate UPPERCASE version of MAC address without colons
            $sUpperMAC = strtoupper(str_replace(':', '', $this->_mac));
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Cisco_local_SIP.tpl',
                array(
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/SIP{$sUpperMAC}.cnf");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/SIP{$sUpperMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state
            if (is_null($this->registered)) {
                // Must execute cisco-check-cfg with extension, not IP
                // This probably won't work, but execute it anyway.
                $this->_log->output('WARN: endpoint is not registered, remote reboot will not work!');
            }
            $this->_amireboot('cisco-check-cfg');
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