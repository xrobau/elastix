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
  $Id: Call.class.php,v 1.1 2012/05/23 23:49:36 Alberto Santos Exp $
*/

$documentRoot = $_SERVER["DOCUMENT_ROOT"];
require_once "$documentRoot/libs/REST_Resource.class.php";
require_once "$documentRoot/libs/paloSantoJSON.class.php";
require_once "$documentRoot/modules/pbxadmin/libs/core.class.php";

/*
 * Para esta implementación de REST, se tienen los siguientes URI
 * 
 *  /Call/XXXX            application/json
 *      GET     realiza una llamada al número XXXX de la extensión asociada al usuario
 *
 */

class Call
{
    private $resourcePath;

    function __construct($resourcePath)
    {
	$this->resourcePath = $resourcePath;
    }

    function URIObject()
    {
	$uriObject = NULL;
	if(count($this->resourcePath) > 0)
	    $uriObject = new MakeCall(array_shift($this->resourcePath));
	if(count($this->resourcePath) > 0)
	    return NULL;
	else
	    return $uriObject;
    }
}

class MakeCall extends REST_Resource
{
    protected $_phoneNumber;

    function __construct($number)
    {
        $this->_phoneNumber = $number;
    }

    function HTTP_GET()
    {
	$json = new paloSantoJSON();
	$pCore_Pbx = new core_PBX();
	$result = $pCore_Pbx->makeCall($this->_phoneNumber);
	if($result === FALSE){
	    $error = $pCore_Pbx->getError();
            if($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            
	    $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
	}
	else{
	     $json = new Services_JSON();
	     return $json->encode($result);
	}
    }
}
?>