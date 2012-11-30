<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                                |
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
  $Id: paloSantoReportCall.class.php,v 1.1 2009-01-06 09:01:38 jvega jvega@palosanto.com Exp $ */

class paloSantoReportCall {
    var $_DB_cdr;
    var $_DB_billing;
    var $errMsg;
    var $arrLang;

    function paloSantoReportCall(&$pDB_cdr, &$pDB_billing=null)
    {
        $this->CargarIdiomas();

        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB_cdr)) {
            $this->_DB_cdr =& $pDB_cdr;
            $this->errMsg = $this->_DB_cdr->errMsg;
        } else {
            if ($pDB_cdr == '') {
                $pDB_cdr = generarDSNSistema('asteriskuser', 'asteriskcdrdb');
            }

            $dsn = (string)$pDB_cdr;
            $this->_DB_cdr = new paloDB($dsn);

            if (!$this->_DB_cdr->connStatus) {
                $this->errMsg = $this->_DB_cdr->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }

        if (is_object($pDB_billing)) {
            $this->_DB_billing =& $pDB_billing;
            $this->errMsg = $this->_DB_billing->errMsg;
        } else {
            $dsn = (string)$pDB_billing;
            $this->_DB_billing = new paloDB($dsn);

            if (!$this->_DB_billing->connStatus) {
                $this->errMsg = $this->_DB_billing->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    function CargarIdiomas()
    {
        global $arrConf;
        $module_name = "summary_by_extension";

        include_once $arrConf['basePath']."/libs/misc.lib.php";
        $lang = get_language($arrConf['basePath'].'/');

        if( file_exists($arrConf['basePath']."/modules/$module_name/lang/$lang.lang") )
            include_once $arrConf['basePath']."/modules/$module_name/lang/$lang.lang";
        else
            include_once $arrConf['basePath']."/modules/$module_name/lang/en.lang";

        global $arrLangModule;
        $this->arrLang = $arrLangModule;
    }

    function ObtainBillingByTrunk()
    {
        $query= "select * from rate";

        $result = $this->_DB_billing->fetchTable($query, true);

        if($result == FALSE){
            $this->errMsg = $this->_DB_billing->errMsg;
            return array();
        }

        return $result;
    }

    function ObtainNumberDevices($type, $value)
    {
        $extension   = "";
        $description = "";
        if( $type == 'Ext' ) $extension=$value;
        else if( $type == 'User' ) $description=$value;

        $query= "select count(*) from asterisk.devices d where d.id like '$extension%' AND d.description like '$description%'";
        $result = $this->_DB_cdr->getFirstRowQuery($query);

        if($result == FALSE){
            $this->errMsg = $this->_DB_cdr->errMsg;
            return 0;
        }
        return $result[0];
    }

    function ObtainReportCall($limit, $offset, $date_ini, $date_end, $type, $value, $order_by, $order_type="desc")
    {
        $expression_extension  = "[A-Za-z0-9]+";
        $extension   = "";
        $description = "";
        $expression_channel    = "substring_index(substring_index(channel,'-',1),'/',-1)"; //solo funciona con formato SIP/123-345gf
        $expression_dstchannel = "substring_index(substring_index(dstchannel,'-',1),'/',-1)"; //solo funciona con formato SIP/123-345gf

        if($type=='Ext'){
            $extension = $value;
            if(trim($value) != "") $expression_extension = $value."[A-Za-z0-9]*";
        }
        else if($type=='User') $description = $value;

        //PASO 1: Obtengo datos salientes de todas las extensiones que estan en la tabla devices, por ello uso
        //        el RIGHT JOIN
        $query_outgoing_call="
            SELECT
                cast(t_devices.id as unsigned) source,
                t_devices.description name,
                ifnull(t_cdr.num_outgoing_call,0) num_outgoing_call,
                ifnull(t_cdr.duration_outgoing_call,0) duration_outgoing_call
            FROM 
                (SELECT 
                    $expression_channel source,
                    count(c.src) num_outgoing_call,
                    sum(c.billsec) duration_outgoing_call
                FROM 
                    asteriskcdrdb.cdr c
                WHERE 
                    c.calldate>='$date_ini' AND
                    c.calldate<='$date_end' AND
                    substring_index(channel,'-',1) regexp '^[A-Za-z0-9]+/$expression_extension$'
                GROUP BY source) t_cdr
            RIGHT JOIN 
                (SELECT 
                    d.id, 
                    d.description
                 FROM
                    asterisk.devices d
                 WHERE
                    d.id like '$extension%' AND
                    d.description like '$description%') t_devices
            ON t_devices.id=t_cdr.source
            ORDER BY 1 $order_type";

        //PASO 2: Obtengo datos entrantes de todas las extensiones que estan en la tabla devices, por ello uso
        //        el RIGHT JOIN, el numero de registros son iguales tanto en el paso 1 y paso2.
        $query_incoming_call="
            SELECT 
                cast(t_devices.id as unsigned) destiny,
                t_devices.description name,
                ifnull(t_cdr.num_incoming_call,0) num_incoming_call, 
                ifnull(t_cdr.duration_incoming_call,0) duration_incoming_call 
            FROM 
                (SELECT 
                    $expression_dstchannel destiny,
                    count(c.dst) num_incoming_call,
                    sum(c.billsec) duration_incoming_call
                FROM 
                    asteriskcdrdb.cdr c
                WHERE 
                    c.calldate>='$date_ini' AND 
                    c.calldate<='$date_end' AND
                    substring_index(dstchannel,'-',1) regexp '^[A-Za-z0-9]+/$expression_extension$'
                GROUP BY destiny ) t_cdr 
            RIGHT JOIN 
                (SELECT 
                    d.id,
                    d.description
                 FROM
                    asterisk.devices d
                 WHERE
                    d.id like '$extension%' AND
                    d.description like '$description%') t_devices
            ON t_devices.id=t_cdr.destiny
            ORDER BY 1 $order_type";

        //PASO 3: Uno ambos resultados.
        $query_extension_call="
            SELECT 
                t_outgoing_call.source extension,
                t_outgoing_call.name user_name,
                t_incoming_call.num_incoming_call,
                t_outgoing_call.num_outgoing_call,
                t_incoming_call.duration_incoming_call,
                t_outgoing_call.duration_outgoing_call
            FROM 
                ($query_outgoing_call) t_outgoing_call
            INNER JOIN 
                ($query_incoming_call) t_incoming_call
            ON
                t_outgoing_call.source=t_incoming_call.destiny
            ORDER BY $order_by $order_type
            LIMIT $limit OFFSET $offset;";

        $result = $this->_DB_cdr->fetchTable($query_extension_call, true);

        if($result == FALSE){
            $this->errMsg = $this->_DB_cdr->errMsg;
            return array();
        }
        return $result;
    }

    //PARA PLOT3D
    function callbackTop10Salientes($date_ini, $date_end, $ext)
    {
        $arrData = $this->obtainTop10Salientes( $date_ini, $date_end, $ext );
        $result = $arrData['data'];
        $numTopCalls = $arrData['total'];
        $num_out = $this->obtainAllSalientes($date_ini, $date_end, $ext);
        
        if($num_out > 0)
            $numCallNoTop = $num_out - $numTopCalls;
        
        $arrColor = array('blue','red','yellow','brown','green','orange','pink','purple','gray','white','violet');

        $arrT = array();
        $i = 0;
        foreach( $result as $num => $arrR ){
            if($num_out <= 0){
                $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>0),
                                        'STYLE'  => array('COLOR'=>$arrColor[$i], 'LEYEND'=>" (0 ".$this->arrLang['calls'].")"));
                break;
            }else{
                if($arrR[0]==1){
                    $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$arrR[0]),
                                            'STYLE'  => array('COLOR'=>$arrColor[$i], 'LEYEND'=>"$arrR[1] ($arrR[0] ".$this->arrLang['call'].")"));
                }else{
                    $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$arrR[0]),
                                            'STYLE'  => array('COLOR'=>$arrColor[$i], 'LEYEND'=>"$arrR[1] ($arrR[0] ".$this->arrLang['calls'].")"));
                }
            }
            $i++;
        }

       if($num_out > 0){
            if($numCallNoTop == 1){
                $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$numCallNoTop),
                                    'STYLE'  => array('COLOR'=>$arrColor[10], 'LEYEND'=>$this->arrLang['Other calls']." (".$numCallNoTop." ".$this->arrLang['call'].")"));
            }else{
                    $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$numCallNoTop),
                                            'STYLE'  => array('COLOR'=>$arrColor[10], 'LEYEND'=>$this->arrLang['Other calls']." (".$numCallNoTop." ".$this->arrLang['calls'].")"));
            }
        }
        
        return array( 
            'ATTRIBUTES' => array(
                //NECESARIOS
                'TITLE'   => $this->arrLang['Top 10 (Outgoing) ext']." ".$ext,
                'TYPE'    => 'plot3d',
                'SIZE'    => "700,250", 
                'MARGIN'  => "5,70,15,20",
            ),

            'MESSAGES'  => array(
                'ERROR' => 'Error', 
                'NOTHING_SHOW' => $this->arrLang['No data to display']
            ),
            //DATOS A DIBUJAR
            'DATA' => $arrT );
    }

    function obtainTop10Salientes( $date_ini, $date_end, $ext )
    {
        if( $ext == null) return array();

        $query = "SELECT count(dst) as num, dst ".
                 "FROM cdr ".
                 "WHERE calldate >= '$date_ini' AND ".
                       "calldate <= '$date_end' AND ".
                       "substring_index(channel,'-',1) regexp '^[A-Za-z0-9]+/$ext$'".
                 "GROUP BY dst ".
                 "ORDER BY 1 desc ".
                 "LIMIT 10 ";
        $result = $this->_DB_cdr->fetchTable($query, false);

        if(!is_array($result)){
            $this->errMsg = $this->_DB_cdr->errMsg;
            print "Errmsg: ".$this->errMsg;
            return array();
        }

         $sum = 0;
        if(is_array($result) & count($result)>0){
          foreach($result as $key => $value)
            $sum+= $value[0];
        }

        $arrData['data']=$result;
        $arrData['total']=$sum;
        return $arrData;
    }
    
    function obtainAllSalientes($date_ini, $date_end, $ext)
    {
        $query = " SELECT
                         count(dst) 
                   FROM
                         cdr 
                   WHERE calldate >= '$date_ini' AND 
                         calldate <= '$date_end' AND
                         substring_index(channel,'-',1) regexp '^[A-Za-z0-9]+/$ext$'";
        $result = $this->_DB_cdr->fetchTable($query, false);

        if($result == FALSE){
            $this->errMsg = $this->_DB_cdr->errMsg;
        }
        return $result[0][0];
    }
    
    function obtainAllEntrantes($date_ini, $date_end, $ext)
    {
        $query = " SELECT
                         count(src) 
                   FROM
                         cdr 
                   WHERE calldate >= '$date_ini' AND 
                         calldate <= '$date_end' AND
                         substring_index(dstchannel,'-',1) regexp '^[A-Za-z0-9]+/$ext$'";
        $result = $this->_DB_cdr->fetchTable($query, false);

        if($result == FALSE){
            $this->errMsg = $this->_DB_cdr->errMsg;
        }
        return $result[0][0];
    }
