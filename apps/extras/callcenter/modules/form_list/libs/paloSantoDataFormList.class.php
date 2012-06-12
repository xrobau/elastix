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
  $Id: formulario $ */
//require_once("libs/js/jscalendar/calendar.php"); 
require_once("libs/smarty/libs/Smarty.class.php");
include_once("libs/paloSantoDB.class.php");
/* Clase que implementa Formulario de Campanign de CallCenter (CC) */
class paloSantoDataForm
{
    var $_db; // instancia de la clase paloDB
    var $errMsg;
    var $rutaDB;
    function paloSantoDataForm($pDB)
    {
        global $arrLang;
        //$this->rutaDB = "/var/www/db/campaign.db";
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_db =& $pDB;
            $this->errMsg = $this->_db->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_db = new paloDB($dsn);

            if (!$this->_db->connStatus) {
                $this->errMsg = $this->_db->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }

    }

    function getFormularios($id_formulario = NULL,$estatus='all')
    {
        global $arrLang;

        $arr_result = FALSE;
        
        $where = "";
        if($estatus=='all')
            $where .= "where 1";
        else if($estatus=='A')
            $where .= "where f.estatus='A'";
        else if($estatus=='I')
            $where .= "where f.estatus='I'";
        if(!is_null($id_formulario))
            $where .= " and f.id = $id_formulario";

        if (!is_null($id_formulario) && !ereg('^[[:digit:]]+$', "$id_formulario")) {
            $this->errMsg = $arrLang["Form ID is not valid"];
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT f.id, f.nombre, f.descripcion, f.estatus FROM form f $where";
            $arr_result =& $this->_db->fetchTable($sPeticionSQL, true);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }


        return $arr_result;
    }

    function obtener_campos_formulario($id_formulario,$id_campo=NULL)
    {
        $respuesta = new xajaxResponse();
        global $arrLangModule;
        $smarty = $this->getSmarty();
        $errMsg=""; 
        $sqliteError='';
        $arrReturn=array();
        $where="";
        $codigo_js="";
        if(!is_null($id_campo))
            $where = " and fd.id=$id_campo";

        $query  = "
                    SELECT  fd.id id_field, fd.etiqueta, fd.value value_field, fd.tipo, fd.orden, fd.id_form 
                    FROM  form_field fd
                    where fd.id_form = $id_formulario $where order by fd.orden";

        $arr_fields = $this->_db->fetchTable($query, true);
//print_r($arr_fields);
        if (is_array($arr_fields) && count($arr_fields)>0) {

            $id = $arr_fields[0]["id_form"];
            foreach($arr_fields as $key=>$field) {
                $funcion_js = "";
                $input = $this->crea_objeto($smarty, $field, "", $funcion_js);
                //echo $input;
                $etiqueta = $field["etiqueta"];
                $tipo = $field["tipo"];
                $data_field[] = array("TYPE" => $tipo, "TAG" => $etiqueta, "INPUT" => $input, "ID_FORM" => $id);
                //$data_field[] = array("TAG" => $etiqueta, "INPUT" => $input, "ID_FORM" => $id);
                $id = "";
                $codigo_js .= $funcion_js;
                //echo $codigo_js;
                $smarty->assign("FORMULARIO", $data_field);
                $smarty->assign("formularios", $arrLangModule["Form"]);
                $mostrar_template=true;
            }
            if ($mostrar_template) $template = "formulario.tpl";
            else $template = "vacio.tpl";
        }else{
            global $arrLang;
            //$smarty->assign("no_definidos_formularios",$arrLangModule['Forms Nondefined']);
            $template = "vacio.tpl";
        }
        if (isset($codigo_js) && trim($codigo_js)!="") {
           $respuesta->addScript($codigo_js);
        }
        $texto_formulario=$smarty->fetch("file:/var/www/html/modules/form_list/themes/default/$template");
        return $texto_formulario;
        //$respuesta->addAssign("contenedor_formulario","innerHTML",$texto_formulario);
        

    }
    function getSmarty() {
    global $arrConf;
    $smarty = new Smarty();
    $smarty->template_dir = "themes/default/";
    $smarty->compile_dir =  "var/templates_c/";
    $smarty->config_dir =   "configs/";
    $smarty->cache_dir =    "var/cache/";
    return $smarty;
    }

