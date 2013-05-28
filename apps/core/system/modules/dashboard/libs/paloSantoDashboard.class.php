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
  $Id: paloSantoDashboard.class.php,v 1.1.1.1 2008/01/31 21:31:55  Exp $ */
include_once "paloSantoSysInfo.class.php";
class paloSantoDashboard {
    var $_DB;
    var $errMsg;

    function paloSantoDashboard(&$pDB)
    {
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

   function getSystemStatus($email,$passw){
    global $arrLang;
        global $arrConf;

    $dbEmails = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/email.db");
    if($email!='' && $passw!='')
        $imap = imap_open("{localhost:143/notls}","$email","$passw");
    else return $arrLang["You don't have a webmail account"];

    if (!$imap)
        return $arrLang["Imap: Connection error"];
    $quotainfo = imap_get_quotaroot($imap,"INBOX");
    imap_close($imap);

    $content = $arrLang["Quota asigned"]." $quotainfo[limit] KB<br>".$arrLang["Quota Used"]." $quotainfo[usage] KB<br>".$arrLang["Quota free space"]." ". (string)($quotainfo['limit'] - $quotainfo['usage']) . " KB";
    return $content;
   }

   function getMails($email,$passw,$numRegs){
        global $arrLang;
        
        $counter    = 0;
        
        if($email!='' && $passw!='')
	    $imap = imap_open("{localhost:143/notls}INBOX",$email,$passw);
        else return $arrLang["You don't have a webmail account"];
        
        if(!$imap)
            return $arrLang["Imap: Connection error"];

        $tmp = imap_check($imap);
        
        if($tmp->Nmsgs==0)
            return $arrLang["You don't recibed emails"];
        
        $result = imap_fetch_overview($imap,"1:{$tmp->Nmsgs}",0);
        
        $mails = array();
            //print_r($result);
        foreach ($result as $overview) {
            $mails[] = array("seen"=>$overview->seen,
                             "recent"=>$overview->recent,
                             "answered"=>$overview->answered,
                             "date"=>$overview->date,
                             "from"=>$overview->from,
                             "subject"=>$overview->subject);
        }
        
        imap_close($imap);
        
        $mails = array_slice($mails,-$numRegs,$numRegs);
            krsort($mails);
        
        $content = "";
        
        /*
        foreach($mails as $value){
            $temp = $arrLang["mail recived"];
            $temp = str_replace("{source}",$value["from"],$temp);
            $temp = str_replace("{date}",$value["date"],$temp);
            $temp = str_replace("{subject}",$value["subject"],$temp);
        
            $b = ($value["seen"] or $value["answered"])?false:true;
            if($b)
                $temp = "<b>$temp</b>";
            $content.=$temp."<br>";
        }

        return $content;*/

        //print_r($mails);

        $temp = '';
        foreach($mails as $index => $value){
            $b = ($value["seen"] or $value["answered"])?false:true;
            if($b){
                $temp .= "<font color='#000080' size='1'>".$value['date']."</font>&nbsp;&nbsp;&nbsp;";
                $temp .= "<font  size='1'>"."From: ".substr($value['from'],0,50)."</font>&nbsp;&nbsp;&nbsp;";
                $temp .= "<font  size='1'>"."Subject: ".substr($value['subject'],0,30)."</font><br>";
            }
        }

        return "<b>".$temp."</b>";
    }

   function getVoiceMails($extension,$numRegs){
    global $arrLang;
    $exists = false;
    $count = 0;

    if(is_null($extension))
                return $arrLang["You haven't extension"];

    $voicePath = "/var/spool/asterisk/voicemail/default/$extension/INBOX";

    $exists = file_exists($voicePath);

        $result = array();
    if($exists)
        exec("ls -t $voicePath/*txt | head -n $numRegs",$result);

    $count = count($result);

    if(!$exists or ($count == 0))
        return $arrLang["You don't recibed voicemails"];
    $data ="";

    foreach ($result as $value){
        $content = array();
        $file = fopen($value,"r");
        if(!$file)
            return $arrLang["Unenabled to open file"];

        while($row = fgetcsv($file,4096,"=")){
            if(isset($row[1]))
                $content[$row[0]]=$row[1];
        }
        fclose($file);

        $date = date('Y/m/d H:i:s',$content["origtime"]);
        $source = ($content["callerid"]=="Unknown")?$arrLang["unknow"]:$content["callerid"];
        $duration = $content["duration"];

        $temp = $arrLang["voicemail recived"];
        $temp = str_replace("{source}",$source,$temp);
                $temp = str_replace("{date}",$date,$temp);
        $temp = str_replace("{duration}",$duration,$temp);
        $data.="$temp.<br>";
    }
    return $data;
   }

   function getLastFaxes($extension,$numRegs){
    global $arrConf;
    global $arrLang;

    $dbFax = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/fax.db");

    if(is_null($extension))
                return $arrLang["You haven't extension"];

    $result = $dbFax->fetchTable("select a.pdf_file,a.company_name,a.date, a.id from info_fax_recvq a,fax b where b.extension='$extension' and b.id=a.fax_destiny_id and type='in' order by a.id desc limit $numRegs");
    if(!$result)
        return $arrLang["You don't recibed faxes"];

    $data = "";

    foreach($result as $value){
        $temp = $arrLang["fax recived"];
        $link="<a href='?menu=faxviewer&action=download&rawmode=yes&id=$value[3]'>$value[0]</a>";
        $temp = str_replace("{file}",$link,$temp);
        $temp = str_replace("{source}",($value[1]=="XXXXXXX")?$arrLang["unknow"]:$value[1],$temp);
        $temp = str_replace("{date}",$value[2],$temp);  
        $data.= $temp."<br>";
    }   
    return $data;
   }

   function getLastCalls($extension,$numRegs){
    global $arrLang;

    if(is_null($extension))
        return $arrLang["You haven't extension"];

    $result = $this->_DB->fetchTable("select calldate,src,duration,disposition from cdr where dst='".$extension."'  order by calldate desc limit $numRegs");
    if(count($result)==0)
        return $arrLang["You don't recibed calls"];

    $data = "";
    foreach($result as $value){
        $answ=($value[3]=="ANSWERED") ? true:false;

        $status = ($answ)?$arrLang['answered']:$arrLang['missed'];
        $source = ($value[1]=="")?$arrLang['unknow']:$value[1];
        $duration = ($answ)?str_replace("{time}",$value[2],$arrLang["call duration"]):".";

        $temp = $arrLang["call record"];
        $temp = str_replace("{status}",$status,$temp);
        $temp = str_replace("{date}",$value[0],$temp);
        $temp = str_replace("{source}",$source,$temp);

        $data.=$temp . $duration."<br>";
    }

    return $data;
   }

    function getDataUserLogon($nameUser)
    {
        global $arrConf;
        //consulto datos del usuario logoneado
        $dbAcl = new paloDB($arrConf["elastix_dsn"]["acl"]);
        $pACL  = new paloACL($dbAcl);

        $arrData = null;
        //paso 1: consulta de los datos de webmail si existen
        $userId  = $pACL->getIdUser($nameUser);
        $arrData = $this->leerPropiedadesWebmail($dbAcl,$userId);
        if(!$arrData)
        {
            $arrData['login'] = '';
            $arrData['domain'] = '';
            $arrData['password'] = '';
        }

        //paso 2: consulta de la extension si tiene asignada
        $extension = $pACL->getUserExtension($nameUser);
        if($extension)
            $arrData['extension'] = $extension;

        $arrData['id'] = $userId;
        return $arrData;
    }

    function leerPropiedadesWebmail($pDB, $idUser)
    {
        // Obtener la información del usuario con respecto al perfil "default" del módulo "webmail"
        $sPeticionPropiedades = 
            'SELECT pp.property, pp.value '.
            'FROM acl_profile_properties pp, acl_user_profile up, acl_resource r '.
            'WHERE up.id_user = ? '.
                'AND up.profile = "default" '.
                'AND up.id_profile = pp.id_profile '.
                'AND up.id_resource = r.id '.
                'AND r.name = "webmail"';
        $listaPropiedades = array();
        $tabla = $pDB->fetchTable($sPeticionPropiedades, FALSE, array($idUser));
        if ($tabla === FALSE) {
            return false;
        } else {
            foreach ($tabla as $tupla) {
                $listaPropiedades[$tupla[0]] = $tupla[1];
            }
        }
        return $listaPropiedades;
   }

   function getEventsCalendar($idUser, $numRegs)
   {
        global $arrConf;
        global $arrLang;
        $db = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/calendar.db");

        $actual_date = date("Y-m-d");
        $actual_date_hour = date("Y-m-d H:i:s");

        $query =     "SELECT id, subject, asterisk_call, startdate, enddate, starttime, eventtype "
                    ."FROM events "
                    ."WHERE uid=$idUser and enddate>='$actual_date' "
                    ."ORDER BY id desc;";

        $result = $db->fetchTable($query, TRUE);
        if(!$result)
            return $arrLang["You don't have events"];

        $data = "";

        $arrEventos = array();
        foreach($result as $value){
            $iStartTimestamp    = strtotime($value['starttime']);
            $endstamp           = strtotime($value['enddate']);
            $startstamp         = strtotime($value['startdate']);

            if($value['eventtype']==1 || $value['eventtype']==5)
            {
                if($value['eventtype']==1)
                {
                    $segundos = 86400;
                    $num_dias = (($endstamp-$startstamp)/$segundos)+1;//Sumo 1 para incluir el ultimo dia
                }else if($value['eventtype']==5)
                {
                    $segundos = 604800;
                    $num_dias = (($endstamp-$startstamp)/$segundos)+1;//Sumo 1 para incluir la ultima semana
                    $num_dias = (int)$num_dias;
                }

                for($i=0; $i<$num_dias; $i++)
                {
                    $sFechaEvento = date('Y-m-d H:i:s', $iStartTimestamp);
                    $iStartTimestamp += $segundos;
                    if($sFechaEvento >= $actual_date_hour)
                    {
                        $arrEventos[] = array(
                                            "date"      =>  $sFechaEvento,
                                            "subject"   =>  $value['subject'],
                                            "call"      =>  $value['asterisk_call'],
                                            "id"        =>  $value['id']
                                        );
                    }
                }
            }else if($value['eventtype']==6)
            {
                $i=0;
                while($iStartTimestamp <= $endstamp)
                {
                    $sFechaEvento = date('Y-m-d H:i:s', $iStartTimestamp);
                    $iStartTimestamp = strtotime("+1 months", $iStartTimestamp);
                    if($sFechaEvento >= $actual_date_hour)
                    {
                        $arrEventos[] = array(
                                            "date"      =>  $sFechaEvento,
                                            "subject"   =>  $value['subject'],
                                            "call"      =>  $value['asterisk_call'],
                                            "id"        =>  $value['id']
                                        );
                    }
                    $i++;
                }
            }
        }

        if(count($arrEventos)<1)
            return $arrLang["You don't have events"];

        //Ordenamiento por fechas en orden descendente (antiguos primero)
        $fechas = array();
        //$horas  = array();
        foreach ($arrEventos as $llave => $valor)
            $fechas[$llave] = $valor['date'];
        array_multisort($fechas,SORT_ASC,$arrEventos);

        $i=0;
        while($i<count($arrEventos) && $i<$numRegs)
        {
            $temp  = "<a href='?menu=calendar&action=display&id=".$arrEventos[$i]["id"]."&event_date=".date("Y-m-d", strtotime($arrEventos[$i]["date"]))."'>".$arrEventos[$i]["subject"]."</a>";
            $temp .= "&nbsp;&nbsp;&nbsp;";
            $temp .= "Date: ";
            $temp .= $arrEventos[$i]['date'];
            $temp .= " - Call: ";
            $temp .= $arrEventos[$i]['call'];
            $temp .= "<br>";
            $data .= $temp;
            $i++;
        }

        return $data;
   }
   
    function controlServicio($sServicio, $sAccion)
    {   $oPalo = new paloSantoSysInfo();
        $flag = 0;
        $acciones = array(
            'processcontrol_start'      =>  'start',
            'processcontrol_restart'    =>  'restart',
            'processcontrol_stop'       =>  'stop',
            'processcontrol_activate'   =>  'on',
            'processcontrol_deactivate' =>  'off',
        );
        $servicios = array(
            'Asterisk'  =>  'asterisk',
            'OpenFire'  =>  'openfire',
            'Hylafax'   =>  'hylafax',
            'Postfix'   =>  'postfix',
            'MySQL'     =>  'mysqld',
            'Apache'    =>  'httpd',
            'Dialer'    =>  'elastixdialer',
        );
        if (!in_array($sServicio, array_keys($servicios))) return FALSE;
        if (!in_array($sAccion, array_keys($acciones))) return FALSE;
        $output = $retval = NULL;
        if(($sAccion=="processcontrol_deactivate")||($sAccion=="processcontrol_activate")){
        //  exec('sudo -u root chkconfig --level 3 '.escapeshellarg($servicios[$sServicio]).' '.escapeshellarg($acciones[$sAccion]),$output,$retval);
	    exec('/usr/bin/elastix-helper rchkconfig --level 3 '.escapeshellarg($servicios[$sServicio]).' '.escapeshellarg($acciones[$sAccion]),$output,$retval);    
	    
	    $arrServices = $oPalo->getStatusServices();  
        if((($arrServices[$sServicio]["status_service"]=="Shutdown")&&($sAccion=="processcontrol_activate"))||(($arrServices[$sServicio]["status_service"]=="OK")&&($sAccion=="processcontrol_deactivate")))
            $sAccion = ($sAccion=="processcontrol_deactivate")?'processcontrol_stop':'processcontrol_start';
        else
            $flag = 1;
        }
        if($flag!=1)
            exec('sudo -u root service generic-cloexec '.$servicios[$sServicio].' '.$acciones[$sAccion].' 1>/dev/null 2>/dev/null');
        return TRUE;
    }
}
?>