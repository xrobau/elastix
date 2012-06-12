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
  $Id: paloSantoForm.class.php,v 1.4 2007/05/09 01:07:03 gcarrillo Exp $ */

/* A continuacion se ilustra como luce un tipico elemento del arreglo $this->arrFormElements
"subject"  => array(
                "LABEL"                  => $arrLang["Fax Suject"],
                "REQUIRED"               => "yes",
                "INPUT_TYPE"             => "TEXT",
                "INPUT_EXTRA_PARAM"      => array("style" => "width:240px"),
                "VALIDATION_TYPE"        => "text",
                "EDITABLE"               => "si",
                "VALIDATION_EXTRA_PARAM" => "")

"content" => array(
                "LABEL"                  => $arrLang["Fax Content"],
                "REQUIRED"               => "no",
                "INPUT_TYPE"             => "TEXTAREA",
                "INPUT_EXTRA_PARAM"      => "",
                "VALIDATION_TYPE"        => "text",
                "EDITABLE"               => "si",
                "COLS"                   => "50",
                "ROWS"                   => "4",
                "VALIDATION_EXTRA_PARAM" => "")

"today"  => array(
                "LABEL"                  => "Today",
                "REQUIRED"               => "yes",
                "INPUT_TYPE"             => "DATE",
                "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "'%d %b %Y' %H:%M","TIMEFORMAT" => "12"),
                "VALIDATION_TYPE"        => '',
                "EDITABLE"               => "si",
                "VALIDATION_EXTRA_PARAM" => '')

'formulario'       => array(
                "LABEL"                  => $arrLang["Form"],
                "REQUIRED"               => "yes",
                "INPUT_TYPE"             => "SELECT",
                "INPUT_EXTRA_PARAM"      => $arrSelectForm,
                "VALIDATION_TYPE"        => "text",
                "VALIDATION_EXTRA_PARAM" => "",
                "EDITABLE"               => "si",
                "MULTIPLE"               => true,
                "SIZE"                   => "5")


"checkbox"  => array(
                "LABEL"                  => "Habiltar",
                "REQUIRED"               => "no",
                "INPUT_TYPE"             => "CHECKBOX",
                "INPUT_EXTRA_PARAM"      => "",
                "VALIDATION_TYPE"        => "",
                "EDITABLE"               => "si",
                "VALIDATION_EXTRA_PARAM" => "")
*/

require_once("misc.lib.php");

class paloForm
{
    var $smarty;
    var $arrFormElements;
    var $arrErroresValidacion;
    var $modo;

    function paloForm(&$smarty, $arrFormElements)
    {
        $this->smarty = &$smarty;
        $this->arrFormElements = $arrFormElements;
        $this->arrErroresValidacion = "";
        $this->modo = 'input'; // Modo puede ser 0 (Modo normal de formulario) o 1 (modo de vista o preview 
                               // de datos donde no se puede modificar.
    }

