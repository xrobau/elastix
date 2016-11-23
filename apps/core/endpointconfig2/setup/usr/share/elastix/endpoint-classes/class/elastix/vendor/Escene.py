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
from elastix.BaseEndpoint import BaseEndpoint
import eventlet
from eventlet.green import socket, os, urllib2
import errno
import re
from xml.dom.minidom import parse
telnetlib = eventlet.import_patched('telnetlib')

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Escene', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = False
        self._timeZone = 12

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'bridge' in param: self._bridge = param['bridge']
        if 'timezone' in param: self._timeZone = param['timezone']
        return True

    def probeModel(self):
        '''Probe specific model of Escene phone

        The phone is a small Linux system. The telnet login, if successful,
        drops into a busybox shell prompt. The cat command is available, and the
        specific phone model is at /mnt/system/PhoneType
        '''
        self._loadCustomCredentials()
        if self._telnet_username == None: self._telnet_username = 'root'
        if self._telnet_password == None: self._telnet_password = 'root'
        if self._http_username == None: self._http_username = 'root'
        if self._http_password == None: self._http_password = 'root'

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

            idx, m, text = telnet.expect([r'login: ', r'\# '], 10)
            if idx == 0:
                # Login failed
                telnet.close()
                return
            telnet.write('cat /mnt/system/PhoneType\r\n')
            text = telnet.read_until('# ', 10)
            telnet.write('exit\r\n')
            telnet.close()

            m = re.search(r'type=(\w+)', text)
            if m != None: sModel = m.group(1)
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # If the model was not identified, this might be a RCA phone with the
        # Escene MAC prefix
        if sModel == None:
            password_manager = urllib2.HTTPPasswordMgrWithDefaultRealm()
            password_manager.add_password(None, 'http://' + self._ip + '/',
                self._http_username, self._http_password)
            basic_auth_handler = urllib2.HTTPBasicAuthHandler(password_manager)
            opener = urllib2.build_opener(basic_auth_handler)
            try:
                response = opener.open('http://' + self._ip + '/overview.asp')
                htmlbody = response.read()
                #<tr><td align="center" valign="middle" height="80"><p class="BB"><strong><span class="style16"><font face="Arial">
                #
                #IP115
                #
                # </font></span></strong><br>
                m = re.search(r'<font face="\w+">\s+(\w+)\s+</font>', htmlbody, re.IGNORECASE | re.DOTALL)
                if m != None:
                    self._saveVendor('RCA')
                    sModel = m.group(1)
            except Exception, e:
                pass

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        return self._updateLocalConfig_Escene(
                self._endpointdir + '/tpl/Escene_template.xml',
                self._endpointdir + '/tpl/Escene_Extern_template.xml')

    def _updateLocalConfig_Escene(self, templatepath, extrtemplatepath):
        '''Configuration for Escene endpoints (local)

        The file XXXXXXXXXXXX.xml contains the plaintext SIP configuration. Here
        XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
        After writing the XML file, a telnet session is opened to make the phone
        retrieve the configuration file via TFTP and store it as its new
        configuration file.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify reboot-yealink {$IP}. Verified with Escene ES620.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        # Generate main configuration file
        sConfigFile = (self._mac.replace(':', '').lower()) + '.xml'
        if not self._replaceXMLConfigVariables(templatepath, sConfigFile):
            return False
        sExternFile = (self._mac.replace(':', '').lower()) + '_Extern.xml'
        if not self._replaceXMLExternVariables(extrtemplatepath, sExternFile):
            return False

        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip)
            telnet.get_socket().settimeout(5)
        except socket.timeout, e:
            logging.error('Endpoint %s@%s failed to telnet - timeout (%s)' %
                (self._vendorname, self._ip, str(e)))
            return
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to telnet - %s' %
                (self._vendorname, self._ip, str(e)))
            return

        # Attempt to login into admin telnet
        try:
            telnet.read_until('login:', 10)
            if self._telnet_username != None: telnet.write(self._telnet_username.encode() + '\r\n')
            telnet.read_until('Password:', 10)
            if self._telnet_password != None: telnet.write(self._telnet_password.encode() + '\r\n')

            # Wait for either prompt or login prompt
            idx, m, text = telnet.expect([r'login:', r'# '], 10)
            if idx == 0:
                telnet.close()
                logging.error('Endpoint %s@%s detected ACCESS DENIED on telnet connect')
                return False
            else:
                # Write all of the commands
                telnetQueue = [
                    'cd /mnt/sip',
                    'tftp -g ' + self._serverip + ' -r ' + sConfigFile,
                    'mv ' + sConfigFile + ' ESConfig.xml',
                    'tftp -g ' + self._serverip + ' -r ' + sExternFile,
                    'mv ' + sExternFile + ' Extern.xml',
                    'reboot'
                ]
                for cmd in telnetQueue:
                    telnet.write(cmd.encode() + '\r\n')
                    idx, m, text = telnet.expect([r'# '], 10)
                telnet.close()
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Reboot the phone.
        self._amireboot('reboot-yealink')
        self._unregister()
        self._setConfigured()
        return True

    def _replaceXMLConfigVariables(self, templatepath, sConfigFile):
        sConfigPath = self._tftpdir + '/' + sConfigFile

        # Load DOM and substitute the relevant variables
        dom = parse(templatepath)
        for network in dom.getElementsByTagName('network'):
            network.setAttribute('NetConfigType', '2')
            network.setAttribute('IPAddress', '')
            network.setAttribute('SubnetMask', '')
            network.setAttribute('DefaultGateways', '')
            network.setAttribute('IPDNS', '')
            network.setAttribute('SecondDNS', '')
            if not self._dhcp:
                network.setAttribute('NetConfigType', '0')
                if self._static_ip != None: network.setAttribute('IPAddress', self._static_ip)
                if self._static_mask != None: network.setAttribute('SubnetMask', self._static_mask)
                if self._static_gw != None: network.setAttribute('DefaultGateways', self._static_gw)
                if self._static_dns1 != None: network.setAttribute('IPDNS', self._static_dns1)
                if self._static_dns2 != None: network.setAttribute('SecondDNS', self._static_dns2)
        for systime in dom.getElementsByTagName('systime'):
            systime.setAttribute('TimeZoneType', str(self._timeZone))
        for nat in dom.getElementsByTagName('nat'):
            nat.setAttribute('PCPortMode', str(1 - int(self._bridge)))
        for privision in dom.getElementsByTagName('privision'): # (sic)
            privision.setAttribute('Firmware', 'TFTP://' + self._serverip)
        # This assumes all of the accounts to configure are SIP
        for sipUser in dom.getElementsByTagName('sipUser'):
            idx = int(sipUser.getAttribute('id'))
            sipUser.setAttribute('EnableAccount', '0')
            sipUser.setAttribute('Describe', '')
            sipUser.setAttribute('DomainName', self._serverip)
            sipUser.setAttribute('Password', '')
            sipUser.setAttribute('UserName', '')
            sipUser.setAttribute('UserNumber', '')
            sipUser.setAttribute('approveName', '')
            if idx < len(self._accounts):
                extension = self._accounts[idx]
                sipUser.setAttribute('EnableAccount', '1')
                sipUser.setAttribute('Describe', extension.description)
                sipUser.setAttribute('Password', extension.secret)
                sipUser.setAttribute('UserName', extension.account)
                sipUser.setAttribute('UserNumber', extension.extension)
                sipUser.setAttribute('approveName', extension.account)

        try:
            self._writeContent(sConfigPath, dom.toxml())
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        dom.unlink()
        dom = None

        return True

    def _replaceXMLExternVariables(self, templatepath, sConfigFile):
        sConfigPath = self._tftpdir + '/' + sConfigFile

        # Load DOM and substitute the relevant variables
        dom = parse(templatepath)
        for programbutton in dom.getElementsByTagName('Programbutton'):
            idx = int(programbutton.getAttribute('id'))
            programbutton.setAttribute('Num', '')
            programbutton.setAttribute('Name', '')
            programbutton.setAttribute('SipAccounts', '0')
            programbutton.setAttribute('Type', '0')
            if idx < len(self._accounts):
                programbutton.setAttribute('SipAccounts', str(idx))

        try:
            self._writeContent(sConfigPath, dom.toxml())
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        dom.unlink()
        dom = None

        return True