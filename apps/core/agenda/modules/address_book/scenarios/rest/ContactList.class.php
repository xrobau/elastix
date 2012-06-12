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
 *              que contiene [phone first_name last_name email], y crea un nuevo
 *              contacto para el usuario.
 *  /ContactList/internal/XXXX application/json
 *      GET     reporta la información del contacto externo cuyo ID de base de 
 *              datos es XXXX.
 *      PUT     actualiza la información del contacto externo con [phone 
 *              first_name last_name email]
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
		$uriObject = (count($this->resourcePath) <= 0) 
		    ? new InternalContactList() 
		    : new InternalContact(array_shift($this->resourcePath));
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
			    $uriObject = new ExternalContactImg($id,"no");
			elseif(array_shift($this->resourcePath) == "thumbnail")
			    $uriObject = new ExternalContactImg($id,"yes");
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
            'url_internal'  =>  $this->requestURL().'/internal',
            'url_external'  =>  $this->requestURL().'/external',));
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
        
        $sBaseUrl = $this->requestURL();
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
        $phone = (isset($_POST["phone"])) ? $_POST["phone"] : NULL;
        $first_name = (isset($_POST["first_name"])) ? $_POST["first_name"] : NULL;
        $last_name = (isset($_POST["last_name"])) ? $_POST["last_name"] : NULL;
        $email = (isset($_POST["email"])) ? $_POST["email"] : NULL;
        
        $result = $pCore_AddressBook->addAddressBookContact($phone, $first_name, $last_name, $email, TRUE);
        if ($result !== FALSE) {
            Header('HTTP/1.1 201 Created');
            Header('Location: '.$this->requestURL()."/$result");
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
        $tupla['url'] = $this->requestURL();
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
        $phone = (isset($putvars["phone"])) ? $putvars["phone"] : NULL;
        $first_name = (isset($putvars["first_name"])) ? $putvars["first_name"] : NULL;
        $last_name = (isset($putvars["last_name"])) ? $putvars["last_name"] : NULL;
        $email = (isset($putvars["email"])) ? $putvars["email"] : NULL;
        
        $result = $pCore_AddressBook->updateContact($this->_idNumero, $phone, $first_name, $last_name, $email);
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

class ExternalContactImg extends REST_Resource
{
    protected $_idNumero;
    protected $_thumbnail;

    function __construct($sNIdumero, $thumbnail)
    {
	 $this->_idNumero = $sNIdumero;
	 $this->_thumbnail = $thumbnail;
    }

    function HTTP_GET()
    {
	$pCore_AddressBook = new core_AddressBook();
	$image = $pCore_AddressBook->getContactImage($this->_idNumero, $this->_thumbnail);
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