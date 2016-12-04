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
require_once 'libs/paloSantoForm.class.php';
require_once 'libs/paloSantoJSON.class.php';

function _moduleContent(&$smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/calendarutils.lib.php";

    // TODO: si la funcionalidad vía REST es suficiente, considérese quitar
    require_once "modules/$module_name/libs/paloSantoCalendar.class.php";

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $h = 'handleHTML_mainReport';
    if (isset($_REQUEST['action'])) {
        $h = NULL;

        if (is_null($h) && function_exists('handleJSON_'.$_REQUEST['action']))
            $h = 'handleJSON_'.$_REQUEST['action'];
        if (is_null($h))
            $h = 'handleJSON_unimplemented';
    }
    return call_user_func($h, $smarty, $module_name, $local_templates_dir);
}

function handleHTML_mainReport($smarty, $module_name, $local_templates_dir)
{
    // Verificar si esta levantado el festival
    if (!isFestivalActive()) {
        $smarty->assign('mb_title', _tr('Message'));
        $smarty->assign('mb_message', _tr('Festival is not up'));
    }

    $oForm = new paloForm($smarty, array(
        "event"   => array(
            'LABEL'                  => _tr('Name'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "TEXT",
            'INPUT_EXTRA_PARAM'      => array(
                "style" => "width:274px; margin: 6px;",
                "id" => "event"
            ),
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => ""
            ),
        "date"   => array(
            'LABEL'                  => _tr('Start_date'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "DATE",
            'INPUT_EXTRA_PARAM'      => array(
                "TIME" => true,
                "TIMELIB" => 'bootstrap-datetimepicker',
                'FORMAT'=> '%Y-%m-%d %H:%M',
                "style" => "width:80px"
            ),
            'VALIDATION_TYPE'        => "",
            'EDITABLE'               => "si",
            'VALIDATION_EXTRA_PARAM' => ""
            ),
        "to"   => array(
            'LABEL'                  => _tr('End_date'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "DATE",
            'INPUT_EXTRA_PARAM'      => array(
                "TIME" => true,
                "TIMELIB" => 'bootstrap-datetimepicker',
                'FORMAT'=> '%Y-%m-%d %H:%M',
            ),
            'VALIDATION_TYPE'        => "",
            'EDITABLE'               => "si",
            'VALIDATION_EXTRA_PARAM' => ""
            ),
        "description"   => array(
            'LABEL'                  => _tr('Description'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "TEXTAREA",
            'INPUT_EXTRA_PARAM'      => array(
                "style"=>"width: 274px; margin: 6px;"
            ),
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => "",
            "COLS"                   => "36px",
            "ROWS"                   => "4",
            'EDITABLE'               => "si",
            ),
        "call_to"   => array(
            'LABEL'                  => _tr('Call to'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "TEXT",
            'INPUT_EXTRA_PARAM'      => array(
                "style"     => "width:70px",
                "id"        =>"call_to",
                'pattern'   =>  '^\d+$',
            ),
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => ""
            ),
        "tts"   => array(
            'LABEL'                  => _tr('Text to Speech'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "TEXTAREA",
            'INPUT_EXTRA_PARAM'      => array(
                "style"=>"width: 365px; height: 36px;",
                "maxlength"=>"140",
            ),
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => "",
            "COLS"                   => "48px",
            "ROWS"                   => "2",
            'EDITABLE'               => "si",
            ),
        "notification"   => array(
            'LABEL'                  => _tr('notification'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "CHECKBOX",
            'INPUT_EXTRA_PARAM'      => "",
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => ""
            ),
        "reminder"   => array(
            'LABEL'                  => _tr('active_foneCall'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "CHECKBOX",
            'INPUT_EXTRA_PARAM'      => "",
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => ""
            ),
        "ReminderTime" => array(
            'LABEL'                  => _tr('ReminderTime'),
            'REQUIRED'               => "no",
            'INPUT_TYPE'             => "SELECT",
            'INPUT_EXTRA_PARAM'      => array(
                '10' => '10'._tr('lblbefore'),
                '30' => '30'._tr('lblbefore'),
                '60' => '60'._tr('lblbefore')
            ),
            'VALIDATION_TYPE'        => "text",
            'VALIDATION_EXTRA_PARAM' => "",
            'EDITABLE'               => "si",
            ),
    ));

    $json = new Services_JSON();
    $smarty->assign(array(
        'module_name'               =>  $module_name,
        'title'                     =>  _tr('Calendar'),
        'icon'                      =>  'modules/'.$module_name.'/images/agenda_calendar.png',
        'LBL_CREATE_EVENT'          =>  _tr('Create New Event'),
        'Color'                     =>  _tr('Color'),
        'add_phone'                 =>  _tr('Search in Address Book'),
        'Listen'                    =>  _tr('Listen'),
        'Listen_here'               =>  _tr('Click here to listen'),
        'Notification_Alert'        =>  _tr('Notification_Alert'),
        'notification_email'        =>  _tr('notification_email'),
        'Call_alert'                =>  _tr('Call_alert'),
        'LBL_EXPORT_CALENDAR'       =>  _tr('Export_Calendar'),
        'LBL_LINK_ICAL'             =>  _tr('Download ical calendar'),
        'LBL_CONTACT_NAME'          =>  _tr('Contact'),
        'LBL_CONTACT_EMAIL'         =>  _tr('Email'),
        'SERVER_YEAR'               =>  date('Y'),
        'SERVER_MONTH'              =>  date('m') - 1,

        'ARRLANG_MAIN'              =>  $json->encode(array(
            'LBL_SAVE'              =>  _tr('Save'),
            'LBL_DELETE'            =>  _tr('Delete'),
            'LBL_CANCEL'            =>  _tr('Cancel'),
            'LBL_NEW_EVENT'         =>  _tr('New_Event'),
            'LBL_EDIT_EVENT'        =>  _tr('Edit Event'),
            'MSG_ERROR_EVENTNAME'   =>  _tr('error_eventName'),
            'MSG_ERROR_DATE'        =>  _tr('error_date'),
            'MSG_ERROR_RECORDING'   =>  _tr('error_recording'),
            'MSG_ERROR_CALLTO'      =>  _tr('call_to_error'),
            'MSG_ERROR_INVALID_EMAIL'=> _tr('email_no_valid'),
            'MSG_ERROR_NO_EMAILS'   =>  _tr('error_notification_emails'),
        )),
    ));
    $smarty->assign('EVENT_DIALOG',
        $oForm->fetchForm("$local_templates_dir/calendar_event_dialog.tpl", _tr('Calendar'), array()));
    return $smarty->fetch($local_templates_dir.'/calendar_gui.tpl');
}

function handleJSON_unimplemented($smarty, $module_name, $local_templates_dir)
{
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode(array(
        'status'    =>  'error',
        'message'   =>  _tr('Unimplemented method'),
    ));
}

function handleJSON_display($smarty, $module_name, $local_templates_dir)
{
    /* Esta función sólo llega a invocarse desde el applet Calendar del
     * Dashboard en el módulo elastix-system. Al invocarse, se asigna
     * $_GET['id'] al id de evento que se desea que se muestre. */
    $smarty->assign('EVENT_ID', (isset($_GET['id']) && ctype_digit($_GET['id']))
        ? trim($_GET['id']) : '');
    return handleHTML_mainReport($smarty, $module_name, $local_templates_dir);
}

function handleJSON_previewtts($smarty, $module_name, $local_templates_dir)
{
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    $response = array(
        'message'   =>  previewTTS(getParameter('call_to'), getParameter('tts'))
    );
    $response['status'] = is_null($response['message']) ? 'success' : 'error';
    return $json->encode($response);
}

// FIXME: el módulo graphic_report también invoca el listado de teléfonos
function handleJSON_phone_numbers($smarty, $module_name, $local_templates_dir)
{
    // Redirigir al módulo address_book
    Header('Location: ?menu=address_book&gridformat=phonepick&rawmode=yes');
    return '';
}
