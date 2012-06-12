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
  $Id: paloSantoRate.class.php,v 1.1.1.1 2007/07/06 21:31:55 Grzegorz Hetman Exp $ */

if (isset($arrConf['basePath'])) {
    include_once($arrConf['basePath'] . "/libs/paloSantoDB.class.php");
} else {
    include_once("libs/paloSantoDB.class.php");
}

class paloCron {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;
    var $module_name;

    function paloCron(&$pDB)
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

    function createCronStr($cron_data=array("predefined"=>"now"))
    {

         switch($cron_data['predefined']){
            case 'now'    : $Cron_String="0 0 0 0 0 ";break;
            case 'hourly' : $Cron_String="0 * * * * ";break;
            case 'daily'  : $Cron_String="0 0 * * * ";break;
            case 'weekly' : $Cron_String="0 0 * * 0 ";break;
            case 'monthly': $Cron_String="0 0 1 * * ";break;
            case 'yearly' : $Cron_String="0 0 1 1 * ";break;
            case 'follow_schedule';
           if (count($cron_data['minutes'])<1)  $mins_string     =":0:"; else foreach ($cron_data['minutes']  as $value) $mins_string     .=":$value:";
           if (count($cron_data['hours'])<1)    $hours_string    =":0:"; else foreach ($cron_data['hours']    as $value) $hours_string    .=":$value:";
           if (count($cron_data['days'])<1)     $days_string     ="*";   else foreach ($cron_data['days']     as $value) $days_string     .=":$value:";
           if (count($cron_data['months'])<1)   $months_string   ="*";   else foreach ($cron_data['months']   as $value) $months_string   .=":$value:";
           if (count($cron_data['weekdays'])<1) $weekdays_string ="*";   else foreach ($cron_data['weekdays'] as $value) $weekdays_string .=":$value:";
             $Cron_String=str_replace("::", ",", trim($mins_string,":")." ".trim($hours_string,":")." ".trim($days_string,":")." ".
              trim($months_string,":")." ".trim($weekdays_string,":")." ");
            break;
         }
	     return $Cron_String;
    }

