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
import re
import eventlet
from eventlet.green import urllib2

class Endpoint(elastix.vendor.Grandstream.Endpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Hanlong', amipool, dbpool, sServerIP, sIP, mac)
        self._timeZone = 'auto'
        self._language = 'es'

    def probeModel(self):
        '''Probe specific model of the Hanlong phone

        To probe for the specific model, a http session is tried. After
        authentication, the status page reveals the phone model.
        '''
        self._loadCustomCredentials()
        if self._http_username == None: self._http_username = 'admin'
        if self._http_password == None: self._http_password = 'admin'

        sModel = None
        # Try detecting Hanlong with updated firmware
        try:
            password_manager = urllib2.HTTPPasswordMgrWithDefaultRealm()
            password_manager.add_password(None, 'http://' + self._ip + '/',
                self._http_username, self._http_password)
            basic_auth_handler = urllib2.HTTPBasicAuthHandler(password_manager)
            opener = urllib2.build_opener(basic_auth_handler)
            response = opener.open('http://' + self._ip + '/')
            htmlbody = response.read()
            #  <TR>
            #  <td width="220"><script> document.write(jscs.product_type);</script></td>
            #  <td width="250">UC862</td>
            #  <TR>
            m = m = re.search(r'product_type\);</script></TD>.*?<TD.*?>(\w+)', htmlbody, re.IGNORECASE | re.DOTALL)
            if m != None:
                sModel = m.group(1)
        except Exception, e:
            pass

        if sModel != None: self._saveModel(sModel)


    def _enableStaticProvisioning(self, vars):

        vars.update({'Update' : 'SaveSet'})
        return self._doAuthPost('/save_management.htm_auto_provision.htm', vars)

    def _updateVarsByModel(self, stdvars, vars):
        if self._model in ('UC862',):
            vars.update({
                # Blank out the standard Grandstream phonesrv because the firmware
                # space for it is too short for the full hash. Use P4401 instead.
                'P331'  :   '',
                'P4401' :   stdvars['phonesrv'] + '/internal.xml',
                'P3316' :   'Elastix - Internal',
                'P4402' :   stdvars['phonesrv'] + '/external.xml',
                'P3312' :   'Elastix - External',

                # Allow DHCP to override config server (0/1)
                'P145'  :   '0',
            })

    def _grandstreamvarmap(self):
        # The P-value for accountname is not used in Hanlong, but must exist
        # since it is referenced in the parent class
        varmap = [
            {'enable'               :   'P271', # Enable account
             'accountname'          :   'P270',
             'sipserver'            :   'P47',  # SIP Server
             'sipid'                :   'P35',  # SIP User ID
             'authid'               :   'P36',  # Authenticate ID
             'secret'               :   'P34',  # Authenticate password
             'displayname'          :   'P3',   # Display Name (John Doe)
             'outboundproxy'        :   'P48',  # Outbound Proxy
             'autoanswercallinfo'   :   'P298', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P401', # Enable account
             'accountname'          :   'P270',
             'sipserver'            :   'P747', # SIP Server
             'sipid'                :   'P735', # SIP User ID
             'authid'               :   'P736', # Authenticate ID
             'secret'               :   'P734', # Authenticate password
             'displayname'          :   'P703', # Display Name (John Doe)
             'outboundproxy'        :   'P748', # Outbound Proxy
             'autoanswercallinfo'   :   'P438', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P501', # Enable account
             'accountname'          :   'P270',
             'sipserver'            :   'P502', # SIP Server
             'sipid'                :   'P504', # SIP User ID
             'authid'               :   'P505', # Authenticate ID
             'secret'               :   'P506', # Authenticate password
             'displayname'          :   'P507', # Display Name (John Doe)
             'outboundproxy'        :   'P503',  # Outbound Proxy
             'autoanswercallinfo'   :   'P538', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P601', # Enable account
             'accountname'          :   'P270',
             'sipserver'            :   'P602', # SIP Server
             'sipid'                :   'P604', # SIP User ID
             'authid'               :   'P605', # Authenticate ID
             'secret'               :   'P606', # Authenticate password
             'displayname'          :   'P607', # Display Name (John Doe)
             'outboundproxy'        :   'P603', # Outbound Proxy
             'autoanswercallinfo'   :   'P638', # Enable auto-answer by Call-Info
            },
            {'enable'               :   'P1701',# Enable account
             'accountname'          :   'P270',
             'sipserver'            :   'P1702',# SIP Server
             'sipid'                :   'P1704',# SIP User ID
             'authid'               :   'P1705',# Authenticate ID
             'secret'               :   'P1706',# Authenticate password
             'displayname'          :   'P1707',# Display Name (John Doe)
             'outboundproxy'        :   'P1703',# Outbound Proxy
             'autoanswercallinfo'   :   'P1738',# Enable auto-answer by Call-Info
            },
            {'enable'               :   'P1801',# Enable account
             'accountname'          :   'P270',
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

    def _rebootbyhttp(self):
        return self._doAuthGet('/rb_phone.htm')
