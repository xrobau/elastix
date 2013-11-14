<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0-16                                               |
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
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/
require_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    require_once "apps/$module_name/libs/elastixutils.lib.php";
    $sFuncName = 'handleJSON_'.getParameter('action');
    if (function_exists($sFuncName))
        return $sFuncName($smarty, $module_name);
    
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status('false');
    $jsonObject->set_error(_tr('Undefined utility action'));
    return $jsonObject->createJSON();
}

function handleJSON_versionRPM($smarty, $module_name)
{
    $json = new Services_JSON();
    return $json->encode(obtenerDetallesRPMS());
}

function handleJSON_changePasswordElastix($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = setUserPassword();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    if($output['status'])
        $jsonObject->set_message($output['msg']);
    else{
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_search_module($smarty, $module_name)
{
    return searchModulesByName();
}

function handleJSON_changeColorMenu($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = changeMenuColorByUser();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    $jsonObject->set_error($output['msg']);
    return $jsonObject->createJSON();
}

function handleJSON_addBookmark($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('false');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $output = putMenuAsBookmark($id_menu);
        if(getParameter('action') == 'deleteBookmark') $output["data"]["menu_url"] = $id_menu;
        $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
        $jsonObject->set_error($output['msg']);
        $jsonObject->set_message($output['data']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_deleteBookmark($smarty, $module_name)
{
    // La función subyacente agrega el bookmark si no existe, o lo quita si existe
    return handleJSON_addBookmark($smarty, $module_name);
}

function handleJSON_save_sticky_note($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('ERROR');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $description_note = getParameter("description");
        $popup_note = getParameter("popup");    
        $output = saveStickyNote($id_menu, $description_note, $popup_note);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'OK' : 'ERROR');
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_get_sticky_note($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('ERROR');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        global $arrConf;
        
        $pdbACL = new paloDB($arrConf['elastix_dsn']['elastix']);
        $pACL = new paloACL($pdbACL);
        $idUser = $pACL->getIdUser($_SESSION['elastix_user']);

        $output = getStickyNote($pdbACL, $idUser, $id_menu);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'OK' : 'ERROR');
        $jsonObject->set_error($output['msg']);
        $jsonObject->set_message($output['data']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_saveNeoToggleTab($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('false');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $statusTab  = getParameter("statusTab");
        $output = saveNeoToggleTabByUser($id_menu, $statusTab);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_showAboutAs($smarty, $module_name)
{
    global $arrConf;
    $jsonObject   = new PaloSantoJSON();
    $about_us_content=_tr('About Elastix Content');
    $html="<table border='0' cellspacing='0' cellpadding='2' width='100%'>".
            "<tr class='tabForm' >".
                "<td class='tabForm' align='center'>".
                    "$about_us_content<br />".
                    "<a href='http://www.elastix.org' target='_blank'>www.elastix.org</a>".
                "</td>".
            "</tr>".
          "</table>";


    $response['html']  = $html;
    $response['title'] = _tr('About Elastix')." ".$arrConf['elastix_version'];

    if($arrConf['mainTheme']=="elastixwave" || $arrConf['mainTheme']=="elastixneo")
        $response['title'] = _tr('About Elastix2');

    $jsonObject->set_message($response);
    return $jsonObject->createJSON();
}

function handleJSON_getImage($smarty, $module_name){
    global $arrCredentials;    
    global $arrConf;
    $pDB = new paloDB($arrConf['elastix_dsn']["elastix"]);
    $pACL       = new paloACL($pDB);
    $imgDefault = "/var/www/html/web/_common/images/Icon-user.png";
    $id_user=getParameter("ID");
    $picture=false;
   
    $picture = $pACL->getUserPicture($id_user);
    
    // Creamos la imagen a partir de un fichero existente
    if($picture!=false && !empty($picture["picture_type"])){
        Header("Content-type: {$picture["picture_type"]}");
        print $picture["picture_content"];
    }else{
        Header("Content-type: image/png");
        $im = file_get_contents($imgDefault);
        echo $im;
    }
    return;
}

function handleJSON_getElastixAccounts($smarty, $module_name){
    global $arrConf;
    $error='';
    $pDB = new paloDB($arrConf['elastix_dsn']["elastix"]);
    $pACL = new paloACL($pDB);
    $jsonObject = new PaloSantoJSON();
    
    $astMang=AsteriskManagerConnect($errorM);
    if($astMang==false){
        $this->errMsg=$errorM;
        return false;
    }
    
    $arrCredentials=getUserCredentials($_SESSION['elastix_user']);
    
    
    //1) obtenemos los parametros generales de configuracion para asterisk websocket y el cliente de chat de elastix
    $chatConfig=getChatClientConfig($pDB,$error);
    if($chatConfig==false){
        $jsonObject->set_error("An error has ocurred to retrieved server configuration params. ".$error);
        return $jsonObject->createJSON();
    }
    
    //2) obtenemos el dominio sip de la organizacion si no se encuentra configurado utilizamos el valor de ws_server       
    $dominio=$chatConfig['elastix_chat_server'];
    
    //3) obtenemos la informacion de las cuentas de los usuarios
    $result=$pACL->getUsersAccountsInfoByDomain($arrCredentials["id_organization"]);
    if($result===false){
        //hubo un error de la base de datos ahi que desactivar la columna lateral
        $jsonObject->set_error("An error has ocurred to retrieved Contacts Info. ".$pACL->errMsg);
    }else{
        $arrContacts=array();
        foreach($result as $key => $value){
            //TODO: por el momento se obtine la presencia del usuario al
            // travès de AMI con la función que extension_state
            // en el futuro esto debe ser manejado con la libreria jssip
            // actualmente este libreria no tiene esa funcion implementada
            /*
            -1 = Extension not found
            0 = Idle
            1 = In Use
            2 = Busy
            4 = Unavailable
            8 = Ringing
            16 = On Hold
            */
            if($value['extension']!='' && isset($value['extension'])){
                if($value['id']!=$arrCredentials['idUser']){
                    $result=$astMang->send_request('ExtensionState',array('Exten'=>"{$value['extension']}", 'Context'=>"cocacolacomc8nm-ext-local"));
                    if($result['Response']=='Success'){
                        $status=getStatusContactFromCode($result['Status']);
                        $st_code=$result['Status'];
                        if($result['Status']=='-1'){
                            $index_st='not_found';
                        }elseif($result['Status']=='4'){
                            $index_st='unava';
                        }else{
                            $index_st='ava';
                        }
                    }else{
                        //TODO:ahi un error con el manager y nopuede determinar le estado de los
                        //contactos por lo tanto dejo a todas como disponibles
                        $index_st='ava';
                        $st_code=0;
                        $status=_tr('Idle');
                    }
                    $arrContacts[$index_st][$key]['idUser']=$value['id'];
                    $arrContacts[$index_st][$key]['display_name']=$value['name'];
                    $arrContacts[$index_st][$key]['username']=$value['username'];
                    $arrContacts[$index_st][$key]['presence']=$status;
                    $arrContacts[$index_st][$key]['st_code']=$st_code;
                    $arrContacts[$index_st][$key]['uri']="{$value['elxweb_device']}@$dominio";
                    $arrContacts[$index_st][$key]['alias']="{$value['alias']}@$dominio";
                }else{
                    $arrContacts['my_info']['uri']="{$value['elxweb_device']}@$dominio";
                    $arrContacts['my_info']['ws_servers']=$chatConfig['ws_servers'];
                    $arrContacts['my_info']['password']=$_SESSION['elastix_pass2'];
                    $arrContacts['my_info']['display_name']=$value['name'];
                    $arrContacts['my_info']['elxuser_username']=$value['username'];
                    $arrContacts['my_info']['elxuser_exten']=$value['extension'];
                    $arrContacts['my_info']['elxuser_faxexten']=$value['fax_extension'];
                    foreach($chatConfig as $key => $value){
                        $arrContacts['my_info'][$key] = $value;
                    }
                }
            }
        }
        $jsonObject->set_message($arrContacts);
    }
    $astMang->disconnect();
    return $jsonObject->createJSON();
}
?>
