<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0                                                  |
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
  $Id: paloSantoDataApplets.class.php,v 1.1.1.1 2011/02/11 Alberto Santos 21:31:55  Exp $ */

include_once "libs/paloSantoGraphImage.lib.php";
include_once "paloSantoSysInfo.class.php";
include_once "paloSantoDashboard.class.php";
require_once "libs/magpierss/rss_fetch.inc";
require_once "libs/paloSantoDB.class.php";

class paloSantoDataApplets
{
    var $arrConf;
    var $icon;
    var $title;
    var $module_name;

    function paloSantoDataApplets($module_name,$arrConf)
    {
        $this->arrConf = $arrConf;
        $this->module_name = $module_name;
        $icon = "";
        $title = "";
    }

    private function _getFastGraphics()
    {
        $uelastix = FALSE;
        if (isset($_SESSION)) {
            $pDB = new paloDB($this->arrConf['elastix_dsn']['settings']);
            if (empty($pDB->errMsg)) {
                $uelastix = get_key_settings($pDB, 'uelastix');
                $uelastix = ((int)$uelastix != 0);
            }
            unset($pDB);
        }
        return $uelastix;
    }

    function getDataApplet_HardDrives()
    {
        $content = '';

    	// Intento de ejecutar los comandos en paralelo
        $pipe_dirspace = popen('/usr/bin/elastix-helper dirspacereport', 'r');
        $pipe_hdmodel = popen('/usr/bin/elastix-helper hdmodelreport', 'r');
        
        $fastgauge = $this->_getFastGraphics();

        // Recolectar la información de particiones
        $output = $retval = NULL;
        exec('/bin/df -P /etc/fstab', $output, $retval);
        $part = array();
        $regexp = "!^([/-_\.[:alnum:]|-]+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d{1,3}%)\s+([/-_\.[:alnum:]]+)$!";
        foreach ($output as $linea) {
            $regs = NULL;
            if (preg_match($regexp, $linea, $regs)) {
            	$particion = array(
                    'dispositivo'               =>  $regs[1],
                    'num_bloques_total'         =>  $regs[2],
                    'num_bloques_usados'        =>  $regs[3],
                    'punto_montaje'             =>  $regs[6],
                );
                $particion['porcentaje_usado'] = 100.0 * $particion['num_bloques_usados'] / $particion['num_bloques_total'];
                $particion['porcentaje_libre'] = 100.0 - $particion['porcentaje_usado'];
                $part[] = $particion;
            }
        }
        
        // Recolectar la información acumulada de modelos de partición
        while ($s = fgets($pipe_hdmodel)) {
            $s = trim($s); $l = explode(' ', $s, 2);
            if (count($l) > 1) $hdmodel[$l[0]] = $l[1];
        }
        pclose($pipe_hdmodel);
        
        // Combinar la información de modelo y generar HTML
        // TODO: mover esto a una plantilla
        foreach ($part as $particion) {
            $sEnlaceImagen = $fastgauge 
                ? $this->_htmldiskuse($particion['porcentaje_usado'] / 100.0, 140, 140)
                : $this->getImage_Disc_Usage($particion['porcentaje_usado']);
            $sTotalGB = number_format($particion['num_bloques_total'] / 1024 / 1024, 2);
            $sPorcentajeUsado = number_format($particion['porcentaje_usado'], 0);
            $sPorcentajeLibre = number_format($particion['porcentaje_libre'], 0);

            // Intentar determinar el modelo del disco que contiene la partición
            $sModelo = isset($hdmodel[$particion['dispositivo']]) ? $hdmodel[$particion['dispositivo']] : 'N/A';
            $content .= <<<PLANTILLA_DISCO
<div>
    $sEnlaceImagen
    <div class="neo-applet-hd-innerbox">
      <div class="neo-applet-hd-innerbox-top">
       <img src="modules/{$this->module_name}/images/light_usedspace.png" width="13" height="11" alt="used" /> {$sPorcentajeUsado}% Used &nbsp;&nbsp;<img src="modules/{$this->module_name}/images/light_freespace.png" width="13" height="11" alt="used" /> {$sPorcentajeLibre}% Available
      </div>
      <div class="neo-applet-hd-innerbox-bottom">
        <div><strong>Hard Disk Capacity:</strong> {$sTotalGB}GB</div>
        <div><strong>Mount Point:</strong> {$particion['punto_montaje']}</div>
        <div><strong>Manufacturer:</strong> $sModelo</div>
      </div>
    </div>
</div>
PLANTILLA_DISCO;

        // Lista de directorios a buscar
        $listaReporteDir = array(
            'logs'  =>  array(
                'dir'   =>  '/var/log',
                'tag'   =>  _tr('Logs'),
                'use'   =>  'N/A',
            ),
            'backups'  =>  array(
                'dir'   =>  '/var/www/backup',
                'tag'   =>  _tr('Local Backups'),
                'use'   =>  'N/A',
            ),
            'emails'  =>  array(
                'dir'   =>  '/var/spool/imap',
                'tag'   =>  _tr('Emails'),
                'use'   =>  'N/A',
            ),
            'config'  =>  array(
                'dir'   =>  '/etc',
                'tag'   =>  _tr('Configuration'),
                'use'   =>  'N/A',
            ),
            'voicemails'  =>  array(
                'dir'   =>  '/var/spool/asterisk/voicemail',
                'tag'   =>  _tr('Voicemails'),
                'use'   =>  'N/A',
            ),
            'recordings'  =>  array(
                'dir'   =>  '/var/spool/asterisk/monitor',
                'tag'   =>  _tr('Recordings'),
                'use'   =>  'N/A',
            ),
        );
        //foreach ($output as $s) {
        while ($s = fgets($pipe_dirspace)) {
            $s = trim($s); $l = explode(' ', $s);
            if (count($l) > 1 && isset($listaReporteDir[$l[0]]))
                $listaReporteDir[$l[0]]['use'] = $l[1];
        }
        pclose($pipe_dirspace);

        // Datos extra de directorios seleccionados
        $content .= <<<PLANTILLA_DIRECTORIOS
<div class="neo-divisor"></div>
<div class="neo-applet-hd-report-row"><div class="neo-applet-hd-report-row-left"><strong>{$listaReporteDir['logs']['tag']}:</strong> {$listaReporteDir['logs']['use']}</div>
<div class="neo-applet-hd-report-row-right"><strong>{$listaReporteDir['backups']['tag']}:</strong> {$listaReporteDir['backups']['use']}</div></div>
<div class="neo-applet-hd-report-row"><div class="neo-applet-hd-report-row-left"><strong>{$listaReporteDir['emails']['tag']}:</strong> {$listaReporteDir['emails']['use']}</div>
<div class="neo-applet-hd-report-row-right"><strong>{$listaReporteDir['config']['tag']}:</strong> {$listaReporteDir['config']['use']}</div></div>
<div class="neo-applet-hd-report-row"><div class="neo-applet-hd-report-row-left"><strong>{$listaReporteDir['voicemails']['tag']}:</strong> {$listaReporteDir['voicemails']['use']}</div>
<div class="neo-applet-hd-report-row-right"><strong>{$listaReporteDir['recordings']['tag']}:</strong> {$listaReporteDir['recordings']['use']}</div></div>
PLANTILLA_DIRECTORIOS;
        return $content;
        }
    }

