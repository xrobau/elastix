<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                               |
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
  $Id: index.php,v 1.1 2008-08-03 11:08:42 Andres Flores aflores@palosanto.com Exp $ */
//include elastix framework
require_once "libs/paloSantoJSON.class.php";
require_once "libs/paloSantoGrid.class.php";
require_once "libs/paloSantoForm.class.php";

function _moduleContent($smarty, $module_name)
{
    global $arrConf;

    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/paloSantoDUNDIExchange.class.php";

    // Se fusiona la configuración del módulo con la configuración global
    $arrConf = array_merge($arrConf, $arrConfModule);

    /* Se pide el archivo de inglés, que se elige a menos que el sistema indique
     otro idioma a usar. Así se dispone al menos de la traducción al inglés
     si el idioma elegido carece de la cadena.
     */
    load_language_module($module_name);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // se conecta a la base
    $pDB = new paloDB($arrConf["dsn_conn_database"]);
    if (!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", _tr('Error when connecting to database')."<br/>".$pDB->errMsg);
    }

    switch (getParameter('action')) {
    case 'peerstatus':
        return handleJSON_peerstatus($smarty, $module_name, $local_templates_dir, $pDB);
    case 'new_request':
        return createNewRequest($smarty, $module_name, $local_templates_dir, $pDB);
    case 'peerdetails':
        return viewPeerDetails($smarty, $module_name, $local_templates_dir, $pDB);
    case 'report':
    default:
        return reportPeerList($smarty, $module_name, $local_templates_dir, $pDB);
    }
}

function reportPeerList($smarty, $module_name, $local_templates_dir, $pDB)
{
    $pInfo = new paloSantoDUNDIExchange($pDB);
    $bActualizarDUNDI = FALSE;

    // Avisar si no se ha creado clave pública local
    if (!$pInfo->isLocalCertificateCreated($_SERVER['SERVER_ADDR'])) {
        $smarty->assign("mb_title", _tr('Message').":");
        $smarty->assign("mb_message",_tr("You should register the <a href='?menu=general_information'>General Information</a>."));
    }

    // TODO: debería usarse la IP a través de la cual se recibió la petición
    $local_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "";

    if (isset($_POST['peerid'])) {
        $okresult = NULL;
        $errmsg = NULL;
        $peerid = (int)$_POST['peerid'];

        // Procesamiento de las operaciones sobre un peer
        if (isset($_POST['peer_delete'])) {
            $r = $pInfo->deleteExchangeRequest($local_ip, $peerid);
            $okresult = 'reject';
            $errmsg = _tr('Error trying to delete peer').': ';
        } elseif (isset($_POST['peer_reject'])) {
            $r = $pInfo->rejectExchangeRequest($local_ip, $peerid);
            $okresult = 'reject';
            $errmsg = _tr('Error trying to reject peer').': ';
        } elseif (isset($_POST['peer_accept'])) {
            $r = $pInfo->acceptExchangeRequest($local_ip, $peerid);
            $okresult = 'accept';
            $errmsg = _tr('Error trying to accept peer').': ';
        } elseif (isset($_POST['peer_connect'])) {
            $r = $pInfo->setPeerConnectedState($local_ip, $peerid, TRUE);
            $okresult = 'connected';
            $errmsg = _tr('Error trying to connect peer').': ';
        } elseif (isset($_POST['peer_disconnect'])) {
            $r = $pInfo->setPeerConnectedState($local_ip, $peerid, FALSE);
            $okresult = 'connected';
            $errmsg = _tr('Error trying to disconnect peer').': ';
        }

        if (!is_null($okresult)) {
            if (!is_null($r) && $r['result'] != $okresult) {
                formatResponseMessage($smarty, $r, $pInfo->errMsg);
            } elseif (is_null($r) || $r['posterror']) {
                $smarty->assign(array(
                    'mb_title'  =>  _tr('Message'),
                    'mb_message'=>  $errmsg.$pInfo->errMsg,
                ));
            } else {
                $bActualizarDUNDI = TRUE;
            }
        }
    }

    if (isset($_POST['update_dundiconf'])) $bActualizarDUNDI = TRUE;
    if ($bActualizarDUNDI) {
        // Procesamiento para actualizar archivo de DUNDI
        if (!$pInfo->refreshDUNDIConfig()) {
            $smarty->assign(array(
                'mb_title'  =>  _tr('ERROR'),
                'mb_message'=>  $pInfo->errMsg,
            ));
        }
    }

    // Procesamiento de la visualización de peers
    $oGrid = new paloSantoGrid($smarty);
    $limit = 20;
    $total = $pInfo->countExchangedPeers();
    if($total === FALSE) {
        $smarty->assign("mb_title", _tr('ERROR'));
        $smarty->assign("mb_message", _tr('Failed to count peers')."<br/>".$pDB->errMsg);
        $total = 0;
    }
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
    $oGrid->setTitle(_tr('Remote Servers'));
    $oGrid->setWidth("99%");
    $oGrid->setIcon('images/list.png');
    $oGrid->setURL(array('menu' => $module_name), array('nav', 'start'));
    $oGrid->setColumns(array('', _tr('Remote Server'), _tr('Connection Status'), _tr('Actions')));

    $arrResult = $pInfo->listExchangedPeers($limit, $offset);
    if (!is_array($arrResult)) {
        $smarty->assign("mb_title", _tr('ERROR'));
        $smarty->assign("mb_message", _tr('Failed to fetch peers')."<br/>".$pDB->errMsg);
        $arrResult = array();
    }

    $arrData = array();
    foreach ($arrResult as $tupla) {
        $arrData[] = array(
            '<input type="radio" name="peerid" value="'.$tupla['id'].'">',
            $tupla['host'].' <strong><div class="peer-company"></div></strong>',
            '<img src="images/loading.gif" height="20px" class="peer-loading" /><span class="peer-status">'.
                '<a class="peer-newrequest" style="display: none;" href="?menu='.$module_name.'&action=peerdetails&id='.$tupla['id'].'">'._tr('New Connection Request').'</a>'.
                '<span class="peer-status-txt"></span></span>',
            '<a class="peer-view" style="display: none;" href="?menu='.$module_name.'&action=peerdetails&id='.$tupla['id'].'">'._tr('View').'</a>',
        );
    }

    // Construcción de la rejilla de vista
    $oGrid->deleteList('Are you sure you wish to delete Connection (s)?',
        "peer_delete", _tr('Delete Selected'));
    $oGrid->addNew("?menu=$module_name&action=new_request", _tr('New Request'), TRUE);
    $oGrid->addSubmitAction('peer_accept', _tr('Accept'), 'check');
    $oGrid->addSubmitAction('peer_reject', _tr('Reject'), 'ban');
    $oGrid->addSubmitAction('peer_connect', _tr('Connect'), 'plug');
    $oGrid->addSubmitAction('peer_disconnect', _tr('Disconnect'), 'eject');
    $oGrid->addSubmitAction('update_dundiconf', _tr('Update DUNDI'), 'refresh');

    return $oGrid->fetchGrid(array(), $arrData);
}

