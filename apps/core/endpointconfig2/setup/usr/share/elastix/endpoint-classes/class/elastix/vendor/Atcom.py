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
import md5
import re
from elastix.BaseEndpoint import BaseEndpoint
import eventlet
from eventlet.green import socket, httplib, urllib
telnetlib = eventlet.import_patched('telnetlib')

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Atcom', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = False
        self._timeZone = 12
        self._firmware = 2  # Assume new firmware

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'bridge' in param: self._bridge = param['bridge']
        if 'timezone' in param: self._timeZone = param['timezone']
        return True

    def probeModel(self):
        '''Probe specific model of the Atcom phone

        To probe for the specific model, a telnet session is started. If the
        first thing the phone asks is 'Password:', it is an AT320. Otherwise,
        we log in with the default credentials, and check the prompt. The default
        prompt contains the phone model.
        '''
        self._loadCustomCredentials()
        if self._telnet_username == None: self._telnet_username = 'admin'
        if self._telnet_password == None: self._telnet_password = 'admin'

        telnet = None
        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip)
            telnet.get_socket().settimeout(5)
        except socket.timeout, e:
            logging.error('Endpoint %s@%s failed to telnet - timeout (%s)' %
                (self._vendorname, self._ip, str(e)))
            return
        except socket.error, e:
            logging.warning('Endpoint %s@%s failed to telnet - %s' %
                (self._vendorname, self._ip, str(e)))
            telnet = None

        sModel = None
        if telnet != None:
            try:
                idx, m, text = telnet.expect([r'Password:', r'Login:'], 10)
                if idx == 0:
                    # This is an AT320 - no need to go further
                    sModel = 'AT320'
                    telnet.close()
                else:
                    # Attempt login with default credentials
                    telnet.write(self._telnet_username + '\r\n') # Username
                    telnet.write(self._telnet_password + '\r\n') # Password
                    idx, m, text = telnet.expect([r'Login:', r'# '], 10)
                    telnet.close()
                    if idx == 0:
                        # Failed to login - credentials changed from default
                        return False
                    else:
                        m = re.search(r'(AT\d+)# ', text)
                        if m == None:
                            # Failed to identify default prompt
                            return False
                        elif not m.group(1) in ('AT530', 'AT610', 'AT620', 'AT640'):
                            # Unsupported phone model
                            return False
                        else:
                            sModel = m.group(1)
            except socket.error, e:
                logging.error('Endpoint %s@%s connection failure - %s' %
                    (self._vendorname, self._ip, str(e)))
                return False
        else:
            # If telnet failed, this might be an AT800 phone
            try:
                http = httplib.HTTPConnection(self._ip)
                http.request('GET', '/index.asp')
                resp = http.getresponse()
                htmlbody = resp.read()
                http.close()
                m = re.search(r'Product Name : .+?>(\w+)<', htmlbody)
                if m != None: sModel = m.group(1)
            except socket.error, e:
                logging.error('Endpoint %s@%s connection failure - %s' %
                    (self._vendorname, self._ip, str(e)))
                return False

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for ATCOM endpoints

        For model AT320, it is necessary to connect to the phone via telnet, and
        issue a series of commands to set the registration address.

        For models AT530 and later, a file should be created in the /tftpboot
        directory, named atcXXXXXXXXXXXX.cfg where XXXXXXXXXXXX is the lowercase
        MAC address of the phone Ethernet interface. This file contains the phone
        connection parameters. There is a version string at the beginning of the
        file, which must be set to a value greater than the version string stored
        in the phone. To fetch the current version string, it is necessary to
        issue a series of HTTP requests to the phone.'''

        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        if self._model in ('AT800'):
            r = self._updateLocalConfig_AT800()
        elif self._model == 'AT320':
            r = self._updateLocalConfig_AT320()
        else:
            r = self._updateLocalConfig_AT530()
        if r:
            self._unregister()
            self._setConfigured()
        return r

    def _updateLocalConfig_AT320(self):
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
            # Attempt to login into admin telnet
            telnet.read_until('Password:', 10)
            if self._telnet_username != None: telnet.write(self._telnet_username.encode() + '\r\n')
            if self._telnet_password != None: telnet.write(self._telnet_password.encode() + '\r\n')

            # Wait for either prompt or password prompt
            idx, m, text = telnet.expect([r'Password:', r'P:\\>'], 10)
            if idx == 0:
                telnet.close()
                logging.error('Endpoint %s@%s detected ACCESS DENIED on telnet connect')
                return False
            else:
                # Unfortunately, the brain-damaged telnet implementation of the
                # AT320 is unable to cope with receiving all of the required
                # commands at once in a single network packet. If this is
                # attempted, the AT320 will only answer to the first command and
                # discard all the others. To work around this, a queue of
                # commands is created, so that they can be spoon-fed to the
                # phone one by one, checking between them whether the phone is
                # ready for the next command.

                # This phone model supports a single account
                extension = self._accounts[0]

                # Program phone parameters
                telnetQueue = [
                    'set codec1 2',                     # g711u
                    'set ringtype 2',                   # user define
                    'set service 1',                    # enable
                    'set phonenumber ' + extension.account,
                    'set account ' + extension.account,
                    'set pin ' + extension.secret
                    ]
                if extension.tech == 'sip':
                    telnetQueue += [
                        'set servicetype 13',               #sipphone
                        'set sipproxy ' + self._serverip,
                        'set domain ' + self._serverip,
                        'set dtmf 1',
                        'set outboundproxy 1'
                        ]
                else:
                    telnetQueue += [
                        'set serviceaddr ' + self._serverip,
                        'set localport 4569'
                        ]

                # Program network parameters
                if self._dhcp:
                    telnetQueue += ['set iptype 1']
                else:
                    telnetQueue += [
                        'set iptype 0',
                        'set ip ' + self._static_ip,
                        'set subnetmask ' + self._static_mask
                        ]
                    if self._static_gw != None:
                        telnetQueue += ['set router ' + self._static_gw ]
                    else:
                        telnetQueue += ['set router 0.0.0.0']
                    if self._static_dns1 != None:
                        telnetQueue += ['set dns ' + self._static_dns1 ]
                    else:
                        telnetQueue += ['set dns 0.0.0.0']
                    if self._static_dns2 != None:
                        telnetQueue += ['set dns2 ' + self._static_dns2 ]
                    else:
                        telnetQueue += ['set dns2 0.0.0.0']

                telnetQueue += ['set sntpip ' + self._serverip, 'write']

                # Write all of the commands
                for cmd in telnetQueue:
                    telnet.write(cmd + '\r\n')
                    idx, m, text = telnet.expect([r'P:\\>', r'rebooting...'], 10)
                telnet.close()
                if idx == 1:
                    # Successfully issued the last command
                    return True
                else:
                    logging.error('Endpoint %s@%s fell out of sync' % (self._vendorname, self._ip))
                    return False
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        return True

    def _updateLocalConfig_AT530(self):
        ''' Local configuration for AT530 and later
        The AT530 and later uses a web server that only understands HTTP/1.1, not
        HTTP/1.0. Additionally, the server needs the Connection: keep-alive
        header (even though keep-alive is supposed to be the HTTP/1.1 default),
        and all of the requests must be performed on the same TCP connection, if
        at all possible. Failure to follow these rules will result in truncated
        output.

        In some versions of the AT610 firmware, if the phone has been already
        configured before, and an attempt is made to fetch the config.txt to
        query the current version number, the request will hang for a very long
        time (more than 5 minutes). Since all we need is the version number, not
        the complete configuration, we attempt to fetch some known pages that
        expose the current version number, which do not hang, before falling back
        to the config.txt file.

        Additionally, in old versions of the AT610 firmware (2011 or earlier),
        the phone will sometimes refuse to apply a configuration file, even
        though it has a version number greater than the one announced by the
        phone. This problem occurs intermittently and is a known firmware bug.
        The only solution for this bug is a firmware update.

        Very old versions of the AT530 firmware use a slightly different config
        format from the newer versions. Old firmware can be identified because
        it exposes the nonce in /right.htm instead of /.
        '''
        configVersion = self._fetchOldConfigVersion()
        if configVersion == None: return False
        logging.info('Endpoint %s@%s previous config version is %s' %
            (self._vendorname, self._ip, configVersion))
        t = str(int(''.join(configVersion.split('.'))) + 1)
        configVersion = t[:1] + '.' + t[1:]
        logging.info('Endpoint %s@%s new config version is %s' %
            (self._vendorname, self._ip, configVersion))

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = 'atc' + (self._mac.replace(':', '').lower()) + '.cfg'
        self._writeAtcom530Template(configVersion, sConfigFile, 'Atcom_local_530_v' + str(self._firmware) + '.tpl')

        # Force download of new configuration followed by reboot
        return self._transferConfig2Phone(sConfigFile)

    def _transferConfig2Phone(self, sConfigFile):
        '''Transfer configuration file to Atcom phone (AT530 or later) '''
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

        # Attempt to login into admin telnet
        try:
            telnet.read_until('Login:', 10)
            if self._telnet_username != None: telnet.write(self._telnet_username.encode() + '\r\n')
            #telnet.read_until('Password:')
            if self._telnet_password != None: telnet.write(self._telnet_password.encode() + '\r\n')

            # Wait for either prompt or login prompt
            idx, m, text = telnet.expect([r'Login:', r'# '], 10)
            if idx == 0:
                telnet.close()
                logging.error('Endpoint %s@%s detected ACCESS DENIED on telnet connect')
                return False
            else:
                telnet.write('download tftp -ip ' + self._serverip.encode() + ' -file ' + sConfigFile.encode() + '\r\n')
                idx, m, text = telnet.expect([r'# '], 10)
                if 'failed' in text:
                    logging.error('Endpoint %s@%s failed to retrieve new config' % (self._vendorname, self._ip))
                    return False

                # Write all of the commands
                telnetQueue = [
                    'save',
                    'reload'
                ]
                for cmd in telnetQueue:
                    telnet.write(cmd + '\r\n')
                    idx, m, text = telnet.expect([r'# ', r'reload'], 10)
                telnet.close()
                if 'reload' in text:
                    # Successfully issued the last command
                    return True
                else:
                    logging.error('Endpoint %s@%s fell out of sync' % (self._vendorname, self._ip))
                    return False
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

    def _fetchOldConfigVersion(self, customList = None):
        '''Open connection to web interface to fetch current configuration '''
        # Try next URL where config version might be found
        if customList == None:
            listaConfigVersion = (
                '/autoprovision.htm',    # AT610
                '/autoupdate.htm',       # AT530
                '/config.txt'            # Placed last because it is prone to hangs on AT610
            )
        else:
            listaConfigVersion = customList
        htmlres = self._fetchAtcomAuthenticatedPage(listaConfigVersion)
        if htmlres == None: return None
        resource, htmlbody = htmlres
        if resource == '/config.txt':
            regexp = r'<<VOIP CONFIG FILE>>Version:([2-9]{1}\.[0-9]{4})'
        else:
            regexp = r'Current( Config)? Version.*?([2-9]{1}\.[0-9]{4})'
        m = re.search(regexp, htmlbody, re.DOTALL)
        if m == None:
            logging.error('Endpoint %s@%s failed to locate config version in HTTP response' %
                (self._vendorname, self._ip))
            print htmlbody
            return None
        if resource == '/config.txt':
            return m.group(1)
        return m.group(2)

    def _getNonce(self, http):
        noncesources = ('/', '/right.htm')
        for noncesource in noncesources:
            http.request('GET', noncesource, None, {'Connection' : 'keep-alive'})
            resp = http.getresponse()
            htmlbody = resp.read()
            if not resp.status in (200, 404):
                logging.error('Endpoint %s@%s failed to fetch nonce for HTTP configuration - got response code %s' %
                    (self._vendorname, self._ip, resp.status))
                return (None, None)
            elif resp.status == 200:
                m = re.search(r'<input type="hidden" name="nonce" value="([0-9a-zA-Z]+)">', htmlbody)
                if m != None: break
        if m == None:
            logging.error('Endpoint %s@%s failed to locate nonce in HTTP response' %
                (self._vendorname, self._ip))
            return (None, None)
        nonce = m.group(1)

        # Identify firmware
        if noncesource == '/right.htm': self._firmware = 1 # Old firmware
        return (nonce, noncesource)

    def _setupAtcomAuthentication(self):
        http = httplib.HTTPConnection(self._ip)

        nonce, noncesource = self._getNonce(http)
        if nonce == None:
            http.close()
            return (None, None)

        # Simulate POST to allow fetching rest of content
        extraheaders = {
            'Connection' : 'keep-alive',
            'Cookie' : 'auth=' + nonce,
            'Content-Type' : 'application/x-www-form-urlencoded'
        }
        postvars = {
            'encoded'   :   self._http_username + ':' +
                md5.new(':'.join((self._http_username, self._http_password, nonce))).hexdigest(),
            'nonce'     :   nonce,
            'goto'      :   'Logon',
            'URL'       :   '/'
        }
        postdata = urllib.urlencode(postvars)
        http.request('POST', noncesource, postdata, extraheaders)
        resp = http.getresponse()
        if resp.status != 200:
            logging.error('Endpoint %s@%s failed to fetch login result - got response code %s' %
                (self._vendorname, self._ip, resp.status))
            http.close()
            return (None, None)
        htmlbody = resp.read()
        return (http, nonce)

    def _cleanupAtcomAuthentication(self, http, nonce):
        # Got page, log out of HTTP interface
        extraheaders = {
            'Connection' : 'keep-alive',
            'Cookie' : 'auth=' + nonce,
            'Content-Type' : 'application/x-www-form-urlencoded'
        }
        http.request('POST', '/LogOut.htm', 'DefaultLogout=Logout', extraheaders)
        resp = http.getresponse()
        htmlbody = resp.read()
        if resp.status != 200:
            logging.error('Endpoint %s@%s failed to logout from phone - got response code %s' %
                (self._vendorname, self._ip, resp.status))
            http.close()
            return False
        else:
            if 'No Post Handler' in htmlbody:
                logging.warning(
                    'Endpoint %s@%s phone lacks handler for LogOut.htm - HTTP '\
                    'login might only work a limited number of times.' %
                    (self._vendorname, self._ip))

        http.close()
        return True

    def _fetchAtcomAuthenticatedPage(self, urlsProbe):
        '''Fetch the first page from the list of URLs under authentication '''
        htmlres = None
        try:
            http, nonce = self._setupAtcomAuthentication()
            if http == None: return None

            for resource in urlsProbe:
                http.request('GET', resource, None, {'Connection' : 'keep-alive', 'Cookie' : 'auth=' + nonce})
                resp = http.getresponse()
                htmlbody = resp.read()
                if resp.status == 200:
                    htmlres = (resource, htmlbody)
                    break
                if resp.status != 404:
                    logging.error('Endpoint %s@%s failed to fetch config resource - got response code %s' %
                        (self._vendorname, self._ip, resp.status))
                    http.close()
                    return None

            if not self._cleanupAtcomAuthentication(http, nonce):
                return None

            return htmlres
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return None

    def _writeAtcom530Template(self, configVersion, sConfigFile, sTemplate):
        sConfigPath = self._tftpdir + '/' + sConfigFile

        # This code assumes all Atcoms support at most 1 iax2 account
        vars = self._prepareVarList()
        vars.update({
            'enable_bridge'     :   int(self._bridge),
            'time_zone'         :   self._timeZone,
            'config_filename'   :   sConfigFile,
            'version_cfg'       :   configVersion,
            'default_protocol'  :   2,
            'iax2'              :   None,
        })
        if self._accounts[0].tech == 'sip': vars['default_protocol'] = 2
        if self._accounts[0].tech == 'iax2': vars['default_protocol'] = 4
        for extension in self._accounts:
            if extension.tech == 'iax2' and vars['iax2'] == None:
                vars['iax2'] = extension
        self._writeTemplate(sTemplate, vars, sConfigPath)

    def _updateLocalConfig_AT800(self):
        ''' Local configuration for AT800
        The AT800 uses a XML configuration file. For this phone, it is enough
        to generate the XML content, then POST it through a special URL on the
        phone, with no authentication. If successful, the phone reboots
        immediately.
        '''
        xmlcontent = self._getAtcom800Template()
        try:
            status = True

            boundary = '------------------ENDPOINTCONFIG'
            postdata = '--' + boundary + '\r\n' +\
                'Content-Disposition: form-data; name="configfile"; filename="config.xml"\r\n' +\
                'Content-Type: text/xml\r\n' +\
                '\r\n' +\
                xmlcontent + '\r\n' +\
                '--' + boundary + '--\r\n'
            http = httplib.HTTPConnection(self._ip)
            http.request('POST', '/goform/submit_upload_configfile', postdata,
                { 'Content-Type' : ' multipart/form-data; boundary=' + boundary })
            resp = http.getresponse()
            htmlbody = resp.read()
            if resp.status != 200:
                logging.error('Endpoint %s@%s failed to post configuration - got response code %s' %
                    (self._vendorname, self._ip, resp.status))
                status = False
            elif not ('Upload Successfully' in htmlbody):
                logging.error('Endpoint %s@%s failed to post configuration - unknown body follows: %s' %
                    (self._vendorname, self._ip, htmlbody))
                status = False
            http.close()
            return status
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False

    def _getAtcom800Template(self):
        vars = self._prepareVarList()
        vars.update({
            'enable_bridge'     :   int(self._bridge),
            'time_zone'         :   self._timeZone,
        })
        return self._fetchTemplate('Atcom_local_800.tpl', vars)