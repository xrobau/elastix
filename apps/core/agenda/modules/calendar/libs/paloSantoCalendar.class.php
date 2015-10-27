<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-7                                               |
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

define ('EVENTO_UNICO', 1);     // Evento se realiza una sola vez
define ('EVENTO_SEMANAL', 5);   // Evento se repite semanalmente
define ('EVENTO_MENSUAL', 6);   // Evento se repite mensualmente

define ('COLOR_EVENTO_OMISION', '#3366CC');  // Color a usar para mostrar el recordatorio
define ('CALENDAR_DATE_FORMAT', 'Y-m-dP');
define ('CALENDAR_DATETIME_FORMAT', 'Y-m-d\TH:i:sP');
define ('PALOCALENDAR_EMAIL_REGEXP', '/^("?([^"]*)"?\s*)?<?([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,4})+)>?$/');

class InvalidCalendarReminderException extends Exception {}
class InvalidCalendarPropertyException extends Exception {}
class FailedEventUpdateException extends Exception {}
class FailedEventReadException extends Exception {}


class paloSantoCalendar
{
    private $_DB;
    var $errMsg;
    private $_userid;
    private $_tpldir;
    private $_calldir;

    /**
     * Constructor del objeto fábrica de eventos de calendario. Se requiere,
     * además de una conexión a la base de datos calendar.db, el ID de usuario
     * para el cual se están manipulando eventos de calendario.
     *
     * @param   object  $pDB    Objeto paloDB o cadena de conexión
     * @param   int     $uid    ID del usuario para el cual consultar eventos
     * @param    string    $tpldir    Ruta a plantillas para correo y para iCal
     *
     * @throws    InvalidCalendarPropertyException
     */
    function __construct($pDB, $uid, $tpldir, $calldir)
    {
        if (is_null($uid) || $uid == 0) {
            throw new InvalidCalendarPropertyException(_tr('Invalid user ID'));
        }
        if (!is_dir($tpldir)) {
            throw new InvalidCalendarPropertyException(_tr('Invalid template directory'));
        }
        if (!is_dir($calldir)) {
            throw new InvalidCalendarPropertyException(_tr('Invalid callfile directory'));
        }
        $this->_userid = (int)$uid;
        $this->_tpldir = $tpldir;
        $this->_calldir = $calldir;

        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
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
     * Construir un nuevo evento que hereda el ID de usuario y la conexión DB
     *
     * @return object paloSantoCalendarEvent
     * @throws FailedEventReadException
     */
    function nuevoEvento()
    {
        return new paloSantoCalendarEvent($this->_DB, $this->_userid, $this->_tpldir, $this->_calldir);
    }

    /**
     * Procedimiento para leer un evento, dado su ID.
     *
     * @return object paloSantoCalendarEvent
     * @throws FailedEventReadException
     */
    function leerEvento($id)
    {
        $sql = 'SELECT * FROM events WHERE uid = ? AND id = ?';
        $paramSQL = array($this->_userid, $id);
        $recordset = $this->_DB->fetchTable($sql, TRUE, $paramSQL);
        if (!is_array($recordset)) {
            /* Aquí lo mejor sería dejar propagar la excepción PDO, pero paloDB
             * se la traga, así que se crea una nueva. */
            throw new FailedEventReadException($this->_DB->errMsg);
        }

        $ev = $this->_construirEventos($recordset);
        return (count($ev) > 0) ? $ev[0] : NULL;
    }

    /**
     * Procedimiento para leer todos los eventos del usuario asociado que estén
     * activos en el intervalo semicerrado [$sFechaInicial, $sFechaFinal)
     * indicado como cadenas yyyy-mm-dd hh:mm:ss. Este método también devolverá
     * registros para los eventos que se encuentren parcialmente fuera del
     * intervalo. Este comportamiento es adecuado para la implementación del
     * visor de calendario del GUI de Elastix.
     *
     * @param    string    $sFechaInicial    Fecha inicial del intervalo
     * @param    string    $sFechaFinal    Fecha final del intervalo (opcional)
     *
     * @return    array    Arreglo de objetos paloSantoCalendarEvent
     * @throws FailedEventReadException
     */
    function leerEventosActivosIntervalo($sFechaInicial = NULL, $sFechaFinal = NULL)
    {
        $sql = 'SELECT * FROM events WHERE uid = ?';
        $paramSQL = array($this->_userid);
        if (!is_null($sFechaInicial)) {
            $sql .= ' AND endtime >= ?';
            $paramSQL[] = $sFechaInicial;
        }
        if (!is_null($sFechaFinal)) {
            $sql .= ' AND starttime < ?';
            $paramSQL[] = $sFechaFinal;
        }
        $sql .= ' ORDER BY starttime';
        $recordset = $this->_DB->fetchTable($sql, TRUE, $paramSQL);
        if (!is_array($recordset)) {
            /* Aquí lo mejor sería dejar propagar la excepción PDO, pero paloDB
             * se la traga, así que se crea una nueva. */
            throw new FailedEventReadException($this->_DB->errMsg);
        }

        return $this->_construirEventos($recordset);
    }

    private function _construirEventos(&$recordset)
    {
        $objlist = array();
        foreach ($recordset as $tupla) {
            $objlist[] = new paloSantoCalendarEvent($this->_DB, $this->_userid,
                $this->_tpldir, $this->_calldir, $tupla);
        }
        return $objlist;
    }
}

class paloSantoCalendarEvent
{
    private $_DB;
    var $errMsg = NULL;

