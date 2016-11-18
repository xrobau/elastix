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
import struct
import eventlet
from eventlet.green import socket, urllib2, urllib, os
import errno
import cjson
from elastix.BaseEndpoint import BaseEndpoint
telnetlib = eventlet.import_patched('telnetlib')
import cookielib
import time
paramiko = eventlet.import_patched('paramiko')

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Grandstream', amipool, dbpool, sServerIP, sIP, mac)

        # Calculate timezone, 'auto' or GMT offset in minutes +720
        #self._timeZone = BaseEndpoint.getTimezoneOffset() / 60 + 720
        self._timeZone = 'auto'
        self._language = 'es'

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'timezone' in param: self._timeZone = param['timezone']
        if 'language' in param: self._language = param['language']
        return True

    def probeModel(self):
        '''Probe specific model of the Grandstream phone

        To probe for the specific model, a telnet session is tried first. The
        login banner exposes the phone model. If the telnet session is refused,
        an attempt is made to invoke the manager URL through HTTP.
        '''
        bTelnetFailed = False
        try:
            telnet = telnetlib.Telnet()
            telnet.open(self._ip)
            telnet.get_socket().settimeout(5)
        except socket.timeout, e:
            logging.error('Endpoint %s@%s failed to telnet - timeout (%s)' %
                (self._vendorname, self._ip, str(e)))
            return
        except socket.error, e:
            logging.info('Endpoint %s@%s failed to telnet - %s. Trying HTTP...' %
                (self._vendorname, self._ip, str(e)))
            bTelnetFailed = True


        sModel = None
        # If telnet failed to connect, the model might still be exposed through HTTP
        if bTelnetFailed:
            try:
                # Try detecting GXP2200 or similar
                try:
                    response = urllib2.urlopen('http://' + self._ip + '/manager?action=product&time=0')
                    htmlbody = response.read()
                    if response.code == 200:
                        # Response=Success\r\nProduct=GXP2200\r\n
                        m = re.search(r'Product=(\w+)', htmlbody)
                        if m != None: sModel = m.group(1)
                except urllib2.HTTPError, e:
                    # Ignore 404 error
                    pass

                # Try detecting Elastix LXP200 with updated firmware
                try:
                    response = urllib2.urlopen('http://' + self._ip + '/cgi-bin/api.values.get?request=phone_model:1395')
                    if response.code == 200:
                        jsonvars = self._parseBotchedJSONResponse(response)
                        if jsonvars != None and 'body' in jsonvars:
                            if '1395' in jsonvars['body'] and jsonvars['body']['1395'] == 'Elastix':
                                self._saveVendor('Elastix')
                            if 'phone_model' in jsonvars['body']:
                                sModel = jsonvars['body']['phone_model']
                except urllib2.HTTPError, e:
                    # Ignore 404 error
                    pass
            except Exception, e:
                pass
        else:
            try:
                idx, m, text = telnet.expect([r'Password:'], 10)
                telnet.close()

                # This is known to detect GXV3140, GXP2120
                m = re.search(r'Grandstream (\S+)\s', text)
                if m != None:
                    sModel = m.group(1)

                # If this matches, this is an Elastix phone (rebranded Grandstream)
                m = re.search(r'Elastix (\S+)\s', text)
                if m != None:
                    self._saveVendor('Elastix')
                    sModel = m.group(1)

                #if sModel == None:
                #    print text
            except socket.error, e:
                logging.error('Endpoint %s@%s connection failure - %s' %
                    (self._vendorname, self._ip, str(e)))
                return False

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for Grandstream endpoints (local):

        The file cfgXXXXXXXXXXXX contains the SIP configuration. Here
        XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
        Grandstream is special in that the file is not text but a binary
        encoding, which is generated by _encodeGrandstreamConfig().

        To reboot the phone, it is necessary to issue the AMI command:
        For GXP280,GXV3140,GXV3175: "sip notify cisco-check-cfg {$EXTENSION}"
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = 'cfg' + (self._mac.replace(':', '').lower())
        sConfigPath = self._tftpdir + '/' + sConfigFile

        vars = self._hashTableGrandstreamConfig()

        try:
            self._writeContent(sConfigPath, self._encodeGrandstreamConfig(vars))
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Attempt to send configuration via HTTP to phone. This is required for
        # static provisioning
        if not self._enableStaticProvisioning(vars):
            return False

        # Try a bunch of reboot methods. If a particular method fails, try the next.
        rebootSuccess = False
        if self._hasRegisteredExtension():
            # Check if there is at least one registered extension. This is required
            # for sip notify to work
            # GXV3175 wants check-sync, not sys-control
            self._amireboot('cisco-check-cfg')
            rebootSuccess = True
        if not rebootSuccess and self._telnet_password != None:
            if self._rebootbytelnet():
                rebootSuccess = True
        if not rebootSuccess and self._ssh_password != None:
            if self._rebootbyssh():
                rebootSuccess = True
        if not rebootSuccess and self._http_password != None:
            if self._rebootbyhttp():
                rebootSuccess = True

        if rebootSuccess:
            self._unregister()
            self._setConfigured()
            return True
        return False
    def _enableStaticProvisioning(self, vars):
        # Detect what kind of HTTP interface is required
        staticProvImpls = [
            # Interface for newer GXP140x firmware - JSON based
            ('GXP140x JSON', '/cgi-bin/api.values.post', self._enableStaticProvisioning_GXP140x),

            # Interface for old BT200 firmware or similar
            ('BT200',        '/update.htm',              self._enableStaticProvisioning_BT200),

            # Interface for GXVxxxx firmware or similar
            ('GXVxxxx',      '/manager',                 self._enableStaticProvisioning_GXV),

            # Interface for GXP1450 firmware or similar
            ('GXP1450',      '/cgi-bin/update',          self._enableStaticProvisioning_GXP1450),
        ]
        for impl in staticProvImpls:
            try:
                response = urllib2.urlopen('http://' + self._ip + impl[1])
                body = response.read()
                logging.info('Endpoint %s@%s appears to have %s interface...' %
                            (self._vendorname, self._ip, impl[0]))
                return impl[2](vars)
            except urllib2.HTTPError, e:
                if e.code != 404:
                    logging.error('Endpoint %s@%s failed to detect %s - %s' %
                        (self._vendorname, self._ip, impl[0], str(e)))
                    return False
            except socket.error, e:
                logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
                return False

        logging.warning('Endpoint %s@%s cannot identify HTTP interface, static provisioning might not work.' %
                    (self._vendorname, self._ip))
        return True

    def _enableStaticProvisioning_GXP140x(self, vars):
        try:
            # Login into interface and get SID. Check proper Content-Type
            response = urllib2.urlopen('http://' + self._ip + '/cgi-bin/dologin',
                urllib.urlencode({'password' : self._http_password}))
            body = response.read()
            if response.info()['Content-Type'] <> 'application/json':
                logging.error('Endpoint %s@%s GXP140x - dologin answered not application/json but %s' %
                    (self._vendorname, self._ip, response.info()['Content-Type']))
                return False

            # Check successful login and get sid
            jsonvars = cjson.decode(body)
            if not ('body' in jsonvars and 'sid' in jsonvars['body']):
                logging.error('Endpoint %s@%s GXP140x - dologin failed login' %
                    (self._vendorname, self._ip))
                return False
            sid = jsonvars['body']['sid']

            # Post vars with sid
            vars.update({'sid' : sid})
            response = urllib2.urlopen('http://' + self._ip + '/cgi-bin/api.values.post',
                urllib.urlencode(vars))

            jsonvars = self._parseBotchedJSONResponse(response)
            if jsonvars == None:
                return False

            if not ('response' in jsonvars and jsonvars['response'] == 'success' \
                    and 'body' in jsonvars and 'status' in jsonvars['body'] and jsonvars['body']['status'] == 'right' ):
                logging.error('Endpoint %s@%s GXP140x - vars rejected by interface - %s' %
                    (self._vendorname, self._ip, body))
                return False

            return True
        except cjson.DecodeError, e:
            logging.error('Endpoint %s@%s GXP140x received invalid JSON - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s GXP140x failed to send vars to interface - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s GXP140x failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

    def _parseBotchedJSONResponse(self, response):
        jsonvars = None
        body = response.read()
        if response.info():
            if response.info()['Content-Type'] <> 'application/json':
                logging.error('Endpoint %s@%s GXP140x - api.values.post answered not application/json but %s' %
                    (self._vendorname, self._ip, response.info()['Content-Type']))
                return None
            jsonvars = cjson.decode(body)
        else:
            # The GXP1400 has been discovered to violate the HTTP protocol.
            # The response for /cgi-bin/api.values.post sticks a shebang
            # header before the HTTP headers of the response. This causes
            # the header parsing to end early and the body gets prepended
            # with the headers. We now have to undo this mess.
            expectbody = False
            for s in body.splitlines():
                if not expectbody:
                    m = re.search(r'Content-Type: (\S+)', s)
                    if m != None:
                        if m.group(1) <> 'application/json':
                            logging.error('Endpoint %s@%s GXP140x - api.values.post answered not application/json but %s' %
                                (self._vendorname, self._ip, m.group(1)))
                            return None
                    if s == '':
                        expectbody = True
                else:
                    # This expects the body to be a single JSON string in one line
                    jsonvars = cjson.decode(s)
                    break
        return jsonvars

    def _enableStaticProvisioning_BT200(self, vars):
        try:
            # Login into interface
            cookiejar = cookielib.CookieJar(cookielib.DefaultCookiePolicy(rfc2965=True))
            opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))
            response = opener.open('http://' + self._ip + '/dologin.htm',
                urllib.urlencode({'Login' : 'Login', 'P2' : self._http_password, 'gnkey' : '0b82'}))
            body = response.read()
            if 'dologin.htm' in body:
                logging.error('Endpoint %s@%s BT200 - dologin failed login' %
                    (self._vendorname, self._ip))
                return False

            # Force cookie version to 0
            for cookie in cookiejar:
                cookie.version = 0

            response = opener.open('http://' + self._ip + '/update.htm',
                urllib.urlencode(vars) + '&gnkey=0b82')
            body = response.read()
            if 'dologin.htm' in body:
                logging.error('Endpoint %s@%s BT200 - dologin failed to keep session' %
                    (self._vendorname, self._ip))
                return False

            return True
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s BT200 failed to send vars to interface - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s BT200 failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

    def _enableStaticProvisioning_GXV(self, vars):
        try:
            # Login into interface
            opener = urllib2.build_opener(urllib2.HTTPCookieProcessor())
            response = opener.open('http://' + self._ip + '/manager?' + urllib.urlencode({
                'action': 'login',
                'Username' : self._http_username,
                'Secret' : self._http_password,
                'time':     (int)(time.time())}))
            body = response.read()
            if 'Error' in body:
                logging.error('Endpoint %s@%s GXV - dologin failed login' %
                    (self._vendorname, self._ip))
                return False

            # For this interface, the variables are translated as follows: The
            # source key of the form Pxxxx produces a variable var-dddd where
            # dddd is a counter. The corresponding value produces a variable
            # val-dddd with the same counter
            varcount = 0
            submitvars = {
                'action'    : 'put',
                'time'      :   (int)(time.time())
            }
            for pk in vars:
                varkey = 'var-' + ('%04d' % (varcount,))
                varval = 'val-' + ('%04d' % (varcount,))
                submitvars[varkey] = pk[1:]
                submitvars[varval] = vars[pk]
                varcount += 1

            response = opener.open('http://' + self._ip + '/manager?' + urllib.urlencode(submitvars))
            body = response.read()
            if not ('Success' in body):
                logging.error('Endpoint %s@%s GXV - dologin failed to keep session' %
                    (self._vendorname, self._ip))
                return False

            # Phonebook programming is a special case.
            submitvars = {
                'action'    :   'putdownphbk',
                'time'      :   (int)(time.time()),
                'url'       :   vars['P331'],
                'mode'      :   2,  # HTTP
                'clear-old' :   1,
                'flag'      :   1,  # 1 forces download right now
                'interval'  :   vars['P332'],
                'rm-redup'  :   1
            }
            response = opener.open('http://' + self._ip + '/manager?' + urllib.urlencode(submitvars))
            body = response.read()
            if not ('Success' in body):
                logging.error('Endpoint %s@%s GXV - could not reprogram phonebook' %
                    (self._vendorname, self._ip))

            return True
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s GXV failed to send vars to interface - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s GXV failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

    def _enableStaticProvisioning_GXP1450(self, vars):
        try:
            # Login into interface
            opener = urllib2.build_opener(urllib2.HTTPCookieProcessor())
            response = opener.open('http://' + self._ip + '/cgi-bin/dologin',
                urllib.urlencode({'Login' : 'Login', 'P2' : self._http_password, 'gnkey' : '0b82'}))
            body = response.read()
            if 'dologin' in body:
                logging.error('Endpoint %s@%s GXP1450 - dologin failed login' %
                    (self._vendorname, self._ip))
                return False

            response = opener.open('http://' + self._ip + '/cgi-bin/update',
                urllib.urlencode(vars) + '&gnkey=0b82')
            body = response.read()
            if 'dologin' in body:
                logging.error('Endpoint %s@%s GXP1450 - dologin failed to keep session' %
                    (self._vendorname, self._ip))
                return False
            return True
        except socket.error, e:
            logging.error('Endpoint %s@%s GXP1450 failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False


    def _rebootbytelnet(self):
        '''Start reboot of Grandstream phone by telnet'''
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

        # The Grandstream GXV3175 needs to have a wait of at least 1 second with
        # the stream open after the reboot command before the reboot command
        # will actually take effect. We let the timeout close the telnet stream.
        telnetwaitmodels = ('GXV3140', 'GXV3175', 'GXP2120', 'GXP1400', 'GXP1405', 'GXP1450')
        deliberatetimeout = False

        # Attempt to login into admin telnet
        try:
            #telnet.read_until('Login:')
            if self._telnet_username != None: telnet.write(self._telnet_username.encode() + '\r\n')
            telnet.read_until('Password:', 10)
            if self._telnet_password != None: telnet.write(self._telnet_password.encode() + '\r\n')

            # Wait for either prompt or login prompt
            idx, m, text = telnet.expect([r'Password:', r'>\s?'], 10)
            if idx == 0:
                telnet.close()
                logging.error('Endpoint %s@%s detected ACCESS DENIED on telnet connect' %
                              (self._vendorname, self._ip))
                return False
            else:
                if self._model in ('GXV3140', 'GXV3175'):
                    rebootcommand = 'reboot'
                else:
                    # GXP280 accepts just a 'r'
                    rebootcommand = 'reboot'
                telnet.write(rebootcommand + '\r\n')
                idx, m, text = telnet.expect([r'Rebooting', r'reboot'], 10)
                if self._model in telnetwaitmodels:
                    telnet.get_socket().settimeout(1)
                    deliberatetimeout = True
                    logging.info('Endpoint %s@%s waiting 1 second for reboot to take effect' %
                        (self._vendorname, self._ip))
                    telnet.read_all()
                else:
                    # For other models, reboot takes effect immediately
                    telnet.close()
        except socket.timeout, e:
            telnet.close()
            if not deliberatetimeout:
                logging.error('Endpoint %s@%s connection failure - %s' %
                    (self._vendorname, self._ip, str(e)))
                return False
        except socket.error, e:
            logging.error('Endpoint %s@%s connection failure - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        return True

    def _rebootbyssh(self):
        '''Start reboot of Grandstream phone by ssh'''
        oldtimeout = socket.getdefaulttimeout()
        try:
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.WarningPolicy())
            ssh.connect(self._ip, username=self._ssh_username, password=self._ssh_password, timeout=5)
            stdin, stdout, stderr = ssh.exec_command('reboot')
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

    def _rebootbyhttp(self):
        logging.error('Endpoint %s@%s unimplemented reboot by HTTP' %
            (self._vendorname, self._ip))
        return False

    def _hashTableGrandstreamConfig(self):
        stdvars = self._prepareVarList()

        # Remove 'http://' from begingging of string
        stdvars['phonesrv'] = stdvars['phonesrv'][7:]

        o = stdvars['server_ip'].split('.')
        vars = {
            'P192'  :   stdvars['server_ip'], # Firmware Server Path
            'P237'  :   stdvars['server_ip'], # Config Server Path
            'P212'  :   '0',            # Firmware Upgrade. 0 - TFTP Upgrade,  1 - HTTP Upgrade.
            'P290'  :   '{ x+ | *x+ | *xx*x+ }', # (GXV3175 specific) Dialplan string

            'P30'   :   stdvars['server_ip'], # NTP server
            'P64'   :   self._timeZone,
            'P144'  :   '0',    # Allow DHCP 42 to override NTP server?
            'P143'  :   '1',    # Allow DHCP to override timezone setting.

            'P8'    :   '0',            # DHCP=0 o static=1
            'P41': o[0], 'P42': o[1], 'P43': o[2], 'P44': o[3], # TFTP Server

            'P330'  :   1,    # 0-Disable phonebook download 1-HTTP 2-TFTP 3-HTTPS
            'P331'  :   stdvars['phonesrv'],
            'P332'  :   20,   # Minutes between XML phonebook fetches, or 0 to disable,

            'P1376' :   '1',  # Enable automatic attended transfer

            # TODO: inherit server language
            'P1362' :   self._language, # Phone display language
        }

        # Do per-model variable adjustments
        self._updateVarsByModel(stdvars, vars)

        if not self._dhcp:
            vars.update({
                'P8'     :  '1',    # DHCP=0 o static=1
            })
            if stdvars['static_ip'] != None:
                # IP Address
                o = stdvars['static_ip'].split('.')
                vars.update({'P9':  o[0], 'P10': o[1], 'P11': o[2], 'P12': o[3],})
            if stdvars['static_mask'] != None:
                # Subnet Mask
                o = stdvars['static_mask'].split('.')
                vars.update({'P13': o[0], 'P14': o[1], 'P15': o[2], 'P16': o[3],})
            if stdvars['static_gateway'] != None:
                # Gateway
                o = stdvars['static_gateway'].split('.')
                vars.update({'P17': o[0], 'P18': o[1], 'P19': o[2], 'P20': o[3],})
            if stdvars['static_dns1'] != None:
                # DNS Server 1
                o = stdvars['static_dns1'].split('.')
                vars.update({'P21': o[0], 'P22': o[1], 'P23': o[2], 'P24': o[3],})
            if stdvars['static_dns2'] != None:
                # IP Address
                o = stdvars['static_dns2'].split('.')
                vars.update({'P25': o[0], 'P26': o[1], 'P27': o[2], 'P28': o[3],})

        varmap = self._grandstreamvarmap()

        # Blank out all variables prior to assignment
        for i in range(0, min(len(varmap), stdvars['max_sip_accounts'])):
            vars[varmap[i]['enable']] = 0
            vars[varmap[i]['sipserver']] = stdvars['server_ip']
            vars[varmap[i]['outboundproxy']] = stdvars['server_ip']
            vars[varmap[i]['accountname']] = ''
            vars[varmap[i]['displayname']] = ''
            vars[varmap[i]['sipid']] = ''
            vars[varmap[i]['authid']] = ''
            vars[varmap[i]['secret']] = ''
            vars[varmap[i]['autoanswercallinfo']] = 1

        for i in range(0, min(len(varmap), len(stdvars['sip']))):
            vars[varmap[i]['enable']] = 1
            vars[varmap[i]['accountname']] = stdvars['sip'][i].description
            vars[varmap[i]['displayname']] = stdvars['sip'][i].description
            vars[varmap[i]['sipid']] = stdvars['sip'][i].extension
            vars[varmap[i]['authid']] = stdvars['sip'][i].account
            vars[varmap[i]['secret']] = stdvars['sip'][i].secret
        return vars

    # Should override this method if the model for the new vendor requires
    # additional variable modification.
    def _updateVarsByModel(self, stdvars, vars):
        if self._model in ('GXP280',):
            vars.update({'P73' : '1'})  # Send DTMF. 8 - in audio, 1 - via RTP, 2 - via SIP INFO

    def _grandstreamvarmap(self):
        varmap = [
            {'enable'               :   'P271', # Enable account
             'accountname'          :   'P270', # Account Name
             'sipserver'            :   'P47',  # SIP Server
             'sipid'                :   'P35',  # SIP User ID
             'authid'               :   'P36',  # Authenticate ID
             'secret'               :   'P34',  # Authenticate password
             'displayname'          :   'P3',   # Display Name (John Doe)
             'outboundproxy'        :   'P48',  # Outbound Proxy
             'autoanswercallinfo'   :   'P298', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P401', # Enable account
             'accountname'          :   'P417', # Account Name
             'sipserver'            :   'P402', # SIP Server
             'sipid'                :   'P404', # SIP User ID
             'authid'               :   'P405', # Authenticate ID
             'secret'               :   'P406', # Authenticate password
             'displayname'          :   'P407', # Display Name (John Doe)
             'outboundproxy'        :   'P403', # Outbound Proxy
             'autoanswercallinfo'   :   'P438', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P501', # Enable account
             'accountname'          :   'P517', # Account Name
             'sipserver'            :   'P502', # SIP Server
             'sipid'                :   'P504', # SIP User ID
             'authid'               :   'P505', # Authenticate ID
             'secret'               :   'P506', # Authenticate password
             'displayname'          :   'P507', # Display Name (John Doe)
             'outboundproxy'        :   'P503',  # Outbound Proxy
             'autoanswercallinfo'   :   'P538', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P601', # Enable account
             'accountname'          :   'P617', # Account Name
             'sipserver'            :   'P602', # SIP Server
             'sipid'                :   'P604', # SIP User ID
             'authid'               :   'P605', # Authenticate ID
             'secret'               :   'P606', # Authenticate password
             'displayname'          :   'P607', # Display Name (John Doe)
             'outboundproxy'        :   'P603', # Outbound Proxy
             'autoanswercallinfo'   :   'P638', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P1701',# Enable account
             'accountname'          :   'P1717',# Account Name
             'sipserver'            :   'P1702',# SIP Server
             'sipid'                :   'P1704',# SIP User ID
             'authid'               :   'P1705',# Authenticate ID
             'secret'               :   'P1706',# Authenticate password
             'displayname'          :   'P1707',# Display Name (John Doe)
             'outboundproxy'        :   'P1703',# Outbound Proxy
             'autoanswercallinfo'   :   'P1738',# Enable auto-answer by Call-Info
            },
            {'enable'               :   'P1801',# Enable account
             'accountname'          :   'P1817',# Account Name
             'sipserver'            :   'P1802',# SIP Server
             'sipid'                :   'P1804',# SIP User ID
             'authid'               :   'P1805',# Authenticate ID
             'secret'               :   'P1806',# Authenticate password
             'displayname'          :   'P1807',# Display Name (John Doe)
             'outboundproxy'        :   'P1803',# Outbound Proxy
             'autoanswercallinfo'   :   'P1838',# Enable auto-answer by Call-Info
            },
        ]

        return varmap

    def _encodeGrandstreamConfig(self, vars):
        # Encode configuration variables. The gnkey must be the last item in
        # order to prevent other variables from being followed by a null byte.
        payload = urllib.urlencode(vars) + '&gnkey=0b82'
        if (len(payload) & 1) != 0: payload = payload + '\x00'

        # Calculate block length in words, plus checksum
        length = 8 + len(payload) / 2
        binmac = self._mac.replace(':', '').lower().decode('hex')
        bindata = struct.pack('>LH6s', length, 0, binmac) + '\x0d\x0a\x0d\x0a' + payload
        wordsize = len(bindata) / 2
        checksum = 0x10000 - (sum(struct.unpack('>' + str(wordsize) +'H', bindata)) & 0xFFFF)
        bindata = struct.pack('>LH6s', length, checksum, binmac) + '\x0d\x0a\x0d\x0a' + payload

        return bindata
