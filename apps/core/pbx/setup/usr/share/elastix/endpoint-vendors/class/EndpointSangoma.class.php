<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
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
  $Id: dhcpconfig.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

class EndpointSangoma extends Endpoint
{
    private $_param;    // Extra parameters from endpoint.db
    private $_telnet;   // Telnet client to reboot Sangoma
    private $_telnet_username = 'admin';
    private $_telnet_password = 'admin';


    public function __construct($oLog, $sServerIP, $sMAC, $sIP,
        $sDeviceID, $sTech, $sDesc, $sAccount, $sSecret)
    {
        parent::__construct('Sangoma', $oLog, $sServerIP, $sMAC, $sIP, $sDeviceID,
            $sTech, $sDesc, $sAccount, $sSecret);
    }

    /**
     * Properties loaded from endpoint parameters:
     * 
     * arrData keys: 
     *  dns_address     If set, defines DNS for the device
     *  lan_type        One of 'dhcp' or 'static'
     *      lan_ip_address  IP address for LAN (static only)
     *      lan_ip_mask     IP mask for LAN (static only) 
     *  analog_extension_lines  Number of analog extension lines (0..N-1)
     *  first_extension         Initial extension in analog extension range
     *  increment               Increment between each extension in analog extension range
     *      user_nameN          Symbolic name for SIP mapping to analog extension N 
     *      userN               SIP account for mapping to analog extension N
     *      authentication_userN Password for SIP account N
     *      call_conferenceN    Enable call conference (on/off?)
     *      call_dndN
     *      caller_idN
     *      call_transferN
     *      call_waitingN
     *      enableN
     *  pbx_address     IP address of SIP server, should default to $this->_serverip
     *  sip_port        UDP port for SIP, should default to 5060
     *  registration    Username for SIP registration on SIP server
     *  registration_password Password for SIP registration on SIP server. Both
     *                  registration and registration_password must be nonempty
     *                  for pbx_address and sip_port to take effect.
     *  analog_trunk_lines      Number of analog trunk lines (0..N-1)
     *      lineN               PSTN number assigned to line N
     *      IDN                 Username for line N
     *      authentication_IDN  Password for line N
     *      enable_lineN
     *      num_listN
     * 
     */
    private function _getSangomaConfiguration($arrData)
    {
        $date =  date("j/m/Y H:i:s"); 
    
    
        $config .= <<<CONF
        ;writing file ...
    ;PUT completed
    ;
    ; Script generated using
    ; PUT MEM:0x95bf3878;length=0x00026666,buffer=0x95bf3878
    ;<all>
    ; CONFIGVERSION:this_hostname:$date 
    ;
        set .tftp.ip=$arrData[pbx_address]
        set .quick.voip.proxy.proxy_addr=$arrData[pbx_address]
        set .quick.voip.proxy.proxy_domain_name=$arrData[pbx_address]
        set .quick.voip.proxy.registrar_addr=$arrData[pbx_address]
        purge .pots.port

CONF;


        if ($arrData["dns_address"] != "") {
                $config .= <<<CONF
purge .dns
 cp .dns.1
  set .dns.1.ip=$arrData[dns_address]

CONF;
    }
else{
 $config .= <<<CONF
purge .dns
 cp .dns.1
  set .dns.1.ip=0.0.0.0

CONF;
        }


        if ($arrData["lan_type"] == "dhcp") {
            $config .= <<<CONF
        set .lan.bridge_mode="1"
        set .lan.file_transfer_method="TFTP"
        set .lan.lan_profile="1"
        set .lan.name="this_hostname"
        set .lan.gateway.dhcp_if="1"

CONF;
        } else {
            $config .= <<<CONF
        set .lan.bridge_mode="1"
        set .lan.file_transfer_method="TFTP"
        set .lan.lan_profile="1"
        set .lan.name="this_hostname"
        set .lan.gateway.dhcp_if="0"
        purge .lan.if
        cp .lan.if.1
        set .lan.if.1.ip=$arrData[lan_ip_address]
        set .lan.if.1.max_tx_rate="0"
        set .lan.if.1.protocol="ip"
        set .lan.if.1.subnet=$arrData[lan_ip_mask]
        set .lan.if.1.use_apipa="1"
        set .lan.if.1.use_dhcp="0"
            set .lan.if.1.dhcp.get_dns="0"
            set .lan.if.1.dhcp.get_gateway="0"
            set .lan.if.1.dhcp.get_ntp="0"
            set .lan.if.1.dhcp.get_tftp="0"
            set .lan.if.1.nat.enable="0"
            set .lan.if.1.nat.private_subnet_auto="1"
            set .lan.if.1.nat.private_subnet_list_index="1"
CONF;
        }

        $registration_ID = $arrData["registration"];
        $registration_password = $arrData["registration_password"];
        if (($registration_ID!="") && ($registration_password=!"")) {
            $config .= <<<CONF

  set .sip.local_rx_port=$arrData[sip_port]
  set .sip.reg_enable="1"
  purge .sip.profile
  purge .sip.reg.user
  purge .pots.port
  purge .quick.fxo
   purge .sip.auth.user
   cp .sip.auth.user.1
    set .sip.auth.user.1.enable="1"
    set .sip.auth.user.1.password=$registration_password
    set .sip.auth.user.1.resource_priority="routine"
    set .sip.auth.user.1.sip_profile="1"
    set .sip.auth.user.1.subscriber="IF:[^9]..."
    set .sip.auth.user.1.username=$registration_ID
   cp .sip.profile.1
   set .sip.profile.1.alt_domain="alt-reg-domain.com"
   set .sip.profile.1.from_header_host="reg_domain"
   set .sip.profile.1.from_header_userinfo="auth_username"
   set .sip.profile.1.interface=$registration_ID
   set .sip.profile.1.name=$registration_ID
   set .sip.profile.1.redirect_host="reg_domain"
   set .sip.profile.1.reg_domain=$arrData[pbx_address]
   set .sip.profile.1.reg_expiry="600"
   set .sip.profile.1.reg_req_uri_port=$arrData[sip_port]
   set .sip.profile.1.req_uri_port=$arrData[sip_port]
   set .sip.profile.1.sig_transport="udp"
   set .sip.profile.1.to_header_host="reg_domain"
    set .sip.profile.1.proxy.accessibility_check="off"
    set .sip.profile.1.proxy.accessibility_check_transport="udp"
    set .sip.profile.1.proxy.min_valid_response="180"
    set .sip.profile.1.proxy.mode="normal"
    set .sip.profile.1.proxy.retry_delay="0"
    set .sip.profile.1.proxy.timeout_ms="5000"
    purge .sip.profile.1.proxy
    cp .sip.profile.1.proxy.1
     set .sip.profile.1.proxy.1.enable="1"
     set .sip.profile.1.proxy.1.ipname=$arrData[pbx_address]
     set .sip.profile.1.proxy.1.port=$arrData[sip_port]
     set .sip.profile.1.proxy.1.tls_port="5061"
    set .sip.profile.1.registrar.accessibility_check="off"
    set .sip.profile.1.registrar.accessibility_check_transport="udp"
    set .sip.profile.1.registrar.max_registrars="3"
    set .sip.profile.1.registrar.min_valid_response="200"
    set .sip.profile.1.registrar.mode="normal"
    set .sip.profile.1.registrar.retry_delay="0"
    set .sip.profile.1.registrar.timeout_ms="5000"
    purge .sip.profile.1.registrar
    cp .sip.profile.1.registrar.1
     set .sip.profile.1.registrar.1.enable="1"
     set .sip.profile.1.registrar.1.ipname=$arrData[pbx_address]
     set .sip.profile.1.registrar.1.port=$arrData[sip_port]
     set .sip.profile.1.registrar.1.tls_port="5061"
   purge .sip.reg.user
   cp .sip.reg.user.1
    set .sip.reg.user.1.auth_user_index="1"
    set .sip.reg.user.1.dn=$registration_ID
    set .sip.reg.user.1.enable="1"
    set .sip.reg.user.1.sip_profile="1"
    set .sip.reg.user.1.username=$registration_ID



CONF;
        }

        $number=0;
        for ($i = 0; $i < $arrData["analog_extension_lines"]; $i++) {
            $extension = $arrData["first_extension"] + $i*$arrData["increment"];
            $username = $arrData["authentication_user$i"];
            $dn = $arrData["user$i"]; 
            $interface = $arrData["user_name$i"];
            $number = $i+1;
            $call_conference = $arrData["call_conference$i"]; 
            $call_transfer = $arrData["call_transfer$i"];
            $caller_id = $arrData["caller_id$i"];
            $call_waiting = $arrData["call_waiting$i"];
            $enable = $arrData["enable$i"];
            $call_dnd = $arrData["call_dnd$i"];
            $config .= <<<CONF

        cp .pots.port.$number
         set .pots.port.$number.call_conference=$call_conference
         set .pots.port.$number.call_fwd_enable=on
         set .pots.port.$number.call_transfer=$call_transfer 
         set .pots.port.$number.call_waiting=$call_waiting
         set .pots.port.$number.callerid=$caller_id
         set .pots.port.$number.dnd_enable=$call_dnd
         set .pots.port.$number.dnd_off_hook_deactivate="off"
         set .pots.port.$number.dnd_response="instant_reject"
         set .pots.port.$number.enable=$enable
         set .pots.port.$number.fx_profile="1"
         set .pots.port.$number.lyr1="g711Alaw64k"
         set .pots.port.$number.tx_gain="0"
              purge .pots.port.$number.if
               cp .pots.port.1.if.1
            set .pots.port.$number.if.1.dn=$dn
            set .pots.port.$number.if.1.interface=$interface
            set .pots.port.$number.if.1.profile="1"
            set .pots.port.$number.if.1.ring_index="1"
            set .pots.port.$number.if.1.username=$username

CONF;
            $user = $arrData["user$i"];
            $password = $arrData["authentication_user$i"];

            $config .= <<<CONF

CONF;
        }

        $number_prof = $number;

        for ($i = 0; $i < $arrData["analog_trunk_lines"]; $i++) {
            $number = $i+1;
            $number_prof++;
            $line = $arrData["line$i"];
            $dn = $arrData["ID$i"];
            $username = $arrData["authentication_ID$i"];
            $numlist = $arrData["num_list$i"];
            $enable_line = $arrData["enable_line$i"];
            $config .= <<<CONF
            
        cp .quick.fxo.$number_prof
        set .quick.fxo.$number_prof.handle_emergency_calls="0"
        set .quick.fxo.$number_prof.incoming_forward="default"
        set .quick.fxo.$number_prof.name=$username
        set .quick.fxo.$number_prof.numlist=$numlist
        set .quick.fxo.$number_prof.this_tel=$dn
        cp .pots.port.$number_prof
         set .pots.port.$number_prof.call_conference="off"
         set .pots.port.$number_prof.call_fwd_enable="on"
         set .pots.port.$number_prof.call_transfer="on"
         set .pots.port.$number_prof.call_waiting="off"
         set .pots.port.$number_prof.callerid="off"
         set .pots.port.$number_prof.dnd_enable="on"
         set .pots.port.$number_prof.dnd_off_hook_deactivate="off"
         set .pots.port.$number_prof.dnd_response="instant_reject"
         set .pots.port.$number_prof.enable=$enable_line
         set .pots.port.$number_prof.fx_profile="2"
         set .pots.port.$number_prof.lyr1="g711Alaw64k"
         set .pots.port.$number_prof.tx_gain="0"
        purge .pots.port.$number_prof.if
         cp .pots.port.$number_prof.if.1
             set .pots.port.$number_prof.if.1.dn=$dn
                 set .pots.port.$number_prof.if.1.interface=$line
             set .pots.port.$number_prof.if.1.profile="2"
             set .pots.port.$number_prof.if.1.ring_index="1"
             set .pots.port.$number_prof.if.1.username= $username

CONF;

        }

        $config.=<<<CONF

cp .
;
; PUT end
;
CONF;
        return $config;  
    }
}
?>