    // Las siguientes son variables de la base de datos
    /*
    id;            // ID en la base de datos del evento, o NULL si no se ha guardado
    uid;           // ID del usuario para el cual se ha creado el evento
    starttime;     // Fecha de inicio del evento de calendario, yyyy-mm-dd hh:mm
    // startdate se deriva de starttime
    endtime;       // Fecha de final del evento de calendario, yyyy-mm-dd hh:mm
    // enddate se deriva de endtime
    eventtype;     // Tipo de regularidad de evento, véase EVENTO_*
    subject;       // Nombre del evento
    description;   // Descripción del evento

    asterisk_call; // booleano, se guarda como 'on' u 'off'
    recording;     // Texto usado para sintetizar mensaje de recordatorio, o cadena vacía
    call_to;       // Número al cual enviar la llamada de recordatorio
    reminderTimer;     // Minutos antes del evento en que hay que llamar

    notification;  // booleano, se guarda como 'on' u 'off'
    emails_notification;   // Lista de correos a notificar

    each_repeat = 1;   // TODO: averiguar qué hace
    days_repeat;       // TODO: averiguar qué hace (lista "Sa," para Saturday)
    color = COLOR_EVENTO_OMISION; // Color a usar para mostrar el recordatorio
    */
    private $_id = NULL;
    private $_userid = NULL;
    private $_timestamp_start;  // timestamp de unix, entero
    private $_timestamp_end;    // timestamp de unix, entero
    private $_title = NULL;
    private $_description = '';
    private $_event_color = COLOR_EVENTO_OMISION;
    private $_event_type = EVENTO_UNICO;

    /* Los siguientes 3 campos definen el recordatorio. Deben de setearse todos
     * a NULL, o todos con valores válidos. */
    private $_reminder_callnum = NULL;
    private $_reminder_tts = NULL;
    private $_reminder_minutes = NULL;

    private $_notify_emails = array();

    private $_tpldir = NULL;
    private $_calldir = NULL;

