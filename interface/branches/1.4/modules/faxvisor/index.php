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
  $Id: index.php,v 1.2 2007/09/07 01:18:43 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoForm.class.php";
    //include_once "libs/paloSantoFax.class.php";
    include_once "libs/paloSantoFaxVisor.class.php";
    include_once "libs/paloSantoDB.class.php";
	include_once "libs/xajax/xajax.inc.php";
	
	//include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']); 
    $templates_dir = (isset($arrConfig['templates_dir'])) ? $arrConfig['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
	$contenidoModulo='';

    $arrFormElements = getFormElements( $arrLang );
    $oForm = new paloForm($smarty, $arrFormElements);   
    $smarty->assign("SEARCH", $arrLang["Search"]);    
    $javascript_xajax = ajax_faxes($arrConfig); //En paloSantoFax.class.php
    $smarty->assign("javascript_xajax", $javascript_xajax);

    $accion = getAction();
    $content = "";

    switch($accion){
        case "view":
            $contenidoModulo = editFax($smarty, $module_name, $arrLang, $oForm, $local_templates_dir);
            break;
        case "save":
            $contenidoModulo = updateFax($smarty, $module_name, $arrLang, $oForm, $local_templates_dir);
            break;
        case "cancel":
            header("Location: ?menu=$module_name");
            break;
        default:
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/visor.tpl", $arrLang["Fax Visor"],$_POST);
            break;
    }
    return $contenidoModulo;
}

