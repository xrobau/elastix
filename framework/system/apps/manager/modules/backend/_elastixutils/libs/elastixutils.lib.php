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
*/

/**
 * Función para obtener un detalle de los rpms que se encuentran instalados en el sistema.
 *
 *
 * @return  mixed   NULL si no se reconoce usuario, o el DNS con clave resuelta
 */
function obtenerDetallesRPMS()
{
    $packageClass = array(
        'Kernel'    =>  NULL,
        'Elastix'   =>  array('elastix*'),
        'RoundCubeMail'  =>  array('RoundCubeMail'),
        'Mail'          =>  array('postfix', 'cyrus-imapd'),
        'IM'            =>  array('openfire'),
        'FreePBX'       =>  array('freePBX'),
        'Asterisk'      =>  array('asterisk', 'asterisk-perl', 'asterisk-addons'),
        'FAX'           =>  array('hylafax', 'iaxmodem'),
        'DRIVERS'       =>  array('dahdi', 'rhino', 'wanpipe-util'),
        
    );
    $sCommand = 'rpm -qa  --queryformat "%{name} %{version} %{release}\n"';
    foreach ($packageClass as $packageLists) {
        if (is_array($packageLists)) $sCommand .= ' '.implode(' ', array_map('escapeshellarg', $packageLists));
    }
    $sCommand .= ' | sort';
    $output = $retval = NULL;
    exec($sCommand, $output, $retval);
    $packageVersions = array();
    foreach ($output as $s) {
        $fields = explode(' ', $s);
        $packageVersions[$fields[0]] = $fields;
    }
    
    $result = array();
    foreach ($packageClass as $sTag => $packageLists) {
        if (!isset($result[$sTag])) $result[$sTag] = array();
        if ($sTag == 'Kernel') {
            // Caso especial
            $result[$sTag][] = explode(' ', trim(`uname -s -r -i`));
        } elseif ($sTag == 'Elastix') {
            // El paquete elastix debe ir primero
            if (isset($packageVersions['elastix']))
                $result[$sTag][] = $packageVersions['elastix'];
            foreach ($packageVersions as $packageName => $fields) {
                if (substr($packageName, 0, 8) == 'elastix-')
                    $result[$sTag][] = $fields;
            }
        } else {
            foreach ($packageLists as $packageName)
                $result[$sTag][] = isset($packageVersions[$packageName])
                    ? $packageVersions[$packageName]
                    : array($packageName, '(not installed)', ' ');
        }
    }
    return $result;
}

function setUserPassword()
{
    global $arrConf;
    include_once "libs/paloSantoACL.class.php";
    include_once "libs/paloSantoOrganization.class.php";

    $old_pass   = getParameter("oldPassword");
    $new_pass   = getParameter("newPassword");
    $new_repass = getParameter("newRePassword");
    $arrResult  = array();
    $arrResult['status'] = FALSE;
    if($old_pass == ""){
      $arrResult['msg'] = _tr("Please write your current password.");
      return $arrResult;
    }
    if($new_pass == "" || $new_repass == ""){
      $arrResult['msg'] = _tr("Please write the new password and confirm the new password.");
      return $arrResult;
    }
    if($new_pass != $new_repass){
      $arrResult['msg'] = _tr("The new password doesn't match with retype new password.");
      return $arrResult;
    }
    //verificamos que la nueva contraseña sea fuerte
    if(!isStrongPassword($new_pass)){
        $arrResult['msg'] = _tr("The new password can not be empty. It must have at least 10 characters and contain digits, uppers and little case letters");
        return $arrResult;
    }

    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB);
    $uid = $pACL->getIdUser($user);
    if($uid===FALSE)
        $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    else{
        // verificando la clave vieja
        $val = $pACL->authenticateUser($user, md5($old_pass));
        if($val === TRUE){
            $pORG=new paloSantoOrganization($pDB);
            $status = $pORG->changeUserPassword($user,$new_pass);
            if($status){
                $arrResult['status'] = TRUE;
                $arrResult['msg'] = _tr("Elastix password has been changed.");
                $_SESSION['elastix_pass'] = md5($new_pass);
                $_SESSION['elastix_pass2'] = $new_pass;
            }else{
                $arrResult['msg'] = _tr("Impossible to change your Elastix password.")." ".$pORG->errMsg;
            }
        }else{
            $arrResult['msg'] = _tr("Impossible to change your Elastix password. User does not exist or password is wrong");
        }
    }
    return $arrResult;
}

