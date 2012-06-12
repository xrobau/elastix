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
  $Id: packages.php $ */

include_once "libs/paloSantoGrid.class.php";
include_once "libs/xajax/xajax.inc.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/PaloSantoPackages.class.php";

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
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $xajax = new xajax();
    $xajax->registerFunction("actualizarRepositorios");
    $xajax->registerFunction("installPaquete");
    $xajax->processRequests();
    $contenidoModulo  = $xajax->printJavascript("libs/xajax/");
    $contenidoModulo .= listPackages($smarty, $module_name, $local_templates_dir,$arrConf);

    return $contenidoModulo;
}

function listPackages($smarty, $module_name, $local_templates_dir,$arrConf) {

    global $arrLang;
    $oPackages = new PaloSantoPackages();

    $submitInstalado = getParametro('submitInstalado');
    $nombre_paquete = getParametro('nombre_paquete');

    $total_paquetes = $oPackages->ObtenerTotalPaquetes($submitInstalado, $arrConf['ruta_yum'], $nombre_paquete);

    $limit = 50;
    $total = $total_paquetes;
    $oGrid = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);
    $end   = ($offset+$limit)<=$total ? $offset+$limit : $total;


    if($submitInstalado =='all'){
        $arrPaquetes = $oPackages->getAllPackages($arrConf['ruta_yum'],$nombre_paquete);
	$arrPaquetes = $oPackages->getDataPagination($arrPaquetes,$limit,$offset);
    }
    else{  //si no hay post por default los instalados
        $arrPaquetes = $oPackages->getPackagesInstalados($arrConf['ruta_yum'],$nombre_paquete, $offset, $limit, $total);
    }


    $url = array(
        'menu'      =>  $module_name,
        'submitInstalado'   =>  $submitInstalado,
        'nombre_paquete'    =>  $nombre_paquete,
    );
    $arrData = array();
    if (is_array($arrPaquetes)) {
        for($i=0;$i<count($arrPaquetes);$i++){
            $estado_paquete = $oPackages->estaPaqueteInstalado($arrPaquetes[$i]['name']);
            $instalar = $arrLang['Updated'];
            $tmpPaquete = $arrPaquetes[$i]['name'];
            if(!$estado_paquete){
                $instalar = "<a href='#'  onclick="."installPackage('$tmpPaquete')".">{$arrLang['Install']}</a>";
            }
            $arrData[] = array(
                            $arrPaquetes[$i]['name'],
                            $arrPaquetes[$i]['summary'],
                            $arrPaquetes[$i]['version'],
                            $arrPaquetes[$i]['release'],
                            $arrPaquetes[$i]['repositorio'],
                            (($estado_paquete)?$arrLang["Package Installed"]:"")." $instalar",
                            );
        }
    }
    $arrGrid = array("title"    => $arrLang["Packages"],
        "icon"     => "images/list.png",
        "width"    => "99%",
        "start"    => ($total==0) ? 0 : $offset + 1,
        "end"      => $end,
        "total"    => $total,
        "url"      => $url,
        "columns"  => array(0 => array("name"      => $arrLang["Package Name"],
                                       "property1" => ""),
                            1 => array("name"      => $arrLang["Package Info"], 
                                       "property1" => ""),
                            2 => array("name"      => $arrLang["Package Version"], 
                                       "property1" => ""),
                            3 => array("name"      => $arrLang["Package Release"], 
                                       "property1" => ""),
                            4 => array("name"      => $arrLang["Repositor Place"], 
                                       "property1" => ""),
                            5 => array("name"     => $arrLang["Status"], 
                                       "property1" => ""),
                            6 => array("name"      => $arrLang["Package Delete"], 
                                       "property1" => ""),));

    /*Inicion Parte del Filtro*/
    $opcion1 = $opcion2= "";
    if(getParametro('submitInstalado')=='all')
        $opcion1 = "selected='selected'";
    else if(getParametro('submitInstalado')=='installed')
        $opcion2 = "selected='selected'";

    $smarty->assign("module_name",$module_name);
    $smarty->assign("RepositoriesUpdate",$arrLang['Repositories Update']);
    $smarty->assign("Name",$arrLang['Name']);
    $smarty->assign("nombre_paquete",htmlentities($nombre_paquete, ENT_QUOTES, 'UTF-8'));
    $smarty->assign("Search",$arrLang['Search']);
    $smarty->assign("Status",$arrLang['Status']);
    $smarty->assign("opcion2",$opcion2);
    $smarty->assign("opcion1",$opcion1);
    $smarty->assign("PackageInstalled",$arrLang['Package Installed']);
    $smarty->assign("AllPackage",$arrLang['All Package']);
    $smarty->assign("UpdatingRepositories",$arrLang['Updating Repositories']);
    $smarty->assign("InstallPackage",$arrLang['Installing Package']);
    $smarty->assign("accionEnProceso",$arrLang['There is an action in process']);
    $contenidoFiltro = $smarty->fetch("file:$local_templates_dir/new.tpl");
    $oGrid->showFilter($contenidoFiltro);
    /*Fin Parte del Filtro*/
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    return $contenidoModulo;
}

function getParametro($parametro)
{
    if(isset($_POST[$parametro]))
        return $_POST[$parametro];
    else if(isset($_GET[$parametro]))
        return $_GET[$parametro];
    else
        return null;
}

function actualizarRepositorios()
{
    global $arrLang;
    $respuesta = new xajaxResponse();
    $oPackages = new PaloSantoPackages();
    $resultado = $oPackages->checkUpdate();
    $respuesta->addAlert($resultado);
    $respuesta->addAssign("relojArena","innerHTML","");
    $respuesta->addAssign("nombre_paquete","value","");
    $respuesta->addAssign("estaus_reloj","value","apagado");
    $respuesta->addScript("document.getElementById('form_packages').submit();\n");
    return $respuesta;
}

function installPaquete($paquete)
{
    global $arrLang;
    $respuesta = new xajaxResponse();
    $oPackages = new PaloSantoPackages();
    $resultado = $oPackages->installPackage($paquete);
    $respuesta->addAlert($resultado);
    $respuesta->addAssign("relojArena","innerHTML","");
    $respuesta->addAssign("nombre_paquete","value","");
    $respuesta->addAssign("estaus_reloj","value","apagado");
    $respuesta->addScript("document.getElementById('form_packages').submit();\n");
    return $respuesta;
}
?>
