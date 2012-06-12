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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoCron.class.php";

   //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $pDBsendmail = new paloDB("sqlite3:////var/www/db/billing_sendmail.db");
    $oCron = new paloCron($pDBsendmail);
    $oCron->module_name = $module_name;
//    echo "<pre>".print_r($arrConf,1)."</pre>";
    $arrData = array();

    if(!empty($pDBsendmail->errMsg)) {
        echo "{$arrLang["ERROR"]}: {$arrLang[$pDBsendmail->errMsg]} <br>";
    }

    $arrFormElements = array(
                        "name"        => array("LABEL"                  => $arrLang["Name"],
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "TEXT",
                                               "INPUT_EXTRA_PARAM"      => "",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => ""),

                        "predefined"  => array("LABEL"                  => $arrLang["Predefined"],
                                               "REQUIRED"               => "no",
                                               "INPUT_TYPE"             => "SELECT",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "now"             => "Now",
                                                                                  "daily"           => "Daily (at midnight)",
                                                                                  "weekly"          => "Weekly (on Sunday)",
                                                                                  "monthly"         => "Monthly (on the 1st)",
                                                                                  "yearly"          => "Yearly (on 1st Jan)",
                                                                                  "follow_schedule" => "Follow Schedule Below")),

                        "sources_mode"=> array("LABEL"                  => $arrLang["Field Name"],
                                               "REQUIRED"               => "no",
                                               "INPUT_TYPE"             => "SELECT",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "dst"             => "Destination",
                                                                                  "src"             => "Source",
                                                                                  "dstchannel"      => "Dst. Channel")),

                        "recipient"   => array("LABEL"                  => $arrLang["Email Address"],
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "TEXT",
                                               "INPUT_EXTRA_PARAM"      => "",
                                               "VALIDATION_TYPE"        => "email",
                                               "VALIDATION_EXTRA_PARAM" => ""),

                        "daysrange"   => array("LABEL"                  => $arrLang["Depth Days"],
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "TEXT",
                                               "INPUT_EXTRA_PARAM"      => "",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => ""),

                        "minutes"     => array("LABEL"                  => $arrLang["Minutes"],
                                               "REQUIRED"               => "no",
                                               "INPUT_TYPE"             => "SELECT",
                                               "SIZE"                   => "12' style='width:85",
                                               "MULTIPLE"               => "true",
                                               "VALIDATION_TYPE"        => "numeric_array",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "0"   => "0","1"    => "1","2"    => "2","3"    => "3",
                                                                                  "4"   => "4","5"    => "5","6"    => "6","7"    => "7",
                                                                                  "8"   => "8","9"    => "9","10"   => "10","11"  => "11",
                                                                                  "12"  => "12","13"  => "13","14"  => "14","15"  => "15",
                                                                                  "16"  => "16","17"  => "17","18"  => "18","19"  => "19",
                                                                                  "20"  => "20","21"  => "21","22"  => "22","23"  => "23",
                                                                                  "24"  => "24","25"  => "25","26"  => "26","27"  => "27",
                                                                                  "28"  => "28","29"  => "29","30"  => "30","31"  => "31",
                                                                                  "32"  => "32","33"  => "33","34"  => "34","35"  => "35",
                                                                                  "36"  => "36","37"  => "37","38"  => "38","39"  => "39",
                                                                                  "40"  => "40","41"  => "41","42"  => "42","43"  => "43",
                                                                                  "44"  => "44","45"  => "45","46"  => "46","47"  => "47",
                                                                                  "48"  => "48","49"  => "49","50"  => "50","51"  => "51",
                                                                                  "52"  => "52","53"  => "53","54"  => "54","55"  => "55",
                                                                                  "56"  => "56","57"  => "57","58"  => "58","59"  => "59")),

                        "hours"       => array("LABEL"                  => $arrLang["Hours"],
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "SELECT",
                                               "SIZE"                   => "12' style='width:85",
                                               "MULTIPLE"               => "true",
                                               "VALIDATION_TYPE"        => "numeric_array",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "0"   => "0","1"    => "1","2"    => "2","3"    => "3",
                                                                                  "4"   => "4","5"    => "5","6"    => "6","7"    => "7",
                                                                                  "8"   => "8","9"    => "9","10"   => "10","11"  => "11",
                                                                                  "12"  => "12","13"  => "13","14"  => "14","15"  => "15",
                                                                                  "16"  => "16","17"  => "17","18"  => "18","19"  => "19",
                                                                                  "20"  => "20","21"  => "21","22"  => "22","23"  => "23")),

                        "days"        => array("LABEL"                  => $arrLang["Days"],
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "SELECT",
                                               "SIZE"                   => "12' style='width:85",
                                               "MULTIPLE"               => "true",
                                               "VALIDATION_TYPE"        => "numeric_array",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "1"   => "1","2"    => "2","3"    => "3","4"   => "4",
                                                                                  "5"   => "5","6"    => "6","7"    => "7","8"   => "8",
                                                                                  "9"   => "9","10"   => "10","11"  => "11","12" => "12",
                                                                                  "13"  => "13","14"  => "14","15"  => "15","16" => "16",
                                                                                  "17"  => "17","18"  => "18","19"  => "19","20" => "20",
                                                                                  "21"  => "21","22"  => "22","23"  => "23","24" => "24",
                                                                                  "25"  => "25","26"  => "26","27"  => "27","28" => "28",
                                                                                  "29"  => "29","30"  => "30","31"  => "31")),

                        "months"      => array("LABEL"                  => $arrLang["Months"],
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "SELECT",
                                               "SIZE"                   => "12",
                                               "MULTIPLE"               => "yes",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "1"  => "January",   "2"  => "February",
                                                                                  "3"  => "March",     "4"  => "April", 
                                                                                  "5"  => "May",       "6"  => "June",
                                                                                  "7"  => "July",      "8"  => "August",
                                                                                  "9"  => "September", "10" => "October",
                                                                                  "11" => "November",  "12" => "December")),

                        "weekdays"    => array("LABEL"                  => $arrLang["Weekdays"],
                                               "REQUIRED"               => "no",
                                               "INPUT_TYPE"             => "SELECT",
                                               "SIZE"                   => "12",
                                               "MULTIPLE"               => "yes",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => "",
                                               "INPUT_EXTRA_PARAM"      => array( "1" => "Monday",    "2" => "Tuesday",
                                                                                  "3" => "Wednesday", "4" => "Thursday",
                                                                                  "5" => "Friday",    "6" => "Saturday",
                                                                                  "7" => "Sunday")),

                        "sources"     => array("LABEL"                  => $arrLang["Source"],
                                               "REQUIRED"               => "no",
                                               "INPUT_TYPE"             => "TEXTAREA",
                                               "COLS"                   => "15",
                                               "ROWS"                   => "8",
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => ""),
                         );

    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);

    if(isset($_POST['submit_create_schedule'])) {

		foreach (array_keys($arrFormElements) as $v) $arrFillUser[$v]='';
        	$arrFillUser["sources"]="example:\n1234567\nSIP/903\nor range: 1234560-1234567\nSIP/903-950";
		$arrFillUser["minutes"]='-1';
		$arrFillUser["hours"]='-1';

	        $oForm = new paloForm($smarty, $arrFormElements);
        	$contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_schedule.tpl", $arrLang['Create New Schedule'],$arrFillUser);

    } else if(isset($_GET['action']) && $_GET['action']=="edit") {

		$arrScheduleList=$oCron->getCronSchedule($_GET['id']);

                        $tmpFillUser                 = explode(" ", $arrScheduleList[0][3]);
                        $arrFillUser["minutes"]      = explode(",", $tmpFillUser[0]);
                        $arrFillUser["hours"]        = explode(",", $tmpFillUser[1]);
                        $arrFillUser["days"]         = explode(",", $tmpFillUser[2]);
      	                $arrFillUser["months"]       = explode(",", $tmpFillUser[3]);
                        $arrFillUser["weekdays"]     = explode(",", $tmpFillUser[4]);
                        $arrFillUser["name"]         = $arrScheduleList[0][1];
                        $arrFillUser["predefined"]   = $arrScheduleList[0][2];
                        $arrFillUser["sources_mode"] = $arrScheduleList[0][4];
                        $arrFillUser["recipient"]    = $arrScheduleList[0][5];
                        $arrFillUser["daysrange"]    = $arrScheduleList[0][6];
                        $arrFillUser["sources"]      = str_replace(";","\n",$arrScheduleList[0][7]);

        	$oForm = new paloForm($smarty, $arrFormElements);
	        $oForm->setEditMode();
        	$smarty->assign("id_bill_sendmail", $_GET['id']);

                $contenidoModulo = $oForm->fetchForm("$local_templates_dir/new_schedule.tpl", "{$arrLang['Edit Schedule']} \"" 
              . $arrFillUser['name'] . "\"", $arrFillUser);

    } else if(isset($_POST['submit_save_schedule'])) {

                   $oForm = new paloForm($smarty, $arrFormElements);

        	if($oForm->validateForm($_POST)) {
		   $oCron->createCronSchedule($_POST);

                if(!empty($oCron->errMsg)) {
                    // Ocurrio algun error aqui
                    $smarty->assign("mb_message", $arrLang["ERROR"].': '. $arrLang[$oCron->errMsg]);

                    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_schedule.tpl", $arrLang["Create New Schedule"], $_POST);

                } else header("Location: ?menu=billing_sendmail");

        	} else {
	            // Error
        	    $smarty->assign("mb_title", $arrLang["Validation Error"]);
	            $arrErrores=$oForm->arrErroresValidacion;
        	    $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
	
	            foreach($arrErrores as $k=>$v) $strErrorMsg .= "$k, ";
	            
	            $strErrorMsg .= "";
        	    $smarty->assign("mb_message", $strErrorMsg);
	            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_schedule.tpl", $arrLang["Create New Schedule"], $_POST);
	        }

    } else if(isset($_POST['submit_apply_changes'])) {

	       $oForm = new paloForm($smarty, $arrFormElements);
	       $oForm->setEditMode();

	       if($oForm->validateForm($_POST)) {
                       $oCron->updateCronSchedule($_POST);

                    if(!empty($oCron->errMsg)) {
                       // Ocurrio algun error aqui
                       $smarty->assign("mb_message", $arrLang["ERROR"].': '. $arrLang[$oCron->errMsg]);
                       $smarty->assign("id_bill_sendmail", $_POST['id_bill_sendmail']);
                       $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_schedule.tpl", $arrLang["Create New Schedule"], $_POST);

                    } else header("Location: ?menu=billing_sendmail");

	        } else {
        	       // Manejo de Error
	               $smarty->assign("mb_title", $arrLang["Validation Error"]);
                       $arrErrores=$oForm->arrErroresValidacion;
	               $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";

                       foreach($arrErrores as $k=>$v) $strErrorMsg .= "$k, ";
           
		       $strErrorMsg .= "";
        	       $smarty->assign("mb_message", $strErrorMsg);
                       $smarty->assign("id_bill_sendmail", $_POST['id_bill_sendmail']);

        	       $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_schedule.tpl", $arrLang["Edit Schedule"], $_POST);
	        }

    } else {

    	       if (isset($_GET['action']) && $_GET['action']=='delete') {
                         $oCron->deleteCronSchedule($_GET['id']);
    	       }
     
     	      $arrScheduleList = $oCron->getCronSchedule();
              $end = count($arrScheduleList);
                      
	      foreach($arrScheduleList as $Clist) {
         	       $arrTmp    = array();
	               $arrTmp[0] = $Clist[1];
                       $arrTmp[1] ="<div title=\"".$Clist[3]."\" align=\"left\">".$arrFormElements['predefined']['INPUT_EXTRA_PARAM'][$Clist[2]]."</div>";
                       $arrTmp[2] = $arrFormElements["sources_mode"]["INPUT_EXTRA_PARAM"][$Clist[4]];
                       $arrTmp[3] = $Clist[5];
                       $arrTmp[4] = $Clist[6];
                       $arrTmp[5] = "<a href='?menu=billing_sendmail&action=edit&id="  .$Clist[0]."'>{$arrLang['Edit']}</a>&nbsp;
	                             <a href='?menu=billing_sendmail&action=delete&id=".$Clist[0]."'>{$arrLang['Delete']}</a>";
                       $arrData[] = $arrTmp;
              }
        
	      $arrGrid = array("title"    => $arrLang["Schedules List"],
                       "icon"     => "images/list.png",
                       "width"    => "99%",
                       "start"    => ($end==0) ? 0 : 1,
                       "end"      => $end,
                       "total"    => $end,
                       "columns"  => array(0 => array("name" => $arrLang["Name"],          "property1" => ""),
                                           1 => array("name" => $arrLang["Predefined"],    "property1" => ""),
                                           2 => array("name" => $arrLang["Field Name"],    "property1" => ""),
                                           3 => array("name" => $arrLang["Email Address"], "property1" => ""),
                                           4 => array("name" => $arrLang["End Date"],      "property1" => ""),
                                           5 => array("name" => $arrLang["Action"],        "property1" => ""))
              );       
                       
              $oGrid = new paloSantoGrid($smarty);
              $oGrid->showFilter(
                       "<form style='margin-bottom:0;' method='POST' action='?menu=billing_sendmail'>" .
                       "<input type='submit' name='submit_create_schedule' value='{$arrLang['Create New Schedule']}' class='button'>".
                       "</form>");

              $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }

    return $contenidoModulo;
}
?>
