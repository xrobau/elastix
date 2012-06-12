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

include_once("libs/paloSantoDB.class.php");
/* Clase que implementa Formulario de Campanign de CallCenter (CC) */
class paloSantoDataForm
{
    var $_db; // instancia de la clase paloDB
    var $errMsg;
    var $rutaDB;
  
    function paloSantoDataForm($pDB)
    {
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
            $this->errMsg = _tr("Form ID is not valid");
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
        $errMsg=""; 
        $sqliteError='';
        $arrReturn=array();
        $where="";
        if(!is_null($id_campo))
            $where = " and fd.id=$id_campo";

        $query  = "
                    SELECT  fd.id, fd.etiqueta, fd.value, fd.tipo, fd.orden 
                    FROM  form_field fd
                    where fd.id_form = $id_formulario $where order by fd.orden";

        $result =& $this->_db->fetchTable($query, true);


        return $result;
    }

    function agregar_campo_formulario($id_formulario,$etiqueta,$value,$tipo,$orden)
    {
        $query  = "INSERT INTO form_field(id_form, etiqueta, value, tipo, orden )
                   VALUES ($id_formulario,'$etiqueta','$value','$tipo',$orden)";
    
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function actualizar_campo_formulario($id_campo,$etiqueta,$value,$tipo,$orden)
    {
        $query  = "UPDATE form_field 
                    set etiqueta = '$etiqueta', 
                        value = '$value',
                        tipo = '$tipo',
                        orden = $orden
                   WHERE id=$id_campo";
    
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function existe_formulario($id_formulario)
    {
        $sqliteError='';
        $Return=-1;
        $query  = "
                SELECT  count(*) cantidad 
                FROM  form 
                where id = $id_formulario";

        $result =&$this->_db->fetchTable($query, true);
        if(count($result)>0 && is_array($result)){
             $Return=$result[0]['cantidad'];
        }    
        else {
            $this->errMsg = $this->_db->errMsg;
        }
        return $Return;
    }

    function crear_formulario($id_formulario,$nombre,$descripcion)
    {
        $query  = "INSERT INTO form(id,nombre,descripcion)
                   VALUES ($id_formulario,'$nombre','$descripcion')";
    
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function eliminar_formulario($id_formulario)
    {
        if($this->eliminar_campos_formulario($id_formulario)){
            $query  = "DELETE FROM form
                    WHERE id = $id_formulario";
        
            $bExito = $this->_db->genQuery($query);
            if (!$bExito) {
                $this->errMsg = $this->_db->errMsg;
                return false;
            }
            return true;
        }
        else return false;
    }
    
    function eliminar_campos_formulario($id_formulario,$id_campo=NULL)
    {
        if (is_null($id_campo) || $id_campo=="") {
            $sQuery = "SELECT count(id) cantidad_data FROM form_data_recolected WHERE id_form_field in (
                            SELECT id FROM form_field WHERE id_form=$id_formulario)";
        } else {
            $sQuery = "SELECT count(id) cantidad_data FROM form_data_recolected WHERE id_form_field = $id_campo";
        }
        $result =& $this->_db->getFirstRowQuery($sQuery, true);

        if (isset($result)) {
            if ($result["cantidad_data"]==0) {
                $where="";
                if(!is_null($id_campo))
                    $where = " and id=$id_campo";
        
                $query  = "DELETE FROM form_field
                        WHERE id_form = $id_formulario".$where;
            
                $bExito = $this->_db->genQuery($query);
                if (!$bExito) {
                    $this->errMsg = $this->_db->errMsg;
                    return false;
                }
                return true;
            } else {
                $this->errMsg = _tr("This field is been used by any campaign")." ".$sQuery;
                return false;
            }
        } else {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
    }

    function actualizar_formulario($id_formulario,$nombre,$descripcion)
    {
         $query  = "UPDATE form set 
                    nombre = '$nombre',
                    descripcion = '$descripcion'
                    WHERE id = $id_formulario";
    
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }
    
    function eliminado_logico_formulario($id_formulario)
    {
         $query  = "UPDATE form set 
                    estatus = 'I'
                    WHERE id = $id_formulario";
    
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function activar_formulario($id_formulario)
    {
         $query  = "UPDATE form set 
                    estatus = 'A'
                    WHERE id = $id_formulario";
    
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function proximo_id_formulario()
    {
        $sqliteError='';
        $Return=0;
        
        $query  = "
                SELECT  max(id) maximo 
                FROM  form";

        $result = &$this->_db->fetchTable($query, true);
        if(count($result)>0 && is_array($result)){
              $Return=$result[0]['maximo'] + 1;
        }
        else {
            $this->errMsg = $this->_db->errMsg;
        }
        return $Return;
    }

    function field_order_existe($id_formulario,$orden_campo)
    {
        $errMsg=""; 
        $sqliteError='';
        $arrReturn=array();
        
            $query  = "
                    SELECT  count(*) existe 
                    FROM  form_field
                    where id_form = $id_formulario and orden = $orden_campo";
    
            $result = &$this->_db->fetchTable($query, true);
            if(count($result)>0 && is_array($result)){
                    $Return=$result[0]['existe'];
            }
            else {
                $this->errMsg = $this->_db->errMsg;
            }
        return $Return;
    }

    function delete_form($id_formulario) {
        $sQuery = "SELECT count(id_campaign) cantidad_campanias FROM campaign_form WHERE id_form=$id_formulario";
        $result =& $this->_db->getFirstRowQuery($sQuery, true);
        $valido = false;
        if (is_array($result) && count($result)>0) {
            if ($result["cantidad_campanias"] == 0) {
                $sQuery = "SELECT count(id) cantidad_data FROM form_data_recolected WHERE id_form_field in (
                            SELECT id FROM form_field WHERE id_form=$id_formulario)";
                if (is_array($result) && count($result)>0) {
                    if ($result["cantidad_data"] == 0) {
                        $result = $this->_db->genQuery("SET AUTOCOMMIT=0");
                        if ($result) {
                            $sql = "DELETE FROM form_field WHERE id_form=$id_formulario";
                            $result = $this->_db->genQuery($sql);
                            if (!$result) {
                                $this->errMsg = $this->_db->errMsg;
                                $this->_db->genQuery("ROLLBACK");
                                $this->_db->genQuery("SET AUTOCOMMIT=1");
                                return false;
                            }

                            $sql = "DELETE FROM form WHERE id=$id_formulario";
                            $result = $this->_db->genQuery($sql);
                            if (!$result) {
                                $this->errMsg = $this->_db->errMsg;
                                $this->_db->genQuery("ROLLBACK");
                                $this->_db->genQuery("SET AUTOCOMMIT=1");
                                return false;
                            }
                            $this->_db->genQuery("COMMIT");
                            $result = $this->_db->genQuery("SET AUTOCOMMIT=1");
                            $valido = true;
                        } else {
                            $valido = false;
                            $this->errMsg = $this->_db->errMsg;
                        }
                    } else {
                        $valido = true;
                        $this->errMsg = _tr("This form is been used by any campaign");
                    }
                } else {
                    $valido = true;
                    $this->errMsg = _tr("This form is been used by any campaign");
                }
            } else {
                $valido = true;
                $this->errMsg = _tr("This form is been used by any campaign");
            }
        } else {
            $valido = true;
            $this->errMsg = _tr("This form is been used by any campaign");
        }
        return $valido;
    }
}

//FUNCIONES PARA LA IMPLEMENTACION XAJAX
function agregar_campos_formulario($id_formulario,$nombre_formulario,$descripcion_formulario,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo)
{
    global $arrConf;

    $respuesta = new xajaxResponse();
    $validar = validar_campos($id_formulario,$nombre_formulario,$descripcion_formulario,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo);
    if($validar=='true'){
        $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
    
        if($oDataForm->existe_formulario($id_formulario)==0) //si no existe lo creo
            $se_creo = $oDataForm->crear_formulario($id_formulario,$nombre_formulario,$descripcion_formulario);
        else 
            $se_creo = $oDataForm->actualizar_formulario($id_formulario,$nombre_formulario,$descripcion_formulario); //ya existe
    
        if($se_creo)
        {
            if($oDataForm->agregar_campo_formulario($id_formulario,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo)){
                $arr_campos = $oDataForm->obtener_campos_formulario($id_formulario); //actualiza la tabla dnd se muestran los campos
                $html_campos = html_campos_formulario($arr_campos);
                $respuesta->addAssign("tabla_campos_agregados","innerHTML",$html_campos);  
                $script = "limpiar_campos(); \n";     
                $respuesta->addScript($script);
                $respuesta->addAssign("mb_title","innerHTML",""); 
                $respuesta->addAssign("mb_message","innerHTML",""); 
                $respuesta->addAssign("mb_msg_ok","innerHTML",_tr("Add Field Successfully"). ":  <b>$etiqueta_campo</b>"); 
             }
            else{
                $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
                $respuesta->addAssign("mb_message","innerHTML",_tr('Field could not be added in the Form').". ".$oDataForm->errMsg); 
            }
        }
        else{
                $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
                $respuesta->addAssign("mb_message","innerHTML",_tr('Form could not be added')); 
        }
    }
    else{
        $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
        $respuesta->addAssign("mb_message","innerHTML",$validar); 
    }
    return $respuesta;
}

function html_campos_formulario($arr_campos,$edit=true)
{ 
    $self=dirname($_SERVER['SCRIPT_NAME']);
    if($self=="/")
      $self="";
    $msm_confimacion = _tr('Are you sure you wish to continue?');
    $nodoTablaInicio = "<table border='0' cellspacing='0' cellpadding='0' width='100%' align='center'>
                            <tr class='table_title_row'>";
    if($edit)
        $nodoTablaInicio .= "   <td class='table_title_row' width='40'><input type='button' name='delete_field' id='delete_field' onclick='"."if(confirmSubmit(\"$msm_confimacion\"))eliminar_campo();"."' value='"._tr('Delete')."' /></td> ";
    $nodoTablaInicio .= "       <td class='table_title_row' width='50'>"._tr('Order')."</td>
                                <td class='table_title_row'>"._tr('Field Name')."</td>
                                <td class='table_title_row'>"._tr('Type')."</td>
                                <td class='table_title_row'>"._tr('Values Field')."</td>";
    if($edit)
        $nodoTablaInicio .= "       <td class='table_title_row'>"._tr('Options')."</td> 
                            </tr>\n";
    $nodoTablaFin    = "</table>";
    $nodoContenido ="";

    if(is_array($arr_campos)&& count($arr_campos)>0){
        foreach($arr_campos as $key => $field) {
            $nodoContenido .= "<tr style='background-color: rgb(255, 255, 255);' onmouseover="."this.style.backgroundColor='#f2f2f2';"." onmouseout="."this.style.backgroundColor='#ffffff';".">\n";
            if($edit)
                $nodoContenido .= " <td class='table_data'><center><input type='checkbox' id='field-".$field['id']."' name='field_chk' /></center></td>\n";
            $nodoContenido .= " <td class='table_data'>".$field['orden']."</td>\n";
            $nodoContenido .= " <td class='table_data'>".$field['etiqueta']."</td>\n";
            $nodoContenido .= " <td class='table_data'>"._tr($field['tipo'])."</td>\n";
            if($field['value']=="")
                $value = "&nbsp;";
            else $value = $field['value'];
            $nodoContenido .= " <td class='table_data'>".$value."</td>\n";
            if($edit)
                $nodoContenido .= " <td class='table_data'><a href='javascript:void(0);' onclick='editar_campo(".$field['id'].")'>"._tr('Edit')."</a></td>\n";
            $nodoContenido .= "</tr>\n";
        }
    }
    else{
         $nodoContenido .= "<tr><td colspan='6'><center>"._tr('No Data Found')."</center></td></tr>";
    }
    return $nodoTablaInicio.$nodoContenido.$nodoTablaFin;
}

function cancelar_formulario_ingreso($id_formulario)
{
    global $arrConf;
    $respuesta = new xajaxResponse();

    $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
    if(!$oDataForm->eliminar_formulario($id_formulario)){
        $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error"));     
        $respuesta->addAssign("mb_message","innerHTML",_tr('Form could not be cancelled'));     
    }
    else
        $respuesta->addScript("window.open('?menu=form_designer','_parent')");
    return $respuesta;
}

function validar_campos($id_formulario,$nombre_formulario,$descripcion_formulario,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo,$actualiza=false)
{
    global $arrConf;

    if(!isset($id_formulario) || $id_formulario=="" || !is_numeric($id_formulario))
        return _tr('Error Id Form');
    if(!isset($nombre_formulario) || $nombre_formulario=="")
        return _tr('Error Form Name is empty');
    if(!isset($etiqueta_campo) || $etiqueta_campo=="")
        return _tr('Error Field Name is empty');
    if((!isset($value_campo) || $value_campo=="") && $tipo_campo=='LIST')
        return _tr('Error List is empty');
    if(!isset($orden_campo) || $orden_campo=="" || !is_numeric($orden_campo))
        return _tr('Error in Order is empty or is not numeric');
    $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
    if($oDataForm->field_order_existe($id_formulario,$orden_campo) && !$actualiza) //si existe
        return _tr('Order already exists');
    
    return 'true';
}

function validar_formulario($id_formulario,$nombre_formulario,$descripcion_formulario)
{
    if(!isset($id_formulario) || $id_formulario=="" || !is_numeric($id_formulario))
        return _tr('Error Id Form');
    if(!isset($nombre_formulario) || $nombre_formulario=="")
        return _tr('Error Form Name is empty');
    return 'true';
}

function guardar_formulario($id_formulario,$form_name,$form_description,$lugar)
{
    global $arrConf;

    $respuesta = new xajaxResponse();

	
    $validar = validar_formulario($id_formulario,$form_name,$form_description);
 	
    if($validar=='true'){
        $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
        if($oDataForm->existe_formulario($id_formulario)==0) //si no existe lo creo
            $se_creo = $oDataForm->crear_formulario($id_formulario,$form_name,$form_description);
        else 
            $se_creo = $oDataForm->actualizar_formulario($id_formulario,$form_name,$form_description); //ya existe
    
        if(!$se_creo){
            $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error"));
            $respuesta->addAssign("mb_message","innerHTML",_tr('Form could not be added')); 
        }
        else{
            if($lugar=='nuevo')
                $respuesta->addScript("window.open('?menu=form_designer','_parent')");
		
            if($lugar=='edit')
                $respuesta->addScript("window.open('?menu=form_designer&action=view&id=$id_formulario','_parent')");
            }
    }
    else{
        $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
        $respuesta->addAssign("mb_message","innerHTML",$validar);
    }
    return $respuesta;
}

function eliminar_campos_formulario($id_formulario,$arr_campos)
{
    global $arrConf;

    $respuesta = new xajaxResponse();

    $ban=true;
    $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
    for($i=0;$i<count($arr_campos);$i++)
    {
        if(!$oDataForm->eliminar_campos_formulario($id_formulario,$arr_campos[$i]))
        {
            $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
            $respuesta->addAssign("mb_message","innerHTML",$oDataForm->errMsg);
            $respuesta->addAssign("mb_msg_ok","innerHTML",""); 
            $ban=false;
            break; 
        }        
    }
    $arr_campos = $oDataForm->obtener_campos_formulario($id_formulario); //actualiza la tabla dnd se muestran los campos
    $html_campos = html_campos_formulario($arr_campos);
    $respuesta->addAssign("tabla_campos_agregados","innerHTML",$html_campos);  
    if($ban) {
        $respuesta->addAssign("mb_title","innerHTML",""); 
        $respuesta->addAssign("mb_message","innerHTML","");
        $respuesta->addAssign("mb_msg_ok","innerHTML",_tr("Delete Field Successfully")); 
    }
    return $respuesta;
}

function editar_campo_formulario($id_formulario,$id_campo)
{
    global $arrConf;

    $respuesta = new xajaxResponse();

    $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
    $campo = $oDataForm->obtener_campos_formulario($id_formulario,$id_campo);
   

    $script  = " document.getElementsByName('field_nombre')[0].value = '".$campo[0]['etiqueta']."'; \n";
    $script .= " document.getElementsByName('order')[0].value = '".$campo[0]['orden']."'; \n";
    $script .= " document.getElementById('type').value = '".$campo[0]['tipo']."'; \n";
    $script .= " document.getElementById('id_campo_act').value = '".$id_campo."'; \n";
    $script .= " presentar_select_item(); \n";
    $script .= " visibilidad_botones_campo(1); \n";

    $respuesta->addAssign("id_estado_field","innerHTML",_tr('Edit Field'));  
    $respuesta->addScript(javascript_option($campo[0]['value'],$campo[0]['tipo']));  
    $respuesta->addScript($script);
    return $respuesta;
}

function javascript_option($value,$tipo)
{
    $arr_values = explode(",",substr($value,0,strlen($value)-1));
    $options = "var select_item = document.getElementById('items'); \n
                var option_tmp; \n";

    if($tipo=='LIST'){
        foreach($arr_values as $key => $value){
            $options .= "option_tmp = document.createElement('option'); \n
                         option_tmp.value = '$value'; \n
                         option_tmp.label = '$value'; \n
                         option_tmp.appendChild(document.createTextNode('$value')); \n
                         select_item.appendChild(option_tmp); \n";
        }
    }
    return $options;
}
function html_option($value,$tipo)
{
    $arr_values = explode(",",substr($value,0,strlen($value)-1));
    $options = "";
    if($tipo=='LIST'){
        foreach($arr_values as $key => $value){
            $options .= "<option label='$value' value='$value'>$value</option>\n";
        }
    }
    return $options;
}

function update_campo_formulario($id_formulario,$nombre_formulario,$descripcion_formulario,$id_campo,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo)
{
    global $arrConf;

    $respuesta = new xajaxResponse();
    $validar = validar_campos($id_formulario,$nombre_formulario,$descripcion_formulario,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo,true);
    if($validar=='true'){
        $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);
    
        if($oDataForm->existe_formulario($id_formulario)==0) //si no existe lo creo
            $se_creo = $oDataForm->crear_formulario($id_formulario,$nombre_formulario,$descripcion_formulario);
        else 
            $se_creo = $oDataForm->actualizar_formulario($id_formulario,$nombre_formulario,$descripcion_formulario); //ya existe
    
        if($se_creo)
        {
            if($oDataForm->actualizar_campo_formulario($id_campo,$etiqueta_campo,$value_campo,$tipo_campo,$orden_campo)){
                $arr_campos = $oDataForm->obtener_campos_formulario($id_formulario); //actualiza la tabla dnd se muestran los campos
                $html_campos = html_campos_formulario($arr_campos);
                $respuesta->addAssign("tabla_campos_agregados","innerHTML",$html_campos);  
                $script  = "\n visibilidad_botones_campo(2); \n";    
                $script .= "limpiar_campos(); \n";     
                $respuesta->addScript($script);
                $respuesta->addAssign("id_estado_field","innerHTML",_tr('Add Field')); 
                $respuesta->addAssign("mb_title","innerHTML",""); 
                $respuesta->addAssign("mb_message","innerHTML",""); 
                $respuesta->addAssign("mb_msg_ok","innerHTML",_tr("Update Field Successfully"). ":  <b>$etiqueta_campo</b>"); 
             }
            else{
                $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
                $respuesta->addAssign("mb_message","innerHTML",_tr('Field could not be updated in the Form')); 
            }
        }
        else{
                $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
                $respuesta->addAssign("mb_message","innerHTML",_tr('Form could not be updated')); 
        }
    }
    else{
        $respuesta->addAssign("mb_title","innerHTML",_tr("Validation Error")); 
        $respuesta->addAssign("mb_message","innerHTML",$validar); 
    }
    return $respuesta;
}

function cancel_campo_formulario()
{
    $respuesta = new xajaxResponse();
    $script = " visibilidad_botones_campo(2); \n limpiar_campos();";     
    $respuesta->addAssign("id_estado_field","innerHTML",_tr('Add Field')); 
    $respuesta->addScript($script);
    return $respuesta;
}

function desactivar_formulario($id_formulario)
{
    global $arrConf;

    $respuesta = new xajaxResponse();
    $oDataForm = new paloSantoDataForm($arrConf["cadena_dsn"]);

    if($oDataForm->eliminado_logico_formulario($id_formulario)) header('?menu=form_designer');
        //$respuesta->addScript("window.open('?menu=form_designer','_parent')");
    else{
        $respuesta->addAssign("mb_title","innerHTML",_tr("Desactivate Error")); 
        $respuesta->addAssign("mb_message","innerHTML",_tr("Error when eliminating the form")); 
    }
    return $respuesta;
}
?>
