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
from elastix.BaseEndpoint import BaseEndpoint
import eventlet
from eventlet.green import socket
telnetlib = eventlet.import_patched('telnetlib')

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'AudioCodes', amipool, dbpool, sServerIP, sIP, mac)

        # Calculate timezone, GMT offset in minutes
        tzoffset = BaseEndpoint.getTimezoneOffset() / 60
        self._timeZone = ''
        if tzoffset < 0: self._timeZone = '-'
        self._timeZone = self._timeZone + ('%02d:%02d' % (abs(tzoffset) / 60, abs(tzoffset) % 60))
        self._language = 'SPANISH'

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'timezone' in param: self._timeZone = param['timezone']
        if 'language' in param: self._language = param['language']
        return True

    def probeModel(self):
        '''Probe specific model of the AudioCodes phone

        The phone is a small Linux system. The telnet login, if successful,
        drops into a busybox shell prompt. The grep command is available, and
        the phone configuration file is at /phone/etc/main.cfg
        '''
        self._loadCustomCredentials()
        if self._telnet_username == None: self._telnet_username = 'admin'
        if self._telnet_password == None: self._telnet_password = '1234'

        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip)
            telnet.get_socket().settimeout(10)
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
            # Attempt login with default credentials
            telnet.read_until('login: ', 10)
            telnet.write(self._telnet_username + '\r\n') # Username
            telnet.read_until('Password: ', 10)
            telnet.write(self._telnet_password + '\r\n') # Password

            idx, m, text = telnet.expect([r'login: ', r'\$ '], 10)
            if idx == 0:
                # Login failed
                telnet.close()
                return
            telnet.write('grep system/type /phone/etc/main.cfg\r\n')
            text = telnet.read_until('$ ', 10)
            telnet.write('exit\r\n')
            telnet.close()

            m = re.search(r'system/type=(\S+)', text)
            if m != None: sModel = m.group(1)
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for AudioCodes endpoints (local):

        The file XXXXXXXXXXXX.cfg contains the SIP configuration. Here
        XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify polycom-check-cfg {$IP}. Yes, this can also reboot an
        AudioCodes phone. Tested with a 310HD.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').lower()) + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        vars.update({
            'config_filename': sConfigFile,
            'timezone': self._timeZone,
            'language': self._language
        })
        try:
            self._writeTemplate('AudioCodes_local_cfg.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # The AudioCodes firmware is stateful, in an annoying way. Just submitting
        # a POST to the autoprovisioning URL from the factory-default setting will
        # only get a 301 Found response, and the settings will NOT be applied.
        # To actually apply the settings, it is required to perform a dummy GET
        # to /mainform.cgi/info.htm, discard anything returned, and only then
        # perform the POST.
        if not self._doAuthGet('/mainform.cgi/info.htm'):
            return False

        # Attempt to force provisioning to STATIC into this server. This allows
        # the system to point the phone at us even if we are not a master DHCP.
        # Otherwise the DHCP server might need to publish option 160.
        postvars = {
            'ASU_METHOD'    :   2,  # 0 is disabled, 1 is DHCP, 2 is static
            'FW_URL'        :   'tftp://' + self._serverip + '/' + sConfigFile,
            'NOW_FW'        :   0,
            'CFG_URL'       :   'tftp://' + self._serverip + '/' + sConfigFile,
            'NOW_CFG'       :   0,
            'FW_DYN'        :   0,
            'CFG_DYN'       :   0,
            'OPTVAL'        :   160,    # dhcp option for dynamic case
            'ASU_PERIOD'    :   3,      # power-up only
            'ASU_HOURS'     :   24,
            'ASU_EDTIME'    :   0,
            'ASU_EDWDAY'    :   0,
            'ASU_EDWTIME'   :   0,
            'ASU_RANDOM'    :   120,
        }
        if not self._doAuthPost('/mainform.cgi/Auto_Provision.htm', postvars):
            return False

        # Reboot the phone.
        self._amireboot('polycom-check-cfg')
        self._unregister()
        self._setConfigured()
        return True
