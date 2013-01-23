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
  /    Autor: Carlos Barcos <cbarcos@palosanto.com>                     /
  +----------------------------------------------------------------------+
  $Id: PaloSantoDontCalls.class.php 

*/

include_once("libs/paloSantoDB.class.php");

/* Clase que implementa breaks */
class PaloSantoDontCalls{
    var $_DB;
    var $errMsg;

    /*
        Constructor de la clase
    */
    function PaloSantoDontCalls(&$pDB){
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);
            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
            }
        }
    }

    /*
        Funcion que permite obtener la lista de numeros telefonicos a bloquear.
        La lista esta ordenada descendentemente por fecha de ingreso y 
        ascendentemente por numero de telefono.
    */
    function getCalls($id_call = NULL,$estatus='all'){
        $sPeticionSQL = "select * from dont_call order by date_income desc , caller_id"; 
        $arr_result =& $this->_DB->fetchTable($sPeticionSQL, true);
        if (!is_array($arr_result)) {
            $arr_result = FALSE;
            $this->errMsg = $this->_DB->errMsg;
        }
        return $arr_result;
    }

    /*
        Funcion que obtiene la lista de numeros habilitados a bloquear con estatus Inactivo.
    */
    function getDontCallList(){
	$sql = "select id,caller_id from dont_call where status='I'";
	$arr_result =& $this->_DB->fetchTable($sql, true);
	if (!is_array($arr_result)) {
	    $arr_result = FALSE;
	    $this->errMsg = $this->_DB->errMsg;
	}
	return $arr_result;
    }

    /*
        Funcion que permite obtener el listado de llamadas habilitadas para bloquear segun el listado
        de getDontCallList.
    */
    function getCallList(){
	$sql = "
	    select c.id,c.id_campaign ,c.phone 
	    from calls c, campaign p 
	    where 
		(c.id_campaign = p.id) 
		    and 
		( (c.status!='Success' and c.status!='OnHold') or c.status is null ) 
		    and 
                (p.estatus = 'A' or p.estatus = 'I') 
		    and 
		c.retries<p.retries";

	$arr_result =& $this->_DB->fetchTable($sql, true);
	if (!is_array($arr_result)) {
	    $arr_result = FALSE;
	    $this->errMsg = $this->_DB->errMsg;
	}
	return $arr_result;
    }

    /*
        Funcion que se encarga de bloquear las llamadas segun el listado de numeros a bloquear
    */
    function applyList(){
        $this->_DB->conn->beginTransaction(FALSE);
        $msg=false;
        $aux=true;  // variable utilizada para evitar asignar la coma al principio de la cadena
        $valor="";
        // obtengo el listado de llamadas habiles para la busqueda y bloqueo
        $dataCall = $this->getCallList();
        // obtengo el listado de numeros a bloquear
        $dataDontCall_original = $this->getDontCallList();
        // convieto el arreglo en un tipo mas apropiado para la busqueda de la funcion in_array de php
        $dataDontCall = $this->convertir_array($dataDontCall_original);
        // si el arreglo tiene datos continuo sino no hago nada
        if(TRUE /*count($dataCall)>0*/ ){
            // leo el listado de llamadas y recorro registro a registro
	    foreach($dataCall as $phone){
                // obtengo el numero de la llamada del registro actual
		$phone_caller = $phone['phone'];
                // pregunto si el numero actual esta en el listado de numeros a bloquear
		// si nunca se entra en esta condicion indica que no hay llamadas a bloquear y
                // la variable $valor conserva su valor original que es vacio
                if( in_array($phone_caller,$dataDontCall) ){
                    // si $aux es true la primera vez no agrego el id actual a la cadena
                    if($aux){
                        // asigno $aux a false para evitar ingresar otra vez en esta condicion
                        $aux=false;
                        // a la variabel $valor el id actual de la lista de llamadas
                        $valor .= $phone['id'];
                    // si la variable $aux es false, a la varable $valor le concateno ',' 
                    // con el id de la llamada actual para ir formando el query string
                    }else{
                        $valor .= ",".$phone['id'];
                    }
                }
	    } // fin de la busqueda 
            // si la variable $valor es diferente de vacio, se ha encontrado llamadas que 
            // estan en la lista de numeros a bloquear y por lo tanto se completa el query string.
            // Si la variable es igual a vacio significa que no existen llamadas a bloquear
            if($valor!=""){
                $valor = "(".$valor.")";
	    }
            // si existen llamadas a bloquear se procede a actualizar el registro bloqueando la llamadas
            if($valor!=""){
                // si todo ha salido bien la funcion retorn true, caso contrario es false
                $msg = $this->actualizarRegistroCalls($valor,1);
            }
	    // si $msg es true significa que se ha bloqeuado las llamadas con exito
            // si $valor es igual vacio indica que no hay llamadas a bloquear
            // en cualquiera de los casos se cambia el status de la lista de numeros a bloqeuar 
            // para asegurar qeu no se haga una busqueda innecesaria en lo posterior 
            if($msg || $valor==""){
                // se actualizan la lista de numeros a bloqeuar
                // la funcion devuelve true en casos de exito y false en caso contario
                $flag = $this->actualizarRegistroDontCall($dataDontCall_original);
                // si la funcion devuelve true, se ha guardan los cambios en la base
                if($flag){
                    $this->_DB->conn->commit();
                    return true;
                // sino se hace un rollback para deshacer los cambios hechos
                }else{
                    $this->_DB->conn->rollBack();
		    return false;
                }
            // ha ocurrido algun error se hace un rollback para deshacer los cambios realizados hasta
            // el momento
            }else{
                $this->_DB->conn->rollBack();
                return false;
            }
        }
    }

    /*
        Funcion que actualiza la lista de llamadas para masrcaralas como bloqeuadas o no
        El parametro $dnc indica si la llamada sera bloqeuada o no
        $dnc = 1 --> la llamada sera bloqueada
        $dnc = 0 --> la llamda sera habilitada para realizarse
    */
    function actualizarRegistroCalls($where_in,$dnc){
        $msg="";
        if($where_in!=""){
	    $sPeticionSQL = paloDB::construirUpdate(
		"calls", array(
		    "dnc"          =>  paloDB::DBCAMPO($dnc),
		),
		" id in $where_in "
	    );
 	    $result = $this->_DB->genQuery($sPeticionSQL);
 	    if(!$result) {
 		$msg .= "Error in $phone_id";
 	    }else{
                return true;
            }
        }
        return false;
    }

    /*
        Funcion que permite actualizar la lista de numeros a bloquear
        Recibe como parametro un arreglo con los id de los numeros a actualizar
    */
    function actualizarRegistroDontCall($dataDontCall){
        $msg="";
        $aux=true;
        $valor="";
        // is el arreglo contiene datos continuo sino no
        if(count($dataDontCall)>0){
            // recorro el arreglo linea a linea
            foreach($dataDontCall as $call){
                // si la variable $aux es true ingreso
		if($aux){
                    // seteo la variable $aux a false para no ingresar en otra ocasion a esta condicion
		    $aux=false;
		    // asigno a $valor el id actual
		    $valor .= $call['id'];
                // si es false concateno a $valor la ','  el id actual para ir fomrando el query string
		}else{
		    $valor .= ",".$call['id'];
		}
	    }
            // si valor es igual a vacio significa qeu no existe ningun valor a actualizar
            // caso contrario se completa el query string
            if($valor!=""){
                $where_in = "(".$valor.")";
	    }else{
                $where_in = "";
            }
	    // si $where_in es vacio no se hace nada ya que no hay valores a modificar y se retor na false
            // caso contrario se construye la sentencia sql completa para realizar el update de los valores
            // recogidos en el arreglo $dataDontCall
            if($where_in!=""){
 		$sPeticionSQL = paloDB::construirUpdate(
 		    "dont_call", array(
 			"status"          =>  paloDB::DBCAMPO('A'),
 		    ),
 		    " id in $where_in"
 		);
 		$result = $this->_DB->genQuery($sPeticionSQL);
		// si ha ocurrido algun error se guarda un mensaje de error
                // caso contrario se devuelve true ya que la lista se ha actualizado con exito
 		if(!$result) {
 		    $msg .= "Error in $phone_id";
 		}
                return true;
            }else{
                return false;
            }
        }
        return $msg;
    }

    /*
        Funcion que permite borrar los numeros de la lista de numeros bloqueados
        Recibe como parametro un arreglo con los id de los numeros a borrar de la lista
    */
    function deleteCalls($arrData){
        $msg="";
        $this->_DB->conn->beginTransaction(FALSE);
        $aux_del=true; // variable para ingresar una sola vez a una condicion
        $valor_del="";
        $exito_update=false;
        $no_data=false;
        $inactivo=false;
        // se recorre el arreglo para obtener cada uno de los id
        foreach($arrData as $id){
            // se consulta el el numero asociado al id actual es un numero con estado activo
            // si el numero es activo se asume que ya ha sido previamente utilizado en una busqueda
            // para bloqeuar algun numero de la lista de llamadas de la tabla calls
            // sin embargo la busqueda pudo haberse realizado y haber marcado con ningun numero de la lista
            if($this->isActivo($id)){
                // se obtiene el numero de telefono asociado al id actual
                $caller = $this->getCallerID($id);
                // se obtiene una lista de los id de los registros de llamadas que han sido bloqeuadas 
                // con el numero $caller obtenido previamente
                // $arrCalls es false solo en el caso de que no se encuentre ningun registro asociado al 
                // numero $caller, caso contrario almacenara un listado con los id obtenidos, siendo su valor
                // diferente de false y asumido como true con lo cual ingresara a la condicion necesario
                $arrCalls = $this->getListaCallerId($caller);
                $aux=true;  // variable para ingresar una sola vez a una condicion
                $valor="";
                // si $arrCalls es true, se asume  que almacena la lista de valores asociados a $caller
                if( $arrCalls ){
                    // se recorre el arreglo para leer uno a uno los id respectivos
		    foreach($arrCalls as $id_calls){
                        // si $aux es true ingresa a la condicion
			if($aux){
                            // se asigna la $aux a true para que no ingrese en otra ocasion
			    $aux=false;
			    $valor .= $id_calls['id'];
			}else{
			    $valor .= ",".$id_calls['id'];
			}
		    }
                    if($valor!=""){
                        // se termina el query string
		      $valor = "(".$valor.")";
		    }
		    if($valor!=""){
                        // se actualiza el registro
			$exito_update .= $this->actualizarRegistroCalls($valor,0);
		    }
                }else{
                    // si no hay datos asociados se setea esta variable a true
                    $no_data=true;
                }		
            }else{
                // si el registro es inactivo no se necesita buscar mas 
                $inactivo=true;
	    }
            // si ha habido exito en el update masivo
            if($exito_update){
                // se consulta la primera vez
                // se alamcenan los id de los registros a borrar
		if($aux_del){
		    $aux_del=false;
		    $valor_del .= $id;
		}else{
		    $valor_del .= ",".$id;
		}
            // si no existen datos asociados
            }elseif($no_data){
                // guarda el id
                if($valor_del==""){
                    $valor_del.=$id;
                }else{
                    $valor_del.=','.$id;
                }
            // si es inactivo
            }elseif($inactivo){
                // se guarda el id
                if($valor_del==""){
                    $valor_del.=$id;
                }else{
                    $valor_del.=','.$id;
                }
            // si ha ocurrido algun error se deshace lo hecho hasta el momento
            }else{
                $this->_DB->conn->rollBack();
                return false;
            }
        }
        // si $valor es vacio no se hace nada
        if($valor_del!=""){
            // se termina el query string
	    $valor_del = "(".$valor_del.")";
	}
        if($valor_del!=""){
            // se completa el delete string
            $sql="delete from dont_call where id in $valor_del";
	    $result = $this->_DB->genQuery($sql);
            // si ha ocurrido error se deshace todo
	    if(!$result) {
                $this->_DB->conn->rollBack();
		$msg .= "Error in delete : $id";
            // sino se graba los cambios en la base de datos
	    }else{
                $this->_DB->conn->commit();
            }
        }
        // si $msg es vacio se retorna true, caso contrario false
        if($msg!="")
            return false;
        else
            return true;
    }

    /*
        Funcion que me permite conocer si un numero es activo o no
    */
    function isActivo($id){
        $sql="select status from dont_call where id={$id}";
        $arr_result =& $this->_DB->getFirstRowQuery($sql, true);
	if (is_array($arr_result) && count($arr_result)>0) {
            if( $arr_result["status"]=='A' )
	       return true;
            else
                return false;
	}
    }

    /*
        Funcion que me permite conocer el caller_id o numero de un registro en la lista 
        de numeros a bloqeuar
    */
    function getCallerID($id){
        $sql="select caller_id from dont_call where id={$id}";
        $arr_result =& $this->_DB->getFirstRowQuery($sql, true);
	if (is_array($arr_result) && count($arr_result)>0) {
            return $arr_result["caller_id"];
	}
        return false;
    }

    /*
        Funcion que permite obtener un listado de llamadas para realizar la 
        busquedo de llamadas a bloqeuar
    */
    function getListaCallerId($phone){
        $sql = "
	    select c.id as id 
	    from calls c, campaign p 
	    where 
		(c.id_campaign = p.id) 
		    and 
		( (c.status!='Success' and c.status!='OnHold') or c.status is null ) 
		    and 
                (p.estatus = 'A' or p.estatus = 'I') 
		    and 
		c.retries<p.retries and c.phone='{$phone}'";
        $arr_result =& $this->_DB->fetchTable($sql, true);
	if (is_array($arr_result) && count($arr_result)>0) {
            return $arr_result;
	}
        return false;
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
}

