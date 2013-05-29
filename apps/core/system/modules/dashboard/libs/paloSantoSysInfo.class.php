<?php
global $arrConf;
require_once "{$arrConf['basePath']}/libs/misc.lib.php";
require_once "{$arrConf['basePath']}/libs/paloSantoDB.class.php";
require_once "{$arrConf['basePath']}/libs/paloSantoSampler.class.php";
require_once "{$arrConf['basePath']}/libs/paloSantoTrunk.class.php";
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

class paloSantoSysInfo
{
    private $_initscript_cache = NULL;
    
    function getSysInfo()
    {
        return obtener_info_de_sistema();
    }

    function getMemInfo()
    {
        $arrInfo = array(
            'MemTotal'      =>  0,
            'MemFree'       =>  0,
            'MemBuffers'    =>  0,
            'SwapTotal'     =>  0,
            'SwapFree'      =>  0,
            'Cached'        =>  0,
        );
        foreach (file('/proc/meminfo') as $linea) {
            $regs = NULL;
            if (preg_match('/^(\w+):\s+(\d+) kB/', $linea, $regs)) {
                if (isset($arrInfo[$regs[1]])) $arrInfo[$regs[1]] = $regs[2];
            }
        }
        return $arrInfo;
    }

    function getCPUInfo()
    {
        $arrInfo = array(
            'CpuModel'      =>  '(unknown)',
            'CpuVendor'     =>  '(unknown)',
            'CpuMHz'        =>  0.0,
        );
        foreach (file('/proc/cpuinfo') as $linea) {
            $regs = NULL;
            if (preg_match('/^(.+?)\s*:\s*(.+)/', $linea, $regs)) {
                $regs[1] = trim($regs[1]);
                $regs[2] = trim($regs[2]);
                if ($regs[1] == 'model name' || $regs[1] == 'Processor')
                    $arrInfo['CpuModel'] = $regs[2];
                if ($regs[1] == 'vendor_id')
                    $arrInfo['CpuVendor'] = $regs[2];
                if ($regs[1] == 'cpu MHz')
                    $arrInfo['CpuMHz'] = $regs[2];
            }
        }
        return $arrInfo;
    }
    
    function getUptime()
    {
        $btime = NULL;
        foreach (file('/proc/stat') as $linea) {
            if (strpos($linea, 'btime ') === 0) {
                $t = explode(' ', $linea);
                $btime = $t[1];
                break;
            }
        }
        return $this->_info_sistema_diff_stat($btime, time());
    }

    function obtener_muestra_actividad_cpu()
    {
        if (!function_exists('_info_sistema_linea_cpu')) {
            function _info_sistema_linea_cpu($s) { return (strpos($s, 'cpu ') === 0); }
        }
        $muestra = preg_split('/\s+/',
            array_shift(array_filter(file('/proc/stat', FILE_IGNORE_NEW_LINES),
                '_info_sistema_linea_cpu')));
        array_shift($muestra);
        return $muestra;
    }

    function calcular_carga_cpu_intervalo($m1, $m2)
    {
        $diffmuestra = array_map(array($this, '_info_sistema_diff_stat'), $m1, $m2);
        $cpuActivo = $diffmuestra[0] + $diffmuestra[1] + $diffmuestra[2] + $diffmuestra[4] + $diffmuestra[5] + $diffmuestra[6];
        $cpuTotal = $cpuActivo + $diffmuestra[3];
        return ($cpuTotal > 0) ? $cpuActivo / $cpuTotal : 0;
    }

    /* Método para poder realizar la resta de dos cantidades enteras que pueden
     * no caber en un entero de PHP, pero cuya diferencia es pequeña y puede 
     * caber en el mismo entero. */
    private function _info_sistema_diff_stat($a, $b)
    {
        $aa = str_split("$a");
        $bb = str_split("$b");
        while (count($aa) < count($bb)) array_unshift($aa, '0');
        while (count($aa) > count($bb)) array_unshift($bb, '0');
        while (count($aa) > 0 && $aa[0] == $bb[0]) {
            array_shift($aa);
            array_shift($bb);
        }
        if (count($aa) <= 0) return 0;
        $a = implode('', $aa); $b = implode('', $bb);
        return (int)$b - (int)$a;
    }
    

