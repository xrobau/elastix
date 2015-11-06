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
  $Id: CalendarEvent.class.php,v 1.1 2012/02/07 23:49:36 Alberto Santos Exp $
*/

$documentRoot = $_SERVER["DOCUMENT_ROOT"];
require_once "$documentRoot/libs/REST_Resource.class.php";
require_once "$documentRoot/libs/paloSantoJSON.class.php";
require_once "$documentRoot/modules/calendar/libs/core.class.php";

define('REST_CALENDAR_BASEURL', '/rest.php/'.$arrConf['module_name'].'/CalendarEvent/');

class CalendarEvent
{
    private $resourcePath;
    function __construct($resourcePath)
    {
        $this->resourcePath = $resourcePath;
    }

    function URIObject()
    {
        $uriObject = (count($this->resourcePath) > 0)
            ? new CalendarEventById(array_shift($this->resourcePath))
            : new CalendarEventBase();
        return (count($this->resourcePath) <= 0) ? $uriObject : NULL;
    }
}

class CalendarEventBase extends REST_Resource
{
    function HTTP_GET()
    {
        global $arrConf;

        $pCore_Calendar = new core_Calendar();
        $json = new paloSantoJSON();

        // Verificar si se está sirviendo un feed para fullcalendar
        $bFullCalendar = isset($_GET['format']) && $_GET['format'] == 'fullcalendar';

        // Verificar si se está sirviendo una descarga iCal
        $bIcal = isset($_GET['format']) && $_GET['format'] == 'ical';

        // FullCalendar manda fechas como timestamps de Unix
        if ($bFullCalendar) {
            if (isset($_GET['start'])) $_GET['startdate'] = date('Y-m-d', $_GET['start']);
            if (isset($_GET['end'])) $_GET['enddate'] = date('Y-m-d', $_GET['end']);
        }

        $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : NULL;
        $enddate = isset($_GET['enddate']) ? $_GET['enddate'] : NULL;
        $result = $pCore_Calendar->listCalendarEvents($startdate, $enddate, NULL, $bIcal);
        if (!is_array($result)) {
            $error = $pCore_Calendar->getError();
            if ($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }

        if ($bIcal) {
            // Formato iCal
            header('Content-Type: text/calendar');
            header('Content-Disposition: inline; filename="icalout.ics"');
            $smarty = getSmarty('default');
            $smarty->assign('eventlist', $result);
            return $smarty->fetch(ROOT.'/modules/'.$arrConf['module_name'].'/themes/default/icalout.tpl');
        } else {
            // Formatos JSON
            if ($bFullCalendar) {
                $fcresult = array();
                foreach ($result['events'] as $event) {
                    $fcresult[] = array(
                        'id'        =>  $event['id'],
                        'title'     =>  $event['subject'],
                        'start'     =>  $event['starttime'],
                        'end'       =>  $event['endtime'],
                        'allDay'    =>  false,
                        'color'     =>  $event['color'],

                        /*  Este URL es el recurso REST y no debe visitarse desde el browser.
                            El callback eventClick() de fullCalendar debe de recoger este
                            URL para hacer la petición REST, y devolver FALSE desde el
                            callback.
                         */
                        'url'       =>  REST_CALENDAR_BASEURL.$event['id'],
                    );
                }
                $result = $fcresult;
            } else {
                foreach (array_keys($result['events']) as $k)
                    $result['events'][$k]['url'] = REST_CALENDAR_BASEURL.$result['events'][$k]['id'];
            }

            $json = new Services_JSON();
            return $json->encode($result);
        }
    }

    function HTTP_POST()
    {
        $json = new paloSantoJSON();
        if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== 0) {
            header('HTTP/1.1 415 Unsupported Media Type');
            $json->set_status("ERROR");
            $json->set_error('Please POST standard URL encoding only');
            return $json->createJSON();
        }

        $postkeys = array_keys($_POST);
        if (in_array('asterisk_call', $postkeys)) {
            $_POST['asterisk_call'] = !(empty($_POST['asterisk_call']) || $_POST['asterisk_call'] == 'false' || $_POST['asterisk_call'] == "0");
        }
        foreach (array('startdate', 'enddate', 'subject', 'description',
            'asterisk_call', 'recording', 'call_to', 'reminder_timer',
            'emails_notification', 'color') as $k) {
            if (!isset($_POST[$k])) $_POST[$k] = NULL;
        }

        $pCore_Calendar      = new core_Calendar();
        $result = $pCore_Calendar->addCalendarEvent($_POST['startdate'],
            $_POST['enddate'], $_POST['subject'], $_POST['description'],
            $_POST['asterisk_call'], $_POST['recording'], $_POST['call_to'],
            $_POST['reminder_timer'], $_POST['color'], $_POST['emails_notification'],
            TRUE);
        if ($result !== FALSE) {
            Header('HTTP/1.1 201 Created');
            Header('Location: '.REST_CALENDAR_BASEURL.$result);
            return 'null';
        } else {
            $error = $pCore_Calendar->getError();
            if ($error["fc"] == "DBERROR")
                header('HTTP/1.1 500 Internal Server Error');
            else
                header('HTTP/1.1 400 Bad Request');
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
    }
}

class CalendarEventById extends REST_Resource
{
    protected $_idNumero;

