<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-14                                               |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: default.conf.php,v 1.1 2012-01-17 11:01:49 Manuel Olvera molvera@palosanto.com Exp $ */
    global $arrConf;
    global $arrConfModule;

    $arrConfModule['module_name']       = 'setup_monitor';
    $arrConfModule['templates_dir']     = 'themes';
    $arrConfModule['collectd_conf_path'] = '/etc/collectd.conf';
    //$arrConfModule['collectd_conf_path'] = '/tmp/collectd.conf';
    $arrConfModule['cloud_server'] = array(
        'PROTOCOL'  => 'https',
        'IP'        => '107.21.106.155',
        'SCRIPT'    => 'mon.php',
    );
    $arrConfModule['elastix_helper'] = '/usr/bin/elastix-helper';
    $arrConfModule['elastix_key_server'] = '/etc/elastix.key';
    $arrConfModule['key_server_db'] = 'setup_monitor_tmp_key_server';

    $arrConfModule['Plugins'] = array(
        'cpu' => array(
        ),'disk' => array(
        ),'memory'=> array(
        ),'network' => array(
        ),'write_http'=> array(
        ),
    )
?>