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
  $Id: SOAP_MyExtension.class.php,v 1.0 2011-03-31 13:10:00 Alberto Santos F.  asantos@palosanto.com Exp $*/

$root = $_SERVER["DOCUMENT_ROOT"];
require_once("$root/modules/myex_config/libs/core.class.php");

class SOAP_MyExtension extends core_MyExtension
{
    /**
     * SOAP Server Object
     *
     * @var object
     */
    private $objSOAPServer;

    /**
     * Constructor
     *
     * @param  object   $objSOAPServer     SOAP Server Object
     */
    public function SOAP_MyExtension($objSOAPServer)
    {
        parent::core_MyExtension();
        $this->objSOAPServer = $objSOAPServer;
    }

    /**
     * Static function that calls to the function getFP of its parent
     *
     * @return  array     Array with the definition of the function points.
     */
    public static function getFP()
    {
        return parent::getFP();
    }

    /**
     * Function that implements the SOAP call to set the option call waiting to the extension of the authenticated user. If an error 
     * exists a SOAP fault is thrown
     * 
     * @param mixed request:
     *                  callWaiting:   (boolean) TRUE the call waiting will be set to 'on', FALSE it will be set to 'off'
     * @return  mixed   Array with boolean data, true if was successful or false if an error exists
     */
    public function setCallWaiting($request)
    {
        $return = parent::setCallWaiting($request->callWaiting);
        if(!$return){
            $eMSG = parent::getError();
            $this->objSOAPServer->fault($eMSG['fc'],$eMSG['fm'],$eMSG['cn'],$eMSG['fd'],'fault');
        }
        return array("return" => $return);
    }

    /**
     * Function that implements the SOAP call to set the option call monitor to the extension of the authenticated user. If an error 
     * exists a SOAP fault is thrown
     * 
     * @param mixed request:
     *                  recordingIN_external  (string) this option can be Always, Never or Don't Care
     *                  recordingIN_internal  (string) this option can be Always, Never or Don't Care
     *                  recordingON_demand    (string) this option can be Disabled or Enabled
     *                  recordingOUT_external (string) this option can be Always, Never or Don't Care
     *                  recordingOUT_internal (string) this option can be Always, Never or Don't Care
     *                  recordingPriority     (integer) this option can be a numeric value between 0 and 20
     * @return  mixed   Array with boolean data, true if was successful or false if an error exists
     */
    public function setCallMonitor($request)
    {
        $return = parent::setCallMonitor($request->recordingIN_external, $request->recordingIN_internal,
                                            $request->recordingON_demand, $request->recordingOUT_external,
                                            $request->recordingOUT_internal, $request->recordingPriority);
        if(!$return){
            $eMSG = parent::getError();
            $this->objSOAPServer->fault($eMSG['fc'],$eMSG['fm'],$eMSG['cn'],$eMSG['fd'],'fault');
        }
        return array("return" => $return);
    }

    /**
     * Function that implements the SOAP call to set the option do not Disturb to the extension of the authenticated user. If an error 
     * exists a SOAP fault is thrown
     * 
     * @param mixed request:
     *                  doNotDisturb:   (boolean) TRUE the do not Disturb option will be set to 'on', FALSE it will be set to 'off'
     * @return  mixed   Array with boolean data, true if was successful or false if an error exists
     */
    public function setDoNotDisturb($request)
    {
        $return = parent::setDoNotDisturb($request->doNotDisturb);
        if(!$return){
            $eMSG = parent::getError();
            $this->objSOAPServer->fault($eMSG['fc'],$eMSG['fm'],$eMSG['cn'],$eMSG['fd'],'fault');
        }
        return array("return" => $return);
    }

    /**
     * Function that implements the SOAP call to set the option call forward to the extension of the authenticated user. If an error 
     * exists a SOAP fault is thrown
     * 
     * @param mixed request:
     *                  callForward:                        (boolean,Optional) true will set a call forward, false will not
     *                  phoneNumberCallForward:             (integer, Optional) Number for the call forward
     *                  callForwardUnavailable:             (boolean, Optional) true will set a call forward on unavailable, false 
     *                                                                          will not
     *                  phoneNumberCallForwardUnavailable:  (integer, Optional) Number for the call forward on unavailable
     *                  callForwardBusy:                    (boolean, Optional) true will set a call forward on busy, false will not
     *                  $phoneNumberCallForwardBusy:        (integer, Optional) Number for the call forward on busy
     * @return  mixed   Array with boolean data, true if was successful or false if an error exists
     */
    public function setCallForward($request)
    {
        $return = parent::setCallForward(
                            $request->callForward,$request->phoneNumberCallForward,
                            $request->callForwardUnavailable,$request->phoneNumberCallForwardUnavailable,
                            $request->callForwardBusy,$request->phoneNumberCallForwardBusy);
        if(!$return){
            $eMSG = parent::getError();
            $this->objSOAPServer->fault($eMSG['fc'],$eMSG['fm'],$eMSG['cn'],$eMSG['fd'],'fault');
        }
        return array("return" => $return);
    }
}
?>