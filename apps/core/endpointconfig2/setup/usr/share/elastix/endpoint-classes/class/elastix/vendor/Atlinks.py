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
from eventlet.green import urllib2, httplib, urllib

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Atlinks', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = True
        
        # Time Zone, hour offset from GMT (assumed from similarity with Yealink)
        self._timeZone = '%g' % (BaseEndpoint.getTimezoneOffset() / 3600.0)
        self._language = 'Spanish'

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'bridge' in param: self._bridge = param['bridge']
        if 'timezone' in param: self._timeZone = param['timezone']
        if 'language' in param: self._language = param['language']
        return True

    def probeModel(self):
        '''Probe specific model of Atlinks phone
        
        The Atlinks web admin interface uses Basic authentication for access 
        control. The authentication realm exposes the phone model like this:
        
        HTTP/1.1 401 Unauthorized
        Server: mini_httpd/1.19 19dec2003
        Date: Fri, 01 Feb 2013 21:00:31 GMT
        Cache-Control: no-cache,no-store
        WWW-Authenticate: Basic realm="Atlinks Temporis IP800"
        Content-Type: text/html; charset=%s
        Connection: close
        
        '''
        sModel = None
        try:
            # Do not expect this to succeed. Only interested in exception.
            urllib2.urlopen('http://' + self._ip + '/')
        except urllib2.HTTPError, e:
            if e.code == 401 and 'WWW-Authenticate' in e.headers:
                m = re.search(r'realm="Atlinks (.+)"', e.headers['WWW-Authenticate'])
                if m != None: sModel = m.group(1)
        except Exception, e:
            pass
        
        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for Atlinks endpoints (local)
        
        The file XXXXXXXXXXXX.cfg contains the plaintext SIP configuration. Here
        XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
        
        To reboot the phone, it is necessary to issue the AMI command:
        sip notify reboot-yealink {$IP}. Verified with Atlinks Temporis IP800.
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
            'enable_bridge'     :   int(self._bridge),
            'time_zone'         :   self._timeZone,
            'language'          :   self._language,
        })
        try:
            self._writeTemplate('Atlinks_local_cfg.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Attempt to force provisioning to STATIC into this server. This allows
        # the system to point the phone at us even if we are not a master DHCP.
        # Otherwise the DHCP server might need to publish option 160.
        if not self._setProvisioningServer_Atlinks():
            return False
        
        # Reboot the phone.
        self._amireboot('reboot-yealink')
        self._unregister()
        self._setConfigured()
        return True

    def _setProvisioningServer_Atlinks(self):
        separator = 'þ'
        provvars = ('1','tftp://'+ self._serverip,'','********','','********',
                    '1','','00:00','00:00','','********','1','1','1','3')

        # The Atlinks (Yealink-derived) firmware is very picky about the order 
        # of the POST variables. The PAGEID variable must appear *before* 
        # CONFIG_DATA. Therefore, urllib.urlencode() cannot be used as-is, 
        # because it places variables in alphabetical sort.
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
    