    private function _htmldiskuse($percent, $width, $height)
    {
        $height_used = (int)($percent * 100.0);
        $height_free = 100 - $height_used;
        return <<<PLANTILLA_DIV
<div style="width: {$width}px; height: {$height}px;">
<div style="position: relative; left: 33%; width: 33%; background: #6e407e;  height: 100%; border: 1px solid #000000;">
<div style="position: relative; background: #3184d5; top: {$height_free}%; height: {$height_used}%">&nbsp;</div>
</div>
</div>
PLANTILLA_DIV;
    }

    function getDataApplet_PerformanceGraphic()
    {
        return "<div class='tabFormTable' style='text-align:center;'>".$this->getImage_Hit()."</div>";
    }

    function getDataApplet_News()
    {
        define('MAGPIE_CACHE_DIR', '/tmp/rss-cache');
        $infoRSS = @fetch_rss($this->arrConf['dir_RSS']);
        $sMensaje = magpie_error();
        if (preg_match("/HTTP Error: connection failed/", $sMensaje)) {
                return _tr('Could not get web server information. You may not have internet access or the web server is down');
        }
        $sContentList = '<div class="neo-applet-news-row">'._tr('No News to display').'</div>';
        if (!empty($infoRSS) && is_array($infoRSS->items) && count($infoRSS->items) > 0) {
                $sContentList = '';
            $sPlantilla = <<<PLANTILLA_RSS_ROW
<div class="neo-applet-news-row">
    <span class="neo-applet-news-row-date">%s</span>
    <a href="https://twitter.com/share?original_referer=%s&related=&source=tweetbutton&text=%s&url=%s&via=elastixGui"  target="_blank">
        <img src="modules/dashboard/images/twitter-icon.png" width="16" height="16" alt="tweet" />
    </a>
    <a href="%s" target="_blank">%s</a>
</div>
PLANTILLA_RSS_ROW;
            for ($i = 0; $i < 7 && $i < count($infoRSS->items); $i++) {
                $sContentList .= sprintf($sPlantilla,
                    date('Y.m.d', $infoRSS->items[$i]['date_timestamp']),
                    rawurlencode('http://www.elastix.org'),
                    rawurlencode(utf8_encode($infoRSS->items[$i]['title'])),
                    rawurlencode($infoRSS->items[$i]['link']),
                    $infoRSS->items[$i]['link'],
					htmlentities($infoRSS->items[$i]['title'], ENT_COMPAT));
            }
        }
        return $sContentList;
    }