//pendiente
function searchModulesByName()
{
    global $arrConf;
    include_once "libs/paloSantoACL.class.php";
    include_once "libs/JSON.php";
    include_once "apps/group_permission/libs/paloSantoGroupPermission.class.php";
    $json = new Services_JSON();

    $pGroupPermission = new paloSantoGroupPermission();
    $name = getParameter("name_module_search");
    $result = array();
    $arrIdMenues = array();
    $lang=get_language();
    global $arrLang;

    // obteniendo los id de los menus permitidos
    $pACL = new paloACL($arrConf['elastix_dsn']['elastix']);
    $pMenu = new paloMenu($arrConf['elastix_dsn']['elastix']);
    
    //antes de obtener el listado de los modulos debemos determinar
    //si la interfaz desde la cual se esta llamando a los metodos es administrativa o 
    //es de usuario final. 
    $tmpPath=explode("/",$arrConf['basePath']);
    if($tmpPath[count($tmpPath)-1]=='admin')
        $administrative="yes";
    else
        $administrative="no";
    
    $org_access=null;
    if(!$pACL->isUserSuperAdmin($_SESSION['elastix_user'])){
        $org_access='yes';
    }
        
    $arrSessionPermissions = $pMenu->filterAuthorizedMenus($pACL->getIdUser($_SESSION['elastix_user']),$administrative);
    if(!is_array($arrSessionPermissions))
        $arrSessionPermissions = array();
        
    $arrIdMenues = array();
    foreach($arrSessionPermissions as $key => $value){
        $arrIdMenues[] = $value['id']; // id, IdParent, Link,  Type, order_no, HasChild
    }

    $parameter_to_find = array(); // arreglo con los valores del name dada la busqueda
    // el metodo de busqueda de por nombre sera buscando en el arreglo de lenguajes y obteniendo su $key para luego buscarlo en la base de
    // datos menu.db
    if($lang != "en"){ // entonces se adjunta la busqueda con el arreglo de lenguajes en ingles
        foreach($arrLang as $key=>$value){
            $langValue    = strtolower(trim($value));
            $filter_value = strtolower(trim($name));
            if($filter_value!=""){
                if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
                    if (strpos($langValue, $filter_value) !== FALSE)
                        $parameter_to_find[] = $key;
            }
        }
    }
    $parameter_to_find[] = $name;

    // buscando en la base de datos acl.db tabla acl_resource con el campo description
    if(empty($parameter_to_find))
        $arrResult = $pACL->getListResources(25, 0, $name, $org_access, $administrative);
    else
        $arrResult = $pACL->getListResources(25, 0, $parameter_to_find, $org_access, $administrative);

    foreach($arrResult as $key2 => $value2){
        // leyendo el resultado del query
        if(in_array($value2["id"], $arrIdMenues)){
            $arrMenu['caption'] = _tr($value2["description"]);
            $arrMenu['value']   = $value2["id"];
            $result[] = $arrMenu;
        }
    }

    header('Content-Type: application/json');
    return $json->encode($result);
}

function changeMenuColorByUser()
{
    global $arrConf;
    include_once "libs/paloSantoACL.class.php";

    $color = getParameter("menuColor");
    $arrResult  = array();
    $arrResult['status'] = FALSE;

    if($color == ""){
       $color = "#454545";
    }

    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $pdbACL = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);

    if($uid===FALSE)
        $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    else{
        //si el usuario no tiene un color establecido entonces se crea el nuevo registro caso contrario se lo actualiza
        if(!$pACL->setUserProp($uid,"menuColor",$color,"profile")){
            $arrResult['msg'] = _tr("ERROR DE DB: ").$pACL->errMsg;
        }else{
            $arrResult['status'] = TRUE;
            $arrResult['msg'] = _tr("OK");
        }
    }
    return $arrResult;
}

