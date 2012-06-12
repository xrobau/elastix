<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-01 05:09:57 Bruno Macias <bmacias@palosanto.com> Exp $ */

class paloSantoAntispam {
    var $fileMaster;
    var $fileLocal;
    var $folderPostfix;
    var $folderSpamassassin;
    var $errMsg;

    function paloSantoAntispam($pathPostfix,$pathSpamassassin,$fileMaster,$fileLocal)
    {
        $this->fileLocal     = $fileLocal;
        $this->fileMaster    = $fileMaster;
        $this->folderPostfix = $pathPostfix;
        $this->folderSpamassassin = $pathSpamassassin;
    }

    /*HERE YOUR FUNCTIONS*/

    function isActiveSpamFilter()
    {
        // Trato de abrir el archivo de configuracion 
        $step_one_config = false;
        $step_two_config = false;
        if($fh = fopen($this->fileMaster, "r")) {
            while($line_file = fgets($fh, 4096)) {
                //line to valid:smtp      inet  n       -       n       -       -       smtpd
                if(ereg("(smtp[[:space:]]{1,}inet[[:space:]]{1,}n[[:space:]]{1,}-[[:space:]]{1,}n[[:space:]]{1,}-[[:space:]]{1,}-[[:space:]]{1,}smtpd)",$line_file,$arrReg)){
                        $line_file_next = fgets($fh, 4096);
                        //line to valid:  -o content_filter=spamfilter:dummy
                        if(ereg("([[:space:]]{1,}-o[[:space:]]{1,}content_filter=spamfilter:dummy)",$line_file_next,$arrReg)){
                            $step_one_config = true;
                        }
                }
                //line to valid:spamfilter unix -       n       n       -       -       pipe
                if(ereg("(spamfilter[[:space:]]{1,}unix[[:space:]]{1,}-[[:space:]]{1,}n[[:space:]]{1,}n[[:space:]]{1,}-[[:space:]]{1,}-[[:space:]]{1,}pipe)",$line_file,$arrReg)){
                        $line_file_next = fgets($fh, 4096);
                        //line to valid:  flags=Rq user=spamfilter argv=/usr/local/bin/spamfilter.sh -f ${sender} -- ${recipient}
                        if(ereg("([[:space:]]{1,}flags=Rq[[:space:]]{1,}user=spamfilter[[:space:]]{1,}argv=/usr/local/bin/spamfilter.sh[[:space:]]{1,}-f[[:space:]]{1,}\\$\{sender\}[[:space:]]{1,}--[[:space:]]{1,}\\$\{recipient\})",$line_file_next,$arrReg)){
                            $step_two_config = true;
                        }
                }
            }
        }
        return array("isOk" => $step_one_config && $step_two_config, "is_smtp" => $step_one_config, "is_spamfilter" => $step_two_config);
    }

    function getValueRequiredHits()
    {
        // Trato de abrir el archivo de configuracion 
        $value_required_hits = false;
        if($fh = fopen($this->fileLocal, "r")) {
            while($line_file = fgets($fh, 4096)) {
                //line to valid:required_hits 5
                if(ereg("[[:space:]]*required_hits[[:space:]]+([[:digit:]]{0,2})",$line_file,$arrReg)){
                        $value_required_hits = $arrReg[1];
                }
            }
        }
        return $value_required_hits;
    }

    function activateSpamFilter()
    {
        global $arrLangModule;
        $return = false;

        $cmd_one  =  "sed -ie 's/smtp[[:space:]]\{1,\}inet[[:space:]]\{1,\}n[[:space:]]\{1,\}-[[:space:]]\{1,\}n[[:space:]]\{1,\}-[[:space:]]\{1,\}-[[:space:]]\{1,\}smtpd/";
        $cmd_one .=  "smtp      inet  n       -       n       -       -       smtpd\\n  -o content_filter=spamfilter:dummy/' {$this->fileMaster}";

        $cmd_two  = "echo '#\n#Add by Elastix\n#\nspamfilter unix -       n       n       -       -       pipe\n";
        $cmd_two .= "  flags=Rq user=spamfilter argv=/usr/local/bin/spamfilter.sh -f \${sender} -- \${recipient}' >> {$this->fileMaster}";

        $arrSpamFilter = $this->isActiveSpamFilter();

        exec("sudo -u root chmod 777 {$this->folderPostfix}",$arrConsole1,$flatStatus1);
        exec("sudo -u root chmod 777 {$this->fileMaster}",$arrConsole2,$flatStatus2);

        if($flatStatus1 != 0 || $flatStatus2 != 0){
            $this->errMsg = $arrLangModule["Failed activate spam filter"];
            return $return;
        }

        if($flatStatus1 == 0 && $flatStatus2 == 0){
            $flatStatus3 = 0;
            if(!$arrSpamFilter["is_smtp"])
                exec($cmd_one,$arrConsole3,$flatStatus3);

            if($flatStatus3 == 0){
                $flatStatus4 = 0;
                if(!$arrSpamFilter["is_spamfilter"])
                    exec($cmd_two,$arrConsole4,$flatStatus4); 
                if($flatStatus4 == 0) $return = true;
                else $this->errMsg = $arrLangModule["Commad failed, try activate spam filter"];
            }
            else $this->errMsg = $arrLangModule["Commad failed, try activate spam filter"];
        }
        exec("sudo -u root chmod 644 {$this->fileMaster}",$arrConsole5,$flatStatus5);
        exec("sudo -u root chmod 755 {$this->folderPostfix}",$arrConsole6,$flatStatus6);
        exec("sudo -u root chown root.root {$this->fileMaster}",$arrConsole5,$flatStatus5);
        return $return;
    }