    function getDataApplet_ProcessesStatus()
    {
        $oPalo = new paloSantoSysInfo();
        $arrServices = $oPalo->getStatusServices();

        $sMsgStart = _tr('Start process');
        $sMsgStop = _tr('Stop process');
        $sMsgRestart = _tr('Restart process');
	$sMsgActivate = _tr('Enable process');
	$sMsgDeactivate = _tr('Disable process');
        $sListaServicios = <<<PLANTILLA_POSICIONABLE
<div class="neo-applet-processes-menu">
<input type="hidden" id="neo_applet_selected_process" value="" />
<div id="neo-applet-processes-controles">
<input type="button" class="neo_applet_process" name="processcontrol_stop" id="neo_applet_process_stop" value="$sMsgStop" />
<input type="button" class="neo_applet_process" name="processcontrol_start" id="neo_applet_process_start" value="$sMsgStart" />
<input type="button" class="neo_applet_process" name="processcontrol_restart" id="neo_applet_process_restart" value="$sMsgRestart" />
<input type="button" class="neo_applet_process" name="processcontrol_activate" id="neo_applet_process_activate" value="$sMsgActivate" />
<input type="button" class="neo_applet_process" name="processcontrol_deactivate" id="neo_applet_process_deactivate" value="$sMsgDeactivate" />
</div>
<img id="neo-applet-processes-processing" src="modules/{$this->module_name}/images/loading.gif" style="display: none;" alt="" />
</div>
PLANTILLA_POSICIONABLE;

        $listaIconos = array(
            'Asterisk'  =>  'icon_pbx.png',
            'OpenFire'  =>  'icon_im.png',
            'Hylafax'   =>  'icon_fax.png',
            'Postfix'   =>  'icon_email.png',
            'MySQL'     =>  'icon_db.png',
            'Apache'    =>  'icon_www.png',
            'Dialer'    =>  'icon_headphones.png',
        );
        $sIconoDesconocido = 'system.png';
        $sPlantilla = <<<PLANTILLA_PROCESS_ROW
<div class="neo-applet-processes-row">
    <div class="neo-applet-processes-row-icon"><img src="modules/dashboard/images/%s" width="32" height="28" alt="%s" /></div>
    <div class="neo-applet-processes-row-name">%s</div>
    <div class="neo-applet-processes-row-menu">
        <input type="hidden" name="key-servicio" id="key-servicio" value="%s" />
        <input type="hidden" name="status-servicio" id="status-servicio" value="%s" />
        <input type="hidden" name="activate-process" id="activate-process" value="%s" />
        <img src="modules/dashboard/images/%s" style="cursor:%s;" width="15" height="15" alt="menu" />
    </div>
    <div class="neo-applet-processes-row-status-msg" style="color: %s">%s</div>
    <div class="neo-applet-processes-row-status-icon"></div></div>
PLANTILLA_PROCESS_ROW;
        // onclick="neoAppletProcesses_manejarMenu(this, '%s', '%s');">
        foreach ($arrServices as $sServicio => $infoServicio) {
            switch ($infoServicio['status_service']) {
            case 'OK':
                $sDescStatus = _tr('Running');
                $sColorStatus = '#006600';
                break;
            case 'Shutdown':
                $sDescStatus = _tr('Not running');
                $sColorStatus = '#880000';
                break;
            default:
                $sDescStatus = _tr('Not installed');
                $sColorStatus = '#000088';
                break;
            }
            $sListaServicios .= sprintf($sPlantilla,
                isset($listaIconos[$sServicio]) ? $listaIconos[$sServicio] : $sIconoDesconocido,
                $sServicio,
                _tr($infoServicio['name_service']),
                $sServicio,
                $infoServicio['status_service'],
		$infoServicio['activate'],
                (in_array($infoServicio['status_service'], array('OK', 'Shutdown'))) ? 'icon_arrowdown.png' : 'icon_arrowdown-disabled.png',
		(in_array($infoServicio['status_service'], array('OK', 'Shutdown'))) ? 'pointer' : '',
                $sColorStatus,
                strtoupper($sDescStatus));
        }
        return $sListaServicios;
    }

