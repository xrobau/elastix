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

//LIBRERIA GRAFICA
include_once "libs/paloSantoGraphImage.lib.php";

function _moduleContent($smarty, $module_name)
{
    require_once "libs/misc.lib.php";

    //include module files
    include_once "modules/$module_name/libs/paloSantoSysInfo.class.php";
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

    // Create actual graphic when requested
    if (isset($_GET['action']) && $_GET['action'] == 'image' && isset($_GET['image'])) {
        executeImage($module_name, $_GET['image']);
        return '';
    }

    $oPalo = new paloSantoSysInfo();
    $arrSysInfo = $oPalo->getSysInfo();


    //CPU INFO
    $smarty->assign("cpu_info", $arrSysInfo['CpuVendor'] . " " . $arrSysInfo['CpuModel']);

    //CPU USAGE
    $img = getImage_CPU_Usage($module_name);
    $inf = number_format($arrSysInfo['CpuUsage']*100, 2)."{$arrLang['% used of']} ".number_format($arrSysInfo['CpuMHz'], 2)." MHz";
    $smarty->assign("cpu_usage", $img."&nbsp;&nbsp;&nbsp;".$inf);

    //MEMORY USAGE
    $mem_usage  = ($arrSysInfo['MemTotal'] - $arrSysInfo['MemFree'] - $arrSysInfo['Cached'] - $arrSysInfo['MemBuffers'])/$arrSysInfo['MemTotal'];
    $img = getImage_MEM_Usage($module_name);
    $inf = number_format($mem_usage*100, 2)."{$arrLang['% used of']} ".number_format($arrSysInfo['MemTotal']/1024, 2)." Mb";
    $smarty->assign("mem_usage", $img."&nbsp;&nbsp;&nbsp;".$inf);

    //SWAP USAGE
    $swap_usage = ($arrSysInfo['SwapTotal'] - $arrSysInfo['SwapFree'])/$arrSysInfo['SwapTotal'];
    $img = getImage_Swap_Usage($module_name);
    $inf = number_format($swap_usage*100, 2)."{$arrLang['% used of']} ".number_format($arrSysInfo['SwapTotal']/1024, 2)." Mb";
    $smarty->assign("swap_usage", $img."&nbsp;&nbsp;&nbsp;".$inf );

    //UPTIME
    $smarty->assign("uptime",  $arrSysInfo['SysUptime']);

    $arrParticiones = array();
    $i=0;

    $info = buildInfoImage_Discs( $arrSysInfo['particiones'], $module_name);
    $smarty->assign("info", $info);

    //asignar los valores del idioma
    $smarty->assign("SYSTEM_INFO_TITLE1",  $arrLang['System Resources']);
    $smarty->assign("CPU_INFO_TITLE",  $arrLang['CPU Info']);
    $smarty->assign("UPTIME_TITLE",  $arrLang['Uptime']);
    $smarty->assign("CPU_USAGE_TITLE",  $arrLang['CPU usage']);
    $smarty->assign("MEMORY_USAGE_TITLE",  $arrLang['Memory usage']);
    $smarty->assign("SWAP_USAGE_TITLE",  $arrLang['Swap usage']);
    $smarty->assign("SYSTEM_INFO_TITLE2",  $arrLang['Hard Drives']);

    $imagen_hist = getImage_Hit($module_name);
    $smarty->assign("imagen_hist", $imagen_hist);

    return $smarty->fetch("file:$local_templates_dir/sysinfo.tpl");
}

function buildInfoImage_Discs($arrParticiones, $module_name)
{
    Global $arrLang;
    $str = ""; $val = null;
    foreach( $arrParticiones as $key => $particion )
    {
        $val_1 = ( preg_match("/^([[:digit:]]{1,2}(\.[[:digit:]]{1,4})?)%$/", trim($particion['uso_porcentaje']), $arrReg) )
                 ? $arrReg[1]: NULL;
        $val_2 = number_format($particion['num_bloques_total'] / 1024 / 1024, 2);

        $str .=
            "<tr>".
                "<td width='15%'><img src='images/arrow-8.gif'>&nbsp;<b>".$arrLang['Partition Name'].":</b></td>".
                "<td width='35%'><b>".$particion['fichero']."</b></td>".
                "<td width='50%' rowspan='5' align='left'>".getImage_Disc_Usage($module_name, $val_1)."</td>".
            "</tr>".
            "<tr>".
                "<td width='15%'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$arrLang['Capacity'].":</td>".
                "<td width='35%'>".$val_2."GB</td>".
            "</tr>".
            "<tr>".
                "<td width='15%'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$arrLang['Usage'].":</td>".
                "<td width='35%'>".$val_1."%</td>".
            "</tr>".
            "<tr>".
                "<td width='15%'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$arrLang['Mount point']."</td>".
                "<td width='35%'>".$particion['punto_montaje']."</td>".
            "</tr>".
            "<tr>".
                "<td width='15%'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>".
                "<td width='35%'>&nbsp;</td>".
            "</tr>";
    }
    return $str;
}

function executeImage($module_name, $sImg)
{
    $listaImgs = array(
        'CallsMemoryCPU'                                =>  array(null, 'functionCallback'),
        'ObtenerInfo_CPU_Usage'                         =>  array(null, null),
        'ObtenerInfo_MemUsage'                          =>  array(null, null),
        'ObtenerInfo_SwapUsage'                         =>  array(null, null),
        'ObtenerInfo_Particion'                         =>  array(array('percent'), null),
    );
    if (isset($listaImgs[$sImg])) {
        $arrParameters = array();
        if (is_array($listaImgs[$sImg][0])) foreach ($listaImgs[$sImg][0] as $k) {
            $arrParameters[] = isset($_GET[$k]) ? $_GET[$k] : '';
        }
        $callback = is_null($listaImgs[$sImg][1]) ? '' : $listaImgs[$sImg][1];
        displayGraph($module_name, 'paloSantoSysInfo', $sImg, $arrParameters, $callback);
    }
}

function genericImage($module_name, $sGraph, $extraParam = array())
{
    return sprintf('<img alt="%s", src="%s" />', 
        $sGraph,
        construirURL(array_merge(array(
            'menu'      => $module_name,
            'action'    =>  'image',
            'rawmode'   =>  'yes',
            'image'     =>  $sGraph,
            ), $extraParam)));    
}

function getImage_Hit($module_name)
{
    return genericImage($module_name, "CallsMemoryCPU");
}

function getImage_CPU_Usage($module_name)
{
    return genericImage($module_name, "ObtenerInfo_CPU_Usage");
}

function getImage_MEM_Usage($module_name)
{
    return genericImage($module_name, "ObtenerInfo_MemUsage");
}

function getImage_Swap_Usage($module_name)
{
    return genericImage($module_name, "ObtenerInfo_SwapUsage");
}

function getImage_Disc_Usage($module_name, $value)
{
    return genericImage($module_name, "ObtenerInfo_Particion", array('percent' => $value));
}
?>