class Cargar_File
{

    var $cFile;             // almacena el valor recibido en el constructor
    var $CADENA_MESSAGE = "";
    var $NUM_ERROR = "";
    /*
        Constructor de la clase, recibe el valor del arreglo global $_FILES['nombre_variable_file']
    */
    function Cargar_File($file) {

        if (!is_null($file))
            $this->cFile = $file;
        else
            $this->guardarMensaje("Error, el nombre del archivo no puede ser null");
    }

    /*
        Esta funccion abre un retorna el nombre del archivo seleccionado
    */    
    function getFileName() {
        if($this->cFile['error']==0 && $this->cFile['size']>0) {
            $nameFile = $this->cFile['tmp_name'];
            return $nameFile;
        } else {
            $this->guardarMensaje("Error al obtener el nombre del archivo : ".$this->cFile['error']);
        }
    }


    /*
        Esta funcion valida si los valores de los registros son validos
            se valida:
            si numero de cedula y telefono son numericos
            si los valores obtenidos del archivo no estan vacios
    */
    function validarValorCampos($caller_id) {
        if ( $caller_id=="") {
            $this->NUM_ERROR++;
            return false;
        }
        if ( !is_numeric($caller_id)) { 
            $this->NUM_ERROR++;
            return false;
        }
        return true;
    }


