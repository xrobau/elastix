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
import glob
import os.path
import re
import elastix.BaseEndpoint
from elastix.BaseEndpoint import BaseEndpoint
import eventlet
telnetlib = eventlet.import_patched('telnetlib')


class Endpoint(BaseEndpoint):
    _global_serverip = None

    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Cisco', amipool, dbpool, sServerIP, sIP, mac)
        if Endpoint._global_serverip == None:
            Endpoint._global_serverip = sServerIP
        elif Endpoint._global_serverip != sServerIP:
            logging.warning('global server IP is %s but endpoint %s requires ' +
                'server IP %s - this endpoint might not work correctly.' %
                (Endpoint._global_serverip, sIP, sServerIP))

    # TODO: might be possible to derive model from MAC range, requires database change

    def probeModel(self):
        ''' Probe specific model of the Cisco phone

        This probe only works if the phone has access to a configuration that
        enables telnet.
        '''
        self._loadCustomCredentials()
        if self._telnet_password == None: self._telnet_password = 'cisco'

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
            telnet.read_until('Password :', 10)
            telnet.write(self._telnet_password + '\r\n') # Password

            idx, m, text = telnet.expect([r'Password :', r'> '], 10)
            if idx == 0:
                # Login failed
                telnet.close()
                return
            telnet.write('show config\r\n')
            text = telnet.read_until('> ', 10)
            telnet.write('exit\r\n')
            telnet.close()

            m = re.search(r'IP Phone CP-(\w+)', text)
            if m != None: sModel = m.group(1)
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        if sModel != None: self._saveModel(sModel)


    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Configuration for Cisco endpoints (global)

        SIP global definition goes in /tftpboot/SIPDefault.cnf and has a
        reference to a firmware file P0S*.sb2. If there are several files, the
        higher version is selected.
        '''
        sFirmwareVersion = None
        for sPathName in glob.glob(elastix.BaseEndpoint.TFTP_DIR + '/P0S*.sb2'):
            sVersion, ext = os.path.splitext(os.path.basename(sPathName))
            if sFirmwareVersion == None or sFirmwareVersion < sVersion:
                sFirmwareVersion = sVersion
        if sFirmwareVersion == None:
            logging.error('Failed to find firmware file P0S*.sb2 in ' + elastix.BaseEndpoint.TFTP_DIR)
            return False

        vars = {
            'firmware_version'  : sFirmwareVersion,
            'phonesrv'          : BaseEndpoint._buildPhoneProv(Endpoint._global_serverip, 'Cisco', 'GLOBAL'),
        }
        try:
            sConfigFile = 'SIPDefault.cnf'
            sConfigPath = elastix.BaseEndpoint.TFTP_DIR + '/' + sConfigFile
            BaseEndpoint._writeTemplate('Cisco_global_SIPDefault.tpl', vars, sConfigPath)
            return True
        except IOError, e:
            logging.error('Failed to write global config for Cisco - %s' % (str(e),))
            return False

    def updateLocalConfig(self):
        '''Configuration for Cisco endpoints (local)

        The file SIPXXXXXXXXXXXX.cnf contains the SIP configuration. Here
        XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify cisco-check-cfg {$EXTENSION}. Verified with Cisco 7960.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate UPPERCASE version of MAC address without colons
        sConfigFile = 'SIP' + (self._mac.replace(':', '').upper()) + '.cnf'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        try:
            self._writeTemplate('Cisco_local_SIP.tpl', vars, sConfigPath)

            # Must execute cisco-check-cfg with extension, not IP
            if self._hasRegisteredExtension():
                self._amireboot('cisco-check-cfg')
            elif self._telnet_password != None and not self._rebootbytelnet():
                return False

            self._unregister()
            self._setConfigured()
            return True
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

    def _rebootbytelnet(self):
        '''Start reboot of Cisco phone by telnet'''
        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip)
            telnet.get_socket().settimeout(10)
        except socket.timeout, e:
            logging.error('Endpoint %s@%s failed to telnet - timeout (%s)' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to telnet - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        try:
            # Attempt login with default credentials
            telnet.read_until('Password :', 10)
            if self._telnet_password != None: telnet.write(self._telnet_password.encode() + '\r\n')

            idx, m, text = telnet.expect([r'Password :', r'> '], 10)
            if idx == 0:
                # Login failed
                telnet.close()
                logging.error('Endpoint %s@%s detected ACCESS DENIED on telnet connect' %
                              (self._vendorname, self._ip))
                return False
            telnet.write('reset\r\n')
            telnet.close()

            return True
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
