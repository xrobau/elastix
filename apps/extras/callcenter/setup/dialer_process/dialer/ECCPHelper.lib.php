<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: ECCPHelper.lib.php,v 1.48 2009/03/26 13:46:58 alex Exp $ */

/**
 * Procedimiento que consulta toda la información de la base de datos sobre
 * una llamada de campaña. Se usa para el evento agentlinked, así como para
 * el requerimiento getcallinfo.
 *
 * @param   string  $sTipoLlamada   Uno de 'incoming', 'outgoing'
 * @param   integer $idCampania     ID de la campaña, puede ser NULL para incoming
 * @param   integer $idLlamada      ID de la llamada dentro de la campaña
 *
 */
function leerInfoLlamada($db, $sTipoLlamada, $idCampania, $idLlamada)
{
    switch ($sTipoLlamada) {
    case 'incoming':
        return _leerInfoLlamadaIncoming($db, $idCampania, $idLlamada);
    case 'outgoing':
        return _leerInfoLlamadaOutgoing($db, $idCampania, $idLlamada);
    default:
        return NULL;
    }
}

// Leer la información de una llamada saliente. La información incluye lo
// almacenado en la tabla calls, más los atributos asociados a la llamada
// en la tabla call_attribute, y los datos ya recogidos en las tablas
// form_data_recolected y form_field
function _leerInfoLlamadaOutgoing($db, $idCampania, $idLlamada)
{
    // Leer información de la llamada principal
    $sPeticionSQL = <<<INFO_LLAMADA
SELECT 'outgoing' AS calltype, calls.id AS call_id, id_campaign AS campaign_id, phone, status, uniqueid,
    duration, datetime_originate, fecha_llamada AS datetime_originateresponse,
    datetime_entry_queue AS datetime_join, start_time AS datetime_linkstart,
    end_time AS datetime_linkend, retries, failure_cause, failure_cause_txt,
    CONCAT(agent.type, '/', agent.number) AS agent_number, trunk
FROM (calls)
LEFT JOIN agent ON agent.id = calls.id_agent
WHERE id_campaign = ? AND calls.id = ?
INFO_LLAMADA;
    $recordset = $db->prepare($sPeticionSQL);
    $recordset->execute(array($idCampania, $idLlamada));
    $tuplaLlamada = $recordset->fetch(PDO::FETCH_ASSOC); $recordset->closeCursor();
    if (!$tuplaLlamada) {
        // No se encuentra la llamada indicada
        return array();
    }

    // Leer información de los atributos de la llamada
    $tuplaLlamada['call_attributes'] = leerAtributosContacto($db, 'outgoing', $idLlamada);

    // Leer información de los datos recogidos vía formularios
    $tuplaLlamada['call_survey'] = leerDatosRecogidosFormularios($db, 'outgoing', $idLlamada);

    return $tuplaLlamada;
}

