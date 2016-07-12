<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                                |
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
  $Id: default.conf.php,v 1.1 2008-06-21 09:06:53 Jonathan Exp $ */

class paloSantoFoneBridge {
    var $_DB;
    var $errMsg;

    var $pathFileConfFoneBridge;

    function paloSantoFoneBridge($pathFile)
    {
        if(isset($pathFile))
            $this->pathFileConfFoneBridge = $pathFile;
        else
            $this->pathFileConfFoneBridge = "/etc/redfone.conf";
    }

    function saveFileConfFoneBridge($_POST)
    {
        global $arrLang;
        global $arrLangModule;

        //PASO 1: CREO EL CONTENIDO DEL ARCHIVO
        $contentFileFoneBridge = $this->createContentConfFoneBridge($_POST);
        exec("sudo -u root touch ".$this->pathFileConfFoneBridge,$arrConsole,$flagStatus0);
        exec("sudo -u root chown asterisk:asterisk ".$this->pathFileConfFoneBridge,$arrConsole,$flagStatus1);
        exec("sudo -u root chmod 666 ".$this->pathFileConfFoneBridge,$arrConsole,$flagStatus2);

        //PASO 2
        if($flagStatus0==0 && $flagStatus1==0 && $flagStatus2==0){
            if($fh_redfone = @fopen($this->pathFileConfFoneBridge, "w")) {
                fwrite($fh_redfone, $contentFileFoneBridge);
                fclose($fh_redfone);
            }
            else{
                $this->errMsg = $arrLang["Failed to configure the file configuration"].": ".$this->pathFileConfFoneBridge; 
                return false;
            }
        } 
        else{ 
            $this->errMsg = $arrLang["Failed to configure the file configuration"].": ".$this->pathFileConfFoneBridge; 
            return false;
        }

        exec("sudo -u root chown root:root ".$this->pathFileConfFoneBridge,$arrConsole,$flagStatus1);
        exec("sudo -u root chmod 644 ".$this->pathFileConfFoneBridge,$arrConsole,$flagStatus2);

        //$this->getConfigurationFoneBridge();

        if($flagStatus1==0 && $flagStatus2==0) 
            return true;
        else{
            $this->errMsg = $arrLangModule['The configure was successful, but was unable to reestablish the permissions of the file redfone.conf']; 
            return false;
        }
    }

    function fileRedFoneExists()
    {
        return file_exists($this->pathFileConfFoneBridge);
    }

    function getConfigurationFoneBridge()
    {
        $arrValores = array();
        $contSpan = 0;

        if(!file_exists($this->pathFileConfFoneBridge)){
            $arrValores['phone_bridge_ip'] = "";
            $arrValores['port_for_TDMoE']  = "";
            $arrValores['server_mac']      = "";
            $arrValores['span1_type']      = "";
            $arrValores['span2_type']      = "";
            $arrValores['span3_type']      = "";
            $arrValores['span4_type']      = "";
            $arrValores['span1_framing']   = "";
            $arrValores['span2_framing']   = "";
            $arrValores['span3_framing']   = "";
            $arrValores['span4_framing']   = "";
            $arrValores['span1_encoding']  = "";
            $arrValores['span2_encoding']  = "";
            $arrValores['span3_encoding']  = "";
            $arrValores['span4_encoding']  = "";
            $arrValores['timing_priority'] = "by_spans";
            $arrValores['priority1']       = "0";
            $arrValores['priority2']       = "1";
            $arrValores['priority3']       = "2";
            $arrValores['priority4']       = "3";
            $arrValores['existe_file_redfone'] = false;
            return $arrValores;
        }

        $hArchivo = fopen($this->pathFileConfFoneBridge, 'r');
        if ($hArchivo) {
            $arrValores['existe_file_redfone'] = true;
            while ($tupla = fgets($hArchivo, 4096))
            {
                if(preg_match("/fb[ |=]*(.*)/", $tupla, $regs))
                    $arrValores['phone_bridge_ip'] = $regs[1];
                else if(preg_match("/port[ |=]*(.*)/", $tupla, $regs))
                    $arrValores['port_for_TDMoE'] = $regs[1];
                else if(preg_match("/server[ |=]*(.*)/", $tupla, $regs))
                    $arrValores['server_mac'] = $regs[1];
                else if(preg_match("/^[[:space:]]*priorities[ |=]*(.*)/", $tupla, $regs)){
                    $arrPriorities = explode(",",$regs[1]);
                    $arrValores['priority1'] = $arrPriorities[0];
                    $arrValores['priority2'] = $arrPriorities[1];
                    $arrValores['priority3'] = $arrPriorities[2];
                    $arrValores['priority4'] = $arrPriorities[3];

                    if($arrPriorities[0]==0 && $arrPriorities[1]==0 && $arrPriorities[2]==0 && $arrPriorities[3]==0)
                        $arrValores['timing_priority'] = "internal"; 
                    else
                        $arrValores['timing_priority'] = "by_spans";
                }

                else if(preg_match("/#SPAN ([[:digit:]]) ([E|T]1)/", $tupla, $regs)){
                    $contSpan = $regs[1];
                    $indice = "span". $contSpan ."_type";
                    $arrValores[$indice] = $regs[2];
                }
                else if(preg_match("/framing[ |=]*(.*)/", $tupla, $regs)){
                    $indice = "span". $contSpan ."_framing";
                    $arrValores[$indice] = $regs[1];
                }
                else if(preg_match("/encoding[ |=]*(.*)/", $tupla, $regs)){
                    $indice = "span". $contSpan ."_encoding";
                    $arrValores[$indice] = $regs[1];
                }
                else if(preg_match("/(crc4)/", $tupla, $regs)){
                    $indice = "span". $contSpan ."_extra";
                    $arrValores[$indice] = $regs[1];
                }
                else if(preg_match("/(loopback)/", $tupla, $regs)){
                    $indice = "span". $contSpan ."_extra";
                    $arrValores[$indice] = $regs[1];
                }
                else if(preg_match("/(rbs)/", $tupla, $regs)){
                    $indice = "span". $contSpan ."_extra";
                    $arrValores[$indice] = $regs[1];
                }
            }
        }
        fclose($hArchivo);
        return $arrValores;
    }

