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
  $Id: openfireWrapper.php,v 1.0 2007/10/30 10:20:03 bmacias Exp $ */

include_once("libs/misc.lib.php");
include_once("configs/default.conf.php");
load_language();

$style = "<style type='text/css'>
                .moduleTitle {
                    padding: 4px 4px 4px 4px;
                    color: #444;
                    background-color: #ffffff;
                      background-image: url(/images/bggrisForm.gif); 
                    color: #990033;
                    FONT-FAMILY: verdana, arial, helvetica, sans-serif;
                    FONT-SIZE: 16px;
                    FONT-WEIGHT: bold;
                }
              </style>";

$tabla_ini = $style."
              <table class='table_data' border='0' cellspacing='6' cellpading='6' align='center'  width='100%'>
                    <tr class='moduleTitle'>
                        <td class='moduleTitle' align='center'>";
$tabla_fin ="          </td>
                    </tr>
              </table>";


//PASO 1
// Revisar si el openfire esta corriendo
exec("sudo /sbin/service webmin status", $arrSalida, $var);
$statusOpenfire = 'on';

exec("echo 'salida:".print_r($arrSalida,true)."' >> /tmp/webmin");
foreach($arrSalida as $linea) { //obtengo el estado de webmin
    if(preg_match("/stopped/", $linea)) {
        $statusOpenfire = 'off';
        break;
    }
}

//PASO 2
if($statusOpenfire == 'off' && isset($_GET['accion']) && $_GET['accion']=='activar') { //no activo openfire y decide activarlo
    exec("sudo /sbin/chkconfig --level 2345 webmin on",$arrSalida, $var);
    if($var==0){
        exec("sudo /sbin/service webmin start",$arrSalida,$var);
        if($var==0){
            sleep(10);
            header("Location: https://".$_GET['IP'].":".$_GET['PORT']);
        }
        else{
            echo $tabla_ini.$arrLang['ERROR'].$tabla_fin;
        }
    }
    else{
        echo $tabla_ini.$arrLang['ERROR'].$tabla_fin;
    }
}
else if($statusOpenfire == 'off') { 
    echo $tabla_ini.$arrLang['The service Webmin No running']."<a href='webminWrapper.php?IP={$_GET['IP']}&PORT={$_GET['PORT']}&accion=activar'>".$arrLang['click here']."</a>".$tabla_fin;
}
else{ //esta activo openfire
    header("Location: https://".$_GET['IP'].":".$_GET['PORT']);
}
?>
