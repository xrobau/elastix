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
  $Id: index.php,v 1.2 2007/08/10 01:32:53 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoEmail.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/cyradm.php";
    include_once "configs/email.conf.php";
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    //asegurar que existe directorio de plantillas local
    // aun no se que se daba hacer si no hay plantillas locales
    //if (!file_exists($local_templates_dir))

    
    $pDB = new paloDB("sqlite3:////var/www/db/email.db");
    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }
    $error="";
    $arrData = array();
    $pEmail = new paloEmail($pDB);
    if(!empty($arrLang[$pEmail->errMsg])) {
        echo "{$arrLang["ERROR"]}: {$arrLang[$pEmail->errMsg]} <br>";
    }

    $bMostrarListado=TRUE;

    $arrFormElements = array(
                             "domain_name"       => array("LABEL"                   => $arrLang["Domain name"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[-_[:alnum:]]+(\.[-[:alnum:]]+)*(\.[a-z]{2,5})+$"),

                         );

    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("CONFIRM_CONTINUE", $arrLang["Are you sure you wish to continue?"]);

    if(isset($_POST['submit_create_domain'])) { 
         //AGREGAR NUEVA TARIFA
        include_once("libs/paloSantoForm.class.php");
        $oForm = new paloForm($smarty, $arrFormElements);
		$formValues['domain_name']='';
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_domain.tpl", $arrLang["New Domain"],$formValues);

    } else if(isset($_POST['edit'])) {

        //EDITAR TARIFA
        // Tengo que recuperar los datos del domain
        $arrDomain= $pEmail->getDomains($_POST['id_domain']);
        $arrFillDomain['domain_name']      = $arrDomain[0][1];
        // Implementar
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);
        $oForm->setEditMode();
        $smarty->assign("id_domain", $_POST['id_domain']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_domain.tpl", "{$arrLang['Edit Domain']} \"" . $arrFillDomain['domain_name'] . "\"", $arrFillDomain);

    } else if(isset($_POST['save'])) { 
        //GUARDAR NUEVA DOMINIO
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);
        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $bExito=create_email_domain($pDB,$error);
            if (!$bExito){
               $smarty->assign("mb_message", $error);
               $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_domain.tpl", $arrLang["New Domain"], $_POST);
            }
            else
                header("Location: ?menu=email_domains");
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_domain.tpl", $arrLang["New Domain"], $_POST);
        }

    } else if(isset($_POST['apply_changes'])) {
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        $oForm->setEditMode();
        if($oForm->validateForm($_POST)) {

            $bExito=$pEmail->updateDomain($_POST['id_domain'],$_POST['domain_name']);
            header("Location: ?menu=email_domains");
        } else {
            // Manejo de Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);


            $smarty->assign("id_domain", $_POST['id_domain']);

            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_domain.tpl", $arrLang["Edit Domain"], $_POST);
            /////////////////////////////////
        }

    } else if(isset($_GET['action']) && $_GET['action']=="view") {
;
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error

        $oForm->setViewMode(); // Esto es para activar el modo "preview"
        $arrDomain = $pEmail->getDomains($_GET['id']);
        // Conversion de formato
        $arrTmp['domain_name']        = $arrDomain[0][1];
        $arrTmp['id_domain']        = $arrDomain[0][0];

        if (isset($_POST['delete'])) {
         // $bExito=$pEmail->deleteDomain($_POST['id_domain']);
          $bExito=eliminar_dominio($pDB,$arrTmp,$errMsg);
          
          if (!$bExito) $smarty->assign("mb_message", $errMsg);
          else header("Location: ?menu=email_domains");
        }

        $smarty->assign("id_domain", $_GET['id']);
        
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_domain.tpl", $arrLang["View Domain"], $arrTmp); // hay que pasar el arreglo

    } 

    else{

        //LISTADO DE DOMINIOS


        $arrDomains = $pEmail->getDomains();
     //   $arrDomains=array(array(1,"Prueba"));
        $end = count($arrDomains);

        foreach($arrDomains as $domain) {
            $arrTmp    = array();

            $arrTmp[0] = "&nbsp;<a href='?menu=email_domains&action=view&id=".$domain[0]."'>$domain[1]</a>";
            //obtener el numero de cuentas que posee ese email
            $arrTmp[1] = $pEmail->getNumberOfAccounts($domain[0]);


            $arrData[] = $arrTmp;
        }
        
        $arrGrid = array("title"    => $arrLang["Domain List"],
                         "icon"     => "images/list.png",
                         "width"    => "99%",
                         "start"    => ($end==0) ? 0 : 1,
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => $arrLang["Domain"],
                                                        "property1" => ""),
                                             1 => array("name"      => $arrLang["Number of Accounts"], 
                                                        "property1" => ""),
                                            )
                        );

        $oGrid = new paloSantoGrid($smarty);
        $oGrid->showFilter(
              "<form style='margin-bottom:0;' method='POST' action='?menu=email_domains'>" .
              "<input type='submit' name='submit_create_domain' value='{$arrLang['Create Domain']}' class='button'></form>");
        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }

    return $contenidoModulo;
}

//funciones separadas

function create_email_domain($pDB,&$errMsg)
{
    $bReturn=FALSE;
    $pEmail = new paloEmail($pDB);
    //creo el dominio en la base de datos
    $bExito=$pEmail->createDomain($_POST['domain_name']);
    if ($bExito){
        $bReturn=guardar_dominio_sistema($_POST['domain_name'],$errMsg);
    }else
        $errMsg= (isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg;
    return $bReturn;

}

?>