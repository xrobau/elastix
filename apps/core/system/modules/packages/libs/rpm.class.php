<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4                                                |
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
  $Id: rpm.class.php,v 1.0 2013-12-10 12:40:05 Luis Abarca Villacís.  labarca@palosanto.com Exp $*/

$root = $_SERVER["DOCUMENT_ROOT"];
require_once("$root/libs/misc.lib.php");
require_once "$root/libs/paloSantoJSON.class.php";

class core_QueryRpms{

    private $errMsg;
    private $pattern;
    private $pattern1;
    private $pattern2;

    public function core_QueryRpms()
    {
        $this->errMsg = NULL;
        $this->pattern = "/N:([[:word:][:blank:][:punct:]ñÑáéíóúÁÉÍÓÚ]+)\sV:([[:word:][:blank:][:punct:]ñÑáéíóúÁÉÍÓÚ]+)\sR:([[:word:][:blank:][:punct:]ñÑáéíóúÁÉÍÓÚ]+)/";
        $this->pattern1 = "/package\s([[:word:][:blank:][:punct:]ñÑáéíóúÁÉÍÓÚ]+)\sis\snot\sinstalled/";
        $this->pattern2 = "/el\spaquete\s([[:word:][:blank:][:punct:]ñÑáéíóúÁÉÍÓÚ]+)\sno\sestá\sinstalado/";
    }

    function listall($length){
        switch($length){
            case "correct":
                exec("/usr/bin/verify_rpm --listall",$output,$retval);
                $i=0;
                foreach($output as $line0){
                    if (preg_match($this->pattern,$line0)) {
                        $separator0 = explode(" ",$line0);
                        foreach($separator0 as $line1){
                            $separator1 = explode(":",$line1);
		                    $out0[] = $separator1[1];
                        }
		                $out1=array_chunk($out0,3);
                        $out[] = array(
                        	"Name" => $out1[$i][0],
                        	"Version" => $out1[$i][1],
                        	"Release" => $out1[$i][2],);
                        $i++;
                    }elseif(preg_match($this->pattern1,$line0)){
                            $separator0 = explode(" ",$line0);
                            $out[] = array(
                        	"Name" => $separator0[1],
                        	"Status" => "Not Installed",
                            );  
                    }elseif (preg_match($this->pattern2,$line0)){
                            $separator0 = explode(" ",$line0);
                            $out[] = array(
                        	"Name" => $separator0[2],
                        	"Status" => "Not Installed",
                            );
                    }
                }
                return $out;
            break;

            case "incorrect":
                $this->errMsg["fc"] = 'BADLENGTH';
                $this->errMsg["fm"] = 'Length of this url is not correct';
                $this->errMsg["fd"] = 'This URL not accept more options';
                return TRUE;
            break;
        }
    }

    function notinstalled(){
        exec("/usr/bin/verify_rpm --notinstalled",$output,$retval);
        foreach($output as $line0){
        if(preg_match($this->pattern1,$line0)){
                    $separator0 = explode(" ",$line0);
                    $out[] = array(
                	"Name" => $separator0[1],
                	"Status" => "Not Installed",
                    );  
            }elseif (preg_match($this->pattern2,$line0)){
                    $separator0 = explode(" ",$line0);
                    $out[] = array(
                	"Name" => $separator0[2],
                	"Status" => "Not Installed",
                    );
        }
        }
        return $out;
    }

    function installed(){
        exec("/usr/bin/verify_rpm --installed",$output,$retval);
        $i=0;
        foreach($output as $line0){
                $separator0 = explode(" ",$line0);
                foreach($separator0 as $line1){
                    $separator1 = explode(":",$line1);
		            $out0[] = $separator1[1];
                }
		        $out1=array_chunk($out0,3);
                $out[] = array(
                	"Name" => $out1[$i][0],
                	"Version" => $out1[$i][1],
                	"Release" => $out1[$i][2],);
                $i++;
        }
        return $out;
    }

    function onlyone($rpm){
        exec("/usr/bin/verify_rpm --onlyone ".$rpm,$output,$retval);
        if ( (!empty($output)) ){
            if(preg_match($this->pattern,$output[0])){
                $separator0 = explode(" ",$output[0]);
                foreach($separator0 as $line1){
                    $separator1 = explode(":",$line1);
		            $out0[] = $separator1[1];
                }
		        $out1=array_chunk($out0,3);
                $out = array(
                	"Name" => $out1[0][0],
                	"Version" => $out1[0][1],
                	"Release" => $out1[0][2],);
            }elseif(preg_match($this->pattern1,$output[0])){
                    $separator0 = explode(" ",$output[0]);
                    $out = array(
                	"Name" => $separator0[1],
                	"Status" => "Not Installed",
                    );  
            }elseif (preg_match($this->pattern2,$output[0])){
                    $separator0 = explode(" ",$output[0]);
                    $out = array(
                	"Name" => $separator0[2],
                	"Status" => "Not Installed",
                    );
            }else{
                $this->errMsg["fc"] = 'BADLENGTH';
                $this->errMsg["fm"] = 'Length of this url is not correct';
                $this->errMsg["fd"] = 'This URL not accept more options';
                return NULL;  
            }
            return $out;
        }else{
            $this->errMsg["fc"] = 'MISSINGRPM';
            $this->errMsg["fm"] = 'Length of this url is not correct';
            $this->errMsg["fd"] = 'This URL needs an extra parameter to query correctly';
            return NULL;
        }
    }

     /**
     * 
     * Function that returns the error message
     *
     * @return  string   Message error if had an error.
     */
    public function getError()
    {
        return $this->errMsg;
    }
    
}
?>
