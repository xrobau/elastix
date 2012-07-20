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

class EndpointLGERICSSON extends Endpoint
{
    private $_telnet;
    private $_telnet_username = NULL;
    private $_telnet_password = NULL;

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('LG-ERICSSON', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    public function setModel($sModel)
    {
        if (in_array($sModel, array('IP8802A'))) {
            $this->_model = $sModel;
            $this->_telnet_username = 'private';
            $this->_telnet_password = 'lip';
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Configuration for LG-ERICSSON endpoints (local):
     * 
     * The file XXXXXXXXXXXX (with no extension) contains the SIP configuration.
     * Here XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
     * 
     * To reboot the phone, it is necessary to issue the AMI command:
     * sip notify reboot-yealink {$EXTENSION}. Verified with IP8802A.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));

            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'LG-ERICSSON_local_IP8802A.tpl',
                array(
                    'SERVER_IP'         =>  $this->_serverip,
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/{$sLowerMAC}");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$sLowerMAC}");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }

            // Queue the command to reboot and switch to AMI_REBOOT state.
            // Yes, reboot-yealink also reboots LG-ERICSSON phones.
            if (is_null($this->registered)) {
                // reboot-yealink does not work if phone is not registered.
                // Use telnet reboot instead.
                $this->_telnet = new AsyncTelnetClient($this->multiplex, $this);
                $this->_telnet->nuevoTimeout(10);
                //$this->_log->output('DEBUG: connecting via telnet...');
                $st = $this->_telnet->connect($this->_ip, 6000);
                if ($st === TRUE) {
                    //$this->_log->output('DEBUG: logging in...');
                    if (!is_null($this->_telnet_username))
                        $this->_telnet->appendLines(array($this->_telnet_username));
                    if (!is_null($this->_telnet_password))
                        $this->_telnet->appendLines(array($this->_telnet_password));
                    $this->_step = 'TELNET_LOGIN';
                } else {
                    $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            } else {
                $this->_amireboot('reboot-yealink');
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
                if (preg_match('/:> $/', $lastline)) {
                    //$this->_log->output('DEBUG: prompt detected, issuing commands...');
                    $this->_telnet->appendLines(array(
                        'System/Reboot',
                        'y'
                    ));
                    $this->_step = 'TELNET_COMMANDS';
                } elseif (count($output) > 2 && strpos($output[count($output)-2], 'Access Denied') !== FALSE) {
                    $this->_log->output('ERR: detected ACCESS DENIED on telnet connect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            }
            break;
        case 'TELNET_COMMANDS':
            if ($oConn !== $this->_telnet) break;

            $output = $this->_telnet->fetchOutput();
            $bFoundRebootMsg = FALSE;
            foreach ($output as $s) if (strpos($s, 'Reboot msg') !== FALSE) $bFoundRebootMsg = TRUE;            
            if ($bFoundRebootMsg) {
                // Successfully issued the write command
                if (!$bClosing) {
                    $this->_telnet->finalizarConexion();
                }
                $this->_telnet = NULL;
                
                // Sets either success, or AMI_UNREGISTER state
                $this->_unregister();
            } elseif ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
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