    function getDataApplet_TelephonyHardware()
    {
        $oPalo = new paloSantoSysInfo();
        $arrCards = $oPalo->checkRegistedCards();
        $str = "";
        $cardsStatus = "";
        $color = "";
        $i = 1;
        if(count($arrCards)>0 && $arrCards!=null){
            foreach($arrCards as $key=>$value){
                if($value["num_serie"]==""){
                    $serStatus = "<a id='editMan1_$value[hwd]' style='text-decoration:none;color:white; cursor:pointer;' onClick ='jfunction(\"editMan1_$value[hwd]\");'>"._tr('No Registered')."</a>";
                    $color = "#FF0000";
                    $image = "modules/hardware_detector/images/card_no_registered.gif";
                }
                else{
                    $serStatus = "<a id='editMan2_$value[hwd]' style='text-decoration:none;color:white;cursor:pointer;' onClick = 'jfunction(\"editMan2_$value[hwd]\");'>"._tr('Registered')."</a>";
                    $color = "#10ED00";
                    $image = "modules/hardware_detector/images/card_registered.gif";
                }
                $cardsStatus .= "<div class='services'>$i.-&nbsp;".$value['card']." ($value[vendor]): &nbsp;&nbsp; </div>
                                <div align='center' style='background-color:".$color.";' class='status' >$serStatus</div>";
                $i++;
            }
        }else{
            $cardsStatus="<br /><div align='center' style='color:red;'><strong>"._tr('Cards no found')."</strong></div>";
        }
        return "<div class='tabFormTable'>$cardsStatus</div>
                    <div id='layerCM' style='position:relative'>
                        <div class='layer_handle' id='closeCM'></div>
                        <div id='layerCM_content'></div>
                    </div>";
    }

