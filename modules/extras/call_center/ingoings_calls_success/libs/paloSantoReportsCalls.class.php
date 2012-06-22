<?php

class paloSantoReportsCalls {

    var $pDB;           // variable que almacena la conexion a la base de datos
    var $msgError="";        // variable que almacena los errores generados en el proceso.

    /*
        Constructor de la clase. Retorna false si la conexion a la base de datos no se ha establecido.
    */
    function paloSantoReportsCalls($pDB) {

        if( is_object($pDB->conn) || $pDB->errMsg!="" ) {
            $this->pDB = $pDB;
        }else {
            $this->msgError .= "Error de conexion a la base de datos";
            return false;
        }
    }

    /*
        Esta funcion retorna un arreglo con las colas registradas en la tabla queue_call_entry.
    */
    function getQueueCallEntry($limit=null,$offset=null) {

        $limite = "";

        if( !empty($limit) ){
            $limite = " limit {$limit} ";
            if ( !empty($offset) ) {
                $limite .= " offset {$offset}";
            }
        }

        $SQLConsulta = "select id,queue from queue_call_entry where estatus='A' ".$limite;
        $resConsulta = $this->pDB->fetchTable($SQLConsulta,true);
        if (!is_array($resConsulta)) {
            $this->msgError .= $this->pDB->errMsg;
            return false;
        } else {
        	return $resConsulta;
        }
    }
    
    /*
        Esta funcion retorna un arreglo que contiene la cantidadde llamadas exitosas por colas.
        Cada fila representa el valor de una cola. Retorna false si ha ocurrido algun error.
        Si no se han ingresado valores para $fecha_init y/o $fecha_end, se les asigna la fecha
        actual del sistema.
    */

    function getCall($arrQueues,$fecha_init=null,$fecha_end=null) {
        $arrSuccess = $this->getCallSuccess($arrQueues,$fecha_init,$fecha_end,'terminada');
        $arrLeft    = $this->getCallLeft($arrQueues,$fecha_init,$fecha_end,'abandonada');

        $arrWaitTimeSuccess = $arrSuccess['tiempo'];//$this->getCallSuccessWaitTime($arrQueues,$fecha_init,$fecha_end,'terminada');
        $arrWaitTimeLeft    = $arrLeft['tiempo'];//$this->getCallLeftWaitTime($arrQueues,$fecha_init,$fecha_end,'abandonada');

        $arrWaitTime = $this->sumaTiempos( $arrWaitTimeSuccess,$arrWaitTimeLeft);
        
        $arrData = array(
                            "Success"   => $arrSuccess['data'],
                            "Left"      => $arrLeft['data'],
                            "WaitTime"  => $arrWaitTime,
                        );

        return $arrData;
    }

    /*
        Esta funcion recibe un arreglo de colas activas para llamadas entrantes y 
        retorna un arreglo con dos sub arreglos:
        $arrData--> contiene el numero de llamadas exitosas por cola.
        $arrTime--> contiene el tiempo de espera total de las llamadas exitosas por cola.
    */
    function getCallSuccess($arrQueues,$fecha_init=null,$fecha_end=null,$tipo) {
        // indice para almacenar los valores obtenidos por la consulta en el arreglo a devolver.
        $indice = 0;
        // aqui voy recorriendo el arreglo que contiene las colas en el sistema para consultar iterativamente
        // por cada cola e ir generando el reporte por cola
        $arrData = array();
        $arrTime = array();
        foreach($arrQueues as $queue) {
            // consulto la cantidad de llamadas entre las fechas $fecha_init y $fecha_end y en la cola $queue['id'].
            $SQLConsulta = 
            "
                select count(*) as cantidad_llamadas  
                from call_entry    
                where   (
                            datetime_entry_queue>='{$fecha_init}' 
                                    and
                            datetime_entry_queue<='{$fecha_end}' 
                        )
                                    and
                        id_queue_call_entry={$queue['id']}
                                    and
                        status='{$tipo}'
            ";

            $resConsulta = $this->pDB->getFirstRowQuery($SQLConsulta,true);

            if(is_array($resConsulta) && count($resConsulta)>0) {
                $arrData[$indice] = $resConsulta['cantidad_llamadas'];
            // si se ha producido un error en el query retorno false.
            }else {
                $this->msgError .= $this->pDB->errMsg;
                return false;
            }
            // consulto el tiempo de espera de la llamada entre las fechas $fecha_init y $fecha_end 
            // y en la cola $queue['id'].
            $SQLConsulta = 
            "
                select sec_to_time( sum( duration_wait ) ) as duration
                from call_entry 
                where 
                        (
                            datetime_entry_queue >= '{$fecha_init}' 
                                            and 
                            datetime_entry_queue <= '{$fecha_end}'
                        ) 
                                            and 
                                id_queue_call_entry = {$queue['id']}
                                            and 
                                        status='{$tipo}'
            ";

            $resConsulta = $this->pDB->getFirstRowQuery($SQLConsulta,true);

            if(is_array($resConsulta) && count($resConsulta)>0) {
                if ( is_null( $resConsulta['duration'] ) ) {
                    $resConsulta['duration'] = '00:00:00';
                }
                $arrTime[$indice] = $resConsulta['duration'];
            // si se ha producido un error en el query retorno false.
            }else {
                $this->msgError .= $this->pDB->errMsg;
                return false;
            }

            $indice++;
        }
        // retorno el arreglo con los datos del reporte
        $arrDatos = array
                    (
                        "data"  => $arrData,
                        "tiempo"=> $arrTime,
                    );
        return $arrDatos;
    }