    function executeFonulator($path = '/etc/redfone.conf')
    {
        global $arrLang;
        global $arrLangModule;

        $fonulatorCommand = NULL;
        $pathList = array_filter(
            array(
                '/usr/bin/fonulator',       // Para Elastix 2
                '/usr/local/bin/fonulator', // Para Elastix 1.6 con compilación a mano
            ), 
            'file_exists');
        if (count($pathList) <= 0) {
            $tmpError['head'] = $arrLang['ERROR'];
            $tmpError['body'] = $arrLangModule['Fonulator command does not exists.']." \"$fonulatorCommand\"";
            $this->errMsg = $tmpError;
            return false;
        } else {
            $fonulatorCommand = $pathList[0];
        }

        //Comando fonulator si existe
        exec("$fonulatorCommand -v $path",$arrConsole,$flagStatus);

        if( $flagStatus == 0){ //no hubo error
            return true;
        }
        else if(is_array($arrConsole) && count($arrConsole)>1){ //algun error conocido
            //Inicialización de estas variables de error para manejar algun futuro error no validado.
            $tmpError['head'] = $arrLang['ERROR'];
            $tmpError['body'] = $arrLangModule['In command fonulator']." \"$fonulatorCommand $path\"";

            if(preg_match("/Bad token in configuration file on line ([[:digit:]]+)/",$arrConsole[0],$arrToken)){
                $tmpError['head'] = $arrLangModule['Bad token in configuration file on line']." {$arrToken[1]}";
                $tmpError['body'] = $arrLangModule['treeParser: Bad or Unknown Configuration Token'];
            }
            else if(preg_match("/Error opening configuration file./",$arrConsole[0],$arrToken)){
                $tmpError['head'] = $arrLangModule['Error opening configuration file.'];
                $tmpError['body'] = $arrLangModule['fopen: No such file or directory']." $path";
            }
             else if(preg_match("/statusInitalize: Internal foneBRIDGE library error/",$arrConsole[1],$arrToken)){
                $tmpError['head'] = $arrLangModule['Connection Error'];
                $tmpError['body'] = $arrLangModule['Connection to device timed out! Check network or device power.'];
            }
            $this->errMsg = $tmpError;
            return false;
        }
        else{ //error desconocido
            $tmpError['head'] = $arrLang['ERROR'];
            $tmpError['body'] = $arrLangModule['In command fonulator']." \"$fonulatorCommand $path\"";
            $this->errMsg = $tmpError;
            return false;
        }
    }

    function getStatusFoneBridge($module_name)
    {
        global $arrLangModule;

        $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
        $fileStatus = "$base_dir/modules/$module_name/libs/paloSantoFoneBridge.status.info";
        if (!file_exists($fileStatus)){ 
            $this->setStatusFoneBridge($module_name,"no");
            return "<font color='#FF0000'>{$arrLangModule["No Configured"]}</font>";
        }
        else{
            exec("cat $fileStatus",$arrConsole,$flagStatus); 
            if($flagStatus==0 && is_array($arrConsole) && count($arrConsole)>0){
                $status = str_replace("configure=","",$arrConsole[0]);
                if($status == "yes")
                    return "<font color='#00AA00'>{$arrLangModule["Configured"]}</font>";
                else
                    return "<font color='#FF0000'>{$arrLangModule["No Configured"]}</font>";
            }
            else{ 
                $this->setStatusFoneBridge($module_name,"no");
                return "<font color='#FF0000'>{$arrLangModule["No Configured"]}</font>";
            }
        }
    }

