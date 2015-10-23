<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4                                                |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the 'License'); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an 'AS IS'  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
*/

define('ROOT', $_SERVER["DOCUMENT_ROOT"]);
ini_set('include_path', ini_get('include_path').':'.$_SERVER["DOCUMENT_ROOT"]);

require_once("libs/misc.lib.php");
require_once("configs/default.conf.php");
require_once("libs/paloSantoACL.class.php");
require_once("libs/paloSantoDB.class.php");
require_once("modules/calendar/configs/default.conf.php");

$arrConf = array_merge($arrConf,$arrConfModule);

require_once("modules/{$arrConf['module_name']}/libs/paloSantoCalendar.class.php");

$arrConf = array_merge($arrConf,$arrConfModule);

class core_Calendar
{
    /**
     * Description error message
     *
     * @var array
     */
    private $errMsg;

    /**
     * Array that contains a paloDB Object, the key is the DSN of a specific database
     *
     * @var array
     */
    private $_dbCache;

    /**
     * ACL User ID for authenticated user
     *
     * @var integer
     */
    private $_id_user;

    /**
     * Object paloACL
     *
     * @var object
     */
    private $_pACL;

    /**
     * Constructor
     *
     */
    function core_Calendar()
    {
        global $arrConf;

        $this->_dbCache = array();
        $this->_id_user = NULL;
        $this->errMsg   = NULL;
        $this->_pACL    = NULL;

        load_language(ROOT.'/');
        load_language_module($arrConf['module_name'], ROOT.'/');
    }

    /**
     * Static function that creates an array with all the functional points with
     * the parameters IN and OUT
     *
     * @return  array     Array with the definition of the function points.
     */
    public static function getFP()
    {
        return array(
            'listCalendarEvents'    =>    array(
                'params_IN'        =>    array(
                    'startdate'       => array('type' => 'date',     'required' => true),
                    'enddate'         => array('type' => 'date',     'required' => true)
                ),
                'params_OUT'    =>    array(
                    'events'         => array('type' => 'array',   'required' => true, 'minOccurs'=>'0', 'maxOccurs'=>'unbounded',
                        'params' => array(
                            'id'                     => array('type' => 'positiveInteger',  'required' => true),
                            'startdate'              => array('type' => 'date',             'required' => true),
                            'enddate'                => array('type' => 'date',             'required' => true),
                            'starttime'              => array('type' => 'dateTime',         'required' => true),
                            'endtime'                => array('type' => 'dateTime',         'required' => true),
                            'subject'                => array('type' => 'string',           'required' => true),
                            'description'            => array('type' => 'string',           'required' => true),
                            'asterisk_call'          => array('type' => 'boolean',          'required' => true),
                            'recording'              => array('type' => 'string',           'required' => false),
                            'call_to'                => array('type' => 'string',           'required' => false),
                            'reminder_timer'         => array('type' => 'positiveInteger',  'required' => false),
                            'color'                  => array('type' => 'string',           'required' => true),
                            'emails_notification'    => array('type' => 'string',           'required' => true, 'minOccurs'=>'0', 'maxOccurs'=>'unbounded')
                        )
                    )
                ),
            ),
            'addCalendarEvent'    =>    array(
                'params_IN'        =>    array(
                    'startdate'                =>    array('required' => TRUE, 'type' => 'dateTime',),
                    'enddate'                =>    array('required' => TRUE, 'type' => 'dateTime',),
                    'subject'                =>    array('required' => TRUE, 'type' => 'string',),
                    'description'            =>    array('required' => TRUE, 'type' => 'string',),
                    'asterisk_call'            =>    array('required' => TRUE, 'type' => 'boolean',),
                    'recording'                =>    array('required' =>    FALSE,'type' => 'string',),
                    'call_to'                =>    array('required' => FALSE,'type' =>    'string',),
                    'reminder_timer'        =>    array('required' => FALSE,'type' => 'positiveInteger',),
                    'color'                    =>    array('required' => FALSE,'type' => 'string',),
                    'emails_notification'    =>    array('required' => TRUE, 'type' => 'string', 'minOccurs' => '0', 'maxOccurs' => 'unbounded', 
                    ),
                ),
                'params_OUT'    =>    array(
                    'return'    =>    array('required' => TRUE, 'type' =>    'boolean',),
                ),
            ),
            'editCalendarEvent'  =>  array(
                'params_IN'     =>  array(
                    'id'                    =>  array('required' => TRUE, 'type' => 'positiveInteger',),
                    'startdate'             =>  array('required' => FALSE, 'type' => 'dateTime',),
                    'enddate'               =>  array('required' => FALSE, 'type' => 'dateTime',),
                    'subject'               =>  array('required' => FALSE, 'type' => 'string',),
                    'description'           =>  array('required' => FALSE, 'type' => 'string',),
                    'asterisk_call'         =>  array('required' => FALSE, 'type' => 'boolean',),
                    'recording'             =>  array('required' => FALSE,'type' => 'string',),
                    'call_to'               =>  array('required' => FALSE,'type' => 'string',),
                    'reminder_timer'        =>  array('required' => FALSE,'type' => 'positiveInteger',),
                    'color'                 =>  array('required' => FALSE,'type' => 'string',),
                    'emails_notification'   =>  array('required' => FALSE, 'type' => 'string', 'minOccurs' => '0', 'maxOccurs' => 'unbounded', 
                    ),
                ),
                'params_OUT'    =>  array(
                    'return'    =>  array('required' => TRUE, 'type' => 'boolean',),
                ),
            ),
            'delCalendarEvent'    =>    array(
                'params_IN'        =>    array(
                    'id'        =>    array('required' => TRUE, 'type' =>    'positiveInteger',),
                ),
                'params_OUT'    =>    array(
                    'return'    =>    array('required' => TRUE, 'type' => 'boolean',),
                ),
            ),
        );
    }
    