    function getDataApplet_CommunicationActivity()
    {
        $oPalo = new paloSantoSysInfo();
        $channels = $oPalo->getAsterisk_Channels();
        $queues = $oPalo->getAsterisk_QueueWaiting();
        $connections = $oPalo->getAsterisk_Connections();
        $network = $oPalo->getNetwork_TrafficAverage();
        $total = $channels['total_calls'];
        $internal = $channels['internal_calls'];
        $external = $channels['external_calls'];
        $channel = $channels['total_channels'];
        $totalQueues = 0;
        // sum queues
        foreach($queues as $key=>$value){
            $totalQueues += $value;
        }

    //     if($total == 1)  $total = $total." ".$arrLang['call'];
    //   else   $total = $total." ".$arrLang['calls'];

        if($internal == 1) $internal = $internal;
        else   $internal = $internal;

        if($external == 1) $external = $external;
        else   $external = $external;

        if($channel == 1)  $channel = $channel." "._tr('channel');
        else   $channel = $channel." "._tr('channels');

    //// asterisk connection
        $sip_Ext_ok  = $connections['sip']['ext']['ok'];
        $sip_Ext_nok = $connections['sip']['ext']['no_ok'];
        $total_sip_Ext = $sip_Ext_ok + $sip_Ext_nok;

        $sip_trunk_ok  = $connections['sip']['trunk']['ok'];
        $sip_trunk_nok = $connections['sip']['trunk']['no_ok'];
        $sip_trunk_unk = $connections['sip']['trunk']['unknown'];
        $total_sip_trunk = $sip_trunk_ok + $sip_trunk_nok + $sip_trunk_unk;

        //$sip_trunk_reg_ok = $connections['sip']['trunk_registry']['ok'];
        //$sip_trunk_reg_nok= $connections['sip']['trunk_registry']['no_ok'];
        //$total_sip_trunk_reg = $sip_trunk_reg_ok + $sip_trunk_reg_nok;

        $iax_Ext_ok  = $connections['iax']['ext']['ok'];
        $iax_Ext_nok = $connections['iax']['ext']['no_ok'];
        $total_iax_Ext = $iax_Ext_ok + $iax_Ext_nok;

        $iax_trunk_ok  = $connections['iax']['trunk']['ok'];
        $iax_trunk_nok = $connections['iax']['trunk']['no_ok'];
        $iax_trunk_unk = $connections['iax']['trunk']['unknown'];
        $total_iax_trunk = $iax_trunk_ok + $iax_trunk_nok + $iax_trunk_unk;

        //$iax_trunk_reg_ok = $connections['iax']['trunk_registry']['ok'];
        //$iax_trunk_reg_nok= $connections['iax']['trunk_registry']['no_ok'];
        //$total_iax_trunk_reg = $iax_trunk_reg_ok + $iax_trunk_reg_nok;

        $total_trunks_ok  = $sip_trunk_ok  + $iax_trunk_ok;
        $total_trunks_nok = $sip_trunk_nok + $iax_trunk_nok;
        $total_trunks_unk = $sip_trunk_unk + $iax_trunk_unk;
        //$total_trunks_reg_ok = $sip_trunk_reg_ok + $iax_trunk_reg_ok;
        //$total_trunks_reg_nok = $sip_trunk_reg_nok + $iax_trunk_reg_nok;
        $total_trunks = $total_sip_trunk + $total_iax_trunk;
        //$total_trunks_reg = $total_trunks_reg_ok + $total_trunks_reg_nok;
        ///////traffic network
        $rx_bytes = $network['rx_bytes'];
        $tx_bytes = $network['tx_bytes'];
        $rx_packets = $network['rx_packets'];
        $tx_packets = $network['tx_packets'];
        return "<div class='tabFormTable'>
                        <div class='infoActivity'>
                            <div class='typeActivity'>
                                <b>"._tr('Total_calls').": </b>
                            </div>
                            <div align='left' class='detailText'>
                                "._tr('Calls')." <b>($total)</b> :
                                <font color='green'>($internal "._tr('internal_calls').")</font> <font color='red'> ($external "  ._tr('external_calls').")</font>
                            </div>
                            <div class='typeActivity'>
                                <b>"._tr('total_channels').": </b>
                            </div>
                            <div align='left' class='detailActivity'>".$channel."</div>
                            <div class='typeActivity'>
                                <b>"._tr('Queues_waiting').": </b>
                            </div>
                            <div align='left' class='detailActivity'>".$totalQueues." "._tr('Waiting')."</div>
                            <div class='typeActivity'><b>"._tr('Extensions').": </b></div>
                            <div class='detailText'>"._tr('sip_extensions')." <b>($total_sip_Ext) </b>: <font color='green'>($sip_Ext_ok "._tr('OK').")</font> <font color='red'>($sip_Ext_nok "._tr('NO_OK').")</font></div>
                            <div class='typeActivity'></div>
                            <div class='detailText'>"._tr('iax_extensions')." <b>($total_iax_Ext) </b>: <font color='green'>($iax_Ext_ok "._tr('OK').")</font> <font color='red'>($iax_Ext_nok "._tr('NO_OK').")</font></div>
                            <div class='typeActivity'><b>"._tr('Trunks')." (SIP/IAX): </b></div>
                            <div class='detailText'>"._tr('Trunks')." <b>($total_trunks) </b>: <font color='green'>($total_trunks_ok "._tr('OK').")</font> <font color='red'>($total_trunks_nok "._tr('NO_OK').")</font> </font> <font color='gray'>($total_trunks_unk "._tr('Unknown').")</font></div>".
                            "<div class='typeActivity'><b>"._tr('Network_traffic').": </b></div>
                            <div class='detailText'>"._tr('Bytes')." <b>(".$rx_bytes."kB/s)</b> <= RX | TX =>  <b>(".$tx_bytes."kB/s)</b></div>
                        </div>
                    </div>";
    }

