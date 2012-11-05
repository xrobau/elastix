<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.org                                               |
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
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/
//require_once 'libs/paloSantoFax.class.php';
require_once 'libs/paloSantoGrid.class.php';
require_once 'libs/paloSantoJSON.class.php';
require_once 'libs/misc.lib.php';

function _moduleContent($smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";
    
    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    switch (getParameter('action')) {
    case 'checkqueue':
        return listarColaFax_json($smarty, $module_name, $local_templates_dir);
    case 'list':
    default:
        return listarColaFax_html($smarty, $module_name, $local_templates_dir);
    }
}

function listarColaFax_html($smarty, $module_name, $local_templates_dir)
{
    $listaColaFax = enumerarFaxesPendientes();
    $hash = md5(serialize($listaColaFax));
    $html = listarColaFax_raw($smarty, $module_name, $local_templates_dir, $listaColaFax);
    return '<div id="faxqueuelist">'.$html.'</div>'.
        "<input name=\"outputhash\" id=\"outputhash\" type=\"hidden\" value=\"$hash\" />";
}

function listarColaFax_json($smarty, $module_name, $local_templates_dir)
{
    session_commit();
    $oldhash = getParameter('outputhash');

    $html = NULL;
    $startTime = time();
    do {
        $listaColaFax = enumerarFaxesPendientes();
        $newhash = md5(serialize($listaColaFax));
        file_put_contents('/tmp/debug-faxqueue.txt', "oldhash=$oldhash newhash=$newhash\n", FILE_APPEND);
        if ($oldhash == $newhash) {
        	usleep(2 * 1000000);
        } else {
            $html = listarColaFax_raw($smarty, $module_name, $local_templates_dir, $listaColaFax);
        }
    } while($oldhash == $newhash && time() - $startTime < 30);

    $jsonObject = new PalosantoJSON();
    $jsonObject->set_status(($oldhash != $newhash) ? 'CHANGED' : 'NOCHANGED');
    $jsonObject->set_message(array('html' => $html, 'outputhash' => $newhash));
    Header('Content-Type: application/json');
    return $jsonObject->createJSON();
}

function listarColaFax_raw($smarty, $module_name, $local_templates_dir, $listaColaFax)
{
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->pagingShow(FALSE);
    $oGrid->setURL("?menu=faxqueue");
    $oGrid->setTitle(_tr('Fax Queue'));
    
    $arrColumns = array(
        _tr('Job ID'),
        _tr('Priority'),
        _tr('Destination'),
        _tr('Pages'),
        _tr('Retries'),
        _tr('Status'));
    $oGrid->setColumns($arrColumns);
    
    function listarColaFax_toHTML($t)
    {
    	return array(
            $t['jobid'],
            $t['priority'],
            $t['outnum'],
            sprintf(_tr('Sent %d pages of %d'), $t['sentpages'], $t['totalpages']),
            sprintf(_tr('Try %d of %d'), $t['retries'], $t['totalretries']),
            _tr($t['status']),
        );
    }    
    $oGrid->setData(array_map('listarColaFax_toHTML', $listaColaFax));
    return $oGrid->fetchGrid();
}

/* Enumerar los faxes pendientes de enviar como una estructura
[root@elx2 ~]# faxstat -s -d
HylaFAX scheduler on localhost: Running
Modem ttyIAX1 (): Running and idle
Modem ttyIAX2 (): Running and idle

JID  Pri S  Owner Number       Pages Dials     TTS Status
28   125 S asteri 1099          0:1   2:12   17:27 Busy signal detected
 */
function enumerarFaxesPendientes()
{
    // %-4j %3i %1a %6.6o %-12.12e %5P %5D %7z %.25s
    $regexp = '/^(\d+)\s+(\d+)\s+(\w+)\s+(\S+)\s+(\S+)\s+(\d+):(\d+)\s+(\d+):(\d+)\s+(\d+:\d+)?\s*(.*)/';    
	$output = $retval = NULL;
    exec('/usr/bin/faxstat -sl', $output, $retval);
    $faxqueue = array();
    foreach ($output as $s) {
    	$regs = NULL;
        if (preg_match($regexp, trim($s), $regs)) {
    		$faxqueue[] = array(
                'jobid'         =>  $regs[1],
                'priority'      =>  $regs[2],
                'state'         =>  $regs[3],
                'owner'         =>  $regs[4],
                'outnum'        =>  $regs[5],
                'sentpages'     =>  $regs[6],
                'totalpages'    =>  $regs[7],
                'retries'       =>  $regs[8],
                'totalretries'  =>  $regs[9],
                'timetosend'    =>  $regs[10],
                'status'        =>  $regs[11],
            );
    	}
    }
    
    return $faxqueue;
}
?>