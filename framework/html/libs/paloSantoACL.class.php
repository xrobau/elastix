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
  $Id: paloSantoACL.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

/*
if (isset($arrConf['basePath'])) {
    include_once($arrConf['basePath'] . "/libs/paloSantoDB.class.php");
} else {
    include_once("libs/paloSantoDB.class.php");
}
*/

define('PALOACL_MSG_ERROR_1', 'Username or password is empty');
define('PALOACL_MSG_ERROR_2', 'Invalid characters found in username');
define('PALOACL_MSG_ERROR_3', 'Invalid characters found in password hash');

class paloACL {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloACL(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB = $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    /**
     * Procedimiento para obtener el listado de los usuarios existentes en los ACL. Si
     * se especifica un ID numérico de usuario, el listado contendrá únicamente al usuario
     * indicado. De otro modo, se listarán todos los usuarios.
     *
     * @param int   $id_user    Si != NULL, indica el ID del usuario a recoger
     *
     * @return array    Listado de usuarios en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getUsers($id_user = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_user) && !preg_match('/^[[:digit:]]+$/', "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description,extension FROM acl_user".
                (is_null($id_user) ? '' : " WHERE id = $id_user");
            $arr_result = $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para obtener el listado de los usuarios existentes en los ACL. Se
     * especifica un limite y un offset para obtener la data paginada.
     *
     * @param int   $limit    Si != NULL, indica el número de maximo de registros a devolver por consulta
     * @param int   $offset   Si != NULL, indica el principio o desde donde parte la consulta
     *
     * @return array    Listado de usuarios en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getUsersPaging($limit = NULL, $offset = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($limit) && !preg_match('/^[[:digit:]]+$/', "$limit")) {
            $this->errMsg = "Limit is not numeric";
            return FALSE;
        }
        if (!is_null($offset) && !preg_match('/^[[:digit:]]+$/', "$offset")) {
            $this->errMsg = "Offset is not numeric";
            return FALSE;
        }

        $this->errMsg = "";
        $sPeticionSQL = "SELECT id, name, description,extension FROM acl_user limit $limit offset $offset";

        $arr_result = $this->_DB->fetchTable($sPeticionSQL);
        if (!is_array($arr_result)) {
            $arr_result = FALSE;
            $this->errMsg = $this->_DB->errMsg;
        }
        return $arr_result;
    }

    /**
     * Procedimiento para obtener el listado de los grupos existentes en los ACL. Se
     * especifica un limite y un offset para obtener la data paginada.
     *
     * @param int   $limit    Si != NULL, indica el número de maximo de registros a devolver por consulta
     * @param int   $offset   Si != NULL, indica el principio o desde donde parte la consulta
     *
     * @return array    Listado de usuarios en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getGroupsPaging($limit = NULL, $offset = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($limit) && !preg_match('/^[[:digit:]]+$/', "$limit")) {
            $this->errMsg = "Limit is not numeric";
            return FALSE;
        }
        if (!is_null($offset) && !preg_match('/^[[:digit:]]+$/', "$offset")) {
            $this->errMsg = "Offset is not numeric";
            return FALSE;
        }
        $this->errMsg = "";
        $sPeticionSQL = "SELECT id, name, description FROM acl_group limit $limit offset $offset";

        $arr_result = $this->_DB->fetchTable($sPeticionSQL);
        if (!is_array($arr_result)) {
            $arr_result = FALSE;
            $this->errMsg = $this->_DB->errMsg;
        }
        return $arr_result;
    }

    /**
     * Procedimiento para obtener la cantidad de usuarios existentes en los ACL.
     *
     * @return int    Cantidad de usuarios existentes, o NULL en caso de error:
     */
    function getNumUsers()
    {
        $this->errMsg = "";
        $sPeticionSQL = "SELECT count(*) cnt FROM acl_user";

        $data = $this->_DB->getFirstRowQuery($sPeticionSQL,true);
        if (!is_array($data) || count($data) <= 0) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $data['cnt'];
    }

    /**
     * Procedimiento para obtener la cantidad de grupos existentes en los ACL.
     *
     * @return int    Cantidad de usuarios existentes, o NULL en caso de error:
     */
    function getNumGroups()
    {
        $this->errMsg = "";
        $sPeticionSQL = "SELECT count(*) cnt FROM acl_group";

        $data = $this->_DB->getFirstRowQuery($sPeticionSQL,true);
        if (!is_array($data) || count($data) <= 0) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $data['cnt'];
    }

    /**
     * Procedimiento para crear un nuevo usuario con hash MD5 de la clave ya proporcionada.
     *
     * @param string    $username       Login del usuario a crear
     * @param string    $description    Descripción del usuario a crear
     * @param string    $md5_password   Hash MD5 de la clave a asignar (32 dígitos y letras min a-f)
     *
     * @return bool     VERDADERO si el usuario se crea correctamente, FALSO en error
     */
    function createUser($username, $description, $md5_password, $extension)
    {
        $bExito = FALSE;
        if ($username == "") {
            $this->errMsg = "UserName can't be empty";
//        } else if (!ereg("^[[:digit:]a-f]{32}$", $md5_password)) {
//            $this->errMsg = "Clave de acceso no es un hash MD5 valido";
        } else {
            if ( !$description ) $description = $username;

            // Verificar que el nombre de usuario no existe previamente
            $id_user = $this->getIdUser($username);
            if ($id_user !== FALSE) {
                $this->errMsg = "Username already exists";
            } elseif ($this->errMsg == "") {
                $sPeticionSQL = "INSERT into acl_user (name,description,md5_password,extension) VALUES (?,?,?,?)";
                $arrParam = array($username,$description,$md5_password,$extension);
                if ($this->_DB->genQuery($sPeticionSQL,$arrParam)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento para modificar al usuario con el ID de usuario especificado, para
     * darle un nuevo login y descripción.
     *
     * @param int       $id_user        Indica el ID del usuario a modificar
     * @param string    $username       Login del usuario a crear
     * @param string    $description    Descripción del usuario a crear
     *
     * @return bool VERDADERO si se ha modificar correctamente el usuario, FALSO si ocurre un error.
     */
    function updateUser($id_user, $username, $description, $extension)
    {
        $bExito = FALSE;
        if ($username == "") {
            $this->errMsg = "UserName can't be empty";
        } else if (!preg_match("/^[[:digit:]]+$/", "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else {
            if ( !$description ) $description = $username;

            // Verificar que el usuario indicado existe
            $tuplaUser = $this->getUsers($id_user);
            if (!is_array($tuplaUser)) {
                $this->errMsg = "On having checked user's existence - ".$this->errMsg;
            } else if (count($tuplaUser) == 0) {
                $this->errMsg = "The user doesn't exist";
            } else {
                $bContinuar = TRUE;

                // Si el nuevo login es distinto al anterior, se verifica si el nuevo
                // login colisiona con un login ya existente
                if ($tuplaUser[0][1] != $username) {
                    $id_user_conflicto = $this->getIdUser($username);
                    if ($id_user_conflicto !== FALSE) {
                        $this->errMsg = "Username already exists";
                        $bContinuar = FALSE;
                    } elseif ($this->errMsg != "") {
                        $bContinuar = FALSE;
                    }
                }

                if ($bContinuar) {
                    // Proseguir con la modificación del usuario
                    $sPeticionSQL = "UPDATE acl_user SET name = ?, description = ?, extension  = ? WHERE id = ?";
                    $arrParam = array($username,$description,$extension,$id_user);
                    if ($this->_DB->genQuery($sPeticionSQL,$arrParam)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para cambiar la clave de un usuario, dado su ID de usuario.
     *
     * @param int       $id_user        ID del usuario para el que se cambia la clave
     * @param string    $md5_password   Nuevo hash MD5 a asignar al usuario
     *
     * @return bool VERDADERO si se ha modificar correctamente el usuario, FALSO si ocurre un error.
     */
    function changePassword($id_user, $md5_password)
    {
        $bExito = FALSE;
        if (!preg_match("/^[[:digit:]]+$/", "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else if (!preg_match("/^[[:digit:]a-f]{32}$/", $md5_password)) {
            $this->errMsg = "Password is not a valid MD5 hash";
        } else {
             if ($this->errMsg == "") {
                $sPeticionSQL = paloDB::construirUpdate(
                    "acl_user",
                    array('md5_password'    =>  paloDB::DBCAMPO($md5_password)),
                    array('id'              =>  $id_user)
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
               }
        }

        return $bExito;
    }
    
    /**
     * Procedimiento para borrar un usuario ACL, dado su ID numérico de usuario
     *
     * @param int   $id_user    ID del usuario que debe eliminarse
     *
     * @return bool VERDADERO si el usuario puede borrarse correctamente
     */
    function deleteUser($id_user)
    {
        $bExito = FALSE;
        if (!preg_match('/^[[:digit:]]+$/', "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else {
            $this->errMsg = "";
            $listaSQL = array(
                "DELETE FROM acl_user_permission WHERE id_user = '$id_user'",
                "DELETE FROM acl_membership WHERE id_user = '$id_user'",
                "DELETE FROM acl_user WHERE id = '$id_user'",
            );
            $bExito = TRUE;

            foreach ($listaSQL as $sPeticionSQL) {
                $bExito = $this->_DB->genQuery($sPeticionSQL);
                if (!$bExito) {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para averiguar el ID de un usuario, dado su login.
     *
     * @param string    $sNombreUser    Login del usuario para buscar ID
     *
     * @return  mixed   Valor entero del ID de usuario, o FALSE en caso de error o si el usuario no existe
     */
    function getIdUser($sNombreUser)
    {
        $idUser = FALSE;

        $this->errMsg = '';
        $sPeticionSQL = "SELECT id FROM acl_user WHERE name = ".paloDB::DBCAMPO($sNombreUser);
        $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE);
        if ($result && is_array($result) && count($result)>0) {
            $idUser = $result[0];
        }else $this->errMsg = $this->_DB->errMsg;
        return $idUser;
    }

    /**
     * Procedimiento para obtener el listado de los grupos existentes en los ACL. Si
     * se especifica un ID numérico de grupos, el listado contendrá únicamente al grupos
     * indicado. De otro modo, se listarán todos los grupos.
     *
     * @param int   $id_group    Si != NULL, indica el ID del grupos a recoger
     *
     * @return array    Listado de grupos en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getGroups($id_group = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_group) && !preg_match('/^[[:digit:]]+$/', "$id_group")) {
            $this->errMsg = "Group ID is not numeric";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_group".
                (is_null($id_group) ? '' : " WHERE id = $id_group");
            $arr_result = $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para construir un arreglo que describe los grupos a los cuales
     * pertenece un usuario identificado por un ID. El arreglo devuelto tiene el siguiente
     * formato:
     *  array(
     *      nombre_grupo_1  =>  id_grupo_1,
     *      nombre_grupo_2  =>  id_grupo_2,
     *  )
     *
     * @param int   $id_user    ID del usuario para el cual se pide la pertenencia
     *
     * @return mixed    Arreglo que describe la pertenencia, o NULL en caso de error.
     */
    function getMembership($id_user)
    {
        $arr_resultado = NULL;
        if (!is_null($id_user) && !preg_match('/^[[:digit:]]+$/', "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else {
            $this->errMsg = "";
            $sPeticionSQL =
                "SELECT g.id, g.name ".
                "FROM acl_group as g, acl_membership as m ".
                "WHERE m.id_group = g.id AND m.id_user = $id_user";
            $result = $this->_DB->fetchTable($sPeticionSQL, FALSE);
            if ($result && is_array($result) && count($result)>0) {
                $arr_resultado = array();
                foreach($result as $key => $value)
                    $arr_resultado[$value[1]] = $value[0];
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return $arr_resultado;
    }

    /**
     * Procedimiento para averiguar el ID de un grupo, dado su nombre.
     *
     * @param string    $sNombreUser    Login del usuario para buscar ID
     *
     * @return  mixed   Valor entero del ID de usuario, o FALSE en caso de error o si el usuario no existe
     */
    function getIdGroup($sNombreGroup)
    {
        $idGroup = FALSE;

        $this->errMsg = '';
        $sPeticionSQL = "SELECT id FROM acl_group WHERE name = ".paloDB::DBCAMPO($sNombreGroup);
        $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE);
        if ($result && is_array($result) && count($result)>0) {
            $idGroup = $result[0];
        }else $this->errMsg = $this->_DB->errMsg;
        return $idGroup;
    }
    
    /**
     * Procedimiento para asegurar que un usuario identificado por su ID pertenezca al grupo
     * identificado también por su ID. Se verifica primero que tanto el usuario como el grupo
     * existen en las tablas ACL.
     *
     * @param int   $id_user    ID del usuario que se desea agregar al grupo
     * @param int   $id_group   ID del grupo al cual se desea agregar al usuario
     *
     * @return bool VERDADERO si se puede agregar el usuario al grupo, o si ya pertenecía al grupo
     */
    function addToGroup($id_user, $id_group)
    {
        $bExito = FALSE;
        if (is_null($id_user) || is_null($id_group)) {
            $this->errMsg = "Se debe proporcionar ID de usuario y de grupo";
        } else if (is_array($listaUser = $this->getUsers($id_user)) &&
            is_array($listaGrupo = $this->getGroups($id_group))) {

            if (count($listaUser) == 0) {
                $this->errMsg = "User doesn't exist";
            } else if (count($listaGrupo) == 0) {
                $this->errMsg = "Group doesn't exist";
            } else {
                // Verificar existencia de la combinación usuario-grupo
                $sPeticionSQL = "SELECT id FROM acl_membership WHERE id_user = $id_user AND id_group = $id_group";
                $listaMembresia = $this->_DB->fetchTable($sPeticionSQL);
                if (!is_array($listaMembresia)) {
                    // Ocurre un error de base de datos
                    $this->errMsg = $this->_DB->errMsg;
                } else if (count($listaMembresia) > 0) {
                    // El usuario ya tiene membresía en el grupo - no se hace nada
                    $bExito = TRUE;
                } else {
                    // El usuario no tiene membresía en el grupo - se debe de agregar
                    $sPeticionSQL = paloDB::construirInsert(
                        'acl_membership', 
                        array(
                            'id_user'   =>  paloDB::DBCAMPO($id_user),
                            'id_group'  =>  paloDB::DBCAMPO($id_group),
                        ));
                    if (!($bExito = $this->_DB->genQuery($sPeticionSQL))) {
                        // Ocurre un error de base de datos
                        $this->errMsg = $this->_DB->errMsg;
                    }
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para asegurar que un usuario ya no pertenece al grupo indicado
     *
     * @param int   $id_user    ID del usuario que se desea agregar al grupo
     * @param int   $id_group   ID del grupo al cual se desea agregar al usuario
     *
     * @return bool VERDADERO si se puede remover el usuario del grupo, FALSO en caso de error.
     */
    function delFromGroup($id_user, $id_group)
    {
        $bExito = FALSE;

        if (!preg_match('/^[[:digit:]]+$/', "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else if (!preg_match('/^[[:digit:]]+$/', "$id_group")) {
            $this->errMsg = "Group ID is not numeric";
        } else {
            $sql = "DELETE FROM acl_membership WHERE id_user = '$id_user' AND id_group = '$id_group'";
            if (!($bExito = $this->_DB->genQuery($sql))) {
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para leer la lista de acciones disponibles para validar. Si se
     * especifica un ID numérico de acción, el listado contendrá únicamente la acción
     * indicada. De otro modo, se listarán todas las acciones.
     *
     * @param int   $id_action  Si != NULL, indica el ID de la acción a leer
     *
     * @return mixed Matriz de la forma descrita abajo, o FALSE en caso de error
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getActions($id_action = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_action) && !preg_match('/^[[:digit:]]+$/', "$id_action")) {
            $this->errMsg = "Action ID is not numeric";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_action".
                (is_null($id_action) ? '' : " WHERE id = $id_action");
            $arr_result = $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;

    }

    /**
     * Procedimiento para crear una acción bajo el nombre descrito, con una descripción opcional.
     * Si una acción con el nombre indicado ya existe, se reemplaza la descripción.
     *
     * @param string    $name           Nombre de la acción a crear
     * @param string    $description    Descripción de la acción a crear, opcional
     *
     * @return bool     VERDADERO si la acción ya existe o fue creada/actualizada correctamente
     */
    function createAction($name, $description = '')
    {
        $bExito = FALSE;
        $this->errMsg = "";
        if ($groupname == "") {
            $this->errMsg = "Action Name can't be empty";
        } else {
            if ($description == '') $description = $groupname;

            // Verificar si la acción ya existe
            $sPeticionSQL =
                "SELECT description FROM acl_action ".
                "WHERE name = ".paloDB::DBCAMPO($groupname);
            $tupla = $this->_DB->getFirstRowQuery($sPeticionSQL);
            if (!is_array($tupla)) {
                // Ocurre error de DB en consulta
                $this->errMsg = $this->_DB->errMsg;
            } else if (count($tupla) == 0) {
                // Acción no existía previamente
                $sPeticionSQL = paloDB::construirInsert(
                    "acl_action",
                    array(
                        "name"          =>  paloDB::DBCAMPO($groupname),
                        "description"   =>  paloDB::DBCAMPO($description),
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            } else {
                // Acción existía previamente, se actualiza opcionalmente desc
                if ($tupla[0] != $description) {
                    // Se modifica descripción de acción existente
                    $sPeticionSQL = paloDB::construirUpdate(
                        'acl_action',
                        array('description' =>  paloDB::DBCAMPO($description)),
                        array('name'        =>  paloDB::DBCAMPO($groupname)));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                } else {
                    // Se intenta crear acción idéntica a existente en DB
                    $bExito = TRUE;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento para obtener el listado de los recursos existentes en los ACL. Si
     * se especifica un ID numérico de recurso, el listado contendrá únicamente al recurso
     * indicado. De otro modo, se listarán todos los recursos.
     *
     * @param int   $id_rsrc    Si != NULL, indica el ID del recurso a recoger
     *
     * @return array    Listado de recursos en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getResources($id_rsrc = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_rsrc) && !preg_match('/^[[:digit:]]+$/', "$id_rsrc")) {
            $this->errMsg = "Resource ID is not numeric";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_resource".
                (is_null($id_rsrc) ? '' : " WHERE id = $id_rsrc");
            $arr_result = $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear un recurso bajo el nombre descrito, con una descripción opcional.
     * Si un recurso con el nombre indicado ya existe, se reemplaza la descripción.
     *
     * @param string    $name           Nombre del grupo a crear
     * @param string    $description    Descripción del grupo a crear, opcional
     *
     * @return bool     VERDADERO si el grupo ya existe o fue creado/actualizado correctamente
     */
    function createResource($name, $description = NULL)
    {
        $bExito = FALSE;
        $this->errMsg = "";
        if ($name == "") {
            $this->errMsg = "Resource Name can't be empty";
        } else {
            if ($description == '') $description = $name;

            // Verificar si el recurso ya existe
            $sPeticionSQL =
                "SELECT description FROM acl_resource ".
                "WHERE name = ".paloDB::DBCAMPO($name);
            $tupla = $this->_DB->getFirstRowQuery($sPeticionSQL);
            if (!is_array($tupla)) {
                // Ocurre error de DB en consulta
                $this->errMsg = $this->_DB->errMsg;
            } else if (count($tupla) == 0) {
                // Recurso no existía previamente
                $sPeticionSQL = paloDB::construirInsert(
                    'acl_resource',
                    array(
                        "name"          =>  paloDB::DBCAMPO($name),
                        "description"   =>  paloDB::DBCAMPO($description),
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            } else {
                // Recurso existía previamente, se actualiza opcionalmente desc
                if ($tupla[0] != $description) {
                    // Se modifica descripción de grupo existente
                    $sPeticionSQL = paloDB::construirUpdate(
                        'acl_resource',
                        array('description' =>  paloDB::DBCAMPO($description)),
                        array('name'        =>  paloDB::DBCAMPO($name)));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                } else {
                    // Se intenta crear recurso idéntico a existente en DB
                    $bExito = TRUE;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento que devuelve un arreglo con todas las acciones y recursos
     *
     * array("calendar" => array("view"),
     *       "calendar" => array("edit"),
     *       "task"     => array("view"),
     *       "contact"  => array("edit"))
     *
     *  como se puede ver el indice es el recurso y el valor es la accion.
     *  Con un arreglo de esta forma se puede usar la funcion array_merge_recursive
     *  para hacer un merge entre los permisos del usuario y de sus grupos
     *
     * @param int   $id_user    ID del usuario para el que se devuelve acciones autorizadas sobre recursos
     *
     * @return mixed    Matriz que describe las acciones autorizadas, o NULL en caso de error
     */
    function getUserPermissions($id_user)
    {
         $arr_resultado = array();
        if (!preg_match('/^[[:digit:]]+$/', "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else {
            $sql =
                "SELECT a.name, r.name, up.id ".
                "FROM acl_user_permission as up, acl_action as a, acl_resource as r ".
                "WHERE up.id_user = $id_user AND up.id_action = a.id AND up.id_resource = r.id";
            $result = $this->_DB->fetchTable($sql, FALSE);
            if ($result && is_array($result) && count($result)>0) {
                $arr_resultado = array();
                foreach($result as $key => $value)
                {
                    $indice  = $value[1];
                    $valor   = $value[0];
                    $indice2 = "u" . $value[2];
                    $arr_resultado[$indice][$indice2] = $valor;
                }
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return $arr_resultado;
    }

    /**
     * Procedimiento que devuelve un arreglo con todas las acciones y recursos de un grupo
     *
     * array("calendar" => array("view"),
     *       "calendar" => array("edit"),
     *       "task"     => array("view"),
     *       "contact"  => array("edit"))
     *
     * @param int   $id_group   ID del grupo para el que se devuelve acciones autorizadas sobre recursos
     *
     * @return mixed    Matriz que describe las acciones autorizadas, o NULL en caso de error
     */
    function getGroupPermissions($id_group)
    {
        $arr_resultado = NULL;
        if (!preg_match('/^[[:digit:]]+$/', "$id_group")) {
            $this->errMsg = "Group ID is not numeric";
        } else {
            $sPeticionSQL =
                "SELECT a.name, r.name, gp.id ".
                "FROM acl_group_permission as gp, acl_action as a , acl_resource as r ".
                "WHERE gp.id_group = $id_group AND gp.id_action = a.id AND gp.id_resource = r.id";
            $result = $this->_DB->fetchTable($sPeticionSQL, FALSE);
            if ($result && is_array($result) && count($result)>0) {
                $arr_resultado = array();
                foreach($result as $key => $value)
                {
                    $indice  = $value[1];
                    $valor   = $value[0];
                    $indice2 = "g" . $value[2];
                    $arr_resultado[$indice][$indice2] = $valor;
                }
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return $arr_resultado;
    }

    /**
     * Procedimiento que construye un arreglo que describe los permisos que tiene el usuario
     * indicado por sí mismo y por su pertenecia a todos los grupos registrados.
     *
     * @param int   $id_user    ID del usuario para el que se recuperan los permisos
     *
     * @return mixed    Arreglo de todos los permisos del usuario, o NULL en caso de error
     */
    function getArrayPermissions($id_user)
    {
        $arr_priv = NULL;
        
        if (!preg_match('/^[[:digit:]]+$/', "$id_user")) {
            $this->errMsg = "User ID is not numeric";
        } else {
            $listaUsuarios = $this->getUsers($id_user);
            if (is_array($listaUsuarios)) {
                if (count($listaUsuarios) == 0) {
                    $this->errMsg = "User doesn't exist";
                } else {
                    // Permisos personales del usuario
                    $arr_priv = $this->getUserPermissions($id_user);

                    // Agregar los permisos de los grupos del usuario
                    $arr_groups = $this->getMembership($id_user);
                    if (!is_array($arr_groups)) {
                        $arr_priv = NULL;
                    } else foreach ($arr_groups as $id_group) {
                        $arr_gpriv = $this->getGroupPermissions($id_group);
                        if (!is_array($arr_gpriv)) {
                            $arr_priv = NULL;
                            break;
                        } else {
                            $arr_priv = array_merge_recursive($arr_priv, $arr_gpriv);
                        }
                    }
                }
            }
        }
        return $arr_priv;
    }

    function isUserAuthorizedById($id_user, $action_name, $resource_name)
    {
        $arr_priv = $this->getArrayPermissions($id_user);
        // ahora hayo el subarreglo perteneciente al recurso en el cual estoy
        // interesado y busco si alli existe la accion

        if(!isset($arr_priv[$resource_name]) || !is_array($arr_priv[$resource_name])) {
            return FALSE; // probablemente porque no existia tal recurso o no tenia acciones
        }

        if(in_array($action_name, $arr_priv[$resource_name])) {
            return TRUE;
        } else {
            return FALSE;
        }

    }

    function isUserAuthorized($username, $action_name, $resource_name)
    {    
        if($id_user = $this->getIdUser($username)) {
            $resultado = $this->isUserAuthorizedById($id_user, $action_name, $resource_name);
        } else {
            $resultado = false;
        }
        return $resultado;
    }

    // Procedimiento para buscar la autenticación de un usuario en la tabla de ACLs.
    // Devuelve VERDADERO si el usuario existe y tiene el password MD5 indicado,
    // FALSE si no lo tiene, o en caso de error
    function authenticateUser ($user, $pass)
    {
        $user = trim($user);
        $pass = trim($pass);
        //$pass = md5($pass);

        if ($this->_DB->connStatus) {
            return FALSE;
        } else {
            $this->errMsg = "";

            if($user == "" or $pass == "") {
                $this->errMsg = PALOACL_MSG_ERROR_1;
                return FALSE;
            } else if (!preg_match("/^[[:alnum:]\.\\-_]+$/", $user)) {
                $this->errMsg = PALOACL_MSG_ERROR_2;
                return FALSE;
            } else if (!preg_match("/^[[:alnum:]]{32}$/", $pass)) {
                $this->errMsg = PALOACL_MSG_ERROR_3;
                return FALSE;
            }

            $sql = "SELECT name FROM acl_user WHERE name = '$user' AND md5_password = '$pass'";
            $arr = $this->_DB->fetchTable($sql);
            if (is_array($arr)) {
                return (count($arr) > 0);
            } else {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
        }
    }

    function saveGroupPermission($idGroup, $resources)
    {
        $bExito=FALSE;
        if (!preg_match('/^[[:digit:]]+$/', "$idGroup")) {
            $this->errMsg = "Group ID is not valid";
        } else {
            foreach ($resources as $resource){
                $sPeticionSQL=
                    "INSERT INTO acl_group_permission (id_action, id_group, id_resource) ".
                    "VALUES (1, $idGroup, $resource)";

                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }

    function deleteGroupPermission($idGroup,$resources)
    {
        $bExito=FALSE;
        if (!preg_match('/^[[:digit:]]+$/', "$idGroup")) {
            $this->errMsg = "Group ID is not valid";
        } else {
            foreach ($resources as $resource){
                $sPeticionSQL=
                    "DELETE FROM acl_group_permission ".
                    "WHERE id_group=$idGroup AND id_resource=$resource";
                
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para obtener la extension de un usuario mediante su username. 
     *
     * @param string   $username  Username del usuario
     *
     * @return string    numero de extension 
     */
    function getUserExtension($username)
    {
        $extension = null;
        if (is_null($username)) {
            $this->errMsg = "Username is not valid";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT extension FROM acl_user WHERE name = ?";
            $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE, array($username));
            if ($result && is_array($result) && count($result)>0) {
                $extension = $result[0];
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return $extension;
    }

    /**
     * Procedimiento para obtener el is del recurso dado su nombre. 
     *
     * @param string   $resource_name  Nombre del recurso
     *
     * @return string    numero de extension 
     */
    function getResourceId($resource_name)
    {
        $id_resource = null;
        if (!preg_match('/^([-_[:alnum:]]+[[a-z0-9\-_]+]*)$/', "$resource_name")) {
            $this->errMsg = "Resource Name is not valid";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id FROM acl_resource WHERE name = '$resource_name'";
            $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE);
            if ($result && is_array($result) && count($result)>0) {
                $id_resource = $result[0];
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return $id_resource;
    }

    /**
     * Procedimiento para saber si un usuario (login) pertenece al grupo administrador
     *
     * @param string   $username  Username del usuario
     *
     * @return boolean true or false 
     */
    function isUserAdministratorGroup($username)
    {
        $is=false;
        $idUser = $this->getIdUser($username);
        if($idUser){
            $arrGroup = $this->getMembership($idUser);
            //$is = array_key_exists('administrator',$arrGroup);
            $is = array_search('1', $arrGroup);
        }
        return $is;
    }

      /**
     * Procedimiento para crear un nuevo grupo
     *
     * @param string    $group       Login del usuario a crear
     * @param string    $description    Descripción del usuario a crear
     *
     * @return bool     VERDADERO si el grupo se crea correctamente, FALSO en error
     */
    function createGroup($group, $description)
    {
        $bExito = FALSE;
        if ($group == "") {
            $this->errMsg = "Group can't be empty";
        } else {
            if ( !$description ) $description = $group;

            // Verificar que el nombre de Grupo no existe previamente
            $id_group = $this->getIdGroup($group);
            if ($id_group !== FALSE) {
                $this->errMsg = "Group already exists";
            } elseif ($this->errMsg == "") {

                $sPeticionSQL = paloDB::construirInsert(
                    "acl_group",
                    array(
                        "name"          =>  paloDB::DBCAMPO($group),
                        "description"   =>  paloDB::DBCAMPO($description)
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento para modificar al grupo con el ID de grupo especificado, para
     * darle un nuevo nombre y descripción.
     *
     * @param int       $id_group        Indica el ID del grupo a modificar
     * @param string    $group           Grupo a modificar
     * @param string    $description     Descripción del grupo a modificar
     *
     * @return bool VERDADERO si se ha modificado correctamente el grupo, FALSO si ocurre un error.
     */
    function updateGroup($id_group, $group, $description)
    {
        $bExito = FALSE;
        if ($group == "") {
            $this->errMsg = "Group can't be empty";
        } else if (!preg_match("/^[[:digit:]]+$/", "$id_group")) {
            $this->errMsg = "Group ID is not numeric";
        } else {
            if ( !$description ) $description = $group;

            // Verificar que el grupo indicado existe
            $tuplaGroup = $this->getGroups($id_group);
            if (!is_array($tuplaGroup)) {
                $this->errMsg = "On having checked group's existence - ".$this->errMsg;
            } else if (count($tuplaGroup) == 0) {
                $this->errMsg = "The group doesn't exist";
            } else {
                $bContinuar = TRUE;

                // Si el nuevo group es distinto al anterior, se verifica si el nuevo
                // group colisiona con uno ya existente
                if ($tuplaGroup[0][1] != $group) {
                    $id_group_conflicto = $this->getIdGroup($group);
                    if ($id_group_conflicto !== FALSE) {
                        $this->errMsg = "Group already exists";
                        $bContinuar = FALSE;
                    } elseif ($this->errMsg != "") {
                        $bContinuar = FALSE;
                    }
                }

                if ($bContinuar) {
                    // Proseguir con la modificación del grupo
                    $sPeticionSQL = paloDB::construirUpdate(
                        "acl_group",
                        array(
                            "name"          =>  paloDB::DBCAMPO($group),
                            "description"   =>  paloDB::DBCAMPO($description),
                            ),
                        array(
                            "id"  =>  $id_group));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para borrar un grupo ACL, dado su ID numérico de grupo
     *
     * @param int   $id_group    ID del grupo que debe eliminarse
     *
     * @return bool VERDADERO si el grupo puede borrarse correctamente
     */
    function deleteGroup($id_group)
    {
        $bExito = FALSE;
        if (!preg_match('/^[[:digit:]]+$/', "$id_group")) {
            $this->errMsg = "Group ID is not numeric";
        } else {
            $this->errMsg = "";
            $listaSQL = array(
                "DELETE FROM acl_group_permission WHERE id_group = '$id_group'",
                "DELETE FROM acl_group WHERE id = '$id_group'",
            );
            $bExito = TRUE;

            foreach ($listaSQL as $sPeticionSQL) {
                $bExito = $this->_DB->genQuery($sPeticionSQL);
                if (!$bExito) {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }

    function HaveUsersTheGroup($id_group)
    {
        $Haveusers = TRUE;
        if (!is_null($id_group) && !preg_match('/^[[:digit:]]+$/', "$id_group")) {
            $this->errMsg = "Group ID is not numeric";
        } else {
            $sPeticionSQL = "SELECT count(*) FROM acl_membership WHERE id_group = $id_group";
            $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE);
            if ($result && is_array($result) && count($result)>0) {
                $users = $result[0];
                if($users==0)
                    $Haveusers = FALSE;
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return $Haveusers;
    }

    //************************************************************************************************************************
    //************************************************************************************************************************

    function getNumResources($filter_resource)
    {
        $query = "SELECT count(id) ".
                 "FROM acl_resource ";
        if(!is_array($filter_resource)){
            $query .= "WHERE description LIKE ?";
            $arrParam = array("%$filter_resource%");
        }else{
            $query .= "WHERE ";
            $i=1;
            $arrParam = array();
            foreach($filter_resource as $key=>$value){
                if($i==count($filter_resource)){
                    $query .= "description LIKE ?";
                    $arrParam[] = "%$value%";
                }
                else{
                    $query .= "description = ? or ";
                    $arrParam[] = $value;
                }
                $i++;
            }
        }
        $result = $this->_DB->getFirstRowQuery($query, FALSE, $arrParam);

        if( $result == false )
        {
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getListResources($limit, $offset, $filter_resource)
    {
        $query = "SELECT id, name, description ".
                 "FROM acl_resource ";
        $arrParam = array();

        if(!is_array($filter_resource)){
            $query .= "WHERE description LIKE ? ";
            $arrParam[] = "%$filter_resource%";
        }else{
            $query .= "WHERE ";
            $i=1;
            foreach($filter_resource as $key=>$value){
                if($i==count($filter_resource)){
                    $query .= "description LIKE ? ";
                    $arrParam[] = "%$value%";
                }
                else{
                    $query .= "description = ? or ";
                    $arrParam[] = $value;
                }
                $i++;
            }
        }
        $query .= "LIMIT ? OFFSET ?";
        $arrParam[] = $limit;
        $arrParam[] = $offset;
        $result = $this->_DB->fetchTable($query, true, $arrParam);

        if( $result == false )
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    function loadGroupPermissions($id_group)
    {
        $query = "SELECT ar.id resource_id, ar.name resource_name, gp.action_name action_name ".
                 "FROM acl_resource ar, (SELECT aa.name action_name, agp.id_resource resource_id ".
                                        "FROM acl_group_permission agp ".
                                        "INNER JOIN acl_action aa on agp.id_action = aa.id ".
                                        "WHERE agp.id_group = $id_group ) AS gp ".
                 "WHERE gp.resource_id = ar.id ".
                 "ORDER BY 1 asc ";

        $result = $this->_DB->fetchTable($query, true);
        if( $result == false ) {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    function loadResourceGroupPermissions($action, $id_group)
    {
        $query = "SELECT acl_resource.name AS resource_name  ".
                 "FROM acl_resource, (SELECT acl_group_permission.id_resource  AS resource_id ".
                                     "FROM acl_group_permission, acl_action ".
                                     "WHERE acl_group_permission.id_action = acl_action.id AND ".
                                           "acl_group_permission.id_group = $id_group AND ".
                                           "acl_action.name = '$action' ) AS gp ".
                 "WHERE gp.resource_id = acl_resource.id ";

        $result = $this->_DB->fetchTable($query, true);
        if( $result == false ) {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    function saveGroupPermissions($action, $idGroup, $resources)
    {
        $bExito = FALSE;
        if (!preg_match('/^[[:digit:]]+$/', "$idGroup"))
            $this->errMsg = "Group ID is not valid";
        else
        {
            foreach ($resources as $resource)
            {
                $sPeticionSQL = "INSERT INTO acl_group_permission (id_action, id_group, id_resource) ".
                                "VALUES( (SELECT id FROM acl_action WHERE name = '$action') , $idGroup, $resource)";

                if ($this->_DB->genQuery($sPeticionSQL))
                    $bExito = TRUE;
                else {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }

    function deleteGroupPermissions($action, $idGroup, $resources)
    {
        $bExito = FALSE;
        if (!preg_match('/^[[:digit:]]+$/', "$idGroup"))
            $this->errMsg = "Group ID is not valid";
        else
        {
            foreach ($resources as $resource){
                $sPeticionSQL = "DELETE FROM acl_group_permission ".
                                "WHERE id_group = $idGroup AND id_resource = $resource AND ".
                                      "id_action = (SELECT id FROM acl_action WHERE name = '$action') ";
                
                if ($this->_DB->genQuery($sPeticionSQL))
                    $bExito = TRUE;
                else
                {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }
 /**
     * Procedimiento para obtener el id del recurso mediante su nameid. 
     *
     * @param string   $username  
     *
     * @return integer    id 
     *******************************************************************/
    function getIdResource($resource_name)
    {
        $id_resource = null;
        if (!preg_match('/^([-_[:alnum:]]+[[a-z0-9\-_]+]*)$/', "$resource_name")) {
            $this->errMsg = "Resource Name is not valid";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id FROM acl_resource WHERE name = '$resource_name'";
            $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE);
            if ($result && is_array($result) && count($result)>0) {
                $id_resource = $result[0];
                return $id_resource;
            }else $this->errMsg = $this->_DB->errMsg;
        }
        return 0;
    }

    /**
     * Procedimiento para eliminar el recurso dado su id. 
     *
     * @param integer   $idresource
     *
     * @return bool     si es verdadero entonces se elimino bien
     ******************************************************************/
    function deleteIdResource($idresource)
    {
        $this->errMsg = "";
        $sPeticionSQL = "DELETE FROM acl_resource WHERE id = $idresource";
        $result = $this->_DB->genQuery($sPeticionSQL);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    /**
     * Procedimiento para eliminar los permisos de un grupo
     *
     * @param integer    $idresource  es el id del recurso
     *
     * @return bool    si es verdadero entonces se elimino bien
     **************************************************************/
    function deleteIdGroupPermission($idresource)
    {
        $id_resource = null;
        $sPeticionSQL = "DELETE FROM acl_group_permission WHERE id_resource = $idresource";
        $result = $this->_DB->genQuery($sPeticionSQL);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

     /**
     * Procedimiento para obtener el nombre del grupo dado un id. 
     *
     * @param integer   $idGroup  id del grupo
     *
     * @return string    nombre del grupo 
     */
    function getGroupNameByid($idGroup)
    {
        $groupName = null;
        $this->errMsg = "";
        $data = array($idGroup);
        $sPeticionSQL = "SELECT name FROM acl_group WHERE id = ?";
        $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE, $data);
        if ($result && is_array($result) && count($result)>0) {
            $groupName = $result[0];
        }else $this->errMsg = $this->_DB->errMsg;
        return $groupName;
    }
}
?>