    function __construct($sIdNumero)
    {
        $this->_idNumero = $sIdNumero;
    }

    function HTTP_GET()
    {
        $pCore_Calendar = new core_Calendar();
        $json = new paloSantoJSON();

        $result = $pCore_Calendar->listCalendarEvents(NULL,NULL, $this->_idNumero);
        if (!is_array($result)) {
            $error = $pCore_Calendar->getError();
            if ($error["fc"] == "DBERROR")
                header('HTTP/1.1 500 Internal Server Error');
            else
                header('HTTP/1.1 400 Bad Request');
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
        if (count($result['events']) <= 0) {
            header('HTTP/1.1 404 Not Found');
            $json->set_status("ERROR");
            $json->set_error('No event was found');
            return $json->createJSON();
        }

        $tupla = $result['events'][0];
        $tupla['url'] = REST_CALENDAR_BASEURL.$this->_idNumero;
        $json = new Services_JSON();
        return $json->encode($tupla);
    }

    function HTTP_POST()
    {
        $json = new paloSantoJSON();
        if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== 0) {
            header('HTTP/1.1 415 Unsupported Media Type');
            $json->set_status("ERROR");
            $json->set_error('Please POST standard URL encoding only');
            return $json->createJSON();
        }

        $postkeys = array_keys($_POST);
        if (in_array('asterisk_call', $postkeys)) {
            $_POST['asterisk_call'] = !(empty($_POST['asterisk_call']) || $_POST['asterisk_call'] == 'false' || $_POST['asterisk_call'] == "0");
        }
        foreach (array('startdate', 'enddate', 'subject', 'description',
            'asterisk_call', 'recording', 'call_to', 'reminder_timer', 'color',
            'emails_notification') as $k)
            if (!isset($_POST[$k])) $_POST[$k] = NULL;

        $pCore_Calendar = new core_Calendar();
        $result = $pCore_Calendar->editCalendarEvent($this->_idNumero,
            $_POST['startdate'], $_POST['enddate'], $_POST['subject'],
            $_POST['description'], $_POST['asterisk_call'], $_POST['recording'],
            $_POST['call_to'], $_POST['reminder_timer'], $_POST['color'],
            $_POST['emails_notification']);
        if ($result !== FALSE) {
            header('HTTP/1.1 204 No Content');
            return 'null';
        } else {
            $error = $pCore_Calendar->getError();
            if ($error["fc"] == "DBERROR")
                header('HTTP/1.1 500 Internal Server Error');
            else
                header('HTTP/1.1 400 Bad Request');
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        }
    }

    function HTTP_DELETE()
    {
        $pCore_Calendar = new core_Calendar();
        $json = new paloSantoJSON();
        $result = $pCore_Calendar->delCalendarEvent($this->_idNumero);
        if ($result === FALSE) {
            $error = $pCore_Calendar->getError();
            if($error["fc"] == "DBERROR")
                header("HTTP/1.1 500 Internal Server Error");
            elseif ($error['fc'] == 'CALENDAR')
                header("HTTP/1.1 404 Not Found");
            else
                header("HTTP/1.1 400 Bad Request");
            $json->set_status("ERROR");
            $json->set_error($error);
            return $json->createJSON();
        } else{
            $json = new Services_JSON();
            $response["message"] = "The event was successfully deleted";
            return $json->encode($response);
        }
    }
}