    /**
     * Constructor de un evento de calendario. Este constructor sólo debería ser
     * invocado desde los métodos de la clase paloSantoCalendar. El valor de
     * $dbcampos se asume que es una tupla con los valores de las columnas de
     * la tabla events de calendar.db .
     *
     * @throws InvalidCalendarPropertyException
     */
    function __construct($pDB, $userid, $tpldir, $calldir, $dbcampos = NULL)
    {
        $this->_DB =& $pDB;
        $this->_timestamp_start = time();
        $this->_timestamp_end = time();

        if (is_null($userid) || $userid == 0) {
            throw new InvalidCalendarPropertyException(_tr('Invalid user ID'));
        }
        if (!is_dir($tpldir)) {
            throw new InvalidCalendarPropertyException(_tr('Invalid template directory'));
        }
        if (!is_dir($calldir)) {
            throw new InvalidCalendarPropertyException(_tr('Invalid callfile directory'));
        }
        $this->_userid = (int)$userid;
        $this->_tpldir = $tpldir;
        $this->_calldir = $calldir;

        if (is_null($dbcampos)) return;

        // Asignar los valores desde la tupla de la base de datos
        $this->_id = (int)$dbcampos['id'];
        $this->_userid = (int)$dbcampos['uid'];
        $this->_timestamp_start = strtotime($dbcampos['starttime']);
        $this->_timestamp_end = strtotime($dbcampos['endtime']);
        $this->_title = $dbcampos['subject'];
        $this->_description = $dbcampos['description'];
        $this->_event_color = $dbcampos['color'];
        $this->_event_type = (int)$dbcampos['eventtype'];
        if (!in_array($this->_event_type, array(EVENTO_UNICO, EVENTO_SEMANAL, EVENTO_MENSUAL)))
            $this->_event_type = EVENTO_UNICO;
        if ($dbcampos['asterisk_call'] == 'on') {
            $this->_reminder_callnum = $dbcampos['call_to'];
            $this->_reminder_tts = $dbcampos['recording'];
            $this->_reminder_minutes = (int)$dbcampos['reminderTimer'];
        }
        if ($dbcampos['notification'] == 'on') {
            $this->_notify_emails = array_filter(
                array_map('trim', explode(',', $dbcampos['emails_notification'])),
                array($this, '_rechazar_correo_vacio'));
        }
    }

    private function _rechazar_correo_vacio($email)
    {
        return (0 != preg_match(PALOCALENDAR_EMAIL_REGEXP, trim($email)));
    }

    public function __get($s)
    {
        // Campos base
        if (in_array($s, array(
            'id', 'userid', 'timestamp_start', 'timestamp_end',
            'title', 'description', 'event_color', 'event_type',
            'reminder_callnum', 'reminder_tts', 'reminder_minutes',
            'notify_emails'))) {
            $s = '_'.$s;
            return $this->$s;
        }

        // Campos derivados para transformaciones
        if (in_array($s, array('datetime_start', 'datetime_end'))) {
            $s = str_replace('datetime_', '_timestamp_', $s);
            return date(CALENDAR_DATETIME_FORMAT, $this->$s);
        }
        if (in_array($s, array('date_start', 'date_end'))) {
            $s = str_replace('date_', '_timestamp_', $s);
            return date(CALENDAR_DATE_FORMAT, $this->$s);
        }
        if (in_array($s, array('icalstart', 'icalend'))) {
            $s = str_replace('ical', '_timestamp_', $s);
            return gmdate('Ymd\THis\Z', $this->$s);
        }

        die(__METHOD__.' - propiedad no implementada: '.$s."\n");
    }

