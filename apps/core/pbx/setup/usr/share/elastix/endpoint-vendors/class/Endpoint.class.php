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
define('ENDPOINT_FAILURE', -1);
define('ENDPOINT_INPROGRESS', 0);
define('ENDPOINT_SUCCESS', 1);

define('TFTP_DIR', '/tftpboot');

abstract class Endpoint
{
    protected $_log;
    private $_vendor_name;

    // All endpoints have these
    protected $_serverip;
    protected $_mac;
    protected $_ip;
    protected $_device_id;
    protected $_tech;
    protected $_desc;
    protected $_account;
    protected $_secret;
    protected $_model;
    
    /* These are required for creation of network clients. These variables are
     * assigned just before calling updateLocalConfig() for the first time. */
    public $ami;
    public $multiplex;
    
    protected $_localConfigStatus;
    protected $_step = 'START';

    /* This field, if non-null, should contain a tuple (TECH, IP) that indicates
     * that the current device_id is registered as channel TECH/IP */
    public $registered = NULL;

    /**
     * Method that sets up an environment for endpoints. Typically this entails
     * creating a directory structure under /tftpboot and/or creating global
     * configuration files under same directory.
     * 
     * @return bool TRUE if global update was successful
     */
    public static function updateGlobalConfig()
    {
    	return TRUE;
    }

    /**
     * Base class constructor for an endpoint.
     * 
     * @param   object  $oLog       Logger object to use for output
     * @param   string  $sMAC       MAC address of the endpoint
     * @param   string  $sIP        IP address of the endpoint
     * @param   string  $sDeviceID  FreePBX device ID
     * @param   string  $sTech      Technology for the device (sip|iax2)
     * @param   string  $sDesc      Description for the endpoint
     * @param   string  $sAccount   Asterisk account for the endpoint
     * @param   string  $sSecret    Secret for the Asterisk account
     */
    function __construct($sVendorName, $oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        $this->_vendor_name = $sVendorName;
        $this->_log = $oLog;
        $this->_mac = $sMAC;
        $this->_serverip = $sServerIP;
        $this->_ip = $sIP;
        $this->_device_id = $sDeviceID;
        $this->_tech = $sTech;
        $this->_desc = $sDesc;
        $this->_account = $sAccount;
        $this->_secret = $sSecret;
        $this->_model = NULL;
        $this->_localConfigStatus = ENDPOINT_INPROGRESS;
    }

    /**
     * Method to assign a model for an endpoint vendor. Should override - 
     * default implementation assigns any model without any check.
     * 
     * @param   string  $sModel Model string identifier
     * 
     * @return  bool    TRUE if model is valid and accepted
     */
    public function setModel($sModel)
    {
    	$this->_model = $sModel;
        return TRUE;
    }
    
    
    /**
     * Method to assign extra parameters for the endpoint. Should override -
     * default implementation discards all parameters without any check.
     * 
     * @param   array   $param  List of extra parameters
     * 
     * @return  bool    TRUE if parameters are valid and accepted 
     */
    public function setExtraParameters($param)
    {
    	return TRUE;
    }

    // Fetch a property value
    public function __get($s)
    {
        switch ($s) {
        case 'vendor_name': return $this->_vendor_name;
        case 'mac':         return $this->_mac;
        case 'serverip':    return $this->_serverip;
        case 'ip':          return $this->_ip;
        case 'device_id':   return $this->_device_id;
        case 'tech':        return $this->_tech;
        case 'desc':        return $this->_desc;
        case 'account':     return $this->_account;
        case 'secret':      return $this->_secret;
        case 'model':       return $this->_model;
        case 'localConfigStatus':
            return $this->_localConfigStatus;
        }
        
        $log = $this->_log;
        $log->output("ERR: invalid reference to property: ".__CLASS__."::$s");         
        foreach (debug_backtrace() as $traceElement) {
            $sNombreFunc = $traceElement['function'];
            if (isset($traceElement['type'])) {
                $sNombreFunc = $traceElement['class'].'::'.$sNombreFunc;
                if ($traceElement['type'] == '::')
                    $sNombreFunc = '(static) '.$sNombreFunc;
            }
            $log->output("\tin {$traceElement['file']}:{$traceElement['line']} function {$sNombreFunc}()");
        }           
        return NULL;
    }
    
    abstract public function updateLocalConfig($oConn, $bClosing);
    
    /**
     * Method to asynchronously execute a SIP notification that should cause a
     * reboot of a phone. The specific notification-string is phone-specific and
     * should be defined in /etc/asterisk/sip_notify.conf or (FreePBX)
     * /etc/asterisk/sip_notify_additional.conf . After running this method, the
     * state machine switches to AMI_REBOOT state.
     * 
     * @param   string  $sNotify    A notification string ('reboot-yealink')
     * 
     * @return  void
     */
    protected function _amireboot($sNotify)
    {
    	$this->_step = 'AMI_REBOOT';
        $this->ami->enqueue_response_handler(array($this, '_amiUnregisterCallback'));
        $this->ami->Command('sip notify '.$sNotify.' '.
            (is_null($this->registered) ? $this->_ip : $this->registered[1]));
    }
    
    /**
     * Method to force unregistration of the registered endpoint as the final
     * step while rebooting the phone.
     */
    protected function _unregister()
    {
        $this->_step = 'AMI_UNREGISTER';
    	if (is_null($this->registered)) {
            $this->_localConfigStatus = ENDPOINT_SUCCESS;
    		return FALSE;
    	}
        $this->ami->enqueue_response_handler(array($this, '_amiUnregisterCallback'));
        $this->ami->Command("{$this->registered[0]} unregister {$this->registered[1]}");                
        return TRUE;
    }
    
    // This needs to be public in order to be called as an AMI callback
    public function _amiUnregisterCallback($r)
    {
    	$this->updateLocalConfig($r, FALSE);
    }
    
    protected static function _fetchSmartyTemplate($sTemplate, $vars)
    {
    	$smarty = new Smarty();
        $smarty->template_dir = ENDPOINT_DIR.'/tpl';
        $smarty->compile_dir = '/var/www/html/var/templates_c';
        $smarty->assign($vars);
        return $smarty->fetch($sTemplate);
    }
    
    protected function _writeSmartyTemplate($sTemplate, $vars, $sOutput)
    {
    	return file_put_contents($sOutput, self::_fetchSmartyTemplate($sTemplate, $vars));
    }
}
?>