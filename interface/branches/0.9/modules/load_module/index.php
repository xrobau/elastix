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
  $Id: index.php,v 1.3 2007/09/05 00:22:09 gcarrillo Exp $ */

function _moduleContent($smarty, $module_name)
{
    require_once "libs/misc.lib.php";
    require_once "libs/paloSantoForm.class.php";
    require_once "libs/paloSantoConfig.class.php";
    require_once "libs/paloSantoMenu.class.php";
    require_once "libs/paloSantoACL.class.php";
    require_once "libs/paloSantoModuloXML.class.php";
    require_once "libs/paloSantoInstaller.class.php";
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates

    $pDB = new paloDB("sqlite3:////var/www/db/menu.db");
    $pDBACL = new paloDB("sqlite3:////var/www/db/acl.db");
    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $oMenu = new paloMenu($pDB);
    $oACL = new paloACL($pDBACL);
        
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $sContenido='';
    $strErrorMsg = '';
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("REFRESH", $arrLang["Refresh"]);
    $smarty->assign("label_select_menu", $arrLang["Menu Location"]);
    $smarty->assign("label_module_file", $arrLang["Module File"]);



    $arrFormElements = array();
    $oForm = new paloForm($smarty, $arrFormElements);


    //recuperar acciones
    if (isset($_POST['refresh']))
    {

    }
    if (isset($_POST['save']))
    {
        $arrTmp=array();
	$bMostrarError = false;
    	if($oForm->validateForm($arrTmp)) {
    		//valido el tipo de archivo
            if (!eregi('.tar.gz$', $_FILES['module_file']['name']) && !eregi('.zip$', $_FILES['module_file']['name']) && !eregi('.tgz$', $_FILES['module_file']['name'])) {
            	$bContinuar = false;
                $bMostrarError = true;
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $strErrorMsg .= $arrLang["Invalid file extension.- It must be tar.gz / zip / tgz"];
            }else {
            	//verificar el contenido del archivo
            	$bExito = verifyFileContent($pDB, $strErrorMsg, $arrLang,$oMenu,$oACL);
                if (!$bExito) $bMostrarError = true;
                else{
//complete, entonces muestro mensaje con boton Refrescar
                    $smarty->assign("refresh", 1);
                    $bMostrarError = true;
                    $strErrorMsg = $arrLang["Module sucessfully loaded"];
                }
            	/*if (!$bExito) $bMostrarError = true;
            	else{
            		$bExito = saveMenu($oMenu,$arrTmp);
            		if (!$bExito){
            			$strErrorMsg = $oMenu->errMsg;
            			$bMostrarError = true;
            		}else{
            			//guardo los acl
            			$bExito = saveACL($pDBACL,$arrTmp,$strErrorMsg);
            			if (!$bExito) $bMostrarError = true;
            			else
            			{
            				//complete, entonces muestro mensaje con boton Refrescar
            				$smarty->assign("refresh", 1);
            				$bMostrarError = true;
            				$strErrorMsg = $arrLang["Module sucessfully loaded"];
            			}
            		} 
            	}*/
            }
    	}else {
            // Error
            $bMostrarError = true;
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            
        }
        if ($bMostrarError){
        	$smarty->assign("mb_message", $strErrorMsg);
        }

    }    
    $sContenido .= $oForm->fetchForm("$local_templates_dir/load_module.tpl", $arrLang["Load Module"],$_POST);
    return $sContenido;
}

