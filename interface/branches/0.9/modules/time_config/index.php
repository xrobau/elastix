<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoForm.class.php" ;

 //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    

	
    $smarty->assign("TIME_TITULO",$arrLang["Date and Time Configuration"]);
    $smarty->assign("INDEX_HORA_SERVIDOR",$arrLang["Current Datetime"]);
    $smarty->assign("TIME_NUEVA_FECHA",$arrLang["New Date"]);
    $smarty->assign("TIME_NUEVA_HORA",$arrLang["New Time"]);
    $smarty->assign("TIME_NUEVA_ZONA",$arrLang["New Timezone"]);
    $smarty->assign("INDEX_ACTUALIZAR",$arrLang["Apply changes"]);
    $smarty->assign("TIME_MSG_1", $arrLang["The change of date and time can concern important  system processes."].'  '.$arrLang["Are you sure you wish to continue?"]);

    $arrForm = array(
    );
    $oForm = new paloForm($smarty, $arrForm);

/*
	Para cambiar la zona horaria:
	1)	Abrir y mostrar columna 3 de /usr/share/zoneinfo/zone.tab que muestra todas las zonas horarias.
	2)	Al elegir fila de columna 3, verificar que sea de la forma abc/def y que
		existe el directorio /usr/share/zoneinfo/abc/def . Pueden haber N elementos
		en la elección, separados por / , incluyendo uno solo (sin / alguno)
	3)	Si existe /etc/localtime, borrarlo
	4)	Copiar archivo /usr/share/zoneinfo/abc/def a /etc/localtime
	5)	Si existe /var/spool/postfix/etc/localtime , removerlo y sobreescribr
		con el mismo archivo copiado a /etc/localtime
		
	Luego de esto, ejecutar cambio de hora local

*/
	// Abrir el archivo /usr/share/zoneinfo/zone.tab y cargar la columna 3
	// Se ignoran líneas que inician con #
	$listaZonas = NULL;
	$hArchivo = fopen('/usr/share/zoneinfo/zone.tab', 'r');
	if ($hArchivo) {
		$listaZonas = array();
		while ($tupla = fgetcsv($hArchivo, 2048, "\t")) {
			if (count($tupla) >= 3 && $tupla[0]{0} != '#') $listaZonas[] = $tupla[2];
		}
		fclose($hArchivo);
		sort($listaZonas);
	}
	
	// Cargar de /etc/sysconfig/clock la supuesta zona horaria configurada.
	// El resto de contenido del archivo se preserva, y la clave ZONE se 
	// escribirá como la última línea en caso de actualizar
	$sZonaActual = "America/New_York";
	$infoZona = NULL;
	$hArchivo = fopen('/etc/sysconfig/clock', 'r');
	if ($hArchivo) {
		$infoZona = array();
		while (!feof($hArchivo)) {
			$s = fgets($hArchivo);
			$regs = NULL;
			if (ereg('^ZONE="(.*)"', $s, $regs))
				$sZonaActual = $regs[1];
			else $infoZona[] = $s;
		}
		fclose($hArchivo);
	}


	if (isset($_POST['Actualizar'])) {
//		print '<pre>';print_r($_POST);print '</pre>';

		// Validación básica
		$listaVars = array(
			'ServerDate_Year'	=>	'^[[:digit:]]{4}$',
			'ServerDate_Month'	=>	'^[[:digit:]]{1,2}$',
			'ServerDate_Day'	=>	'^[[:digit:]]{1,2}$',
			'ServerDate_Hour'	=>	'^[[:digit:]]{1,2}$',
			'ServerDate_Minute'	=>	'^[[:digit:]]{1,2}$',
			'ServerDate_Second'	=>	'^[[:digit:]]{1,2}$',
		);
		$bValido = TRUE;
		foreach ($listaVars as $sVar => $sReg) {
			if (!ereg($sReg, $_POST[$sVar])) {
				$bValido = FALSE;
			}
		}
		if ($bValido && !checkdate($_POST['ServerDate_Month'], $_POST['ServerDate_Day'], $_POST['ServerDate_Year'])) $bValido = FALSE;
		
		// Validación de zona horaria nueva
		$sZonaNueva = $_POST['TimeZone'];
		if (!in_array($sZonaNueva, $listaZonas)) $sZonaNueva = $sZonaActual;
		
		if (!$bValido) {
			// TODO: internacionalizar
			$smarty->assign("mb_message", 'Fecha u hora no es válida');
		} else {
			if ($sZonaNueva != $sZonaActual) {
				// Construir la ruta del archivo que hay que copiar a /etc/localtime
				$sRutaArchivoFuente = '/usr/share/zoneinfo/'.$sZonaNueva;
				if (!file_exists($sRutaArchivoFuente)) {
					$smarty->assign('mb_message', "No se puede localizar archivo $sRutaArchivoFuente");
				} else {
					$bExitoEscritura = FALSE;
					$sContenido = NULL;
					$iRetVal = NULL;
					$sOutput = NULL;					
					exec("/usr/bin/sudo -u root chown asterisk /etc/localtime", $sOutput, $iRetVal);
					if ($iRetVal != 0) {
						$smarty->assign('mb_message', "(interno) chown /etc/localtime ha fallado");
					} else {
						$sContenido = file_get_contents($sRutaArchivoFuente);
						if ($sContenido === FALSE) {
							$smarty->assign('mb_message', "(interno) lectura de $sRutaArchivoFuente ha fallado");
						} else {
							$hArchivo = fopen('/etc/localtime', 'w');
							if ($hArchivo) {
								fwrite($hArchivo, $sContenido);
								fclose($hArchivo);
								exec("/usr/bin/sudo -u root chown root.root /etc/localtime", $sOutput, $iRetVal);
								$bExitoEscritura = TRUE;
							} else {
								$smarty->assign('mb_message', "(interno) apertura (w) de /etc/localtime ha fallado");
								$bExitoEscritura = FALSE;
							}
						}
					}
					
					// Escribir /var/spool/postfix/etc/localtime si es necesario
					if ($bExitoEscritura && file_exists('/var/spool/postfix/etc/localtime')) {
						exec("/usr/bin/sudo -u root chown asterisk /var/spool/postfix/etc/localtime", $sOutput, $iRetVal);
    					if ($iRetVal != 0) {
    						$smarty->assign('mb_message', "(interno) chown /var/spool/postfix/etc/localtime ha fallado");
    					} else {
							$hArchivo = fopen('/var/spool/postfix/etc/localtime', 'w');
							if ($hArchivo) {
								fwrite($hArchivo, $sContenido);	// Depende de haber leído previamente en código de arriba!
								fclose($hArchivo);
								exec("/usr/bin/sudo -u root chown root.root /var/spool/postfix/etc/localtime", $sOutput, $iRetVal);
								$bExitoEscritura = TRUE;
							} else {
								$smarty->assign('mb_message', "(interno) apertura (w) de /var/spool/postfix/etc/localtime ha fallado");
								$bExitoEscritura = FALSE;
							}
						}
					}
					
					// Actualizar /etc/sysconfig/clock
					if ($bExitoEscritura) {
						exec("/usr/bin/sudo -u root chown asterisk /etc/sysconfig/clock", $sOutput, $iRetVal);
    					if ($iRetVal != 0) {
    						$smarty->assign('mb_message', "(interno) chown /etc/sysconfig/clock ha fallado");
    					} else {
							$hArchivo = fopen('/etc/sysconfig/clock', 'w');
							if ($hArchivo) {
								foreach ($infoZona as $s) {
									fputs($hArchivo, $s);
								}
								fputs($hArchivo, "ZONE=\"$sZonaNueva\"\n");
								fclose($hArchivo);
								exec("/usr/bin/sudo -u root chown root.root /etc/sysconfig/clock", $sOutput, $iRetVal);
								$sZonaActual = $sZonaNueva;
								$bExitoEscritura = TRUE;
							} else {
								$smarty->assign('mb_message', "(interno) apertura (w) de /etc/sysconfig/clock ha fallado");
								$bExitoEscritura = FALSE;
							}
    					}
					}
				}
			}

			// Para que funcione esto, se requiere agregar a /etc/sudoers lo siguiente:
			// asterisk ALL = NOPASSWD: /bin/date
            $fecha = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
            	$_POST['ServerDate_Year'], $_POST['ServerDate_Month'], $_POST['ServerDate_Day'],
            	$_POST['ServerDate_Hour'], $_POST['ServerDate_Minute'], $_POST['ServerDate_Second']);
            $cmd = "/usr/bin/sudo -u root /bin/date -s '$fecha'";
            $output=$ret_val="";
            exec($cmd,$output,$ret_val);
			
			if ($ret_val == 0) {
				$smarty->assign('mb_message', 'Hora del sistema cambiada correctamente');
			} else {
				$smarty->assign('mb_message', 'No se puede cambiar la hora del sistema - '.$output);
			}
		}
	}
    $sContenido = '';

//    $smarty->assign("COMBO_FECHA_HORA",/*$combo_fecha_hora*/ 'gato' );
    $mes = date("m",time())-1;
    $smarty->assign("CURRENT_DATETIME", strftime("%Y,$mes,%d,%H,%M,%S",time()));
    $smarty->assign("MES_ACTUAL", ucwords(strftime("%B",time())));
    $smarty->assign('LISTA_ZONAS', $listaZonas);
    $smarty->assign('ZONA_ACTUAL', $sZonaActual);

	$sContenido .= $oForm->fetchForm("$local_templates_dir/time.tpl", "{$arrLang['Date and Time Configuration']}", $_POST);
	return $sContenido;
}

?>
