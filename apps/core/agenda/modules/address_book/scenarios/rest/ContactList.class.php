<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2003 Palosanto Solutions S. A.                    |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  +----------------------------------------------------------------------+
  | Este archivo fuente está sujeto a las políticas de licenciamiento    |
  | de Palosanto Solutions S. A. y no está disponible públicamente.      |
  | El acceso a este documento está restringido según lo estipulado      |
  | en los acuerdos de confidencialidad los cuales son parte de las      |
  | políticas internas de Palosanto Solutions S. A.                      |
  | Si Ud. está viendo este archivo y no tiene autorización explícita    |
  | de hacerlo, comuníquese con nosotros, podría estar infringiendo      |
  | la ley sin saberlo.                                                  |
  +----------------------------------------------------------------------+
  | Autores: Alberto Santos Flores <asantos@palosanto.com>               |
  +----------------------------------------------------------------------+
  $Id: ContactList.class.php,v 1.1 2012/02/07 23:49:36 Alberto Santos Exp $
*/

$documentRoot = $_SERVER["DOCUMENT_ROOT"];
require_once "$documentRoot/libs/REST_Resource.class.php";
require_once "$documentRoot/libs/paloSantoJSON.class.php";
require_once "$documentRoot/modules/address_book/libs/core.class.php";
/*
 * Para esta implementación de REST, se tienen los siguientes URI
 *
 *  /ContactList            application/json
 *      GET     lista un par de URIs para contactos internos y externos
 *  /ContactList/internal[?limit=X&offset=Y]   application/json
 *      GET     lista un reporte de todos los contactos internos, o de los
 *              indicados por los parámetros limit y offset.
 *  /ContactList/internal/XXXX application/json
 *      GET     reporta la información del contacto interno cuyo número de
 *              teléfono es XXXX
 *  /ContactList/external[?limit=X&offset=Y]   application/json
 *      GET     lista un reporte de todos los contactos externos, o de los
 *              indicados por los parámetros limit y offset.
 *      POST    recibe una representación estándar application/x-www-form-urlencoded
 *              que contiene [phone first_name last_name email address company
 *              notes status cell_phone home_phone fax1 fax2 province city
 *              company_contact contact_rol], y crea un nuevo
 *              contacto para el usuario.
 *  /ContactList/internal/XXXX application/json
 *      GET     reporta la información del contacto externo cuyo ID de base de
 *              datos es XXXX.
 *      PUT     actualiza la información del contacto externo XXXX con [phone
 *              first_name last_name email address company notes status cell_phone
 *              home_phone fax1 fax2 province city company_contact contact_rol]
 *      DELETE  borra el contacto externo
 */

class ContactList
{
    private $resourcePath;
    function __construct($resourcePath)
    {
        $this->resourcePath = $resourcePath;
    }

    function URIObject()
    {
        $uriObject = NULL;
        if (count($this->resourcePath) <= 0) {
                $uriObject = new ContactListBase();
        } elseif (in_array($this->resourcePath[0], array('internal', 'external'))) {
            switch (array_shift($this->resourcePath)) {
            case 'internal':
                if(count($this->resourcePath) <= 0)
                        $uriObject =  new InternalContactList();
                else{
                        $id = array_shift($this->resourcePath);
                        if(count($this->resourcePath) <= 0)
                                $uriObject = new InternalContact($id);
                        elseif(array_shift($this->resourcePath) == "icon"){
                                if(count($this->resourcePath) <= 0)
                                        $uriObject = new ContactImg($id,"no","internal");
                                 elseif(array_shift($this->resourcePath) == "thumbnail")
                                        $uriObject = new ContactImg($id,"yes","internal");
                        }
                }
                break;
            case 'external':
                if(count($this->resourcePath) <= 0)
                    $uriObject = new ExternalContactList();
                else{
                    $id = array_shift($this->resourcePath);
                    if(count($this->resourcePath) <= 0)
                        $uriObject = new ExternalContact($id);
                    elseif(array_shift($this->resourcePath) == "icon"){
                        if(count($this->resourcePath) <= 0)
                            $uriObject = new ContactImg($id,"no","external");
                        elseif(array_shift($this->resourcePath) == "thumbnail")
                            $uriObject = new ContactImg($id,"yes","external");
                    }
                }
                break;
            }
        }
        if(count($this->resourcePath) > 0)
            return NULL;
        else
            return $uriObject;
    }
}