    function crea_objeto(&$smarty, $field, $prefijo_objeto, &$funcion_js) {
        $tipo_objeto = $field["tipo"];
        $input="";
        switch ($tipo_objeto) {
            case "LIST":
                $listado = explode(",",$field["value_field"]);
                $input = "";
                $selected="";
                foreach($listado as $key=>$item) {
                    //if ($field["value_data"] == $item) $selected = "selected";
                    //else $selected="";
                    if($item!="") $input .= "<option value='$item'>$item</option>";
                }
                if ($input!="") {
                    $input = "<select name='$prefijo_objeto"."$field[id_field]' id='$prefijo_objeto"."$field[id_field]' class='SELECT'>$input</select>";
                }
            break;
            case "DATE":
                $input = $this->calendario("txt_".$field['id_field'],"btn_".$field['id_field']);
            break;
            case "TEXTAREA":
                $input = "<textarea name='$prefijo_objeto"."$field[id_field]' id='$prefijo_objeto"."$field[id_field]' rows='3' cols='50'></textarea>";
            break;
            case "LABEL":
                $input = "<label class='style_label'>$field[etiqueta]</label>";
            break;
            default:
                $input = "<input type='text' name='$prefijo_objeto"."$field[id_field]' id='$prefijo_objeto"."$field[id_field]' value='' class='INPUT'>";
        }

        return $input;
    }

    function calendario($id_txt,$btn_txt) {

    return 
    "
    <td>
            <input name='name_$id_txt' id='$id_txt' type='text' 
            style='width: 10em; color: #840; background-color: #fafafa; border: 1px solid #999999; text-align: center'/>
            <a href='#' id='$btn_txt'>
                <img align='middle' border='0' src='/libs/js/jscalendar/img.gif' alt='' />
            </a>
    </td>

    <script type='text/javascript'>
        Calendar.setup(
            {
                'ifFormat':'%Y-%m-%d',
                'daFormat':'%Y-%m-%d',
                'firstDay':1,
                'showsTime':true,
                'showOthers':true,
                'timeFormat':24,
                'inputField':'$id_txt',
                'button':'$btn_txt'
            }
        );
    </script> 
    
    " ;
    
    }

}


