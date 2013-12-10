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
  $Id: new_campaign.php $ */

require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoGrid.class.php";
require_once "libs/xajax/xajax.inc.php";

require_once "modules/agent_console/libs/elastix2.lib.php";

function _moduleContent(&$smarty, $module_name){

    load_language_module($module_name);

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;

    // Se fusiona la configuración del módulo con la configuración global
    $arrConf = array_merge($arrConf, $arrConfModule);

    require_once "modules/$module_name/libs/PaloSantoDontCalls.class.php";
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // se conecta a la base
    $pDB = new paloDB($arrConf["cadena_dsn"]);
    if(!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", _tr('Error when connecting to database')."<br/>".$pDB->errMsg);
    }
    $smarty->assign("MODULE_NAME", _tr('Add Number'));
    $smarty->assign("label_file", _tr('Upload File'));
    $smarty->assign("label_text", _tr('Add new Number'));
    $smarty->assign("NAME_BUTTON_SUBMIT", _tr('SAVE'));
    $smarty->assign("NAME_BUTTON_CANCEL", _tr('CANCEL'));

    $formCampos = array();
    $oForm = new paloForm($smarty, $formCampos);

    if (isset($_POST['submit_Add_Call'])) {
        $contenidoModulo = AddCalls($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);

    } else if (isset($_POST['submit_new'])) {
        $contenidoModulo = newCalls($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    } else if ( isset( $_POST['submit_Apply'] ) ) {
        $contenidoModulo = applyList($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    } else if ( isset( $_POST['submit_delete'] ) ){
        $contenidoModulo = deleteCalls($pDB, $smarty, $module_name, $local_templates_dir);
    } else  if ( isset( $_POST['submit_cancel'] ) ){
        $contenidoModulo = listCalls($pDB, $smarty, $module_name, $local_templates_dir);
    }else{
        $contenidoModulo = listCalls($pDB, $smarty, $module_name, $local_templates_dir);
    }

    return $contenidoModulo;
}


function AddCalls($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {
    $smarty->assign('FRAMEWORK_TIENE_TITULO_MODULO', existeSoporteTituloFramework());
    $smarty->assign('icon', 'images/list.png');
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/new.tpl", _tr('Add Number'),$_POST);
    return $contenidoModulo;
}

function newCalls($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {
    $fContenido="";
    $msgResultado="";

    $smarty->assign('FRAMEWORK_TIENE_TITULO_MODULO', existeSoporteTituloFramework());
    if (isset($_FILES['file_number'])) {
        if($_FILES['file_number']['name']!=""){
	    $file = $_FILES['file_number'];
	    $cargaDatos = new Cargar_File($file);
	    if( is_object($cargaDatos) )  {
		$nameFile=$cargaDatos->getFileName();
		$flag = $cargaDatos->guardarDatosCallsFromFile($pDB,$nameFile);
	    } else { 
		$smarty->assign("mb_title", _tr('Error'));
		$smarty->assign("mb_message", _tr('Error when is loading file'));
	    }
        }else{
            $msgResultado = _tr('Please select any file');
        }
    }elseif( isset( $_POST["txt_new_number"] ) ){
        if( $_POST["txt_new_number"]!="" ){
            $new_number = $_POST["txt_new_number"];
            if(is_numeric($new_number) && $new_number>0){
                $msgResultado = registrarNuevoNumero($pDB,$new_number);
            }else{
                $msgResultado = _tr('Number phone is not numeric value');
            }
        }else{
            $msgResultado = _tr('Please enter a number phone');
        }
    }

    $oForm->setViewMode();

    if($msgResultado==""){
        header("Location: ?menu=dont_call_list");
    }else{
        $smarty->assign("mb_title", _tr('Result'));
        $smarty->assign("mb_message",$msgResultado);
    }
    $smarty->assign('icon', 'images/list.png');
    $fContenido = $oForm->fetchForm("$local_templates_dir/new.tpl", _tr('Load File') ,null);
    return $fContenido;
}

function listCalls($pDB, $smarty, $module_name, $local_templates_dir) {
    global $arrLang;

    $arrCalls=array();
    $oCalls = new PaloSantoDontCalls($pDB);
    $arrCalls = $oCalls->getCalls();
    $end = count($arrCalls);

    if (is_array($arrCalls) && count($arrCalls)>0) {
        foreach($arrCalls as $call) {
            $arrTmp    = array();
            $arrTmp[0] = construirCheck($call['id']);
            $arrTmp[1] = $call['caller_id'];
            $arrTmp[2] = $call['date_income'];
            if($call['status']=='I'){
                $arrTmp[3] = _tr('Inactive');
            }else{
                $arrTmp[3] = _tr('Active');
            } 
            $arrData[] = $arrTmp;
         }
    }else{
        $arrData=array();
    }

    $button_delete="<input class='button' type='submit' name='submit_delete'".
                    " value='"._tr('Remove')."'>";

    $url = construirURL(array('menu' => $module_name), array('nav', 'start'));
    $arrGrid = array("title"    => _tr('Phone List'),
        "url"      => $url,
        "icon"     => "images/list.png",
        "width"    => "99%",
        "start"    => ($end==0) ? 0 : 1,
        "end"      => $end,
        "total"    => $end,
        "columns"  => array(0 => array("name"      => $button_delete,
                                       "property1" => ""),
                            1 => array("name"      => _tr("Number Phone's"),
                                       "property1" => ""),
                            2 => array("name"      => _tr('Date Income'),
                                       "property1" => ""),
                            3 => array("name"     => _tr('Status'),
                                       "property1" => "")));

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->showFilter(
        "<input type='submit' name='submit_Add_Call' value='"._tr('Add')."' class='button' />&nbsp&nbsp&nbsp&nbsp".
        "<input type='submit' name='submit_Apply' value='"._tr('Apply')."' class='button' />");
    $sContenido = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    if (strpos($sContenido, '<form') === FALSE)
        $sContenido = "<form  method=\"POST\" style=\"margin-bottom:0;\" action=\"$url\">$sContenido</form>";
    return $sContenido;
}

function applyList($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm){
    $contenido="";
    $oCalls = new PaloSantoDontCalls($pDB);
    $oCalls->applyList();
    header("Location:?menu=dont_call_list");
    return $contenido;
}

function deleteCalls($pDB, $smarty, $module_name, $local_templates_dir){
    $sContenido="";
    $arrIdCalls=array();
    $patronBusqueda = '^chk_[0-9]+$';
    foreach($_POST as $nombre => $valor) {
	if( ereg( $patronBusqueda , $nombre ) ) {
	    $arrIdCalls[] = $valor;
	}
    }
    if(count($arrIdCalls)<=0){
        $smarty->assign("mb_title", _tr('Result'));
        $smarty->assign("mb_message","No data selected");
    }else{
	$oCalls = new PaloSantoDontCalls($pDB);
	$bExito = $oCalls->deleteCalls($arrIdCalls);
	if($bExito){
	    header("Location: ?menu=dont_call_list");
	}else{
	    //$sContenido .=$insTpl->crearAlerta("error","Error",$oMasterSet->getMessage());
	}

    }
    return $sContenido;
}

function construirCheck($id){
    $html_chk = "<input type='checkbox' name='chk_{$id}' value='{$id}'/>";
    return $html_chk;
}

?>
