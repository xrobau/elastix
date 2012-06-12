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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ 
*/

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoACL.class.php";
    include_once "libs/paloSantoForm.class.php";

//include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    

    $pDB = new paloDB("sqlite3:////var/www/db/acl.db");
    $msgError='';
    $listaPermisosNuevosGrupo=array();
    $listaPermisosAusentesGrupo=array();
    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $arrData = array();
    $pACL = new paloACL($pDB);
    if(!empty($pACL->errMsg)) {
        echo "ERROR DE ACL: $pACL->errMsg <br>";
    }
    $contenidoModulo='';
    $arrResources=$pACL->getResources();

    $arrGruposACL=$pACL->getGroups();
    for($i=0; $i<count($arrGruposACL); $i++)
    {
        $arrGrupos[$arrGruposACL[$i][0]] = $arrGruposACL[$i][1];
    }

    //obtener valor de grupo 
    $idGroup=(isset($_POST['group']))?$_POST['group']:1;

    /*$arrGrupos=array(1 => "Administrator",
                     2 => "Operator",
                     3 => "Extension User");
*/

    if (!isset($_POST['group'])) $_POST['group']=1;
    if(isset($_POST['apply'])) {
        $arrPermisos=$pACL->getGroupPermissions($idGroup);
        $listaPermisos=array_keys($arrPermisos);
        $selectedResources= isset($_POST['groupPermission'])?array_keys($_POST['groupPermission']):array();
        $listaPermisosNuevos = array_diff($selectedResources, $listaPermisos);
        $listaPermisosAusentes = array_diff($listaPermisos, $selectedResources);
        foreach($arrResources as $resource) {
            if (in_array($resource[1],$listaPermisosNuevos))
                $listaPermisosNuevosGrupo[]=$resource[0];
            if (in_array($resource[1],$listaPermisosAusentes))
                $listaPermisosAusentesGrupo[]=$resource[0];
        }
        if (count($listaPermisosAusentesGrupo)>0){
            $bExito=$pACL->deleteGroupPermission($idGroup,$listaPermisosAusentesGrupo);
            if (!$bExito)
               $msgError=$pACL->errMsg;
        }
        if (count($listaPermisosNuevosGrupo)>0){
            $bExito=$pACL->saveGroupPermission($idGroup,$listaPermisosNuevosGrupo);
            if (!$bExito)
               $msgError.=$pACL->errMsg;
        }
        if (!empty($msgError))
                $smarty->assign("mb_message", $msgError);

        //borra los menus q tiene de permisos que estan guardados en la session, el index.php principal (html) volvera a generar esta arreglo de permisos.
        unset($_SESSION['elastix_user_permission']); 
    } 

       $arrFormElements = array(
                                 "group"  => array("LABEL"                  => $arrLang["Group"],
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "SELECT",
                                                        "INPUT_EXTRA_PARAM"      => $arrGrupos,
                                                        "VALIDATION_TYPE"        => "integer",
                                                        "VALIDATION_EXTRA_PARAM" => ""),
                                 
                                 );
    
        $oFilterForm = new paloForm($smarty, $arrFormElements);
        $smarty->assign("SHOW", $arrLang["Show"]);
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/group_permission.tpl", "", $_POST);


        $end = count($arrResources);
        $arrPermisos=$pACL->getGroupPermissions($idGroup);
        foreach($arrResources as $resource) {
            $checked=array_key_exists($resource[1],$arrPermisos)?"checked":'';
            $arrTmp[0] = "<input type='checkbox' name='groupPermission[".$resource[1]."][".$resource[0]."]' $checked>";
            
            $arrTmp[1] = $arrLang[$resource[2]];
            
            $arrData[] = $arrTmp;
        }
        
        $arrGrid = array("title"    => $arrLang["Group Permission"],
                         "icon"     => "images/user.png",
                         "width"    => "99%",
                         "start"    => ($end==0) ? 0 : 1,
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => "<input class='button' type='submit' name='apply' value='{$arrLang['Apply']}' >",
                                                        "property1" => ""),
                                             1 => array("name"      => $arrLang["Resource"], 
                                                        "property1" => ""))
                        );
        
        $oGrid = new paloSantoGrid($smarty);
        $oGrid->showFilter(trim($htmlFilter));

        $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=group_permission'>".$oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";


    return $contenidoModulo;
}
?>