    function procesarValorCampos($caller_id,$pDB,&$numActualizados,&$numInsertados) {

        $SQLConsultaCallerId = 'select * from dont_call where caller_id='.paloDB::DBCAMPO($caller_id);
        $resConsultaCallerId = $pDB->fetchTable($SQLConsultaCallerId,true);

	if( !count( $resConsultaCallerId )>0 ){
	    $sPeticionSQL = paloDB::construirInsert(
		"dont_call", array(
		    "caller_id"     =>  paloDB::DBCAMPO($caller_id),
		    "date_income"   =>  paloDB::DBCAMPO(date('Y-m-d H-i-s')),
		    "status"        =>  paloDB::DBCAMPO('I'),
		)
	    );

	    $result = $pDB->genQuery($sPeticionSQL);
	    if(!$result) {
		$this->guardarMensaje("Error al ingresar el registro" );
	    }else {
		$numInsertados++;
	    }
	}
    }

    function guardarDatosCallsFromFile($pDB,$name_fileCSV) {
        $numInsertados      = 0;
        $numErrores         = 0;
        $gestorFile = fopen($name_fileCSV,"r");
	if($gestorFile) {
	    if (!is_object($pDB->conn) || $pDB->errMsg!="") {
		//echo $pDB->errMsg;
	    }else{
		$numRegistro = 0;
		$registrosValidos = false;
		while(!feof($gestorFile)  ) {
		    $numRegistro++;
		    $valorCampos = fgetcsv($gestorFile);
		    if (count($valorCampos)>0) {
			$caller_id   = $valorCampos[0];
			$registrosValidos = $this->validarValorCampos($caller_id);
			if($registrosValidos ) {
				$this->procesarValorCampos($caller_id,$pDB,$numActualizados,$numInsertados);
			} else {
			    $this->NUM_ERROR++;
			}
		    }
		}
		//$this->guardarMensaje("El numero de registros insertados es: $numInsertados");
	    } 
	    fclose($gestorFile);
	}
    }

