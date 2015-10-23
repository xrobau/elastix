# vim: set fileencoding=utf-8 :
# vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
# Codificación: UTF-8
# +----------------------------------------------------------------------+
# | Elastix version 2.0.4                                                |
# | http://www.elastix.com                                               |
# +----------------------------------------------------------------------+
# | Copyright (c) 2006 Palosanto Solutions S. A.                         |
# +----------------------------------------------------------------------+
# | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
# | Telfs. 2283-268, 2294-440, 2284-356                                  |
# | Guayaquil - Ecuador                                                  |
# | http://www.palosanto.com                                             |
# +----------------------------------------------------------------------+
# | The contents of this file are subject to the General Public License  |
# | (GPL) Version 2 (the "License"); you may not use this file except in |
# | compliance with the License. You may obtain a copy of the License at |
# | http://www.opensource.org/licenses/gpl-license.php                   |
# |                                                                      |
# | Software distributed under the License is distributed on an "AS IS"  |
# | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
# | the License for the specific language governing rights and           |
# | limitations under the License.                                       |
# +----------------------------------------------------------------------+
# | The Original Code is: Elastix Open Source.                           |
# | The Initial Developer of the Original Code is PaloSanto Solutions    |
# +----------------------------------------------------------------------+
# $Id: dialerd,v 1.2 2008/09/08 18:29:36 alex Exp $
import logging
import re
import eventlet
from eventlet.green import socket
from elastix.BaseEndpoint import BaseEndpoint
telnetlib = eventlet.import_patched('telnetlib')

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'LG-ERICSSON', amipool, dbpool, sServerIP, sIP, mac)
        self._timeZone = None

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'timezone' in param: self._timeZone = param['timezone']
        return True

    def probeModel(self):
        '''Probe specific model of the LG-Ericsson phone
        
        To probe for the specific model, a telnet session is tried. The 
        login banner exposes the phone model.
        '''
        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip, 6000)
            telnet.get_socket().settimeout(5)
        except socket.timeout, e:
            logging.error('Endpoint %s@%s failed to telnet - timeout (%s)' %
                (self._vendorname, self._ip, str(e)))
            return
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to telnet - %s' %
                (self._vendorname, self._ip, str(e)))
            return

        sModel = None

        try:
            idx, m, text = telnet.expect([r'Login:'], 10)
            telnet.close()
            
            m = re.search(r'Welcome to LG-Ericsson (\w+)', text)
            if m != None:
                sModel = m.group(1)
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        
        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for LG-ERICSSON endpoints (local):
        
        The file XXXXXXXXXXXX (with no extension) contains the SIP configuration.
        Here XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
        
        To reboot the phone, it is necessary to issue the AMI command:
        sip notify reboot-yealink {$EXTENSION}. Verified with IP8802A.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').lower())
        sConfigPath = self._tftpdir + '/' + sConfigFile
        
        vars = self._prepareVarList()
        vars['time_zone'] = self._timeZone
        try:
            self._writeTemplate('LG-ERICSSON_local_IP8802A.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # TODO: point TFTP provisioning statically at us

        # Check if there is at least one registered extension. This is required
        # for sip notify to work
        #if self._hasRegisteredExtension():
        #    self._amireboot('reboot-yealink')
        #elif not self._rebootbytelnet():
        #    return False
        if not self._rebootbytelnet():
            return False            
        
        self._unregister()
        self._setConfigured()
        return True

    def _rebootbytelnet(self):
        '''Start reboot of LG-Ericsson phone by telnet'''
        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip, 6000)
            telnet.get_socket().settimeout(10)
        except socket.timeout, e:
            logging.error('Endpoint %s@%s failed to telnet - timeout (%s)' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to telnet - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Attempt to login into admin telnet
        try:
            telnet.read_until('Login:', 10)
            if self._telnet_username != None: telnet.write(self._telnet_username.encode() + '\r\n')
            telnet.read_until('Password:', 10)
            if self._telnet_password != None: telnet.write(self._telnet_password.encode() + '\r\n')

            # Wait for either prompt or login prompt
            idx, m, text = telnet.expect([r'Login:', r':>\s'], 10)
            if idx == 0:
                telnet.close()
                logging.error('Endpoint %s@%s detected ACCESS DENIED on telnet connect')
                return False
            else:
                if self._dhcp:
                    telnet.write('Config/Lan/Change nmod dhcp\r\n')
                else:
                    telnet.write('Config/Lan/Change nmod static\r\n')
                    telnet.write('Config/Lan/Change ip ' + self._static_ip.encode() + '\r\n')
                    telnet.write('Config/Lan/Change nm ' + self._static_mask.encode() + '\r\n')
                    telnet.write('Config/Lan/Change gw ' + self._static_gw.encode() + '\r\n')
                    telnet.write('Config/Lan/Change dns1 ' + self._static_dns1.encode() + '\r\n')
                    telnet.write('Config/Lan/Change dns2 ' + self._static_dns2.encode() + '\r\n')
                telnet.write('Config/Update/Change tftp ' + self._serverip.encode() + '\r\n')
                telnet.write('System/Reboot\r\ny\r\n')
                idx, m, text = telnet.expect([r'Reboot msg'], 10)
                telnet.close()
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        return True        