    /**
     * Implementación de asignación de varias propiedades del evento
     *
     * @throws InvalidCalendarPropertyException
     */
    public function __set($s, $v)
    {
        switch ($s) {
        case 'timestamp_start':     $this->_timestamp_start = (int)$v; break;
        case 'timestamp_end':       $this->_timestamp_end = (int)$v; break;
        case 'datetime_start':
        case 'datetime_end':
            $k = str_replace('datetime_', '_timestamp_', $s);
            $v = strtotime($v);
            if ($v === FALSE)
                throw new InvalidCalendarPropertyException(_tr('Unrecognized date format'));
            $this->$k = $v;
            break;
        case 'title':
            if (trim("$v") == '')
                throw new InvalidCalendarPropertyException(_tr('Subject must be specified and nonempty'));
            $this->_title = trim("$v");
            break;
        case 'description':
            $this->_description = trim("$v");
            break;
        case 'event_color':
            if (!preg_match('/^#[[:xdigit:]]{6}$/', $v))
                throw new InvalidCalendarPropertyException(_tr('Invalid CSS color, expected #rrggbb'));
            $this->_event_color = strtoupper($v);
            break;
        case 'event_type':
            if (!in_array($this->_event_type, array(EVENTO_UNICO, EVENTO_SEMANAL, EVENTO_MENSUAL)))
                throw new InvalidCalendarPropertyException(_tr('Invalid event type, expected oneshot/weekly/monthly'));
            $this->_event_type = (int)$v;
            break;
        case 'notify_emails':
            if (!is_array($v))
                throw new InvalidCalendarPropertyException(_tr('Invalid email list, expected array'));
            $email_list = array();
            foreach ($v as $email) {
               if (!preg_match(PALOCALENDAR_EMAIL_REGEXP, $email))
                   throw new InvalidCalendarPropertyException(_tr('Invalid email in list'));
                $regs = NULL;
                if (preg_match('/^\s*"?([^"]*)"?\s*<(\S*)>\s*$/', $email, $regs)) {
                    $sNombre = '';
                    if (trim($regs[1]) != '') $sNombre = '"'.trim($regs[1]).'" ';
                    $email_list[] = "$sNombre<{$regs[2]}>";
                } else {
                    $email_list[] = "<$email>";
                }
            }
            $this->_notify_emails = $email_list;
            break;
        default:
            die(__METHOD__.' - propiedad no implementada: '.$s."\n");
        }
    }

    /**
     * Método para quitar el recordatorio de llamada, antes de guardar cambios.
     * El método NO QUITA archivos de llamada existentes producto de
     * recordatorios definidos anteriormente. La actualización del estado de los
     * archivos de llamada se realizará al momento de guardar el estado del
     * evento.
     *
     * @return void
     */
    function quitarRecordatorio()
    {
        $this->_reminder_callnum = NULL;
        $this->_reminder_tts = NULL;
        $this->_reminder_minutes = NULL;
    }

    /**
     * Método para asignar el recordatorio de llamada, antes de guardar cambios.
     * El método NO CREA archivos de llamada nuevos al momento de ser invocado.
     * La actualización del estado de los archivos de llamada se realizará al
     * momento de guardar el estado del evento.
     *
     * @param   string  $callnum    Número que se debe marcar para notificar
     * @param   string  $tts        Texto a usar para Text2Speech con Festival
     * @param   int     $minutes    Número de minutos antes de evento para recordatorio
     *
     * @return  void
     * @throws  InvalidCalendarReminderException
     */
    function asignarRecordatorio($callnum, $tts, $minutes)
    {
        // Número a marcar debe ser no-vacío y consistir sólo de dígitos
        if (!preg_match('/^\d+$/', $callnum))
            throw new InvalidCalendarReminderException(_tr('Invalid extension to call for reminder'));

        // Texto a generar no debe contener saltos de línea
        if (FALSE !== strpbrk($tts, "\r\n"))
            throw new InvalidCalendarReminderException(_tr('Reminder text may not have newlines'));

        $minutes = (int)$minutes;
        if ($minutes <= 0)
            throw new InvalidCalendarReminderException(_tr('Invalid interval for remainder timer'));

        $this->_reminder_callnum = "$callnum";
        $this->_reminder_tts = "$tts";
        $this->_reminder_minutes = $minutes;
    }

