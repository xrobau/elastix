<?php
global $arrConf;
require_once "{$arrConf['basePath']}/libs/misc.lib.php";
require_once "{$arrConf['basePath']}/libs/paloSantoDB.class.php";
require_once "{$arrConf['basePath']}/libs/paloSantoSampler.class.php";

class paloSantoSysInfo
{
    var $arrSysInfo;

    function paloSantoSysInfo()
    {
        $this->arrSysInfo = obtener_info_de_sistema();
    }

    function getSysInfo()
    {
        return $this->arrSysInfo;
    }

    function ObtenerInfo_Particion($value)
    {
        $result = array();

        $result['ATTRIBUTES'] = array('TITLE'=>'Disk Usage','TYPE'=>'plot3d','SIZE'=>"630,170",'POS_LEYEND' => "0.12,0.5",);
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $arrTemp = array();
        for($i=1; $i<=2; $i++){
            $data = array();
            $data['VALUES'] = ($i==1) ? array('VALUE'=>$value) : array('VALUE'=>100-$value);
            $data['STYLE'] = array('COLOR'=> ($i==1) ? '#3333cc' : '#9999cc','LEYEND'=> ($i==1) ? 'Used space' : 'Free space');
            $arrTemp["DAT_$i"] = $data;
        }

        $result['DATA'] = $arrTemp;

        return $result;

    }

    function ObtenerInfo_CPU_Usage()
    {
        $value = $this->arrSysInfo['CpuUsage'];

        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,20");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$value));
        $result['DATA'] = $temp;

        return $result;
    }

    function ObtenerInfo_MemUsage()
    {
        $value = ($this->arrSysInfo['MemTotal'] - $this->arrSysInfo['MemFree'] - $this->arrSysInfo['Cached'] - $this->arrSysInfo['MemBuffers'])/$this->arrSysInfo['MemTotal'];

        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,20");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$value));
        $result['DATA'] = $temp;

        return $result;
    }

    function ObtenerInfo_SwapUsage()
    {
        $value = ($this->arrSysInfo['SwapTotal'] - $this->arrSysInfo['SwapFree'])/$this->arrSysInfo['SwapTotal'];

        $result = array();
        $result['ATTRIBUTES'] = array('TYPE'=>'bar','SIZE'=>"90,20");
        $result['MESSAGES'] = array('ERROR'=>'Error','NOTHING_SHOW'=>'Nada que mostrar');

        $temp = array();
        $temp['DAT_1'] = array('VALUES'=>array("value"=>$value));
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
            'LABEL_X'=>"Etiqueta X",'LABEL_Y'=>'Etiqueta Y','SHADOW'=>false,'SIZE'=>"630,170",'MARGIN'=>"50,230,30,50",
            'COLOR' => "#fafafa",'POS_LEYEND'=> "0.02,0.5");

        $arrayResult['MESSAGES'] = array('ERROR' => 'Error', 'NOTHING_SHOW' => 'Nothing to show yet');

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
}
?>