    /**
     * Function that creates, if do not exist in the attribute dbCache, a new paloDB object for the given DSN
     *
     * @param   string   $sDSN   DSN of a specific database
     * @return  object   paloDB object for the entered database
     */
    private function & _getDB($sDSN)
    {
        if (!isset($this->_dbCache[$sDSN])) {
            $this->_dbCache[$sDSN] = new paloDB($sDSN);
        }
        return $this->_dbCache[$sDSN];
    }

    /**
     * Function that creates, if do not exist in the attribute _pACL, a new paloACL object
     *
     * @return  object   paloACL object
     */
    private function & _getACL()
    {
        global $arrConf;

        if (is_null($this->_pACL)) {
            $pDB_acl = $this->_getDB($arrConf['elastix_dsn']['acl']);
            $this->_pACL = new paloACL($pDB_acl);
        }
        return $this->_pACL;
    }

    private function _getCalendar()
    {
        global $arrConf;

        return new paloSantoCalendar(
            $this->_getDB($arrConf['dsn_conn_database']),
            $this->_leerIdUser(), 
            ROOT.'/modules/'.$arrConf['module_name'].'/themes/default',
            $arrConf['dir_outgoing']);
    }

    /**
     * Function that reads the login user ID, that assumed is on $_SERVER['PHP_AUTH_USER']
     *
     * @return  integer   ACL User ID for authenticated user, or NULL if the user in $_SERVER['PHP_AUTH_USER'] does not exist
     */
    private function _leerIdUser()
    {
        if (!is_null($this->_id_user)) return $this->_id_user;

        $pACL = $this->_getACL();        
        $id_user = $pACL->getIdUser($_SERVER['PHP_AUTH_USER']);
        if ($id_user == FALSE) {
            $this->errMsg["fc"] = 'INTERNAL';
            $this->errMsg["fm"] = 'User-ID not found';
            $this->errMsg["fd"] = 'Could not find User-ID in ACL for user '.$_SERVER['PHP_AUTH_USER'];
            $this->errMsg["cn"] = get_class($this);
            return NULL;
        }
        $this->_id_user = $id_user;
        return $id_user;    
    }

    /**
     * Function that verifies if the parameter can be parsed as a date, and returns the canonic value of the date
     * like yyyy-mm-dd in local time.
     *
     * @param   string   $sDateString   string date to be parsed as a date
     * @return  date     parsed date, or NULL if the $sDateString can not be parsed
     */
    private function _checkDateFormat($sDateString)
    {
        $sTimestamp = strtotime($sDateString);
        if ($sTimestamp === FALSE) {
            $this->errMsg["fc"] = 'PARAMERROR';
            $this->errMsg["fm"] = 'Invalid format';
            $this->errMsg["fd"] = 'Unrecognized date format, expected yyyy-mm-dd';
            $this->errMsg["cn"] = get_class($this);
            return NULL;
        }
        return date('Y-m-d', $sTimestamp);
    }

