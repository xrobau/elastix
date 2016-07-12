<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0                                                |
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
*/

// 1) comprobamos que existan el archivo /etc/collectd.conf.bkp y /etc/collectd.conf
// si los archivos no existen no podemos continuar

// 2) revisamos si en el archivo /etc/collectd.conf.bkp se encontra onfigurado el serverkey 
//    del servidor. 

// 3) Si se encontro configurado el serverkey procedemos a setearlo en el archivo
//    /etc/collectd.conf y mandamos a iniciar el servicio

// 4) Si no se encontraba configurado no ahi nada que hacer
global $conf_path_file;
global $conf_path_file_bkp;

$conf_path_file='/etc/collectd.conf';
$conf_path_file_bkp='/etc/collectd.conf.bkp';

if(is_file($conf_path_file) && is_file($conf_path_file_bkp)){
    $serverkey=action_get_server_key();
    if(!empty($serverkey)){
        //si el valor configurado es diferente al valor por default del archivo
        if($serverkey!="12345123451234512345"){
            //seteamos el valor en el archivo de configuracion
            $exito=action_set_server_key($serverkey);
            if($exito==false){
                print("File /etc/collectd.conf could not be correctly updated.\n");
                print("Go to module ELX_CLOUD in Elastix Web Interface and start the service from there.\n");
                exit(1);
            }
            if(!action_start()){
                print("Service couldn't be started\n"); 
                print("Go to module ELX_CLOUD in Elastix Web Interface and start the service from there.\n");  
                exit(1);     
            }else
                exit(0);
        }
    }
}else{
    if(!is_file($conf_path_file)){
        print("Missing File /etc/collectd.conf\n");
        exit(1);
    }
}

// Read server key on file configuration. Return string
function action_get_server_key(){
    require_once '/var/www/html/modules/setup_monitor/libs/parseFile.class.php';
    
    global $conf_path_file,$conf_path_file_bkp;

    $opReadFile = parseFile::NO_DEFAULT | parseFile::SECTIONS | parseFile::COLLECT_REPEATED;
    $oFile = new parseFile($conf_path_file_bkp, $opReadFile, array(
        'headers' => array(
            'start' =>'^\s*<(.+\s.+)>\s*$',
            'end'   =>'^#?\s*</.+\s*>\s*$',
        ),
    ));
    //Si encuentra que la clave del servidor elastix no coincide forza la bandera para hacer el cambio en el archivo.
    $k_write_http = '';
    if($oFile->offsetExists('Plugin write_http'))       $k_write_http = 'Plugin write_http';
    elseif($oFile->offsetExists('Plugin "write_http"')) $k_write_http = 'Plugin "write_http"';
    $arr_data = $oFile[$k_write_http];
    if('' != $k_write_http && preg_match('@https?:\/\/(cloud\.elastix\.org|107\.21\.106\.155)/mon\.php\?sk=(.+)"@',key($arr_data),$m))
        return trim($m[2]);
    return '';
}

// Write server key on file configuration. Return TRUE on success
function action_set_server_key($serverkey){
    global $conf_path_file,$conf_path_file_bkp;

    $file = file_get_contents($conf_path_file);
    $newData = preg_replace('@(<URL "http)s?(:\/\/)(cloud\.elastix\.org|107\.21\.106\.155)(/mon\.php\?sk=).*(">)@','${1}s${2}cloud.elastix.org${4}'.$serverkey.'$5',$file);

    if(preg_match('@^x86_64$@',php_uname('m')))
        $newData = preg_replace('@(Exec ".+" "/usr/)lib(/collectd/plugins/elastixcalls.pl")@','${1}lib64${2}',$newData);

    $fp = fopen($conf_path_file, 'w+'); //Overwrite config file
    if ($fp) {
        fwrite($fp,$newData);
        fclose($fp);
        return TRUE;
    }else
        return FALSE;
}
// Starts collect and marks it for autostart. Returns TRUE on success.
function action_start(){
    $output = $ret = NULL;
    if (!file_exists('/var/run/collectdmon.pid')) {
        exec('/sbin/service collectd start', $output, $ret);
        if ($ret != 0) return FALSE;
    }
    exec('/sbin/chkconfig --level 235 collectd on', $output, $ret);
    return ($ret == 0);
}
?>