    function getCronSchedule($id_rate = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_rate) && !ereg('^[[:digit:]]+$', "$id_rate")) {
            $this->errMsg = "Schedule ID is not valid";
        } 
        else {
            $this->errMsg = "";
              $sPeticionSQL = "SELECT * FROM billing_sendmail".
                (is_null($id_rate) ? '' : " WHERE id = $id_rate");
            $sPeticionSQL .=" ORDER BY id";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    function createCronSchedule($post_data)
    {
	    $bExito = FALSE;
		$this->updateCrontab();
               $Cron_String=$this->createCronStr($post_data);
               $sPeticionSQL = "SELECT id FROM billing_sendmail WHERE recipient = '".$post_data['recipient']."' AND cron_str = '$Cron_String'";

               $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
               if (is_array($arr_result) && count($arr_result)>0) {
                  $bExito = FALSE;
                  $this->errMsg = "Schedule for this recipient already exists";
               }else{
                  $sources=$this->GetSourcesString($post_data['sources']);
                  $sPeticionSQL = paloDB::construirInsert("billing_sendmail",
                                           array("name"         => paloDB::DBCAMPO($post_data['name']),
                                                 "predefined"   => paloDB::DBCAMPO($post_data['predefined']),
                                                 "sources_mode" => paloDB::DBCAMPO($post_data['sources_mode']),
                                                 "recipient"    => paloDB::DBCAMPO($post_data['recipient']),
                                                 "daysrange"    => paloDB::DBCAMPO($post_data['daysrange']),
                                                 "cron_str"     => paloDB::DBCAMPO($Cron_String),
                                                 "sources"      => paloDB::DBCAMPO($sources))
                        );
                   if ($this->_DB->genQuery($sPeticionSQL)) {
                        if ($post_data['predefined'] == 'now') $bExito = $this->runSchedule($post_data); else $bExito = $this->updateCrontab();

                   } else {
                       $this->errMsg = $this->_DB->errMsg;
                   }
               }
        return $bExito;
    }

    function updateCronSchedule($post_data)
    {
        $bExito = FALSE;

        if (!ereg("^[[:digit:]]+$", $post_data['id_bill_sendmail'])) {
            $this->errMsg = "Schedule ID is not valid";
        } else {
                       $Cron_String=$this->createCronStr($post_data);
                       $sources=$this->GetSourcesString($post_data['sources']);
                       $sPeticionSQL = paloDB::construirUpdate(
                           "billing_sendmail", array("name"         => paloDB::DBCAMPO($post_data['name']),
                                                     "predefined"   => paloDB::DBCAMPO($post_data['predefined']),
                                                     "sources_mode" => paloDB::DBCAMPO($post_data['sources_mode']),
                                                     "recipient"    => paloDB::DBCAMPO($post_data['recipient']),
                                                     "daysrange"    => paloDB::DBCAMPO($post_data['daysrange']),
                                                     "cron_str"     => paloDB::DBCAMPO($Cron_String),
                                                     "sources"      => paloDB::DBCAMPO($sources)),
                                               array("id"  => $post_data['id_bill_sendmail'])
                       );
                    if ($this->_DB->genQuery($sPeticionSQL)) {
			if ($bExito = $this->updateCrontab() && $post_data['predefined'] == 'now') $bExito = $this->runSchedule($post_data);

                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
          
        }
        return $bExito;
    }


    function deleteCronSchedule($id_rate)
    {
        $bExito = FALSE;
        if (!ereg('^[[:digit:]]+$', "$id_rate")) {
            $this->errMsg = "Schedule ID is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = 
                "DELETE FROM billing_sendmail WHERE id = '$id_rate'";
            $bExito = TRUE;
            $bExito = $this->_DB->genQuery($sPeticionSQL);
            if (!$bExito) {
                $this->errMsg = $this->_DB->errMsg;
                break;
            } else {
		$bExito = $this->updateCrontab();
            }

        }
        return $bExito;
    }

    function GetSourcesString ($sources){
            $pattern = array("%([^/gb.@vp$^\n,A-Z,0-9,:-]+)%","/\n+/","%[pg]+?:%");
            $replacement = array("",";");
            return trim(preg_replace($pattern,$replacement,$sources),";");
    }

    function GetLastID($recipient)
    {
        $arr_result = FALSE;
        if (is_null($recipient)) {
            $this->errMsg = "Recipient is not valid";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id FROM billing_sendmail WHERE recipient = '".$recipient."'";
	    $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result[0][0];
	
    }

    function runSchedule($post_data){
	if (empty($post_data['id_bill_sendmail'])) $id = $this->GetLastID($post_data['recipient']); else $id = $post_data['id_bill_sendmail'];
	exec("./modules/billing_sendmail/$this->module_name.sh $id");
	return true;
    }

    function updateCrontab ($desc=null){
        $cron_str="";
        $this->errMsg = "";
        $bExito = FALSE;
        $cron_script=exec("pwd").'/modules/billing_sendmail/'.$this->module_name.'.sh';
	$arrScheduleList = $this->getCronSchedule();

		foreach ( $arrScheduleList as $v ) if ($v[2] != 'now') $New_cron_jobs[]=$v[3].$cron_script.' '.$v[0];

                if (!$desc) $desc="part of ".($this->module_name?$this->module_name:'Unknow')." cron jobs for EWA";

                   exec ("crontab -l 2>/dev/null",$curent_crontab);

                   foreach ($curent_crontab as $cron_job) {
		       if (!ereg ("$cron_script|$desc--------",$cron_job)) $cron_str .= $cron_job."\n";
                   }

                   if (isset($New_cron_jobs) && is_array($New_cron_jobs) && count($New_cron_jobs) > 0) {
                       $cron_str .= "#--------Start $desc--------\n".implode("\n",$New_cron_jobs)."\n#--------Stop $desc---------";
                   }

                   $cron_str=trim($cron_str);

                   system("echo \"$cron_str\"|crontab",$ret_val);
                   if ( $ret_val == 0 ) $bExito = TRUE; else $this->errMsg = "Error occurr when inserting new crontab";

        return $bExito;
   }

}
?>
