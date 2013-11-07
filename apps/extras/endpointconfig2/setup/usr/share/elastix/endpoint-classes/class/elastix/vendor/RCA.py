# vim: set fileencoding=utf-8 :
# vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
# Codificación: UTF-8
# +----------------------------------------------------------------------+
# | Elastix version 2.0.4                                                |
# | http://www.elastix.org                                               |
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
from eventlet.green import socket, urllib2, urllib, os
import errno
import re
import xml.dom.minidom

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'RCA', amipool, dbpool, sServerIP, sIP, mac)

    def probeModel(self):
        ''' Probe specific model of RCA phone
        
        An HTTP request is issued to the phone. The welcome screen exposes the
        phone model. 
        '''
        sModel = None
        try:
            response = urllib2.urlopen('http://' + self._ip + '/console/start')
            htmlbody = response.read()
            if response.code == 200:
                # RCA IP150 (Glass.GB.2.0/4085) Settings
                m = re.search(r'RCA (\w+)', htmlbody)
                if m != None: sModel = m.group(1)
        except Exception, e:
            pass

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for RCA endpoints (local)
        
        The file voip-XXXXXXXXXXXX.xml contains the plaintext SIP configuration. 
        Here XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone. 
        After writing the XML file, it is necessary to configure the static
        provisioning via a single authenticated post, then simulate a 
        configuration restore through another post. This applies the changes
        immediately.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = 'voip-' + (self._mac.replace(':', '').lower()) + '.xml'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        
        # Save the XML content for POST, write to TFTP directory
        xmlcontent = self._buildXMLConfig()
        try:
            self._writeContent(sConfigPath, xmlcontent)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        
        # Send XML configuration to phone by HTTP
        if not self._sendPhoneConfiguration(xmlcontent):
            return False
        
        self._unregister()
        self._setConfigured()
        return True

    def _buildXMLConfig(self):
        stdvars = self._prepareVarList()
        
        # Build the DOM from scratch.
        dom = xml.dom.minidom.parseString('<configuration/>')
        xml_system = dom.createElement('system')
        
        # The following values are left empty
        for s in ('address1', 'address2', 'address3', 'city', 'state', 'zip',\
                  'country', 'company', 'appStoreUserName'):
            self._addSetting(dom, xml_system, s, '')
        
        # Set endpoint-wide settings
        self._addSetting(dom, xml_system, 'dhcp_option_protocol', 'TFTP')
        if self._dhcp:
            self._addSetting(dom, xml_system, 'dhcp', 'T')
        else:
            self._addSetting(dom, xml_system, 'dhcp', 'F')
            self._addSetting(dom, xml_system, 'static', stdvars['static_ip'])
            self._addSetting(dom, xml_system, 'netMask', stdvars['static_mask'])
            self._addSetting(dom, xml_system, 'gateway', stdvars['static_gateway'])
            self._addSetting(dom, xml_system, 'dns1', stdvars['static_dns1'])
            self._addSetting(dom, xml_system, 'dns2', stdvars['static_dns2'])
        self._addSetting(dom, xml_system, 'displayName', stdvars['sip'][0].description)
        self._addSetting(dom, xml_system, 'displayNumber', stdvars['sip'][0].extension)
        self._addSetting(dom, xml_system, 'timeZone', 'America/Bogota')
        dom.documentElement.appendChild(xml_system)

        
        # Set endpoint settings
        xml_voip = dom.createElement('voip')
        for account in stdvars['sip']:
            xml_line = dom.createElement('line')
            self._addSetting(dom, xml_line, 'description', account.description)
            self._addSetting(dom, xml_line, 'username', account.account)
            self._addSetting(dom, xml_line, 'password', account.secret)
            self._addSetting(dom, xml_line, 'server', stdvars['server_ip'])
            for s in ('authUsername', 'domain', 'voiceMail'):
                self._addSetting(dom, xml_line, s, '')
            self._addSetting(dom, xml_line, 'outboundTransferAllowed', 'T')
            xml_voip.appendChild(xml_line)        
        dom.documentElement.appendChild(xml_voip)

        xmlcontent = dom.toxml('UTF-8')
        dom.unlink()
        dom = None
        return xmlcontent

    def _addSetting(self, dom, xml_container, name, value):
        xml_setting = dom.createElement('setting')
        xml_setting.setAttribute('name', name)
        xml_setting.setAttribute('value', value)
        xml_container.appendChild(xml_setting)

    def _sendPhoneConfiguration(self, xmlcontent):
        try:
            # Login into interface
            opener = urllib2.build_opener(urllib2.HTTPCookieProcessor())
            response = opener.open('http://' + self._ip + '/console/j_security_check',
                urllib.urlencode({
                    'submit' : 'Login',
                    'j_username' : self._http_username,
                    'j_password' : self._http_password
                }))
            body = response.read()
            if not '/console/start' in body:
                logging.error('Endpoint %s@%s - j_security_check failed login' %
                    (self._vendorname, self._ip))
                return False
            
            # Build a custom request with form data
            boundary = '------------------ENDPOINTCONFIG'
            postdata = '--' + boundary + '\r\n' +\
                'Content-Disposition: form-data; name="COMMAND"\r\n' +\
                '\r\n' +\
                'RX' + '\r\n' +\
                '--' + boundary + '\r\n' +\
                'Content-Disposition: form-data; name="RX"; filename="config.xml"\r\n' +\
                'Content-Type: text/xml\r\n' +\
                '\r\n' +\
                xmlcontent + '\r\n' +\
                '--' + boundary + '--\r\n'
            filerequest = urllib2.Request(
                'http://' + self._ip + '/console/configuration',
                postdata, {'Content-Type': 'multipart/form-data; boundary=' + boundary})
            # The phone configuration restore is known to hang for 25-30 seconds 
            oldtimeout = socket.getdefaulttimeout()
            socket.setdefaulttimeout(40)            
            try:
                response = opener.open(filerequest)
            finally:
                socket.setdefaulttimeout(oldtimeout)
            body = response.read()
                
            if not 'Configuration restore complete' in body:
                logging.error('Endpoint %s@%s - configuration post failed' %
                    (self._vendorname, self._ip))
                return False
            
            # Attempt to set just the provisioning server
            response = opener.open('http://' + self._ip + '/console/general',
                urllib.urlencode({
                    'COMMAND' : 'AP',
                    '@p.provisioningServer' : self._serverip,
                    '@dhcp_option_protocol' : 'TFTP'
                }))
            body = response.read()
            #if not '/console/start' in body:
            #    logging.error('Endpoint %s@%s - j_security_check failed login' %
            #        (self._vendorname, self._ip))
            #    return False            
            return True
        except paramiko.AuthenticationException, e:
            logging.error('Endpoint %s@%s failed to authenticate ssh - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        