    /*
        Esta funcion almacena los mensajes y errores producidos en el procedimiento de
        carga de datos del cliente
    */
    function guardarMensaje($cadena) {
        //$this->CADENA_MESSAGE .= $cadena."<br>";
    }

    /*
        Esta funcion retorna los mensajes almacenados durante proceso de carga de datos del cliente
    */
    function getMsgResultado() {
        $this->getNumErrores();
        return $this->CADENA_MESSAGE;
    }

    function getNumErrores() {
         $this->guardarMensaje($this->NUM_ERROR);
    }

}

function registrarNuevoNumero($pDB,$caller_id){
    $SQLConsultaCallerId = 'select * from dont_call where caller_id='.paloDB::DBCAMPO($caller_id);
    $resConsultaCallerId = $pDB->fetchTable($SQLConsultaCallerId,true);
    if( !count( $resConsultaCallerId )>0 ){
	$sPeticionSQL = paloDB::construirInsert(
	    "dont_call", array(
		"caller_id"     =>  paloDB::DBCAMPO($caller_id),
		"date_income"   =>  paloDB::DBCAMPO(date('Y-m-d H-i-s')),
		"status"        =>  paloDB::DBCAMPO('I'),
	    )
	);
	$result = $pDB->genQuery($sPeticionSQL);
	if(!$result) {
	    return "Error al ingresar el registro";
	}else {
            return "";
	}
    }else{
        return _tr('the number already exists');
    }
}


?>