    /**
     * Método que ejecuta el guardado del evento en la base de datos, creando o
     * actualizando el registro en caso necesario, y ejecutando la creación o
     * borrado de archivos de llamada, además del envío de correo.
     *
     * @return void
     * @throws FailedEventUpdateException
     */
    function guardarEvento()
    {
        // Establecer orden correcto de fechas
        if ($this->_timestamp_start > $this->_timestamp_end) {
            $t = $this->_timestamp_start;
            $this->_timestamp_start = $this->_timestamp_end;
            $this->_timestamp_end = $t;
        }

        // Elegir SQL correcto para insertar o actualizar evento
        $paramSQL = array(
            $this->_userid,
            date('Y-m-d', $this->_timestamp_start),
            date('Y-m-d', $this->_timestamp_end),
            date('Y-m-d H:i', $this->_timestamp_start),
            $this->_event_type,
            $this->_title,
            $this->_description,
            (is_null($this->_reminder_callnum) ? 'off' : 'on'),
            (is_null($this->_reminder_tts) ? '' : $this->_reminder_tts),
            (is_null($this->_reminder_callnum) ? '' : $this->_reminder_callnum),
            (count($this->_notify_emails) > 0 ? 'on' : 'off'),
            (count($this->_notify_emails) > 0 ? implode(', ', $this->_notify_emails).', ' : ''),
            date('Y-m-d H:i', $this->_timestamp_end),
            1,                              // 1 es audio de una sola vez
            substr(date('D', $this->_timestamp_start), 0, 2).',', // Primeras 2 letras de día semana, con coma
            (is_null($this->_reminder_minutes) ? '' : $this->_reminder_minutes),
            $this->_event_color,
        );
        if (is_null($this->_id)) {
            $sMailEvento = 'CREATE';
            $sql = <<<SQL_INSERT_EVENT
INSERT INTO events (uid, startdate, enddate, starttime, eventtype, subject,
    description, asterisk_call, recording, call_to, notification,
    emails_notification, endtime, each_repeat, days_repeat, reminderTimer, color)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL_INSERT_EVENT;
        } else {
            $sMailEvento = 'UPDATE';
            $paramSQL[] = $this->_id;
            $sql = <<<SQL_UPDATE_EVENT
UPDATE events SET uid = ?, startdate = ?, enddate = ?, starttime = ?, eventtype = ?,
    subject = ?, description = ?, asterisk_call = ?, recording = ?, call_to = ?,
    notification = ?, emails_notification = ?, endtime = ?, each_repeat = ?,
    days_repeat = ?, reminderTimer = ?, color = ?
WHERE id = ?
SQL_UPDATE_EVENT;
        }
        $r = $this->_DB->genQuery($sql, $paramSQL);
        if ($r === FALSE) {
            /* Aquí lo mejor sería dejar propagar la excepción PDO, pero paloDB
             * se la traga, así que se crea una nueva. */
            throw new FailedEventUpdateException($this->_DB->errMsg);
        }
        if (is_null($this->_id)) $this->_id = $this->_DB->getLastInsertId();

        // Archivos de llamada de evento
        $this->_borrarArchivosLlamadaEvento();
        $this->_crearArchivosLlamadaEventoPeriodico();

        // Enviar correo de notificación CREATE/UPDATE
        $this->_enviarCorreosNotificacionEvento($sMailEvento);
    }

    /**
     * Método que ejecuta el borrado del evento previamente guardado. Este método
     * también se encarga de borrar todos los archivos de llamadas de notificación
     * pendientes, y también de enviar los correos que notifican que el evento
     * ha sido borrado.
     *
     * @throws FailedEventUpdateException
     */
    function borrarEvento()
    {
        if (is_null($this->_id)) return;

        // Enviar correo de notificación DELETE
        $this->_enviarCorreosNotificacionEvento('DELETE');

        $this->_borrarArchivosLlamadaEvento();
        $r = $this->_DB->genQuery('DELETE FROM events WHERE id = ?', array($this->_id));
        if ($r === FALSE) {
            /* Aquí lo mejor sería dejar propagar la excepción PDO, pero paloDB
             * se la traga, así que se crea una nueva. */
            throw new FailedEventUpdateException($this->_DB->errMsg);
        }
        $this->_id = NULL;
    }

