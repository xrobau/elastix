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
import elastix.BaseEndpoint
from elastix.BaseEndpoint import BaseEndpoint
from eventlet.green import httplib, urllib, urllib2, socket
import base64

class Endpoint(BaseEndpoint):
    _global_serverip = None

    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Aastra', amipool, dbpool, sServerIP, sIP, mac)
        if Endpoint._global_serverip == None:
            Endpoint._global_serverip = sServerIP
        elif Endpoint._global_serverip != sServerIP:
            logging.warning('global server IP is %s but endpoint %s requires ' +
                'server IP %s - this endpoint might not work correctly.' %
                (Endpoint._global_serverip, sIP, sServerIP))

    def probeModel(self):
        '''Probe specific model of Aastra phone

        The Aastra web admin interface uses Basic authentication for access
        control. The authentication realm exposes the phone model like this:

        HTTP/1.1 401 Unauthorized
        Server: Aragorn
        WWW-Authenticate: Basic realm="Aastra 6757i"
        Connection: close
        Content-Length: 745
        Content-Type: text/html

        '''
        self._loadCustomCredentials()
        if self._http_username == None: self._http_username = 'admin'
        if self._http_password == None: self._http_password = '22222'
        sModel = None
        try:
            # Do not expect this to succeed. Only interested in exception.
            urllib2.urlopen('http://' + self._ip + '/')
        except urllib2.HTTPError, e:
            if e.code == 401 and 'WWW-Authenticate' in e.headers:
                m = re.search(r'realm="Aastra (.+)"', e.headers['WWW-Authenticate'])
                if m != None:
                    sModel = m.group(1)
                else:
                    password_manager = urllib2.HTTPPasswordMgrWithDefaultRealm()
                    password_manager.add_password(None, 'http://' + self._ip + '/',
                        self._http_username, self._http_password)
                    basic_auth_handler = urllib2.HTTPBasicAuthHandler(password_manager)
                    opener = urllib2.build_opener(basic_auth_handler)
                    try:
                        response = opener.open('http://' + self._ip + '/sysinfo.html')
                        htmlbody = response.read()
                        #  <TR>
                        #    <TD style="BORDER-BOTTOM: 1px dashed">Platform</TD>
                        #    <TD style="BORDER-BOTTOM: 1px dashed">9112i Revision 0</TD></TR>
                        #  <TR>
                        m = re.search(r'Platform</TD>.*?<TD.*?>(\w+)', htmlbody, re.IGNORECASE | re.DOTALL)
                        if m != None:
                            sModel = m.group(1)
                    except Exception, e:
                        pass
        except Exception, e:
            pass

        if sModel != None: self._saveModel(sModel)

    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Configuration for Aastra endpoints (global)

        SIP global definition goes in /tftpboot/aastra.cfg. Even though its
        contents are very similar to the per-phone config, and it also defines
        a SIP server, this file must exist and have a "valid" (even if redundant)
        configuration, or the phone will refuse to boot.
        '''
        vars = {'server_ip' : Endpoint._global_serverip}
        try:
            sConfigFile = 'aastra.cfg'
            sConfigPath = elastix.BaseEndpoint.TFTP_DIR + '/' + sConfigFile
            BaseEndpoint._writeTemplate('Aastra_global_cfg.tpl', vars, sConfigPath)
            return True
        except IOError, e:
            logging.error('Failed to write global config for Aastra - %s' % (str(e),))
            return False

    def updateLocalConfig(self):
        '''Configuration for Aastra endpoints (local)

        The file XXXXXXXXXXXX.cfg contains the plaintext SIP configuration. Here
        XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify aastra-check-cfg {$IP}. Verified with Aastra 57i and 6757i.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate UPPERCASE version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').upper()) + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        try:
            self._writeTemplate('Aastra_local_cfg.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        if not self._enableStaticProvisioning():
            return False

        # Reboot the phone.
        self._amireboot('aastra-check-cfg')
        self._unregister()
        self._setConfigured()
        return True

    def _enableStaticProvisioning(self):
        # The Aastra firmware is stateful, in an annoying way. Just submitting
        # a POST to the autoprovisioning URL from the factory-default setting will
        # only get a 200 OK with a message to visit sysinfo.html, and the settings
        # will NOT be applied.
        # To actually apply the settings, it is required to perform a dummy GET
        # to /sysinfo.html, discard anything returned, and only then
        # perform the POST.
        # Additionally, the TCP/IP and HTTP stack of the Aastra 6739i is buggy.
        # When performing a POST, the firmware wants the end of the headers and
        # the start of the body in the same TCP/IP packet. If they are on
        # different packets, the request hangs. Due to the way urllib2 works,
        # it introduces a flush between the two, which triggers said hang.
        # Therefore, the full POST request must be assembled and sent manually
        # as a single write.
        if not self._doAuthGet('/sysinfo.html'):
            return False

        # Set the Elastix server as the provisioning server
        postvars = {
            'protocol'      :   'TFTP',
            'tftp'          :   self._serverip,
            'tftppath'      :   '',
            'alttftp'       :   self._serverip,
            'alttftppath'   :   '',
            'usealttftp'    :   '1',
            'ftpserv'       :   '',
            'ftppath'       :   '',
            'ftpuser'       :   '',
            'ftppass'       :   '',
            'httpserv'      :   '',
            'httppath'      :   '',
            'httpport'      :   80,
            'httpsserv'     :   '',
            'httpspath'     :   '',
            'httpsport'     :   80,
            'autoResyncMode':   0,
            'autoResyncTime':   '00:00',
            'maxDelay'      :   15,
            'days'          :   0,
            'postList'      :   self._serverip,
        }
        postbody = urllib.urlencode(postvars)
        urlpath = '/configurationServer.html'
        postrequest = (\
            'POST %s HTTP/1.1\r\n' +\
            'Host: %s\r\n' +\
            'Connection: close\r\n' +\
            'Accept-Encoding: identity\r\n' +\
            'Authorization: Basic %s\r\n' +\
            'Content-length: %d\r\n' +\
            'Content-type: application/x-www-form-urlencoded\r\n' +\
            '\r\n%s') % (urlpath, self._ip, base64.encodestring('%s:%s' % (self._http_username, self._http_password)).strip(), len(postbody), postbody)
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.connect((self._ip, 80))
            sock.sendall(postrequest)

            # Rather than parse the response myself, I create an instance of
            # HTTPResponse. However, begin() is an internal method, and not
            # guaranteed to exist in future versions of the library.
            resp = httplib.HTTPResponse(sock, strict=1, method='POST')
            resp.begin()
            htmlbody = resp.read()

            if resp.status <> 200:
                logging.error('Endpoint %s@%s failed to post configuration - %s' %
                    (self._vendorname, self._ip, r))
                return False
            if not 'Provisioning complete' in htmlbody:
                logging.error('Endpoint %s@%s failed to set configuration server - not provisioned' %
                    (self._vendorname, self._ip))
                return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        return True
