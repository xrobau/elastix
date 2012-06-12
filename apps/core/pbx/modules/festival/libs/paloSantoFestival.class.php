<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-5                                               |
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
  $Id: paloSantoFestival.class.php,v 1.1 2011-04-14 11:04:34 Alberto Santos asantos@palosanto.com Exp $ */

class paloSantoFestival{
    /**
     * Description error message
     *
     * @var string
     */
    var $errMsg;

    /**
     * Constructor. It sets the attribute errMsg to an empty string
     *
     */
    function paloSantoFestival()
    {
        $this->errMsg = "";
    }

     /**
     * Function that verifies if the file /usr/share/festival/festival.scm is correctly configurated
     *
     * @return  boolean   true if the file if the file is correctly configurated, false if not
     */
    function isConfigurationFileCorrect()
    {
        $path = "/usr/share/festival/festival.scm";
        if(file_exists($path)){
            $defineTemplate = <<<TEMP
(define (tts_textasterisk string mode)
"(tts_textasterisk STRING MODE)
Apply tts to STRING. This function is specifically designed for
use in server mode so a single function call may synthesize the
string.
This function name may be added to the server safe functions."
   (let ((wholeutt (utt.synth (eval (list 'Utterance 'Text string)))))
      (utt.wave.resample wholeutt 8000)
      (utt.wave.rescale wholeutt 5)
      (utt.send.wave.client wholeutt)))
TEMP;
            $fileString = file_get_contents($path);
            if(strstr($fileString,$defineTemplate))
                return true;
            else
                return false;
        }
        else{
            $this->errMsg = _tr("File could not be found in the following path").": $path";
            return false;
        }
    }

    /**
     * Function that writes the necessary configuration in file /usr/share/festival/festival.scm in order to festival works
     *
     * @return  boolean   true if the file was correctly configurated, false if not
     */
    function setConfigurationFile()
    {
        $path = "/usr/share/festival/festival.scm";
        exec("sudo -u root chown asterisk.asterisk $path");
        $file = fopen($path,'a');
        $result = true;
        if($file){
            fwrite($file,"\n");
            $defineTemplate = <<<TEMP
(define (tts_textasterisk string mode)
"(tts_textasterisk STRING MODE)
Apply tts to STRING. This function is specifically designed for
use in server mode so a single function call may synthesize the
string.
This function name may be added to the server safe functions."
   (let ((wholeutt (utt.synth (eval (list 'Utterance 'Text string)))))
      (utt.wave.resample wholeutt 8000)
      (utt.wave.rescale wholeutt 5)
      (utt.send.wave.client wholeutt)))
TEMP;
            $result = fwrite($file,$defineTemplate);
            fclose($file);
        }
        exec("sudo -u root chown root.root $path");
        if(!$file || $result === false){
            $this->errMsg = _tr("Could not write the configuration on file").": $path";
            return false;
        }
        return true;
    }

    /**
     * Function that activates the festival service
     *
     * @return  boolean   true if the festival service is correctly activated, false if not
     */
    function activateFestival()
    {
        exec("sudo /sbin/service generic-cloexec festival restart",$result,$status);
        if($status==0)
            return true;
        return false;
    }

    /**
     * Function that deactives the festival service
     *
     * @return  boolean   true if the festival service is correctly deactivated, false if not
     */
    function deactivateFestival()
    {
        exec("sudo /sbin/service generic-cloexec festival stop",$result,$status);
        if($status==0)
            return true;
        return false;
    }

    /**
     * Function that verifies if the festival service is running
     *
     * @return  boolean   true if the festival service is running, false if not
     */
    function isFestivalActivated()
    {
        exec("sudo /sbin/service generic-cloexec festival status",$result,$status);
        if($status == 0){
            if(preg_match("/pid/",$result[0]))
                return true;
            return false;
        }
        else{
            $this->errMsg = _tr("Error determining status of festival");
            return false;
        }
    }

    /**
     * Function that returns the error attribute variable of this class
     *
     * @return  string   string with the error message
     */
    function getError()
    {
        return $this->errMsg;
    }
}
?>