// Leer la información de la llamada entrante. En esta implementación, a
// diferencia de las llamadas salientes, las llamadas entrantes tienen un
// solo formulario, y su conjunto de atributos es fijo.
function _leerInfoLlamadaIncoming($db, $idCampania, $idLlamada)
{
    // Leer información de la llamada principal
    $sPeticionSQL = <<<INFO_LLAMADA
SELECT 'incoming' AS calltype, call_entry.id AS call_id, id_campaign AS campaign_id,
    callerid AS phone, status, uniqueid, duration, datetime_entry_queue AS datetime_join,
    datetime_init AS datetime_linkstart, datetime_end AS datetime_linkend,
    trunk, queue, id_contact, CONCAT(agent.type, '/', agent.number) AS agent_number
FROM (call_entry, queue_call_entry)
LEFT JOIN agent ON agent.id = call_entry.id_agent
WHERE call_entry.id = ? AND call_entry.id_queue_call_entry = queue_call_entry.id
INFO_LLAMADA;
    $recordset = $db->prepare($sPeticionSQL);
    $recordset->execute(array($idLlamada));
    $tuplaLlamada = $recordset->fetch(PDO::FETCH_ASSOC); $recordset->closeCursor();
    if (!$tuplaLlamada) {
        // No se encuentra la llamada indicada
        return array();
    }

    // Leer información de los atributos de la llamada
    // TODO: expandir cuando se tenga tabla de atributos arbitrarios
    $idContact = $tuplaLlamada['id_contact'];
    unset($tuplaLlamada['id_contact']);
    $tuplaLlamada['call_attributes'] = array();
    if (!is_null($idContact)) {
        $tuplaLlamada['call_attributes'] = leerAtributosContacto($db, 'incoming', $idContact);
    }

    // Leer información de todos los contactos que coincidan en callerid
    $tuplaLlamada['matching_contacts'] = array();
    $sPeticionSQL = <<<INFO_ATRIBUTOS
SELECT id, name AS first_name, apellido AS last_name, telefono AS phone, cedula_ruc
FROM contact WHERE telefono = ?
INFO_ATRIBUTOS;
    $recordset = $db->prepare($sPeticionSQL);
    $recordset->execute(array($tuplaLlamada['phone']));
    foreach ($recordset as $tuplaContacto) {
        $tuplaLlamada['matching_contacts'][$tuplaContacto['id']] = array(
            array(
                'label' =>  'first_name',
                'value' =>  $tuplaContacto['first_name'],
                'order' =>  1,
            ),
            array(
                'label' =>  'last_name',
                'value' =>  $tuplaContacto['last_name'],
                'order' =>  2,
            ),
            array(
                'label' =>  'phone',
                'value' =>  $tuplaContacto['phone'],
                'order' =>  3,
            ),
            array(
                'label' =>  'cedula_ruc',
                'value' =>  $tuplaContacto['cedula_ruc'],
                'order' =>  4,
            ),
        );
    }

    // Leer información de los datos recogidos vía formularios
    $tuplaLlamada['call_survey'] = leerDatosRecogidosFormularios($db, 'incoming', $idLlamada);

    return $tuplaLlamada;
}

function leerAtributosContacto($db, $sTipoLlamada, $idContacto)
{
    $r = array();

    switch ($sTipoLlamada) {
    case 'outgoing':
        $sPeticionSQL = <<<INFO_ATRIBUTOS
SELECT columna AS `label`, value, column_number AS `order`
FROM call_attribute WHERE id_call = ?
ORDER BY column_number
INFO_ATRIBUTOS;
        break;
    case 'incoming':
        $sPeticionSQL = NULL;
        break;
    }

    if (!is_null($sPeticionSQL)) {
        $recordset = $db->prepare($sPeticionSQL);
        $recordset->execute(array($idContacto));
        $r = $recordset->fetchAll(PDO::FETCH_ASSOC);
        $recordset->closeCursor();
    }

    // Caso especial: llamadas entrantes
    if ($sTipoLlamada == 'incoming') {
        $sPeticionSQL = <<<INFO_ATRIBUTOS
SELECT name AS first_name, apellido AS last_name, telefono AS phone, cedula_ruc, origen AS contact_source
FROM contact WHERE id = ?
INFO_ATRIBUTOS;
        $recordset = $db->prepare($sPeticionSQL);
        $recordset->execute(array($idContacto));
        $atributosLlamada = $recordset->fetch(PDO::FETCH_ASSOC);
        $recordset->closeCursor();
        foreach ($atributosLlamada as $k => $v) {
            $r[] = array(
                'label' =>  $k,
                'value' =>  $v,
                'order' =>  count($r) + 1,
            );
        }
    }
    return $r;
}

function nombresCamposFormulariosEstaticos($sTipoLlamada)
{
    switch ($sTipoLlamada) {
    case 'incoming':
        $fdr_tabla = 'form_data_recolected_entry';
        $fdr_campo = 'id_call_entry';
        break;
    case 'outgoing':
        $fdr_tabla = 'form_data_recolected';
        $fdr_campo = 'id_calls';
        break;
    }
    return array($fdr_tabla, $fdr_campo);
}