    function getDataApplet_SystemResources()
    {
        $oPalo = new paloSantoSysInfo();
        $fastgauge = $this->_getFastGraphics();

        $cpu_a = $oPalo->obtener_muestra_actividad_cpu();
        
        $cpuinfo = $oPalo->getCPUInfo();
        $speed = number_format($cpuinfo['CpuMHz'], 2)." MHz";
        $cpu_info = $cpuinfo['CpuModel'];
        
        $meminfo = $oPalo->getMemInfo();

        //MEMORY USAGE
        $fraction_mem_used = ($meminfo['MemTotal'] - $meminfo['MemFree'] - $meminfo['Cached'] - $meminfo['MemBuffers']) / $meminfo['MemTotal'];
        $mem_usage_val  = number_format(100.0 * $fraction_mem_used, 1);
        $mem_usage = $fastgauge
            ? $this->_htmlgauge($fraction_mem_used, 140, 140) 
            : $this->genericImage('rbgauge', array(
                'percent' => $fraction_mem_used,
                'size' => '140,140'),
                null, null);
        $inf2 = number_format($meminfo['MemTotal']/1024, 2)." Mb";

        //SWAP USAGE
        $fraction_swap_used = ($meminfo['SwapTotal'] - $meminfo['SwapFree']) / $meminfo['SwapTotal'];
        $swap_usage_val = number_format(100.0 * $fraction_swap_used, 1);
        $swap_usage = $fastgauge
            ? $this->_htmlgauge($fraction_swap_used, 140, 140) 
            : $this->genericImage('rbgauge', array(
                'percent' => $fraction_swap_used,
                'size' => '140,140'),
                null, null);
        $inf3 = number_format($meminfo['SwapTotal']/1024, 2)." Mb";

        //UPTIME
        $upfields = array();
        $up = $oPalo->getUptime(); // Segundos de actividad desde arranque
        $upfields[] = $up % 60; $up = ($up - $upfields[0]) / 60;
        $upfields[] = $up % 60; $up = ($up - $upfields[1]) / 60;
        $upfields[] = $up % 24; $up = ($up - $upfields[2]) / 24;
        $upfields[] = $up;
        
        $uptime = $upfields[1].' '._tr('minute(s)');
        if ($upfields[2] > 0) $uptime = $upfields[2].' '._tr('hour(s)').' '.$uptime;
        if ($upfields[3] > 0) $uptime = $upfields[3].' '._tr('day(s)').' '.$uptime;
        
        usleep(200000);
        $cpu_b = $oPalo->obtener_muestra_actividad_cpu();
        $cpu_percent = calcular_carga_cpu_intervalo($cpu_a, $cpu_b);
        $cpu_usage = $fastgauge
            ? $this->_htmlgauge($cpu_percent, 140, 140) 
            : $this->genericImage('rbgauge', array(
                'percent' => $cpu_percent,
                'size' => '140,140'),
                null, null);
        $inf1 = number_format($cpu_percent * 100.0, 1);

        $html ="<div style='height:165px; position:relative; text-align:center;'>
                          <div style='width:155px; float:left; position: relative;'>
                                $cpu_usage
                                <div class=\"neo-applet-sys-gauge-percent\">$inf1%</div><div>"._tr('CPU')."</div>
                          </div>
                          <div style='width:154px; float:left; position: relative;'>
                                $mem_usage
                                <div class=\"neo-applet-sys-gauge-percent\">$mem_usage_val%</div><div>"._tr('RAM')."</div>
                          </div>
                          <div style='width:155px; float:right; position: relative;'>
                                $swap_usage
                          <div class=\"neo-applet-sys-gauge-percent\">$swap_usage_val%</div><div>"._tr('SWAP')."</div>
                          </div>
                        </div>
                        <div class='neo-divisor'></div>
                        <div class=neo-applet-tline>
                          <div class='neo-applet-titem'><strong>"._tr('CPU Info').":</strong></div>
                          <div class='neo-applet-tdesc'>$cpu_info</div>
                        </div>
                        <div class=neo-applet-tline>
                          <div class='neo-applet-titem'><strong>"._tr('Uptime').":</strong></div>
                          <div class='neo-applet-tdesc'>$uptime</div>
                        </div>
                        <div class='neo-applet-tline'>
                          <div class='neo-applet-titem'><strong>"._tr('CPU Speed').":</strong></div>
                          <div class='neo-applet-tdesc'>$speed</div>
                        </div>
                        <div class='neo-applet-tline'>
                          <div class='neo-applet-titem'><strong>"._tr('Memory usage').":</strong></div>
                          <div class='neo-applet-tdesc'>RAM: $inf2 SWAP: $inf3</div>
                        </div>";
        return $html;
    }

