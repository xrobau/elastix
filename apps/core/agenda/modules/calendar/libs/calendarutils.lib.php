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

function previewTTS($call_to, $tts)
{
    require_once '/var/lib/asterisk/agi-bin/phpagi-asmanager.php';

    // Número a llamar sólo puede ser numérico
    if (!preg_match('/^\d+$/', $call_to))
        return _tr('Invalid extension to call for reminder');

    // Texto a generar no debe contener saltos de línea
    if (FALSE !== strpbrk($tts, "\r\n"))
        return _tr('Reminder text may not have newlines');

    // Obtener la información para poder ejecutar la marcación
    $astman = new AGI_AsteriskManager();
    if (!$astman->connect('127.0.0.1', 'admin' , obtenerClaveAMIAdmin()))
        return _tr('Error when connecting to Asterisk Manager');

    // Claves registradas por FreePBX
    $cidname = $astman->database_get('AMPUSER', $call_to.'/cidname');
    $dial = $astman->database_get('DEVICE', $call_to.'/dial');

    $r = $astman->Originate($dial,
        NULL, NULL, NULL,
        'Festival', $tts,
        NULL, "{$cidname} <{$call_to}>", "TTS={$tts}", NULL, TRUE);
    $astman->disconnect();

    return NULL;
}

function searchCalendarContacts($search, $elastixuser)
{
    global $arrConf;
    $response = array();

    // Obtener ID de ACL del usuario, dado el nombre de usuario
    $pACL = new paloACL(new paloDB($arrConf['elastix_dsn']['acl']));
    $id_user = $pACL->getIdUser($elastixuser);

    // Buscar coincidencias de la búsqueda
    $pDBAddress = new paloDB($arrConf['dsn_conn_database3']);
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

    return $response;
}

function isFestivalActive()
{
    $output = $retval = NULL;
    exec('/sbin/service festival status', $output, $retval);
    return ($retval == 0);
}
?>