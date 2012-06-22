#!/usr/bin/php
<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
  | http://www.elastix.org                                               |
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
  $Id: checkSpamFolder.php,v 1.1 2011-05-01 05:09:57 Eduardo Cueva <ecueva@palosanto.com> Exp $ */

// script para crear carpetas spam y suscribirlas
    $module_name  = "antispam";
    $module_name2 = "vacations";
    include_once "/var/www/html/libs/misc.lib.php";
    include_once "/var/www/html/libs/paloSantoDB.class.php";
    include_once "/var/www/html/configs/email.conf.php";
    include_once "/var/www/html/libs/cyradm.php";
    include_once "/var/www/html/modules/$module_name/libs/paloSantoAntispam.class.php";
    include_once "/var/www/html/modules/$module_name2/libs/paloSantoVacations.class.php";

    $pDB = new paloDB("sqlite3:////var/www/db/email.db");
    $objAntispam = new paloSantoAntispam("", "", "", "");
    $pVacations  = new paloSantoVacations($pDB);
    // primero se verifica si alguna cuenta no tiene la carpeta Spam creada
    $accounts = $objAntispam->listEmailSpam($pDB);
    $status = "";
    $SIEVE = array();
    $SIEVE['HOST'] = "localhost";
    $SIEVE['PORT'] = 4190;
    $SIEVE['USER'] = "";
    $SIEVE['PASS'] = obtenerClaveCyrusAdmin("/var/www/html/");
    $SIEVE['AUTHTYPE'] = "PLAIN";
    $SIEVE['AUTHUSER'] = "cyrus";
    $SIEVE['AUTHPASS'] = obtenerClaveCyrusAdmin("/var/www/html/");

    exec("/etc/init.d/spamassassin status", $flag, $status);

    if($status == 0){
        $content = $objAntispam->getContentScript();
        $fileScript = "/tmp/scriptTest.sieve";
	$fileScriptVaca = "/tmp/vacations.sieve";
        $fp = fopen($fileScript,'w');
        fwrite($fp,$content);
        fclose($fp);

        // si el arreglo es vacio no hace nada
        if(isset($accounts) & $accounts!=""){// existe alguna cuenta sin esa carpeta
            foreach($accounts as $key => $value){
                $status .= $objAntispam->creacionSpamFolder($value);
            }
        }
        // verificando usuarios que no tengan activados el script del sieve
        $emails = $objAntispam->getEmailList($pDB);
        if(isset($emails) & $emails!=""){// si no existe ninguna cuenta
            foreach($emails as $key2 => $value2){
                $SIEVE['USER']     = $value2["username"];
		$scripts = $objAntispam->existScriptSieve($SIEVE['USER'], "scriptTest.sieve");
		$spamCapture = true;
		if($scripts['actived'] != ""){
		    if(preg_match("/vacations.sieve/",$scripts['actived']) && !$scripts['status']){
			$messageVacations = $pVacations->getMessageVacationByUser($SIEVE['USER']);
			if(is_array($messageVacations) && count($messageVacations) > 0){
			    $spamCapture = false;
			    $subject = $messageVacations['subject'];
			    $body = $messageVacations['body'];
			    $end_date = $messageVacations['end_date'];
			    $body = str_replace("{END_DATE}", $end_date, $body);
			    $contentVacations = $pVacations->getVacationScript($subject, $body);
			    $contentSpamFilter = str_replace("require \"fileinto\";", "require [\"fileinto\",\"vacation\"];", $content);
			    $new_content = $contentSpamFilter."\n".$contentVacations;
			    $fp = fopen($fileScriptVaca,'w');
			    fwrite($fp,$new_content);
			    fclose($fp);
			    exec("echo ".$SIEVE['AUTHPASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'put $fileScriptVaca'",$flags, $status);
			    exec("echo ".$SIEVE['AUTHPASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'activate vacations.sieve'",$flags, $status);
			    // se sube el script de captura de spam porque para la eliminacion del vacation es necesario de que exista
			    exec("echo ".$SIEVE['AUTHPASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." localhost:4190 -e 'put $fileScript'",$flags, $status);
			}
		    }
		}
		if($spamCapture && !$scripts['status']){
		    exec("echo ".$SIEVE['AUTHPASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'put $fileScript'",$flags, $status);
		    exec("echo ".$SIEVE['AUTHPASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'activate scriptTest.sieve'");
		}
            }
        }
	if(is_file($fileScript))
	    unlink($fileScript);
	if(is_file($fileScriptVaca))
	    unlink($fileScriptVaca);
    }else{
        echo "ERROR: ".$flag[0]."\n";
    }

?>