function putMenuAsBookmark($menu)
{
    global $arrConf;
    include_once "libs/paloSantoACL.class.php";
    $arrResult['status'] = FALSE;
    $arrResult['data'] = array("action" => "none", "menu" => "$menu");
    $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    if($menu != ""){
        $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
        $pdbACL = new paloDB($arrConf['elastix_dsn']['elastix']);
        $pACL = new paloACL($pdbACL);
        $uid = $pACL->getIdUser($user);
        if($uid!==FALSE){
            //antes de obtener el listado de los modulos debemos determinar
            //si la interfaz desde la cual se esta llamando a los metodos es administrativa o 
            //es de usuario final. 
            $tmpPath=explode("/",$arrConf['basePath']);
            if($tmpPath[count($tmpPath)-1]=='admin')
                $administrative="yes";
            else
                $administrative="no";
        
            //si el que realiza la accion no es el superadmin incluir en la busqueda la restriccion
            //de que el modulo puede ser accedido por la organizacion
            $org_access=(!$pACL->isUserSuperAdmin($_SESSION['elastix_user']))?'yes':NULL;
            
            //OBTENEMOS EL RECURSO
            $resource = $pACL->getResources($menu,$org_access,$administrative);
            
            $exist = false;
            $bookmarks = "SELECT aus.id AS id, ar.id AS id_menu,  ar.description AS description FROM user_shortcut aus, acl_resource ar WHERE id_user = ? AND aus.type = 'bookmark' AND ar.id = aus.id_resource ORDER BY aus.id DESC";
            $arr_result1 = $pdbACL->fetchTable($bookmarks, TRUE, array($uid));
            if($arr_result1 !== FALSE){
                $i = 0;
                $arrIDS = array();
                foreach($arr_result1 as $key => $value){
                    if($value['id_menu'] == $menu)
                        $exist = true;
                }
                //existia anteriormente se procede a eliminarlo del bookmark
                if($exist){
                    $pdbACL->beginTransaction();
                    $query = "DELETE FROM user_shortcut WHERE id_user = ? AND id_resource = ? AND type = ?";
                    $r = $pdbACL->genQuery($query, array($uid, $menu, "bookmark"));
                    if(!$r){
                        $pdbACL->rollBack();
                        $arrResult['status'] = FALSE;
                        $arrResult['data'] = array("action" => "delete", "menu" => _tr($resource[0][1]), "idmenu" => $menu, "menu_session" => $menu);
                        $arrResult['msg'] = _tr("Bookmark cannot be removed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
                        return $arrResult;
                    }else{
                        $pdbACL->commit();
                        $arrResult['status'] = TRUE;
                        $arrResult['data'] = array("action" => "delete", "menu" => _tr($resource[0][1]), "idmenu" => $menu,  "menu_session" => $menu);
                        $arrResult['msg'] = _tr("Bookmark has been removed.");
                        return $arrResult;
                    }
                }

                //no existia anteriormente se lo agrega
                if(count($arr_result1) > 4){
                    $arrResult['msg'] = _tr("The bookmark maximum is 5. Please uncheck one in order to add this bookmark");
                }else{
                    $pdbACL->beginTransaction();
                    $query = "INSERT INTO user_shortcut(id_user, id_resource, type) VALUES(?, ?, ?)";
                    $r = $pdbACL->genQuery($query, array($uid, $menu, "bookmark"));
                    if(!$r){
                        $pdbACL->rollBack();
                        $arrResult['status'] = FALSE;
                        $arrResult['data'] = array("action" => "add", "menu" => _tr($resource[0][1]), "idmenu" => $menu,  "menu_session" => $menu );
                        $arrResult['msg'] = _tr("Bookmark cannot be added. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
                    }else{
                        $pdbACL->commit();
                        $arrResult['status'] = TRUE;
                        $arrResult['data'] = array("action" => "add", "menu" => _tr($resource[0][1]), "idmenu" => $menu,  "menu_session" => $menu );
                        $arrResult['msg'] = _tr("Bookmark has been added.");
                        return $arrResult;
                    }
                }
            }
        }
    }
    return $arrResult;
}

/**
 * Funcion que se encarga de guardar o editar una nota de tipo sticky note.
 *
 * @return array con la informacion como mensaje y estado de resultado
 * @param string $menu nombre del menu al cual se le va a agregar la nota
 * @param string $description contenido de la nota que se desea agregar o editar
 *
 * @author Eduardo Cueva
 * @author ecueva@palosanto.com
 */
function saveStickyNote($menu, $description, $popup)
{
    global $arrConf;
    include_once "libs/paloSantoACL.class.php";
    $arrResult['status'] = FALSE;
    $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    if($menu != ""){
        $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
        $pdbACL = new paloDB($arrConf['elastix_dsn']['elastix']);
        $pACL = new paloACL($pdbACL);
        //$id_resource = $pACL->getIdResource($menu);
        $uid = $pACL->getIdUser($user);
        $date_edit = date("Y-m-d h:i:s");
        if($uid!==FALSE){
            $exist = false;
            $query = "SELECT * FROM sticky_note WHERE id_user = ? AND id_resource = ?";
            $arr_result1 = $pdbACL->getFirstRowQuery($query, TRUE, array($uid, $menu));
            if($arr_result1 !== FALSE && count($arr_result1) > 0)
                $exist = true;

            if($exist){
                $pdbACL->beginTransaction();
                $query = "UPDATE sticky_note SET description = ?, date_edit = ?, auto_popup = ? WHERE id_user = ? AND id_resource = ?";
                $r = $pdbACL->genQuery($query, array($description, $date_edit, $popup, $uid, $menu));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = "";
                    return $arrResult;
                }
            }else{
                $pdbACL->beginTransaction();
                $query = "INSERT INTO sticky_note(id_user, id_resource, date_edit, description, auto_popup) VALUES(?, ?, ?, ?, ?)";
                $r = $pdbACL->genQuery($query, array($uid, $menu, $date_edit, $description, $popup));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = "";
                    return $arrResult;
                }
            }
        }
    }
    return $arrResult;
}

