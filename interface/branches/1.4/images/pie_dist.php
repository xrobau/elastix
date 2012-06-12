<?php
require_once("../libs/jpgraph/jpgraph.php");
require_once("../libs/jpgraph/jpgraph_pie.php");
require_once("../libs/jpgraph/jpgraph_pie3d.php");


$datos=$_GET['data'];
$data_graph=unserialize(base64_decode(urldecode($datos)));
  /* $data_graph=array(
     "values"=>,
     "legend"=>,
     "title"=>,
     );*/


//////////////////



if (count($data_graph["values"])>0){
// Create the Pie Graph.
    $graph = new PieGraph(630, 220,"auto");
//$graph->SetShadow();
    $graph->SetMarginColor('#fafafa');
    $graph->SetFrame(true,'#999999');

    $graph->legend->SetFillColor("#fafafa");
//$graph->legend->Pos(0.012, 0.5, "right","center");
    $graph->legend->SetColor("#444444", "#999999");
    $graph->legend->SetShadow('gray@0.6',4);



// Set A title for the plot
    $graph->title->Set(utf8_decode($data_graph["title"]));
//$graph->title->SetFont(FF_VERDANA,FS_BOLD,18);
    $graph->title->SetColor("#444444");
    $graph->legend->Pos(0.1,0.2);

// Create 3D pie plot
    $p1 = new PiePlot3d($data_graph["values"]);
//$p1->SetTheme("water");
//$p1->SetSliceColors(array("#3333cc", "#9999cc", "#CC3333", "#72394a", "#aa3424","#ECFB43","#047C3A")); 
//$p1->SetSliceColors($colores);
    $p1->SetCenter(0.4);
    $p1->SetSize(100);

// Adjust projection angle
    $p1->SetAngle(60);

// Adjsut angle for first slice
    $p1->SetStartAngle(45);

// Display the slice values
//$p1->value->SetFont(FF_ARIAL,FS_BOLD,11);
//$p1->value->SetColor("navy");
    $p1->value->SetColor("black");

// Add colored edges to the 3D pie
// NOTE: You can't have exploded slices with edges!
    $p1->SetEdge("black");

//$p1->SetLegends(array("Used space","Free space"));
    $p1->SetLegends($data_graph["legend"]);
    $graph->Add($p1);
    $graph->Stroke();
}else{
   //no hay datos - por ahora muestro una imagen en blanco con mensaje no records found

    $titulo=utf8_decode($data_graph["title"]);
    $im = imagecreate(630, 220);
    $background_color = imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 10, 5, 5, $titulo. "  -  No records found", $text_color);
    imagepng($im);
    imagedestroy($im);
}




?>
