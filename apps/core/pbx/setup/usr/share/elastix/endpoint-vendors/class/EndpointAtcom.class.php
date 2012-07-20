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

class EndpointAtcom extends Endpoint
{
    private $_telnet_username = NULL;
    private $_telnet_password = NULL;
    private $_http_username = NULL;
    private $_http_password = NULL;
    private $_telnet;
    private $_http;
    private $_nonce;            // Authentication nonce for AT530 and later
    private $_configVersion;    // Version string for AT530 and later

    // Queue of telnet commands for AT320
    private $_telnetQueue;

    // The following set of parameters is only used for AT530 and later
    private $_enableDHCP = TRUE;
    private $_enableBridge = TRUE;
    private $_staticIP = NULL;
    private $_staticMask = NULL;
    private $_staticGW = NULL;
    private $_staticDNS1 = NULL;
    private $_staticDNS2 = NULL;
    private $_timeZone = 12;

    // List of URLs where the config version number might appear
    private $_listaConfigVersion = array(
        'autoprovision.htm',    // AT610
        'autoupdate.htm',       // AT530
        'config.txt',           // Placed last because it is prone to hangs on AT610
    );

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
    	parent::__construct('Atcom', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    public function setModel($sModel)
    {
    	if (in_array($sModel, array('AT320', 'AT530', 'AT610', 'AT620', 'AT640'))) {
            $this->_model = $sModel;
            switch ($this->_model) {
            case 'AT320':
                $this->_telnet_username = NULL;
                $this->_telnet_password = '12345678';
                break;
            case 'AT530':
            case 'AT610':
            case 'AT620':
            case 'AT640':
            default:
                $this->_telnet_username = 'admin';
                $this->_telnet_password = 'admin';
                break;
            }
            $this->_http_username = 'admin';
            $this->_http_password = 'admin';
    		return TRUE;
    	}
        return FALSE;
    }

    public function setExtraParameters($param)
    {
        // Ignore extra parameters for unsupported models
        if (!in_array($this->_model, array('AT530', 'AT610', 'AT620', 'AT640')))
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
     * Configuration for ATCOM endpoints:
     * 
     * For model AT320, it is necessary to connect to the phone via telnet, and
     * issue a series of commands to set the registration address. 
     * 
     * For models AT530 and later, a file should be created in the /tftpboot
     * directory, named atcXXXXXXXXXXXX.cfg where XXXXXXXXXXXX is the lowercase
     * MAC address of the phone Ethernet interface. This file contains the phone 
     * connection parameters. There is a version string at the beginning of the
     * file, which must be set to a value greater than the version string stored
     * in the phone. To fetch the current version string, it is necessary to
     * issue a series of HTTP requests to the phone.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        if ($this->_localConfigStatus != ENDPOINT_INPROGRESS) return;
    	switch ($this->_model) {
    	case 'AT320':
            return $this->_updateLocalConfig_AT320($oConn, $bClosing);
        default:
            return $this->_updateLocalConfig_AT530($oConn, $bClosing);
    	}
    }
    
    private function _updateLocalConfig_AT320($oConn, $bClosing)
    {
        // TODO: implementar detección de timeout

    	switch ($this->_step) {
    	case 'START':
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
            if ($bClosing) {
                $this->_log->output('ERR: abnormal telnet disconnect');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            } else {
            	$output = $this->_telnet->fetchOutput();
                $lastline = $output[count($output)-1];
                if (preg_match('/^P:\\\\>$/', $lastline)) {
                    //$this->_log->output('DEBUG: prompt detected, issuing commands...');

                    /*
                     * Unfortunately, the brain-damaged telnet implementation of
                     * the AT320 is unable to cope with receiving all of the 
                     * required commands at once in a single network packet. If
                     * this is attempted, the AT320 will only answer to the 
                     * first command and discard all the others. To work around
                     * this, a queue of commands is created, so that they can be
                     * spoon-fed to the phone one by one, checking between them
                     * whether the phone is ready for the next command.
                     */
                    $this->_telnetQueue = array();
                    $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                        'set codec1 2',     // g711u
                        'set ringtype 2',   // user define
                        'set service 1',    // enable
                    ));

                    if ($this->_tech == 'iax2') {
                        $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                            "set serviceaddr {$this->_serverip}"
                        ));

                    } elseif ($this->_tech = 'sip') {
                        $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                            'set servicetype 13',       //sipphone
                            "set sipproxy {$this->_serverip}",
                            "set domain {$this->_serverip}",
                        ));
                    }
                    $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                        "set phonenumber {$this->_device_id}",
                        "set account {$this->_account}",
                        "set pin {$this->_secret}",
                    ));
                    if ($this->_tech == 'iax2') {
                        $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                            'set localport 4569'
                        ));
                    } elseif ($this->_tech = 'sip') {
                        $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                            'set dtmf 1',           // rfc2833
                            'set outboundproxy 1',  // enable
                        ));
                    }
                    $this->_telnetQueue = array_merge($this->_telnetQueue, array(
                        "set sntpip {$this->_serverip}",
                        'write',
                    ));
                    
                    // Start telnet interaction
                    $s = array_shift($this->_telnetQueue);
                    $this->_telnet->appendLines(array($s));
                    $this->_step = 'TELNET_COMMANDS';
                } elseif (count($output) > 2 && preg_match('/^Password:/', $lastline)) {
                    $this->_log->output('ERR: detected ACCESS DENIED on telnet connect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            }
            break;
        case 'TELNET_COMMANDS':
            $output = $this->_telnet->fetchOutput();
            $lastline = $output[count($output)-1];
            if (count($this->_telnetQueue) > 0) {
            	// There are still commands to be spoon-fed to the phone
                if ($bClosing) {
                    $this->_log->output('ERR: abnormal telnet disconnect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                } elseif (preg_match('/^P:\\\\>$/', $lastline)) {
                    // Feed the next command
                    $s = array_shift($this->_telnetQueue);
                    $this->_telnet->appendLines(array($s));
                }
            } elseif (strpos($lastline, 'rebooting...') === 0) {
                // Successfully issued the last command
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
        case 'AMI_UNREGISTER':
            if (is_array($oConn)) {
                $this->_localConfigStatus = isset($oConn['data'])
                    ? ENDPOINT_SUCCESS
                    : ENDPOINT_FAILURE; 
            }
            break;
    	}
    }

    /**
     * The AT530 and later uses a web server that only understands HTTP/1.1, not
     * HTTP/1.0. Additionally, the server needs the Connection: keep-alive 
     * header (even though keep-alive is supposed to be the HTTP/1.1 default),
     * and all of the requests must be performed on the same TCP connection, if
     * at all possible. Failure to follow these rules will result in truncated 
     * output.
     * 
     * In some versions of the AT610 firmware, if the phone has been already 
     * configured before, and an attempt is made to fetch the config.txt to 
     * query the current version number, the request will hang for a very long 
     * time (more than 5 minutes). Since all we need is the version number, not
     * the complete configuration, we attempt to fetch some known pages that 
     * expose the current version number, which do not hang, before falling back
     * to the config.txt file.
     * 
     * Additionally, in old versions of the AT610 firmware (2011 or earlier), 
     * the phone will sometimes refuse to apply a configuration file, even 
     * though it has a version number greater than the one announced by the 
     * phone. This problem occurs intermittently and is a known firmware bug. 
     * The only solution for this bug is a firmware update.
     */
    private function _updateLocalConfig_AT530($oConn, $bClosing)
    {
    	switch ($this->_step) {
    	case 'START':
            // Open connection to web interface to fetch current configuration
            $sURL = "http://{$this->_ip}/";
            //$this->_log->output("DEBUG: connecting to $sURL ...");
            $this->_http = new AsyncHTTPClient($this->multiplex, $this);
            $this->_http->nuevoTimeout(5);
            $this->_http->setRequestProtocolVersion('1.1');
            $this->_http->addRequestHeaders(array('Connection' => 'keep-alive'));
            $st = $this->_http->GET($sURL);
            if ($st === TRUE) {
                //$this->_log->output("DEBUG: requested $sURL, waiting for response...");
                $this->_step = 'HTTP_NONCE';
            } else {
                $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'HTTP_NONCE':
            if ($this->_http->getResponseComplete()) {
                if ($this->_http->getResponseCode() == '200') {
                    $htmlbody = $this->_http->getResponseData();
                    $regs = NULL;
                    if (!preg_match('/<input type="hidden" name="nonce" value="([0-9a-zA-Z]+)">/',
                        $htmlbody, $regs)) {
                        $this->_log->output('ERR: failed to locate nonce in HTTP response');
                        $this->_localConfigStatus = ENDPOINT_FAILURE;
                    } else {
                        $this->_nonce = $regs[1];

                        // Simulate POST to allow fetching rest of content
                        $sURL = 'http://'.$this->_ip.'/';
                        $this->_http->addRequestHeaders(array(
                            'Connection' => 'keep-alive',
                            'Cookie' => "auth={$this->_nonce}"
                            ));
                        $sEncodedAuth = $this->_http_username.':'.md5($this->_http_username.':'.$this->_http_password.':'.$this->_nonce);
                        $this->_http->setPostData("encoded=$sEncodedAuth&nonce={$this->_nonce}&goto=Logon&URL=/");
                        $st = $this->_http->POST($sURL);
                        if ($st === TRUE) {
                            //$this->_log->output("DEBUG: requested POST $sURL, waiting for response...");
                            $this->_step = 'HTTP_POST_LOGIN';
                        } else {
                            $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                            $this->_localConfigStatus = ENDPOINT_FAILURE;
                        }
                    }
                } else {
                    $this->_log->output(
                        'ERR: failed to fetch nonce for HTTP configuration - '.
                        'got response code '.$this->_http->getResponseCode());
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                    $this->_http->finalizarConexion();
                }
            } elseif ($bClosing) {
                $this->_log->output(
                    'ERR: failed to fetch nonce for HTTP configuration - '.
                    'failed to get full response');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'HTTP_POST_LOGIN':
            if ($this->_http->getResponseComplete()) {
                if ($this->_http->getResponseCode() == '200') {
                    // Try next URL where config version might be found
                    $sURL = 'http://'.$this->_ip.'/'.$this->_listaConfigVersion[0];
                    $this->_http->addRequestHeaders(array(
                        'Connection' => 'keep-alive',
                        'Cookie' => "auth={$this->_nonce}"
                        ));
                    $st = $this->_http->GET($sURL);
                    if ($st === TRUE) {
                        //$this->_log->output("DEBUG: requested $sURL, waiting for response...");
                        $this->_step = 'HTTP_CONFIG';
                    } else {
                        $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                        $this->_localConfigStatus = ENDPOINT_FAILURE;
                    }
                } else {
                    $this->_log->output(
                        'ERR: failed to fetch login result - '.
                        'got response code '.$this->_http->getResponseCode());
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                    $this->_http->finalizarConexion();
                }
            } elseif ($bClosing) {
                $this->_log->output(
                    'ERR: failed to fetch login result - '.
                    'failed to get full response');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'HTTP_CONFIG':
            if ($this->_http->getResponseComplete()) {
                if ($this->_http->getResponseCode() == '200') {
                    $htmlbody = $this->_http->getResponseData();
                    $regs = NULL;
                    if ($this->_listaConfigVersion[0] == 'config.txt') {
                        $regexp = '/<<VOIP CONFIG FILE>>Version:([2-9]{1}\.[0-9]{4})/';
                    } else {
                    	$regexp = '/Current Version.*?([2-9]{1}\.[0-9]{4})/s';
                    }
                    if (!preg_match($regexp, $htmlbody, $regs)) {
                        $this->_log->output('ERR: failed to locate config version in HTTP response');
                        $this->_localConfigStatus = ENDPOINT_FAILURE;
                    } else {
                        $this->_configVersion = $regs[1];
                        $this->_log->output("INFO: previous config version is {$this->_configVersion}");
                        $this->_http->setPostData('DefaultLogout=Logout');
                        $this->_http->addRequestHeaders(array(
                            'Connection' => 'keep-alive',
                            'Cookie' => "auth={$this->_nonce}"
                            ));
                        $sURL = "http://{$this->_ip}/LogOut.htm";
                        $st = $this->_http->POST($sURL);
                        if ($st === TRUE) {
                            //$this->_log->output("DEBUG: requested $sURL, waiting for response...");
                            $this->_step = 'HTTP_LOGOUT';
                        } else {
                            $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                            $this->_localConfigStatus = ENDPOINT_FAILURE;
                        }
                    }
                } elseif ($this->_http->getResponseCode() == '404') {
                    //$this->_log->output('DEBUG: config version not found in '.$this->_listaConfigVersion[0].' - trying next source...');
                    array_shift($this->_listaConfigVersion);
                    if (count($this->_listaConfigVersion) <= 0) {
                        $this->_log->output(
                            'ERR: failed to fetch configuration version - '.
                            'no more sources to try.');
                        $this->_localConfigStatus = ENDPOINT_FAILURE;
                        $this->_http->finalizarConexion();
                    } else {
                        // Try next URL where config version might be found
                        $sURL = 'http://'.$this->_ip.'/'.$this->_listaConfigVersion[0];
                        $this->_http->addRequestHeaders(array(
                            'Connection' => 'keep-alive',
                            'Cookie' => "auth={$this->_nonce}"
                            ));
                        $st = $this->_http->GET($sURL);
                        if ($st === TRUE) {
                            //$this->_log->output("DEBUG: requested $sURL, waiting for response...");
                            $this->_step = 'HTTP_CONFIG';
                        } else {
                            $this->_log->output('ERR: failed to connect - ('.$st[0].') '.$st[1]);
                            $this->_localConfigStatus = ENDPOINT_FAILURE;
                        }
                    }
                } else {
                    $this->_log->output(
                        'ERR: failed to fetch configuration version - '.
                        'got response code '.$this->_http->getResponseCode());
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                    $this->_http->finalizarConexion();
                }
            } elseif ($bClosing) {
                $this->_log->output(
                    'ERR: failed to fetch configuration version - '.
                    'failed to get full response');
                $this->_localConfigStatus = ENDPOINT_FAILURE;
            }
            break;
        case 'HTTP_LOGOUT':
            if ($this->_http->getResponseComplete()) {
                if ($this->_http->getResponseCode() == '200') {
                    $htmlbody = $this->_http->getResponseData();
                    $this->_http->finalizarConexion();
                    $this->_http = NULL;

                    if (strpos($htmlbody, 'No Post Handler') !== FALSE) {
                    	$this->_log->output(
                            'WARN: phone lacks handler for LogOut.htm - HTTP '.
                            'login might only work a limited number of times.');
                    }

                    $r = $this->_writeAtcom530Template();
                    if ($r === FALSE) {
                        $this->_localConfigStatus = ENDPOINT_FAILURE;
                    } else {
                    	// Telnet to phone to refresh config and reboot
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
                    }
                } else {
                    $this->_log->output(
                        'ERR: failed to logout from phone - '.
                        'got response code '.$this->_http->getResponseCode());
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                    $this->_http->finalizarConexion();
                    $this->_http = NULL;
                }
            } elseif ($bClosing) {
                $this->_log->output(
                    'ERR: failed to logout from phone - '.
                    'failed to get full response');
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
                if (preg_match('/# $/', $lastline)) {
                    //$this->_log->output('DEBUG: prompt detected, issuing commands...');
                    $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
                    
                    $this->_telnetQueue = array(
                        "download tftp -ip $this->_serverip -file atc$sLowerMAC.cfg",
                        'save',
                        'reload'
                    );
                    
                    $this->_telnet->appendLines(array(array_shift($this->_telnetQueue)));
                    $this->_step = 'TELNET_COMMANDS';
                } elseif (count($output) > 1 && preg_match('/^Login:/', $lastline)) {
                    $this->_log->output('ERR: detected ACCESS DENIED on telnet connect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                }
            }
            break;
        case 'TELNET_COMMANDS':
            if ($oConn !== $this->_telnet) break;

            $output = $this->_telnet->fetchOutput();
            while (trim($output[count($output)-1]) == '')
                array_pop($output);
            $lastline = $output[count($output)-1];
            
            if (count($this->_telnetQueue) > 0) {
                // There are still commands to be spoon-fed to the phone
                if ($bClosing) {
                    $this->_log->output('ERR: abnormal telnet disconnect');
                    $this->_localConfigStatus = ENDPOINT_FAILURE;
                } elseif (preg_match('/# $/', $lastline)) {
                    // Feed the next command
                	//$this->_log->output('DEBUG: sleeping for 3 seconds...');
                    //usleep(3 * 1000000);
                    $this->_telnet->appendLines(array(array_shift($this->_telnetQueue)));
                }
            } elseif (strpos($lastline, '# reload') !== FALSE) {
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
        case 'AMI_UNREGISTER':
            if (is_array($oConn)) {
                $this->_localConfigStatus = isset($oConn['data'])
                    ? ENDPOINT_SUCCESS
                    : ENDPOINT_FAILURE; 
            }
            break;
    	}
    }

    private function _writeAtcom530Template()
    {
        $this->_incrementAtcomVersion();
        $this->_log->output("INFO: new config version is {$this->_configVersion}");
        
        // Need to calculate lowercase version of MAC address without colons
        $sLowerMAC = strtolower(str_replace(':', '', $this->_mac));
        $sConfigFile = "atc{$sLowerMAC}.cfg";
        $sConfigPath = TFTP_DIR."/$sConfigFile";
        
        // Assign Smarty variables and write out the file
        $r = self::_writeSmartyTemplate(
            "Atcom_local_530_{$this->_tech}.tpl",
            array(
                'SERVER_IP'         =>  $this->_serverip,
                'ID_DEVICE'         =>  $this->_device_id,
                'SECRET'            =>  $this->_secret,
                'DISPLAY_NAME'      =>  $this->_desc,
                
                'VERSION_CFG'       =>  $this->_configVersion,
                'CONFIG_FILENAME'   =>  $sConfigFile,
                'ENABLE_DHCP'       =>  $this->_enableDHCP ? 1 : 0,
                'STATIC_IP'         =>  $this->_staticIP,
                'STATIC_MASK'       =>  $this->_staticMask,
                'STATIC_GATEWAY'    =>  $this->_staticGW,
                'STATIC_DNS1'       =>  $this->_staticDNS1,
                'STATIC_DNS2'       =>  $this->_staticDNS2,
                'TIME_ZONE'         =>  $this->_timeZone,
                'ENABLE_BRIDGE'     =>  $this->_enableBridge ? 1 : 0,
            ),
            $sConfigPath);

        if ($r === FALSE) {
            $this->_log->output(
                'ERR: failed to write to configuration file '.$sConfigPath);
        }
        return $r;
    }

    private function _incrementAtcomVersion()
    {
    	// 2.0002
        $s = (int)implode('', explode('.', $this->_configVersion));
        $s++;
        $this->_configVersion = substr($s, 0, strlen($s) - 4).'.'.substr($s, strlen($s) - 4);
    }
}
?>