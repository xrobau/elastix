<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-4                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-23 11:09:23 aflores@palosanto.com Exp $ */

class paloSantoTexttoWav {
    var $errMsg;

	
    function paloSantoTexttoWav()
    {
    }

    function TextoWav($path, $format, $message)
    {
        global $arrLang;

        $text_file = $path.'/tts.txt';
        $wave_file = $path.'/tts.wav';
        $gsm_file  = $path.'/tts.gsm';

        $cmd1 = "/usr/bin/text2wave $text_file -F 8000 -o $wave_file -scale 4.0 -otype wav";
        $cmd2 = "/usr/bin/sox       $wave_file -r 8001    $gsm_file   resample -ql";

        $fh = fopen($text_file, "w+");
        if ($fh) {
            if(fwrite($fh, $message) == false){
                $this->errMsg = $arrLang["Unabled write file"];
                fclose($fh);
                return false;
            }
            fclose($fh);
        }
        else{
            $this->errMsg = $arrLang["Unabled open file"];
            return false;
        }

        if (file_exists($text_file)) {
            if($format == "wav"){
                exec($cmd1,$arrConsole1,$flatStatus1);
                if($flatStatus1 == 0)
                    return true;
                else{
                    $this->errMsg = $arrLang["Unabled create file wav"];
                    return false;
                }
            }
            else{
                exec($cmd1,$arrConsole1,$flatStatus1);
                if($flatStatus1 == 0){
                    exec($cmd2,$arrConsole2,$flatStatus2);
                    if($flatStatus2 == 0)
                        return true;
                    else{
                        $this->errMsg = $arrLang["Unabled create file gsm"];
                        return false;
                    }
                }
                else{
                    $this->errMsg = $arrLang["Unabled create file gsm"];
                    return false;
                }
            }
        }
        else{
            $this->errMsg = $arrLang["Not exists file"];
            return false;
        }
    }
}
?>
