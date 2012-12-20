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

class EndpointGrandstream extends Endpoint
{
    private $_telnet;
    private $_telnet_username = NULL;
    private $_telnet_password = 'admin';

    private $_enableDHCP = TRUE;
    private $_staticIP = NULL;
    private $_staticMask = NULL;
    private $_staticGW = '0.0.0.0';
    private $_staticDNS1 = '0.0.0.0';
    private $_staticDNS2 = '0.0.0.0';
    private $_timeZone = 'auto';

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Grandstream', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    public function setModel($sModel)
    {
        if (in_array($sModel, array(
            // Tested models
            'GXP280', 'GXV3140', 'GXV3175', 'GXP2120', 'BT200',
            // Tested by Sergio
            'GXP2100', 'GXP1405',
            // Untested models 
            'GXP2000', 'GXP2020','HT386',
            ))) {
            $this->_model = $sModel;
            return TRUE;
        }
        return FALSE;
    }

    public function setExtraParameters($param)
    {
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
     * Configuration for Grandstream endpoints (local):
     * 
     * The file cfgXXXXXXXXXXXX contains the SIP configuration. Here 
     * XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone. 
     * Grandstream is special in that the file is not text but a binary 
     * encoding, which is generated by _encodeGrandstreamConfig().
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * For GXP280,GXV3140,GXV3175: "sip notify cisco-check-cfg {$EXTENSION}"
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
            
            // Choose which template to use based on model
            switch ($this->_model) {
            case 'GXV3140':
                $sTemplate = 'Grandstream_local_GXV3140.tpl';
                break;
            case 'GXP2120':
            case 'GXV3175':
            default:
                $sTemplate = 'Grandstream_local_GXP2120.tpl';
                break;
            }

            // Assign Smarty variables and write out the file
            $r = file_put_contents(TFTP_DIR."/cfg{$sLowerMAC}",
                $this->_encodeGrandstreamConfig($sLowerMAC, 
                    self::_fetchSmartyTemplate($sTemplate, array(
                        'SERVER_IP'         =>  $this->_serverip,
                        'ID_DEVICE'         =>  $this->_device_id,
                        'SECRET'            =>  $this->_secret,
                        'DISPLAY_NAME'      =>  $this->_desc,

                        'ENABLE_DHCP'       =>  $this->_enableDHCP ? 1 : 0,
                        'STATIC_IP'         =>  explode('.', $this->_staticIP),
                        'STATIC_MASK'       =>  explode('.', $this->_staticMask),
                        'STATIC_GATEWAY'    =>  explode('.', $this->_staticGW),
                        'STATIC_DNS1'       =>  explode('.', $this->_staticDNS1),
                        'STATIC_DNS2'       =>  explode('.', $this->_staticDNS2),
                        'TIME_ZONE'         =>  $this->_timeZone,
                        
                        'SERVER_IP_OCTETS'  =>  explode('.', $this->_serverip),
                        'FORCE_DTMF_RTP'    =>  in_array($this->_model, array('GXP280')),
                    ))));

                
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/cfg{$sLowerMAC}");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state
            if (is_null($this->registered)) {
                // grandstream-check-cfg does not work if phone is not registered.
                // Use telnet reboot instead.
                $this->_telnet = new AsyncTelnetClient($this->multiplex, $this);
                $this->_telnet->nuevoTimeout(10);
                //$this->_log->output('DEBUG: connecting via telnet...');
                $st = $this->_telnet->connect($this->_ip);
                if ($st === TRUE) {
                    //$this->_log->output('DEBUG: logging in...');
                    
                    /* The Grandstream GXV3175 has a weird bug in telnet. If the
                     * password is sent before all of the identification strings
                     * have been received, the password is discarded. To work
                     * around this, we need to wait for the login prompt before
                     * sending the password */                    
                    $this->_step = 'TELNET_WAIT_LOGIN_PROMPT';
                } else {
                    $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            } else {
                // GXV3175 wants check-sync, not sys-control
                //$this->_amireboot('grandstream-check-cfg');
                $this->_amireboot('cisco-check-cfg');
            }
            break;
        case 'TELNET_WAIT_LOGIN_PROMPT':
            if ($oConn !== $this->_telnet) break;
            
            if ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            } else {
                $output = $this->_telnet->fetchOutput();
                $lastline = $output[count($output)-1];
            
                if (strpos($lastline, 'Password:') !== FALSE) {
                    if (!is_null($this->_telnet_username))
                        $this->_telnet->appendLines(array($this->_telnet_username));
                    if (!is_null($this->_telnet_password))
                        $this->_telnet->appendLines(array($this->_telnet_password));
                    $this->_step = 'TELNET_LOGIN';
                }
            }            
            break;
        case 'TELNET_LOGIN':
            if ($oConn !== $this->_telnet) break;
            
            if ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            } else {
                $output = $this->_telnet->fetchOutput();
                $lastline = $output[count($output)-1];
                if (preg_match('/>\s?$/', $lastline)) {
                    //$this->_log->output('DEBUG: prompt detected, issuing commands...');
                    if (in_array($this->_model, array('GXV3140', 'GXV3175'))) {
                        $this->_telnet->appendLines(array(
                            'reboot',
                        ));
                    } else {
                        // GXP280 accepts just a 'r'
                        $this->_telnet->appendLines(array(
                            'reboot',
                        ));
                    }
                    $this->_step = 'TELNET_COMMANDS';
                } elseif ($this->_detectTelnetAccessDenied($output)) {
                    $this->_log->output('ERR: detected ACCESS DENIED on telnet connect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            }
            break;
        case 'TELNET_COMMANDS':
            if ($oConn !== $this->_telnet) break;

            $output = $this->_telnet->fetchOutput();
            $bFoundRebootMsg = FALSE;            
            switch ($this->_model) {
            case 'GXV3175':
            case 'GXP2120':
                $sRebootMsg = 'Rebooting...';
                break;
            case 'GXV3140':
                $sRebootMsg = '> reboot';
                break;
            case 'GXP280':
            case 'BT200':
                $sRebootMsg = '>reboot';
                break;
            case 'HT386':
                $this->_log->output('WARN: untested model, might not recognize reboot!');
                $sRebootMsg = '>reboot';
                break;
            default:
                $this->_log->output('WARN: untested model, might not recognize reboot!');
                $sRebootMsg = 'Rebooting...';
                break;
            }
            foreach ($output as $s) if (strpos($s, $sRebootMsg) !== FALSE) $bFoundRebootMsg = TRUE;
            if ($bFoundRebootMsg) {
                // Successfully issued the write command
                
                if (in_array($this->_model, array('GXV3140', 'GXV3175', 'GXP2120'))) {
                    /* The Grandstream GXV3175 needs to have a wait of at least 1 
                     * second with the stream open after the reboot command before 
                     * the reboot command will actually take effect. We let the 
                     * timeout close the telnet stream. */
                    $this->_step = 'TELNET_REBOOT';
                    $this->_telnet->nuevoTimeout(1);
                    $this->_log->output('INFO: waiting 1 second for reboot to take effect');
                } else {
                    // For other models, reboot takes effect immediately
                    if (!$bClosing) {
                        $this->_telnet->finalizarConexion();
                    }
                    $this->_telnet = NULL;
                    
                    // Sets either success, or AMI_UNREGISTER state
                    $this->_unregister();
                }
            } elseif ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'TELNET_REBOOT':
            if ($oConn !== $this->_telnet) break;

            if (!$bClosing) {
                $this->_telnet->finalizarConexion();
            }
            $this->_telnet = NULL;
            
            // Sets either success, or AMI_UNREGISTER state
            $this->_unregister();
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

    private function _detectTelnetAccessDenied($output)
    {
    	$accessDeniedMsgs = array(
            'Permission denied, please try again.',
            'Access Denied.'
        );
        foreach ($output as $s) {
            foreach ($accessDeniedMsgs as $p) {
    		  if (strpos($s, $p) !== FALSE) return TRUE;
            }
    	}
        return FALSE;
    }

    /**
     * Procedure to encode the INI-style configuration into the binary format 
     * expected by the Grandstream phone. This procedure replaces the call to 
     * the external program GS_CFG_GEN/bin/encode.sh.
     * 
     * @param   string  $sMac MAC for Grandstream phone in aabbccddeeff format
     * @param   string  $sTxtConfig Configuration block in INI format
     * 
     * @return  string  Binary block ready to be written to file
     */
    private function _encodeGrandstreamConfig($sMAC, $sTxtConfig)
    {
        $sBloqueConfig = '';
    
        // Validate and encode phone MAC
        if (!preg_match('/^[[:xdigit:]]{12}$/', $sMAC)) return FALSE;
    
        // Parse and encode configuration variables
        $params = array();
        foreach (preg_split("/(\x0d|\x0a)+/", $sTxtConfig) as $s) {
            $s = trim($s);
            if (strpos($s, '#') === 0) continue;
            $regs = NULL;
            if (preg_match('/^(\w+)\s*=\s*(.*)$/', $s, $regs))
                $params[] = $regs[1].'='.rawurlencode($regs[2]);
        }
        $params[] = 'gnkey=0b82';
        $sPayload = implode('&', $params);
        if (strlen($sPayload) & 1) $sPayload .= "\x00";
        //if (strlen($sPayload) & 3) $sPayload .= "\x00\x00";
        
        // Calculate block length in words, plus checksum
        $iLongitud = 8 + strlen($sPayload) / 2;
        $sPayload = pack('NxxH*', $iLongitud, $sMAC)."\x0d\x0a\x0d\x0a".$sPayload;
        $iChecksum = 0x10000 - (array_sum(unpack('n*', $sPayload)) & 0xFFFF);
    
        $sPayload[4] = chr(($iChecksum >> 8) & 0xFF);
        $sPayload[5] = chr(($iChecksum     ) & 0xFF);
    
        if ((array_sum(unpack("n*", $sPayload)) & 0xFFFF) != 0) 
            die('Invalid checksum');
        return $sPayload;
    }
}
?>