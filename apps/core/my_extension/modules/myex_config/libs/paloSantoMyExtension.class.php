<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-31                                               |
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
  $Id: paloSantoMyExtension.class.php,v 1.1 2010-08-09 10:08:51 Mercy Anchundia manchundia@palosanto.com Exp $ */

require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

class paloSantoMyExtension {
    var $errMsg;
    var $astman;

    /* La siguiente estructura declara, para cada estado de extensión, los
     * cambios que tienen que hacerse para que el estado tome efecto completo
     * en el dialplan de FreePBX. La estructura define lo siguiente:
     *
     * DB_FLAG_KEY      Clave en astdb de la bandera base para uso de FreePBX
     *                  que indica si el estado está activo o no. Esta bandera
     *                  es IGNORADA por Asterisk.
     * DB_FLAG_STATES   El primer elemento es el valor de la clave DB_FLAG_KEY
     *                  para estado inactivo, y el segundo elemento es el valor
     *                  para el estado activo. Un valor NULL indica BORRAR la
     *                  clave.
     * DEVICE_STATE     Si está definido, el primer elemento es el valor a
     *                  asignar de variable vía DEVICE_STATE cuando el estado
     *                  está inactivo. El segundo, cuando está activo.
     * DEVICES          Estructura que define los hints sobre los cuales aplicar
     *                  DEVICE_STATE:
     *      BASE_HINT   Hint base que sólo depende de {EXTENSION}
     *      DEVICE_LIST Lista de dispositivos adicionales separada con &
     *      DEVICE_HINT Hint de un dispositivo en la lista de dispositivos, que
     *                  depende de {DEVICE} para cada elemento de la lista.
     *
     * En todas las definiciones anteriores, {EXTENSION} se reemplaza con el
     * valor de la extensión manipulada, y {NUMBER} con el valor del número
     * asignado al estado (para Call Forward y similares). Se usa {DEVICE} para
     * hints adicionales de cada lista de dispositivos.
     */
    private $_deviceStateChanges = array(
        /* Do Not Disturb */
        'DND'       =>  array(
            'DB_FLAG_KEY'   =>  'DND/{EXTENSION}',
            'DB_FLAG_STATES'=>  array(NULL, 'YES'),
            'DEVICE_STATE'  =>  array('NOT_INUSE', 'BUSY'),
            'DEVICES'       =>  array(
                'BASE_HINT'     =>  'Custom:DND{EXTENSION}',
                'DEVICE_LIST'   =>  'AMPUSER/{EXTENSION}/device',   // <-- compartido entre todos?
                'DEVICE_HINT'   =>  'Custom:DEVDND{DEVICE}',
            ),
        ),
        /* Call Waiting */
        'CW'        =>  array(
            'DB_FLAG_KEY'   =>  'CW/{EXTENSION}',
            'DB_FLAG_STATES'=>  array(NULL, 'ENABLED'),
        ),
        /* Call Forward, incondicional */
        'CF'        =>  array(
            'DB_FLAG_KEY'   =>  'CF/{EXTENSION}',
            'DB_FLAG_STATES'=>  array(NULL, '{NUMBER}'),
            'DEVICE_STATE'  =>  array('NOT_INUSE', 'BUSY'),
            'DEVICES'       =>  array(
                'BASE_HINT'     =>  'Custom:CF{EXTENSION}',
                'DEVICE_LIST'   =>  'AMPUSER/{EXTENSION}/device',   // <-- compartido entre todos?
                'DEVICE_HINT'   =>  'Custom:DEVCF{DEVICE}',
            ),
        ),
        /* Call Forward, si no está disponible */
        'CFU'       =>  array(
            'DB_FLAG_KEY'   =>  'CFU/{EXTENSION}',
            'DB_FLAG_STATES'=>  array(NULL, '{NUMBER}'),
        ),
        /* Call Forward, si está ocupado */
        'CFU'       =>  array(
            'DB_FLAG_KEY'   =>  'CFB/{EXTENSION}',
            'DB_FLAG_STATES'=>  array(NULL, '{NUMBER}'),
        ),
        /* Follow Me */
        'FOLLOWME'  =>  array(
            'DB_FLAG_KEY'   =>  'AMPUSER/{EXTENSION}/followme/ddial',
            'DB_FLAG_STATES'=>  array('EXTENSION', 'DIRECT'),
            'DEVICE_STATE'  =>  array('NOT_INUSE', 'INUSE'),
            'DEVICES'       =>  array(
                // OJO: no hay BASE_HINT
                'DEVICE_LIST'   =>  'AMPUSER/{EXTENSION}/device',   // <-- compartido entre todos?
                'DEVICE_HINT'   =>  'Custom:FOLLOWME{DEVICE}',
            ),
        ),
    );

