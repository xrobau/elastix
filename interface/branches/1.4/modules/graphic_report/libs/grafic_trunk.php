<?php
require_once("../../../libs/jpgraph/jpgraph.php");
require_once("../../../libs/jpgraph/jpgraph_pie.php");
require_once("../../../libs/jpgraph/jpgraph_pie3d.php");
require_once "../../../libs/paloSantoDB.class.php";

//cargar el idioma
include_once("../../../libs/misc.lib.php");
load_language("../../../");

//language
$lang = get_language("../../../");
$lang_file = "../lang/$lang.lang";

if (file_exists( $lang_file ))
    include_once($lang_file);
else
    include_once("../lang/en.lang");
global $arrLangModule;

//*******************
require_once "paloSantoExtention.class.php";
$dsnAsteriskCdr = "mysql://asteriskuser:eLaStIx.asteriskuser.2oo7@localhost/asteriskcdrdb";
$pDB_ast_cdr = new paloDB($dsnAsteriskCdr);//asteriskcdrdb -> CDR
$objPalo_AST_CDR = new paloSantoExtention($pDB_ast_cdr);

//variables
$trunk = isset($_GET['trunk'])?$_GET['trunk']:"";
$dti  = isset($_GET['dti'])?$_GET['dti']:"";
$dtf  = isset($_GET['dtf'])?$_GET['dtf']:"";

//total minutos de llamadas in y out
require_once "paloSantoExtention.class.php";
$arrayTemp = $objPalo_AST_CDR->loadTrunks($trunk, "min", $dti, $dtf);
$arrResult = $arrayTemp[0];

//$arrResult[0] => "IN"
//$arrResult[1] => "OUT"
$tot = $arrResult[0] + $arrResult[1];
$usoDisco = ($tot!=0)?100*( $arrResult[0] / $tot ):0;

if( $tot != 0 )
{
    $freeDisco = 100 - $usoDisco;
    
    // Some data
    $data = array($usoDisco, $freeDisco);
    
    // Create the Pie Graph.
    $graph = new PieGraph(400, 170,"auto");
    //$graph->SetShadow();
    $graph->SetMarginColor('#fafafa');
    $graph->SetFrame(true,'#999999');
    
    $graph->legend->SetFillColor("#fafafa");
    //$graph->legend->Pos(0.012, 0.5, "right","center");
    $graph->legend->SetColor("#444444", "#999999");
    $graph->legend->SetShadow('gray@0.6',4);
    
    //$graph->title->SetColor("#444444");
    
    // Set A title for the plot
    $graph->title->Set(utf8_decode($arrLangModule["Total Time"]));
    //$graph->title->SetFont(FF_VERDANA,FS_BOLD,18);
    $graph->title->SetColor("#444444");
    $graph->legend->Pos(0.05,0.2);
    
    // Create 3D pie plot
    $p1 = new PiePlot3d($data);
    //$p1->SetTheme("water");
    $p1->SetSliceColors( array("#3333cc", "#9999cc", "#CC3333", "#72394a", "#aa3424") ); 
    $p1->SetCenter(0.3);
    $p1->SetSize(80);
    
    // Adjust projection angle
    $p1->SetAngle(45);
    
    // Adjsut angle for first slice
    $p1->SetStartAngle(45);
    
    // Display the slice values
    //$p1->value->SetFont(FF_ARIAL,FS_BOLD,11);
    //$p1->value->SetColor("navy");
    $p1->value->SetColor("black");
    
    // Add colored edges to the 3D pies
    // NOTE: You can't have exploded slices with edges!
    $p1->SetEdge("black");
    
    $p1->SetLegends(array( utf8_decode($arrLangModule["Incoming Calls"].":\n").SecToHHMMSS($arrResult[0]),
                           utf8_decode($arrLangModule["Outcoming Calls"].":\n").SecToHHMMSS($arrResult[1])));
    
    $graph->Add($p1);
    $graph->Stroke();
}
else
{
    $ancho = "700";
    $margenDerecho = "100";
    
    $graph = new Graph($ancho,250);
    $graph->SetMargin(50,$margenDerecho,30,40);
    $graph->SetMarginColor('#fafafa');
    $graph->SetFrame(true,'#999999');
    
    $graph->legend->SetFillColor("#fafafa");
    $graph->legend->Pos(0.012, 0.5, "right","center");
    $graph->legend->SetColor("#444444", "#999999");
    $graph->legend->SetShadow('gray@0.6',4);
    $graph->title->SetColor("#444444");
    
    // Especifico la escala
    $graph->SetScale("intlin");
    $graph->title->Set(utf8_decode("Gráfico de Movimientos"));
    $graph->xaxis->SetLabelFormatCallback('MonthCallback');
    $graph->xaxis->SetLabelAngle(90);
    $graph->xaxis->SetColor("#666666","#444444");

    $titulo=utf8_decode($arrLangModule["There are no data to present"]);
    $im = imagecreate(400, 140);
    $background_color = imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 50, 0, "$titulo",$text_color);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 2, 130, 20, "", $text_color);
    if (!empty($msgError)){
        $msgError="Error data base...";
        imagestring($im, 2, 10, 40, $msgError, $text_color);
    }
    imagepng($im);
    imagedestroy($im);
}

function SecToHHMMSS($sec)
{
    $HH = 0;$MM = 0;$SS = 0;
    $segundos = $sec;

    if( $segundos/3600 >= 1 ){ $HH = (int)($segundos/3600);$segundos = $segundos%3600;} if($HH < 10) $HH = "0$HH";
    if(  $segundos/60 >= 1  ){ $MM = (int)($segundos/60);  $segundos = $segundos%60;  } if($MM < 10) $MM = "0$MM";
    $SS = $segundos; if($SS < 10) $SS = "0$SS";

    return "$HH:$MM:$SS";
}
?>