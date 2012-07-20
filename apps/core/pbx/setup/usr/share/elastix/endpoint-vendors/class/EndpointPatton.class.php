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


class EndpointPatton extends Endpoint
{
    static private $_country_toneset = array();

    private $_param;    // Extra parameters from endpoint.db
    private $_telnet;   // Telnet client to reboot Patton
    private $_telnet_username = 'administrator';
    private $_telnet_password = '';

    // Queue of telnet commands
    private $_telnetQueue;
    
    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Patton', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    public function setExtraParameters($param)
    {
        if (!isset($param['pbx_address'])) $param['pbx_address'] = $this->_serverip;
        if (!isset($param['sip_port'])) $param['sip_port'] = 5060; 

        // Unset empty keys
        foreach (array('sntp_address', 'dns_address') as $k) {
        	if (isset($param[$k]) && trim($param[$k]) == '')
                unset($param[$k]);
        }

        // Keys that must be present
        foreach (array('lan_type', 'router_present', 'timeout', 
            'analog_extension_lines', 'first_extension', 'increment', 
            'callerID_presentation', 'callerID_format', 'analog_trunk_lines',
            'delivery_announcements', 'wait_callerID', 'lines_sip_port',
            'extensions_sip_port', 'country') as $k) {
        	if (!isset($param[$k])) {
                $this->_log->output('ERR: required extra field not assigned: '.$k);
        		return FALSE;
        	}
        }
        
        // Keys that must be conditionally present
        $condKeys = array();
        for ($i = 0; $i < $param['analog_extension_lines']; $i++) {
            $condKeys[] = 'user_name'.$i;
            $condKeys[] = 'user'.$i;
            $condKeys[] = 'authentication_user'.$i;
        }
        for ($i = 0; $i < $param['analog_trunk_lines']; $i++) {
            $condKeys[] = 'line'.$i;
            $condKeys[] = 'ID'.$i;
            $condKeys[] = 'authentication_ID'.$i;
        }
        if ($param['lan_type'] != 'dhcp') {
        	$condKeys[] = 'lan_ip_address';
            $condKeys[] = 'lan_ip_mask';
        }
        if ($param['router_present'] == 'yes') {
        	$condKeys[] = 'wan_type';
            if (isset($param['wan_type']) && $param['wan_type'] != 'dhcp') {
                $condKeys[] = 'wan_ip_address';
                $condKeys[] = 'wan_ip_mask';
            }
        }
        foreach ($condKeys as $k) {
            if (!isset($param[$k])) {
                $this->_log->output('ERR: required extra field not assigned: '.$k);
                return FALSE;
            }
        }
        
        // Keys that must be valid IP addresses
        foreach (array('sntp_address', 'dns_address', 'lan_ip_address', 
            'lan_ip_mask', 'wan_ip_address', 'wan_ip_mask', 'pbx_address') as $k) {
            if (isset($param[$k])) {
                if ($param[$k] == '0.0.0.0' || 
                    !preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $param[$k])) {
                    $this->_log->output(
                        'ERR: the following extra field is not a valid IPv4 address: '.
                        $k.'='.$param[$k]);
                    return FALSE;
                }
            }
        }
        
        $this->_param = $param;
        if (isset($param['telnet_username']))
            $this->_telnet_username = $param['telnet_username'];
        if (isset($param['telnet_password']))
            $this->_telnet_password = $param['telnet_password'];
        
        // The values of tone_set and fxo_fxs_profile need to be read from the
        // database
        if (!isset(self::$_country_toneset[$param['country']])) {
        	try {
                $conn = new PDO(DSN_ENDPOINT);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sth = $conn->prepare(
                    'SELECT country, fxo_fxs_profile, tone_set '.
                    'FROM settings_by_country WHERE id = ?');
                $sth->execute(array($param['country']));
                self::$_country_toneset[$param['country']] = $sth->fetch(PDO::FETCH_ASSOC);
                $sth->closeCursor();
                
                if (!is_array(self::$_country_toneset[$param['country']])) {
                    $this->_log->output('ERR: no tone set found for country ID '.$param['country']);
                	return FALSE;
                } else {
                    $this->_log->output('INFO: loaded tone set for country: '.
                        self::$_country_toneset[$param['country']]['country']);
                }
        	} catch (PDOException $e) {
                $this->_log->output("ERR: failed to query country tone set - ".$e->getMessage());
                return FALSE;
        	}
        }
        
