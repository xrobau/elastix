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
  $Id: paloSantoCampaignCC.class.php,v 1.1 2007/08/09 00:53:17 avivar Exp $ */

include_once("libs/paloSantoDB.class.php");

/* Clase que implementa campaña (saliente por ahora) de CallCenter (CC) */
class paloSantoCampaignCC
{
    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloSantoCampaignCC(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }
    
    /**
     * Procedimiento para obtener el listado de los campañas existentes. Si
     * se especifica id, el listado contendrá únicamente la campaña
     * indicada por el valor. De otro modo, se listarán todas las campañas.
     *
     * @param int   $id_campaign    Si != NULL, indica el id de la campaña a recoger
     *
     * @return array    Listado de campañas en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      //array(id,nombre,fecha_ini,hora_ini,prompt,llamadas_prog,llamadas_real,reintentos,llamadas_pend,detalles),
     *		array(id, name, start_time, retries, b_status, trunk),
     *      ...
     *  )
     */
    function getCampaigns($id_campaign = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_campaign) && !ereg('^[[:digit:]]+$', "$id_campaign")) {
            $this->errMsg = "Campaign ID is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, trunk, context, queue, datetime_init, datetime_end, daytime_init, daytime_end, script FROM campaign".
                (is_null($id_campaign) ? '' : " WHERE id = $id_campaign");
            $sPeticionSQL .=" ORDER BY datetime_init, daytime_init";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear una nueva campaña, vacía e inactiva. Esta campaña 
     * debe luego llenarse con números de teléfono en sucesivas operaciones.
     *
     * @param   $sNombre            Nombre de la campaña
     * @param   $iMaxCanales        Número máximo de canales a usar simultáneamente por campaña
     * @param   $iRetries           Número de reintentos de la campaña, por omisión 5
     * @param   $sTrunk             troncal por donde se van a realizar las llamadas (p.ej. "DAHDI/g0")
     * @param   $sContext           Contexto asociado a la campaña (p.ej. 'from-internal')
     * @param   $sQueue             Número que identifica a la cola a conectar la campaña saliente (p.ej. '402')
     * @param   $sFechaInicio       Fecha YYYY-MM-DD en que inicia la campaña
     * @param   $sFechaFinal        Fecha YYYY-MM-DD en que finaliza la campaña
     * @param   $sHoraInicio        Hora del día (HH:MM:SS militar) en que se puede iniciar llamadas
     * @param   $sHoraFinal         Hora del día (HH:MM:SS militar) en que se debe dejar de hacer llamadas
     * 
     * @return  int    El ID de la campaña recién creada, o NULL en caso de error
     */
    function createEmptyCampaign($sNombre, $iMaxCanales, $iRetries, $sTrunk, $sContext, $sQueue, 
        $sFechaInicio, $sFechaFinal, $sHoraInicio, $sHoraFinal, $script)
    {
        $id_campaign = NULL;
        $bExito = FALSE;
        
        $sNombre = trim("$sNombre");
        if ($sNombre == '') {
            $this->errMsg = 'Nombre de campaña no puede estar vacío';
      //  } elseif (!ereg('^[[:digit:]]+$', $iStartTimestamp)) {
        //    $this->errMsg = 'Momento de inicio de campaña está mal formado';
        } elseif ($sTrunk == '') {
            $this->errMsg = 'Troncal no puede estar vacío';
        } elseif ($sContext == '') {
            $this->errMsg = 'Contexto no puede estar vacío';
        } elseif (!ereg('^[[:digit:]]+$', $iRetries)) {
            $this->errMsg = 'Número de reintentos debe de ser numérico y entero';
        } elseif ($sQueue == '') {
            $this->errMsg = 'Número de cola no puede estar vacío';
        } elseif (!ereg('^[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}$', $sFechaInicio)) {
            $this->errMsg = 'Fecha de inicio no es válida (se espera yyyy-mm-dd)';
        } elseif (!ereg('^[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}$', $sFechaFinal)) {
            $this->errMsg = 'Fecha de final no es válida (se espera yyyy-mm-dd)';
        } elseif ($sFechaInicial >= $sFechaFinal) {
            $this->errMsg = 'Fecha de inicio debe ser anterior a la fecha final';
        } elseif (!ereg('^[[:digit:]]{2}:[[:digit:]]{2}(:[[:digit:]]{2})?$', $sHoraInicio)) {
            $this->errMsg = 'Hora de inicio no es válida (se espera hh:mm:ss)';
        } elseif (!ereg('^[[:digit:]]{2}:[[:digit:]]{2}(:[[:digit:]]{2})?$', $sHoraFinal)) {
            $this->errMsg = 'Hora de final no es válida (se espera hh:mm:ss)';
        } else {
            // Inicia transacción
            if(!$this->_DB->genQuery("BEGIN TRANSACTION")) {
            	$this->errMsg = $this->_DB->errMsg;
            } else {
                // Verificar que el nombre de la campaña es único
                $recordset =& $this->_DB->fetchTable("SELECT * FROM campaign WHERE name = ".paloDB::DBCAMPO($sNombre));
                if (is_array($recordset) && count($recordset) > 0) {
                    // Ya existe una campaña duplicada
                    $this->errMsg = 'Nombre de campaña indicado ya está en uso';
                } else {
                    // Construir y ejecutar la orden de inserción SQL
                    $sPeticionSQL = paloDB::construirInsert(
                        "campaign",
                        array(
                            "name"       	=>  paloDB::DBCAMPO($sNombre),
                            "max_canales"   =>  paloDB::DBCAMPO($iMaxCanales),
                            "retries"       =>  paloDB::DBCAMPO($iRetries),
                         //   "b_status"		=>  1,
                            "trunk"       =>  paloDB::DBCAMPO($sTrunk),
                            "context"     =>  paloDB::DBCAMPO($sContext),
                            "queue"       =>  paloDB::DBCAMPO($sQueue),


                            "datetime_init" =>  paloDB::DBCAMPO($sFechaInicio),
                            "datetime_end"       =>  paloDB::DBCAMPO($sFechaFinal),
                            "daytime_init"       =>  paloDB::DBCAMPO($sHoraInicio),
                            "daytime_end"       =>  paloDB::DBCAMPO($sHoraFinal),
                            "script"       =>  paloDB::DBCAMPO($script),
                        )
                    );

    	            $result = $this->_DB->genQuery($sPeticionSQL);
    	            if ($result) {
                        // Leer el ID insertado por la operación
                        $sPeticionSQL = 'SELECT MAX(id) FROM campaign WHERE name = '.paloDB::DBCAMPO($sNombre);
                        $tupla =& $this->_DB->getFirstRowQuery($sPeticionSQL);
                		if (!is_array($tupla)) {
                			$this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
                		} else {
                			$id_campaign = (int)$tupla[0];
        	                $bExito = TRUE;
                		}
    	            } else {
    	                $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
    	            }                    
                }
                
                if ($bExito) {
	            	$this->_DB->genQuery("COMMIT");
	            } else{
	            	$this->_DB->genQuery("ROLLBACK");
                }
            }
        }
        return $id_campaign;
    }

	/**
	 * Procedimiento para contar el número de teléfonos asignados a ser marcados
	 * en la campaña indicada por $idCampaign.
	 *
     * @param	int		$idCampaign	ID de la campaña a leer
     *
     * @return	mixed	NULL en caso de error o número de teléfonos total
	 */
    function countCampaignNumbers($idCampaign)
    {
    	$iNumTelefonos = NULL;
    	
    	if (!ereg('^[[:digit:]]+$', $idCampaign)) {
    		$this->errMsg = 'ID de campaña no es numérico';
    	} else {
    		$sPeticionSQL = "SELECT COUNT(*) FROM calls WHERE id_campaign = $idCampaign";
    		$tupla =& $this->_DB->getFirstRowQuery($sPeticionSQL);
    		if (!is_array($tupla)) {
    			$this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
    		} else {
    			$iNumTelefonos = (int)$tupla[0];
    		}
    	}
    	return $iNumTelefonos;
    }
    
    /**
     * Procedimiento para agregar los números de teléfono indicados por la
     * ruta de archivo indicada a la campaña. No se hace intento alguno por
     * eliminar números existentes de la campaña (véase clearCampaignNumbers()), ni
     * tampoco para verificar si los números existentes se encuentran en el
     * listado nuevo definido.
     *
     * Esta función está construida en base a parseCampaignNumbers() y 
     * addCampaignNumbers()
     *
     * @param	int		$idCampaign	ID de la campaña a modificar
     * @param	string	$sFilePath	Archivo local a leer para los números
     *
     * @return bool		VERDADERO si éxito, FALSO si ocurre un error
     */
    function addCampaignNumbersFromFile($idCampaign, $sFilePath)
    {
    	$bExito = FALSE;
    	
    	$listaNumeros = $this->parseCampaignNumbers($sFilePath);
    	if (is_array($listaNumeros)) {
    		$bExito = $this->addCampaignNumbers($idCampaign, $listaNumeros);
    	}
    	return $bExito;
    }
    
    /**
     * Procedimiento que carga un archivo CSV con números y parámetros en memoria
     * y devuelve la matriz de datos obtenida. El formato del archivo es CSV, 
     * con campos separados por comas. La primera columna contiene el número
     * telefónico, el cual consiste de cualquier cadena numérica. El resto de
     * columnas contienen parámetros que se agregan como campos adicionales. Las
     * líneas vacías se ignoran, al igual que las líneas que empiecen con #
     *
     * @param	string	$sFilePath	Archivo local a leer para la lista
     *
     * @return	mixed	Matriz cuyas tuplas contienen los contenidos del archivo,
     *					en el orden en que fueron leídos, o NULL en caso de error.
     */
    function parseCampaignNumbers($sFilePath)
    {
    	$listaNumeros = NULL;
    	
    	$hArchivo = fopen($sFilePath, 'rt');
    	if (!$hArchivo) {
    		$this->errMsg = 'No se puede abrir archivo especificado para leer CSV';
    	} else {
    		$iNumLinea = 0;
    		$listaNumeros = array();
    		$clavesColumnas = array();
    		while ($tupla = fgetcsv($hArchivo, 2048)) {
    			$iNumLinea++;
    			if (count($tupla) == 1 && trim($tupla[0]) == '') {
    				// Línea vacía
    			} elseif ($tupla[0]{0} == '#') {
    				// Línea que empieza por numeral
    			} elseif (!ereg('^[[:digit:]#*]+$', $tupla[0])) {
                    if ($iNumLinea == 1) {
                        // Podría ser una cabecera de nombres de columnas
                        array_shift($tupla);
                        $clavesColumnas = $tupla;
                    } else {
    					// Teléfono no es numérico
	    				$this->errMsg = "Línea $iNumLinea: teléfono no es una cadena numérica";
	    				break;
					}
    			} else {
                    // Como efecto colateral, $tupla pierde su primer elemento
    				$tuplaLista = array('__PHONE_NUMBER' => array_shift($tupla));

                    // Asignar atributos de la tupla
    				for ($i = 0; $i < count($tupla); $i++) {
                        // Si alguna fila tiene más elementos que la lista inicial de nombres, el resto de columnas tiene números
    				    $sClave = "$i";
    				    if ($i < count($clavesColumnas) && $clavesColumnas[$i] != '') $sClave = $clavesColumnas[$i];    				    
    				    $tuplaLista[$sClave] = $tupla[$i];
    				}
  					$listaNumeros[] = $tuplaLista;
    			}
    		}
    		fclose($hArchivo);
    	}
    	return $listaNumeros;
    }
    
    /**
     * Procedimiento que agrega números a una campaña existente. La lista de
     * números consiste en un arreglo de tuplas, cuyo elemento __PHONE_NUMBER
     * es el número de teléfono, y el resto de claves es el conjunto clave->valor
     * a guardar en la tabla call_attribute para cada llamada
     *
     * @param int $idCampaign   ID de Campaña
     * @param array $listaNumeros   Lista de números como se describe arriba
     *      array('__PHONE_NUMBER' => '1234567', 'Name' => 'Fulano de Tal', 'Address' => 'La Conchinchina')
     *
     * @return bool VERDADERO si todos los números fueron insertados, FALSO en error
     */
    function addCampaignNumbers($idCampaign, $listaNumeros)
    {
    	$bExito = FALSE;
    	
    	if (!ereg('^[[:digit:]]+$', $idCampaign)) {
    		$this->errMsg = 'ID de campaña no es numérico';
    	} elseif (!is_array($listaNumeros)) {
    		$this->errMsg = 'Lista de números tiene que ser un arreglo';
    	} else {
        	$bContinuar = TRUE;
        	$listaValidada = array(); // Se usa copia porque tupla se modifica en validación
        	
        	// Verificar si todos los elementos son de max. 4 parametros y son
        	// todos numéricos o NULL
        	if ($bContinuar) {
        		foreach ($listaNumeros as $tuplaNumero) {
/*
        			if (count($tuplaNumero) < 1) {
        				$this->errMsg = "Encontrado elemento sin número telefónico";
        				$bContinuar = FALSE;
        			} elseif (!ereg('^[[:digit:]]+$', $tuplaNumero[0])) {
        				$this->errMsg = "Teléfono encontrado que no es numerico";
        				$bContinuar = FALSE;
        			} elseif (count($tuplaNumero) > 1 + 4) {
						$this->errMsg = "Para teléfono $tuplaNumero[0]: implementación actual soporta máximo 4 parámetros";
						break;
        			} else {
        				$iCount = count($tuplaNumero) - 1;
        				for ($i = 1; $i <= $iCount; $i++) {
        					if (trim($tuplaNumero[$i]) == '') $tuplaNumero[$i] = NULL;
        					if (!is_null($tuplaNumero[$i]) && !is_numeric($tuplaNumero[$i])) {
        						$this->errMsg = "Para teléfono $tuplaNumero[0] se encontró parámetro $i = $tuplaNumero[$i] no numérico";
        						$bContinuar = FALSE;
        					}
        				}
        				if ($bContinuar) $listaValidada[] = $tuplaNumero;
        			}
*/
                    if (!isset($tuplaNumero['__PHONE_NUMBER'])) {
        				$this->errMsg = "Encontrado elemento sin número telefónico";
        				$bContinuar = FALSE;
                    } elseif (!ereg('^[[:digit:]#*]+$', $tuplaNumero['__PHONE_NUMBER'])) {
        				$this->errMsg = "Teléfono encontrado que no es numerico";
        				$bContinuar = FALSE;
                    } else {
        				if ($bContinuar) $listaValidada[] = $tuplaNumero;
                    }
        			if (!$bContinuar) break;
                        			
        		}
        	}
        	
        	if ($bContinuar) {
                // Inicia transacción
                if(!$this->_DB->genQuery("BEGIN TRANSACTION")) {
                	$this->errMsg = $this->_DB->errMsg;
                } else {
					foreach ($listaValidada as $tuplaNumero) {
						$campos = array(
							'id_campaign'	=>	$idCampaign,
							'phone'			=>	paloDB::DBCAMPO($tuplaNumero['__PHONE_NUMBER']),
							'status'		=>	NULL,
						);
                        $sPeticionSQL = paloDB::construirInsert("calls", $campos);
						$result = $this->_DB->genQuery($sPeticionSQL);
						if (!$result) {
							$bContinuar = FALSE;
							$this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
							break;
						}
    
    			        $id_call = NULL;

                        // TODO: investigar equivalente de LAST_INSERT_ID() en SQLite
                		$sPeticionSQL = "SELECT MAX(id) FROM calls WHERE id_campaign = $idCampaign and phone = '$tuplaNumero[__PHONE_NUMBER]' and status IS NULL";
                		$tupla =& $this->_DB->getFirstRowQuery($sPeticionSQL);
                		if (!is_array($tupla)) {
                			$this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
                			$bContinuar = FALSE;
                		} else {
                			$id_call = (int)$tupla[0];
                		}
						        
						
						if ($bContinuar) foreach ($tuplaNumero as $sClave => $sValor) {
						    if ($sClave !== '__PHONE_NUMBER') {
						        $campos = array(
						            'id_call'   =>  $id_call,
						            'key'       =>  paloDB::DBCAMPO($sClave),
						            'value'     =>  paloDB::DBCAMPO($sValor),
						        );
                                $sPeticionSQL = paloDB::construirInsert("call_attribute", $campos);
        						$result = $this->_DB->genQuery($sPeticionSQL);
        						if (!$result) {
        							$bContinuar = FALSE;
        							$this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
        							break;
        						}
						    }
						}
						
						if (!$bContinuar) break;
					}

                    $bExito = $bContinuar;
                    if ($bExito) {
    	            	$this->_DB->genQuery("COMMIT");
    	            } else{
    	            	$this->_DB->genQuery("ROLLBACK");
                    }
                }        		
        	}
    	}
    	
    	return $bExito;
    }

};

?>
