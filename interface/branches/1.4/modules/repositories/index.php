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
  $Id: repositories.php $ */

require_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoGrid.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/PaloSantoRepositories.class.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $contenidoModulo = listRepositories($smarty, $module_name, $local_templates_dir,$arrConfig);

    return $contenidoModulo;
}

function listRepositories($smarty, $module_name, $local_templates_dir,$arrConfig) {

    global $arrLang;
    $oRepositories = new PaloSantoRepositories();
    $arrReposActivos=array();
    if(isset($_POST['submit_aceptar'])){
        foreach($_POST as $key => $value){
            if(substr($key,0,5)=='repo-')
                $arrReposActivos[]=substr($key,5);
        }
        $oRepositories->setRepositorios($arrConfig['ruta_repos'],$arrReposActivos);
    }

    $arrRepositorios = $oRepositories->getRepositorios($arrConfig['ruta_repos']);
    $limit  = 50;
    $total  = count($arrRepositorios); 
    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $smarty->assign("url","?menu=".$module_name);
    $arrData = array();
    $version = $oRepositories->obtenerVersionDistro();
    if (is_array($arrRepositorios)) {
        for($i=$offset;$i<$end;$i++){
            $activo = "";
            if($arrRepositorios[$i]['activo'])
                $activo="checked='checked'";
             $arrData[] = array(
                            "<input $activo name='repo-".$arrRepositorios[$i]['id']."' type='checkbox'>",
                            str_replace("\$releasever",$version,$arrRepositorios[$i]['name']),);
        }
    }

    $arrGrid = array("title"    => $arrLang["Repositories"],
        "icon"     => "images/list.png",
        "width"    => "99%",
        "start"    => ($total==0) ? 0 : $offset + 1,
        "end"      => $end,
        "total"    => $total,
        "columns"  => array(0 => array("name"      => $arrLang["Choice"],
                                       "property1" => ""),
                            1 => array("name"      => $arrLang["Name"], 
                                       "property1" => "")));

    $oGrid->showFilter( "<input type='submit' name='submit_aceptar' value='{$arrLang['Accept']}' class='button' />");
    $contenidoModulo  = "<form style='margin-bottom:0;' method='POST' action='?menu=$module_name'>";
    $contenidoModulo .= $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    $contenidoModulo .= "</form>";
    return $contenidoModulo;
}
?>