function updateFax($smarty, $module_name, $arrLang, $oForm, $local_templates_dir)
{
    $oFax = new paloFaxVisor();
    $idFax = getParameter('id');
    $company_name = getParameter('name_company');
    $company_fax = getParameter('fax_company');
    $error=false;
    if(empty($company_name) || empty($company_fax))
    {
        $smarty->assign("mb_title", $arrLang['ERROR'].":");
        $smarty->assign("mb_message", $arrLang["ERROR"]);
        $error=true;
    }

    if(!$error && !$oFax->updateInfoFaxFromDB($idFax, $company_name, $company_fax)){
        $smarty->assign("mb_title", $arrLang['ERROR'].":");
        $smarty->assign("mb_message", $arrLang["ERROR"]);
        $error=true;
    }

    if($error){
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $smarty->assign("id_fax", $idFax);

        $htmlForm = $oForm->fetchForm("$local_templates_dir/edit.tpl", $arrLang["Edit"],$_POST);
        return "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    }
    else{
        header("Location: ?menu=$module_name");
    }
}

function editFax($smarty, $module_name, $arrLang, $oForm, $local_templates_dir)
{
    $arrData = array();
    $oFax = new paloFaxVisor();
    $idFax = getParameter('id');

    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("id_fax", $idFax);

    $arrDataFax = $oFax->obtener_fax($idFax);
    if(is_array($arrDataFax) && count($arrDataFax)>0){
        $arrData['name_company'] = $arrDataFax['company_name'];
        $arrData['fax_company'] = $arrDataFax['company_fax'];
    }
    $htmlForm = $oForm->fetchForm("$local_templates_dir/edit.tpl", $arrLang["Edit"],$arrData);
    return "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
}

function getFormElements( $arrLang )
{
    return  array("name_company"=> array("LABEL"                  => $arrLang["Company Name"],
                                         "REQUIRED"               => "no",
                                         "INPUT_TYPE"             => "TEXT",
                                         "INPUT_EXTRA_PARAM"      => "",
                                         "VALIDATION_TYPE"        => "text",
                                         "VALIDATION_EXTRA_PARAM" => ""),
                  "fax_company" => array("LABEL"                  => $arrLang["Company Fax"],
                                         "REQUIRED"               => "no",
                                         "INPUT_TYPE"             => "TEXT",
                                         "INPUT_EXTRA_PARAM"      => "",
                                         "VALIDATION_TYPE"        => "email",
                                         "VALIDATION_EXTRA_PARAM" => ""),
                  "date_fax"    => array("LABEL"                  => $arrLang["Fax Date"],
                                         "REQUIRED"               => "no",
                                         "INPUT_TYPE"             => "DATE",
                                         "INPUT_EXTRA_PARAM"      => array("TIME" => false, "FORMAT" => "%Y-%m-%d","TIMEFORMAT" => "12"),
                                         "VALIDATION_TYPE"        => "text",
                                         "VALIDATION_EXTRA_PARAM" => ""),
                  "filter"      => array("LABEL"                  => $arrLang["Type Fax"],
                                         "REQUIRED"               => "no",
                                         "INPUT_TYPE"             => "SELECT",
                                         "INPUT_EXTRA_PARAM"      => array("All"=>$arrLang["All"],"In"=>$arrLang["in"],"Out"=>$arrLang["out"]),
                                         "VALIDATION_TYPE"        => "text",
                                         "VALIDATION_EXTRA_PARAM" => ""),
        );
}

function getParameter($parameter)
{
    if(isset($_POST[$parameter]))
        return $_POST[$parameter];
    else if(isset($_GET[$parameter]))
        return $_GET[$parameter];
    else
        return null;
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if(getParameter("save"))
        return "save";
    else if(getParameter("cancel"))
        return "cancel";
    else if(getParameter("action")=="view") //Get parameter by GET (command pattern, links)
        return "view";
    else
        return "report";
}


//IMPLEMENTACION DE VISOR DE FAXES CON XAJAX EN EL MODULO EXTRAS
function ajax_faxes($arrConfig)
{
    $base_dir=dirname($_SERVER['SCRIPT_NAME']);
    if($base_dir=="/")
        $base_dir="";

    include_once $base_dir.$arrConfig['xajax_path_lib']."xajax.inc.php";
//     echo $base_dir.$arrConfig['xajax_path_lib']."xajax.inc.php";
     //instanciamos el objeto de la clase xajax
    $xajax = new xajax();
    //asociamos la función creada anteriormente al objeto xajax
    $xajax->registerFunction("faxes");
    $xajax->registerFunction("deleteFaxes");
//     if($xajax->canProcessRequests()){        
        //El objeto xajax tiene que procesar cualquier petición        
        $xajax->processRequests();  
//     }
    //En el {$javascript_xajax} indicamos al objeto xajax se encargue de generar el javascript necesario
    $javascript_xajax = $xajax->printJavascript($base_dir.$arrConfig['xajax_path_lib'],"xajax_js/xajax.js");
    return $javascript_xajax;
}

function faxes($company_name,$company_fax,$fecha_fax,$inicio_paginacion,$accion,$type){
    $tamanio_busqueda = 20;
    $oFax = new paloFaxVisor(); 
    $cantidad_faxes = $oFax->obtener_cantidad_faxes($company_name,$company_fax,$fecha_fax,$type);
    switch($accion)
    {
//         case 'search':
//             $offset = 0;
//             break;
        case 'next':
            $offset = $inicio_paginacion + $tamanio_busqueda;
            break;
        case 'previous':
            $offset = $inicio_paginacion - $tamanio_busqueda ;
            break;
//         case 'start':
//             $offset = 0;
//             break;
        case 'end':
            $pagina = floor($cantidad_faxes/$tamanio_busqueda);
            $offset = $pagina * $tamanio_busqueda; 
            break;
        default: //accion=search,start
            $offset = 0;
            break;
    } 
   
    
    $arr_faxes = $oFax->obtener_faxes($company_name,$company_fax,$fecha_fax,$offset,$tamanio_busqueda,$type);

    $html_faxes = html_faxes($arr_faxes);
    $html_paginacion = html_paginacion_faxes($offset,$cantidad_faxes,$tamanio_busqueda,"crm/themes/Sugar/images");

    $respuesta = new xajaxResponse();
    $respuesta->addAssign("td_paginacion","innerHTML",$html_paginacion);
    $respuesta->addAssign("td_contenido","innerHTML",$html_faxes);

   //tenemos que devolver la instanciación del objeto xajaxResponse
    return $respuesta;
}

function html_faxes($arr_faxes)
{ 
    global $arrLang;
    $fax_folder="";
    $en_proceso="  file in process..";
	$oFax = new paloFaxVisor();
    $newfile = "";

    $self=dirname($_SERVER['SCRIPT_NAME']);
    if($self=="/")
      $self="";

    $nodoTablaInicio = "<table border='0' cellspacing='0' cellpadding='0' width='100%' align='center'>
                            <tr class='table_title_row'>
                                <td class='table_title_row'><input type='button' name='faxes_delete' class='button' value='".$arrLang['Delete']."' onclick=\"if(confirmSubmit('{$arrLang["Are you sure you wish to delete fax (es)?"]}')) elimimar_faxes();\" /></td>
                                <td class='table_title_row'>".$arrLang['Type']."</td>
                                <td class='table_title_row'>".$arrLang['File']."</td>
                                <td class='table_title_row'>".$arrLang['Company Name']."</td>
                                <td class='table_title_row'>".$arrLang['Company Fax']."</td>
                                <td class='table_title_row'>".$arrLang['Fax Destiny']."</td>
                                <td class='table_title_row'><center>".$arrLang['Fax Date']."</center></td>
					            <td class='table_title_row'>".$arrLang['Options']."</td>
                            </tr>\n";
    $nodoTablaFin    = "</table>";
    $nodoContenido ="";

    if(is_array($arr_faxes)&& count($arr_faxes)>0)
    {
        foreach($arr_faxes as $key => $fax)
        {
            $nodoContenido .= "<tr style='background-color: rgb(255, 255, 255);' onmouseover="."this.style.backgroundColor='#f2f2f2';"." onmouseout="."this.style.backgroundColor='#ffffff';".">\n";
            $nodoContenido .= " <td class='table_data'><input type='checkbox' name='faxpdf_".$fax['pdf_file']."' id='faxpdf_".$fax['pdf_file']."' /></td>\n";
            $nodoContenido .= " <td class='table_data'>".$arrLang[$fax['type']]."</td>\n";
            if($fax['type'] == "in" || $fax['type'] == "IN"){
	     		$fax_folder="/faxes/recvq/";
                $nodoContenido .= " <td class='table_data'><a href='".$self."/faxes/".$fax['faxpath'].'/fax.pdf'."'".">".$fax['pdf_file']."</a></td>\n";
            }else{
                /*Codigo de validacion de archivo doc13.ps to doc13.ps2 to doc13.pdf */
                $newfile = $fax['pdf_file'];
				$tmp_pd = "";
                if(ereg("(\.ps)",$fax['pdf_file'])){
                    $newfileTmp = $oFax->testFile($fax['pdf_file']);
                    if($newfileTmp != "") //ya existe el archivo y esta completo
                        $newfile = $newfileTmp;
                }
		$fax_folder="/faxes/sendq/";
                if(ereg("(\.pdf)",$fax['pdf_file'])){
$nodoContenido .= " <td class='table_data'><a href='".$self."/faxes/".$fax['faxpath'].'/fax.pdf'."'".">".$newfile."</a></td>\n";
				}else
                	$nodoContenido .= " <td class='table_data'>$newfile</td>\n";
            }			

            $nodoContenido .= " <td class='table_data'>".$fax['company_name']."</td>\n";
            $nodoContenido .= " <td class='table_data'>".$fax['company_fax']."</td>\n";
            $nodoContenido .= " <td class='table_data'>".$fax['destiny_name']." - ".$fax['destiny_fax']."</td>\n";
            $nodoContenido .= " <td class='table_data'><center>".$fax['date']."</center></td>\n";
            $nodoContenido .= " <td class='table_data'><a href='?menu=faxvisor&action=view&id=".$fax['id']."'>".$arrLang['Edit']."</a></td>\n";
            $nodoContenido .= "</tr>\n";
        }
    }
    else
    {
         $nodoContenido .= "<tr><td colspan='8'><center>".$arrLang['No Data Found']."</center></td></tr>";
    }
    return $nodoTablaInicio.$nodoContenido.$nodoTablaFin;
}

function html_paginacion_faxes($regPrimeroMostrado,$regTotal,$tamanio_busqueda,$ruta_image='images')
{
    global $arrLang;
    
    if($regTotal <= $regPrimeroMostrado + $tamanio_busqueda)
        $regUltimoMostrado = $regTotal;
    else
        $regUltimoMostrado = $regPrimeroMostrado + $tamanio_busqueda;
    
    $pagTotal = ($regTotal / $tamanio_busqueda);
    $pagActual= ($regPrimeroMostrado / $tamanio_busqueda) + 1;

    
    if($pagActual > 1){
        $parteIzquierda  = "<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('start');   "."><img src='$ruta_image/start.gif' width='13' height='11' alt='' border='0' align='absmiddle' /></a>&nbsp;".$arrLang['Start']."&nbsp;";
        $parteIzquierda .= "<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('previous');"."><img src='$ruta_image/previous.gif' width='8' height='11' alt='' border='0' align='absmiddle' /></a>&nbsp;".$arrLang['Previous'];
    }
    else{
        $parteIzquierda  = "<img src='$ruta_image/start_off.gif' width='13'   height='11' alt='' align='absmiddle' />&nbsp;".$arrLang['Start']."&nbsp;";
        $parteIzquierda .= "<img src='$ruta_image/previous_off.gif' width='8' height='11' alt='' align='absmiddle' />&nbsp;".$arrLang['Previous'];
    }

    $search_title = "(".($regPrimeroMostrado + 1)." - ".$regUltimoMostrado." of ".$regTotal.")";
    $parteCentro  = "&nbsp;<span class='pageNumbers'>".$search_title."</span> 
     <input type='hidden' name='primer_registro_mostrado_paginacion' id='primer_registro_mostrado_paginacion' value='".$regPrimeroMostrado."' />
     <input type='hidden' name='ultimo_registro_mostrado_paginacion' id='ultimo_registro_mostrado_paginacion' value='".$regUltimoMostrado."' />
     <input type='hidden' name='total_registros_paginacion'          id='total_registros_paginacion'          value='".$regTotal."' />"; 

    if($pagActual < $pagTotal){
        $parteDerecha  = $arrLang['Next']."&nbsp;<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('next');"."><img src='$ruta_image/next.gif' width='8'  height='11' alt='' border ='0' align='absmiddle' /></a>&nbsp;";
        $parteDerecha .= $arrLang['End']."&nbsp;<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('end');  "."><img src='$ruta_image/end.gif'  width='13' height='11' alt='' border ='0' align='absmiddle' /></a>";
    }
    else{
        $parteDerecha  = $arrLang['Next']."&nbsp;<img src='$ruta_image/next_off.gif' width='8'  height='11' alt='' align='absmiddle' />&nbsp;";
        $parteDerecha .= $arrLang['End']."&nbsp;<img src='$ruta_image/end_off.gif'  width='13' height='11' alt='' align='absmiddle' />";
    }
    return " <table class='table_navigation_text' align='center' cellspacing='0' cellpadding='0' width='100%' border='0'
                <tr><td align='right'>".$parteIzquierda.$parteCentro.$parteDerecha."</td></tr></table>";
}

function deleteFaxes($csv_files,$company_name,$company_fax,$fecha_fax,$inicio_paginacion,$type_filter)
{
    global $arrLang;
    $arrFaxes = explode(",",$csv_files); 
    $oFax = new paloFaxVisor(); 
    $msgError = "";
	$path_file = "";

    if(is_array($arrFaxes) && count($arrFaxes) > 0){
        foreach($arrFaxes as $key => $pdf_file){
	    $path_file = $oFax->getPathByPdfFile($pdf_file);
	    $oFax->_db->conn->beginTransaction();
            if($oFax->deleteInfoFaxFromDB($pdf_file)){//validar error $tmp_file               
                if(!$oFax->deleteInfoFaxFromPathFile($path_file)){
		    $msgError = $arrLang["Unable to eliminate pdf file from the path."];
		    $oFax->_db->conn->rollBack();
		}
		else $oFax->_db->conn->commit();
	    }
            else $msgError = $arrLang["Unable to eliminate pdf file from the database."];
        }
    }

    $respuesta = faxes($company_name,$company_fax,$fecha_fax,$inicio_paginacion,"search",$type_filter);
    if($msgError!="")
	$respuesta->addAlert($msgError);
    return $respuesta;
}



?>