class ContactListBase extends REST_Resource
{
        function HTTP_GET()
    {
            $json = new Services_JSON();
        return $json->encode(array(
            'url_internal'  =>  '/rest.php/address_book/ContactList/internal',
            'url_external'  =>  '/rest.php/address_book/ContactList/external',));
    }
}

class ContactListResource extends REST_Resource
{
        protected $_addressBookType;

    function __construct($sAddressBookType)
    {
            $this->_addressBookType = $sAddressBookType;
    }

    function HTTP_GET()
    {
        $pCore_AddressBook = new core_AddressBook();
        $json = new paloSantoJSON();

        $limit = isset($_GET["limit"]) ? $_GET["limit"] : NULL;
        $offset = isset($_GET["offset"]) ? $_GET["offset"] : NULL;
        $result = $pCore_AddressBook->listAddressBook($this->_addressBookType,
            $offset, $limit, NULL);
        if (!is_array($result)) {
            $error = $pCore_AddressBook->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }

        $sBaseUrl = '/rest.php/address_book/ContactList/'.$this->_addressBookType;
        foreach (array_keys($result['extension']) as $k)
            $result['extension'][$k]['url'] = $sBaseUrl.'/'.$result['extension'][$k]['id'];
        $json = new Services_JSON();
        return $json->encode($result);
    }
}

class InternalContactList extends ContactListResource
{
        function __construct()
    {
            parent::__construct('internal');
    }
}

class ExternalContactList extends ContactListResource
{
    function __construct()
    {
        parent::__construct('external');
    }

    function HTTP_GET()
    {
        if (isset($_GET['querytype'])) switch ($_GET['querytype']) {
        case 'emailsearch':
            return $this->_emailSearch();
        }
        return parent::HTTP_GET();
    }

    function HTTP_POST()
    {
        $json = new paloSantoJSON();
            if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded') {
            header('HTTP/1.1 415 Unsupported Media Type');
            $json->set_status("ERROR");
            $json->set_error('Please POST standard URL encoding only');
            return $json->createJSON();
            }

        $pCore_AddressBook = new core_AddressBook();

        $first_name          = (isset($_POST["first_name"]))      ? $_POST["first_name"]           : NULL;
        $last_name          = (isset($_POST["last_name"]))       ? $_POST["last_name"]           : NULL;
        $work_phone         = (isset($_POST["phone"]))               ? $_POST["phone"]           : NULL;
        $cell_phone          = (isset($_POST["cell_phone"]))      ? $_POST["cell_phone"]           : NULL;
        $home_phone          = (isset($_POST["home_phone"]))      ? $_POST["home_phone"]           : NULL;
        $fax1                  = (isset($_POST["fax1"]))               ? $_POST["fax1"]                   : NULL;
        $fax2                  = (isset($_POST["fax2"]))               ? $_POST["fax2"]                   : NULL;
        $email                  = (isset($_POST["email"]))               ? $_POST["email"]           : NULL;
        $province         = (isset($_POST["province"]))               ? $_POST["province"]           : NULL;
        $city                 = (isset($_POST["city"]))               ? $_POST["city"]                   : NULL;
        $address          = (isset($_POST["address"]))               ? $_POST["address"]           : NULL;
        $company          = (isset($_POST["company"]))               ? $_POST["company"]           : NULL;
        $company_contact = (isset($_POST["company_contact"])) ? $_POST["company_contact"] : NULL;
        $contact_rol           = (isset($_POST["contact_rol"]))     ? $_POST["contact_rol"]          : NULL;
        $picture         = (isset($_POST["picture"]))         ? $_POST["picture"]          : NULL;
        $notes                   = (isset($_POST["notes"]))               ? $_POST["notes"]           : NULL;
        $status          = (isset($_POST["status"]))               ? $_POST["status"]           : NULL;




