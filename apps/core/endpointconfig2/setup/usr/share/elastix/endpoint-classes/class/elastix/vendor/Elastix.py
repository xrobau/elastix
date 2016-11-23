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
import elastix.vendor.Grandstream
from eventlet.green import socket, urllib2, urllib
import cookielib
import re
import time
import os

class Endpoint(elastix.vendor.Grandstream.Endpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Elastix', amipool, dbpool, sServerIP, sIP, mac)
        self._timeZone = 'auto'
        self._language = 'es'

    def probeModel(self):
        ''' Probe specific model of Elastix phone. This method should only be
            entered on non-Grandstream models (LXP-180) '''
        self._loadCustomCredentials()
        if self._http_username == None: self._http_username = 'admin'
        if self._http_password == None: self._http_password = 'admin'

        sModel = None
        try:
            response = urllib2.urlopen('http://' + self._ip + '/')
            htmlbody = response.read()
        except urllib2.HTTPError, e:
            if e.code == 401 and 'WWW-Authenticate' in e.headers:
                m = re.search(r'realm="Base station"', e.headers['WWW-Authenticate'])
                if m != None:
                    sModel = 'LXP180'
        except Exception, e:
            pass

        if  sModel == None and 'fcgi/do?id=1' in htmlbody:
            # This condition means we got an LXP150 or LXP250
            try:
                opener, body = self._getAuthOpener_LXP150(self._http_username, self._http_password)
                if not opener is None:
                    m = re.search(r"id=hcProductName type=hidden value='(.+?)'", body)
                    if m != None:
                        if m.group(1) == 'SP-R50':
                            sModel = 'LXP150'
                        if m.group(1) == 'SP-R53':
                            sModel = 'LXP250'
            except Exception, e:
                logging.error('Endpoint %s@%s LXPx50 failed to authenticate - %s' %
                (self._vendorname, self._ip, str(e)))


        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        if self._model in ('LXP180'):
            return self._updateLocalConfig_LXP180()
        elif self._model in ('LXP150', 'LXP250'):
            return self._updateLocalConfig_LXPx50()
        else:
            # Delegate to old (Grandstream) configuration
            return super(Endpoint, self).updateLocalConfig()

    # TODO: consolidar con método parecido de RCA
    def _updateLocalConfig_LXP180(self):
        # This phone configuration is verbose on the network.

        # Not because it requires Digest authentication...
        # ...but because nowhere in sight is a way to send the entire
        # configuration at once, such as a configuration file. Therefore
        # the following code needs to send each bit of configuration, on a
        # separate request:

        # Delete each of the existing account configurations, one by one:
        postvars = [
            ('ConfPage', 'sipsetting'),
            ('D7', 1),  # account number, 0-based
            ('D28', 0), # 0 to disable, 1 to enable
            ('T23', ''),
            ('T24', ''),
            ('T20', ''),
            ('T21', ''),
            ('T22', ''),
            ('T2', ''),
            ('T4', 5060),
            ('D57', 0),
            ('T3', ''),
            ('D8', 0),
            ('D1', 0),
            ('T5', ''),
            ('T6', 3478),
            ('T25', ''),
            ('D30', 0),
            ('T32', ''),
            ('T33', ''),
            ('D31', 0),
            ('T34', ''),
            ('T35', ''),
            ('D33', 0),
            ('D11', 9),
            ('D58', 18),
            ('D59', 8),
            ('D60', 0),
            ('D61', 2),
            ('D62', 99),
            ('D63', 98),
            ('D64', -1),
            ('T18', 5060),
            ('D22', 2),
            ('t_ExpirySeconds', 3600),
            ('D65', 0),
            ('D66', 0),
            ('D67', 0),
            ('D68', 0),
            ('D46', 0),
            ('D47', 20),
            ('D29', 0),
            ('t_SiteIp', ''),
        ]
        for idx in range(self._max_sip_accounts -1, -1, -1):
            self._updateList(postvars, {'D7': idx})
            if not self._doAuthPostIP160s('/cgi-bin/config.cgi', postvars):
                return False

        # Now, send the new account configurations again, one by one:
        stdvars = self._prepareVarList()
        for idx in range(len(stdvars['sip'])):
            self._updateList(postvars, {
                'D7':   idx,
                'D28':  1,
                'T23':  stdvars['sip'][idx].description,
                'T24':  stdvars['sip'][idx].description,
                'T20':  stdvars['sip'][idx].account,
                'T21':  stdvars['sip'][idx].account,
                'T22':  stdvars['sip'][idx].secret,
                'T2':   stdvars['server_ip'],
                'T25':  '*97'
            })
            if not self._doAuthPostIP160s('/cgi-bin/config.cgi', postvars):
                return False

        # Send the network configuration LAST
        if self._dhcp:
            postvars = [
                ('netsetting', 'networkset'),
                ('RadioGroup1', 'r1'),
            ]
        else:
            postvars = [
                ('netsetting', 'networkset'),
                ('RadioGroup2', 'r2'),
                ('textfield2', stdvars['static_ip']),
                ('textfield3', stdvars['static_mask']),
                ('textfield4', stdvars['static_gateway']),
                ('textfield5', stdvars['static_dns1']),
                ('textfield6', '0.0.0.0'),
            ]
            if stdvars['static_dns2'] != None:
                self._updateList(postvars, { 'textfield6': stdvars['static_dns2'] })
        if not self._doAuthPostIP160s('/cgi-bin/config.cgi', postvars):
            return False

        self._unregister()
        self._setConfigured()
        return True

    # TODO: consolidar con mismo método de RCA
    def _updateList(self, postvars, updatevars):
        for i in range(len(postvars)):
            if postvars[i][0] in updatevars:
                postvars[i] = (postvars[i][0], updatevars[postvars[i][0]])

    # TODO: consolidar con mismo método de RCA
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

    def _getAuthOpener_LXP150(self, http_user, http_pass):
        ''' Create an authenticated opener for the LXPx50 series.

        The LXPx50 HTTP authentication is again weird. First, a request must be
        sent to the phone with a Cookie with a SessionId set to a random number
        between 0 and 99999. Sending 0 works just as well. The first request must
        be a GET that asks the phone to calculate a hash for a specified username
        and password. The hash is embedded inside a HTML fragment in the response.
        Next the hash must be sent as a new Cookie in a POST request that also
        includes the original SessionId number and the UserName as cookies. A
        successful login returns a response with the phone status, including the
        phone model. Additionally, the response after a successful login includes
        a brand new SessionId that must be replaced in the opener cookie.
        '''
        cookiejar = cookielib.CookieJar(cookielib.DefaultCookiePolicy(rfc2965=True))
        sesscookie = cookielib.Cookie(None, 'SessionId', '0', None, False,
            self._ip, False, False,
            '/', False, False, str((int)(time.time() + 3600)),
            False, 'SessionId', None, None)
        cookiejar.set_cookie(sesscookie)
        opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))
        response = opener.open('http://' + self._ip + '/fcgi/do?' + urllib.urlencode({
            'action': 'Encrypt',
            'UserName' : http_user,
            'Password' : http_pass}))
        body = response.read()
        m = re.search(r"id=hcSingleResult type=hidden value='(.+?)'", body)
        if m is None:
            return (None, None)
        encrypted_password = m.group(1)

        sesscookie = cookielib.Cookie(None, 'UserName', http_user, None, False,
            self._ip, False, False, '/', False, False, str((int)(time.time() + 3600)),
            False, 'UserName', None, None)
        cookiejar.set_cookie(sesscookie)
        sesscookie = cookielib.Cookie(None, 'Password', encrypted_password, None, False,
            self._ip, False, False, '/', False, False, str((int)(time.time() + 3600)),
            False, 'Password', None, None)
        cookiejar.set_cookie(sesscookie)
        response = opener.open('http://' + self._ip + '/fcgi/do?id=1',
            'SubmitData=begin%26Operation%3DCreateSession%26DestURL%3Did%6021%26SubmitData%3Dend')

        # Find new SessionId value. What, no Set-Cookie header?
        body = response.read()
        m = re.search(r"id=hcSessionIdNow type=hidden value='(.+?)'", body)
        if m != None:
            sesscookie = cookielib.Cookie(None, 'SessionId', m.group(1), None, False,
                self._ip, False, False,
                '/', False, False, str((int)(time.time() + 3600)),
                False, 'SessionId', None, None)
            cookiejar.set_cookie(sesscookie)
        else:
            logging.error('Endpoint %s@%s LXPx50 failed to authenticate - new session ID not found in response' %
                (self._vendorname, self._ip))
            return (None, None)

        # Subsequent requests must NOT have the UserName/Password cookies
        cookiejar.clear(self._ip, '/', 'UserName')
        cookiejar.clear(self._ip, '/', 'Password')
        return (opener, body)

    def _updateLocalConfig_LXPx50(self):
        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').lower()) + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile

        vars = self._prepareVarList()

        # _writeTemplate is used instead of _fetchTemplate because file is
        # requested by TFTP on reboot
        self._writeTemplate('Elastix_LXPx50_cfg.tpl', vars, sConfigPath)

        # Prepare an opener with authentication cookies
        try:
            opener, body = self._getAuthOpener_LXP150(self._http_username, self._http_password)
            if opener is None:
                # Failed to authenticate
                os.remove(sConfigPath)
                return False
        except Exception, e:
            logging.error('Endpoint %s@%s LXPx50 failed to authenticate - %s' %
                (self._vendorname, self._ip, str(e)))
            os.remove(sConfigPath)
            return False

        # Send the configuration file through it
        try:
            f = open(sConfigPath)
            cfgcontent = f.read()
            f.close()

            boundary = '------------------ENDPOINTCONFIG'
            postdata = '--' + boundary + '\r\n' +\
                'Content-Disposition: form-data; name="uploadType"\r\n' +\
                '\r\n' +\
                '&Operation=Upload&DestUpFile=endpointconfig.cfg&' + '\r\n' +\
                '--' + boundary + '\r\n' +\
                'Content-Disposition: form-data; name="importConfigFile"; filename="endpointconfig.cfg"\r\n' +\
                'Content-Type: application/octet-stream\r\n' +\
                '\r\n' +\
                cfgcontent + '\r\n' +\
                '--' + boundary + '--\r\n'

            request = urllib2.Request(
                'http://' + self._ip + '/fcgi/do?id=6&id=2',
                postdata,
                {'Content-Type': ' multipart/form-data; boundary=' + boundary})

            # The phone configuration restore is known to hang for 25-30 seconds
            oldtimeout = socket.getdefaulttimeout()
            socket.setdefaulttimeout(40)
            try:
                response = opener.open(request)
            finally:
                socket.setdefaulttimeout(oldtimeout)

            body = response.read()
            if not 'reboot' in body.lower():
                logging.error('Endpoint %s@%s failed to maintain authentication (POST)' %
                        (self._vendorname, self._ip))
                os.remove(sConfigPath)
                return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s unable to send file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Reboot the phone.
        self._amireboot('aastra-check-cfg')
        self._unregister()
        self._setConfigured()
        return True