function saveNeoToggleTabByUser($menu, $action_status)
{
    global $arrConf;
    include_once "libs/paloSantoACL.class.php";
    $arrResult['status'] = FALSE;
    $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    if($menu != ""){
        $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
        $pdbACL = new paloDB($arrConf['elastix_dsn']['elastix']);
        $pACL = new paloACL($pdbACL);
        $uid = $pACL->getIdUser($user);
        if($uid!==FALSE){
            $exist = false;
            $togglesTabs = "SELECT * FROM user_shortcut WHERE id_user = ? AND type = 'NeoToggleTab'";
            $arr_result1 = $pdbACL->getFirstRowQuery($togglesTabs, TRUE, array($uid));
            if($arr_result1 !== FALSE && count($arr_result1) > 0)
                $exist = true;

            if($exist){
                $pdbACL->beginTransaction();
                $query = "UPDATE user_shortcut SET description = ? WHERE id_user = ? AND type = ?";
                $r = $pdbACL->genQuery($query, array($action_status, $uid, "NeoToggleTab"));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = _tr("Request has been sent.");
                    return $arrResult;
                }
            }else{
                $pdbACL->beginTransaction();
                $query = "INSERT INTO user_shortcut(id_user, id_resource, type, description) VALUES(?, ?, ?, ?)";
                $r = $pdbACL->genQuery($query, array($uid, $menu, "NeoToggleTab", $action_status));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your elastix administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = _tr("Request has been sent.");
                    return $arrResult;
                }
            }
        }
    }
    return $arrResult;
}
function getChatClientConfig($pDB,&$error){
    $query="SELECT property_name,property_val from elx_chat_config";
    $result=$pDB->fetchTable($query,true);
    if($result===false){
        //error de conexion a la base no podemos determinar los parametros de configuracion del chat
        //mostramos un error
        $error='Error to obtain elastix chat configurations';
        return false;
    }
    $chat_conf=array();
    $type_connection="ws";
    foreach($result as $value){
        switch($value['property_name']){
            case 'type_connection':
                if($value['property_val']=='ws' || $value['property_val']=='wss')
                    $type_connection=$value['property_val'];
                else{
                    $type_connection='ws';
                }
                break;
            case 'register':
            //Indicate if JsSIP User Agent should register automatically when starting. Valid values are true and false (Boolean). Default value is true.
                if($value['property_val']=='no'){
                    $chat_conf[$value['property_name']]=false;
                }else{
                    $chat_conf[$value['property_name']]=true;
                }
                break;
            case 'register_expires':
            //Registration expiry time (in seconds) (Integer). Default value is 600.
                if($value['property_val']==''){
                    if(ctype_digit($value['property_val'])){
                        $chat_conf[$value['property_name']]=$value['property_val'];
                    }
                }
                break;    
            case 'no_answer_timeout':
            //Time (in seconds) (Integer) after which an incoming call is rejected if not answered. Default value is 60.
                if($value['property_val']==''){
                    if(ctype_digit($value['property_val'])){
                        $chat_conf[$value['property_name']]=$value['property_val'];
                    }
                }
                break;  
            case 'trace_sip':
            //Indicate whether incoming and outgoing SIP request/responses must be logged in the browser console (Boolean). Default value is false
                if($value['property_val']=='yes'){
                    $chat_conf[$value['property_name']]=true;
                }else{
                    $chat_conf[$value['property_name']]=false;
                }
                $chat_conf[$value['property_name']]=true;
                break;
            case 'use_preloaded_route':
            //If set to true every SIP initial request sent by JsSIP includes a Route header with the SIP URI associated to the WebSocket server as value. Some SIP Outbound Proxies require such a header. Valid values are true and false (Boolean). Default value is false.
                if($value['property_val']=='yes'){
                    $chat_conf[$value['property_name']]=true;
                }else{
                    $chat_conf[$value['property_name']]=false;
                }
                break;    
            case 'connection_recovery_min_interval':
            //Minimum interval (Number) in seconds between WebSocket reconnection attempts. Default value is 2
                if($value['property_val']==''){
                    if(ctype_digit($value['property_val'])){
                        $chat_conf[$value['property_name']]=$value['property_val'];
                    }
                }
                break; 
            case 'connection_recovery_max_interval':
            //Minimum interval (Number) in seconds between WebSocket reconnection attempts. Default value is 2
                if($value['property_val']==''){
                    if(ctype_digit($value['property_val'])){
                        $chat_conf[$value['property_name']]=$value['property_val'];
                    }
                }
                break; 
            case 'hack_via_tcp':
            //Set Via transport parameter in outgoing SIP requests to “TCP”, Valid values are true and false (Boolean). Default value is false.
                if($value['property_val']=='yes'){
                    $chat_conf[$value['property_name']]=true;
                }else{
                    $chat_conf[$value['property_name']]=false;
                }
                break; 
            case 'hack_ip_in_contact':
            //Set a random IP address as the host value in the Contact header field and Via sent-by parameter. Valid values are true and false (Boolean). Default value is a false.
                if($value['property_val']=='yes'){
                    $chat_conf[$value['property_name']]=true;
                }else{
                    $chat_conf[$value['property_name']]=false;
                }
                break;
            default:
                $chat_conf[$value['property_name']]=$value['property_val'];
                break;
        }
    }
    
    //obtenemos las configuraciones de asterisk del module http para web_socket support
    $http=getAsteriskHttpModuleConfig($pDB,$error);
    if($http===false){
        return false;
    }
    
    //se quiere usar wss debe estar habilitado soporte tls
    //si no está habilitado se debe usar ws en su lugar
    if(!isset($http['tlsenable'])){
        $type_connection='ws';
    }elseif($http['tlsenable']=='no'){
        $type_connection='ws';
    }
    
    if($type_connection=='ws'){
        if(!empty($http['bindport'])){
            $puerto=$http['bindport'];
        }else{
            $puerto=8088; //no esta configurado usamos los valores por default de asterisk
        }
    }else{
        if(!empty($http['tlsbindport'])){
            $puerto=$http['tlsbindport'];
        }else{
            $puerto=8089; //no esta configurado usamos los valores por default de asterisk
        }
    }
    $prefix='';
    if(isset($http['prefix'])){
        if($http['prefix']!='' && $http['prefix']!==false){
            $prefix="{$http['prefix']}";
        }
    }
    
    //"ws://192.168.5.110:8088/asterisk/ws"
    //ws -> transport, puede ser ws o wss
    //192.168.5.110 -> server
    //8088 -> puerto usado para la comunicacion definido en /etc/asterisk/http.conf 
    //asterisk -> prefix usado para la coneccion definido en http.conf
    //el ultimo ws simpre va, esto es una especificacion de asterisk
    
    //TODO: el valor para el parametro server se obtiene de la direccion web a la cual estamoas accediendo
    //No se si esto este bien, se debe probar haber si debe ser o deberia poder ser configurable y estatico
    //print($_SERVER['SERVER_ADDR']);
    //print($_SERVER['SERVER_NAME']);
    $server=$_SERVER['SERVER_ADDR'];
    $chat_conf['ws_servers']="$type_connection://$server:$puerto/".( ($prefix!='')?"$prefix/":'')."ws";
    $chat_conf['elastix_chat_server']=$server;
    return $chat_conf;
}
function getAsteriskHttpModuleConfig($pDB,&$error){
    $query="SELECT property_name,property_val FROM http_ast";
    $result=$pDB->fetchTable($query,true);
    if($result===false){
        //problemas con la coneccion no podemos determinar los 
        //valores usados para configurar web_socket con asterisk
        $error='Error to obtain asterisk web socket configurations';
        return false;
    }
    $http_conf=array();
    foreach($result as $value){
        $http_conf[$value['property_name']]=$value['property_val'];
    }
    return $http_conf;
}
function getStatusContactFromCode($code){
    /*
        -1 = Extension not found
        0 = Idle
        1 = In Use
        2 = Busy
        4 = Unavailable
        8 = Ringing
        16 = On Hold
    */
    switch($code){
        case "0":
            $status=_tr('Idle');
            break;
        case "1":
            $status=_tr('In Use');
            break;
        case "2":
            $status=_tr('Busy');
            break;
        case "4":
            $status=_tr('Unavailable');
            break;
        case "8":
            $status=_tr('Ringing');
            break;
        case "16":
            $status=_tr('On Hold');
            break;
        default:
            $status=_tr('Extension not found');
            break;
    }
    return $status;
}
?>