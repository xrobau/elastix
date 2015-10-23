<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

require_once "libs/paloSantoValidar.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // Textos estáticos
    $smarty->assign(array(
        'APPLY_CHANGES'     =>  _tr('Save'),
        'EMAIL_RELAY_MSG'   =>  _tr('These IPs are allowed to send faxes through Elastix.  You must insert one IP per row.  We recommend keeping localhost and 127.0.0.1  in the configuration because some processes could need them.'),
        'title'             =>  _tr('Clients allowed to send faxes'),
    ));
    $smarty->assign("icon", "/modules/$module_name/images/fax_fax_clients.png");

    if (isset($_POST['update_hosts']) ) {
        $val = new PaloValidar();
        $bGuardar = TRUE;

        // Validar toda la lista de IPs
        $_POST['lista_hosts'] = trim($_POST['lista_hosts']);
        $arrHostsFinal = empty($_POST['lista_hosts']) ? array() : explode("\n", $_POST['lista_hosts']);
        if (count($arrHostsFinal) <= 0) {
        	$bGuardar = FALSE;
            $smarty->assign(array(
                'mb_title'      =>  _tr('Error'),
                'mb_message'    =>  _tr('No IP entered, you must keep at least the IP 127.0.0.1'),
            ));
        } else foreach (array_keys($arrHostsFinal) as $k) {
        	$arrHostsFinal[$k] = trim($arrHostsFinal[$k]);
            $bGuardar = $bGuardar && (
                $arrHostsFinal[$k] == 'localhost' || 
                $val->validar(_tr('IP').' '.$arrHostsFinal[$k], $arrHostsFinal[$k], 'ip')
            ); 
        }

        // Formato de errores de validación
        if ($val->existenErroresPrevios()) {
            $msgErrorVal = _tr('Validation Error').'<br/><br/>';
            foreach($val->arrErrores as $nombreVar => $arrVar) {
                $msgErrorVal .= "<b>$nombreVar</b>: {$arrVar['mensaje']}<br/>";
            }
            $smarty->assign(array(
                'mb_title'      =>  _tr('Error'),
                'mb_message'    =>  $msgErrorVal,
            ));
            $bGuardar = FALSE;
        } 

        if ($bGuardar) {
            // Si no hay errores de validacion entonces ingreso las redes al archivo de host
            $output = NULL; $retval = NULL;
            exec('/usr/bin/elastix-helper faxconfig --setfaxhosts '.implode(' ', $arrHostsFinal).' 2>&1', $output, $retval);
            if ($retval == 0) {
            	$smarty->assign(array(
                    'mb_title'      =>  _tr('Message'),
                    'mb_message'    =>  _tr('Configuration updated successfully'),
                ));
            } else {
                $smarty->assign(array(
                    'mb_title'      =>  _tr('Error'),
                    'mb_message'    =>  _tr('Write error when writing the new configuration.').
                                        ' '.implode(' ', $output),
                ));
            }
        }
    }

    // Listar IPs actualmente permitidas
    $output = NULL; $retval = NULL;
    exec('/usr/bin/elastix-helper faxconfig --getfaxhosts 2>&1', $output, $retval);
    if ($retval != 0) {
    	$smarty->assign(array(
            'mb_title'      =>  _tr('Error'),
            'mb_message'    =>  _tr('Could not read the clients configuration.'),
        ));
    }
    $smarty->assign("RELAY_CONTENT", implode("\n", $output));
    return $smarty->fetch("file:$local_templates_dir/form_hosts.tpl");
}
?>
