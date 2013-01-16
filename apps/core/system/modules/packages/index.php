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
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";

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

    $action = getParameter("action");
    switch($action){
        case "updateRepositories":
            $contenidoModulo = actualizarRepositorios();
            break;
        case "install":
            $contenidoModulo = installPaquete();
            break;
        case "uninstall":
            $contenidoModulo = uninstallPaquete($paquete);
            break;
        default:
            $contenidoModulo = listPackages($smarty, $module_name, $local_templates_dir,$arrConf);
            break;
    }

    return $contenidoModulo;
}

function listPackages($smarty, $module_name, $local_templates_dir,$arrConf) {

    global $arrLang;
    $oPackages = new PaloSantoPackages();

    $submitInstalado = getParameter('submitInstalado');
    $nombre_paquete = getParameter('nombre_paquete');

    $total_paquetes = $oPackages->ObtenerTotalPaquetes($submitInstalado, $arrConf['ruta_yum'], $nombre_paquete);

// Pagination
    $limit = 50;
    $total = $total_paquetes;
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
    $end    = $oGrid->getEnd();

	$actualizar=false;
	//print($arrConf['ruta_yum']);
    if($submitInstalado =='all'){
        $arrPaquetes = $oPackages->getAllPackages($arrConf['ruta_yum'],$nombre_paquete, $offset, $limit, $total,$actualizar);
		$arrPaquetes = $oPackages->getDataPagination($arrPaquetes,$limit,$offset);
    }
    else{  //si no hay post por default los instalados
        $arrPaquetes = $oPackages->getPackagesInstalados($arrConf['ruta_yum'],$nombre_paquete, $offset, $limit, $total);
		$arrPaquetes = $oPackages->getDataPagination($arrPaquetes,$limit,$offset);
    }

    $url = array(
        'menu'      =>  $module_name,
        'submitInstalado'   =>  $submitInstalado,
        'nombre_paquete'    =>  $nombre_paquete,
    );
    $smarty->assign("msgConfirmDelete", _tr("You will uninstall package along with everything what it depends on it. System can lose important functionalities or become unstable! Are you sure want to Uninstall?") ); 
    $smarty->assign("msgConfirmInstall", _tr("Are you sure want to Install this package?") );
    $smarty->assign("UninstallPackage",_tr("Uninstalling Package") );
    $smarty->assign("msgConfirmUpdate", _tr("Are you sure want to Update this package?") ); 
    $arrData = array();
    $strErrorMsg = "";
     if(!$resultado = $oPackages->readTempFile())
	$strErrorMsg = _tr("Can't read list Packages to update");
     
     if (is_array($arrPaquetes)) {
      for($i=0;$i<count($arrPaquetes);$i++){
            $estado_paquete = $oPackages->estaPaqueteInstalado($arrPaquetes[$i]['name']);
            $instalar = "";//$arrLang['Updated'];
            $tmpPaquete = $arrPaquetes[$i]['name'];
	    if ((!empty($resultado))&&(is_array($resultado))){
	      if (in_array($arrPaquetes[$i]['name'], $resultado)) 
		  $update = "<a href='#'  onclick="."confirmUpdate('$tmpPaquete')".">["._tr("Update")."]</a>";
	      else
		  $update =_tr("Installed");
	    }else
		  $update =_tr("Installed");
            
	    $desinstalar = "<a href='#'  onclick="."confirmDelete('$tmpPaquete')".">["._tr("Uninstall")."]</a>";
            if(!$estado_paquete){
                $instalar = "<a href='#'  onclick="."installaPackage('$tmpPaquete',0)".">[{$arrLang['Install']}]</a>";
                $desinstalar = "";
		$update = "";
            }
	    $arrData[] = array(
                            $arrPaquetes[$i]['name'],
                            $arrPaquetes[$i]['summary'],
                            $arrPaquetes[$i]['version']." / ".$arrPaquetes[$i]['release'],
                            $arrPaquetes[$i]['repositorio'],
                            $update."  ".$instalar." "."$desinstalar",
                            );
        }
    }
    $arrGrid = array("title"    => $arrLang["Packages"],
        "icon"     => "/modules/$module_name/images/system_updates_packages.png",
        "width"    => "99%",
        "start"    => ($total==0) ? 0 : $offset + 1,
        "end"      => $end,
        "total"    => $total,
        "url"      => $url,
        "columns"  => array(0 => array("name"      => $arrLang["Package Name"],
                                       "property1" => ""),
                            1 => array("name"      => $arrLang["Package Info"],
                                       "property1" => ""),
                            2 => array("name"      => $arrLang["Package Version"]." / ".$arrLang["Package Release"],
                                       "property1" => ""),
                            3 => array("name"      => $arrLang["Repositor Place"],
                                       "property1" => ""),
                            4 => array("name"     => $arrLang["Status"],
                                       "property1" => ""),
                            ));

    /*Inicion Parte del Filtro*/
    $arrFilter = filterField();
    $oFilterForm = new paloForm($smarty, $arrFilter);

    if(getParameter('submitInstalado')=='all'){
        $arrFilter["submitInstalado"] = 'all';
        $tipoPaquete = _tr('All Package');
    }else{
        $arrFilter["submitInstalado"] = 'installed';
        $tipoPaquete = _tr('Package Installed');
    }
    $arrFilter["nombre_paquete"] = $nombre_paquete;

    $smarty->assign("module_name",$module_name);
    $smarty->assign("RepositoriesUpdate",$arrLang['Repositories Update']);
    //$smarty->assign("Name",$arrLang['Name']);

    //$smarty->assign("nombre_paquete",htmlentities($nombre_paquete, ENT_QUOTES, 'UTF-8'));
    $smarty->assign("Search",$arrLang['Search']);
   // $smarty->assign("Status",$arrLang['Status']);
   // $smarty->assign("opcion2",$opcion2);
   // $smarty->assign("opcion1",$opcion1);
   // $smarty->assign("PackageInstalled",$arrLang['Package Installed']);
   // $smarty->assign("AllPackage",$arrLang['All Package']);
    $smarty->assign("UpdatingRepositories",$arrLang['Updating Repositories']);
    $smarty->assign("InstallPackage",$arrLang['Installing Package']);
    $smarty->assign("UpdatePackage",_tr("Updating Package"));
    $smarty->assign("accionEnProceso",$arrLang['There is an action in process']);

	if($actualizar){
		$smarty->assign("mb_title",_tr("Message"));
		$smarty->assign("mb_message",_tr("The repositories are not up to date. Click on the")." <b>\""._tr('Repositories Update')."\"</b> "._tr("button to list all available packages."));
	}

    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Status")." =  $tipoPaquete", $arrFilter, array("submitInstalado" => "installed"),true);
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Name")." = $nombre_paquete", $arrFilter, array("nombre_paquete" => ""));

    $oGrid->addButtonAction("update_repositorios",_tr('Repositories Update'),null,'mostrarReloj()');

    $contenidoFiltro =$oFilterForm->fetchForm("$local_templates_dir/new.tpl","",$arrFilter);
    $oGrid->showFilter($contenidoFiltro);
    /*Fin Parte del Filtro*/
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    if($strErrorMsg!=""){
      $smarty->assign("mb_title", _tr("Error"));
      $smarty->assign("mb_message", $strErrorMsg);
    }
    return $contenidoModulo;
}

function filterField(){
    $arrPackages = array("all"=>_tr('All Package'),"installed"=>_tr('Package Installed'));

    $arrFilter = array(
            "nombre_paquete" => array( "LABEL"                  => _tr("Name"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "submitInstalado"   => array("LABEL"                  => _tr("Status"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrPackages,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "ONCHANGE"               => "javascript:submit()"),
                        );
    return $arrFilter;
}

function actualizarRepositorios()
{
    $oPackages = new PaloSantoPackages();
    $resultado = $oPackages->checkUpdate();
    
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status($resultado);
    return $jsonObject->createJSON();
}

function installPaquete()
{
    $oPackages = new PaloSantoPackages();
    $paquete = getParameter("paquete");
    $val  = getParameter("val");
    $resultado = $oPackages->installPackage($paquete,$val);

    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status($resultado);
    return $jsonObject->createJSON();
}

function uninstallPaquete()
{
    $oPackages = new PaloSantoPackages();
    $paquete = getParameter("paquete");
    $resultado = $oPackages->uninstallPackage($paquete);

    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status($resultado);
    return $jsonObject->createJSON();
}

?>