////////
    function callbackTop10Entrantes($date_ini, $date_end, $ext)
    {
        $arrData = $this->obtainTop10Entrantes( $date_ini, $date_end, $ext );
        $result = $arrData['data'];
        $numTopCalls = $arrData['total'];
        $num_in = $this->obtainAllEntrantes($date_ini, $date_end, $ext);
        
        if($num_in > 0)
            $numCallNoTop = $num_in - $numTopCalls;
        $arrColor = array('blue','red','yellow','brown','green','orange','pink','purple','gray','white','violet');

        $arrT = array();
        $i = 0;
        $externalCalls = $this->arrLang['External #'];
        foreach( $result as $num => $arrR ){
            if($num_in <= 0){
                $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>0),
                                        'STYLE'  => array('COLOR'=>$arrColor[$i], 'LEYEND'=>" (0 ".$this->arrLang['calls'].")"));
                break;
            }else{
                if($arrR[1] == "") $arrR[1] = $externalCalls;
                if($arrR[0]==1){
                    $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$arrR[0]),
                                            'STYLE'  => array('COLOR'=>$arrColor[$i], 'LEYEND'=>"$arrR[1] ($arrR[0] ".$this->arrLang['call'].")"));
                }else{
                    $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$arrR[0]),
                                            'STYLE'  => array('COLOR'=>$arrColor[$i], 'LEYEND'=>"$arrR[1] ($arrR[0] ".$this->arrLang['calls'].")"));
                }
            }
            $i++;
        }
        
        if($num_in > 0){
            if($numCallNoTop==1){
                $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$numCallNoTop),
                                        'STYLE'  => array('COLOR'=>$arrColor[10], 'LEYEND'=>$this->arrLang['Other calls']." (".$numCallNoTop." ".$this->arrLang['call'].")"));
            }else{
                $arrT["DAT_$i"] = array('VALUES' => array('VALUE'=>$numCallNoTop),
                                        'STYLE'  => array('COLOR'=>$arrColor[10], 'LEYEND'=>$this->arrLang['Other calls']." (".$numCallNoTop." ".$this->arrLang['calls'].")"));
            }
        }
        

        return array( 
            'ATTRIBUTES' => array(
                //NECESARIOS
                'TITLE'   => $this->arrLang['Top 10 (Incoming) ext']." ".$ext,
                'TYPE'    => 'plot3d',
                'SIZE'    => "700,250", 
                'MARGIN'  => "5,70,15,20",
            ),

            'MESSAGES'  => array(
                'ERROR' => 'Error', 
                'NOTHING_SHOW' => $this->arrLang['No data to display']
            ),
            //DATOS A DIBUJAR
            'DATA' => $arrT );
    }

    function obtainTop10Entrantes( $date_ini, $date_end, $ext )
    {
        if( $ext == null) return array();

        $query = "SELECT count(src) as num, src ".
                 "FROM cdr ".
                 "WHERE calldate >= '$date_ini' AND ".
                       "calldate <= '$date_end' AND ".
                       "substring_index(dstchannel,'-',1) regexp '^[A-Za-z0-9]+/$ext$'".
                 "GROUP BY src ".
                 "ORDER BY 1 desc ".
                 "LIMIT 10 ";
        $result = $this->_DB_cdr->fetchTable($query, false);

        if(!is_array($result)){
            $this->errMsg = $this->_DB_cdr->errMsg;
            print "Errmsg: ".$this->errMsg ;
            return array();
        }

        $sum = 0;
        if(is_array($result) & count($result)>0){
          foreach($result as $key => $value)
            $sum+= $value[0];
        }

        $arrData['data']=$result;
        $arrData['total']=$sum;
        return $arrData;
    }

    function Sec2HHMMSS($sec)
    {
        $HH = '00'; $MM = '00'; $SS = '00';

        if($sec >= 3600){ 
            $HH = (int)($sec/3600);
            $sec = $sec%3600; 
            if( $HH < 10 ) $HH = "0$HH";
        }

        if( $sec >= 60 ){ 
            $MM = (int)($sec/60);
            $sec = $sec%60;
            if( $MM < 10 ) $MM = "0$MM";
        }

        $SS = $sec;
        if( $SS < 10 ) $SS = "0$SS";

        return "{$HH}h. {$MM}m. {$SS}s";
    }
}
?>