        // TODO: terminar de implementar
        return TRUE;
    }
    
    /**
     * Configuration for Patton endpoints:
     * 
     * The file XXXXXXXXXXXX_Patton.cfg is created in /tftpboot/ . Here, 
     * XXXXXXXXXXXX is replaced by the MAC address of the endpoint. Next, the
     * Patton is instructed to read the configuration file through a series of
     * telnet commands, and then rebooted through the same telnet connection.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
    	switch ($this->_step) {
    	case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
            
            // Write out the configuration file
            $sConfigPath = TFTP_DIR."/{$sLowerMAC}_Patton.cfg";
            if (!file_put_contents(
                $sConfigPath,
                $this->_getPattonConfiguration(
                    $this->_param,
                    self::$_country_toneset[$this->_param['country']]))) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.$sConfigPath);
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            
            $this->_telnet = new AsyncTelnetClient($this->multiplex, $this);
            $this->_telnet->nuevoTimeout(10);
            //$this->_log->output('DEBUG: connecting via telnet...');
            $st = $this->_telnet->connect($this->_ip);
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
            break;
        case 'TELNET_LOGIN':
            if ($oConn !== $this->_telnet) break;
            
            if ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            } else {
                $output = $this->_telnet->fetchOutput();
                $lastline = $output[count($output)-1];
                if (preg_match('/^\d+\.\d+\.\d+\.\d+>$/', $lastline)) {
                    //$this->_log->output('DEBUG: prompt detected, issuing commands...');
                    $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
                    
                    /*
                     * Unfortunately, the Patton is unable to cope with receiving
                     * all of the required commands in a single packet. If a
                     * command is received before the prompt has been sent to 
                     * the client, it will be discarded. To work around this, a
                     * queue of commands is created, so that they can be 
                     * spoon-fed to the gateway one by one, checking between 
                     * them whether the phone is ready for the next command.
                     */
                    $this->_telnetQueue = array(
                        'enable',
                        "copy tftp://{$this->_serverip}/{$sLowerMAC}_Patton.cfg startup-config",
                        'reload forced'
                    );
                    // Start telnet interaction
                    $s = array_shift($this->_telnetQueue);
                    $this->_telnet->appendLines(array($s));
                    $this->_step = 'TELNET_COMMANDS';
                } elseif (count($output) > 2 && 
                    strpos($lastline, 'login: ') === 0 && 
                    strpos($output[count($output)-2], 'Authentication failed!') !== FALSE) {
                    $this->_log->output('ERR: detected ACCESS DENIED on telnet connect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            }
            break;
        case 'TELNET_COMMANDS':
            if ($oConn !== $this->_telnet) break;

            $output = $this->_telnet->fetchOutput();
            $lastline = $output[count($output)-1];
            
            if (count($this->_telnetQueue) > 0) {
                // There are still commands to be spoon-fed to the gateway
                if ($bClosing) {
                    $this->_log->output('ERR: abnormal telnet disconnect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                } elseif (preg_match('/^\d+\.\d+\.\d+\.\d+#$/', $lastline)) {
                    // Feed the next command
                    $s = array_shift($this->_telnetQueue);
                    $this->_telnet->appendLines(array($s));
                }
            } elseif (strpos($lastline, 'The system is going down') !== FALSE || 
                    strpos($output[count($output)-2], 'The system is going down') !== FALSE) {
                // Successfully issued the write command
                //$this->_log->output("DEBUG: telnet session:".print_r($output, TRUE));
                if (!$bClosing) {
                    $this->_telnet->finalizarConexion();
                }
                $this->_telnet = NULL;
                
                $this->_localConfigStatus = ENDPOINT_SUCCESS;
            } elseif ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
    	}
    }

    /**
     * Properties loaded from endpoint parameters:
     * 
     * arrData keys: 
     *  sntp_address    If set, defines IP of NTP server for device
     *  dns_address     If set, defines DNS for the device
     *  lan_type        One of 'dhcp' or 'static'
     *      lan_ip_address  IP address for LAN (static only)
     *      lan_ip_mask     IP mask for LAN (static only) 
     *  router_present  Set to 'yes' if device has 2 Ethernet ports, else 'no'
     *      wan_type    One of 'dhcp' or 'static'
     *          wan_ip_address  IP address for WAN (static only)
     *          wan_ip_mask     IP mask for WAN (static only) 
     *  timeout         Timeout for digit collection
     *  analog_extension_lines  Number of analog extension lines (0..N-1)
     *  first_extension         Initial extension in analog extension range
     *  increment               Increment between each extension in analog extension range
     *      user_nameN          Symbolic name for SIP mapping to analog extension N 
     *      userN               SIP account for mapping to analog extension N
     *      authentication_userN Password for SIP account N
     * 
     *  pbx_address     IP address of SIP server, should default to $this->_serverip
     *  sip_port        UDP port for SIP, should default to 5060
     *  callerID_presentation   When to present callerid information (pre-ring, mid-ring, none)
     *  callerID_format Profile to use for outgoing callerid (etsi, bell)
     *  analog_trunk_lines      Number of analog trunk lines (0..N-1)
     *  delivery_announcements  Enable delivery announcements (yes, no)
     *  wait_callerID           Wait for callerid on trunk (yes, no)
     *      lineN               PSTN number assigned to line N
     *      IDN                 Username for line N
     *      authentication_IDN  Password for line N
     *  lines_sip_port          First SIP port for bridge to outgoing lines
     *  extensions_sip_port     SIP port for bridge to extensions
     * 
     * tone_set keys:
     *  tone_set        Configuration of tones to play, defined from country
     *  fxo_fxs_profile Profile to use for incoming caller-id (etsi,au,nl,ch,gb,us), defined from country
     */
    private function _getPattonConfiguration($arrData, $tone_set)
    {
        $pbx_side = ($arrData['router_present'] == 'yes') 
            ? strtoupper($arrData['pbx_side']) 
            : 'LAN'; 
        $config = <<<CONF
webserver port 80 language en

CONF;
        if (isset($arrData['sntp_address']) && $arrData['sntp_address'] != '') {
            $config .= <<<CONF
sntp-client server $arrData[sntp_address]

CONF;
        }
        if (isset($arrData['dns_address']) && $arrData['dns_address'] != '') {
            $config .= <<<CONF
dns-client server $arrData[dns_address]

CONF;
        }
        $config .= <<<CONF

system
ic voice 0
low-bitrate-codec g729
profile ppp default


$tone_set[tone_set]


profile tone-set default
profile voip default
codec 1 g711alaw64k rx-length 20 tx-length 20
codec 2 g711ulaw64k rx-length 20 tx-length 20
fax transmission 1 relay t38-udp
fax transmission 2 bypass g711alaw64k
profile pstn default
profile sip default
profile aaa default
method 1 local
method 2 none

context ip router
interface IF_IP_LAN

CONF;
        if ($arrData['lan_type'] == 'dhcp') {
            $config .= <<<CONF
ipaddress dhcp

CONF;
        } else {
            $config .= <<<CONF
  ipaddress $arrData[lan_ip_address] $arrData[lan_ip_mask]

CONF;
        }
        $config .= <<<CONF
tcp adjust-mss rx mtu
tcp adjust-mss tx mtu

interface IF_IP_WAN

CONF;
        if ($arrData['router_present'] == 'yes') {
            if ($arrData['wan_type'] == 'dhcp') {
                $config .= <<<CONF
  ipaddress dhcp

CONF;
            } else {
                $config .= <<<CONF
  ipaddress $arrData[wan_ip_address] $arrData[wan_ip_mask]

CONF;
            }
        }
        $config .= <<<CONF
tcp adjust-mss rx mtu
tcp adjust-mss tx mtu

context ip router
  route 0.0.0.0 0.0.0.0  $arrData[default_gateway]
context cs switch

CONF;
        if ($arrData['timeout'] == '') {
    $config .= <<<CONF
  no digit-collection timeout

CONF;
        } else {
    $config .= <<<CONF
  digit-collection timeout $arrData[timeout]

CONF;
        }
        $interface_fxs = '';
        $authentication_extensions = '';
        $location_service = '';
        $port_fxs = '';
        if ($arrData['analog_extension_lines'] != 0) {
            $config .= <<<CONF

routing-table called-e164 RT_DIGITCOLLECTION
  route .T dest-interface IF_SIP_PBX MT_EXT_TO_NAME

routing-table called-e164 RT_TO_FXS

CONF;
            $mapping_table = <<<CONF

mapping-table calling-e164 to calling-name MT_EXT_TO_NAME

CONF;
            $interface_fxs = <<<CONF


interface sip IF_SIP_PBX
    bind context sip-gateway GW_SIP_ALL_EXTENSIONS
    route call dest-table RT_TO_FXS
      remote $arrData[pbx_address] $arrData[sip_port]



CONF;
            $authentication_extensions = <<<CONF

authentication-service AS_ALL_EXTENSIONS


CONF;
            $location_service = <<<CONF

location-service LS_ALL_EXTENSIONS
  domain 1 $arrData[pbx_address] $arrData[sip_port]
  identity-group default

CONF;
            $presentation = in_array($arrData['callerID_presentation'], array('pre-ring', 'mid-ring')) 
                ? 'caller-id-presentation '.$arrData['callerID_presentation'] 
                : '';

            for ($i = 0; $i < $arrData['analog_extension_lines']; $i++) {
                $extension = $arrData['first_extension'] + $i * $arrData['increment'];
                $number = $i+1;
                $config .= <<<CONF
    route $extension dest-interface IF_FXS_$number

CONF;
                $user_name = $arrData["user_name$i"];
                $mapping_table .= <<<CONF
    map $extension to "$user_name"

CONF;
                $interface_fxs .= <<<CONF
interface fxs IF_FXS_$number
    $presentation
      route call dest-table RT_DIGITCOLLECTION
      message-waiting-indication stutter-dial-tone
      message-waiting-indication frequency-shift-keying
      call-transfer
      subscriber-number $extension

CONF;
                $user = $arrData["user$i"];
                $password = $arrData["authentication_user$i"];
                $authentication_extensions .= <<<CONF
  username $user password $password

CONF;
                $location_service .= <<<CONF
  identity $extension
      authentication outbound
      authenticate 1 authentication-service AS_ALL_EXTENSIONS username $user
      registration outbound
      registrar $arrData[pbx_address] $arrData[sip_port]
      lifetime 300
      register auto
      retry-timeout on-system-error 10
      retry-timeout on-client-error 10
      retry-timeout on-server-error 10

CONF;
                $port_fxs .= <<<CONF

port fxs 0 $i
    caller-id format $arrData[callerID_format]
      use profile fxs $tone_set[fxo_fxs_profile]
  encapsulation cc-fxs
    bind interface IF_FXS_1 switch 
  no shutdown
CONF;
            }
            $config .= $mapping_table;
        }
        $authentication_trunks = '';
        $context_sip_gateway = '';
        $port_fxo = '';
        if ($arrData['analog_trunk_lines'] != 0) {
            $interface_fxo = '';
            $authentication_trunks = <<<CONF

authentication-service AS_ALL_LINES



CONF;
            $early_connect = (($arrData['delivery_announcements'] == 'yes') ? '' : 'no ').'early-connect';
            $ring_number = 'ring-number '.(($arrData['wait_callerID'] == 'yes') ? 'on-caller-id' : '1');
            for ($i = 0; $i < $arrData['analog_trunk_lines']; $i++) {
                $number = $i+1;
                $line = $arrData["line$i"];
                $config .= <<<CONF

interface sip IF_SIP_$number
    bind context sip-gateway GW_SIP_$number
    $early_connect 
    early-disconnect
    route call dest-interface IF_FXO_$number
    remote $arrData[pbx_address] $arrData[sip_port]
    address-translation outgoing-call request-uri user-part fix $line host-part to-header target-param none
    address-translation incoming-call called-e164 request-uri

CONF;
                $interface_fxo .= <<<CONF

interface fxo IF_FXO_$number
      route call dest-interface IF_SIP_$number
      loop-break-duration min 200 max 1000
      $ring_number 
      mute-dialing
    disconnect-signal loop-break
      disconnect-signal busy-tone
      dial-after timeout 1
CONF;
                $id_trunk = $arrData["ID$i"];
                $password_trunk = $arrData["authentication_ID$i"];
                $authentication_trunks .= <<<CONF
  username $id_trunk password $password_trunk

CONF;
                $port = $arrData["lines_sip_port"] + $i*2;
                $context_sip_gateway .= <<<CONF

context sip-gateway GW_SIP_$number
interface $pbx_side
bind interface IF_IP_$pbx_side context router port $port
context sip-gateway GW_SIP_$number
bind location-service LS_$number
no shutdown
CONF;
                $port_fxo .= <<<CONF


port fxo 0 $i
  use profile fxo $tone_set[fxo_fxs_profile]
  encapsulation cc-fxo
  bind interface IF_FXO_$number switch
  no shutdown
CONF;
            }
            $config .= $interface_fxo;
        }
        $config .= $interface_fxs;
        $config .= <<<CONF

context cs switch
no shutdown

CONF;
        $config .= $authentication_extensions;
        $config .= $authentication_trunks;
        $config .= $location_service;
        $config .= $context_sip_gateway;
        $config .= $port_fxo;
        $config .= <<<CONF


context sip-gateway GW_SIP_ALL_EXTENSIONS
  interface $pbx_side
    bind interface IF_IP_$pbx_side context router port $arrData[extensions_sip_port]

context sip-gateway GW_SIP_ALL_EXTENSIONS
    bind location-service LS_ALL_EXTENSIONS
    no shutdown

port ethernet 0 0
medium auto
encapsulation ip
bind interface IF_IP_LAN router
no shutdown

CONF;
        if ($arrData['router_present'] == 'yes') {
            $config .= <<<CONF

port ethernet 0 1
medium auto
encapsulation ip
bind interface IF_IP_WAN router
no shutdown

CONF;
        }
        $config .= $port_fxs;
        return $config;  
    }
}
?>