    /*
        Esta funcion recibe un arreglo de colas activas para llamadas entrantes y 
        retorna un arreglo con dos sub arreglos:
        $arrData--> contiene el numero de llamadas abandonadas por cola.
        $arrTime--> contiene el tiempo de espera total de las llamadas abandonadas por cola.
    */
    function getCallLeft($arrQueues,$fecha_init=null,$fecha_end=null,$tipo) {

        // indice para almacenar los valores obtenidos por la consulta en el arreglo a devolver.
        $indice = 0;
        // aqui voy recorriendo el arreglo que contiene las colas en el sistema para consultar iterativamente
        // por cada cola e ir generando el reporte por cola
        $arrData = array();
        $arrTime = array();
        foreach($arrQueues as $queue) {
            // consulto la cantidad de llamadas entre las fechas $fecha_init y $fecha_end y en la cola $queue['id'].
            $SQLConsulta = 
            "
                select count(*) as cantidad_llamadas  
                from call_entry    
                where   (
                            datetime_entry_queue>='{$fecha_init}' 
                                    and
                            datetime_entry_queue<='{$fecha_end}' 
                        )
                                    and
                        id_queue_call_entry={$queue['id']}
                                    and
                        status='{$tipo}'
            ";

            $resConsulta = $this->pDB->getFirstRowQuery($SQLConsulta,true);

            if(is_array($resConsulta) && count($resConsulta)>0) {
                $arrData[$indice] = $resConsulta['cantidad_llamadas'];
            // si se ha producido un error en el query retorno false.
            }else {
                $this->msgError .= $this->pDB->errMsg;
                return false;
            }
            // consulto el tiempo de espera de la llamada entre las fechas $fecha_init y $fecha_end 
            // y en la cola $queue['id'].
            $SQLConsulta = 
            "
                select sec_to_time( sum( duration_wait ) ) as duration
                from call_entry 
                where 
                        (
                            datetime_entry_queue >= '{$fecha_init}' 
                                            and 
                            datetime_entry_queue <= '{$fecha_end}'
                        ) 
                                            and 
                                id_queue_call_entry = {$queue['id']}
                                            and 
                                        status='{$tipo}'
            ";

            $resConsulta = $this->pDB->getFirstRowQuery($SQLConsulta,true);

            if(is_array($resConsulta) && count($resConsulta)>0) {
                if ( is_null( $resConsulta['duration'] ) ) {
                    $resConsulta['duration'] = '00:00:00';
                }
                $arrTime[$indice] = $resConsulta['duration'];
            // si se ha producido un error en el query retorno false.
            }else {
                $this->msgError .= $this->pDB->errMsg;
                return false;
            }

            $indice++;
        }
        // retorno el arreglo con los datos del reporte
        $arrDatos = array
                    (
                        "data"  => $arrData,
                        "tiempo"=> $arrTime,
                    );
        return $arrDatos;
    }


    /*
        Esta funcion recibe dos arreglos:
        $arrSuccess--> contiene el tiempo de espera por cola de las llamadas exitosas.
        $arrLeft--> contiene el tiempo de espera por cola de las llamadas abandonadas.
        Suma los respectivos valores y devuelve un arreglo que contiene la suma de los tiempos por 
        cola de las llamadas.
    */
    function sumaTiempos($arrSuccess,$arrLeft) {
        if (is_array($arrSuccess) && is_array($arrLeft) ) {

            if( count($arrSuccess) > 0 && count($arrLeft) > 0) {
                for( $i=0 ; $i<count($arrSuccess) ; $i++ ) {

                    if ( is_null($arrSuccess[$i]) ) {
                        $arrSuccess[$i] = '00:00:00';
                    }

                    if ( is_null($arrLeft[$i]) ) {
                        $arrLeft[$i] = '00:00:00';
                    }

                    $SQLConsulta = 
                    "
                        select addtime('".$arrSuccess[$i]."','".$arrLeft[$i]."') as duration
                    ";

                    $resConsulta = $this->pDB->getFirstRowQuery($SQLConsulta,true);

                    if(!$resConsulta)  {
                        $arrWaitTime[$i] = '00:00:00';
                        $this->msgError = $this->errMsg;
                    }else {
                        $arrWaitTime[$i] = $resConsulta['duration'];
                    }
                }
                return $arrWaitTime;
            } else if( count($arrSuccess) > 0 ) {
                return $arrSuccess;
            } else if( count($arrLeft) > 0 ) {
                return $arrLeft;
            } else {
                $this->msgError .= $this->pDB->errMsg;
            }
        } else {
            $this->msgError .= $this->pDB->errMsg;
        }
        return false;
    }

    /*
        Esta funcion recibe un dos tiempos y retorna la suma de ellos
    */
    function getTotalWaitTime($time1,$time2) {

 	if( is_null($time1) ) {
 	    $time1 = "00:00:00";
 	}
 	if( is_null($time2) ) {
 	    $time2 = "00:00:00";
 	}
 
 	$SQLConsulta = "select addtime('{$time1}','{$time2}') duracion";
 	$resConsulta = $this->pDB->getFirstRowQuery($SQLConsulta,true); 

 	if(!$resConsulta)  {
             $this->msgError = $this->errMsg;
 	    return false;
 	} else {
 	   return $resConsulta['duracion'];
 	}
    }



}

?>