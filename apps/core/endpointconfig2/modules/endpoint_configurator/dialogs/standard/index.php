<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
  | http://www.elastix.com                                               |
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
  | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

class Dialog_Standard
{
	static function templateContent($smarty, $module_name, $local_templates_dir)
    {
        $smarty->assign(array(
            'DIALOG_STANDARD_TITLE_INFORMATION'     =>  _tr('Information'),
            'DIALOG_STANDARD_TITLE_ACCOUNTS'        =>  _tr('Accounts'),
            'DIALOG_STANDARD_TITLE_NETWORK'         =>  _tr('Network'),
            'DIALOG_STANDARD_TITLE_CREDENTIALS'     =>  _tr('Custom credentials'),
            'DIALOG_STANDARD_TITLE_PROPERTIES'      =>  _tr('Custom endpoint properties'),
            'DIALOG_STANDARD_LBL_UNKNOWN'           =>  _tr('(unknown)'),
            'DIALOG_STANDARD_LBL_YES'               =>  _tr('Yes'),
            'DIALOG_STANDARD_LBL_NO'                =>  _tr('No'),
            'DIALOG_STANDARD_LBL_MANUFACTURER'      =>  _tr('Manufacturer'),
            'DIALOG_STANDARD_LBL_MODEL'             =>  _tr('Model'),
            'DIALOG_STANDARD_LBL_UNKNOWN_MODEL'     =>  _tr('(unknown/not detected)'),
            'DIALOG_STANDARD_LBL_MAX_SIP_ACCOUNTS'  =>  _tr('Maximum number of SIP accounts'),
            'DIALOG_STANDARD_LBL_MAX_IAX2_ACCOUNTS' =>  _tr('Maximum number of IAX2 accounts'),
            'DIALOG_STANDARD_LBL_MAC'               =>  _tr('MAC'),
            'DIALOG_STANDARD_LBL_CURRENT_IP'        =>  _tr('Current IP'),
            'DIALOG_STANDARD_TOOLTIP_DYNIP'         =>  _tr('This flag is set if phone can get an IP address via DHCP'),
            'DIALOG_STANDARD_LBL_DYNIP'             =>  _tr('Dynamic IP supported'),
            'DIALOG_STANDARD_TOOLTIP_STATICIP'      =>  _tr('This flag is set if phone can be configured to use a static IP address'),
            'DIALOG_STANDARD_LBL_STATICIP'          =>  _tr('Static IP supported'),
            'DIALOG_STANDARD_TOOLTIP_VLAN'          =>  _tr('This flag is set if phone supports QoS with VLAN'),
            'DIALOG_STANDARD_LBL_VLAN'              =>  _tr('VLAN supported'),
            'DIALOG_STANDARD_TOOLTIP_STATICPROV'    =>  _tr('This flag is set if phone can query its configuration without cooperation of DHCP'),
            'DIALOG_STANDARD_LBL_STATICPROV'        =>  _tr('Static provisioning supported'),
            'DIALOG_STANDARD_UNASSIGNED_ACCOUNTS'   =>  _tr('Unassigned accounts'),
            'DIALOG_STANDARD_ASSIGNED_ACCOUNTS'     =>  _tr('Assigned accounts'),
            'DIALOG_STANDARD_LBL_PROPERTIES'        =>  _tr('Properties for'),
            'DIALOG_STANDARD_LBL_NOACCOUNTS'        =>  _tr('No account selected.'),
            'DIALOG_STANDARD_LBL_DYNIP'             =>  _tr('Dynamic IP (DHCP)'),
            'DIALOG_STANDARD_LBL_STATICIP'          =>  _tr('Static IP'),
            'DIALOG_STANDARD_LBL_STATIC_NETATTR'    =>  _tr('Static network attributes'),
            'DIALOG_STANDARD_STATIC_IP'             =>  _tr('IP'),
            'DIALOG_STANDARD_STATIC_NETMASK'        =>  _tr('Network mask'),
            'DIALOG_STANDARD_STATIC_GW'             =>  _tr('Gateway'),
            'DIALOG_STANDARD_STATIC_DNS1'           =>  _tr('Primary DNS'),
            'DIALOG_STANDARD_STATIC_DNS2'           =>  _tr('Secondary DNS'),
            'DIALOG_STANDARD_TELNET_USER'           =>  _tr('Telnet username'),
            'DIALOG_STANDARD_TELNET_PASS'           =>  _tr('Telnet password'),
            'DIALOG_STANDARD_HTTP_USER'             =>  _tr('HTTP username'),
            'DIALOG_STANDARD_HTTP_PASS'             =>  _tr('HTTP password'),
            'DIALOG_STANDARD_SSH_USER'              =>  _tr('SSH username'),
            'DIALOG_STANDARD_SSH_PASS'              =>  _tr('SSH password'),
            'DIALOG_STANDARD_PROPERTIES_MESSAGE'    =>  _tr('DIALOG_STANDARD_PROPERTIES_MESSAGE'),
            'DIALOG_STANDARD_LBL_PROPERTY'          =>  _tr('Property'),
            'DIALOG_STANDARD_LBL_VALUE'             =>  _tr('Value'),
            'DIALOG_STANDARD_LBL_REGISTERED_AT'     =>  _tr('Registered at'),
            'DIALOG_STANDARD_TOOLTIP_REGISTERED'    =>  _tr('This account is in use by an unsupported/unscanned host'),
        ));
    	return $smarty->fetch("$local_templates_dir/../../dialogs/standard/tpl/dialog.tpl");
    }
    
    static function handleJSON_loadDetails($smarty, $module_name, $local_templates_dir, $dlglist)
    {
        require_once "modules/$module_name/dialogs/standard/EndpointManager_Standard.class.php";
                
        $respuesta = array(
            'status'    =>  'success',
            'message'   =>  '(no message)',
        );
        
        // Validar ID de endpoint y cargar detalles
        if (!isset($_REQUEST['id_endpoint']) || !ctype_digit($_REQUEST['id_endpoint'])) {
        	$respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Invalid endpoint ID');
        } else {
        	$endpoint = new EndpointManager_Standard();
            $details = $endpoint->cargarDetalles($_REQUEST['id_endpoint']);
            if (!is_array($details)) {
                $respuesta['status'] = 'error';
                $respuesta['message'] = $endpoint->getErrMsg();
            } else {
            	$respuesta['details'] = $details;
            }
        }
    
        $json = new Services_JSON();
        Header('Content-Type: application/json');
        return $json->encode($respuesta);
    }
    
    static function handleJSON_saveDetails($smarty, $module_name, $local_templates_dir, $dlglist)
    {
        require_once "modules/$module_name/dialogs/standard/EndpointManager_Standard.class.php";
                
        $respuesta = array(
            'status'    =>  'success',
            'message'   =>  '(no message)',
        );
        
        // Validar ID de endpoint y cargar detalles
        if (!isset($_REQUEST['id_endpoint']) || !ctype_digit($_REQUEST['id_endpoint'])) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Invalid endpoint ID');
        } else {
            $endpoint = new EndpointManager_Standard();
            $sFechaModificacion = $endpoint->guardarDetalles($_REQUEST['id_endpoint'], $_REQUEST); 
            if (is_null($sFechaModificacion)) {
                $respuesta['status'] = 'error';
                $respuesta['message'] = $endpoint->getErrMsg();
            } else {
            	$respuesta['last_modified'] = $sFechaModificacion;
            }
        }
    
        $json = new Services_JSON();
        Header('Content-Type: application/json');
        return $json->encode($respuesta);
    }
}

?>