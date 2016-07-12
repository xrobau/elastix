<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-14                                               |
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
  $Id: index.php,v 1.1 2012-01-17 11:01:49 Manuel Olvera molvera@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoSetup.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);


    include_once "modules/$module_name/libs/forms/setupForm.class.php";
    include_once "modules/$module_name/libs/parseFile.class.php";

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $smarty->assign('module_name',$module_name);

    //conexion resource
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);

    //actions
    $action = getAction();
    $content = "";
    $result = FALSE;

    switch($action){
        case 'up_service':
        case 'down_service':
            $result = updateService($smarty, $pDB, $arrConf);
        default: // view_form
            $content = viewFormSetup($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $result);
            break;
    }
    return $content;
}


function updateService($smarty, &$pDB, $arrConf){
    $bRunning = FALSE;
    $bExito = TRUE;
    $errMsg = '';
    $conf_path_file = $arrConf['collectd_conf_path'];
    if(!file_exists($conf_path_file)){
        if(array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)){//solicitud por ajax
            include_once 'libs/paloSantoJSON.class.php';
            $json = new PaloSantoJSON();
            $json->set_message(array(
                'button' => _tr(setupForm::$buttons['nf']),
                'status' => setupForm::$texts['nf'],
                'class'  => setupForm::$class_html['nf'],
            ));
            header('Content-type: application/json');
            print $json->createJSON();
            exit(0);
        }else
            return FALSE;
    }

    $pSetup = new paloSantoSetup($pDB, $conf_path_file);
    $bRunning = $pSetup->isRunningService();

    $action = getParameter('action');

    if($bRunning && 'down'== $action && !$pSetup->stopService()){//Si no hay problemas y si estando arriba el servicio se desea detener..
        $bExito = FALSE;
        $errMsg = $pSetup->errMsg;
    }if(!$bRunning && 'up'== $action && !$pSetup->startService()){//Si no hay problemas y si estando abajo el servicio se desea inciar..
        $bExito = FALSE;
        $errMsg = $pSetup->errMsg;
    }
    if(array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)){//solicitud por ajax
        include_once 'libs/paloSantoJSON.class.php';
        $json = new PaloSantoJSON();

        if($bExito){
            $json->set_message(array(
                'button' => _tr($bRunning?setupForm::$buttons['nr']:setupForm::$buttons['r']),
                'status' => $bRunning?setupForm::$texts['nr'] : setupForm::$texts['r'],
                'class'  => setupForm::$class_html['nr'],
            ));
        }else{
            $json->set_status('ERROR');
            $json->set_error($errMsg);
        }

        header('Content-type: application/json');
        print $json->createJSON();
        exit(0);
    }
    if(!$bExito){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", $errMsg);
    }else{
        $smarty->assign("mb_title", _tr("Great"));
        $smarty->assign("mb_message", _tr('Last changes was done successfully'));
    }
    return $bExito;
}

/**
 *
 * @param Smarty $smarty
 * @param string $module_name
 * @param string $local_templates_dir
 * @param paloDB $pDB
 * @param array $arrConf
 * @return string
 */
function viewFormSetup($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $update_service = FALSE)
{
    $conf_path_file = $arrConf['collectd_conf_path'];

    $oForm = new setupForm($smarty);
    $pSetup = new paloSantoSetup($pDB, $conf_path_file);
    $oForm->setEditMode();
    
    $bExito = TRUE;
    $bInstalled = file_exists($arrConf['collectd_conf_path']);
    $bRegistred = FALSE;
    $bUpdateKey = FALSE;
    $bRunning = $pSetup->isRunningService();
    $register = FALSE;
    $_DATA  = array();

    $oForm->reallyHasServerKey();
    if($bInstalled){
        try{
            $oKeyFile = new parseFile($arrConf['elastix_key_server'], parseFile::NO_PARSE);
            if($oKeyFile->offsetExists(0)){
                $_DATA['server_key'] = trim($oKeyFile[0]);//Obtengo el server key del archivo
                $bRegistred = !empty($_DATA['server_key']);

                //Si existe dejar el formulario sin opción de edición del valor del campo
                if($bRegistred){
                    $register = TRUE;
                    $serverkey=$pSetup->getServerKeyFromFile();
                    if($serverkey===false){
                        $smarty->assign("mb_title", _tr("Error"));
                        $smarty->assign("mb_message", _tr("Couldn't be retrieved info from /etc/collectd.conf file. ").$pSetup->errMsg);
                    }else{
                        if($_DATA['server_key']!=$serverkey){
                            $bUpdateKey=true;
                            $exito=$pSetup->saveServerKeyOnFile($_DATA['server_key']);
                            if($exito===false){
                                $smarty->assign("mb_title", _tr("Error"));
                                $smarty->assign("mb_message", _tr("Couldn't be established serverkey in  /etc/collectd.conf file. ").$pSetup->errMsg);
                            }
                        }
                    }
                }
            }
            unset($oKeyFile);
        }catch(InvalidArgumentException $e){
            
        }//File not found
    }else{
        $_DATA['enable'] = 'off';
        $oForm->serviceNotEnable();
    }

    if(!$bInstalled){
        $smarty->assign("mb_title", _tr("Installer error"));
        $smarty->assign("mb_message", _tr('Monitoring service was not detected'));
    }else{
        if(!$register){
            $oForm->noRegister();
            $_DATA['server_key']="?";
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", _tr("In order to use this service you must register you server first.  Do this from link register"));
            //if service is running then stop service
            if($bRunning){
                $pSetup->stopService();
            }
        }else{
            if($bUpdateKey){
                if($bRunning){
                    $pSetup->stopService();
                }
            }else{
		if($bRunning){
        	    $oForm->isRunning();
    		}
            }
        }
    }
        
    $content = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Setup"), $_DATA);

    return $content;
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("new_open"))
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else if(getParameter("action")=="up" || !is_null(getParameter("action_nr"))){
        $_POST['action'] = 'up';
        return "up_service";
    }else if(getParameter("action")=="down" || !is_null(getParameter("action_r"))){
        $_POST['action'] = 'down';
        return "down_service";
    }else
        return "report"; //cancel
}

if(!function_exists('_tr')){
    function _tr($value){
        global $arrLang;
        if(array_key_exists($value,$arrLang))   return $arrLang[$value];
        else                                    return '';
    }
}