/*---------------------------------codigo no usado -----------------------*/
/*
function crea_objeto(&$smarty, $field, $prefijo_objeto, &$funcion_js) {
        $tipo_objeto = $field["tipo"];
        $input="";
        switch ($tipo_objeto) {
            case "LIST":
                $listado = explode(",",$field["value_field"]);
                $input = "";
                $selected="";
                foreach($listado as $key=>$item) {
                    //if ($field["value_data"] == $item) $selected = "selected";
                    //else $selected="";
                    if($item!="") $input .= "<option value='$item'>$item</option>";
                }
                if ($input!="") {
                    $input = "<select name='$prefijo_objeto"."$field[id_field]' id='$prefijo_objeto"."$field[id_field]' class='SELECT'>$input</select>";
                }
            break;
            case "DATE":
                //require_once("libs/js/jscalendar/calendar.php");
                    $input = $this->calendario("txt_".$field['id_field'],"btn_".$field['id_field']);


                        $time = false;
                        $format = '%d %b %Y';
                        $timeformat = '12';
                      
                        $oCal = new DHTML_Calendar("/libs/js/jscalendar/", "en", "calendar-win2k-2", $time);
                        $smarty->assign("HEADER", $oCal->load_files());

                        $strInput = $oCal->make_input_field(
                                        array('firstDay'       => 1, // show Monday first
                                              'showsTime'      => true,
                                              'showOthers'     => true,
                                              'ifFormat'       => $format,
                                              'timeFormat'     => $timeformat),
                                        // field attributes go here
                                        array('style'          => 'width: 10em; color: #840; background-color: #fafafa; ' .
                                                                   'border: 1px solid #999999; text-align: center',
                                              'name'        => $field["id_field"],
                                              //'value'       => strftime('%d %b %Y', strtotime('now'))));
                                              'value'       => $arrPreFilledValues[$varName]));
//echo "<br>";
//echo $strInput;
//echo "<br>";
                        //echo "hola".$strInput."fin";
                $input=$strInput;
                $input = '<input style="width: 10em; color: #840; background-color: #fafafa; border: 1px solid #999999; text-align: center" name="'.$prefijo_objeto.$field["id_field"].'" value="" id="'.$prefijo_objeto.$field["id_field"].'" type="text" />
                <a href="#" id="calendar_'.$prefijo_objeto.$field["id_field"].'">
                <img align="middle" border="0" src="/libs/js/jscalendar/img.gif" alt="" />
                </a>';
    
                $funcion_js = 'Calendar.setup({"ifFormat":"%d %b %Y","daFormat":"%Y/%m/%d","firstDay":1,"showsTime":true,"showOthers":true,"timeFormat":12,"inputField":"'.$prefijo_objeto.$field['id_field'].'","button":"calendar_'.$prefijo_objeto.$field['id_field'].'"});';
    
            break;
            case "TEXTAREA":
                $input = "<textarea name='$prefijo_objeto"."$field[id_field]' id='$prefijo_objeto"."$field[id_field]' rows='3' cols='50'></textarea>";
            break;
            case "LABEL":
                $input = "<label class='style_label'>$field[etiqueta]</label>";
            break;
            default:
                $input = "<input type='text' name='$prefijo_objeto"."$field[id_field]' id='$prefijo_objeto"."$field[id_field]' value='' class='INPUT'>";
        }

        return $input;
    }
*/
/*
function html_campos_formulario($arr_campos,$edit=true)
    { 
        global $arrLang;
        global $arrLangModule;
        $self=dirname($_SERVER['SCRIPT_NAME']);
        if($self=="/")
        $self="";
        $msm_confimacion = $arrLang['Are you sure you wish to continue?'];
        $nodoTablaInicio = "<table border='0' cellspacing='0' cellpadding='0' width='100%' align='center'>
                                <tr class='table_title_row'>";
        if($edit)
            $nodoTablaInicio .= "   <td class='table_title_row' width='40'><input type='button' name='delete_field' id='delete_field' onclick='"."if(confirmSubmit(\"$msm_confimacion\"))eliminar_campo();"."' value='".$arrLang['Delete']."' /></td> ";
        $nodoTablaInicio .= "       <td class='table_title_row' width='50'>".$arrLangModule['Order']."</td>
                                    <td class='table_title_row'>".$arrLangModule['Field Name']."</td>
                                    <td class='table_title_row'>".$arrLang['Type']."</td>
                                    <td class='table_title_row'>".$arrLangModule['Values Field']."</td>";
        if($edit)
            $nodoTablaInicio .= "       <td class='table_title_row'>".$arrLang['Options']."</td> 
                                </tr>\n";
        $nodoTablaFin    = "</table>";
        $nodoContenido ="";
    
        if(is_array($arr_campos)&& count($arr_campos)>0){
            foreach($arr_campos as $key => $field) {
                $nodoContenido .= "<tr style='background-color: rgb(255, 255, 255);' onmouseover="."this.style.backgroundColor='#f2f2f2';"." onmouseout="."this.style.backgroundColor='#ffffff';".">\n";
                if($edit)
                    $nodoContenido .= ETQUETA" <td class='table_data'><center><input type='checkbox' id='field-".$field['id']."' name='field_chk' /></center></td>\n";
                $nodoContenido .= " <td class='table_data'>".$field['orden']."</td>\n";
                $nodoContenido .= " <td class='table_data'>".$field['etiqueta']."</td>\n";
                $nodoContenido .= " <td class='table_data'>".$arrLangModule[$field['tipo']]."</td>\n";
                if($field['value']=="")
                    $value = "&nbsp;";
                else $value = $field['value'];
                $nodoContenido .= " <td class='table_data'>".$value."</td>\n";
                if($edit)
                    $nodoContenido .= " <td class='table_data'><a href='javascript:void(0);' onclick='editar_campo(".$field['id'].")'>".$arrLang['Edit']."</a></td>\n";
                $nodoContenido .= "</tr>\n";
            }
        }
        else{
            $nodoContenido .= "<tr><td colspan='6'><center>".$arrLang['No Data Found']."</center></td></tr>";
        }
        return $nodoTablaInicio.$nodoContenido.$nodoTablaFin;
    }


*/


?>