    function disactivateSpamFilter()
    {
        global $arrLangModule;
        $return = false;

        $cmd_one  = "sed -ie 's/[[:space:]]\{0,\}-o[[:space:]]\{1,\}content_filter=spamfilter:dummy//' {$this->fileMaster}";
        $cmd_two  = "sed -ie 's/spamfilter unix -       n       n       -       -       pipe//' {$this->fileMaster}";
        $cmd_thr  = "sed -ie 's/  flags=Rq user=spamfilter argv=\/usr\/local\/bin\/spamfilter.sh -f \${sender} -- \${recipient}//' {$this->fileMaster}";

        $arrSpamFilter = $this->isActiveSpamFilter();

        exec("sudo -u root chmod 777 {$this->folderPostfix}",$arrConsole1,$flatStatus1);
        exec("sudo -u root chmod 777 {$this->fileMaster}",$arrConsole2,$flatStatus2);

        if($flatStatus1 != 0 || $flatStatus2 != 0){
            $this->errMsg = $arrLangModule["Failed disactivate spam filter"];
            return $return;
        }

        if($flatStatus1 == 0 && $flatStatus2 == 0){
            if($arrSpamFilter["is_smtp"] || $arrSpamFilter["is_spamfilter"]){
                exec($cmd_one,$arrConsole3,$flatStatus3);
                if($flatStatus3 == 0){
                    exec($cmd_thr,$arrConsole4,$flatStatus4); 
                    exec($cmd_two,$arrConsole4,$flatStatus4); 
                    if($flatStatus4 == 0) $return = true;
                    else $this->errMsg = $arrLangModule["Commad failed, try disactivate spam filter"];
                }
                else $this->errMsg = $arrLangModule["Commad failed, try disactivate spam filter"];
            }
        }

        exec("sudo -u root chmod 644 {$this->fileMaster}",$arrConsole5,$flatStatus5);
        exec("sudo -u root chmod 755 {$this->folderPostfix}",$arrConsole6,$flatStatus6);
        exec("sudo -u root chown root.root {$this->fileMaster}",$arrConsole5,$flatStatus5);
        return $return;
    }

    function changeThoroughnessLevel($level)
    {
        global $arrLangModule;

        $cmd ="sed -ie 's/required_hits[[:space:]]\{1,\}[[:digit:]]\{0,2\}/required_hits $level/' {$this->fileLocal}";

        exec("sudo -u root chmod 777 {$this->folderSpamassassin}",$arrConsole1,$flatStatus1);
        exec("sudo -u root chmod 777 {$this->fileLocal}",$arrConsole2,$flatStatus2);

        if($flatStatus1 == 0 && $flatStatus2 == 0){
            exec($cmd,$arrConsole3,$flatStatus3);
            exec("sudo -u root chmod 644 {$this->fileLocal}",$arrConsole4,$flatStatus4);
            exec("sudo -u root chown root.root {$this->fileLocal}",$arrConsole4,$flatStatus4);
            exec("sudo -u root chmod 755 {$this->folderSpamassassin}",$arrConsole5,$flatStatus5);
            if($flatStatus3 == 0) return true;
            else $this->errMsg = $arrLangModule["Commad failed, try change thoroughness level"];
        }
        else $this->errMsg = $arrLangModule["Failed change thoroughness level"];
        return false;
    }
}
?>