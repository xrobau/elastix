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
import eventlet
from eventlet.green import socket, urllib2, urllib
import cjson
from elastix.BaseEndpoint import BaseEndpoint

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'INCOM', amipool, dbpool, sServerIP, sIP, mac)

    def probeModel(self):
        '''Probe specific model of the INCOM phone

        To probe for the specific model, the index.htm page is fetched. The
        phone model is found as a string in the HTML response.
        '''
        sModel = None
        try:
            response = urllib2.urlopen('http://' + self._ip + ':8080/index.htm')
            htmlbody = response.read()
            if response.code == 200:
                # <title>ICW-1000</title>
                m = re.search(r'<title>(ICW-\w+)</title>', htmlbody, re.IGNORECASE)
                if m != None: sModel = m.group(1)
        except Exception, e:
            pass

        if sModel != None: self._saveModel(sModel)

    def updateLocalConfig(self):
        '''Configuration for INCOM endpoints (local):

        The following procedure was developed for the ICW-1000 wireless phone.

        All interaction must be done on unencrypted HTTP protocol on port 8080.
        The phone interface requires an administrator login on /s_login.htm . The
        POST is unusual in that it apparently needs the question mark that would
        normally separate a GET request from its parameters. However, tests show
        that a GET works just as well for login. The session is IP-based with
        a timeout. 200 OK for success, 500 for error.

        The query /xpdb.uds?mode=list&db=config lists the entire catalog of
        known configuration variables, as a JSON structure. This structure
        consists of an object with a "section" member and an "entry" member. The
        "section" member is an array of objects that describe one section each,
        with a "name" member and a "sid" member. The "sid" value for a given
        section must be picked for a later POST.

        The top-level "entry" member is an array of objects that describe one
        scalar configuration variable each. Each variable has a "sid" number,
        an "eid" number and a "name" description. The "eid" value for a given
        variable must be picked for a later POST.

        To change a set of variables, a POST to /xpdb.uds must be done with
        the following variables: ?mode=change&db=config&... Each variable to
        update must set sidN=###&eidN=###&valN=### for each N counting from
        0. Additionally, a "cnt" variable must be set at the end with the number
        of modified variables. If successful, 200 OK and a listing of updated
        variables.

        USER ACCOUNT/Displayname
        USER ACCOUNT/Phone Number
        USER ACCOUNT/User ID
        USER ACCOUNT/User Password
        USER ACCOUNT/URL Scheme

        SERVER SETTINGS/1st Proxy
        SERVER SETTINGS/Domain Realm

        To reboot the phone, it is necessary to POST to /s_reboot.htm while
        authenticated.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        try:
            # Login into interface
            response = urllib2.urlopen('http://' + self._ip + ':8080/s_login.htm',
                urllib.urlencode({'id' : self._http_username, 'password' : self._http_password}))
            body = response.read()

            # The body is JSON but Content-Type is set to text/plain
            response = urllib2.urlopen('http://' + self._ip + ':8080/xpdb.uds?mode=list&db=config')
            body = response.read()
            jsonvars = cjson.decode(body)

            # Use the JSON map to find out values for sid and eid
            varmap = {
                'USER ACCOUNT': {
                    'Displayname'   : None,
                    'Phone Number'  : None,
                    'User ID'       : None,
                    'User Password' : None,
                    'URL Scheme'    : None,
                },
                'SERVER SETTINGS': {
                    '1st Proxy'     : None,
                    'Domain Realm'  : None,
                },
            }

            # The ICW-1000 has up to 12 network configurations, labeled NETWORK1 to
            # NETWORK12. The one that should be configured is the one that contains
            # the current IP being used to access the device.
            for i in range(1,13):
                k_sid = 'NETWORK' + str(i)
                varmap[k_sid] = {
                    'Enable DHCP'   : None,
                    'Address'       : None,
                    'Netmask'       : None,
                    'Gateway'       : None,
                    'DNS1'          : None,
                    'DNS2'          : None,
                }
            for k_sid in varmap:
                v_sid = self._sectionFromName(jsonvars, k_sid)
                if v_sid == None:
                    logging.error('Endpoint %s@%s received invalid JSON - cannot locate sid for %s' %
                        (self._vendorname, self._ip, k_sid))
                    return False
                for k_entry in varmap[k_sid]:
                    v_entry = self._entryFromName(jsonvars, v_sid, k_entry)
                    if v_entry == None:
                        logging.error('Endpoint %s@%s received invalid JSON - cannot locate eid for %s' %
                            (self._vendorname, self._ip, k_entry))
                        return False
                    varmap[k_sid][k_entry] = v_entry
            current_network = None
            for i in range(1,13):
                k_sid = 'NETWORK' + str(i)
                #logging.info('Endpoint %s@%s - %s Address %s looking for %s' %
                #    (self._vendorname, self._ip, k_sid, varmap[k_sid]['Address'], self._ip))
                if varmap[k_sid]['Address']['value'] == self._ip:
                    current_network = k_sid
                    break
            if current_network == None:
                logging.warning('Endpoint %s@%s - cannot locate network for %s, cannot update network settings' %
                    (self._vendorname, self._ip, self._ip))
                return False

            vars = self._prepareVarList()
            postvars = {'mode': 'change', 'db': 'config'}
            postvars.update(self._createPOSTVar(varmap, 0, 'SERVER SETTINGS', '1st Proxy', vars['server_ip']))
            postvars.update(self._createPOSTVar(varmap, 1, 'SERVER SETTINGS', 'Domain Realm', vars['server_ip']))
            postvars.update(self._createPOSTVar(varmap, 2, 'USER ACCOUNT', 'Displayname', vars['sip'][0].description))
            postvars.update(self._createPOSTVar(varmap, 3, 'USER ACCOUNT', 'Phone Number', vars['sip'][0].extension))
            postvars.update(self._createPOSTVar(varmap, 4, 'USER ACCOUNT', 'User ID', vars['sip'][0].account))
            postvars.update(self._createPOSTVar(varmap, 5, 'USER ACCOUNT', 'User Password', vars['sip'][0].secret))
            postvars.update(self._createPOSTVar(varmap, 6, 'USER ACCOUNT', 'URL Scheme', 'SIP'))
            postvar_count = 7
            postvars.update({'cnt': postvar_count})

            # Send updated variables
            response = urllib2.urlopen('http://' + self._ip + ':8080/xpdb.uds',
                urllib.urlencode(postvars))
            body = response.read()

            # Apparently the ICW-1000 does not support setting the phone account
            # and the network settings in a single request
            if current_network != None:
                postvars = {'mode': 'change', 'db': 'config'}
                postvars.update(self._createPOSTVar(varmap, 0, current_network, 'Enable DHCP', vars['enable_dhcp']))
                postvar_count = 1
                if not self._dhcp:
                    postvars.update(self._createPOSTVar(varmap, 1, current_network, 'Address', vars['static_ip']))
                    postvars.update(self._createPOSTVar(varmap, 2, current_network, 'Netmask', vars['static_mask']))
                    postvars.update(self._createPOSTVar(varmap, 3, current_network, 'Gateway', vars['static_gateway']))
                    postvars.update(self._createPOSTVar(varmap, 4, current_network, 'DNS1', vars['static_dns1']))
                    postvars.update(self._createPOSTVar(varmap, 5, current_network, 'DNS1', vars['static_dns2']))
                    postvar_count += 5
                postvars.update({'cnt': postvar_count})
                # Send updated variables
                response = urllib2.urlopen('http://' + self._ip + ':8080/xpdb.uds',
                    urllib.urlencode(postvars))
                body = response.read()

            # Reiniciar el teléfono
            response = urllib2.urlopen('http://' + self._ip + ':8080/s_reboot.htm', '')
            body = response.read()

            self._unregister()
            self._setConfigured()
            return True
        except cjson.DecodeError, e:
            logging.error('Endpoint %s@%s received invalid JSON - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s failed to send vars to interface - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

    def _sectionFromName(self, jsonvars, name):
        for section in jsonvars['section']:
            if section['name'] == name:
                return section['sid']
        return None

    def _entryFromName(self, jsonvars, sid, name):
        for entry in jsonvars['entry']:
            if entry['sid'] == sid and entry['name'] == name:
                return entry
        return None

    def _createPOSTVar(self, varmap, index, k_sid, k_eid, val):
        e = varmap[k_sid][k_eid]
        return {
            'sid' + str(index): e['sid'],
            'eid' + str(index): e['eid'],
            'val' + str(index): val,
        }