        $result = $pCore_AddressBook->addAddressBookContact($work_phone, $first_name, $last_name, $email, TRUE, $address, $company, $notes, $status, $cell_phone, $home_phone, $fax1, $fax2, $province, $city, $company_contact, $contact_rol, $picture);
        if ($result !== FALSE) {
            Header('HTTP/1.1 201 Created');
            Header('Location: /rest.php/address_book/ContactList/'.$this->_addressBookType."/$result");
        } else {
            $error = $pCore_AddressBook->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
    }

    private function _emailSearch()
    {
        global $arrConf;

        $elastixuser = $_SERVER['PHP_AUTH_USER'];

        $json = new paloSantoJSON();
        $response = array();
        if (isset($_GET['q']) && trim($_GET['q']) != '') {
            // Obtener ID de ACL del usuario, dado el nombre de usuario
            $pACL = new paloACL(new paloDB($arrConf['elastix_dsn']['acl']));
            $id_user = $pACL->getIdUser($elastixuser);

            $search = trim($_GET['q']);

            // Buscar coincidencias de la búsqueda
            $pDBAddress = new paloDB($arrConf['dsn_conn_database']);
            $sql = <<<SQL_BUSCAR
SELECT name, last_name, email, id
FROM contact
WHERE (iduser = ? OR status = 'isPublic')
    AND email <> ''
    AND (name LIKE ? OR last_name LIKE ? OR email LIKE ?)
ORDER BY last_name, name, email, id
SQL_BUSCAR;
            $recordset = $pDBAddress->fetchTable($sql, TRUE,
                array($id_user, "%$search%", "%$search%", "%$search%"));
            if (!is_array($recordset)) $recordset = array();
            foreach ($recordset as $tupla) {
                $response[] = array(
                    'label' =>  trim($tupla['name']).
                                ((trim($tupla['last_name']) == '')
                                    ? ''
                                    : ' '.' '.trim($tupla['last_name'])).
                                ' <'.$tupla['email'].'>',
                    'value' =>  $tupla['id'],
                );
            }

        }
        $json = new Services_JSON();
        return $json->encode($response);
    }
}

class Contact extends REST_Resource
{
    protected $_addressBookType;
    protected $_idNumero;

        function __construct($sAddressBookType, $sIdNumero)
    {
        $this->_addressBookType = $sAddressBookType;
        $this->_idNumero = $sIdNumero;
    }

    function HTTP_GET()
    {
        $pCore_AddressBook = new core_AddressBook();
        $json = new paloSantoJSON();

        $result = $pCore_AddressBook->listAddressBook($this->_addressBookType,
            NULL, NULL, $this->_idNumero);
        if (!is_array($result)) {
            $error = $pCore_AddressBook->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
        if (count($result['extension']) <= 0) {
                header("HTTP/1.1 404 Not Found");
            $json->set_status("ERROR");
            $json->set_error('No contact was found');
            return $json->createJSON();
        }

        $tupla = $result['extension'][0];
        $tupla['url'] = '/rest.php/address_book/ContactList/'.$this->_addressBookType.'/'.$this->_idNumero;
        $json = new Services_JSON();
        return $json->encode($tupla);
    }
}

class InternalContact extends Contact
{
    function __construct($sIdNumero)
    {
        parent::__construct('internal', $sIdNumero);
    }
}

class ExternalContact extends Contact
{
    function __construct($sIdNumero)
    {
        parent::__construct('external', $sIdNumero);
    }