    // Esta funcion muestra un formulario. Para hacer esto toma una plantilla de 
    // formulario e inserta en ella los elementos de formulario.
    function fetchForm($templateName, $title, $arrPreFilledValues=array())
    {
        foreach($this->arrFormElements as $varName=>$arrVars) {
            if(!isset($arrPreFilledValues[$varName]))
                $arrPreFilledValues[$varName] = "";
            $arrMacro = array();
            $strInput = "";
            $arrVars['EDITABLE'] = isset($arrVars['EDITABLE'])?$arrVars['EDITABLE']:'';

            switch($arrVars['INPUT_TYPE']) {
                case "TEXTAREA":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        $cols = isset($arrVars['COLS'])?$arrVars['COLS']:20;
                        $rows = isset($arrVars['ROWS'])?$arrVars['ROWS']:3;
                        $strInput = "<textarea name='$varName' rows='$rows' cols='$cols'>$arrPreFilledValues[$varName]</textarea>";
                    } else {
                        $strInput = "$arrPreFilledValues[$varName]";
                    }
                    break;
                case "TEXT":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        $extras="";
                        if(is_array($arrVars['INPUT_EXTRA_PARAM']) && count($arrVars['INPUT_EXTRA_PARAM'])>0) {
                            foreach($arrVars['INPUT_EXTRA_PARAM'] as $key => $value)
                                $extras .= " $key = '$value' ";
                        }
                        $strInput = "<input type='text' name='$varName' value='$arrPreFilledValues[$varName]' $extras >";
                    } else {
                        $strInput = "$arrPreFilledValues[$varName]";
                    }
                    break;
                case "CHECKBOX":
                    $checked = 'off';
                    $disable = 'on';
                    if($arrPreFilledValues[$varName]=='on')
                        $checked = 'on';
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no'))
                        $disable = 'off';

                    //Funcion definida en misc.lib.php
                    $strInput = checkbox($varName,$checked, $disable);
                    break;
                case "PASSWORD":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        $strInput = "<input type='password' name='$varName' value='$arrPreFilledValues[$varName]'>";
                    } else {
                        $strInput = "$arrPreFilledValues[$varName]";
                    }
                    break;
                case "HIDDEN":
                    $strInput = "<input type='hidden' name='$varName' value='$arrPreFilledValues[$varName]'>";
                    break;
                case "FILE":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        // Si viene un arreglo entonces puede ser que sea un submit de un campo tipo 'file'
                        if(is_array($arrPreFilledValues[$varName]) and $arrPreFilledValues[$varName]['error']==0 and 
                           !empty($arrPreFilledValues[$varName]['tmp_name']) and !empty($arrPreFilledValues[$varName]['name']) ) {

                            $tmpFilename = $arrPreFilledValues[$varName]['name'] . "_" . basename($arrPreFilledValues[$varName]['tmp_name']);

                            // Creo que no esta bien hacer esto aqui. Porque aqui se debe mostrar el formulario unicamente
                            // y naturalmente no se esperaria que se copie aqui el archivo. 
                            // Por ej. Qué pasa si este archivo no pasa alguna validación y por lo tanto no se desea guardarlo?
                            //         Qué pasa si el formulario paso las validaciones correctamente y por lo tanto no se pasa por
                            //         este bloque de codigo?
                            // O qué pasa si la copia da error, cómo notifico esto al programa?
                            copy($arrPreFilledValues[$varName]['tmp_name'], "/var/www/html/var/tmp/$tmpFilename");

                            $strInput = "<div id='showFile'><i>File: " . $arrPreFilledValues[$varName]['name'] . 
                                        //"</i>&nbsp;&nbsp;<input type='button' name='' value='Change file' class=button onClick=''>" . 
                                        "</i>" . 
                                        "<input type='hidden' name='$varName' value='" . $arrPreFilledValues[$varName]['name'] . "'>" .
                                        "<input type='hidden' name='_hidden_$varName' value='$tmpFilename'></div>";
                        // It's not and array, but can be a hidden field
                        } else if (!is_array($arrPreFilledValues[$varName]) and !empty($arrPreFilledValues[$varName]) and
                                   !empty($arrPreFilledValues["_hidden_" . $varName]) ) {
                            $strInput = "<div id='showFile'><i>File: " . $arrPreFilledValues[$varName] .
                                        //"</i>&nbsp;&nbsp;<input type='button' name='' value='Change file' class=button onClick=''>" .
                                        "</i>" . 
                                        "<input type='hidden' name='$varName' value='$arrPreFilledValues[$varName]'>" .
                                        "<input type='hidden' name='_hidden_$varName' value='" . $arrPreFilledValues["_hidden_" . $varName] . "'></div>";
                        // default. It's not an array and there is not hidden field
                        } else {
                            $strInput = "<input type='file' name='$varName'>";
                        }
                    } else {
                        $strInput = "$arrPreFilledValues[$varName]";
                    }
                    break;
                case "RADIO":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        $strInput = "";
                        if(is_array($arrVars['INPUT_EXTRA_PARAM'])) {
                            foreach($arrVars['INPUT_EXTRA_PARAM'] as $radioValue => $radioLabel) {
                                if($radioValue==$arrPreFilledValues[$varName]) {
                                    $strInput .= "<input type='radio' name='$varName' value='$radioValue' " .
                                                 "checked>&nbsp;$radioLabel&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                                } else {
                                    $strInput .= "<input type='radio' name='$varName' value='$radioValue'" .
                                                 ">&nbsp;$radioLabel&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                                }
                            }
                        }
                    } else {
                        $strInput = "$arrPreFilledValues[$varName]";
                    }
                    break;
                case "SELECT":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        $multiple = "";
                        $size = "";
                        if(isset($arrVars['SIZE'])){
                            if($arrVars['SIZE']!="")
                                $size=" size='".$arrVars['SIZE']."' ";
                        }
                        if(isset($arrVars['MULTIPLE'])){
                            if($arrVars['MULTIPLE']!="" || $arrVars['MULTIPLE']==true)
                                $multiple=" multiple='multiple' ";
                        }
                        $strInput  = "<select name='$varName' $multiple $size>";
                        if(is_array($arrVars['INPUT_EXTRA_PARAM'])) {
                            foreach($arrVars['INPUT_EXTRA_PARAM'] as $idSeleccion => $nombreSeleccion) {
                                if(is_array($arrPreFilledValues[$varName])){
                                    $bandera = true;
                                    foreach($arrPreFilledValues[$varName] as $key => $value){ //si hay mas  de uno elegido informacion como arreglo
                                        if($idSeleccion==$value) {
                                            $strInput .= "<option value='$idSeleccion' selected>$nombreSeleccion</option>";
                                            $bandera = false; //bandera que me ayuda a que no se cree otro option en el caso de que ya se creo uno en forma seleccionada
                                            break; // rompo porque ya lo encontre
                                        }
                                    }
                                    if($bandera) //si es true $idSeleccion es no seleccionado
                                         $strInput .= "<option value='$idSeleccion' >$nombreSeleccion</option>";    
                                }
                                else{ //solo uno elegido informacion como texto
                                    if($idSeleccion==$arrPreFilledValues[$varName]) {
                                        $strInput .= "<option value='$idSeleccion' selected>$nombreSeleccion</option>";
                                    } else {
                                        $strInput .= "<option value='$idSeleccion' >$nombreSeleccion</option>";
                                    }
                                }
                            }
                        }
                        $strInput .= "</select>";
                    } else { 
                            if(is_array($arrPreFilledValues[$varName])){
                                $strInput .= "| ";
                                foreach($arrVars['INPUT_EXTRA_PARAM'] as $idSeleccion => $nombreSeleccion) {
                                    foreach($arrPreFilledValues[$varName] as $key => $value){ //si hay mas  de uno elegido informacion como arreglo
                                        if($idSeleccion==$value) {
                                            $strInput .=  $arrVars['INPUT_EXTRA_PARAM'][$idSeleccion]." | ";
                                            break; // rompo porque ya lo encontre
                                        }
                                    }
                                }
                            }
                            else{//solo uno elegido, informacion como texto
                                $idSeleccion = $arrPreFilledValues[$varName];
                                $strInput .= isset($arrVars['INPUT_EXTRA_PARAM'][$idSeleccion])?$arrVars['INPUT_EXTRA_PARAM'][$idSeleccion]:'';
                            }
                    }
                    break;
                case "DATE":
                    if($this->modo=='input' or ($this->modo=='edit' and $arrVars['EDITABLE']!='no')) {
                        require_once("libs/js/jscalendar/calendar.php");    
                        $time = false;
                        $format = '%d %b %Y';
                        $timeformat = '12';
                        if(is_array($arrVars['INPUT_EXTRA_PARAM']) && count($arrVars['INPUT_EXTRA_PARAM'])>0) {
                            foreach($arrVars['INPUT_EXTRA_PARAM'] as $key => $value){
                                if($key=='TIME')
                                    $time=$value;
                                if($key=='FORMAT')
                                    $format = $value;
                                if($key=='TIMEFORMAT')
                                    $timeformat = $value;
                            }
                        }
                        $oCal = new DHTML_Calendar("/libs/js/jscalendar/", "en", "calendar-win2k-2", $time);
                        $this->smarty->assign("HEADER", $oCal->load_files());

                        $strInput .= $oCal->make_input_field(
                                        array('firstDay'       => 1, // show Monday first
                                              'showsTime'      => true,
                                              'showOthers'     => true,
                                              'ifFormat'       => $format,
                                              'timeFormat'     => $timeformat),
                                        // field attributes go here
                                        array('style'          => 'width: 10em; color: #840; background-color: #fafafa; ' .
                                                                   'border: 1px solid #999999; text-align: center',
                                              'name'        => $varName,
                                              //'value'       => strftime('%d %b %Y', strtotime('now'))));
                                              'value'       => $arrPreFilledValues[$varName]));

                    } else {
                        $strInput = "$arrPreFilledValues[$varName]";
                    }
                    break;
                default:
                    $strInput = "";
            }
            $arrMacro['LABEL'] = $arrVars['LABEL'];
            $arrMacro['INPUT'] = $strInput;
            $this->smarty->assign($varName, $arrMacro);
        }
        $this->smarty->assign("title", $title);
        $this->smarty->assign("mode", $this->modo);
        return $this->smarty->fetch("file:$templateName");
    }
    
    function setViewMode()
    {
        $this->modo = 'view';
    }

    function setEditMode()
    {
        $this->modo = 'edit';
    }

    // TODO: No se que hacer en caso de que el $arrCollectedVars sea un arreglo vacio
    //       puesto que en ese caso la funcion devolvera true. Es ese el comportamiento esperado?
    function validateForm($arrCollectedVars)
    {
        include_once("libs/paloSantoValidar.class.php");
        $oVal = new PaloValidar();
        foreach($arrCollectedVars as $varName=>$varValue) {
            // Valido si la variable colectada esta en $this->arrFormElements
            if(@array_key_exists($varName, $this->arrFormElements)) {
                if($this->arrFormElements[$varName]['REQUIRED']=='yes' or ($this->arrFormElements[$varName]['REQUIRED']!='yes' AND !empty($varValue))) {
                    if($this->modo=='input' || ($this->modo=='edit' AND $this->arrFormElements[$varName]['EDITABLE']!='no')) {
                        $oVal->validar($this->arrFormElements[$varName]['LABEL'], $varValue, $this->arrFormElements[$varName]['VALIDATION_TYPE'], 
                                       $this->arrFormElements[$varName]['VALIDATION_EXTRA_PARAM']);
                    }
                }
            }
        }
        if($oVal->existenErroresPrevios()) {
            $this->arrErroresValidacion = $oVal->obtenerArregloErrores();
            return false;
        } else {
            return true;
        }
    }
}
?>
