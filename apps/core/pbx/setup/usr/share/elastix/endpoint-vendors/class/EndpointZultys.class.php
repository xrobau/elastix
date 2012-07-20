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

class EndpointZultys extends Endpoint
{
    static protected $_global_serverip = NULL;
    static private $_supported_models = array('ZIP2x1', 'ZIP2x2');

    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Zultys', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
        if (is_null(self::$_global_serverip)) {
        	self::$_global_serverip = $sServerIP;
        } elseif (self::$_global_serverip != $sServerIP) {
        	$this->_log->output('WARN: global server IP is '.self::$_global_serverip.
                ' but endpoint '.$sIP.' requires server IP '.$sServerIP.
                ' - this endpoint might not work correctly.');
        }
    }

    public function setModel($sModel)
    {
        if (in_array($sModel, self::$_supported_models)) {
            $this->_model = $sModel;
            return TRUE;
        }
        return FALSE;
    }


    /**
     * Configuration for Zultys endpoints (global):
     * 
     *  Apparently, each supported model fetches a common file named 
     *  {$MODEL}_common.cfg in the base TFTP directory. Additionally, each
     *  supported phone model gets its own configuration directory.
     */
    public static function updateGlobalConfig()
    {
        // Ensure the required directory structure exists
        foreach (self::$_supported_models as $sModel) {
            // Write out {$MODEL}_common.cfg to tftp directory
            if (FALSE === self::_writeSmartyTemplate(
                'Zultys_global_cfg.tpl',
                array(
                    'SERVER_IP' => self::$_global_serverip,
                    'MODEL'     =>  $sModel,
                ), 
                TFTP_DIR."/{$sModel}_common.cfg")) {
                $this->_log->output('ERR: failed to write '.TFTP_DIR."/{$sModel}_common.cfg");
                return FALSE;
            }

        	// Create directory for model if nonexistent
            $sDir = TFTP_DIR.'/'.$sModel;
            if (!is_dir($sDir)) {
        		if (!mkdir($sDir, 0777, TRUE)) {
                    $this->_log->output('ERR: failed to create directory: '.$sDir);
        			return FALSE;
        		}
        	}
        }
        return TRUE;
    }

    /**
     * Configuration for Zultys endpoints (local):
     * 
     * A file XXXXXXXXXXXX.cfg must be created under the appropriate directory
     * according to the phone model. Here XXXXXXXXXXXX is replaced by the
     * UPPERCASE MAC address of the phone.
     * 
     * Phone auto-reboot is not supported for this vendor.
     */
    public function updateLocalConfig($oConn, $bClosing)
    {
        switch ($this->_step) {
        case 'START':
            // Need to calculate lowercase version of MAC address without colons
            $sUpperMAC = strtoupper(str_replace(':', '', $this->_mac));
            
            // Assign Smarty variables and write out the file
            $r = self::_writeSmartyTemplate(
                'Zultys_local_cfg.tpl',
                array(
                    'ID_DEVICE'         =>  $this->_device_id,
                    'SECRET'            =>  $this->_secret,
                    'DISPLAY_NAME'      =>  $this->_desc,
                ),
                TFTP_DIR."/{$this->_model}/{$sUpperMAC}.cfg");
            if ($r === FALSE) {
                $this->_log->output(
                    'ERR: failed to write to configuration file '.TFTP_DIR."/{$this->_model}/{$sUpperMAC}.cfg");
                $this->_localConfigStatus = ENDPOINT_FAILURE;
                return;
            }
            
            // Sets either success, or AMI_UNREGISTER state
            $this->_unregister();
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