function verifyFileContent($pDB, &$errorMsg, $arrLang,$oMenu,$oACL)
{
	$arrArchivos = array();
	$output = '';
	$retVal = 1;
	//$tmpDir = '/var/www/tmp';
	$tmpDir = '/tmp/new_module';
        #crear un directorio para descomprimir
        mkdir($tmpDir);
	$tmpFile = $_FILES['module_file']['tmp_name'];
	$dirModules = '/var/www/html/modules';
	//$dirModules = '/home/gcarrillo/workspace/elastix/modules';
	//descomprimir archivo en carpeta temporal
	//archivo zip
	if (eregi('.zip$', $_FILES['module_file']['name'])){
		$cmd_unzip = escapeshellcmd("unzip $tmpFile -d $tmpDir");
    	exec($cmd_unzip,$output,$retVal);
	}
	//archivo tar.gz
	if (eregi('.tar.gz$', $_FILES['module_file']['name']) || eregi('.tgz$', $_FILES['module_file']['name'])){
		
		$cmd_unzip = escapeshellcmd("tar xfz $tmpFile -C $tmpDir");
    	exec($cmd_unzip,$output,$retVal);
	}
	//luego de descomprimir verificar que existe el archivo module.xml
        if ($retVal == 0){
            #verificar que existe el archivo installer.php
            if(!file_exists("$tmpDir/installer/installer.php")) {
                $errorMsg = $arrLang["File installer.php doesn't exist in package"];
		deleteTmpFolder($tmpDir);
	        return false;
            }
            #verificar que existe module xml
            if(!file_exists("$tmpDir/module.xml")) {
                $errorMsg = $arrLang["File module.xml doesn't exist in package"];
		deleteTmpFolder($tmpDir);
	        return false;
            }else{
                #existe, leer el archivo para sacar los nombres del nuevo menu y los modulos
                #el archivo tiene que traer el id del menu sobre el cual se va a agregar el o los modulos.
                $oModuloXML= new ModuloXML("$tmpDir/module.xml");
                #el consutructor parsea el archivo y ya debo tener el arbol de menu
              //  print "<pre>";print_r($oModuloXML->_arbolMenu);print "</pre>";
                if (count($oModuloXML->_arbolMenu)>0)
                {
                    $oMenu = new paloMenu($pDB);
                    $bTieneModulo=false;
                    $arrNewMenu=array();
                    //obtengo el nombre del menu superior
                    foreach ($oModuloXML->_arbolMenu as $menulist)
                    {
                        $menuid=$menulist['MENUID'];
                        if (!empty($menuid))
                        {
                            #buscar si ya existe ese menu
                            
                            if (!$oMenu->existeMenu($menuid))
                            {
                                $arrNewMenu[]=array("menuid"=>$menuid,"tag"=>$menulist['TAG'],"type"=>"menu");
                            }
                            
                        }else
                        {
                            $errorMsg="Menu for module is not defined";
		            deleteTmpFolder($tmpDir);
                            return false;
                        }
                        //reviso los items
                        if (count($menulist['ITEMS'])>0)
                        {
                            $parentId=$menuid;
                            foreach ($menulist['ITEMS'] as $menuitem)
                            {
                                $menuid=$menuitem['MENUID'];
                                if (!empty($menuid))
                                {
                                    #buscar si ya existe ese menu
                                    if (!$oMenu->existeMenu($menuid))
                                    {
                                        #verificar que exista una carpeta con ese nombre de menu
                                        if (file_exists("$tmpDir/$menuid"))
                                        {
                                        //por ahora no voy a verificar la estructura de la carpeta del modulo
                                            $bTieneModulo=true;
                                            $arrNewMenu[]=array("menuid"=>$menuid,"tag"=>$menuitem['TAG'],"type"=>"module","parent"=>$parentId);
                                        }
                                        
                                    }else
                                    {
                                        //ya existe ese nombre de menu, notificar
                                        $errorMsg="Menu name for module already exists";
                                        deleteTmpFolder($tmpDir);
                                        return false;
                                    }
                                }
                            }
                        }
                        if (!$bTieneModulo)
                        {
                            $errorMsg="No module defined";
                            deleteTmpFolder($tmpDir);
                            return false;
                        }
                        $oInstaller = new Installer();
                        //defino las entradas del menu, asigno permisos y copio las carpetas de los modulos
                        foreach ($arrNewMenu as $menuModule)
                        {
                            #uso la clase paloInstaler
                            #agregar la entrada del menu
                            $oInstaller->addMenu($oMenu,$menuModule);
                            #agregar los permisos
                            #si es un modulo cpiar el contenido a la carpeta de modulos
                            if ($menuModule['type']=="module")
                            {
                                $oInstaller->addResourceMembership($oACL,$menuModule);
                                #copio la carpeta con el contenido del modulo
                                $cmd_cp = escapeshellcmd("cp -r $tmpDir/$menuModule[menuid]  $dirModules");
                                exec($cmd_cp,$output,$retVal);
                            }
                        }
                        #ejecuto el installer.php
	        	$cmd_install = escapeshellcmd("php $tmpDir/installer/installer.php");
    			exec($cmd_install,$output,$retVal);
    			if ($retVal!=0){
                            $errorMsg = $arrLang["installer.php failed"];
                            deleteTmpFolder($tmpDir);
                            return false;
    			}
                //Actualizo paramentros de mantencion de permisos de menus por usuarios y borro los templates de smarty.
                $oInstaller->refresh($_SERVER['DOCUMENT_ROOT']);
                    }
		//borrar directorio temporal 
                }else
                {
                    #verificar si hay un error de la clase
                    if ($oModuloXML->_errMsg!="") $errorMsg=$oModuloXML->_errMsg;
                    else $errorMsg="XML file is incorrect";
                    deleteTmpFolder($tmpDir);
                    return false;
                }
		deleteTmpFolder($tmpDir);
            }
	}else
	{
		$errorMsg = $arrLang["Could not uncompress file"];
		return false;
	}

	return true;
}

function deleteTmpFolder($tmpModuleDir){
			//borrar directorio temporal 
	$cmd_rm = escapeshellcmd("rm -r $tmpModuleDir -f");
        $mensaje = `$cmd_rm 2>&1`;
	
}


function saveACL($pDBACL,$arrTmp,&$errMsg){

	$bExito = $oACL->createResource($arrTmp['module_id'], $arrTmp['module_name']);
	if ($bExito){
		//inserto en acl_group_permission
		//recupero el id del recurso insertado
		$resource_id= $oACL->getResourceId($arrTmp['module_id']);
		$bExito = false;
		if (!is_null($resource_id))
		{
			 $bExito = $oACL->saveGroupPermission(1,array($resource_id));
		}
		
	}
	$errMsg = $oACL->errMsg;
	return $bExito;
}
?>
