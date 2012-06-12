<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoValidar.class.php";

    
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
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

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    $msgErrorVal = "";
    $contenido='';
    $bGuardar=TRUE;
    $conf_hosts = "/var/spool/hylafax/etc/hosts.hfaxd";
    $val = new PaloValidar();
    if(isset($_POST['update_hosts']) ) {
        $arrHostsFinal = array();
        $in_lista_hosts = trim($_POST['lista_hosts']);
        if(!empty($in_lista_hosts)) {
            $arrHosts = explode("\n", $in_lista_hosts);
            // Ahora valido que las redes estén en formato correcto
            if(is_array($arrHosts) and count($arrHosts)>0) {
                foreach ($arrHosts as $ip_host) {
                    //validar
                    $ip_host = trim($ip_host);
                    if ($ip_host!='localhost'){
                        if($val->validar("$arrLang[IP] $ip_host", $ip_host, "ip"))
                            $arrHostsFinal[]=$ip_host;
                        else
                            $bGuardar=FALSE;
                    }
                    else{
                        $arrHostsFinal[]=$ip_host;
                    }
                }
            } else {
                $smarty->assign("mb_title",$arrLang["Error"]);
                $smarty->assign("mb_message", $arrLang["No IP entered, you must keep at least the IP 127.0.0.1"]);
                $bGuardar=FALSE;
            }
        } else {
            // El textarea esta vacia
            $bGuardar=FALSE;
            $smarty->assign("mb_title",$arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["No IP entered, you must keep at least the IP 127.0.0.1"]);
        }

        if($val->existenErroresPrevios()) {
            foreach($val->arrErrores as $nombreVar => $arrVar) {
                $msgErrorVal .= "<b>" . $nombreVar . "</b>: " . $arrVar['mensaje'] . "<br>";

            }
            $smarty->assign("mb_title",$arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["Validation Error"]."<br><br>$msgErrorVal");
            $bGuardar=FALSE;
        } 

        if($bGuardar) {
            // Si no hay errores de validacion entonces ingreso las redes al archivo de host
            if(file_exists($conf_hosts)) {
                exec("sudo -u root chown asterisk.asterisk $conf_hosts");
                if($fh = fopen($conf_hosts, "w")){
                    if(is_array($arrHostsFinal) && count($arrHostsFinal) > 0){
                        foreach($arrHostsFinal as $key => $line){
                            fputs($fh,$line."\n");
                        }
                        $smarty->assign("mb_title",$arrLang["Message"]);
                        $smarty->assign("mb_message", $arrLang["Configuration updated successfully"]);
                    }
                    else{
                        $smarty->assign("mb_title",$arrLang["Error"]);
                        $smarty->assign("mb_message", $arrLang["Write error when writing the new configuration."]);
                    }
                }
                else{
                    $smarty->assign("mb_title",$arrLang["Error"]);
                    $smarty->assign("mb_message", $arrLang["Write error when writing the new configuration."]);
                }
                fclose($fh);
                exec("sudo -u root chown uucp.uucp $conf_hosts");
            }
        }

    }

    if(file_exists($conf_hosts)) {
        exec("sudo -u root chown asterisk.asterisk $conf_hosts");
        if($fh = @fopen($conf_hosts, "r")) {
            while($linea = fgets($fh, 1024)) {
                $contenido .= $linea;
            }
            fclose($fh);
        } else {
            // Si no se puede abrir el archivo se debe mostrar mensaje de error
            $smarty->assign("mb_title",$arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["Could not read the clients configuration."]);
        }
        exec("sudo -u root chown uucp.uucp $conf_hosts");
    } else {
        // Si el archivo no existe algo anda mal.
        // Por ahora lo creo al archivo vacio
        exec("/sg/bin/sudo -u root touch $conf_hosts");
    } 




    $hosts_msg=$arrLang["These IPs are allowed to send faxes through Elastix.  You must insert one IP per row.  We recommend keeping localhost and 127.0.0.1  in the configuration because some processes could need them."];
    $smarty->assign("APPLY_CHANGES",$arrLang["Apply changes"]);
    $smarty->assign("EMAIL_RELAY_MSG",$hosts_msg);
    $smarty->assign("RELAY_CONTENT", $contenido);
    $smarty->assign("title",$arrLang["Clients allowed to send faxes"]);
    $contenidoModulo=$smarty->fetch("file:$local_templates_dir/form_hosts.tpl");
    return $contenidoModulo;
}
?>
