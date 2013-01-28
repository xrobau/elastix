<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2003 Palosanto Solutions S. A.                    |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  +----------------------------------------------------------------------+
  | Este archivo fuente está sujeto a las políticas de licenciamiento    |
  | de Palosanto Solutions S. A. y no está disponible públicamente.      |
  | El acceso a este documento está restringido según lo estipulado      |
  | en los acuerdos de confidencialidad los cuales son parte de las      |
  | políticas internas de Palosanto Solutions S. A.                      |
  | Si Ud. está viendo este archivo y no tiene autorización explícita    |
  | de hacerlo, comuníquese con nosotros, podría estar infringiendo      |
  | la ley sin saberlo.                                                  |
  +----------------------------------------------------------------------+
  | Autores: Gladys Carrillo B.   <gcarrillo@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: paloSantoInstaller.class.php,v 1.1 2007/09/05 00:25:25 gcarrillo Exp $
*/

require_once "paloSantoDB.class.php";
require_once "paloSantoModuloXML.class.php";
require_once "misc.lib.php";

// La presencia de MYSQL_ROOT_PASSWORD es parte del API global.
define('MYSQL_ROOT_PASSWORD', obtenerClaveConocidaMySQL('root', '/var/www/html/'));

class Installer
{

    var $_errMsg;

    function Installer()
    {

    }

    function addMenu($oMenu,$arrTmp){
    //verificar si tiene que crear un nuevo menu raiz

    $parentId = isset($arrTmp['parent'])?$arrTmp['parent']:"";
    $link     = isset($arrTmp['link'])?$arrTmp['link']:"";
    $order    = isset($arrTmp['order'])?$arrTmp['order']:"-1";
    $tag      = isset($arrTmp['tag'])?$arrTmp['tag']:"";
    $menuid   = isset($arrTmp['menuid'])?$arrTmp['menuid']:"";

    if ($parentId=="")
        $type="";
    else{
        if($link=="")
          $type="module";
        else
          $type="framed";
    }
   
    //creo el menu
    $bExito = $oMenu->createMenu($menuid,$tag,$parentId,$type,$link,$order);
    if (!$bExito){
            $this->_errMsg = $oMenu->errMsg;
            return false;
        }
    return true;
    }

 /*****************************************************************************************************/
// funcion para actualizar un item de menu
    function UpdateMenu($oMenu,$arrTmp){
        $parentId = isset($arrTmp['parent'])?$arrTmp['parent']:"";
        $link     = isset($arrTmp['link'])?$arrTmp['link']:"";
        $order    = isset($arrTmp['order'])?$arrTmp['order']:"-1";
        $tag      = isset($arrTmp['tag'])?$arrTmp['tag']:"";
        $menuid   = isset($arrTmp['menuid'])?$arrTmp['menuid']:"";
    
        if ($parentId=="")
            $type="";
        else{
            if($link=="")
            $type="module";
            else
            $type="framed";
        }
    
        //creo el menu
        $bExito = $oMenu->updateItemMenu($menuid,$tag,$parentId,$type,$link,$order);
        if (!$bExito){
                $this->_errMsg = $oMenu->errMsg;
                return false;
            }
        return true;
    }

    function updateResourceMembership($oACL,$arrTmp, $arrGroup=array()){
        $oACL->_DB->beginTransaction();
        $bExito = $oACL->createResource($arrTmp['menuid'], $arrTmp['tag']);
        if ($bExito){
			$oACL->_DB->commit();
        }else
            $oACL->_DB->rollBack();
        $this->_errMsg = $oACL->errMsg;
        return $bExito;
    }

/*****************************************************************************************************/