    // Procedimiento para borrar todos los archivos de llamada pendientes
    private function _borrarArchivosLlamadaEvento()
    {
        array_map(
            'unlink',
            glob($this->_calldir."/event_{$this->_id}_*.call"));
    }

    // Procedimiento para crear los archivos de llamada con la periodicidad requerida
    // TODO: extraer el bucle de periodicidad como un método público
    private function _crearArchivosLlamadaEventoPeriodico()
    {
        if (is_null($this->_reminder_callnum)) return;

        $horasNotificacion = $this->generarIntervalosEventoPeriodico();
        $timestampHoy = time();
        foreach ($horasNotificacion as $i => $t) {
            $timestampLlamada = $t[0] - 60 * $this->_reminder_minutes;
            if ($timestampLlamada > $timestampHoy) {
                $this->_crearArchivoLlamadaEvento($i, _tr('Calendar Event'), 2,
                    $timestampLlamada);
            }
        }
    }

    /**
     * Procedimiento para generar el conjuntos de intervalos diarios para un
     * evento semanal o mensual. Si el evento es único, se devuelve una lista de
     * un solo elemento. Para eventos semanales o mensuales, se calcula la lista
     * de intervalos apropiados dentro de un solo día.
     *
     * @return    array    Lista de intervalos, como tuplas de 2 timestamp UNIX.
     */
    function generarIntervalosEventoPeriodico()
    {
        $horasNotificacion = array();
        if ($this->_event_type == EVENTO_UNICO) {
            $horasNotificacion[] = array($this->_timestamp_start, $this->_timestamp_end);
        } else {
            // Calcular diferencia entre porciones de hora para timestamps inicio y final
            $intervaloDiario =
                ($this->_timestamp_end - strtotime(date('Y-m-d', $this->_timestamp_end)))
                - ($this->_timestamp_start - strtotime(date('Y-m-d', $this->_timestamp_start)));
            if ($intervaloDiario < 0) $intervaloDiario = 0;

            switch ($this->_event_type) {
            case EVENTO_SEMANAL:
                $strtotime_increment = '+ 1 week';
                break;
            case EVENTO_MENSUAL:
                $strtotime_increment = '+ 1 month';
                break;
            }
            $t = $this->_timestamp_start;
            while ($t <= $this->_timestamp_end) {
                $horasNotificacion[] = array($t, $t + $intervaloDiario);

                $t = strtotime(date('Y-m-d H:i:s', $t).$strtotime_increment);
            }
        }

        return $horasNotificacion;
    }

    /**
     * Procedimiento para crear el archivo de llamada para un nuevo evento. El
     * archivo de llamada se crea con la fecha de creación y modificación
     * adecuados para que Asterisk realice la llamada en el momento requerido.
     *
     * @param   integer    $sCallerID              Texto para CallerID
     * @param   integer    $iRetries               Máximo número de reintentos
     * @param   date       $iCallTimestamp         Timestamp UNIX de llamada
     */
    private function _crearArchivoLlamadaEvento($idx, $sCallerID, $iRetries, $iCallTimestamp)
    {
        $sContenido = <<<CONTENIDO_ARCHIVO_AUDIO
Channel: Local/{$this->_reminder_callnum}@from-internal
CallerID: $sCallerID
MaxRetries: $iRetries
RetryTime: 60
WaitTime: 30
Application: Festival
Data: {$this->_reminder_tts}
Set: TTS={$this->_reminder_tts}
CONTENIDO_ARCHIVO_AUDIO;
        $sNombreTemp = tempnam('/tmp', 'callfile_');
        $r = file_put_contents($sNombreTemp, $sContenido);
        if ($r === FALSE) {
            throw new FailedEventUpdateException(_tr('Unable to create callfile for event in calendar'));
        }
        touch($sNombreTemp, $iCallTimestamp, $iCallTimestamp);

        // La función rename() de PHP no preserva atime o mtime. Grrrr...
        $sRutaArchivo = $this->_calldir."/event_{$this->_id}_{$idx}.call";
        system("mv $sNombreTemp $sRutaArchivo");
    }