    private $_usedevstate = FALSE;

    function paloSantoMyExtension()
    {
        $this->astman = null;
        if (is_readable('/etc/amportal.conf')) {
            $ini = parse_ini_file('/etc/amportal.conf');
            $this->_usedevstate = (isset($ini['USEDEVSTATE']) &&
                in_array(strtolower($ini['USEDEVSTATE']), array('1', 'true')));
        }
    }

    public function AMI_OpenConnect()
    {
        $astman = new AGI_AsteriskManager();
        $root   = $_SERVER["DOCUMENT_ROOT"];
        if(!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin("$root/"))){
            $this->errMsg = "Error connect AGI_AsteriskManager";
            $this->astman = null;
            return null;
        }

        $this->astman = $astman;
        return $astman;
    }

    public function AMI_CloseConnect()
    {
        $this->astman->disconnect();
    }

    private function _setConfig($cfgkey, $extension, $enable, $number = '')
    {
        // Evaluar y asignar/borrar bandera de FreePBX
        list($db_family, $db_key) = explode('/', str_replace(
            '{EXTENSION}', $extension,
            $this->_deviceStateChanges[$cfgkey]['DB_FLAG_KEY']), 2);
        $dbflag_states = $this->_deviceStateChanges[$cfgkey]['DB_FLAG_STATES'];
        for ($i = 0; $i < count($dbflag_states); $i++) {
            if (!is_null($dbflag_states[$i])) $dbflag_states[$i] = str_replace(
                array('{EXTENSION}', '{NUMBER}'),
                array($extension,     $number),
                $dbflag_states[$i]);
        }
        $db_val = $dbflag_states[$enable ? 1 : 0];
        $r = (is_null($db_val))
            ? $this->astman->database_del($db_family, $db_key)
            : $this->astman->database_put($db_family, $db_key, $db_val);
        if (!$r) return FALSE;

        if (!$this->_usedevstate) return TRUE;
        if (!isset($this->_deviceStateChanges[$cfgkey]['DEVICE_STATE'])) return TRUE;

        // Evaluar lista de hints a modificar
        $hintstate = $this->_deviceStateChanges[$cfgkey]['DEVICE_STATE'][$enable ? 1 : 0];
        $hintlist = array();
        if (isset($this->_deviceStateChanges[$cfgkey]['DEVICES']['BASE_HINT'])) {
            $hintlist[] = str_replace('{EXTENSION}', $extension,
                $this->_deviceStateChanges[$cfgkey]['DEVICES']['BASE_HINT']);
        }
        if (isset($this->_deviceStateChanges[$cfgkey]['DEVICES']['DEVICE_LIST'])) {
            list($db_family, $db_key) = explode('/', str_replace(
                '{EXTENSION}', $extension,
                $this->_deviceStateChanges[$cfgkey]['DEVICES']['DEVICE_LIST']), 2);
            $devicehint = $this->_deviceStateChanges[$cfgkey]['DEVICES']['DEVICE_HINT'];
            $r = $this->astman->database_get($db_family, $db_key);
            if ($r === FALSE) return FALSE;
            foreach (explode('&', $r) as $dev) {
                $hintlist[] = str_replace(
                    array('{EXTENSION}', '{NUMBER}', '{DEVICE}'),
                    array($extension,     $number,   $dev),
                    $devicehint);
            }
        }

        foreach ($hintlist as $hint) {
            /* Es necesario invocar send_request directamente porque el método
             * SetVar asume que se requiere un Channel pero los hints existen
             * con independencia de un canal. */
            $r = $this->astman->send_request('SetVar', array(
                'Variable'  =>  'DEVICE_STATE('.$hint.')',
                'Value'     =>  $hintstate,
            ));
            if (!(is_array($r) && isset($r['Response']) && $r['Response'] == 'Success'))
                return FALSE;
        }

        return TRUE;
    }

    function setConfig_CallWaiting($enableCW,$extension)
    {
        $enableCW = trim(strtolower($enableCW));
        $return = false;

        $return = $this->_setConfig('CW', $extension, ($enableCW == 'on'));
        if($return === false)
            $this->errMsg = "Error processing CallWaiting";

        return $return;
    }

    function setConfig_DoNotDisturb($enableDND,$extension)
    {
        $enableDND = trim(strtolower($enableDND));
        $return = false;

        $return = $this->_setConfig('DND', $extension, ($enableDND == 'on'));
        if($return === false)
            $this->errMsg = "Error processing Do Not Disturb";

        return $return;
    }

    function setConfig_CallForward($enableCF,$phoneNumberCF,$extension)
    {
        $enableCF = trim(strtolower($enableCF));
        $return = false;

        $enableCF = ($enableCF == 'on');
        if ($enableCF && !preg_match("/^[0-9]+$/", $phoneNumberCF)) {
            $this->errMsg = "Please check your phone number for Call Forward";
            return false;
        }
        $return = $this->_setConfig('CF', $extension, $enableCF, $phoneNumberCF);
        if($return === false)
            $this->errMsg = "Error processing Call Forward";

        return $return;
    }

    function setConfig_CallForwardOnUnavail($enableCFU,$phoneNumberCFU,$extension)
    {
        $enableCFU = trim(strtolower($enableCFU));
        $return = false;

        $enableCFU = ($enableCFU == 'on');
        if ($enableCFU && !preg_match("/^[0-9]+$/", $phoneNumberCFU)) {
            $this->errMsg = "Please check your phone number for Call Forward On Unavailable";
            return false;
        }
        $return = $this->_setConfig('CFU', $extension, $enableCFU, $phoneNumberCFU);
        if($return === false)
            $this->errMsg = "Error processing Call Forward on Unavailable";

        return $return;
    }

    function setConfig_CallForwardOnBusy($enableCFB,$phoneNumberCFB,$extension)
    {
        $enableCFB = trim(strtolower($enableCFB));
        $return = false;

        $enableCFB = ($enableCFB == 'on');
        if ($enableCFB && !preg_match("/^[0-9]+$/", $phoneNumberCFB)) {
            $this->errMsg = "Please check your phone number for Call Forward On Busy";
            return false;
        }
        $return = $this->_setConfig('CFU', $extension, $enableCFB, $phoneNumberCFB);
        if($return === false)
            $this->errMsg = "Error processing Call Forward on Busy";

        return $return;
    }

    function getConfig_CallWaiting($extension)
    {
        $return = $this->astman->database_get("CW",$extension);
        if($return != false && $return=="ENABLED")
             $return = "on";
        else $return = "off";
        return $return;
    }

    function getConfig_DoNotDisturb($extension)
    {
        $return = $this->astman->database_get("DND",$extension);
        if($return != false && $return=="YES")
                $return = "on";
        else $return = "off";
        return $return;
    }

    function getConfig_CallForwarding($extension)
    {
            $return = array();
            $r = $this->astman->database_get("CF",$extension);
            if($r != false && $r!=""){
               $return["enable"] = "on";
               $return["phoneNumber"] = $r;
            }else $return["enable"] = "off";
            return $return;
    }

    function getConfig_CallForwardingOnUnavail($extension)
    {
            $return = array();
            $r = $this->astman->database_get("CFU",$extension);
            if($r != false && $r!=""){
               $return["enable"] = "on";
               $return["phoneNumber"] = $r;
            }else $return["enable"] = "off";
            return $return;
    }

    function getConfig_CallForwardingOnBusy($extension)
    {
            $return = array();
            $r = $this->astman->database_get("CFB",$extension);
            if($r != false && $r!=""){
               $return["enable"] = "on";
               $return["phoneNumber"] = $r;
            }else $return["enable"] = "off";
            return $return;
    }

    //database get AMPUSER/10004 cidname
    function getExtensionCID($extension)
    {
        $return = false;
        $r = $this->astman->database_get("AMPUSER","$extension/cidname");
        if($r != false && $r!="")
             $return  = $r;

        return $return;
    }

    /*Recordings*/
    function setRecordSettings($extension,$arrRecordingStatus)
    {
        if(!in_array($arrRecordingStatus['recording_in_external'],array("always","dontcare","never"))){
            $this->errMsg = "Inbound External Calls option is not valid";
            return false;
        }

        if(!in_array($arrRecordingStatus['recording_out_external'],array("always","dontcare","never"))){
            $this->errMsg = "Outbound External Calls option is not valid";
            return false;
        }

        if(!in_array($arrRecordingStatus['recording_in_internal'],array("always","dontcare","never"))){
            $this->errMsg = "Inbound Internal Calls option is not valid";
            return false;
        }

        if(!in_array($arrRecordingStatus['recording_out_internal'],array("always","dontcare","never"))){
            $this->errMsg = "Outbound Internal Calls option is not valid";
            return false;
        }

        if(!in_array($arrRecordingStatus['recording_ondemand'],array("disabled","enabled"))){
            $this->errMsg = "On Demand Recording  option is not valid";
            return false;
        }

        if(!preg_match("/^[0-9]+$/",$arrRecordingStatus['recording_priority'])){
            $this->errMsg = "Record Priority Policy is not numeric";
            return false;
        }
        else if(!($arrRecordingStatus['recording_priority'] >=0 && $arrRecordingStatus['recording_priority'] <=20)){
            $this->errMsg = "Record Priority Policy must be a value between 0 and 20";
            return false;
        }

        $r1 = $this->astman->database_put("AMPUSER",$extension."/recording/in/external","\"".$arrRecordingStatus['recording_in_external']."\"");
        $r2 = $this->astman->database_put("AMPUSER",$extension."/recording/out/external","\"".$arrRecordingStatus['recording_out_external']."\"");
        $r3 = $this->astman->database_put("AMPUSER",$extension."/recording/in/internal","\"".$arrRecordingStatus['recording_in_internal']."\"");
        $r4 = $this->astman->database_put("AMPUSER",$extension."/recording/out/internal","\"".$arrRecordingStatus['recording_out_internal']."\"");
        $r5 = $this->astman->database_put("AMPUSER",$extension."/recording/ondemand","\"".$arrRecordingStatus['recording_ondemand']."\"");
        $r6 = $this->astman->database_put("AMPUSER",$extension."/recording/priority","\"".$arrRecordingStatus['recording_priority']."\"");

        if($r1 && $r2 && $r3 && $r4 && $r5 && $r6)
            return true;
        else{
            $this->errMsg = "Error processing Recording options";
            return false;
        }
    }

    private function getTechnology($extension)
    {    $technology = null;
         $r = $this->astman->database_get("DEVICE","$extension/dial");
         if($r != false && $r!=""){
            $arrDataTech          = explode("/",$r);
            $technology           = strtolower(trim($arrDataTech[0]));//i.e: sip
         }
         return $technology;
    }

    function getRecordSettings($extension)
    {
      $return = array(
          "recording_in_external"  => "dontcare",
          "recording_in_internal"  => "dontcare",
          "recording_ondemand"     => "disabled",
          "recording_out_external" => "dontcare",
          "recording_out_internal" => "dontcare",
          "recording_priority"     => "10"
      );

      $r = $this->astman->database_show("AMPUSER/$extension/recording");
      if(is_array($r) && count($r)>0){
        if(isset($r["/AMPUSER/$extension/recording/in/external"]))
            $return['recording_in_external']  = $r["/AMPUSER/$extension/recording/in/external"];

        if(isset($r["/AMPUSER/$extension/recording/in/internal"]))
            $return['recording_in_internal']  = $r["/AMPUSER/$extension/recording/in/internal"];

        if(isset($r["/AMPUSER/$extension/recording/ondemand"]))
            $return['recording_ondemand']     = $r["/AMPUSER/$extension/recording/ondemand"];

        if(isset($r["/AMPUSER/$extension/recording/out/external"]))
            $return['recording_out_external'] = $r["/AMPUSER/$extension/recording/out/external"];

        if(isset($r["/AMPUSER/$extension/recording/out/internal"]))
            $return['recording_out_internal'] = $r["/AMPUSER/$extension/recording/out/internal"];

        if(isset($r["/AMPUSER/$extension/recording/priority"]))
            $return['recording_priority']     = $r["/AMPUSER/$extension/recording/priority"];
      }
      return $return;
    }
}
?>