function leerDatosRecogidosFormularios($db, $sTipoLlamada, $idLlamada)
{
    list($fdr_tabla, $fdr_campo) = nombresCamposFormulariosEstaticos($sTipoLlamada);

    // Leer información de los datos recogidos vía formularios
    $sPeticionSQL = <<<INFO_FORMULARIOS
SELECT form_field.id_form, form_field.id, form_field.etiqueta AS label,
    $fdr_tabla.value
FROM $fdr_tabla, form_field
WHERE $fdr_tabla.$fdr_campo = ?
    AND $fdr_tabla.id_form_field = form_field.id
ORDER BY form_field.id_form, form_field.orden
INFO_FORMULARIOS;
    $recordset = $db->prepare($sPeticionSQL);
    $recordset->execute(array($idLlamada));
    $datosFormularios = $recordset->fetchAll(PDO::FETCH_ASSOC);

    $call_survey = array();
    foreach ($datosFormularios as $tuplaFormulario) {
        $call_survey[$tuplaFormulario['id_form']][] = array(
            'id'    => $tuplaFormulario['id'],
            'label' => $tuplaFormulario['label'],
            'value' => $tuplaFormulario['value'],
        );
    }

    return $call_survey;
}

/**
 * Método para marcar en las tablas de auditoría que el agente ha terminado
 * su hold o break.
 *
 * @param   int     $idAuditBreak   ID del break devuelto por marcarInicioBreakAgente()
 */
function marcarFinalBreakAgente($db, $idAuditBreak, $iTimestampLogout)
{
    $sTimeStamp = date('Y-m-d H:i:s', $iTimestampLogout);
    $sth = $db->prepare(
            'UPDATE audit SET datetime_end = ?, duration = TIMEDIFF(?, datetime_init) WHERE id = ?');
    $sth->execute(array($sTimeStamp, $sTimeStamp, $idAuditBreak));
}

function construirEventoPauseEnd($db, $sAgente, $id_audit_break, $pause_class)
{
    // Obtener inicio, fin y duración de break para lanzar evento
    $recordset = $db->prepare(
        'SELECT break.id AS break_id, break.name AS break_name, '.
            'audit.datetime_init AS datetime_breakstart, audit.datetime_end AS datetime_breakend, '.
            'TIME_TO_SEC(audit.duration) AS duration_sec '.
        'FROM audit, break '.
        'WHERE audit.id = ? AND audit.id_break = break.id');
    $recordset->execute(array($id_audit_break));
    $tuplaBreak = $recordset->fetch(PDO::FETCH_ASSOC);
    $recordset->closeCursor();
    $paramsEvento = array(
        'pause_class'   =>  $pause_class,
        'pause_start'   =>  $tuplaBreak['datetime_breakstart'],
        'pause_end'     =>  $tuplaBreak['datetime_breakend'],
        'pause_duration'=>  $tuplaBreak['duration_sec'],
    );
    if ($pause_class != 'hold') {
    	$paramsEvento['pause_type'] = $tuplaBreak['break_id'];
        $paramsEvento['pause_name'] = $tuplaBreak['break_name'];
    }
    return array('PauseEnd', array($sAgente, $paramsEvento));
}

function cargarInfoPausa($db, &$infoAgente)
{
    if (!is_null($infoAgente['id_break'])) {
        $recordset = $db->prepare(
                'SELECT audit.datetime_init, break.name, break.id '.
                'FROM audit, break WHERE audit.id_break = break.id AND audit.id = ?');
        $recordset->execute(array($infoAgente['id_audit_break']));
        $tupla = $recordset->fetch(PDO::FETCH_ASSOC);
        $recordset->closeCursor();
        if ($tupla) {
            $infoAgente['pausename'] = $tupla['name'];
            $infoAgente['pausestart'] = $tupla['datetime_init'];
        }
    }
}