    function HTTP_PUT()
    {
        $json = new paloSantoJSON();
        if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded') {
            header('HTTP/1.1 415 Unsupported Media Type');
            $json->set_status("ERROR");
            $json->set_error('Please POST standard URL encoding only');
            return $json->createJSON();
        }

        $pCore_AddressBook = new core_AddressBook();
        $putvars = NULL;
        parse_str(file_get_contents('php://input'), $putvars);

        $first_name          = (isset($putvars["first_name"]))      ? $putvars["first_name"]      : NULL;
        $last_name          = (isset($putvars["last_name"]))       ? $putvars["last_name"]       : NULL;
        $work_phone         = (isset($putvars["phone"]))                 ? $putvars["phone"]               : NULL;
        $cell_phone          = (isset($putvars["cell_phone"]))      ? $putvars["cell_phone"]      : NULL;
        $home_phone          = (isset($putvars["home_phone"]))      ? $putvars["home_phone"]      : NULL;
        $fax1                  = (isset($putvars["fax1"]))                 ? $putvars["fax1"]            : NULL;
        $fax2                  = (isset($putvars["fax2"]))                 ? $putvars["fax2"]               : NULL;
        $email                  = (isset($putvars["email"]))                 ? $putvars["email"]               : NULL;
        $province         = (isset($putvars["province"]))        ? $putvars["province"]               : NULL;
        $city                 = (isset($putvars["city"]))                 ? $putvars["city"]               : NULL;
        $address          = (isset($putvars["address"]))         ? $putvars["address"]               : NULL;
        $company          = (isset($putvars["company"]))         ? $putvars["company"]               : NULL;
        $company_contact = (isset($putvars["company_contact"])) ? $putvars["company_contact"] : NULL;
        $contact_rol           = (isset($putvars["contact_rol"]))     ? $putvars["contact_rol"]     : NULL;
        $picture         = (isset($putvars["picture"]))         ? $putvars["picture"]              : NULL;
        $notes                   = (isset($putvars["notes"]))                 ? $putvars["notes"]               : NULL;
        $status          = (isset($putvars["status"]))                 ? $putvars["status"]               : NULL;

        $result = $pCore_AddressBook->updateContact($this->_idNumero, $work_phone, $first_name, $last_name, $email, $address, $company, $notes, $status, $cell_phone, $home_phone, $fax1, $fax2, $province, $city, $company_contact, $contact_rol, $picture);
        if ($result === FALSE) {
            $error = $pCore_AddressBook->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
        else{
            $json = new Services_JSON();
            $response["message"] = "The contact was successfully modified";
            return $json->encode($response);
        }
    }

    function HTTP_DELETE()
    {
        $json = new paloSantoJSON();
        $pCore_AddressBook = new core_AddressBook();
            $result = $pCore_AddressBook->delAddressBookContact($this->_idNumero);
        if ($result === FALSE) {
            $error = $pCore_AddressBook->getError();
            if($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            elseif ($error['fc'] == 'ADDRESSBOOK')
                header("HTTP/1.1 404 Not Found");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
        else{
            $json = new Services_JSON();
            $response["message"] = "The contact was successfully deleted";
            return $json->encode($response);
        }
    }
}

class ContactImg extends REST_Resource
{
    protected $_idNumero;
    protected $_thumbnail;
    protected $_directory;

    function __construct($sNIdumero, $thumbnail, $directory)
    {
         $this->_idNumero = $sNIdumero;
         $this->_thumbnail = $thumbnail;
         $this->_directory = $directory;
    }

    function HTTP_GET()
    {
        $pCore_AddressBook = new core_AddressBook();
        $image = $pCore_AddressBook->getContactImage($this->_idNumero, $this->_thumbnail, $this->_directory);
        if($image === FALSE){
            $json = new paloSantoJSON();
            $error = $pCore_AddressBook->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
        else
            return $image;
    }
}
?>