function viewPeerDetails($smarty, $module_name, $local_templates_dir, $pDB)
{
    if (!isset($_REQUEST['id'])) {
        Header('Location: ?menu='.$module_name);
        return '';
    }
    $smarty->assign(array(
        "MODE"          => "view",
        "REQUIRED_FIELD"=>_tr('Required field'),
        "BACK"          => _tr('Back'),
    ));
    $pInfo = new paloSantoDUNDIExchange($pDB);
    $peerInfo = $pInfo->loadPeerDataById($_REQUEST['id']);
    $oForm = new paloForm($smarty, array(
        "host"   => array(
            "LABEL"                  => _tr('Host'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "ip",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "comment"  => array(
            "LABEL"                  => _tr('Comment'),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXTAREA",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "EDITABLE"               => "no",
            "COLS"                   => "30",
            "ROWS"                   => "4",
            "VALIDATION_EXTRA_PARAM" => ""),
        "company"   => array(
            "LABEL"                  => _tr('Company'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "ip",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
    ));
    $oForm->setViewMode();
    $htmlForm = $oForm->fetchForm("$local_templates_dir/view_peer.tpl",_tr('View Remote Server'), $peerInfo);
    return "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
}

function createNewRequest($smarty, $module_name, $local_templates_dir, $pDB)
{
    // El botón de nombre "show" tiene la etiqueta "Cancel"
    if (isset($_POST['show'])) {
        Header('Location: ?menu='.$module_name);
        return '';
    }

    $smarty->assign(array(
        'Request'           =>  _tr('Request'),
        'Cancel'            =>  _tr('Cancel'),
        'REQUIRED_FIELD'    =>  _tr('Required field'),
        'icon'              =>  'images/list.png'
    ));
    $pInfo = new paloSantoDUNDIExchange($pDB);
    $oForm = new paloForm($smarty, array(
        "ip"   => array(
            "LABEL"                  => _tr('Host Remote'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "ip",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "comment_request"   => array(
            "LABEL"                  => _tr('Comment'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXTAREA",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "EDITABLE"               => "si",
            "COLS"                   => "50",
            "ROWS"                   => "4",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "secret"   => array(
            "LABEL"                  => _tr('Secret'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),

    ));
    if (isset($_POST['request'])) {
        if (!$oForm->validateForm($_POST)) {
            $smarty->assign(array(
                "mb_title"  => _tr('Validation Error'),
                "mb_message"=> "<b>"._tr('The following fields contain errors').":</b><br/>".
                    implode(', ', array_keys($oForm->arrErroresValidacion)),
            ));
        } else {
            $r = $pInfo->createExchangeRequest(
                isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : NULL,
                $_POST['comment_request'], $_POST['ip'], $_POST['secret']);
            if (is_null($r)) {
                $smarty->assign(array(
                    'mb_title'  =>  _tr('ERROR'),
                    'mb_message'=>  $pInfo->errMsg,
                ));
            } else {
                formatResponseMessage($smarty, $r, $pInfo->errMsg);
                if ($r['result'] == 'request' && !$r['posterror']) {
                    // Se vuelve al listado de peers
                    Header('Location: ?menu='.$module_name);
                    return '';
                }
            }
        }
    }

    $htmlForm = $oForm->fetchForm("$local_templates_dir/request.tpl", _tr('Connection Request'), $_POST);
    return "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".
        '<input type="hidden" name="action" value="new_request"/>'.
        $htmlForm.
        "</form>";
}

function formatResponseMessage($smarty, $r, $errmsg)
{
    $sAdicional = '';
    if (!in_array($r['diagnostics'], array('cURL error', 'invalid body', ''))) {
        $sAdicional = ': '._tr('Extra information').': '.$r['diagnostics'];
    }
    switch ($r['result']) {
    case 'request':
        // Lado remoto aceptó la petición
        if ($r['posterror']) {
            $smarty->assign(array(
                'mb_title'  =>  _tr('ERROR'),
                'mb_message'=>  $pInfo->errMsg,
            ));
        }
        break;
    case 'norequest':
        $smarty->assign(array(
            'mb_title'  =>  _tr('Alert'),
            'mb_message'=>  _tr('Remote request failed').$sAdicional,
        ));
        break;
    case 'exist':
        $smarty->assign(array(
            'mb_title'  =>  _tr('Alert'),
            'mb_message'=>  _tr('Currently there is a connection request').$sAdicional,
        ));
        break;
    case 'nosecret':
        $smarty->assign(array(
            'mb_title'  =>  _tr('ERROR'),
            'mb_message'=>  _tr('Secret incorrect').$sAdicional,
        ));
        break;
    case 'invalid body':
        if ($r['http_code'] == 404) {
            // El punto de entrada no existe en el servidor remoto
            $smarty->assign(array(
                'mb_title'  =>  _tr('ERROR'),
                'mb_message'=>  _tr('Remote entry point not found. Check that remote system has addon installed.'),
            ));
        } else {
            $s = "<br/><pre>\n";
            foreach ($r as $k => $v) $s .= $k.': '.$v."\n";
            $s .= "</pre>\n";
            $smarty->assign(array(
                'mb_title'  =>  _tr('ERROR'),
                'mb_message'=>  _tr('Invalid response body').': '.$s,
            ));
        }
        break;
    }
}

function handleJSON_peerstatus($smarty, $module_name, $local_templates_dir, $pDB)
{
    $pInfo = new paloSantoDUNDIExchange($pDB);

    $respuesta = array(
        'error'             =>  '',
        'message'           =>  array(),
        'statusResponse'    =>  'OK',
    );

    if (!isset($_GET['peerid']) || !is_array($_GET['peerid'])) {
        $respuesta['statusResponse'] = 'ERROR';
        $respuesta['error'] = _tr('Invalid peerid array');
    } else {
        foreach ($_GET['peerid'] as $peerid) {
            $tupla = $pInfo->loadPeerDataById($peerid);
            if (is_null($tupla)) {
                $respuesta['statusResponse'] = 'ERROR';
                $respuesta['error'] = _tr('Failed to fetch peers').': '.$pInfo->errMsg;
                break;
            } elseif (count($tupla) <= 0) {
                $respuesta['statusResponse'] = 'ERROR';
                $respuesta['error'] = _tr('Peer not found').': '.$peerid;
                break;
            } else {
                if (is_null($tupla['company']) || in_array($tupla['status'], array(PSDEX_STATE_LOCAL_WAITING, PSDEX_STATE_LOCAL_ACCEPTED)))
                    $tupla['company'] = '('._tr('Unknown').')';

                // Mensaje a mostrar como estado
                $tupla['color'] = 'black';
                $tupla['status_txt'] = _tr($tupla['status']);
                if ($tupla['his_status'] == PSDEX_STATE_REMOTE_DELETED) {
                    $tupla['status_txt'] = _tr('Request Connection has been Deleted');
                    $tupla['color'] = 'red';
                } elseif ($tupla['status'] == PSDEX_STATE_LOCAL_REJECTED) {
                    $tupla['status_txt'] = _tr('Request Connection has been Rejected');
                    $tupla['color'] = 'red';
                } elseif ($tupla['status'] == PSDEX_STATE_LOCAL_REQUESTING) {
                    $tupla['status_txt'] = '';
                    $tupla['color'] = 'blue';
                } elseif ($tupla['status'] == PSDEX_STATE_LOCAL_WAITING) {
                    $tupla['status_txt'] = _tr('Waiting Response...');
                    $tupla['color'] = 'red';
                } elseif ($tupla['status'] == PSDEX_STATE_LOCAL_ACCEPTED) {
                    $tupla['color'] = 'green';
                    if ($tupla['his_status'] == PSDEX_STATE_REMOTE_DISCONNECTED) {
                        $tupla['status_txt'] = _tr('Request Connection has been Accepted');
                        $tupla['color'] = 'blue';
                    }
                    if ($tupla['his_status'] == PSDEX_STATE_REMOTE_CONNECTED) {
                        $tupla['status_txt'] = _tr('Connecting...');
                        $tupla['color'] = 'blue';
                    }
                } elseif ($tupla['status'] == PSDEX_STATE_LOCAL_DISCONNECTED) {
                    if ($tupla['his_status'] == PSDEX_STATE_REMOTE_DISCONNECTED)
                        $tupla['status_txt'] = _tr('Disconnected');
                    if ($tupla['his_status'] == PSDEX_STATE_REMOTE_CONNECTED) {
                        $tupla['status_txt'] = _tr('Remote Server is requesting Connection...');
                        $tupla['color'] = 'red';
                    }
                } elseif ($tupla['status'] == PSDEX_STATE_LOCAL_CONNECTED) {
                    $tupla['color'] = 'green';
                    if ($tupla['his_status'] == PSDEX_STATE_REMOTE_DISCONNECTED) {
                        $tupla['status_txt'] = _tr('Requesting Connection to Remote Server...');
                        $tupla['color'] = 'blue';
                    }
                    if ($tupla['his_status'] == PSDEX_STATE_REMOTE_CONNECTED)
                        $tupla['status_txt'] = _tr('Connected');
                }

                // Botones a activar y desactivar según estado
                $tupla['ctlenable'] = array();
                $tupla['trclasses'] = array();
                if (!in_array($tupla['status'], array(PSDEX_STATE_LOCAL_REQUESTING, PSDEX_STATE_LOCAL_WAITING, PSDEX_STATE_LOCAL_REJECTED)) &&
                    $tupla['his_status'] != PSDEX_STATE_REMOTE_DELETED)
                    $tupla['ctlenable'][] = 'a.peer-view';
                if ($tupla['status'] == PSDEX_STATE_LOCAL_REQUESTING) {
                    $tupla['trclasses'][] = 'peer-accept';
                    $tupla['trclasses'][] = 'peer-reject';
                }
                if ($tupla['status'] == PSDEX_STATE_LOCAL_REQUESTING &&
                    $tupla['his_status'] != PSDEX_STATE_REMOTE_DELETED)
                    $tupla['ctlenable'][] = 'a.peer-newrequest';
                if (in_array($tupla['status'], array(PSDEX_STATE_LOCAL_ACCEPTED, PSDEX_STATE_LOCAL_DISCONNECTED)))
                    $tupla['trclasses'][] = 'peer-connect';
                if ($tupla['status'] == PSDEX_STATE_LOCAL_CONNECTED &&
                    in_array($tupla['his_status'], array(PSDEX_STATE_REMOTE_DISCONNECTED, PSDEX_STATE_REMOTE_CONNECTED)))
                    $tupla['trclasses'][] = 'peer-disconnect';

                // Autoconectar según estado
                $tupla['autoconn'] = ($tupla['status'] == PSDEX_STATE_LOCAL_ACCEPTED &&
                    $tupla['his_status'] == PSDEX_STATE_REMOTE_CONNECTED);
                $respuesta['message'][] = $tupla;
            }
        }
    }

    Header('Content-Type: application/json');
    $json = new Services_JSON();
    return $json->encode($respuesta);
}
?>