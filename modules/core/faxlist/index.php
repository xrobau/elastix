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

function _moduleContent($smarty, $module_name)
{
    include_once "libs/paloSantoFax.class.php";
    include_once "libs/paloSantoGrid.class.php";

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


    $contenidoModulo = listFax($smarty, $module_name, $local_templates_dir);
    return $contenidoModulo;
}

function listFax($smarty, $module_name, $local_templates_dir)
{
    global $arrLang;
    $arrData = array();
    $oFax    = new paloFax();
    $arrFax  = $oFax->getFaxList();

    $end = count($arrFax);
    $arrFaxStatus = $oFax->getFaxStatus();
 
    foreach($arrFax as $fax) {
        $arrTmp    = array();
        $arrTmp[0] = "&nbsp;<a href='?menu=faxnew&action=view&id=" . $fax['id'] . "'>" . $fax['name'] . "</a>";
        $arrTmp[1] = $fax['extension'];
        $arrTmp[2] = $fax['secret'];
        $arrTmp[3] = $fax['email'];
        $arrTmp[4] = $fax['clid_name'] . "&nbsp;";
        $arrTmp[5] = $fax['clid_number'] . "&nbsp;";
        $arrTmp[6] = $arrFaxStatus['ttyIAX' . $fax['dev_id']].' on ttyIAX' . $fax['dev_id'];
        $arrData[] = $arrTmp;
    }
    
    $arrGrid = array("title"    => $arrLang["Virtual Fax List"],
                     "icon"     => "/modules/$module_name/images/kfaxview.png",
                     "width"    => "99%",
                     "start"    => ($end==0) ? 0 : 1,
                     "end"      => $end,
                     "total"    => $end,
                     "columns"  => array(0 => array("name"      => $arrLang["Virtual Fax Name"],
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Fax Extension"], 
                                                    "property1" => ""),
                                         2 => array("name"      => $arrLang["Secret"],
                                                    "property1" => ""),
                                         3 => array("name"      => $arrLang["Destination Email"],
                                                    "property1" => ""),
                                         4 => array("name"      => $arrLang["Caller ID Name"],
                                                    "property1" => ""),
                                         5 => array("name"      => $arrLang["Caller ID Number"],
                                                    "property1" => ""),
                                         6 => array("name"      => $arrLang["Status"],
                                                    "property1" => "")
                                        )
                    );
    
    $oGrid = new paloSantoGrid($smarty);
    return $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
}
?>
