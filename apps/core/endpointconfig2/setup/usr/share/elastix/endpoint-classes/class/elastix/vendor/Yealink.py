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
from eventlet.green import socket, urllib2, httplib, urllib

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Yealink', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = True

        # Time Zone, hour offset from GMT
        self._timeZone = '%g' % (BaseEndpoint.getTimezoneOffset() / 3600.0)

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'bridge' in param: self._bridge = param['bridge']
        if 'timezone' in param: self._timeZone = param['timezone']
        return True

    def probeModel(self):
        '''Probe specific model of Yealink phone

        The Yealink web admin interface uses Basic authentication for access
        control. The authentication realm exposes the phone model like this:

        HTTP/1.0 401 Unauthorized
        Server: mini_httpd/1.19 19dec2003
        Date: Wed, 13 Feb 2013 22:54:25 GMT
        Cache-Control: no-cache,no-store
        WWW-Authenticate: Basic realm="Enterprise IP phone SIP-T20P"
        Content-Type: text/html; charset=%s
        Connection: close

        In case there is not enough information from the auth realm, or if the
        url request does not trigger a 401 Unauthorized exception, we attempt
        to recover the configuration file through HTTP. Inside the binary file
        there is a string "UserAgent = (Yealink) $MODEL $FW_VER" that can be
        used to fetch the phone model.

        '''
        sModel = None
        realm = None
        for urlpath in ('/cgi-bin/', '/'):
            try:
                # Attempt to tickle a 401 Unauthorized from the server.
                response = urllib2.urlopen('http://' + self._ip + urlpath)
                htmlbody = response.read()
                urlmatch = re.findall(r'<a href="(.+?)">', htmlbody, re.IGNORECASE)
                for url in urlmatch:
                    response = urllib2.urlopen('http://' + self._ip + '/cgi-bin/' + url)
                    htmlbody = response.read()
            except urllib2.HTTPError, e:
                if e.code == 401 and 'WWW-Authenticate' in e.headers:
                    realm = None
                    m = re.search(r'realm="(.+)"', e.headers['WWW-Authenticate'])
                    if m != None: realm = m.group(1)

                    relist = (r'realm="Enterprise IP phone (.+)"', r'realm="Gigabit Color IP Phone (.+)"')
                    for regexp in relist:
                        m = re.search(regexp, e.headers['WWW-Authenticate'])
                        if m != None:
                            sModel = m.group(1)
                            break
                    if sModel == None:
                        logging.warning('Endpoint %s@%s failed to identify model from WWW-Authenticate: %s' %
                                (self._vendorname, self._ip, e.headers['WWW-Authenticate']))
                elif e.code == 400:
                    # Ignore Bad Request on newer SIP-T38G triggered by /cgi-bin/
                    pass
                elif e.code == 404:
                    # Ignore Not Found on /cgi-bin/ on incompatible Yealink
                    pass
                else:
                    logging.warning('Endpoint %s@%s failed to identify model from WWW-Authenticate: %s' %
                        (self._vendorname, self._ip, str(e)))
            except Exception, e:
                #print str(e)
                pass
            if sModel != None: break

        if sModel == None:
            if realm != None:
                self._loadCustomCredentials()
                if self._http_username == None: self._http_username = 'admin'
                if self._http_password == None: self._http_password = 'admin'

                # The 401 Unauthorized provided unhelpful realm. Try fetching the phone config
                configSources = (
                    (
                        '/cgi-bin/ConfigManApp.com?Id=26',
                    ),
                    (
                        '/cgi-bin/cgiServer.exx?command=msgSendMessage(%22app_vpPhone%22,%220x30007%22,%220%22,%221%22)',
                        '/cgi-bin/cgiServer.exx?command=getDownloadConfig(%221%22)',
                        '/cgi-bin/cgiServer.exx?download=/tmp/config.bin',
                    ),
                )
                for sourceList in configSources:
                    basic_auth_handler = urllib2.HTTPBasicAuthHandler()
                    basic_auth_handler.add_password(
                        realm=realm,
                        uri='http://' + self._ip + '/',
                        user=self._http_username,
                        passwd=self._http_password)
                    opener = urllib2.build_opener(basic_auth_handler)
                    try:
                        for sourceUrl in sourceList:
                            response = opener.open('http://' + self._ip + sourceUrl)
                            htmlbody = response.read()
                            m = re.search(r'UserAgent\s*=\s*(Yealink)?\s*(\S+)', htmlbody)
                            if (m != None): sModel = m.group(2)
                    except urllib2.HTTPError, e:
                        if e.code != 404:
                            logging.warning('Endpoint %s@%s failed to identify model from WWW-Authenticate: %s' %
                                    (self._vendorname, self._ip, str(e)))
                            break
                    except Exception, e:
                        #print str(e)
                        break
                    if sModel != None: break
            else:
                # Failed to tickle 401 unauthorized. Newer firmware with form login
                try:
                    response = urllib2.urlopen('http://' + self._ip + '/servlet?p=login&q=loginForm&jumpto=status')
                    htmlbody = response.read()
                    m = re.search(r'T\("Enterprise IP phone (\S+)"\)', htmlbody, re.IGNORECASE)
                    if (m != None): sModel = m.group(1)
                except urllib2.HTTPError, e:
                    logging.warning('Endpoint %s@%s failed to identify model from form login: %s' %
                            (self._vendorname, self._ip, str(e)))
                except Exception, e:
                    #print str(e)
                    pass

        if sModel != None:
            # Remove trailing 'P' from SIP-T28P and VP530P
            if re.search(r'^SIP-T(\d+)P$', sModel):
                sModel = sModel[0:-1]
            if re.search(r'^VP(\d+)P$', sModel):
                sModel = sModel[0:-1]
            modelmap = {
                'SIP-T20': 'SIP-T20/T20P',
                'SIP-T22': 'SIP-T22/T22P',
                'SIP-T26': 'SIP-T26/T26P',
                'SIP-T28': 'SIP-T28/T28P',
            }
            if sModel in modelmap: sModel = modelmap[sModel]
            self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for Yealink endpoints (local):

        The file XXXXXXXXXXXX.cfg contains the SIP configuration. Here
        XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone. The
        file format is different for the SIP-T2x and the VP530, and the
        difference is accounted for in the templates.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify reboot-yealink {$IP}
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Select template based on phone model
        if self._model in ('VP530', 'SIP-T38G'):
            sTemplate = 'Yealink_local_VP530.tpl'
        else:
            sTemplate = 'Yealink_local_SIP-T2x.tpl'

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').lower()) + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        vars.update({
            'enable_bridge'     :   int(self._bridge),
            'time_zone'         :   self._timeZone,
        })
        try:
            self._writeTemplate(sTemplate, vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Attempt to force provisioning to STATIC into this server. This allows
        # the system to point the phone at us even if we are not a master DHCP.
        # Otherwise the DHCP server might need to publish option 160.
        if self._model in ('VP530', 'SIP-T38G'):
            success = self._setProvisioningServer_VP530()
        else:
            success = self._setProvisioningServer_SIPT2x()
        if not success: return False

        # Reboot the phone.
        self._amireboot('reboot-yealink')
        self._unregister()
        self._setConfigured()
        return True

    def _setProvisioningServer_SIPT2x(self):
        separator = 'þ'
        provvars = ('1','tftp://'+ self._serverip,'','********','','********',
                    '1','','00:00','00:00','','********','1','1','5','3', '', '1')

        # The Yealink firmware is very picky about the order of the POST
        # variables. The PAGEID variable must appear *before* CONFIG_DATA.
        # Therefore, urllib.urlencode() cannot be used as-is, because it
        # places variables in alphabetical sort.
        postvars =  'PAGEID=16&CONFIG_DATA=' + urllib.quote_plus(separator + separator.join(provvars))

        try:
            if not self._doAuthPost('/cgi-bin/ConfigManApp.com', postvars):
                return False
        except httplib.BadStatusLine, e:
            # Apparently a successful POST will start provisioning immediately
            logging.error('Endpoint %s@%s failed to set provisioning server - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        return True

    def _setProvisioningServer_VP530(self):
        syspath = ''
        if self._model in ('VP530'):
            syspath = '/config/system/system.ini'
        elif self._model in ('SIP-T38G'):
            syspath = '/phone/config/system.ini'
        postvars = {
            'command'   :   'regSetString("' + syspath + '","AutoProvision","strServerURL","tftp://' + self._serverip +'")'
        }
        return self._doAuthPost('/cgi-bin/cgiServer.exx', postvars)