    function addResourceMembership($oACL,$arrTmp, $arrGroup=array()){
        $oACL->_DB->beginTransaction();
        $bExito = $oACL->createResource($arrTmp['menuid'], $arrTmp['tag']);
        if ($bExito){
            //inserto en acl_group_permission
            //recupero el id del recurso insertado
            $resource_id= $oACL->getResourceId($arrTmp['menuid']);
            $bExito = false;
            if (!is_null($resource_id))
            {
                if(is_array($arrGroup) & !empty($arrGroup)){
                    foreach($arrGroup as $key => $value){
                        $idGroup   = $value['id'];
                        $nameGroup = $value['name'];
                        $descGroup = $value['desc'];
                        $idGroupTmp = $oACL->getIdGroup($nameGroup);// obtiene el id del grupo dado su nombre
                        if($idGroupTmp){
                           $bExito = $oACL->saveGroupPermission($idGroupTmp,array($resource_id));
                        }else{
                           if(is_null($oACL->getGroupNameByid($idGroup))){// no existe el grupo
                                $bExito = $oACL->createGroup($nameGroup, $descGroup);
                                if(!$bExito){
                                    $oACL->_DB->rollBack();
                                    $this->_errMsg = $oACL->errMsg;
                                    return $bExito;
                                }
                                $idGroup = $oACL->_DB->getLastInsertId();
                           }
                           $bExito = $oACL->saveGroupPermission($idGroup,array($resource_id));
                        }
                    }
                }else
                    $bExito = $oACL->saveGroupPermission(1,array($resource_id));
                if($bExito)
                    $oACL->_DB->commit();
                else
                    $oACL->_DB->rollBack();
            }
        }else
            $oACL->_DB->rollBack();
        $this->_errMsg = $oACL->errMsg;
        return $bExito;
    }

    function createNewDatabase($path_script_db,$sqlite_db_path,$db_name)
    {
        $comando="cat $path_script_db | sqlite3 $sqlite_db_path/$db_name.db";
        exec($comando,$output,$retval);
        return $retval;
    }
    function createNewDatabaseMySQL($path_script_db, $db_name, $datos_conexion)
    {
        $root_password = MYSQL_ROOT_PASSWORD;

        $db = 'mysql://root:'.$root_password.'@localhost/';
        $pDB = new paloDB ($db);
        $sPeticionSQL = "CREATE DATABASE $db_name";
        $result = $pDB->genExec($sPeticionSQL);
        if($datos_conexion['locate'] == "")
            $datos_conexion['locate'] = "localhost";
        $GrantSQL = "GRANT SELECT, INSERT, UPDATE, DELETE ON $db_name.* TO ";
        $GrantSQL .= $datos_conexion['user']."@".$datos_conexion['locate']." IDENTIFIED BY '".                          $datos_conexion['password']."'";
        $result = $pDB->genExec($GrantSQL);
        $comando="mysql --password=".escapeshellcmd($root_password)." --user=root $db_name < $path_script_db";
        exec($comando,$output,$retval);
        return $retval;
    }

    function addModuleLanguage($tmpDir,$DocumentRoot)
    {
        require_once("configs/languages.conf.php");
        //array que incluye todos los lenguages que existan en /html/lang
        $languages = array_keys($languages);

        $oModuloXML= new ModuloXML("$tmpDir/module.xml");
        //Se recorre por cada lenguaje
        foreach ($languages as $lang)
        {
            if (file_exists("$DocumentRoot/lang/$lang.lang")) {
                require_once("$DocumentRoot/lang/$lang.lang");
                global $arrLang;
                //Se realiza por cada modulo
                if (count(($oModuloXML->_arbolMenu))>0) {
                    foreach (($oModuloXML->_arbolMenu) as $menulist) {
                        foreach ($menulist['ITEMS'] as $item_modules) {
                                $menuid = $item_modules['MENUID'];
        //                         echo "MENUID".$menuid;
                                if (!empty($menuid))
                                {
                                    $nodo = array($item_modules['DESC'] => $item_modules['DESC']);
                                    $result = array_merge($arrLang,$nodo);
                                    $arrLang = $result;
                                }
                        }
                    }
                }
                $gestor = fopen("$DocumentRoot/lang/$lang.lang", "w");
                $contenido = "<?php \nglobal \$arrLang; \n\$arrLang =";
                $contenido .= var_export($arrLang,TRUE)."?>";
                if (fwrite($gestor, $contenido) === FALSE) {
                        echo "Error al escribir archivo";
                }
                fclose($gestor);
            } else {
                echo "No existe";
            }
        }
    }

    function refresh($documentRoot='')
    {
        if($documentRoot == ''){
            global $arrConf;
            $documentRoot = $arrConf['basePath'];
        }

        //STEP 1: Delete tmp templates of smarty.
        exec("rm -rf $documentRoot/var/templates_c/*",$arrConsole,$flagStatus); 

        //STEP 2: Update menus elastix permission.
        if(isset($_SESSION['elastix_user_permission']))
          unset($_SESSION['elastix_user_permission']);

        return $flagStatus;
    }
}
?>
