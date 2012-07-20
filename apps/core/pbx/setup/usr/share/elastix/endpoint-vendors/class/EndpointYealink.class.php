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

class EndpointYealink extends Endpoint
{
    private $_enableDHCP = TRUE;
    private $_enableBridge = TRUE;
    private $_staticIP = NULL;
    private $_staticMask = NULL;
    private $_staticGW = NULL;
    private $_staticDNS1 = NULL;
    private $_staticDNS2 = NULL;
    private $_timeZone = -5;

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Yealink', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    public function setModel($sModel)
    {
        if (in_array($sModel, array('VP530', 'SIP-T20/T20P', 'SIP-T22/T22P', 'SIP-T26/T26P', 'SIP-T28/T28P'))) {
            $this->_model = $sModel;
            return TRUE;
        }
        return FALSE;
    }

    public function setExtraParameters($param)
    {
        // Ignore extra parameters for unsupported models
        if (!in_array($this->_model, array('SIP-T20/T20P', 'SIP-T22/T22P', 'SIP-T26/T26P', 'SIP-T28/T28P')))
            return TRUE;

        if (isset($param['Bridge'])) $this->_enableBridge = ($param['Bridge'] != 0);
        if (isset($param['Time_Zone'])) $this->_timeZone = $param['Time_Zone'];
        if (isset($param['By_DHCP'])) {
            $this->_enableDHCP = ($param['By_DHCP'] != 0);
            if (!$this->_enableDHCP) {
                // Unset empty values
                foreach (array('IP', 'Mask', 'GW', 'DNS1', 'DNS2') as $k) {
                    if (isset($param[$k]) && trim($param[$k]) == '')
                        unset($param[$k]); 
                }

                // The following must be present
                foreach (array('IP', 'Mask') as $k) {
                    if (!isset($param[$k])) {
                        $this->_log->output('ERR: required extra field not assigned: '.$k);
                        return FALSE;
                    }
                }
                
                // All of these must be valid IPs
                foreach (array('IP', 'Mask', 'GW', 'DNS1', 'DNS2') as $k) {
                    if (isset($param[$k])) {
                        if ($param[$k] == '0.0.0.0' || 
                            !preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $param[$k])) {
                            $this->_log->output(
                                'ERR: the following extra field is not a valid IPv4 address: '.
                                $k.'='.$param[$k]);
                            return FALSE;
                        }
                        $sField = '_static'.$k;
                        $this->$sField = $param[$k];
                    }
                }
            }
        }
        return TRUE;
    }
    
    /**
     * Configuration for Yealink endpoints (local):
     * 
     * The file XXXXXXXXXXXX.cfg contains the SIP configuration. Here 
     * XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone. The 
     * file format is different for the SIP-T2x and the VP530, and the 
     * difference is accounted for in the templates.
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

            // Select template based on the phone model
            switch ($this->_model) {
            case 'SIP-T20/T20P':
            case 'SIP-T22/T22P':
            case 'SIP-T26/T26P':
            case 'SIP-T28/T28P':
                $sTemplate = 'Yealink_local_SIP-T2x.tpl';
                break;
            default:
                $sTemplate = 'Yealink_local_VP530.tpl';
                break;
            }
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                $sTemplate,
                array(
                    'SERVER_IP'         =>  $this->_serverip,
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                    
                    'ENABLE_DHCP'       =>  $this->_enableDHCP ? 1 : 0,
                    'STATIC_IP'         =>  $this->_staticIP,
                    'STATIC_MASK'       =>  $this->_staticMask,
                    'STATIC_GATEWAY'    =>  $this->_staticGW,
                    'STATIC_DNS1'       =>  $this->_staticDNS1,
                    'STATIC_DNS2'       =>  $this->_staticDNS2,
                    'TIME_ZONE'         =>  $this->_timeZone,
                    'ENABLE_BRIDGE'     =>  $this->_enableBridge ? 1 : 0,
                ),
                TFTP_DIR."/{$sLowerMAC}.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$sLowerMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state
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