    function ObtenerInfo_Particion($value)
    {
        $result = array();

        $result['ATTRIBUTES'] = array('TITLE'=>'','TYPE'=>'plot3d2','SIZE'=>"220,100",'POS_LEYEND' => "0.06,0.3", "COLOR" => "#fafafa", "SIZE_PIE" => "50", "MARGIN_COLOR" => "#fafafa");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $arrTemp = array();
        for($i=1; $i<=2; $i++){
            $data = array();
            $data['VALUES'] = ($i==1) ? array('VALUE'=>$value) : array('VALUE'=>100-$value);
            $data['STYLE'] = array('COLOR'=> ($i==1) ? '#3184d5' : '#6e407e','LEYEND'=> ($i==1) ? 'Used' : 'Free');
            $arrTemp["DAT_$i"] = $data;
        }

        $result['DATA'] = $arrTemp;

        return $result;

    }

    function rbgauge($value, $size = "90,20")
    {
        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'gauge','SIZE'=>$size);  // bar => gauge
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$value));
        $result['DATA'] = $temp;

        return $result;
    }

	 function ObtenerInfo_Asterisk_Channel_internalCalls()
    {
        //external_calls] => 0 [internal_calls] => 0 [total_calls] => 0 [total_channels]
		  $values = $this->getAsterisk_Channels();
		  $total = $values['total_calls'];
		  $internalCalls = $values['internal_calls'];
		  if($total!=0)
		  		$valor = $internalCalls * 100 / $total;
		  else
			   $valor = 0.0;
        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,10");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$valor));
        $result['DATA'] = $temp;
        return $result;
    }

	 function ObtenerInfo_Asterisk_Channel_totalChannels()
    {
        //external_calls] => 0 [internal_calls] => 0 [total_calls] => 0 [total_channels]
		  $values = $this->getAsterisk_Channels();
		  $total = $values['total_channels'];
		  if($total!=0)
		  		$valor = $total * 100 / $total;
		  else
			   $valor = 0.0;
        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,10");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$valor));
        $result['DATA'] = $temp;
        return $result;
    }

	 function ObtenerInfo_Asterisk_Channel_totalCalls()
    {
        //external_calls] => 0 [internal_calls] => 0 [total_calls] => 0 [total_channels]
		  $values = $this->getAsterisk_Channels();
		  $total = $values['total_calls'];
		  $internalCalls = $values['internal_calls'];
		  if($total!=0)
		  		$valor = $total * 100 / $total;
		  else
			   $valor = 0.0;
        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,10");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$valor));
        $result['DATA'] = $temp;

        return $result;
    }

	 function ObtenerInfo_Asterisk_Channel_externalCalls()
    {
        //external_calls] => 0 [internal_calls] => 0 [total_calls] => 0 [total_channels]
		  $values = $this->getAsterisk_Channels();
		  $total = $values['total_calls'];
		  $internalCalls = $values['external_calls'];
		  if($total!=0)
		  		$valor = $internalCalls * 100 / $total;
		  else
			   $valor = 0.0;
        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,10");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$valor));
        $result['DATA'] = $temp;

        return $result;
    }

    function CallsMemoryCPU()
    {
        $arrayResult = array();

        $oSampler = new paloSampler();

        //retorna
        //Array ( [0] => Array ( [id] => 1 [name] => Sim. calls [color] => #00cc00 [line_type] => 1 )
        $arrLines = $oSampler->getGraphLinesById(1);

        //retorna
        //Array ( [name] => Simultaneous calls, memory and CPU )
        $arrGraph = $oSampler->getGraphById(1);

        $endtime = time();
        $starttime = $endtime - 26*60*60;
        $oSampler->deleteDataBeforeThisTimestamp($starttime);

        $arrayResult['ATTRIBUTES'] = array('TITLE' => $arrGraph['name'],'TYPE'=>'lineplot_multiaxis',
            'LABEL_X'=>"Etiqueta X",'LABEL_Y'=>'Etiqueta Y','SHADOW'=>false,'SIZE'=>"450,260",'MARGIN'=>"50,110,30,120",
            'COLOR' => "#fafafa",'POS_LEYEND'=> "0.35,0.85");

        $arrayResult['MESSAGES'] = array('ERROR' => 'Error', 'NOTHING_SHOW' => _tr('Nothing to show yet'));

        //$oSampler->getSamplesByLineId(1)
        //retorna
        //Array ( [0] => Array ( [timestamp] => 1230562202 [value] => 2 ), ....... 

        $i = 1;
        $arrData = array();
        foreach($arrLines as $num => $line)
        {
            $arraySample = $oSampler->getSamplesByLineId($line['id']);

            $arrDat_N = array();

            $arrValues = array();
            foreach( $arraySample as $num => $time_value )
                $arrValues[ $time_value['timestamp'] ] = (int)$time_value['value'];

            $arrStyle = array();
            $arrStyle['COLOR'] = $line['color'];
            $arrStyle['LEYEND'] = $line['name'];
            $arrStyle['STYLE_STEP'] = true;
            $arrStyle['FILL_COLOR'] = ($i==1)?true:false;

            $arrDat_N["VALUES"] = $arrValues;
            $arrDat_N["STYLE"] = $arrStyle;

            if(count($arrValues)>1)
		$arrData["DAT_$i"] = $arrDat_N;
	    else
		$arrData["DAT_$i"] = array();

            $i++;
        }
        $arrayResult['DATA'] = $arrData;

        return $arrayResult;
    }

    function functionCallback($value)
    {
        return Date('H:i', $value);
    }

    function _isActivate($process)
    {
        if (!is_array($this->_initscript_cache)) {
            $this->_initscript_cache = array();
            
            // Esta lista asume systemd
            foreach (glob('/etc/systemd/system/multi-user.target.wants/*.service') as $path) {
                $regs = NULL;
                if (preg_match('|([^/]+)\.service$|', $path, $regs))
                    $this->_initscript_cache[] = $regs[1];
            }
            
            // Esta lista asume scripts SysV
            foreach (glob('/etc/rc3.d/S*') as $path) {
                $regs = NULL;
            	if (preg_match('|/S\d+(\S+)$|', $path, $regs))
                    $this->_initscript_cache[] = $regs[1];
            }
        }
        return in_array($process, $this->_initscript_cache) ? 1 : 0;
    }

    function getStatusServices()
    {   // file pid service asterisk    is /var/run/asterisk/asterisk.pid
        // file pid service openfire    is /var/run/openfire.pid
        // file pid service hylafax     no founded but name services are hfaxd and faxq
        // file pid service iaxmodem    is /var/run/iaxmodem.pid
        // file pid service postfix     is /var/spool/postfix/pid/master.pid (can't to access to file by own permit,is better to use by CMD the serviceName is master)
        // file pid service mysql       is /var/run/mysqld/mysqld.pid (can't to access to file by own permit,is better to use by CMD the serviceName is mysqld)
        // file pid service apache      is /var/run/httpd.pid
        // file pid service call_center is /opt/elastix/dialer/dialerd.pid

        $arrSERVICES["Asterisk"]["status_service"] = $this->_existPID_ByFile("/var/run/asterisk/asterisk.pid","asterisk");
        $arrSERVICES["Asterisk"]["activate"] = $this->_isActivate("asterisk");
        $arrSERVICES["Asterisk"]["name_service"]   = "Telephony Service";

        $arrSERVICES["OpenFire"]["status_service"] = $this->_existPID_ByFile("/var/run/openfire.pid","openfire");
        $arrSERVICES["OpenFire"]["activate"] = $this->_isActivate("openfire");
        $arrSERVICES["OpenFire"]["name_service"]   = "Instant Messaging Service";

        $arrSERVICES["Hylafax"]["status_service"]  = $this->getStatusHylafax();
        $arrSERVICES["Hylafax"]["activate"] 	   = $this->_isActivate("hylafax");
        $arrSERVICES["Hylafax"]["name_service"]    = "Fax Service";
/*
        $arrSERVICES["IAXModem"]["status_service"] = $this->_existPID_ByFile("/var/run/iaxmodem.pid","iaxmodem");
        $arrSERVICES["IAXModem"]["name_service"]   = "IAXModem Service";
*/
        $arrSERVICES["Postfix"]["status_service"]  = $this->_existPID_ByCMD("master","postfix");
        $arrSERVICES["Postfix"]["activate"] 	   = $this->_isActivate("postfix");
        $arrSERVICES["Postfix"]["name_service"]    = "Email Service";

        $arrSERVICES["MySQL"]["status_service"]    = $this->_existPID_ByCMD("mysqld","mysqld");
        $arrSERVICES["MySQL"]["activate"] 	   = $this->_isActivate("mysqld");
        $arrSERVICES["MySQL"]["name_service"]      = "Database Service";

        $arrSERVICES["Apache"]["status_service"]   = $this->_existPID_ByCMD('httpd',"httpd");
        $arrSERVICES["Apache"]["activate"] 	   = $this->_isActivate("httpd");
        $arrSERVICES["Apache"]["name_service"]     = "Web Server";

        $arrSERVICES["Dialer"]["status_service"]   = $this->_existPID_ByFile("/opt/elastix/dialer/dialerd.pid","elastixdialer");
        $arrSERVICES["Dialer"]["activate"] 	   = $this->_isActivate("elastixdialer");
        $arrSERVICES["Dialer"]["name_service"]     = "Elastix Call Center Service";

        return $arrSERVICES;
    }

    function getStatusTrunks()
    {
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/hardware_detector.db";
        $pDB  = new paloDB($dsn);

        $query   = "select c.id_card, c.additonal, cp.num_serie from card c left join card_parameter cp on c.id_card = cp.id_card;";
        $result=$pDB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $pDB->errMsg;
            return array();
        }

        return $result;
    }

    function getCardsTelephony()
    {
        $arrDATA = array();
        exec("/usr/sbin/dahdi_hardware",$arrConsle,$flatStatus);
        if($flatStatus==0){
            foreach($arrConsle as $k => $v){
                if(preg_match("/([a-z0-9\:\.\-\_]*)[[:space:]]+([a-z0-9\:\.\-\_\+]*)[[:space:]]+([a-z0-9\:\.\-\_]*) (.*)/",$v,$arrReg)){
                    $arrDATA[] = array("hwd" => $arrReg[1], "module" => $arrReg[2], "vendor" => $arrReg[3], "card" => $arrReg[4], "num_serie" => "");
                }
            }
        }
        return $arrDATA;
    }

    function checkRegistedCards()
    {
        //Verifica si las tarjetas que son listadas por dahdi_hardware (systema) se encuentran en la base de datos
        //hardware_detector.db, si no estan lo unico que hace es aumentarla o agregarla, se valida por hwd, vendor
        //y card o data.
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/hardware_detector.db";
        $pDB  = new paloDB($dsn);

        $arrDATA = $this->getCardsTelephony();

        foreach($arrDATA as $k => $v){
            $query = "select count(*) existe from car_system where hwd='$v[hwd]' and data='$v[card]'";
            $result=$pDB->getFirstRowQuery($query, true);
            if($result['existe']==1){
                $query = "select num_serie from car_system where hwd='$v[hwd]' and data='$v[card]'";
                $result=$pDB->getFirstRowQuery($query, true);
                $arrDATA[$k]['num_serie'] = isset($result['num_serie'])?$result['num_serie']:"";
            }
            else{
                $query = "insert into car_system (hwd,module,vendor,num_serie,data) values ('$v[hwd]','$v[module]','$v[vendor]','','$v[card]');";
                $result=$pDB->genQuery($query);
            }
        }
        return $arrDATA;
    }

    function registerCard($hwd, $num_serie, $vendor)
    {
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/hardware_detector.db";
        $pDB  = new paloDB($dsn);

        $query = "update car_system set num_serie='$num_serie', vendor='$vendor' where hwd='$hwd' and num_serie='' and vendor='';";
        $result=$pDB->genQuery($query);

        if($result==FALSE){
            $this->errMsg = $pDB->errMsg;
            return false;
        }
        return true;
    }

    function getDataCardRegister($id_card)
    {
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/hardware_detector.db";
        $pDB  = new paloDB($dsn);

        $query = "select * from car_system where hwd='$id_card'";
        $result=$pDB->getFirstRowQuery($query, true);

        if($result==FALSE){
            $this->errMsg = $pDB->errMsg;
            return $result;
        }

        return "$result[vendor],$result[num_serie]";
    }

    function getStatusHylafax()
    {
        $status_hfaxd = $this->_existPID_ByCMD("hfaxd","hylafax");
        $status_faxq  = $this->_existPID_ByCMD("faxq","hylafax");
        if($status_hfaxd == "OK" && $status_faxq == "OK")
            return "OK";
        elseif($status_hfaxd == "Shutdown" && $status_faxq == "Shutdown")
            return "Shutdown";
        elseif($status_hfaxd == "Not_exists" && $status_faxq == "Not_exists")
            return "Not_exists";
    }

    function _existPID_ByFile($filePID, $nameService)
    {
        if (!$this->_existService($nameService)) return "Not_exists";
        if (file_exists($filePID)) {
            $pid = trim(file_get_contents($filePID));
            return (is_dir("/proc/$pid")) ? 'OK' : 'Shutdown';
        }
        return "Shutdown";
    }

    function _existPID_ByCMD($serviceName, $nameService)
    {
        if (!$this->_existService($nameService)) return "Not_exists";
        foreach (explode(' ', trim(`/sbin/pidof $serviceName`)) as $pid) {
            if (ctype_digit($pid) && (is_dir("/proc/$pid"))) return 'OK';
        }
        return 'Shutdown';
    }

    function _existService($nameService)
    {
        if (file_exists("/usr/lib/systemd/system/{$nameService}.service"))
            return TRUE;
        if (file_exists("/etc/rc.d/init.d/{$nameService}"))
            return TRUE;
        return FALSE;
    }

    function getAsterisk_Connections()
    {
        //SIPs
        $arrActivity["sip"]["ext"]["ok"]=0;
        $arrActivity["sip"]["ext"]["no_ok"]=0;
        $arrActivity["sip"]["trunk"]["ok"]=0;
        $arrActivity["sip"]["trunk"]["no_ok"]=0;
        $arrActivity["sip"]["trunk"]["unknown"]=0;
        $arrActivity["sip"]["trunk_registry"]["ok"]=0;
        $arrActivity["sip"]["trunk_registry"]["no_ok"]=0;
        //IAXs
        $arrActivity["iax"]["ext"]["ok"]=0;
        $arrActivity["iax"]["ext"]["no_ok"]=0;
        $arrActivity["iax"]["trunk"]["ok"]=0;
        $arrActivity["iax"]["trunk"]["no_ok"]=0;
        $arrActivity["iax"]["trunk"]["unknown"]=0;
        $arrActivity["iax"]["trunk_registry"]["ok"]=0;
        $arrActivity["iax"]["trunk_registry"]["no_ok"]=0;

        //1.- get all trunk in asterisk
        $arrTrunks = $this->_getAll_Trunk();

        //2.- get sip peers.
        $arrSIPs = $this->AsteriskManager_Command("sip show peers");
        if(is_array($arrSIPs) & count($arrSIPs)>0){
            foreach($arrSIPs as $key => $line){

                //ex: Name/username              Host            Dyn Nat ACL Port     Status
                //    412/412                    192.168.1.82     D   N   A  5060     OK (17 ms)
		if(preg_match("/^\s*(.+)\s+((\d{1,3}(\.\d{1,3}){1,3})|\(Unspecified\))\s+\D*\s*\D*\s*\D*\s*\d+\s+(\D+)/",$line,$arrToken)){
                    if(preg_match("/OK/i",$arrToken[5])){
			// estado OK
			$name = explode("/",$arrToken[1]);
                        if(in_array($name[0],$arrTrunks)) // es una troncal?, registrada
                            $arrActivity["sip"]["trunk"]["ok"]++;
                        else
                            $arrActivity["sip"]["ext"]["ok"]++;
                    }
                    else if(preg_match("/Unmonitored/i",$arrToken[5])){ // estado desconocido, un caso es cuando no esta definido el parametro quality=yes
			$name = explode("/",$arrToken[1]);
                        if(in_array($name[0],$arrTrunks)) // es una troncal?, registrada
                            $arrActivity["sip"]["trunk"]["unknown"]++;
                        else
                            $arrActivity["sip"]["ext"]["ok"]++;
                    }
                    else{
			$name = explode("/",$arrToken[1]);
                        if(in_array($name[0],$arrTrunks)) // es una troncal?, no registrada
                            $arrActivity["sip"]["trunk"]["no_ok"]++;
                        else
                            $arrActivity["sip"]["ext"]["no_ok"]++;
                    }
                }
            }
        }

        //3.- get iax peers
        $arrIAXs = $this->AsteriskManager_Command("iax2 show peers");
        if(is_array($arrIAXs) & count($arrIAXs)>0){
            foreach($arrIAXs as $key => $line){
                //ex: Name/Username    Host                 Mask             Port          Status
                //    512              127.0.0.1       (D)  255.255.255.255  40002         OK (3 ms)
                if(preg_match("/^\s*(.+)\s+((\d{1,3}(\.\d{1,3}){1,3})|\(null\))\s+\(\D\)\s+\d{1,3}(\.\d{1,3}){1,3}\s+\d+\s+\(?\D?\)?\s+(\D+)/",$line,$arrToken)){
                    if(preg_match("/OK/i",$arrToken[6])){ // estado OK
			$name = explode("/",$arrToken[1]);
                        if(in_array($name[0],$arrTrunks)) // es una troncal?, registrada
                            $arrActivity["iax"]["trunk"]["ok"]++;
                        else
                            $arrActivity["iax"]["ext"]["ok"]++;
                    }
                    else if(preg_match("/Unmonitored/i",$arrToken[6])){ // estado desconocido, un caso es cuando no esta definido el parametro quality=yes
			$name = explode("/",$arrToken[1]);
                        if(in_array($name[0],$arrTrunks)) // es una troncal?, registrada
                            $arrActivity["iax"]["trunk"]["unknown"]++;
                        else
                            $arrActivity["iax"]["ext"]["ok"]++;
                    }
                    else{
			$name = explode("/",$arrToken[1]);
                        if(in_array($name[0],$arrTrunks)) // es una troncal?, no registrada
                            $arrActivity["iax"]["trunk"]["no_ok"]++;
                        else
                            $arrActivity["iax"]["ext"]["no_ok"]++;
                    }
                }
            }
        }

        //4.- get sip registry
        /*$arrSIPsRegistry = $this->AsteriskManager_Command("sip show registry");
        if(is_array($arrSIPsRegistry) & count($arrSIPsRegistry)>0){
            foreach($arrSIPsRegistry as $key => $line){
                if(ereg("^([[:digit:]\:\.]+).*(Registered*)",$line,$arrToken))
                    $arrActivity["sip"]["trunk_registry"]["ok"]++;
                else if(ereg("^([[:digit:]\:\.]+)",$line))
                    $arrActivity["sip"]["trunk_registry"]["no_ok"]++;
            }
        }

        //5.- get sip registry
        $arrIAXsRegistry = $this->AsteriskManager_Command("iax2 show registry");
        if(is_array($arrIAXsRegistry) & count($arrIAXsRegistry)>0){
            foreach($arrIAXsRegistry as $key => $line){
                if(ereg("^([[:digit:]\:\.]+).*(Registered*)",$line,$arrToken))
                    $arrActivity["iax"]["trunk_registry"]["ok"]++;
                else if(ereg("^([[:digit:]\:\.]+)",$line))
                    $arrActivity["iax"]["trunk_registry"]["no_ok"]++;
            }
        }*/
        return $arrActivity;
    }

    function getAsterisk_Channels() {
        $arrChann["external_calls"]=0;
        $arrChann["internal_calls"]=0;
        $arrChann["total_calls"]=0;
        $arrChann["total_channels"]=0;

        $arrChannels = $this->AsteriskManager_Command("core show channels");
        if(is_array($arrChannels) & count($arrChannels)>0){
            foreach($arrChannels as $line){
                if(preg_match("/s@macro-dialout/",$line))
                    $arrChann["external_calls"]++;
                else if(preg_match("/s@macro-dial:/",$line))
                    $arrChann["internal_calls"]++;
                else if(preg_match("/^([0-9]+) active call/",$line,$arrToken))
                    $arrChann["total_calls"] = $arrToken[1];
                else if(preg_match("/^([0-9]+) active channel/",$line,$arrToken))
                    $arrChann["total_channels"] = $arrToken[1];
            }
        }
        return $arrChann;
    }

    function getAsterisk_QueueWaiting() {
        $arrQueues = $this->AsteriskManager_Command("queue show");
        $arrQue = array();

        if(is_array($arrQueues) & count($arrQueues)>0){
            foreach($arrQueues as $line){
                if(preg_match("/^([0-9]+)[[:space:]]*has ([0-9]+)/",$line,$arrToken))
                    $arrQue[$arrToken[1]] = $arrToken[2];
            }
        }
        return $arrQue;
    }

    function getNetwork_Traffic()
    {
        $results = array();
        $data = `cat /proc/net/dev`;

        if(isset($data)){
            $arrData = explode("\n", $data);
            foreach($arrData as $line){
                if(preg_match('/:/', $line)) {
                    list($dev, $stats_list) = preg_split('/:/', $line, 2);
                    $stats = preg_split('/\s+/', trim($stats_list));
                    $dev = trim($dev);
                    $results[$dev]['rx_bytes']   = $stats[0];
                    $results[$dev]['rx_packets'] = $stats[1];
                    $results[$dev]['tx_bytes']   = $stats[8];
                    $results[$dev]['tx_packets'] = $stats[9];
                } 
            }
        }
        return isset($results["eth0"])?$results["eth0"]:$results["eth1"];
    }

    function getNetwork_TrafficAverage()
    {
        $r1 = $this->getNetwork_Traffic();
        sleep(3);
        $r2 = $this->getNetwork_Traffic();

        $result['rx_bytes']   = number_format((($r2['rx_bytes']   - $r1['rx_bytes'])/1000),2);
        $result['rx_packets'] = number_format((($r2['rx_packets'] - $r1['rx_packets'])/1000),2);
        $result['tx_bytes']   = number_format((($r2['tx_bytes']   - $r1['tx_bytes'])/1000),2);
        $result['tx_packets'] = number_format((($r2['tx_packets'] - $r1['tx_packets'])/1000),2);
        return $result;
    }

    function _getAll_Trunk()
    {
        $dsn = generarDSNSistema('asteriskuser', 'asterisk');
        $pDBTrunk  = new paloDB($dsn);
        $arrTrunks = getTrunks($pDBTrunk);
        $trunks = array();
        if(empty($arrTrunks)) return $trunks;

        if(is_array($arrTrunks) & count($arrTrunks)>0){
            foreach($arrTrunks as $key => $trunk){
                $tmp = explode("/",$trunk[1]);
                $trunks[] = $tmp[1];
            }
        }
        return $trunks;
    }

    function AsteriskManager_Command($command_data, $return_data=true) {
        global $arrLang;
        $salida = array();
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("127.0.0.1", "admin" , obtenerClaveAMIAdmin())) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
            $salida = $astman->send_request('Command', array('Command'=>"$command_data"));
            $astman->disconnect();
            $salida["Response"] = isset($salida["Response"])?$salida["Response"]:"";
            if (strtoupper($salida["Response"]) != "ERROR") {
                if($return_data) return explode("\n",$salida["data"]);
                else return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

    function getAppletsActivated($user)
    {
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/dashboard.db";
        $pDB  = new paloDB($dsn);
        $arrApplets = array();
        $pDB2 = new paloDB($arrConf['elastix_dsn']['acl']);
        $pACL = new paloACL($pDB2);
        if($pACL->isUserAdministratorGroup($user))
            $typeUser = "admin";
        else
            $typeUser = "no_admin";

        $query = "select
                    a.code, a.icon, a.name, aau.id aau_id
                  from 
                    activated_applet_by_user aau 
                        inner join 
                    default_applet_by_user dau on aau.id_dabu=dau.id 
                        inner join 
                    applet a on dau.id_applet=a.id 
                  where 
                    dau.username=? and aau.username=?
                  order 
                    by aau.order_no asc";

        $result=$pDB->fetchTable($query,true,array($typeUser,$user));

        if($result==FALSE){
            $this->errMsg = $pDB->errMsg; 
            return array();
        }

        return $result;
    }

    function setApplets_UserOrder($ids_applet)
    {
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/dashboard.db";
        $pDB  = new paloDB($dsn);

        if(isset($ids_applet)){

            $tmp1 = explode(",",$ids_applet);
            foreach($tmp1 as $value){
                if($value != ""){
                    list($aau_id,$order_no) = explode(":",$value);

                    $query = "update activated_applet_by_user set order_no=$order_no where id=$aau_id";
                    $result=$pDB->genQuery($query);

                    if($result==FALSE){
                        $this->errMsg = $pDB->errMsg;
                        return false;
                    }
                }
            }
        }
        return false;
    }

    function setDefaultActivatedAppletsByUser($user)
    {
        global $arrConf;
        $dsn = "sqlite3:///$arrConf[elastix_dbdir]/dashboard.db";
        $pDB = new paloDB($dsn);
        $pDB2 = new paloDB($arrConf['elastix_dsn']['acl']);
        $pACL = new paloACL($pDB2);
        if($pACL->isUserAdministratorGroup($user))
            $id_dabu = 1;
        else
            $id_dabu = 13;
        for($i=1;$i<=5;$i++){
            $query  = "insert into activated_applet_by_user (id_dabu,order_no,username) values (?,?,?)";
            $result = $pDB->genQuery($query,array($id_dabu,$i,$user));
            if($result==FALSE){
                $this->errMsg = $pDB->errMsg;
                return false;
            }
            $id_dabu++;
        }
        return true;
    }
}
?>
