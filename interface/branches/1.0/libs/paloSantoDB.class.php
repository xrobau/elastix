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
  $Id: paloSantoDB.class.php,v 1.2 2007/09/07 00:20:03 gcarrillo Exp $ */

require_once "DB.php";

// La siguiente clase es una clase prototipo... Usela bajo su propio riesgo
class paloDB {

    var $conn;          // Referencia a la conexion activa a la DB
    var $connStatus;    // Se asigna a VERDADERO si ocurrió error de DB
    var $errMsg;        // Texto del mensaje de error
    //var $conn; // Mensaje de error

    /**
     * Constructor de la clase, recibe como parámetro el DSN de PEAR a usar
     * para la conexión a la base de datos. El DSN debe de indicar como base
     * por omisión la base donde se encuentran los ACLs.
     * En caso de éxito, se asigna FALSE a $this->connStatus.
     * En caso de error, se asigna VERDADERO a $this->connStatus y se asigna
     * en $this->errMsg la cadena de error de conexión.
     *
     * @param string    $dsn    cadena de conexión, de la forma "mysql://user:password@dbhost/baseomision"
     */
    function paloDB($dsn) // Creo que aqui debo pasar el dsn
    {
        // Creo una conexion y hago login en la base de datos
        $this->conn = NULL;
        $this->errMsg = "";
        if (is_object($dsn)) {
            $this->conn = $dsn;
            $this->connStatus = FALSE;
        } else {
            $this->conn =& DB::connect($dsn, FALSE); // esto inicia una conexion persistente

            // Ojo con DB::isConnection()... no sabia que existia tal funcion... a lo mejor me sirva para algo

            if (DB::isError($this->conn)) {
//                print '<pre>';print_r($this->conn);print '</pre>';
                $this->errMsg = "Error de conexion a la base de datos - ".
                    $this->conn->getMessage();//$this->conn->getUserInfo();
                $this->connStatus = true;
                $this->conn = NULL;
            } else {
                $this->connStatus = false;
                $this->conn->setOption('autofree', TRUE);
            }
        }
    }

    /**
     * Procedimiento para indicar la desconexión de la base de datos a PEAR
     */
    function disconnect()
    {
        if (!is_null($this->conn)) $this->conn->disconnect();
    }

