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
  $Id: index.php,v 1.2 2007/07/07 22:50:39 admin Exp $ */

function _moduleContent($smarty, $module_name)
{
    require_once "libs/misc.lib.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $arrSysInfo = obtener_info_de_sistema();

    //print_r($arrSysInfo);

    $cpu_usage  = $arrSysInfo['CpuUsage'];
    $mem_usage  = ($arrSysInfo['MemTotal'] - $arrSysInfo['MemFree'] - $arrSysInfo['Cached'] - $arrSysInfo['MemBuffers'])/$arrSysInfo['MemTotal'];
    $swap_usage = ($arrSysInfo['SwapTotal'] - $arrSysInfo['SwapFree'])/$arrSysInfo['SwapTotal'];

    $smarty->assign("cpu_info", $arrSysInfo['CpuVendor'] . " " . $arrSysInfo['CpuModel']);
    $smarty->assign("cpu_usage",  "<img src='images/bar.php?prog=$cpu_usage' border='0'> &nbsp;&nbsp;" . 
                    number_format($arrSysInfo['CpuUsage']*100, 2) . "% used of " . number_format($arrSysInfo['CpuMHz'], 2) . " MHz");
    $smarty->assign("mem_usage",  "<img src='images/bar.php?prog=$mem_usage' border='0'> &nbsp;&nbsp;" . 
                    number_format($mem_usage*100, 2) . "% used of " . number_format($arrSysInfo['MemTotal']/1024, 2) . " Mb");
    $smarty->assign("swap_usage",  "<img src='images/bar.php?prog=$swap_usage' border='0'> &nbsp;&nbsp;" . 
                    number_format($swap_usage*100, 2) . "% used of " . number_format($arrSysInfo['SwapTotal']/1024, 2) . " Mb");
    $smarty->assign("uptime",  $arrSysInfo['SysUptime']);

    $arrParticiones = array();
    $i=0;
    foreach($arrSysInfo['particiones'] as $particion) {
        if(ereg("^/dev/(.*)$", trim($particion['fichero']), $arrReg)) {
            $arrParticiones[$i]['fichero'] = strtoupper($arrReg[1]);
        } else {
            $arrParticiones[$i]['fichero'] = $particion['fichero'];
        }
        $arrParticiones[$i]['total_bloques'] = number_format($particion['num_bloques_total'] / 1024 / 1024, 2);
        $arrParticiones[$i]['punto_montaje'] = $particion['punto_montaje'];
        if(ereg("^([[:digit:]]{1,2}(\.[[:digit:]]{1,4})?)%$", trim($particion['uso_porcentaje']), $arrReg)) {
            $arrParticiones[$i]['uso'] = $arrReg[1];
        } else {
            $arrParticiones[$i]['uso'] = NULL;
        }
        $i++;
    }


    $smarty->assign("arrParticiones",  $arrParticiones);
    //asignar los valores del idioma
    $smarty->assign("SYSTEM_INFO_TITLE1",  $arrLang['System Resources']);
    $smarty->assign("CPU_INFO_TITLE",  $arrLang['CPU Info']);
    $smarty->assign("UPTIME_TITLE",  $arrLang['Uptime']);
    $smarty->assign("CPU_USAGE_TITLE",  $arrLang['CPU usage']);
    $smarty->assign("MEMORY_USAGE_TITLE",  $arrLang['Memory usage']);
    $smarty->assign("SWAP_USAGE_TITLE",  $arrLang['Swap usage']);
    $smarty->assign("SYSTEM_INFO_TITLE2",  $arrLang['Hard Drives']);
    $smarty->assign("PARTICION_NAME_TITLE",  $arrLang['Partition Name']);
    $smarty->assign("CAPACITY_TITLE",  $arrLang['Capacity']);
    $smarty->assign("USAGE_TITLE",  $arrLang['Usage']);
    $smarty->assign("MOUNT_POINT_TITLE",  $arrLang['Mount point']);

    return $smarty->fetch("file:$local_templates_dir/sysinfo.tpl");
}
?>