function construirEventoProgresoLlamada($db, $prop)
{
    $id_campaignlog = NULL;
    $ev = NULL;

    $campaign_type = NULL;
    foreach (array('incoming', 'outgoing') as $ct) {
        if (isset($prop['id_call_'.$ct])) {
            $campaign_type = $ct;
            break;
        }
    }

    /* Se leen las propiedades del último log de la llamada, o NULL si no
     * hay cambio de estado previo. */
    $recordset = $db->prepare(
        "SELECT retry, uniqueid, trunk, id_agent, duration ".
        "FROM call_progress_log WHERE id_call_{$campaign_type} = ? ".
        "ORDER BY datetime_entry DESC, id DESC LIMIT 0,1");
    $recordset->execute(array($prop['id_call_'.$campaign_type]));
    $tuplaAnterior = $recordset->fetch(PDO::FETCH_ASSOC);
    $recordset->closeCursor();
    if (!is_array($tuplaAnterior) || count($tuplaAnterior) <= 0) {
        $tuplaAnterior = array(
            'retry'             =>  0,
            'uniqueid'          =>  NULL,
            'trunk'             =>  NULL,
            'id_agent'          =>  NULL,
            'duration'          =>  NULL,
        );
    }

    // Si el número de reintento es distinto, se anulan datos anteriores
    if (isset($prop['retry']) && $tuplaAnterior['retry'] != $prop['retry']) {
        $tuplaAnterior['uniqueid'] = NULL;
        $tuplaAnterior['trunk'] = NULL;
        $tuplaAnterior['id_agent'] = NULL;
        $tuplaAnterior['duration'] = NULL;
    }
    $tuplaAnterior = array_merge($tuplaAnterior, $prop);

    // Escribir los valores nuevos en un nuevo registro
    unset($tuplaAnterior['queue']);
    $columnas = array_keys($tuplaAnterior);
    $paramSQL = array();
    foreach ($columnas as $k) $paramSQL[] = $tuplaAnterior[$k];
    $sPeticionSQL = 'INSERT INTO call_progress_log ('.
            implode(', ', $columnas).') VALUES ('.
            implode(', ', array_fill(0, count($columnas), '?')).')';
    $sth = $db->prepare($sPeticionSQL);
    $sth->execute($paramSQL);

    $id_campaignlog = $tuplaAnterior['id'] = $db->lastInsertId();

    /* Emitir el evento a las conexiones ECCP. Para mantener la
     * consistencia con el resto del API, se quitan los valores de
    * id_call_* y id_campaign_*, y se sintetiza tipo_llamada. */
    if (!in_array($tuplaAnterior['new_status'], array('Success', 'Hangup', 'ShortCall'))) {
        // Todavía no se soporta emitir agente conectado para OnHold/OffHold
        unset($tuplaAnterior['id_agent']);

        $tuplaAnterior['campaign_type'] = $campaign_type;
        if (isset($tuplaAnterior['id_campaign_'.$campaign_type]))
            $tuplaAnterior['campaign_id'] = $tuplaAnterior['id_campaign_'.$campaign_type];
        $tuplaAnterior['call_id'] = $tuplaAnterior['id_call_'.$campaign_type];
        unset($tuplaAnterior['id_campaign_'.$campaign_type]);
        unset($tuplaAnterior['id_call_'.$campaign_type]);

        // Agregar el teléfono callerid o marcado
        $sql = array(
            'outgoing'  =>
                'SELECT calls.phone, campaign.queue '.
                'FROM calls, campaign '.
                'WHERE calls.id_campaign = campaign.id AND calls.id = ?',
            'incoming'  =>
                'SELECT call_entry.callerid AS phone, queue_call_entry.queue '.
                'FROM call_entry, queue_call_entry '.
                'WHERE call_entry.id_queue_call_entry = queue_call_entry.id AND call_entry.id = ?',
        );
        $recordset = $db->prepare($sql[$tuplaAnterior['campaign_type']]);
        $recordset->execute(array($tuplaAnterior['call_id']));
        $tuplaNumero = $recordset->fetch(PDO::FETCH_ASSOC);
        $recordset->closeCursor();
        $tuplaAnterior['phone'] = $tuplaNumero['phone'];
        $tuplaAnterior['queue'] = $tuplaNumero['queue'];
        $ev = array('CallProgress', array($tuplaAnterior));
    }

    return array($id_campaignlog, $ev);
}
?>