    /**
     * Procedimiento para ejecutar una sentencia SQL que no devuelve filas de resultados.
     * En caso de error, se asigna mensaje a $this->errMsg
     *
     * @param string $query Sentencia SQL a ejecutar
     *
     * @return bool VERDADERO en caso de éxito, FALSO en caso de error
     */
    function genQuery($query)
    {
        // Revisar existencia de conexión activa
        if ($this->connStatus) {
            return false;
        } else {
            $this->errMsg = "";
            $result =& $this->conn->query($query);
            if (DB::isError($result)) {
                $this->errMsg = $result->getMessage();
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }

    /**
     * Procedimiento que recupera todas las filas resultado de una
     * petición SQL que devuelve una o más filas.
     *
     * @param   string  $query          Cadena de la petición SQL
     * @param   bool    $arr_colnames   VERDADERO si se desea que cada tupla tenga por
     *  índice el nombre de columna
     *
     * @return  mixed   Matriz de las filas de recordset en éxito, o FALSE en error
     */
    function & fetchTable($query, $arr_colnames = FALSE)
    {
        if ($this->connStatus) {
            return FALSE;
        } else {
            $this->errMsg = "";
            $result = $this->conn->query($query);
            if (DB::isError($result)) {
                $this->errMsg = "Query Error: " . $result->getMessage();
                return FALSE;
            }

            $arrResult = array();
            while($row = $result->fetchRow($arr_colnames ? DB_FETCHMODE_OBJECT : DB_FETCHMODE_DEFAULT)) {
                $arrResult[] = (array)$row;
            }
            return $arrResult;
        }
    }

    /**
     * Procedimiento para recuperar una sola fila del query que devuelve una o
     * más filas. Devuelve una fila con campos si el query devuelve al menos
     * una fila, un arreglo vacía si el query no devuelve ninguna fila, o FALSE
     * en caso de error.
     *
     * @param   string  $query          Cadena de la petición SQL
     * @param   bool    $arr_colnames   VERDADERO si se desea que la tupla tenga por
     *  índice el nombre de columna
     *
     * @return  mixed   tupla del recordset en éxito, o FALSE en error
     */
    function & getFirstRowQuery($query, $arr_colnames = FALSE)
    {
        $matriz =& $this->fetchTable($query, $arr_colnames);
        if (is_array($matriz)) {
            if (count($matriz) > 0) {
                return $matriz[0];
            } else {
                return array();
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Procedimiento para escapar comillas y encerrar entre
     * comillas un valor de texto para una sentencia SQL
     *
     * @param string  $sVal    Cadena de texto a escapar
     *
     * @return string Valor con las comillas escapadas
     */
    function DBCAMPO($sVal)
    {
//        if (get_magic_quotes_gpc()) $sVal = stripslashes($sVal);
        $sVal = ereg_replace("\\\\", "\\\\", "$sVal");
        $sVal = ereg_replace("'", "\\'", "$sVal");
        return "'$sVal'";
    }

    /**
     * Procedimiento para construir un INSERT para una tabla.
     * Se espera el nombre de la tabla en $sTabla, y un arreglo
     * asociativo en $arrValores, el cual consiste en <clave> => <valor>
     * donde <clave> es la columna a modificar, y <valor> es la
     * expresión a asignar a la columna. No se insertan comillas simples,
     * así que se debe usar DBCAMPO($val) si se requieren comillas
     * simples o escapes de comillas.
     *
     * @param string    $sTabla     Nombre de la tabla de la base de datos
     * @param array     $arrValores Arreglo asociativo de columna => expresion
     *
     * @return string   Cadena que representa al INSERT generado
     */
    function construirInsert($sTabla, $arrValores)
    {
        $sCampos = "";
        $sValores = "";
        foreach ($arrValores as $sCol => $sVal) {
            if ($sCampos != "") $sCampos .= ", ";
            if ($sValores != "") $sValores .= ", ";
            $sCampos .= $sCol;
            if (is_null($sVal)) {
                $sValores .= "NULL";
            } else {
                $sValores .= $sVal;
            }
        }
        $insert = "INSERT INTO $sTabla ($sCampos) VALUES ($sValores)";
        return $insert;
    }

    /**
     * Procedimiento para construir un REPLACE para una tabla
     * Se espera el nombre de la tabla en $sTabla, y un arreglo
     * asociativo en $arrValores, el cual consiste en <clave> => <valor>
     * donde <clave> es la columna a modificar, y <valor> es la
     * expresión a asignar a la columna. No se insertan comillas simples,
     * así que se debe usar DBCAMPO($val) si se requieren comillas
     * simples o escapes de comillas.
     *
     * @param string    $sTabla     Nombre de la tabla de la base de datos
     * @param array     $arrValores Arreglo asociativo de columna => expresion
     *
     * @return string   Cadena que representa al REPLACE generado
     */
    function construirReplace($sTabla, $arrValores)
    {
        $sCampos = "";
        $sValores = "";
        foreach ($arrValores as $sCol => $sVal) {
            if ($sCampos != "") $sCampos .= ", ";
            if ($sValores != "") $sValores .= ", ";
            $sCampos .= $sCol;
            if (is_null($sVal)) {
                $sValores .= "NULL";
            } else {
                $sValores .= $sVal;
            }
        }
        return "REPLACE INTO $sTabla ($sCampos) VALUES ($sValores)";
    }

    /**
     * Procedimiento para construir un UPDATE para una tabla.
     * El parámetro $sTabla contiene la tabla a modificar. El parámetro
     * $arrValores contiene un arreglo asociativo de la forma
     * <nombre_de_columna> => <expresion_valor>. El parámetro $where
     * tiene, si no es NULL, un arreglo asociativo que se interpreta de
     * la siguiente manera: una clave numérica indica que el valor es
     * una expresión compleja construida a la discreción del código que
     * llamó a construirUpdate(). Una clave de texto se asume el nombre
     * de una columna, con una especificación de igualdad a la expresión
     * indicada, si no es NULL, o equivalente a NULL.
     *
     * @param string    $sTabla     Nombre de la tabla de la base de datos
     * @param array     $arrValores Arreglo asociativo de columna => expresion
     * @param array     $where      Arreglo que describe las condiciones WHERE
     *
     * @return string Cadena que representa al UPDATE generado
     */
    function construirUpdate($sTabla, $arrValores, $where = NULL)
    {
        $sValores = "";
        $sCondicion = "";

        // Si la condicion $where es un arreglo, se construye
        // lista AND con los valores de igualdad. Si no, se
        // asume una condición WHERE directa.
        if (!is_null($where)) {
            $sPredicado = "";
            if (is_array($where)) {
                foreach ($where as $sCol => $sVal) {
                    if ($sPredicado != "") $sPredicado .= " AND ";
                    if (is_integer($sCol))
                        $sPredicado .= "$sVal";   // Se asume condición compleja
                    else if (is_null($sVal))
                        $sPredicado .= "$sCol IS NULL";
                    else $sPredicado .= "$sCol = $sVal"; // Se asume igualdad
                }
            } else {
                $sPredicado = $where;
            }
            $sCondicion = "WHERE $sPredicado";
        }

        // Construir la lista de valores nuevos a modificar
        foreach ($arrValores as $sCol => $sVal) {
            if ($sValores != "") $sValores .= ", ";
            if (is_null($sVal))
                $sValores .= "$sCol = NULL";
            else $sValores .= "$sCol = $sVal";
        }

        return "UPDATE $sTabla SET $sValores $sCondicion";
    }
}
?>
