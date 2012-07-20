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

class EndpointXorcom extends Endpoint
{
    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Xorcom', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    public function setModel($sModel)
    {
        if (in_array($sModel, array('XP0120P', 'XP0100P'))) {
            $this->_model = $sModel;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Configuration for Xorcom endpoints (local):
     * 
     * The file XXXXXXXXXXXX.cfg  contains the SIP configuration.
     * Here XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * sip notify reboot-yealink {$IP}
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));

            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Xorcom_local_cfg.tpl',
                array(
                    'SERVER_IP'         =>  $this->_serverip,
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/{$sLowerMAC}.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$sLowerMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state.
            // Yes, reboot-yealink also reboots Xorcom phones.
            $this->_amireboot('reboot-yealink');
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