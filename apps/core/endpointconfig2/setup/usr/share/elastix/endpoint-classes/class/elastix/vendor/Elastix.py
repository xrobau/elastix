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
import elastix.vendor.Grandstream
from eventlet.green import urllib2, urllib
import re

class Endpoint(elastix.vendor.Grandstream.Endpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Elastix', amipool, dbpool, sServerIP, sIP, mac)
        self._timeZone = 'auto'
        self._language = 'es'

    def probeModel(self):
        ''' Probe specific model of Elastix phone. This method should only be
            entered on non-Grandstream models (LXP-180) '''
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

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        if self._model in ('LXP180'):
            return self._updateLocalConfig_LXP180()
        else:
            # Delegate to old (Grandstream) configuration
            return super(Endpoint, self).updateLocalConfig(self)
    
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
        