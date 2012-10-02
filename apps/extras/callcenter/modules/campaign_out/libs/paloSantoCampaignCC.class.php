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
  $Id: paloSantoCampaignCC.class.php,v 1.2 2008/06/06 07:15:07 cbarcos Exp $ */

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
    function getCampaigns($limit, $offset, $id_campaign = NULL,$estatus='all')
    {
        $where = "";
        if($estatus=='all')
            $where .= " where 1";
        else if($estatus=='A')
            $where .= " where estatus='A'";
        else if($estatus=='I')
            $where .= " where estatus='I'";
        else if($estatus=='T')
            $where .= " where estatus='T'";

        $arr_result = FALSE;
        if (!is_null($id_campaign) && !ereg('^[[:digit:]]+$', "$id_campaign")) {
            $this->errMsg = _tr("Campaign ID is not valid");
        } 
        else {
            if ($where=="") {
                $where = (is_null($id_campaign) ? '' : " WHERE id = $id_campaign");
            } else {
                $where =  $where." ".(is_null($id_campaign) ? '' : " and id = $id_campaign");
            }
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, trunk, context, queue, datetime_init, datetime_end, daytime_init, daytime_end, script, retries, promedio, num_completadas, estatus, max_canales, id_url FROM campaign ".$where;
            $sPeticionSQL .=" ORDER BY datetime_init, daytime_init";
            if (!is_null($limit)) {
                $sPeticionSQL .= " LIMIT $limit OFFSET $offset";
            }

//echo "$sPeticionSQL<br>";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL, true);
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
     * @param   $sTrunk             troncal por donde se van a realizar las llamadas (p.ej. "Zap/g0")
     * @param   $sContext           Contexto asociado a la campaña (p.ej. 'from-internal')
     * @param   $sQueue             Número que identifica a la cola a conectar la campaña saliente (p.ej. '402')
     * @param   $sFechaInicio       Fecha YYYY-MM-DD en que inicia la campaña
     * @param   $sFechaFinal        Fecha YYYY-MM-DD en que finaliza la campaña
     * @param   $sHoraInicio        Hora del día (HH:MM militar) en que se puede iniciar llamadas
     * @param   $sHoraFinal         Hora del día (HH:MM militar) en que se debe dejar de hacer llamadas
     * 
     * @return  int    El ID de la campaña recién creada, o NULL en caso de error
     */
    function createEmptyCampaign($sNombre, $iMaxCanales, $iRetries, $sTrunk, $sContext, $sQueue, 
        $sFechaInicial, $sFechaFinal, $sHoraInicio, $sHoraFinal, $script, $combo,
        $id_url)
    {
        $id_campaign = NULL;
        $bExito = FALSE;
//hacemos el query para ver lasa colas seleccionadas
    global $arrConf;
    $error_cola = 0;
    $pDB = new paloDB($arrConf["cadena_dsn"]);
    $query_call_entry = "SELECT queue FROM queue_call_entry WHERE estatus='A'";
    $arr_call_entry = $pDB->fetchTable($query_call_entry, true);
    $arreglo_colas = array();
    foreach($arr_call_entry as $cola){
        foreach($cola as $row){
                 array_push($arreglo_colas,$row);//llenamos el arreglo de colas que estan en queue_call_entry
        }
    }

//para traer valor de cola elegida en combo Colas
	$sCombo = trim($combo);
    if (is_array($arreglo_colas)){
        foreach($arreglo_colas as $queue) {
            if (in_array($sCombo,$arreglo_colas)){//si la cola de queue_call_entry no esta siendo usada la asignamos al combo
                $error_cola = "1";
            }
        }
    }


        $sNombre = trim($sNombre);
        $iMaxCanales = trim($iMaxCanales);
        $iRetries = trim($iRetries);
        $sTrunk = trim($sTrunk); 
        $sContext = trim($sContext);
        $sQueue = trim($sQueue);
        $sFechaInicial = trim($sFechaInicial);
        $sFechaFinal = trim($sFechaFinal);
        $sHoraInicio = trim($sHoraInicio);
        $sHoraFinal = trim($sHoraFinal);
        $script = trim($script);

        if ($sTrunk == '') $sTrunk = NULL;

        if ($sNombre == '') {
            $this->errMsg = _tr("Name Campaign can't be empty");//'Nombre de campaña no puede estar vacío';
        } elseif ($sContext == '') {
            $this->errMsg = _tr("Context can't be empty");//'Contexto no puede estar vacío';
        } elseif (!ereg('^[[:digit:]]+$', $iRetries)) {
            $this->errMsg = _tr("Retries must be numeric");//'Número de reintentos debe de ser numérico y entero';
        } elseif ($sQueue == '') {
            $this->errMsg = _tr("Queue can't be empty");//'Número de cola no puede estar vacío';
        } elseif (!ereg('^[[:digit:]]+$', $sQueue)) {
            $this->errMsg = _tr("Queue must be numeric");//'Número de cola debe de ser numérico y entero';
        } elseif (!ereg('^[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}$', $sFechaInicial)) {
            $this->errMsg = _tr("Invalid Start Date");//'Fecha de inicio no es válida (se espera yyyy-mm-dd)';
        } elseif (!ereg('^[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}$', $sFechaFinal)) {
            $this->errMsg = _tr("Invalid End Date");//'Fecha de final no es válida (se espera yyyy-mm-dd)';
        } elseif ($sFechaInicial > $sFechaFinal) {
            $this->errMsg = _tr("Start Date must be greater than End Date");//'Fecha de inicio debe ser anterior a la fecha final';
        } elseif (!ereg('^[[:digit:]]{2}:[[:digit:]]{2}$', $sHoraInicio)) {
            $this->errMsg = _tr("Invalid Start Time");//'Hora de inicio no es válida (se espera hh:mm)';
        } elseif (!ereg('^[[:digit:]]{2}:[[:digit:]]{2}$', $sHoraFinal)) {
            $this->errMsg = _tr("Invalid End Time");//'Hora de final no es válida (se espera hh:mm)';
        } elseif (strcmp($sFechaInicial,$sFechaFinal)==0 && strcmp ($sHoraInicio,$sHoraFinal)>=0) {
            $this->errMsg = _tr("Start Time must be greater than End Time");//'Hora de inicio debe ser anterior a la hora final';
   	} elseif ($error_cola==1){
	     $this->errMsg =  _tr("Queue is being used, choose other one");//La cola ya está siendo usada, escoja otra
        } elseif (!is_null($id_url) && !ereg('^[[:digit:]]+$', $id_url)) {
            $this->errMsg = _tr('(internal) Invalid URL ID');
	}
	else {
                // Verificar que el nombre de la campaña es único
                $recordset =& $this->_DB->fetchTable("SELECT * FROM campaign WHERE name = ".paloDB::DBCAMPO($sNombre));
                if (is_array($recordset) && count($recordset) > 0) {
                    // Ya existe una campaña duplicada
                    $this->errMsg = _tr("Name Campaign already exists");//'Nombre de campaña indicado ya está en uso';
                } else {
                    // Construir y ejecutar la orden de inserción SQL
                    $sPeticionSQL = paloDB::construirInsert(
                        "campaign",
                        array(
                            "name"          =>  paloDB::DBCAMPO($sNombre),
                            "max_canales"   =>  paloDB::DBCAMPO($iMaxCanales),
                            "retries"       =>  paloDB::DBCAMPO($iRetries),
                            "trunk"       =>  (is_null($sTrunk) ? NULL : paloDB::DBCAMPO($sTrunk)),
                            "context"     =>  paloDB::DBCAMPO($sContext),
                            "queue"       =>  paloDB::DBCAMPO($sQueue),
                            "datetime_init" =>  paloDB::DBCAMPO($sFechaInicial),
                            "datetime_end"       =>  paloDB::DBCAMPO($sFechaFinal),
                            "daytime_init"       =>  paloDB::DBCAMPO($sHoraInicio),
                            "daytime_end"       =>  paloDB::DBCAMPO($sHoraFinal),
                            "script"       =>  paloDB::DBCAMPO($script),
                            "id_url"        =>  (is_null($id_url) ? NULL : paloDB::DBCAMPO($id_url)),
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
        }
        return $id_campaign;
    }

    /**
	 * Procedimiento para agregar los formularios a la campaña
	 *
     * @param	int		$id_campaign	ID de la campaña 
     * @param	string		$formularios	los id de los formularios 1,2,....., 
     * @return	bool            true or false       
    */
    function addCampaignForm($id_campania,$formularios)
    {

        if ($formularios != "") {
            $arr_form = explode(",",$formularios);
            foreach($arr_form as $key => $value){
                $sPeticionSQL = paloDB::construirInsert(
                            "campaign_form",
                            array(
                                "id_campaign"    =>  paloDB::DBCAMPO($id_campania),
                                "id_form"        =>  paloDB::DBCAMPO($value)
                            ));
                $result = $this->_DB->genQuery($sPeticionSQL);
                if (!$result){ 
                    $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
                    return false;
                }
            }
        } else {
            $this->errMsg = _tr("There aren't form selected");
            return false;
        }
        return true;
    }

    /**
	 * Procedimiento para actualizar los formularios a la campaña
	 *
     * @param	int		$id_campaign	ID de la campaña 
     * @param	string		$formularios	los id de los formularios 1,2,....., 
     * @return	bool            true or false       
    */
    function updateCampaignForm($id_campania,$formularios)
    {
        $arr_form = explode(",",substr($formularios,0,strlen($formularios)-1));
        $sql = "delete from campaign_form where id_campaign = $id_campania";
        $result = $this->_DB->genQuery($sql);
        if (!$result){ 
             $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
            return false;
        }
        else{
            return $this->addCampaignForm($id_campania,$formularios);
        }
    }

    /**
	 * Procedimiento para obtener los formualarios de una campaña
	 *
     * @param	int		$id_campaign	ID de la campaña 
     * @return	mixed	NULL en caso de error o los id formularios
    */
    function obtenerCampaignForm($id_campania)
    {
        $sPeticionSQL = "SELECT id_form FROM campaign_form WHERE id_campaign = $id_campania";
        $tupla =& $this->_DB->fetchTable($sPeticionSQL);
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
            return null;
        } else {
            $salida = array();
            foreach($tupla as $key => $value){
                $salida[] = $value[0];
            }
            return $salida;
        }
    }

    function getExternalUrls()
    {
    	$sPeticionSQL = 'SELECT id, description FROM campaign_external_url WHERE active = 1';
        $tupla = $this->_DB->fetchTable($sPeticionSQL);
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
            return null;
        } else {
            $salida = array();
            foreach($tupla as $key => $value){
                $salida[$value[0]] = $value[1];
            }
            return $salida;
        }
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
    		$this->errMsg = _tr("Invalid Campaign ID"); //;'ID de campaña no es numérico';
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
    function addCampaignNumbersFromFile($idCampaign, $sFilePath, $sEncoding)
    {
    	$bExito = FALSE;
    	
    	$listaNumeros = $this->parseCampaignNumbers($sFilePath, $sEncoding); 
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
     * @param   string  $sEncoding  Codificación a usar para archivo, NULL para 
     *                              autodetectar.
     *
     * @return	mixed	Matriz cuyas tuplas contienen los contenidos del archivo,
     *					en el orden en que fueron leídos, o NULL en caso de error.
     */
    private function parseCampaignNumbers($sFilePath, $sEncoding)
    {

    	$listaNumeros = NULL;

        // Detectar codificación para procesar siempre como UTF-8 (bug #325)
        if (is_null($sEncoding))
            $sEncoding = $this->_adivinarCharsetArchivo($sFilePath);    	

    	$hArchivo = fopen($sFilePath, 'rt');
    	if (!$hArchivo) {
    		$this->errMsg = _tr("Invalid CSV File");//'No se puede abrir archivo especificado para leer CSV';
    	} else {
    		$iNumLinea = 0;
    		$listaNumeros = array();
    		$clavesColumnas = array();
    		while ($tupla = fgetcsv($hArchivo, 2048,",")) {
    			$iNumLinea++;
    			if (function_exists('mb_convert_encoding')) {
    			    foreach ($tupla as $k => $v)
    			        $tupla[$k] = mb_convert_encoding($tupla[$k], "UTF-8", $sEncoding);
    			}
                $tupla[0] = trim($tupla[0]);
    			if (count($tupla) == 1 && trim($tupla[0]) == '') {
    				// Línea vacía
    			} elseif (strlen($tupla[0]) > 0 && $tupla[0]{0} == '#') {
    				// Línea que empieza por numeral
    			} elseif (!ereg('^[[:digit:]#*]+$', $tupla[0])) {
                    if ($iNumLinea == 1) {
                        // Podría ser una cabecera de nombres de columnas
                        array_shift($tupla);
                        $clavesColumnas = $tupla;
                    } else {
                        // Teléfono no es numérico
                        $this->errMsg = _tr("Invalid CSV File Line")." "."$iNumLinea: "._tr("Invalid number");
                        return NULL;
                    }
    			} else {
                    // Como efecto colateral, $tupla pierde su primer elemento
                    $tuplaLista = array(
                        'NUMERO'    =>  array_shift($tupla),
                        'ATRIBUTOS' =>  array(),
                    );
                    for ($i = 0; $i < count($tupla); $i++) {
                    	$tuplaLista['ATRIBUTOS'][$i + 1] = array(
                            'CLAVE' =>  ($i < count($clavesColumnas) && $clavesColumnas[$i] != '') ? $clavesColumnas[$i] : ($i + 1),
                            'VALOR' =>  $tupla[$i],
                        );
                    }
  					$listaNumeros[] = $tuplaLista;
    			}
    		}
    		fclose($hArchivo);
    	}
    	return $listaNumeros;
    }

    // Función que intenta adivinar la codificación de caracteres del archivo
    private function _adivinarCharsetArchivo($sFilePath)
    {
        if (!function_exists('mb_detect_encoding')) return 'UTF-8';

        // Agregar a lista para detectar más encodings. ISO-8859-15 debe estar
        // al último porque toda cadena de texto es válida como ISO-8859-15.
        $listaEncodings = array(
            "ASCII",
            "UTF-8",
            //"EUC-JP",
            //"SJIS",
            //"JIS",
            //"ISO-2022-JP",
            "ISO-8859-15"
        );
        $sContenido = file_get_contents($sFilePath);
        $sEncoding = mb_detect_encoding($sContenido, $listaEncodings);
        return $sEncoding;
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
    private function addCampaignNumbers($idCampaign, $listaNumeros)
    {
    	
    	if (!ereg('^[[:digit:]]+$', $idCampaign)) {
    		$this->errMsg = _tr("Invalid Campaign ID");//'ID de campaña no es numérico';
    	} elseif (!is_array($listaNumeros)) {
            // TODO: internacionalizar
    		$this->errMsg = '(internal) Lista de números tiene que ser un arreglo';
    	} else {
            // Realizar inserción de número y de atributos 
            // TODO: reportar cuáles números no serán marcados por DNC
            foreach ($listaNumeros as $tuplaNumero) {
            	// Buscar número en lista DNC. Esto es más rápido si hay índice sobre dont_call(caller_id).
                $sql = 'SELECT COUNT(*) FROM dont_call WHERE caller_id = ? AND status = ?';
                $tupla = $this->_DB->getFirstRowQuery($sql, FALSE, array($tuplaNumero['NUMERO'], 'A'));
                $iDNC = ($tupla[0] != 0) ? 1 : 0;
                
                // Inserción del número principal
                $sql = 'INSERT INTO calls (id_campaign, phone, status, dnc) VALUES (?, ?, NULL, ?)';
                $result = $this->_DB->genQuery($sql, array($idCampaign, $tuplaNumero['NUMERO'], $iDNC));
                if (!$result) {
                    // TODO: internacionalizar
                    $this->errMsg = sprintf('(internal) Cannot insert phone %s - %s', 
                        $tuplaNumero['NUMERO'], $this->_DB->errMsg);
                	return FALSE;
                }
                
                // Recuperar el ID de inserción para insertar atributos. Esto asume MySQL.
                $tupla = $this->_DB->getFirstRowQuery('SELECT LAST_INSERT_ID()');
                $idCall = $tupla[0];
                
                // Insertar atributos adicionales.
                foreach ($tuplaNumero['ATRIBUTOS'] as $iNumColumna => $atributos) {
                    $sClave = $atributos['CLAVE'];
                    $sValor = $atributos['VALOR'];
                    $sql = 'INSERT INTO call_attribute (id_call, columna, value, column_number) VALUES (?, ?, ?, ?)';
                    $result = $this->_DB->genQuery($sql, array($idCall, $sClave, $sValor, $iNumColumna));
                    if (!$result) {
                        // TODO: internacionalizar
                        $this->errMsg = sprintf('(internal) Cannot insert attribute %s=%s for phone %s - %s',
                            $sClave, $sValor, $tuplaNumero['__PHONE_NUMBER'], $this->_DB->errMsg);
                    	return FALSE;
                    }
                }
            }
    	}
    	
    	return TRUE;
    }

    /**
     * Procedimiento para crear una nueva campaña, vacía e inactiva. Esta campaña 
     * debe luego llenarse con números de teléfono en sucesivas operaciones.
     *
     * @param   $sNombre            Nombre de la campaña
     * @param   $iMaxCanales        Número máximo de canales a usar simultáneamente por campaña
     * @param   $iRetries           Número de reintentos de la campaña, por omisión 5
     * @param   $sTrunk             troncal por donde se van a realizar las llamadas (p.ej. "Zap/g0")
     * @param   $sContext           Contexto asociado a la campaña (p.ej. 'from-internal')
     * @param   $sQueue             Número que identifica a la cola a conectar la campaña saliente (p.ej. '402')
     * @param   $sFechaInicio       Fecha YYYY-MM-DD en que inicia la campaña
     * @param   $sFechaFinal        Fecha YYYY-MM-DD en que finaliza la campaña
     * @param   $sHoraInicio        Hora del día (HH:MM militar) en que se puede iniciar llamadas
     * @param   $sHoraFinal         Hora del día (HH:MM militar) en que se debe dejar de hacer llamadas
     * 
     * @return  int    El ID de la campaña recién creada, o NULL en caso de error
     */
    function updateCampaign($idCampaign,$sNombre, $iMaxCanales, $iRetries, $sTrunk, $sContext, $sQueue, 
        $sFechaInicial, $sFechaFinal, $sHoraInicio, $sHoraFinal, $script, $id_url)
    {

        $bExito = FALSE;

        $sNombre = trim($sNombre);
        $iMaxCanales = trim($iMaxCanales);
        $iRetries = trim($iRetries);
        $sTrunk = trim($sTrunk);
        $sContext = trim($sContext);
        $sQueue = trim($sQueue);
        $sFechaInicial = trim($sFechaInicial);
        $sFechaFinal = trim($sFechaFinal);
        $sHoraInicio = trim($sHoraInicio);
        $sHoraFinal = trim($sHoraFinal);
        $script = trim($script);

        if ($sTrunk == '') $sTrunk = NULL;

        if ($sNombre == '') {
            $this->errMsg = _tr("Name Campaign can't be empty");//'Nombre de campaña no puede estar vacío';
        } elseif ($sContext == '') {
            $this->errMsg = _tr("Context can't be empty");//'Contexto no puede estar vacío';
        } elseif (!ereg('^[[:digit:]]+$', $iRetries)) {
            $this->errMsg = _tr("Retries must be numeric");//'Número de reintentos debe de ser numérico y entero';
        } elseif ($sQueue == '') {
            $this->errMsg = _tr("Queue can't be empty");//'Número de cola no puede estar vacío';
        } elseif (!ereg('^[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}$', $sFechaInicial)) {
            $this->errMsg = _tr("Invalid Start Date");//'Fecha de inicio no es válida (se espera yyyy-mm-dd)';
        } elseif (!ereg('^[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}$', $sFechaFinal)) {
            $this->errMsg = _tr("Invalid End Date");//'Fecha de final no es válida (se espera yyyy-mm-dd)';
        } elseif ($sFechaInicial > $sFechaFinal) {
            $this->errMsg = _tr("Start Date must be greater than End Date");//'Fecha de inicio debe ser anterior a la fecha final';
        } elseif (!ereg('^[[:digit:]]{2}:[[:digit:]]{2}$', $sHoraInicio)) {
            $this->errMsg = _tr("Invalid Start Time");//'Hora de inicio no es válida (se espera hh:mm)';
        } elseif (!ereg('^[[:digit:]]{2}:[[:digit:]]{2}$', $sHoraFinal)) {
            $this->errMsg = _tr("Invalid End Time");//'Hora de final no es válida (se espera hh:mm)';
        } elseif (strcmp($sFechaInicial,$sFechaFinal)==0 && strcmp ($sHoraInicio,$sHoraFinal)>=0) {
            $this->errMsg = _tr("Start Time must be greater than End Time");//'Hora de inicio debe ser anterior a la hora final';
        } elseif (!is_null($id_url) && !ereg('^[[:digit:]]+$', $id_url)) {
            $this->errMsg = _tr('(internal) Invalid URL ID');
        } else {

            // Construir y ejecutar la orden de update SQL
            $sPeticionSQL = paloDB::construirUpdate(
                "campaign",
                array(
                    "name"          =>  paloDB::DBCAMPO($sNombre),
                    "max_canales"   =>  paloDB::DBCAMPO($iMaxCanales),
                    "retries"       =>  paloDB::DBCAMPO($iRetries),
                    "trunk"         =>  (is_null($sTrunk) ? NULL : paloDB::DBCAMPO($sTrunk)),
                    "context"       =>  paloDB::DBCAMPO($sContext),
                    "queue"         =>  paloDB::DBCAMPO($sQueue),
                    "datetime_init" =>  paloDB::DBCAMPO($sFechaInicial),
                    "datetime_end"  =>  paloDB::DBCAMPO($sFechaFinal),
                    "daytime_init"  =>  paloDB::DBCAMPO($sHoraInicio),
                    "daytime_end"   =>  paloDB::DBCAMPO($sHoraFinal),
                    "script"        =>  paloDB::DBCAMPO($script),
                    "id_url"        =>  (is_null($id_url) ? NULL : paloDB::DBCAMPO($id_url)),
                ),
                " id=$idCampaign "
            );

            $result = $this->_DB->genQuery($sPeticionSQL);
            if ($result) {
                return true;
            } else {
                $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
            }
        }
        return false;
    }

    function activar_campaign($idCampaign,$activar)
    {
         $sPeticionSQL = paloDB::construirUpdate(
             "campaign",
             array("estatus"       =>  paloDB::DBCAMPO($activar)),
             " id=$idCampaign "
            );
        
            $result = $this->_DB->genQuery($sPeticionSQL);
            if ($result) 
                return true;
            else 
                $this->errMsg = $this->_DB->errMsg."<br/>$sPeticionSQL";
            return false;
    } 

    function delete_campaign($idCampaign)
    {
        $sQuery = "SELECT count(id) llamadas_realizadas FROM calls WHERE id_campaign=$idCampaign and status is not null";
//echo "query = $sQuery<br>";
        $result =& $this->_DB->getFirstRowQuery($sQuery, true);
//print_r($result); echo "<br>";
        $valido = false;
        if (is_array($result) && count($result)>0) {
            if ($result["llamadas_realizadas"] == 0) {
                $result = $this->_DB->genQuery("SET AUTOCOMMIT=0");
                if ($result) {
                    $sql = "DELETE FROM campaign_form WHERE id_campaign=$idCampaign";
                    $result = $this->_DB->genQuery($sql);
                    if (!$result) {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->genQuery("ROLLBACK");
                        $this->_DB->genQuery("SET AUTOCOMMIT=1");
                        return false;
                    }
                    $sql = "DELETE FROM call_attribute WHERE id_call in (select id from calls where id_campaign=$idCampaign)";
                    $result = $this->_DB->genQuery($sql);
                    if (!$result) {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->genQuery("ROLLBACK");
                        $this->_DB->genQuery("SET AUTOCOMMIT=1");
                        return false;
                    }
                    $sql = "DELETE FROM calls WHERE id_campaign=$idCampaign";
                    $result = $this->_DB->genQuery($sql);
                    if (!$result) {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->genQuery("ROLLBACK");
                        $this->_DB->genQuery("SET AUTOCOMMIT=1");
                        return false;
                    }

                    $sql = "DELETE FROM campaign WHERE id=$idCampaign";
                    $result = $this->_DB->genQuery($sql);
                    if (!$result) {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->genQuery("ROLLBACK");
                        $this->_DB->genQuery("SET AUTOCOMMIT=1");
                        return false;
                    }
                    $this->_DB->genQuery("COMMIT");
                    $result = $this->_DB->genQuery("SET AUTOCOMMIT=1");
                    $valido = true;
                }
            } else {
                $valido = true;
                $this->errMsg = _tr("This campaign have calls done");
            }
        }
        return $valido;
    }

/********************************************
///// codigo agregado por Carlos Barcos
*********************************************/

    /*
        Funcion que me permite obtener una lista de llamadas a ser bloquedas
    */
    function getDontCallList(){
	$sql = "select id,caller_id from dont_call where status='A'";
	$arr_result =& $this->_DB->fetchTable($sql, true);
	if (!is_array($arr_result)) {
	    $arr_result = FALSE;
	    $this->errMsg = $this->_DB->errMsg;
	}
	return $arr_result;
    }

    /*
        Funcion que permite tomar un arreglo de la forma:
            [0]=>array("clave"=>valor1)
            [1]=>array("clave"=>valor2)
                .
                .
                .
        y convertirlo en otro de la forma:
            [0]=>valor1
            [1]=>valor1
                .
                .
                .
    */
    function convertir_array($data){
        $data_modificada=array();
        if(is_array($data) && count($data)>0){
            foreach($data as $d){
                $data_modificada[] = $d["caller_id"];
            }
        }
        return $data_modificada;
    }

/********************************************
///// Fin codigo agregado por Carlos Barcos
*********************************************/


    /**
     * Procedimiento para leer la totalidad de los datos de una campaña terminada, 
     * incluyendo todos los datos recogidos en los diversos formularios asociados.
     *
     * @param   object  $pDB            Conexión paloDB a la base de datos call_center
     * @param   int     $id_campaign    ID de la campaña a recuperar
     * @param(out) string $errMsg       Mensaje de error
     *
     * @return  NULL en caso de error, o una estructura de la siguiente forma:
    array(
        BASE => array(
            LABEL   =>  array(
                "id_call",
                "Phone Customer"
                ...
            ),
            DATA    =>  array(
                array(...),
                array(...),
                ...
            ),
        ),
        FORMS => array(
            {id_form} => array(
                NAME    =>  'TestForm',
                LABEL   =>  array(
                    "Label A",
                    "Label B"
                    ...
                ),
                DATA    =>  array(
                    {id_call} => array(...),
                    {id_call} => array(...),
                    ...
                ),
            ),
            ...
        ),
    )
     */
    function & getCompletedCampaignData($id_campaign)
    {

        $this->errMsg = NULL;

        $sqlLlamadas = <<<SQL_LLAMADAS
SELECT
    c.id                AS id,
    c.phone             AS telefono,
    c.status            AS estado,
    a.number            AS number,
    c.start_time        AS fecha_hora,
    c.duration          AS duracion,
    c.uniqueid          AS uniqueid,
    c.failure_cause     AS failure_cause,
    c.failure_cause_txt AS failure_cause_txt
FROM calls c
LEFT JOIN agent a 
    ON c.id_agent = a.id
WHERE
    c.id_campaign = ? AND
    (c.status='Success' OR c.status='Failure' OR c.status='ShortCall' OR c.status='NoAnswer' OR c.status='Abandoned')
ORDER BY
    telefono ASC
SQL_LLAMADAS;

        $datosCampania = NULL;
        $datosTelefonos = $this->_DB->fetchTable($sqlLlamadas, FALSE, array($id_campaign));
        if (!is_array($datosTelefonos)) {
            $this->errMsg = 'Unable to read campaign phone data - '.$this->_DB->errMsg;
            return $datosCampania;
        }
        $datosCampania = array(
            'BASE'  =>  array(
                'LABEL' =>  array(
                    'id_call',
                    _tr('Phone Customer'),
                    _tr('Status Call'),
                    "Agente",
                    _tr('Date & Time'),
                    _tr('Duration'),
                    'Uniqueid',
                    _tr('Failure Code'),
                    _tr('Failure Cause'),
                ),
                'DATA'  =>  $datosTelefonos,
            ),
            'FORMS' =>  array(),
        );
        $datosTelefonos = NULL;

        // Construir índice para obtener la posición de la llamada, dado su ID
        $datosCampania['BASE']['ID2POS'] = array();
        foreach ($datosCampania['BASE']['DATA'] as $pos => $tuplaTelefono) {
            $datosCampania['BASE']['ID2POS'][$tuplaTelefono[0]] = $pos;
        }

        // Leer los datos de los atributos de cada llamada
        $iOffsetAttr = count($datosCampania['BASE']['LABEL']);
        $sqlAtributos = <<<SQL_ATRIBUTOS
SELECT
    call_attribute.id_call          AS id_call,
    call_attribute.columna          AS etiqueta,
    call_attribute.value            AS valor,
    call_attribute.column_number    AS posicion
FROM calls, call_attribute
WHERE calls.id_campaign = ? AND calls.id = ? AND calls.id = call_attribute.id_call AND
    (calls.status='Success' OR calls.status='Failure' OR calls.status='ShortCall' OR calls.status='NoAnswer' OR calls.status='Abandoned')
ORDER BY calls.id, call_attribute.column_number
SQL_ATRIBUTOS;
        foreach ($datosCampania['BASE']['ID2POS'] as $id_call => $pos) {
            $datosAtributos = $this->_DB->fetchTable($sqlAtributos, TRUE, array($id_campaign, $id_call));
            if (!is_array($datosAtributos)) {
                $this->errMsg = 'Unable to read attribute data - '.$this->_DB->errMsg;
                $datosCampania = NULL;
                return $datosCampania;
            }
            foreach ($datosAtributos as $tuplaAtributo) {
                // Se asume que el valor posicion empieza desde 1
                $iPos = $iOffsetAttr + $tuplaAtributo['posicion'] - 1;
                $datosCampania['BASE']['LABEL'][$iPos] = $tuplaAtributo['etiqueta'];
                $datosCampania['BASE']['DATA'][$pos][$iPos] = $tuplaAtributo['valor'];
            }
        }

        // Leer los datos de los formularios asociados a esta campaña
        $sqlFormularios = <<<SQL_FORMULARIOS
(SELECT 
    f.id        AS id_form,
    ff.id       AS id_form_field,
    ff.etiqueta AS campo_nombre,
    f.nombre    AS formulario_nombre,
    ff.orden    AS orden
FROM campaign_form cf, form f, form_field ff
WHERE cf.id_form = f.id AND f.id = ff.id_form AND ff.tipo <> 'LABEL' AND cf.id_campaign = ?)
UNION DISTINCT
(SELECT DISTINCT
    f.id        AS id_form,
    ff.id       AS id_form_field,
    ff.etiqueta AS campo_nombre,
    f.nombre    AS formulario_nombre,
    ff.orden    AS orden
FROM form f, form_field ff, form_data_recolected fdr, calls c
WHERE f.id = ff.id_form AND ff.tipo <> 'LABEL' AND fdr.id_form_field = ff.id AND fdr.id_calls = c.id AND c.id_campaign = ?)
ORDER BY id_form, orden ASC
SQL_FORMULARIOS;
        $datosFormularios = $this->_DB->fetchTable($sqlFormularios, FALSE, array($id_campaign, $id_campaign));
        if (!is_array($datosFormularios)) {
            $this->errMsg = 'Unable to read form data - '.$this->_DB->errMsg;
            $datosCampania = NULL;
            return $datosCampania;
        }
        foreach ($datosFormularios as $tuplaFormulario) {
            if (!isset($datosCampania['FORMS'][$tuplaFormulario[0]])) {
                $datosCampania['FORMS'][$tuplaFormulario[0]] = array(
                    'NAME'  =>  $tuplaFormulario[3],
                    'LABEL' =>  array(),
                    'DATA'  =>  array(),
                    'FF2POS'=>  array(),
                );
            }
            $datosCampania['FORMS'][$tuplaFormulario[0]]['LABEL'][] = $tuplaFormulario[2];

            // Construir índice para obtener posición/orden del campo de formulario, dado su ID.
            $datosCampania['FORMS'][$tuplaFormulario[0]]['FF2POS'][$tuplaFormulario[1]] = count($datosCampania['FORMS'][$tuplaFormulario[0]]['LABEL']) - 1;
        }
        $datosFormularios = NULL;

        // Leer los datos recolectados de los formularios
        $sqlDatosForm = <<<SQL_DATOS_FORM
SELECT
    c.id AS id_call,
    ff.id_form AS id_form,
    ff.id AS id_form_field,
    fdr.value AS campo_valor
FROM calls c, form_data_recolected fdr, form_field ff
WHERE fdr.id_calls = c.id AND fdr.id_form_field = ff.id AND c.id_campaign = ?
    AND ff.tipo <> 'LABEL'
    AND (c.status='Success' OR c.status='Failure' OR c.status='ShortCall' OR c.status='NoAnswer' OR c.status='Abandoned')
ORDER BY id_call, id_form, id_form_field
SQL_DATOS_FORM;
        $datosRecolectados = $this->_DB->fetchTable($sqlDatosForm, TRUE, array($id_campaign));
        if (!is_array($datosRecolectados)) {
            $this->errMsg = 'Unable to read form fill-out data - '.$this->_DB->errMsg;
            $datosCampania = NULL;
            return $datosCampania;
        }
        foreach ($datosRecolectados as $vr) {
            if (!isset($datosCampania['FORMS'][$vr['id_form']]['DATA'][$vr['id_call']])) {
                // No está asignada la tupla de valores para esta llamada. Se construye
                // una tupla de valores NULL que será llenada progresivamente.
                $tuplaVacia = array_fill(0, count($datosCampania['FORMS'][$vr['id_form']]['LABEL']), NULL);
                $datosCampania['FORMS'][$vr['id_form']]['DATA'][$vr['id_call']] = $tuplaVacia;
            }
            $iPos = $datosCampania['FORMS'][$vr['id_form']]['FF2POS'][$vr['id_form_field']];
            $datosCampania['FORMS'][$vr['id_form']]['DATA'][$vr['id_call']][$iPos] = $vr['campo_valor'];
        }
        $datosRecolectados = NULL;

        return $datosCampania;
    }
}

//FUNCIONES AJAX
function desactivar_campania($idCampaign)
{
    global $arrConf;
    $respuesta = new xajaxResponse();
    
    // se conecta a la base
    $pDB = new paloDB($arrConf["cadena_dsn"]);

    if($pDB->errMsg != "") {
        $respuesta->addAssign("mb_message","innerHTML",_tr("Error when connecting to database")."<br/>".$pDB->errMsg);
    }

    $oCampaign = new paloSantoCampaignCC($pDB);

    if($oCampaign->activar_campaign($idCampaign,'I'))
        $respuesta->addScript("window.open('?menu=campaign_out','_parent')");
    else{
        $respuesta->addAssign("mb_title","innerHTML",_tr("Desactivate Error")."<br/>".$pDB->errMsg); 
        $respuesta->addAssign("mb_message","innerHTML",_tr("Error when desactivating the Campaign")."<br/>".$pDB->errMsg); 
    }
    return $respuesta;
}
?>