    function setStatusFoneBridge($module_name, $status)
    {
        $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
        $fileStatus = "$base_dir/modules/$module_name/libs/paloSantoFoneBridge.status.info";
        exec("echo 'configure=$status' > $fileStatus",$arrConsole,$flagStatus);
    }

    function createContentConfFoneBridge($_POST)
    {
        $tpl = $this->getTemplateFileConfFoneBridge();

        if($_POST["timing_priority"]=="by_spans")
            $priorities = "{$_POST['priority1']},{$_POST['priority2']},{$_POST['priority3']},{$_POST['priority4']}";
        else $priorities = "0,0,0,0";

        $tpl = str_replace("{PHONE_BRIDGE_IP}", trim($_POST['phone_bridge_ip']), $tpl);
        $tpl = str_replace("{PORT_FOR_TDMOE}", trim($_POST['port_for_TDMoE']), $tpl);
        $tpl = str_replace("{SERVER_MAC}", strtoupper(trim($_POST['server_mac'])), $tpl);
        $tpl = str_replace("{PRIORITIES}", $priorities, $tpl);

        $tpl = str_replace("{FRAMING_1}", trim($_POST['span1_framing']), $tpl);
        $tpl = str_replace("{ENCODING_1}", trim($_POST['span1_encoding']), $tpl);
        $tpl = str_replace("{EXTRA_1}", trim($_POST['span1_extra']), $tpl);
        $tpl = str_replace("{TYPE_1}", trim($_POST['span1_type']), $tpl);

        $tpl = str_replace("{FRAMING_2}", trim($_POST['span2_framing']), $tpl);
        $tpl = str_replace("{ENCODING_2}", trim($_POST['span2_encoding']), $tpl);
        $tpl = str_replace("{EXTRA_2}", trim($_POST['span2_extra']), $tpl);
        $tpl = str_replace("{TYPE_2}", trim($_POST['span2_type']), $tpl);

        $tpl = str_replace("{FRAMING_3}", trim($_POST['span3_framing']), $tpl);
        $tpl = str_replace("{ENCODING_3}", trim($_POST['span3_encoding']), $tpl);
        $tpl = str_replace("{EXTRA_3}", trim($_POST['span3_extra']), $tpl);
        $tpl = str_replace("{TYPE_3}", trim($_POST['span3_type']), $tpl);

        $tpl = str_replace("{FRAMING_4}", trim($_POST['span4_framing']), $tpl);
        $tpl = str_replace("{ENCODING_4}", trim($_POST['span4_encoding']), $tpl);
        $tpl = str_replace("{EXTRA_4}", trim($_POST['span4_extra']), $tpl);
        $tpl = str_replace("{TYPE_4}", trim($_POST['span4_type']), $tpl);

        return $tpl;
    }

    function getTemplateFileConfFoneBridge()
    {
        $template = "[globals]\n".
                    "# IP-address of the IP Configuration port\n".
                    "# Factory defaults are; FB1=192.168.1.254 FB2=192.168.1.253\n".
                    "fb={PHONE_BRIDGE_IP}\n".
                    "# Which port to use for TDMoE Traffic (1 or 2)\n".
                    "port={PORT_FOR_TDMOE}\n".
                    "# Which Asterisk server destination MAC address for TDMoE Traffic?\n".
                    "server={SERVER_MAC}\n".
                    "# For 2.0 version firmware/hardware and above, specify priorities as\n".
                    "# priorities=0,1,2,3\n".
                    "# or for all internal timing\n".
                    "# priorities=0,0,0,0\n".
                    "priorities={PRIORITIES}\n".
                    "\n".
                    "#SPAN 1 {TYPE_1}\n".
                    "[span1]\n".
                    "framing={FRAMING_1}\n".
                    "encoding={ENCODING_1}\n".
                    "{EXTRA_1}\n".
                    "\n".
                    "#SPAN 2 {TYPE_2}\n".
                    "[span2]\n".
                    "framing={FRAMING_2}\n".
                    "encoding={ENCODING_2}\n".
                    "{EXTRA_2}\n".
                    "\n".
                    "#SPAN 3 {TYPE_3}\n".
                    "[span3]\n".
                    "framing={FRAMING_3}\n".
                    "encoding={ENCODING_3}\n".
                    "{EXTRA_3}\n".
                    "\n".
                    "#SPAN 4 {TYPE_4} \n".
                    "[span4]\n".
                    "framing={FRAMING_4}\n".
                    "encoding={ENCODING_4}\n".
                    "{EXTRA_4}\n".
                    "\n";
        return $template;
    }
}
?>
