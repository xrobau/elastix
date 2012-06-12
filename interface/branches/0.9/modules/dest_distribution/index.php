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
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoCDR.class.php";
    require_once "libs/misc.lib.php";
    include_once "libs/paloSantoRate.class.php";
    include_once "libs/paloSantoTrunk.class.php";
    
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    

    $MAX_SLICES=10;
    $MAX_DAYS=60;

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
               $arrConfig['AMPDBHOST']['valor'] . "/asteriskcdrdb";
    $dsn2     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
               $arrConfig['AMPDBHOST']['valor'] . "/asterisk";
    $pDBSet = new paloDB("sqlite3:////var/www/db/settings.db");
    $pDBTrunk = new paloDB("sqlite3:////var/www/db/trunk.db");
    $pDB     = new paloDB($dsn);
    $arrData = array();
    $oCDR    = new paloSantoCDR($pDB);
    $smarty->assign("menu","dest_distribution");
    $pDB2     = new paloDB($dsn2);


    $pDBSQLite = new paloDB("sqlite3:////var/www/db/rate.db");
    if(!empty($pDBSQLite->errMsg)) {
        echo "{$arrLang['ERROR']}: $pDB->errMsg <br>";
    }

    $smarty->assign("Filter",$arrLang['Filter']);
    $pRate = new paloRate($pDBSQLite);
    if(!empty($pRate->errMsg)) {
        echo "{$arrLang['ERROR']}: $pRate->errMsg <br>";
    }



    
    $arrFormElements = array("date_start"  => array("LABEL"                  => $arrLang["Start Date"],
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "DATE",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                                 "date_end"    => array("LABEL"                  => $arrLang["End Date"],
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "DATE",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                                  "criteria"  => array("LABEL"                  => $arrLang["Criteria"],
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "SELECT",
                                                        "INPUT_EXTRA_PARAM"      => array(
                                                                 "minutes"         => $arrLang["Distribution by Time"],
                                                                                    "num_calls"         => $arrLang["Distribution by Number of Calls"],
                                                                                    "charge"     => $arrLang["Distribution by Cost"]),
                                                        "VALIDATION_TYPE"        => "text",
                                                        "VALIDATION_EXTRA_PARAM" => ""),
                                 );
    
    $oFilterForm = new paloForm($smarty, $arrFormElements);
    
        // Por omision las fechas toman el sgte. valor (la fecha de hoy)
    $date_start = date("Y-m-d") . " 00:00:00"; 
    $date_end   = date("Y-m-d") . " 23:59:59";
    $value_criteria ="minutes";
       
    
    if(isset($_POST['filter'])) {
        if($oFilterForm->validateForm($_POST)) {
                // Exito, puedo procesar los datos ahora.
            $date_start = translateDate($_POST['date_start']) . " 00:00:00"; 
            $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
        //valido que no exista diferencia mayor de 31 dias entre las fechas
            $inicio=strtotime($date_start);
            $fin=strtotime($date_end);
            $num_dias=($fin-$inicio)/86400;
            if ($num_dias>$MAX_DAYS){
                $_POST['date_start']=date("d M Y");
                $_POST['date_end']=date("d M Y");
                $date_start = date("Y-m-d"). " 00:00:00";
                $date_end   = date("Y-m-d"). " 23:59:59";
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", "{$arrLang['Date Range spans maximum number of days']}:$MAX_DAYS");
            }
            $value_criteria = $_POST['criteria'];    
            $arrFilterExtraVars = array("date_start" => $_POST['date_start'], "date_end" => $_POST['date_end'],"criteria"=>$_POST['criteria']);
        } else {
                // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oFilterForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                    $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
        }
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/dest_dist_filter.tpl", "", $_POST);
    
    } else if(isset($_GET['date_start']) && isset($_GET['date_end'])) {
        //valido que no exista diferencia mayor de 31 dias entre las fechas
        $date_start = translateDate($_GET['date_start']) . " 00:00:00";
        $date_end   = translateDate($_GET['date_end']) . " 23:59:59";

        $inicio=strtotime($date_start);
        $fin=strtotime($date_end);
        $num_dias=($fin-$inicio)/86400;
        if ($num_dias>$MAX_DAYS){
            $_GET['date_start']=date("d M Y");
            $_GET['date_end']=date("d M Y");
            $date_start = date("Y-m-d"). " 00:00:00";
            $date_end   = date("Y-m-d"). " 23:59:59";
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $smarty->assign("mb_message", "{$arrLang['Date Range spans maximum number of days']}:$MAX_DAYS");
        }
           
        $value_criteria = $_GET['criteria'];    
        $arrFilterExtraVars = array("date_start" => $_GET['date_start'], "date_end" => $_GET['date_end'],"criteria"=>$_GET['criteria']);
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/dest_dist_filter.tpl", "", $_GET);
    } else {
        $date_start = date("Y-m-d"). " 00:00:00";
        $date_end   = date("Y-m-d"). " 23:59:59";
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/dest_dist_filter.tpl", "", 
        array('date_start' => date("d M Y"), 'date_end' => date("d M Y"), 'criteria'=>'minutes'));
    }

//obtener los datos a mostrar



    $type_graph=$value_criteria;

  //consulto cuales son los trunks de salida
    $oTrunk     = new paloTrunk($pDBTrunk);
    $arrTrunksBill=$oTrunk->getTrunksBill();//ej array("ZAP/g0","ZAP/g1");
    $troncales=NULL;

   //

    //leer el archivo /etc/zapata.conf para poder reemplazar para ZAP g#  con los respectivos canales
    $ultGrupo="";
    $grupos=array();
    if (file_exists("/etc/asterisk/zapata.conf")){
        $contenido_archivo=file("/etc/asterisk/zapata.conf");
        foreach ($contenido_archivo as $linea){
            if (ereg("^(group|channel)=([[:space:]]*.*)",$linea,$regs)){
                if ($regs[1]=="group"){
                    $ultGrupo=$regs[2];
                    $grupos[$regs[2]]['channel']="";
                }
                if ($regs[1]=="channel"){
                    if (!empty($ultGrupo))
                        $grupos[$ultGrupo]['channel']=$regs[2];
                }
            }
        }
    }
    //poner los canales en un arreglo de la forma
    //array(id_grupo => array (valor1, valor2, valor3,....))
    $canales=array();
    foreach ($grupos as $id_grupo =>$valores_grupo)
    {
        if (ereg("([[:digit:]])+([[:space:]]*-[[:space:]]*([[:digit:]])+)*",$valores_grupo['channel'],$regs1)){
           //los valores vendrian en 1 y 3
            $fin=0;
            $inicio=$regs1[1];
            if (isset($regs1[3])) $fin=$regs1[3];
            if ($fin>0 && $fin>$inicio){
               for ($i=$inicio;$i<=$fin;$i++)
                    $canales[trim($id_grupo)][]=$i;
            }else
                   $canales[trim($id_grupo)][]=$inicio;
            //print_r($regs1);
        }
    }
   // print_r($canales);
    //reemplazo el id del grupo por el valor
    foreach ($arrTrunksBill as $trunkBill)
    {
        if (ereg("^ZAP/g([[:digit:]]+)",$trunkBill,$regs2))
        {
            $id_group=$regs2[1];
            if (isset($canales[$id_group])){
               foreach($canales[$id_group] as $canal)
                $troncales[]="ZAP/$canal";
            }
        }else
            $troncales[]=$trunkBill;

    }




    $arrCDR  = $oCDR->obtenerCDRs("", 0, $date_start,$date_end, "", "","ANSWERED","outgoing",$troncales);


    $total =$arrCDR['NumRecords'][0];
    $num_calls=array();
    $minutos=array();
    $val_charge=array();
    $nombre_rate=array();

    if ($total>0){
        foreach($arrCDR['Data'] as $cdr) {
        //tengo que buscar la tarifa para el numero de telefono
            $numero=$cdr[2];
            $tarifa=array();
            $rate_name="";
            $charge=0;
            $bExito=$pRate->buscarTarifa($numero,$tarifa);
            if (!$bExito)
            {
                echo "{$arrLang['ERROR']}: $pRate->errMsg <br>";
            }else
            {
             //verificar si tiene tarifa
                if (count($tarifa)>0)
                {
                    foreach ($tarifa as $id_tarifa=>$datos_tarifa)
                    {
                        $rate_name=$datos_tarifa['name'];
                        $id_rate=$datos_tarifa['id'];
                        $charge=(($cdr[8]/60)*$datos_tarifa['rate'])+$datos_tarifa['offset'];
                    }
                }else
                {
                    $rate_name=$arrLang["default"];
                    $id_rate=0;
                //no tiene tarifa buscar tarifa por omision
                //por ahora para probar $1 el minuto
                    $rate=get_key_settings($pDBSet,"default_rate");
                    $rate_offset=get_key_settings($pDBSet,"default_rate_offset");
                    $charge=(($cdr[8]/60)*$rate)+$rate_offset;
                }
                $nombre_rate[$id_rate]=$rate_name;
                if (!isset($minutos[$id_rate])) $minutos[$id_rate]=0;
                if (!isset($num_calls[$id_rate])) $num_calls[$id_rate]=0;
                if (!isset($val_charge[$id_rate])) $val_charge[$id_rate]=0;
                $minutos[$id_rate]+=($cdr[8]/60);
                $num_calls[$id_rate]++;
                $val_charge[$id_rate]+=$charge;
            }
        }

    //ordenar los valores a mostrar
        arsort($num_calls);
        arsort($minutos);
        arsort($val_charge);

    //verificar que los valores no excedan el numero de slices del pie
//numero de llamadas

        if (count($num_calls)>$MAX_SLICES){
            $i=1;
            foreach($num_calls as $id_rate=>$valor)
            {

                if ($i>$MAX_SLICES-1){
                    if (!isset($valores_num_calls['otros'])) $valores_num_calls['otros']=0;
                    $valores_num_calls['otros']+=$valor;
                }
                else
                    $valores_num_calls[$id_rate]=$valor;
                $i++;
            }
        }else
            $valores_num_calls=$num_calls;

    //minutos
        if (count($minutos)>$MAX_SLICES){
            $i=1;
            foreach($minutos as $id_rate=>$valor)
            {
                if ($i>$MAX_SLICES-1){
                    if (!isset($valores_minutos['otros'])) $valores_minutos['otros']=0;
                    $valores_minutos['otros']+=$valor;
                }
                else
                    $valores_minutos[$id_rate]=$valor;
                $i++;
            }
        }else
            $valores_minutos=$minutos;


    //charge
        if (count($val_charge)>$MAX_SLICES){
            $i=1;
            foreach($val_charge as $id_rate=>$valor)
            {
                if ($i>$MAX_SLICES-1){
                    if (!isset($valores_charge['otros'])) $valores_charge['otros']=0;
                    $valores_charge['otros']+=$valor;
                }
                else
                    $valores_charge[$id_rate]=$valor;
                $i++;
            }
        }else
            $valores_charge=$val_charge;

        if ($type_graph=="minutes"){
            $titulo=$arrLang["Distribution by Time"];
            $valores_grafico=$valores_minutos;
            $title_sumary=$arrLang["Minutes"];
        }elseif ($type_graph=="charge"){
            $titulo=$arrLang["Distribution by Cost"];
            $valores_grafico=$valores_charge;
            $title_sumary=$arrLang["Cost"];
        }
        else{
            $titulo=$arrLang["Distribution by Number of Calls"];
            $valores_grafico=$valores_num_calls;
            $title_sumary=$arrLang["Number of Calls"];
        }

        //nombres de tarifas para leyenda
        foreach ($valores_grafico as $id=>$valor)
        {
            $nombres_tarifas[]=isset($nombre_rate[$id])?$nombre_rate[$id]:$arrLang["others"];
        }

        $data=array_values($valores_grafico);
   }else
   {
        if ($type_graph=="minutes"){
            $titulo=$arrLang["Distribution by Time"];
        }elseif ($type_graph=="charge"){
            $titulo=$arrLang["Distribution by Cost"];
        }
        else{
            $titulo=$arrLang["Distribution by Number of Calls"];
        }
        $nombres_tarifas=$data=array();
   }
//formar la estructura a pasar al pie

   $data_graph=array(
     "values"=>$data,
     "legend"=>$nombres_tarifas,
     "title"=>$titulo,
     );


    //contruir la tabla de sumario

    $data=urlencode(base64_encode(serialize($data_graph)));
    $smarty->assign("data", $data);
    if (count($data_graph["values"])>0){
         $mostrarSumario=TRUE;
        $total_valores=array_sum($data_graph["values"]);
        $resultados=$data_graph["values"];
        foreach ($resultados as $pos => $valor){
             $results[]=array($nombres_tarifas[$pos],
                              number_format($valor,2),
                              number_format(($valor/$total_valores)*100,2)
                             );
        }
        if (count($results)>1)
        $results[]=array("<b>Total<b>",
                              "<b>".number_format($total_valores,2)."<b>",
                              "<b>".number_format(100,2)."<b>"
                             );

        $smarty->assign("Rate_Name", $arrLang["Rate Name"]);
        $smarty->assign("Title_Criteria", $title_sumary);
        $smarty->assign("results", $results);
    }else
        $mostrarSumario=FALSE;
    $smarty->assign("mostrarSumario", $mostrarSumario);
    $smarty->assign("contentFilter", $htmlFilter);
    $smarty->assign("Destination_Distribution", $arrLang['Destination Distribution']);
    return $smarty->fetch("file:$local_templates_dir/dest_distribution.tpl");
}
?>
