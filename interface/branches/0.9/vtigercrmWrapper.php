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
  $Id: openfireWrapper.php,v 1.0 2007/10/30 10:20:03 bmacias Exp $ */

include_once("libs/misc.lib.php");
include_once("configs/default.conf.php");
include_once "libs/paloSantoDB.class.php";

$usuario = "root";
$clave   = "eLaStIx.2oo7";
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
$pDB = new paloDB("mysql://$usuario:$clave@localhost/information_schema");
if(!empty($pDB->errMsg)) {
    echo $tabla_ini.$arrLang['ERROR']." DB: ".$pDB->errMsg.$tabla_fin;
}

$sql = "select count(*) existe from tables where table_schema='vtigercrm503'";
$result = $pDB->getFirstRowQuery($sql,true);

//PASO 2
if(is_array($result) && count($result) > 0){
    if($result['existe']==0 && $_GET['accion']=='crear'){ // no existe la base completamente 
        // ejecutar comanado para crear la base de datos.
        exec("/usr/bin/mysql --user=$usuario --password=$clave < /var/www/html/schema.vtiger", $arrSalida, $var); 
        if($var==0){
            sleep(5);
            header("Location: ".$_GET['URL']);
        }
        else{
            echo $tabla_ini.$arrLang['ERROR'].$tabla_fin;
        }
    }
    else if($result['existe']==0){
        echo $tabla_ini.$arrLang['The vTiger installation is almost done. To complete it please']."<a href='vtigercrmWrapper.php?IP={$_GET['IP']}&URL={$_GET['URL']}&accion=crear'>".$arrLang['click here']."</a>".$tabla_fin;
    }
    else{  //si existe la base de datos
        header("Location: ".$_GET['URL']);
    }
}
else{
    echo $tabla_ini.$arrLang['ERROR']." DB: ".$pDB->errMsg.$tabla_fin;
}
?>