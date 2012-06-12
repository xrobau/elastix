<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2007 Palosanto Solutions S. A.                         |
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
  $Id: index.php,v  $ */

require_once "libs/paloSantoDB.class.php";

function _moduleContent($smarty, $module_name)
{
    require_once "libs/misc.lib.php";
    require_once "libs/paloSantoForm.class.php";
    require_once "libs/paloSantoConfig.class.php";
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    global $arrConf;
    global $arrLang;
    //folder path for custom templates

    $errMsg="";
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    //$sContenido="en construcción: $local_templates_dir";

    $smarty->assign("title", $arrLang["Echo Canceller"]);
    $smarty->assign("status_label", $arrLang["Status"]);

    //print_r($_POST);
    if (isset($_POST['action'])) {

        if (isset($_POST['accion']) && $_POST['accion']=="activate" ) {

            exec("sudo -u root service oslec start",$output,$retval);  

        
        } elseif (isset($_POST['accion']) && $_POST['accion']=="inactivate") {

            exec("sudo -u root service oslec stop",$output,$retval);   
        }

//        print "<pre>";print_r($output);print "</pre>";

    }
    #JMA: obtener el estado actual
    $output=null;
    $status=1; # por defecto inactivo
    exec("sudo -u root service oslec status",$output,$retval);    

    $status = $retval;
   // if ($retval<>0) //no se pudo ejecutar el comando
//        $errMsg= "Could not get oslec status";
 //   else {
  //      print "<pre>";print_r($output);print "</pre>";

  /*      switch ($output[0])  {
            case "Oslec module is loaded and active":
                    $status=0; #JMA: Activo. Si el módulo está cargado y el contenido del archivo /proc/oslec/mode es un numero mayor a 0
            break;
            case "Oslec module is loaded and inactive":
                    $status=2; #JMA: inactivo. Si el módulo está cargado y el contenido del archivo /proc/oslec/mode es 0
            break;
            case "Oslec module is not loaded":
                    $status=1; #JMA: inactivo. Si el módulo no está cargado
            break;
            default:
                    $status=1; #JMA: inactivo
            break;
        }
*/


    //}


    if ($status==0){
        $output=null;
        exec("sudo -u root service oslec info",$output,$retval);    
        if ($retval<>0) //no se pudo ejecutar el comando
            $errMsg= $arrLang["Could not get oslec info"];
        else {
            //print_r($output);
            $texto="";
            foreach ($output as $linea) {
                if (!empty($linea))
                    $texto.=trim($linea)."\n";
            }
            $smarty->assign("STATS", $texto);    
        }

        $smarty->assign("info", $arrLang["Info"]);    
        $smarty->assign("accion", "inactivate");    
        $smarty->assign("action", $arrLang["Inactivate"]);    
        $smarty->assign("status", $arrLang["Active"]);   
    }
    else  {
        $smarty->assign("accion", "activate");    
        $smarty->assign("action", $arrLang["Activate"]);    
        $smarty->assign("status", $arrLang["Inactive"]);  
    }

    if (!empty($errMsg)) {
        $smarty->assign("ERROR_MSG", "$errMsg");
    }

    
    $sContenido = $smarty->fetch("$local_templates_dir/canceller.tpl");
    return $sContenido;

}
?>