    private function _htmlgauge($percent, $width, $height)
    {
        if ($percent > 1) $percent = 1.0;
        if ($percent < 0.25)
            $rgb = array(0, $percent * 4, 1.0);
        elseif ($percent < 0.5)
            $rgb = array(0, 1.0, (1.0 - ($percent - 0.25) * 4));
        elseif ($percent < 0.75)
            $rgb = array(($percent - 0.5) * 4, 1.0, 0);
        else
            $rgb = array(1.0, (1.0 - ($percent - 0.75) * 4), 0);
        $color = sprintf('#%02x%02x%02x', (int)($rgb[0] * 255), (int)($rgb[1] * 255), (int)($rgb[2] * 255));

        $height_used = (int)($percent * 100.0);
        $height_free = 100 - $height_used;
        return <<<PLANTILLA_DIV
<div style="width: {$width}px; height: {$height}px;">
<div style="position: relative; left: 33%; width: 33%; background: #FFFFFF;  height: 100%; border: 1px solid #000000;">
<div style="position: relative; background: {$color}; top: {$height_free}%; height: {$height_used}%">&nbsp;</div>
</div>
</div>
PLANTILLA_DIV;
    }

    function getDataApplet_Faxes()
    {
        $faxRows = _tr("Error at read yours faxes.");

        $pDB2 = $this->conectionAsteriskCDR();
        if($pDB2){
            $objUserInfo = new paloSantoDashboard($pDB2);
            $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);

            if(is_array($arrData) && count($arrData)>0){
                $extension = isset($arrData['extension'])?$arrData['extension']:"";
                $numRegs   = 8;
                $faxRows   = $objUserInfo->getLastFaxes($extension,$numRegs);
            }
        }
        return $faxRows;
    }

    function getDataApplet_System()
    {
        global $arrConf;
        $systemStatus=_tr("Error at read status system.");

        $pDB2 = $this->conectionAsteriskCDR();
        if($pDB2){
            $objUserInfo = new paloSantoDashboard($pDB2);
            $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);

            if(is_array($arrData) && count($arrData)>0){
                if(isset($arrData['login']) && $arrData['login'] != "" && isset($arrData['domain']) && $arrData['domain'] != ""){
                    $email     = "{$arrData['login']}@{$arrData['domain']}";
                    if(file_exists("$arrConf[elastix_dbdir]/email.db")){
                        $pDBemail = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/email.db");
                        $passw     = isset($arrData['password'])?$arrData['password']:"";
                        if($this->emailExists($email,$pDBemail) && $this->isPasswordCorrect($email,$passw,$pDBemail)){
                            $systemStatus= $objUserInfo->getSystemStatus($email,$passw);
                        }
                        else
                            $systemStatus = "$email "._tr("does not exist locally or password is incorrect");
                    }
                    else
                        $systemStatus = _tr("The following database could not be found").": $arrConf[elastix_dbdir]/email.db";
                }
                else
                    $systemStatus = _tr("You don't have a webmail account");
            }
        }
        return $systemStatus;
    }

    function getDataApplet_Calls()
    {
        $callsRows   =_tr("Error at read yours calls.");
        $pDB2 = $this->conectionAsteriskCDR();
        if($pDB2){
            $objUserInfo = new paloSantoDashboard($pDB2);
            $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);

            if(is_array($arrData) && count($arrData)>0){
                $extension = isset($arrData['extension'])?$arrData['extension']:"";
                $numRegs   = 8;
                $callsRows   = $objUserInfo->getLastCalls($extension,$numRegs);
            }
        }
        return $callsRows;
    }

    function getDataApplet_Calendar()
    {
        $eventsRows  =_tr("Error at read your calendar.");
        $pDB2 = $this->conectionAsteriskCDR();
        if($pDB2){
            $objUserInfo = new paloSantoDashboard($pDB2);
            $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);

            if(is_array($arrData) && count($arrData)>0){
                $numRegs   = 8;
                $eventsRows  = $objUserInfo->getEventsCalendar($arrData['id'], $numRegs);
            }
        }
        return $eventsRows;
    }

    function getDataApplet_Emails()
    {
        global $arrConf;
        $mails =_tr("Error at read yours mails.");
        $pDB2 = $this->conectionAsteriskCDR();

        if($pDB2){
            $objUserInfo = new paloSantoDashboard($pDB2);
            $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);
            if(is_array($arrData) && count($arrData)>0){
                if(isset($arrData['login']) && $arrData['login'] != "" && isset($arrData['domain']) && $arrData['domain'] != ""){
                    $email     = "{$arrData['login']}@{$arrData['domain']}";
                    if(file_exists("$arrConf[elastix_dbdir]/email.db")){
                          $pDBemail = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/email.db");
                          $passw    = isset($arrData['password'])?$arrData['password']:"";
                          if($this->emailExists($email,$pDBemail) && $this->isPasswordCorrect($email,$passw,$pDBemail)){
                              $numRegs   = 8;
                              $mails     = @$objUserInfo->getMails($email,$passw,$numRegs);
                          }
                          else
                              $mails = "$email "._tr("does not exist locally or password is incorrect");
                    }
                    else
                        $mails = _tr("The following database could not be found").": $arrConf[elastix_dbdir]/email.db";
                }
                else
                    $mails = _tr("You don't have a webmail account");
            }
        }
        return $mails;
    }

    function emailExists($email,&$pDB)
    {
        $query = "select count(*) from accountuser where username=?";
        $result = $pDB->getFirstRowQuery($query,false,array($email));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($result[0] > 0)
            return true;
        else
            return false;
    }

    function isPasswordCorrect($email,$password,&$pDB)
    {
        $query = "select password from accountuser where username=?";
        $result = $pDB->getFirstRowQuery($query,true,array($email));
        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($password == $result["password"])
            return true;
        else
            return false;
    }

    function getDataApplet_Voicemails()
    {
        $voiceMails  =_tr("Error at read yours voicemails.");
        $pDB2 = $this->conectionAsteriskCDR();
        if($pDB2){
            $objUserInfo = new paloSantoDashboard($pDB2);
            $arrData     = $objUserInfo->getDataUserLogon($_SESSION["elastix_user"]);

            if(is_array($arrData) && count($arrData)>0){
                $extension = isset($arrData['extension'])?$arrData['extension']:"";
                $numRegs   = 8;
                $voiceMails  = $objUserInfo->getVoiceMails($extension,$numRegs);
            }
        }
        return $voiceMails;
    }

    function drawApplet($idApplet, $code)
    {
        $icon = $this->getIcon();
        $title = $this->getTitle();
        return  "<div class='portlet' id='applet-{$code}-{$idApplet}'>
                    <div class='portlet_topper'>
                        <div class='tabapplet' width='80%' style='float:left;'>
                            $title
                        </div>
                        <div class='closeapplet' align='right' width='10%'>
                            <a id='refresh_{$code}' style='cursor: pointer;' class='toggle' onclick='javascript:refresh(this)'>
                                <img id='imga11'  class='ima'  src='modules/{$this->module_name}/images/reload.png' border='0' align='absmiddle' />
                            </a>
                        </div>
                    </div>
                    <div class='portlet_content' id = '$code'>
                        <img class='ima' src='modules/{$this->module_name}/images/loading.gif' border='0' align='absmiddle' />&nbsp;
                        "._tr('Loading')."
                    </div>
                </div>";
    }

   function genericImage($sGraph, $extraParam = array(), $w = NULL, $h = NULL)
   {
         return sprintf('<img alt="%s" src="%s" %s %s />',
             $sGraph,
             construirURL(array_merge(array(
                  'menu'      => $this->module_name,
                  'action'    =>  'image',
                  'rawmode'   =>  'yes',
                  'image'     =>  $sGraph,
                   ), $extraParam)),
               is_null($w) ? '' : "width=\"$w\"",
               is_null($h) ? '' : "height=\"$w\""
               );
   }

    function getImage_Disc_Usage($value)
    {
        return $this->genericImage("ObtenerInfo_Particion", array('percent' => $value), 140, NULL);
    }

    function getImage_Hit()
    {
        return $this->genericImage("CallsMemoryCPU");
    }

    function conectionAsteriskCDR()
    {
        $dsnAsteriskCDR = generarDSNSistema("asteriskuser","asteriskcdrdb");
        $pDB = new paloDB($dsnAsteriskCDR);

        if(!empty($pDB->errMsg))
            return false;
        else
            return $pDB;
    }

    function getIcon()
    {
        return $this->icon;
    }

    function getTitle()
    {
        return $this->title;
    }

    function setIcon($icon)
    {
        $this->icon = $icon;
    }

    function setTitle($title)
    {
        $this->title = $title;
    }
}

?>
