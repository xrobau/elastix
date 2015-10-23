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
import elastix.vendor.Escene
import eventlet
from eventlet.green import socket, urllib2, urllib, os
import errno
import re
import xml.dom.minidom
paramiko = eventlet.import_patched('paramiko')

class Endpoint(elastix.vendor.Escene.Endpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'RCA', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = False
        self._timeZone = 12

    def probeModel(self):
        ''' Probe specific model of RCA phone (which is not a rebranded Escene)
        
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
        except urllib2.HTTPError, e:
            if e.code == 401 and 'WWW-Authenticate' in e.headers:
                m = re.search(r'realm="RCA (\w+)"', e.headers['WWW-Authenticate'])
                if m != None:
                    sModel = m.group(1)
        except Exception, e:
            pass

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        if self._model in ('IP150'):
            return self._updateLocalConfig_IP150()
        elif self._model in ('IP160s'):
            return self._updateLocalConfig_IP160s()
        else:
            return self._updateLocalConfig_Escene(
                        self._endpointdir + '/tpl/RCA_template.xml',
                        self._endpointdir + '/tpl/RCA_Extern_template.xml')
        
    
    def _updateLocalConfig_IP150(self):
        '''Configuration for RCA IP150 endpoints (local)
        
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
            if stdvars['static_dns2'] <> None:
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
            
            # Since the web interface will NOT immediately apply the network
            # changes, we need to go raw and ssh into the phone. Additionally,
            # if we are changing the network setting from DHCP to static or 
            # viceversa, we expect the SSH connection to be disconnected in the
            # middle of the update. A timeout of 5 seconds should do it.
            if self._dhcp:
                command = '/root/dhcp-configure.sh'
            else:
                dns2 ='none'
                if self._static_dns2 != None:
                    dns2 = self._static_dns2
                command = '/root/staticip-configure.sh %s %s %s %s %s' %\
                    (self._static_ip, self._static_mask, self._static_gw, self._static_dns1, dns2)
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.WarningPolicy())
            ssh.connect(self._ip, username=self._ssh_username, password=self._ssh_password, timeout=5)
            stdin, stdout, stderr = ssh.exec_command(command)            
            logging.info('Endpoint %s@%s - about to set timeout of %d on stdout' % (self._vendorname, self._ip, oldtimeout,))            
            stdout.channel.settimeout(5)
            try:
                s = stdout.read()
                logging.info('Endpoint %s@%s - answer follows:\n%s' % (self._vendorname, self._ip, s,))
            except socket.error, e:
                pass
            ssh.close()
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

    def _updateLocalConfig_IP160s(self):
        # This phone configuration is verbose on the network. 
        
        # Not because it requires Digest authentication...
        # ...but because nowhere in sight is a way to send the entire 
        # configuration at once, such as a configuration file. Therefore
        # the following code needs to send each bit of configuration, on a
        # separate request:
        
        # Delete each of the existing account configurations, one by one:
        for idx in range(self._max_sip_accounts, -1, -1):
            if not self._doAuthGet('/cgi-bin/cgiwebgen.cgi?PageId=5&account_id=%d' % (idx,)):
                return False
        
        # Now, send the new account configurations again, one by one:
        stdvars = self._prepareVarList()
        postvars = [
            ('PageId', '55'),
            ('h_account_id', None),
            ('o_AccountActive', '1'),
            ('t_AccountName', None),
            ('t_SipServer', stdvars['server_ip']),
            ('t_SipServerPort', '5060'),
            ('t_UserName', None),
            ('t_RegisteredName', None),
            ('t_Password', None),
            ('o_EnableOutProxyServer', '0'),
            ('t_OutProxyServer', ''),
            ('t_OutProxyServerPort', '5060'),
            ('t_NetVoiceMailAccessNum', ''),
            ('o_Codec0', '9'),
            ('o_Codec1', '0'),
            ('o_Codec2', '8'),
            ('o_Codec3', '18'),
            ('o_Codec4', '2'),
            ('o_Codec5', '98'),
            ('o_Codec6', '99'),
            ('o_Codec7', '-1'),
            ('o_Transport', '0'),
            ('t_UdpPort', '5060'),
            ('t_TcpPort', '5060'),
            ('t_TlsPort', '5060'),
            ('o_DtmfMode', '2'),
            ('o_FwdAlways', '0'),
            ('t_FwdAlwaysTarget', ''),
            ('t_FwdAlwaysOn', ''),
            ('t_FwdAlwaysOff', ''),
            ('o_FwdNoAns', '0'),
            ('t_FwdNoAnsAfterRingTime', '5'),
            ('t_FwdNoAnsTarget', ''),
            ('t_FwdNoAnsOn', ''),
            ('t_FwdNoAnsOff', ''),
            ('o_FwdBusy', '0'),
            ('t_FwdBusyTarget', ''),
            ('t_FwdBusyOn', ''),
            ('t_FwdBusyOff', ''),
            ('o_EnableDnd', '0'),
            ('t_DndOn', ''),
            ('t_DndOff', ''),
            ('o_ConferenceType', '0'),
            ('t_ConferenceURI', ''),
            ('o_EnableStun', '0'),
            ('t_StunAddr', ''),
            ('t_StunPort', '3478'),
            ('o_VoiceEncrypt', '0'),
            ('o_Unreg_Single_Contact', '0'),
            ('t_LoginExpireSeconds', '3600'),
            ('o_SubscribeForMwi', '1'),
            ('t_MWI_Subscribe_Expiry', '3600'),
            ('o_UseNaptr', '0'),
            ('o_UsePrack', '0'),
            ('o_Update', '0'),
            ('o_UseSessionTimer', '0'),
            ('o_broadsoft_profile', '0'),
            ('o_Callee_ID', '1'),
            ('t_SiteIp', '0.0.0.0'),
            ('o_udpKeepAliveEnable', '1'),
            ('t_udpKeepAliveTimeout', '30'),
            ('o_featureKeySyncEnable', '0'),
        ]
        for idx in range(len(stdvars['sip'])):
            self._updateList(postvars, {
                'h_account_id': idx,
                't_AccountName': stdvars['sip'][idx].description,
                't_UserName': stdvars['sip'][idx].account,
                't_RegisteredName': stdvars['sip'][idx].account,
                't_Password': stdvars['sip'][idx].secret,
            })
            if not self._doAuthPostIP160s('/cgi-bin/cgiwebgen.cgi', postvars):
                return False
        
        # Send the network configuration LAST
        if self._dhcp:
            postvars = [
                ('PageId', '54'),
                ('r_dhcp', 'wantype_dhcp'),
            ]
        else:
            postvars = [
                ('PageId', '54'),
                ('r_static_ip', 'wantype_static_ip'),
                ('t_wan_ip', stdvars['static_ip']),
                ('t_subnet_mask', stdvars['static_mask']),
                ('t_gateway', stdvars['static_gateway']),
                ('t_pri_dns', stdvars['static_dns1']),
                ('t_sec_dns', ''),
            ]
            if stdvars['static_dns2'] != None:
                self._updateList(postvars, { 't_sec_dns': stdvars['static_dns2'] })
        if not self._doAuthPostIP160s('/cgi-bin/cgiwebgen.cgi', postvars):
            return False
        
        self._unregister()
        self._setConfigured()
        return True

    def _updateList(self, postvars, updatevars):
        for i in range(len(postvars)):
            if postvars[i][0] in updatevars:
                postvars[i] = (postvars[i][0], updatevars[postvars[i][0]])
    
    def _doAuthPostIP160s(self, urlpath, postvars):
        '''Send a POST request to the IP160s phone, in the special way required.
        
        The IP160s firmware has a shoddy implementation for processing of POST
        requests. When processing a POST request, the firmware expects each of
        the form variables to appear in a specific position in the encoding
        string sent by the browser, rather than relying on the key/value 
        structure that is implied by the encoding protocol. This firmware happens
        to work with ordinary web browsers because they, by lucky chance, send
        the variables in form order. However, if urllib is used to encode an
        ordinary dictionary of variables, there is no ordering guarantee (in 
        Python 2.4) and the variables get sent out of order. The firwmare will
        then set the variables according to the received position, which results
        in a broken setup.
        
        An OrderedDict would be ideal for this, but for now I will manually build
        an array of 2-tuples and use that to encode the POST data.
        '''
        postdata = []
        for tuple in postvars:
            postdata.append(urllib.quote_plus(tuple[0]) + '=' + urllib.quote_plus(str(tuple[1])))
        return self._doAuthPost(urlpath, '&'.join(postdata))
