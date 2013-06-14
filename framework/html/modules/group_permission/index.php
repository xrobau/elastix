<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2                                                |
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
  $Id: index.php,v 1.1 2009-05-06 04:05:41 Jonathan Vega jvega112@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoMenu.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoGroupPermission.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = "";

    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "apply":
            $content = applyGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function applyGroupPermission($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pGroupPermission = new paloSantoGroupPermission();

    $filter_resource = getParameter("resource_apply");
    $limit = getParameter("limit_apply");
    $offset = getParameter("offset_apply");

    $action_apply = getParameter("action_apply");
    $start_apply = getParameter("start_apply");

    $arrResources = $pGroupPermission->ObtainResources($limit, $offset, $filter_resource);

    //****************************************************************************************************
    // ACTION -> access
    //****************************************************************************************************

    //permisos recursos seleccionados en el grid
    // Array ( [0] => build_module [1] => delete_module [2] => language_admin ...
    $selectedAccess = isset( $_POST['groupPermission'] ) ? array_keys( $_POST['groupPermission'] ) : array();

    $idGroup = getParameter("filter_group");
    $isAdministrator = ( $idGroup == 1 ) ? true : false;

    if( $isAdministrator ){
        $selectedAccess[] = "usermgr";
        $selectedAccess[] = "grouplist";
        $selectedAccess[] = "userlist";
        $selectedAccess[] = "group_permission";
    }

    $listaPermisos = OrderResourceGroupPermissions( $pGroupPermission->loadResourceGroupPermissions("access", $idGroup) );

    $listaPermisosNuevos = array_diff( $selectedAccess, $listaPermisos);
    $listaPermisosAusentes = array_diff( $listaPermisos, $selectedAccess);
    $listaPermisosNuevosGrupo = array();
    $listaPermisosAusentesGrupo = array();

    foreach($arrResources as $resource) {
        if( in_array( $resource["name"], $listaPermisosNuevos) )    $listaPermisosNuevosGrupo[]   = $resource["id"];
        if( in_array( $resource["name"], $listaPermisosAusentes) )  $listaPermisosAusentesGrupo[] = $resource["id"];
    }
    if( count($listaPermisosAusentesGrupo) > 0 ){
        $bExito = $pGroupPermission->deleteGroupPermissions("access", $idGroup, $listaPermisosAusentesGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }

    if( count($listaPermisosNuevosGrupo) > 0 ){
        $bExito = $pGroupPermission->saveGroupPermissions("access", $idGroup, $listaPermisosNuevosGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }
    if (!empty($msgError))
            $smarty->assign("mb_message", $msgError);

//TODO: Las acciones de view, delete, create y update no existen en la base de datos en la tabla acl_action (sólo está la acción access), por lo tanto se generaba un error al no existir dichas acciones. Queda para un futuro la implementación de estas acciones.

    //****************************************************************************************************
    // ACTION -> view
    //****************************************************************************************************
/*
    //permisos recursos seleccionados en el grid
    // Array ( [0] => build_module [1] => delete_module [2] => language_admin ...
    $selectedViews = isset($_POST['viewPermission']) ? array_keys($_POST['viewPermission']) : array();
    if( $isAdministrator ){
        $selectedViews[] = "usermgr";
        $selectedViews[] = "grouplist";
        $selectedViews[] = "userlist";
        $selectedViews[] = "group_permission";
    }

    $listaPermisos = OrderResourceGroupPermissions( $pGroupPermission->loadResourceGroupPermissions("view", $idGroup) );

    $listaPermisosNuevos = array_diff( $selectedViews, $listaPermisos);
    $listaPermisosAusentes = array_diff( $listaPermisos, $selectedViews);
    $listaPermisosNuevosGrupo = array();
    $listaPermisosAusentesGrupo = array();

    foreach($arrResources as $resource) { print_r("<br/>".$resource["name"]);
        if( in_array( $resource["name"], $listaPermisosNuevos) )    $listaPermisosNuevosGrupo[]   = $resource["id"];
        if( in_array( $resource["name"], $listaPermisosAusentes) )  $listaPermisosAusentesGrupo[] = $resource["id"];
    }

    if( count($listaPermisosAusentesGrupo) > 0 ){
        $bExito = $pGroupPermission->deleteGroupPermissions("view", $idGroup, $listaPermisosAusentesGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }
print_r($listaPermisosNuevosGrupo);
    if( count($listaPermisosNuevosGrupo) > 0 ){
        $bExito = $pGroupPermission->saveGroupPermissions("view", $idGroup, $listaPermisosNuevosGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }
    if (!empty($msgError))
            $smarty->assign("mb_message", $msgError);

    //****************************************************************************************************
    // ACTION -> create
    //****************************************************************************************************

    //permisos recursos seleccionados en el grid
    // Array ( [0] => build_module [1] => delete_module [2] => language_admin ...
    $selectedCreates = isset($_POST['createPermission']) ? array_keys($_POST['createPermission']) : array();

    if( $isAdministrator ){
        $selectedCreates[] = "usermgr";
        $selectedCreates[] = "grouplist";
        $selectedCreates[] = "userlist";
        $selectedCreates[] = "group_permission";
    }

    $listaPermisos = OrderResourceGroupPermissions( $pGroupPermission->loadResourceGroupPermissions("create", $idGroup) );

    $listaPermisosNuevos = array_diff( $selectedCreates, $listaPermisos);
    $listaPermisosAusentes = array_diff( $listaPermisos, $selectedCreates);
    $listaPermisosNuevosGrupo = array();
    $listaPermisosAusentesGrupo = array();

    foreach($arrResources as $resource) {
        if( in_array( $resource["name"], $listaPermisosNuevos) )    $listaPermisosNuevosGrupo[]   = $resource["id"];
        if( in_array( $resource["name"], $listaPermisosAusentes) )  $listaPermisosAusentesGrupo[] = $resource["id"];
    }

    if( count($listaPermisosAusentesGrupo) > 0 ){
        $bExito = $pGroupPermission->deleteGroupPermissions("create", $idGroup, $listaPermisosAusentesGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }

    if( count($listaPermisosNuevosGrupo) > 0 ){
        $bExito = $pGroupPermission->saveGroupPermissions("create", $idGroup, $listaPermisosNuevosGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }
    if (!empty($msgError))
            $smarty->assign("mb_message", $msgError);

    //****************************************************************************************************
    // ACTION -> delete
    //****************************************************************************************************

    $selectedDeletes = isset($_POST['deletePermission']) ? array_keys($_POST['deletePermission']) : array();

    if( $isAdministrator ){
        $selectedDeletes[] = "usermgr";
        $selectedDeletes[] = "grouplist";
        $selectedDeletes[] = "userlist";
        $selectedDeletes[] = "group_permission";
    }

    $listaPermisos = OrderResourceGroupPermissions( $pGroupPermission->loadResourceGroupPermissions("delete", $idGroup) );

    $listaPermisosNuevos = array_diff( $selectedDeletes, $listaPermisos);
    $listaPermisosAusentes = array_diff( $listaPermisos, $selectedDeletes);
    $listaPermisosNuevosGrupo = array();
    $listaPermisosAusentesGrupo = array();

    foreach($arrResources as $resource) {
        if( in_array( $resource["name"], $listaPermisosNuevos) )    $listaPermisosNuevosGrupo[]   = $resource["id"];
        if( in_array( $resource["name"], $listaPermisosAusentes) )  $listaPermisosAusentesGrupo[] = $resource["id"];
    }

    if( count($listaPermisosAusentesGrupo) > 0 ){
        $bExito = $pGroupPermission->deleteGroupPermissions("delete", $idGroup, $listaPermisosAusentesGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }

    if( count($listaPermisosNuevosGrupo) > 0 ){
        $bExito = $pGroupPermission->saveGroupPermissions("delete", $idGroup, $listaPermisosNuevosGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }
    if (!empty($msgError))
            $smarty->assign("mb_message", $msgError);

    //****************************************************************************************************
    // ACTION -> update
    //****************************************************************************************************

    $selectedUpdates = isset($_POST['updatePermission']) ? array_keys($_POST['updatePermission']) : array();

    if( $isAdministrator ){
        $selectedUpdates[] = "usermgr";
        $selectedUpdates[] = "grouplist";
        $selectedUpdates[] = "userlist";
        $selectedUpdates[] = "group_permission";
    }

    $listaPermisos = OrderResourceGroupPermissions( $pGroupPermission->loadResourceGroupPermissions("update", $idGroup) );

    $listaPermisosNuevos = array_diff( $selectedUpdates, $listaPermisos);
    $listaPermisosAusentes = array_diff( $listaPermisos, $selectedUpdates);
    $listaPermisosNuevosGrupo = array();
    $listaPermisosAusentesGrupo = array();

    foreach($arrResources as $resource) {
        if( in_array( $resource["name"], $listaPermisosNuevos) )    $listaPermisosNuevosGrupo[]   = $resource["id"];
        if( in_array( $resource["name"], $listaPermisosAusentes) )  $listaPermisosAusentesGrupo[] = $resource["id"];
    }

    if( count($listaPermisosAusentesGrupo) > 0 ){
        $bExito = $pGroupPermission->deleteGroupPermissions("update", $idGroup, $listaPermisosAusentesGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }

    if( count($listaPermisosNuevosGrupo) > 0 ){
        $bExito = $pGroupPermission->saveGroupPermissions("update", $idGroup, $listaPermisosNuevosGrupo);
        if (!$bExito)
            $msgError = "ERROR";
    }
    if (!empty($msgError))
            $smarty->assign("mb_message", $msgError);*/

    //borra los menus q tiene de permisos que estan guardados en la session, el index.php principal (html) volvera a generar esta arreglo de permisos.
    unset($_SESSION['elastix_user_permission']);
    return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, true, $action_apply, $start_apply);
}

function reportGroupPermission($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $wasSaved = false, $value_action = "", $value_start = 0)
{
    global $arrLang;
    
    $pGroupPermission = new paloSantoGroupPermission();

    $filter_group = getParameter("filter_group");
    $id_administrador = 1;
    $filter_group = isset( $filter_group ) ? $filter_group : $id_administrador;
    $filter_resource = getParameter("filter_resource");

    $action = getParameter("nav");
    $start  = getParameter("start");
    if( $wasSaved ){
        $action = $value_action;
        $start = $value_start;
    }
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
        $parameter_to_find = array();
    $lang = get_language();
    if($lang != "en"){
        foreach($arrLang as $key=>$value){
            $langValue    = strtolower(trim($value));
            $filter_value = strtolower(trim($filter_resource));
            if($filter_value!=""){
                if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
                    if (strpos($langValue, $filter_value) !== FALSE)
                            $parameter_to_find[] = $key;
            }
        }
    }

    $parameter_to_find[] = $filter_resource;

    if(empty($parameter_to_find))
        $totalGroupPermission = $pGroupPermission->ObtainNumResouces($filter_resource);
    else
        $totalGroupPermission = $pGroupPermission->ObtainNumResouces($parameter_to_find);

    $limit  = 25;
    $total  = $totalGroupPermission;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

    $oGrid->calculatePagination($action,$start);
    $offset = $oGrid->getOffsetValue();
    $end    = $oGrid->getEnd();


    $arrData = null;
    if(empty($parameter_to_find))
                $arrResult = $pGroupPermission->ObtainResources($limit, $offset, $filter_resource);
        else
        $arrResult = $pGroupPermission->ObtainResources($limit, $offset, $parameter_to_find);

    $url = array(
        'menu'              =>  $module_name,
        'filter_group'      =>  $filter_group,
        'filter_resource'   =>  $filter_resource,
    );

    $idGroup = $filter_group;
    $arrPermisos = $pGroupPermission->loadGroupPermissionsACL($idGroup);
    $arrPermisos = OrderGroupPermissions($arrPermisos);

    $isAdministrator = ($idGroup == 1) ? true :false;

    if( is_array($arrResult) && $total > 0){
        foreach( $arrResult as $key => $resource ){
            $disabled = "";
            if( ( $resource["name"] == 'usermgr'   || $resource["name"] == 'grouplist' || $resource["name"] == 'userlist'  ||
                  $resource["name"] == 'group_permission') & $isAdministrator ){
                $disabled = "disabled='disabled'";
            }

            $checked0 = ""; $checked1 = ""; $checked2 = ""; $checked3 = ""; $checked4 = "";

            if( isset( $arrPermisos[ $resource["name"] ] ) ){
                $T = $arrPermisos[ $resource["name"] ];
                $T = $T["actions"];

                foreach( $T as $num => $key ){
                    if( $key == "access" ) $checked0 = "checked";
                    if( $key == "view" )   $checked1 = "checked";
                    if( $key == "create" ) $checked2 = "checked";
                    if( $key == "delete" ) $checked3 = "checked";
                    if( $key == "update" ) $checked4 = "checked";
                }
            }

            $arrTmp[0] = "<input type='checkbox' $disabled name='groupPermission[".$resource["name"]."][".$resource["id"]."]' $checked0>";
            $arrTmp[1] = _tr($resource["description"]);
            $arrTmp[2] = "<input type='checkbox' $disabled name='viewPermission[".$resource["name"]."][".$resource["id"]."]' $checked1>";
            $arrTmp[3] = "<input type='checkbox' $disabled name='createPermission[".$resource["name"]."][".$resource["id"]."]' $checked2>";
            $arrTmp[4] = "<input type='checkbox' $disabled name='deletePermission[".$resource["name"]."][".$resource["id"]."]' $checked3>";
            $arrTmp[5] = "<input type='checkbox' $disabled name='updatePermission[".$resource["name"]."][".$resource["id"]."]' $checked4>";

            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array(   "title"    => _tr("Group Permission"),
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array(0 => array("name"      => "<input class='button' type='submit' name='apply' value='"._tr('Apply')."' />",
                                                        "property1" => ""),
                                            1 => array("name"      => _tr("Resource"),
                                                        "property1" => ""),
                        ));

    //begin section filter
    $arrFormFilterGroupPermission = createFieldFilter($pGroupPermission);
    $oFilterForm = new paloForm($smarty, $arrFormFilterGroupPermission);
    $smarty->assign("SHOW", _tr("Show"));

    $_POST["filter_group"] = $filter_group;
    $_POST["filter_resource"] = $filter_resource;

    $nameGroup=$arrFormFilterGroupPermission["filter_group"]["INPUT_EXTRA_PARAM"][$filter_group];
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Group")." = $nameGroup", $_POST, array("filter_group" => 1),true);
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Resource")." = $filter_resource", $_POST, array("filter_resource" =>""));
    //ayuda para el pagineo -> estod datos son tomados en la function applyGroupPermission($smarty, $module_name, ......
    //ayuda a que despues de "aplicar" se quede en la misma pagina
    $smarty->assign("resource_apply", htmlspecialchars($filter_resource, ENT_COMPAT, 'UTF-8'));
    $smarty->assign("limit_apply", htmlspecialchars($limit, ENT_COMPAT, 'UTF-8'));
    $smarty->assign("offset_apply", htmlspecialchars($offset, ENT_COMPAT, 'UTF-8'));

    $smarty->assign("action_apply", htmlspecialchars($action, ENT_COMPAT, 'UTF-8'));
    $smarty->assign("start_apply", htmlspecialchars($start, ENT_COMPAT, 'UTF-8'));

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
    //end grid parameters

    return $contenidoModulo;
}


function createFieldFilter($pGroupPermission)
{
    $arrGruposACL = $pGroupPermission->getGroupsACL();
    $arrGrupos = array();

    for( $i = 0; $i < count($arrGruposACL); $i++ )
    {
        if( $arrGruposACL[$i][1] == 'administrator')  $arrGruposACL[$i][1] = _tr('administrator');
        else if( $arrGruposACL[$i][1] == 'operator')  $arrGruposACL[$i][1] = _tr('operator');
        else if( $arrGruposACL[$i][1] == 'extension') $arrGruposACL[$i][1] = _tr('extension');

        $arrGrupos[$arrGruposACL[$i][0]] = $arrGruposACL[$i][1];
    }

    $arrFormElements = array(
            "filter_group" => array(    "LABEL"                  => _tr("Group"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => $arrGrupos,
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "filter_resource" => array( "LABEL"                  => _tr("Resource"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function getAction()
{
    if(getParameter("apply")) //Get parameter by POST (submit)
        return "apply";
    //else if(getParameter("new"))
    //    return "new";
    //else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
    //    return "show";
    else
        return "report";
}

//**************************************************************************************************************************

//FUNCIONES DE AYUDA

function OrderResourceGroupPermissions( $arrPermisos )
{
    $arrResult = array();

    //Array ( [0] => Array ( [resource_name] => bib_consultaLibro )
    //        [1] => Array ( [resource_name] => build_module )
    //        [2] => Array ( [resource_name] => con )

    foreach( $arrPermisos as $num => $data )
        $arrResult[] = $data["resource_name"];

    return $arrResult;
}

function OrderGroupPermissions($arrPermisos)
{
    $arrArray = array();

    //Array ( [0] => Array ( [resource_id] => 2 [resource_name] => usermgr [action_name] => view )
    //        [1] => Array ( [resource_id] => 2 [resource_name] => usermgr [action_name] => access )
    //        [2] => Array ( [resource_id] => 3 [resource_name] => grouplist [action_name] => access )
    // --> $arrPermisos

    $bandera = true;
    $bandera2 = false;
    $resource_id = 0;
    $resource_name = "";
    $arrActions = array();
    foreach($arrPermisos as $num => $data)
    {
        if( $bandera == true )
        {
            $resource_id = $data["resource_id"];
            $resource_name = $data["resource_name"];

            $bandera = false;
        }

        if( $resource_id == $data["resource_id"] )
        {
            $arrActions[] = $data["action_name"];
        }
        else
        {
            $arrTemp = array();
            $arrTemp["resource_id"] = $resource_id;
            $arrTemp["actions"] = $arrActions;
            $arrArray[ $resource_name ] = $arrTemp;

            $resource_id = $data["resource_id"];
            $resource_name = $data["resource_name"];
            $arrActions = array();
            $arrActions[] = $data["action_name"];
        }
    }

    if( sizeof($arrPermisos) > 0 )
    {
        $arrTemp = array();
        $arrTemp["resource_id"] = $resource_id;
        $arrTemp["actions"] = $arrActions;
        $arrArray[ $resource_name ] = $arrTemp;
    }

    return $arrArray;
}

?>