    // Ejecutar el envío del correo de notificación del evento. El valor de
    // $sMailEvento puede ser CREATE|UPDATE|DELETE
    private function _enviarCorreosNotificacionEvento($sMailEvento)
    {
        $map_tipoEvento = array(
            'CREATE'    =>    _tr('New_Event'),
            'UPDATE'    =>    _tr('Change_Event'),
            'DELETE'    =>    _tr('Delete_Event'),
        );
        if (count($this->_notify_emails) <= 0) return;

        require_once 'PHPMailer/class.phpmailer.php';

        $sNombreUsuario = $this->_leerNombreUsuario();
        $sHostname = trim(file_get_contents("/proc/sys/kernel/hostname")); // TODO: mejorar petición de nombre de host
        $sRemitente = 'noreply@'.$sHostname;
        $sTema = _tr($map_tipoEvento[$sMailEvento]);
        $sContenidoCorreo = $this->_generarContenidoCorreoEvento($sTema, $sMailEvento, $sNombreUsuario);
        $sContenidoIcal = $this->_generarContenidoIcal();

        $oMail = new PHPMailer();
        $oMail->CharSet = 'UTF-8';
        $oMail->Host = 'localhost';
        $oMail->Body = $sContenidoCorreo;
        $oMail->IsHTML(true); // Correo HTML
        $oMail->WordWrap = 50;
        $oMail->From = $sRemitente;
        $oMail->FromName = $sNombreUsuario;
        // Depende de carga de idiomas hecha por _generarContenidoCorreoEvento()
        $oMail->Subject = _tr($sTema).': '.$this->title;
        $oMail->AddStringAttachment($sContenidoIcal, 'icalout.ics', 'base64', 'text/calendar');
        foreach ($this->_notify_emails as $sDireccionEmail) {
            $sNombre = '';
            $sEmail = $sDireccionEmail;
            $regs = NULL;
            if (preg_match('/"?(.*?)"?\s*<(\S+)>/', $sDireccionEmail, $regs)) {
                $sNombre = $regs[1];
                $sEmail = $regs[2];
            }
            if ($oMail->ValidateAddress($sEmail)) {
                $oMail->ClearAddresses();
                $oMail->AddAddress($sEmail, $sNombre);
                $oMail->Send();
            }
        }
    }

    // Procedimiento para leer el nombre del usuario a partir del ACL
    private function _leerNombreUsuario()
    {
        global $arrConf;

        $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
        $pACL = new paloACL($pDB_acl);
        $tuplaUser = $pACL->getUsers($this->_userid);
        if (count($tuplaUser) < 1)
            throw new FailedEventUpdateException(_tr('Invalid user'));
        return $tuplaUser[0][1];
    }

    private function _generarContenidoCorreoEvento($sTema, $sMailEvento, $sNombreUsuario)
    {
        $smarty = getSmarty('default');
        $smarty->assign(array(
            'event'                =>    $this,
            'TAG_EVENTTYPE'        =>    $sTema,
            'TAG_EVENT'            =>    _tr('Event'),
            'TAG_DATE_START'    =>    _tr('Date'),
            'TAG_DATE_END'        =>    _tr('To'),
            'TAG_TIME_INTERVAL'    =>    _tr('time'),
            'TAG_DESCRIPTION'    =>    _tr('Description'),
            'TAG_ORGANIZER'        =>    _tr('Organizer'),
            'USER_NAME'            =>    $sNombreUsuario,
            'MSG_FOOTER'        =>    _tr('footer'),
        ));
        return $smarty->fetch($this->_tpldir.'/emailbody.tpl');
    }

    private function _generarContenidoIcal()
    {
        $smarty = getSmarty('default');
        $smarty->assign('eventlist', array($this));
        return $smarty->fetch($this->_tpldir.'/icalout.tpl');
    }
}
?>