    /**
     * Function that verifies if the parameter can be parsed as a date, and returns the canonic value of the date
     * like yyyy-mm-dd hh:mm:ss in local time.
     *
     * @param   string   $sDateString   string date to be parsed as a date time
     * @return  date     parsed date, or NULL if the $sDateString can not be parsed
     */
    private function _checkDateTimeFormat($sDateString)
    {
        $sTimestamp = strtotime($sDateString);
        if ($sTimestamp === FALSE) {
            $this->errMsg["fc"] = 'PARAMERROR';
            $this->errMsg["fm"] = 'Invalid format';
            $this->errMsg["fd"] = 'Unrecognized date format, expected yyyy-mm-dd hh:mm:ss';
            $this->errMsg["cn"] = get_class($this);
            return NULL;
        }
        return date('Y-m-d H:i:s', $sTimestamp);
    }

    /**
     * Function that verifies if the authenticated user is authorized to the passed module.
     *
     * @param   string   $sModuleName   name of the module to check if the user is authorized
     * @return  boolean    true if the user is authorized, or false if not
     */ 
    private function _checkUserAuthorized($sModuleName)
    {
        $pACL = $this->_getACL();        
        $id_user = $this->_leerIdUser();
        if (!$pACL->isUserAuthorizedById($id_user, "access", $sModuleName)) { 
            $this->errMsg["fc"] = 'UNAUTHORIZED';
            $this->errMsg["fm"] = 'Not authorized for this module: '.$sModuleName;
            $this->errMsg["fd"] = 'Your user login is not authorized for this functionality. Please contact your system administrator.';
            $this->errMsg["cn"] = get_class($this);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function that gets the extension of the login user, that assumed is on $_SERVER['PHP_AUTH_USER']
     *
     * @return  string   extension of the login user, or NULL if the user in $_SERVER['PHP_AUTH_USER'] does not have an extension     *                   assigned
     */
    private function _leerExtension()
    {
        // Identificar el usuario para averiguar el número telefónico origen
        $id_user = $this->_leerIdUser();

        $pACL = $this->_getACL();        
        $user = $pACL->getUsers($id_user);
        if ($user == FALSE) {
            $this->errMsg["fc"] = 'ACL';
            $this->errMsg["fm"] = 'ACL lookup failed';
            $this->errMsg["fd"] = 'Unable to read information from ACL - '.$pACL->errMsg;
            $this->errMsg["cn"] = get_class($pACL);
            return NULL;
        }
        
        // Verificar si tiene una extensión
        $extension = $user[0][3];
        if ($extension == "") {
            $this->errMsg["fc"] = 'EXTENSION';
            $this->errMsg["fm"] = 'Extension lookup failed';
            $this->errMsg["fd"] = 'No extension has been set for user '.$_SERVER['PHP_AUTH_USER'];
            $this->errMsg["cn"] = get_class($pACL);
            return NULL;
        }

        return $extension;        
    }

    /**
     * Functional point that returns the calendar events for the authenticated user
     *
     * @param   date    $startdate         Starting date event
     * @param   date    $enddate           Ending date event
     * @return  array   Array of contacts with the following information:
     *                      id (positiveInteger) in database ID of the event
     *                      startdate (date) Event Start Date
     *                      enddate (date) Date of end of event
     *                      starttime (datetime) Start date and time
     *                      endtime (datetime) final time
     *                      subject (string) Subject Event
     *                      description (string) Long Description of event
     *                      asterisk_call (bool) TRUE if must be generated reminder call
     *                      Recording (string, optional) Name of the recording used to call.
     *                      call_to (string, optional) Extent to which call for Reminder
     *                      reminder_timer (string, optional) number of minutes before which will make the call reminder
     *                      emails_notification (array (string)) Zero or more emails will be notified with a message when creating the
     *                                                           event.
     *                   or false if an error exists
     */
    function listCalendarEvents($startdate = NULL, $enddate = NULL,
        $id_event = NULL, $bRawObjects = FALSE)
    {
        if (!$this->_checkUserAuthorized('calendar')) return false;

        // Validación de fechas
        $sFechaInicio = $sFechaFinal = NULL;
        if (is_null($id_event)) {
            if (isset($startdate)) {
                $sFechaInicio = $this->_checkDateFormat($startdate);
                if (is_null($sFechaInicio)) return false;
            }
            if (isset($enddate)) {
                $sFechaFinal  = $this->_checkDateFormat($enddate);
                if (is_null($sFechaFinal)) return false;
            }
            if (!is_null($sFechaInicio) && !is_null($sFechaFinal)) {
                if ($sFechaFinal < $sFechaInicio) {
                    $t = $sFechaFinal; $sFechaFinal = $sFechaInicio; $sFechaInicio = $t;
                }
            }
        }

        try {
            $pCalendar = $this->_getCalendar();
    
            // Elegir manera de leer en base a presencia de $id_event
            if (is_null($id_event)) {
                $r = $pCalendar->leerEventosActivosIntervalo($sFechaInicio, $sFechaFinal);
            } else {
                $r = $pCalendar->leerEvento($id_event);
                $r = is_null($r) ? array() : array($r);
            }

            if ($bRawObjects) return $r;
            
            $events = array();
            foreach ($r as $event) {
                $events[] = array(
                    'id'            =>  $event->id,
    
                    // Las siguientes 4 son fechas
                    'startdate'     =>  $event->date_start,
                    'enddate'       =>  $event->date_end,
                    'starttime'     =>  $event->datetime_start,
                    'endtime'       =>  $event->datetime_end,
    
                    'subject'       =>  $event->title,
                    'description'   =>  $event->description,
                    'asterisk_call' =>  (!is_null($event->reminder_callnum)),
                    
                    // Los siguientes 3 campos dependen de asterisk_call
                    'recording'     =>  (!is_null($event->reminder_callnum)) ? $event->reminder_tts : NULL,
                    'call_to'       =>  (!is_null($event->reminder_callnum)) ? $event->reminder_callnum : NULL,
                    'reminder_timer' => (!is_null($event->reminder_callnum)) ? $event->reminder_minutes : NULL,
                    
                    'emails_notification' => $event->notify_emails,
                    'color'         =>  $event->event_color,
                );
            }
            return array('events' => $events);
        } catch (InvalidCalendarPropertyException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'PARAMERROR';
                $this->errMsg["fm"] = 'Server configuration error';
                $this->errMsg["fd"] = 'Configuration error - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (FailedEventReadException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'DBERROR';
                $this->errMsg["fm"] = 'Database operation failed';
                $this->errMsg["fd"] = 'Unable to read data from calendar - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        }
    }

    /**
     * Functional point that adds a new event in the calendar of the authenticated user
     *
     * @param   date      $startdate                 Starting date and time of event
     * @param   date      $enddate                   Ending date and time of event
     * @param   string    $subject                   Subject of event
     * @param   string    $description               Long description of event
     * @param   boolean   $asterisk_call             TRUE if must be generated reminder call
     * @param   string    $recording                 (Optional)  Name of the recording used to call
     * @param   string    $call_to                   (Optional) Extension to which call for Reminder
     * @param   string    $reminder_timer            (Optional) Number of minutes before which will make the call reminder
     * @param   array     $emails_notification       Zero or more emails will be notified with a message when creating the event.
     * @param   string    $color                     (Optional) Color for the event
     * 
     * @return  boolean   True if the event was successfully created, or false if an error exists
     */
    function addCalendarEvent($startdate, $enddate, $subject, $description,
        $asterisk_call, $recording, $call_to, $reminder_timer,
        $color, $emails_notification, $getIdInserted = FALSE)
    {
        if (!$this->_checkUserAuthorized('calendar')) return false;

        // Validación de instantes de inicio y final
        $sFechaInicio = $this->_checkDateTimeFormat(isset($startdate) ? $startdate : NULL);
        $sFechaFinal  = $this->_checkDateTimeFormat(isset($enddate) ? $enddate : NULL);
        if (is_null($sFechaInicio) || is_null($sFechaFinal)) return false;
        if ($sFechaFinal < $sFechaInicio) {
            $t = $sFechaFinal; $sFechaFinal = $sFechaInicio; $sFechaInicio = $t;
        }

        // Verificar presencia de asunto y descripción
        if (!isset($subject) || trim($subject) == '') {
            $this->errMsg["fc"] = 'PARAMERROR';
            $this->errMsg["fm"] = 'Invalid subject';
            $this->errMsg["fd"] = 'Subject must be specified and nonempty';
            $this->errMsg["cn"] = get_class($this);
            return false;
        }
        if (!isset($description) || trim($description) == '') {
            $description = '';
        }

        // Validaciones dependientes de asterisk_call
        if (!isset($asterisk_call) || 
            ($asterisk_call !== TRUE && $asterisk_call !== FALSE)) {
            $this->errMsg["fc"] = 'PARAMERROR';
            $this->errMsg["fm"] = 'Invalid reminder flag';
            $this->errMsg["fd"] = 'Reminder flag must be specified and be a boolean';
            $this->errMsg["cn"] = get_class($this);
            return false;
        }

        if (!isset($emails_notification)) $emails_notification = array();
        if (!is_array($emails_notification)) $emails_notification = array($emails_notification);

        try {
            $pCalendar = $this->_getCalendar();
            $event = $pCalendar->nuevoEvento();
            
            $event->title = trim($subject);
            $event->description = trim($description);
            $event->event_type = EVENTO_UNICO;
            $event->datetime_start = $sFechaInicio;
            $event->datetime_end = $sFechaFinal;
            if (!is_null($color)&& trim($color) != '') $event->event_color = $color;
            $event->notify_emails = $emails_notification;
            if ($asterisk_call) {
                $event->asignarRecordatorio(
                    (is_null($call_to) || trim($call_to) == '') ? $this->_leerExtension() : trim($call_to),
                    $recording,
                    $reminder_timer);
            } else {
                $event->quitarRecordatorio();
            }
            
            $event->guardarEvento();
            return $getIdInserted ? $event->id : TRUE;
        } catch (InvalidCalendarPropertyException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'PARAMERROR';
                $this->errMsg["fm"] = 'Invalid property';
                $this->errMsg["fd"] = 'Unable to set property - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (InvalidCalendarReminderException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'PARAMERROR';
                $this->errMsg["fm"] = 'Invalid reminder';
                $this->errMsg["fd"] = 'Unable to set reminder - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (FailedEventUpdateException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'DBERROR';
                $this->errMsg["fm"] = 'Database operation failed';
                $this->errMsg["fd"] = 'Unable to create event in calendar - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($pCalendar);
            }
            return false;
        }
    }

    function editCalendarEvent($id, $startdate = NULL, $enddate = NULL, 
        $subject = NULL, $description = NULL, $asterisk_call = NULL, 
        $recording = NULL, $call_to = NULL, $reminder_timer = NULL,
        $color = NULL, $emails_notification = NULL)
    {
        if (!$this->_checkUserAuthorized('calendar')) return false;

        // Verificar presencia de ID del evento
        if (!isset($id) || !preg_match('/^\d+$/', $id)) {
            $this->errMsg["fc"] = 'PARAMERROR';
            $this->errMsg["fm"] = 'Invalid ID';
            $this->errMsg["fd"] = 'Event ID must be nonnegative integer';
            $this->errMsg["cn"] = get_class($this);
            return false;
        }
        $id = (int)$id;

        try {
            $pCalendar = $this->_getCalendar();
            $event = $pCalendar->leerEvento($id);
            if (is_null($event)) {
                $this->errMsg["fc"] = 'CALENDAR';
                $this->errMsg["fm"] = 'Event lookup failed';
                $this->errMsg["fd"] = 'No event was found for user '.$_SERVER['PHP_AUTH_USER'];
                $this->errMsg["cn"] = get_class($pCalendar);
                return false;
            }
            
            // Validación de instantes de inicio y final
            $sFechaInicio = NULL; $sFechaFinal = NULL;
            if (!is_null($startdate)) {
                $sFechaInicio = $this->_checkDateTimeFormat($startdate);
                if (is_null($sFechaInicio)) return false;
                $event->datetime_start = $sFechaInicio;
            }
            if (!is_null($enddate)) {
                $sFechaFinal = $this->_checkDateTimeFormat($enddate);
                if (is_null($sFechaFinal)) return false;
                $event->datetime_end = $sFechaFinal;
            }
            if ($event->timestamp_end < $event->timestamp_start) {
                $t = $event->timestamp_end;
                $event->timestamp_end = $event->timestamp_start;
                $event->timestamp_start = $t;
            }

            if (!is_null($subject)) $event->title = trim($subject);
            if (!is_null($description)) $event->description = trim($description);
            if (!is_null($color)&& trim($color) != '') $event->event_color = $color;
            if (!is_null($emails_notification)) $event->notify_emails = $emails_notification;
            if (!is_null($asterisk_call)) {
                if ($asterisk_call) {
                    if (is_null($call_to)) $call_to = $event->reminder_callnum;
                    if (is_null($recording)) $recording = $event->reminder_tts;
                    if (is_null($reminder_timer)) $reminder_timer = $event->reminder_minutes;
                    $event->asignarRecordatorio(
                        (is_null($call_to) || trim($call_to) == '') ? $this->_leerExtension() : trim($call_to),
                        $recording,
                        $reminder_timer);
                } else {
                    $event->quitarRecordatorio();
                }
            }
            
            $event->guardarEvento();
            return TRUE;
        } catch (FailedEventReadException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'DBERROR';
                $this->errMsg["fm"] = 'Database operation failed';
                $this->errMsg["fd"] = 'Unable to read data from calendar - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (InvalidCalendarPropertyException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'PARAMERROR';
                $this->errMsg["fm"] = 'Invalid property';
                $this->errMsg["fd"] = 'Unable to set property - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (InvalidCalendarReminderException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'PARAMERROR';
                $this->errMsg["fm"] = 'Invalid reminder';
                $this->errMsg["fd"] = 'Unable to set reminder - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (FailedEventUpdateException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'DBERROR';
                $this->errMsg["fm"] = 'Database operation failed';
                $this->errMsg["fd"] = 'Unable to update event in calendar - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($pCalendar);
            }
            return false;
        }
    }

    /**
     * Functional point that deletes an existing event in the calendar of the authenticated user
     *
     * @param   integer      $id        ID of the event to be deleted
     * @return  boolean      True if the event was successfully deleted, or false if an error exists
     */
    function delCalendarEvent($id)
    {
        if (!$this->_checkUserAuthorized('calendar')) return false;

        // Verificar presencia de ID del evento
        if (!isset($id) || !preg_match('/^\d+$/', $id)) {
            $this->errMsg["fc"] = 'PARAMERROR';
            $this->errMsg["fm"] = 'Invalid ID';
            $this->errMsg["fd"] = 'Event ID must be nonnegative integer';
            $this->errMsg["cn"] = get_class($this);
            return false;
        }
        $id = (int)$id;

        try {
            $pCalendar = $this->_getCalendar();
            $event = $pCalendar->leerEvento($id);
            if (is_null($event)) {
                $this->errMsg["fc"] = 'CALENDAR';
                $this->errMsg["fm"] = 'Event lookup failed';
                $this->errMsg["fd"] = 'No event was found for user '.$_SERVER['PHP_AUTH_USER'];
                $this->errMsg["cn"] = get_class($pCalendar);
                return false;
            }
            
            $event->borrarEvento();
            return TRUE;
        } catch (FailedEventReadException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'DBERROR';
                $this->errMsg["fm"] = 'Database operation failed';
                $this->errMsg["fd"] = 'Unable to read data from calendar - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($this);
            }
            return false;
        } catch (FailedEventUpdateException $e) {
            if (is_null($this->errMsg)) {
                $this->errMsg["fc"] = 'DBERROR';
                $this->errMsg["fm"] = 'Database operation failed';
                $this->errMsg["fd"] = 'Unable to delete event in calendar - '.$e->getMessage();
                $this->errMsg["cn"] = get_class($pCalendar);
            }
            return false;
        }
    }

    /**
     * 
     * Function that returns the error message
     *
     * @return  string   Message error if had an error.
     */
    public function getError()
    {
        return $this->errMsg;
    }
}
?>