<?
require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoConfig.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once("libs/paloSantoGrid.class.php");
    require_once "libs/misc.lib.php";
    global $arrConf;
    global $arrLang;
    global $arrConfig;
    $sAccion='';
    
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $arrFormElements = array("file"  => array("LABEL" => $arrLang["File"],
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "TEXT",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "text",
                                                        "VALIDATION_EXTRA_PARAM" => ""),
                                 );

    $smarty->assign("Filter",$arrLang['Filter']);
    $smarty->assign("NEW_FILE", $arrLang["New File"]);
    $verListado = true;

    if(isset($_POST["submit_new_file"]) && $_POST["submit_new_file"])
    {
        $sAccion='new_file';
        $verListado = false;
    }else if(isset($_POST["guardar"]) && $_POST["guardar"])
    {
        if(isset($_POST['archivo']) && $_POST['archivo']!=''){
            $fichero = $_POST['archivo'];
            $fichero .= '.conf';

            $ruta_archivo = $arrOtro['etc_asterisk'].$fichero;

            if(isset($_POST["archivo_textarea"]))
                    $texto = $_POST['archivo_textarea'];

            if(file_exists($ruta_archivo))
            {
                $smarty->assign("se_guardo", $arrLang["File already exist"]);
                $smarty->assign("fichero","<input type='text' name='archivo' value='".substr($fichero, 0, -5)."' />.conf");
                $smarty->assign("contenido", "<textarea cols='100' rows='25' name='archivo_textarea'>".$texto."</textarea>");
                $smarty->assign("action", "&action=".$_GET['action']."&archivo=".$_GET['archivo']);
                $smarty->assign("Save","<input type='submit' name='guardar' onclick=' ' value='".$arrLang['Save']."'/>");
            }else{
                $comando = "> " . $arrOtro['etc_asterisk'] . $fichero;
                exec($comando);
                $smarty->assign("se_guardo", $arrLang["The changes was saved in the file"]);

                if(is_writable($ruta_archivo)){ 
                    if($texto != ''){
                        if($fp = fopen($ruta_archivo,"w+"))
                            fwrite($fp,stripslashes($texto));
                        fclose($fp);
                    }
                }
                $smarty->assign("fichero",$fichero);
            }

            $smarty->assign("File",$arrLang['File']);
            $smarty->assign("Back",$arrLang["Back"]);
            $smarty->assign("action","&action=new_file");

            EscribirArchivo($arrOtro,$contenidoModulo,$arrLang,$_GET,$_POST, $smarty);
            $oForm = new paloForm($smarty, $arrFormElements);
            $htmlFilter = $oForm->fetchForm("$local_templates_dir/file_editor.tpl", $arrLang["File Editor"], $_POST);

            $contenidoModulo = $htmlFilter;
        }else if(isset($_GET["action"]) && $_GET["action"]=="EditarArchivo"){
            $sAccion='editar';
        }
        else{
            $smarty->assign("File",$arrLang['File']);
            $smarty->assign("Back",$arrLang["Back"]);
            $smarty->assign("Save","<input type='submit' name='guardar' onclick=' ' value='".$arrLang['Save']."'/>");
            $smarty->assign("action","&action=new_file");
            $smarty->assign("se_guardo", $arrLang["Please write the file name"]);
            $smarty->assign("fichero","<input type='text' name='archivo' value='' />.conf");
            if(isset($_POST["archivo_textarea"]))
                    $texto = $_POST['archivo_textarea'];
            $smarty->assign("contenido", "<textarea cols='100' rows='25' name='archivo_textarea'>".$texto."</textarea>");

            $oForm = new paloForm($smarty, $arrFormElements);
            $htmlFilter = $oForm->fetchForm("$local_templates_dir/file_editor.tpl", $arrLang["File Editor"], $_POST);

            $contenidoModulo = $htmlFilter;
        }

        $verListado = false;
    }else if(isset($_POST["filter"]) && $_POST["filter"]){
        $verListado=true;
    }
    if($verListado){
        ////codigo para mostrar la lista de archivos
        $path=$arrOtro['etc_asterisk'];

        //para formar el arreglo de todos los archivos
        if (is_dir($path) && file_exists($path)) {
            $directorio=dir($path);
            $arreglo_archivos = array();
            while ($archivo = $directorio->read())
            {
                if ($archivo!="." && $archivo!=".."){
                    array_push($arreglo_archivos, $archivo);
                }
            }
            $directorio->close();
        } else {
            $smarty->assign("msj_err",$arrLang['This is not a valid directory']);
        }

        //para mostrar la lista de archivos
        $arrData=array();
        if (is_array($arreglo_archivos)) {
            sort($arreglo_archivos);
            foreach($arreglo_archivos as $item){
                //Filtrar
                $file = "";
                if(isset($_POST["filter"]) && $_POST["filter"])
                    $file = $_POST['file'];
                if(eregi(".*$file.*", $item))
                {
                    $arrTmp    = array();
                    $arrTmp[0] = "&nbsp;<a href='?menu=$module_name&action=EditarArchivo&archivo=$item'>".$item."</a>" ;
                    $arrData[] = $arrTmp;
                }
            }
        }

        if(isset($_GET['archivo']) && $_GET['archivo']!="") {
            $sAccion='editar';
        }

        if(isset($_POST['back'])) {
            $sAccion='regresar';
            $_POST['action'] = $_GET['action'] = "";
            $_POST['archivo'] = $_GET['archivo'] = "";
        }

        ////PARA EL PAGINEO
        $total=count($arreglo_archivos);
        // LISTADO
        $limit = 25;
        $offset = 0;

        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="end") {

            // Mejorar el sgte. bloque.
            if(($total%$limit)==0) {
                $offset = $total - $limit;
            } else {
                $offset = $total - $total%$limit;
            }
        }

        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="next") {
            $offset = $_GET['start'] + $limit - 1;
        }

        // Si se quiere retroceder
        if(isset($_GET['nav']) && $_GET['nav']=="previous") {
            $offset = $_GET['start'] - $limit - 1;
        }

        // Construyo el URL base
        if(is_array($arreglo_archivos) and count($arreglo_archivos)>0) {

            $url = construirURL($arreglo_archivos, array("nav", "start"));
        } else {
            $url = construirURL(array(), array("nav", "start")); 
        }
        $smarty->assign("url", $url);

        $inicio = ($total==0) ? 0 : $offset + 1;
        $fin = ($offset+$limit)<=$total ? $offset+$limit : $total;
        $leng=$fin-$inicio;
        //muestro los registros correspondientes al offset
        $arr_archivos_final=array_slice($arrData,$inicio-1,$leng+1);
        ////FIN DEL PAGINEO

        $arrGrid = array("title"    => $arrLang["File Editor"],
                            "icon"     => "images/kfaxview.png",
                            "width"    => "99%",
                            "start"    => $inicio,
                            "end"      => $fin,
                            "total"    => $total,
                            "columns"  => array(0 => array("name"      => $arrLang["File List"],
                                                            "property1" => ""),
                                                )
                        );

        $oGrid = new paloSantoGrid($smarty);
        $oForm = new paloForm($smarty, $arrFormElements);
        $htmlFilter = $oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["File Editor"], $_POST);
        $oGrid->showFilter($htmlFilter);

        $oFilterForm = new paloForm($smarty, $arrFormElements);

        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arr_archivos_final,$arrLang);
    }

    ////PARA EJECUTAR EL ACTION DEL TPL
    switch ($sAccion) {
        case "editar":
            $fichero = $_GET['archivo'];
            $directorio = dirname($fichero);
            if($directorio!=".")
            {
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", $arrLang["Permission denied"]);
                return $contenidoModulo;
                break;
            }

            $smarty->assign("File",$arrLang['File']);
            $smarty->assign("Back",$arrLang["Back"]);
            $smarty->assign("Save","<input type='submit' name='guardar' onclick=' ' value='".$arrLang['Save']."'/>");
            $smarty->assign("fichero",$fichero);

            EscribirArchivo($arrOtro,$contenidoModulo,$arrLang,$_GET,$_POST, $smarty);
            $oForm = new paloForm($smarty, $arrFormElements);
            $htmlFilter = $oForm->fetchForm("$local_templates_dir/file_editor.tpl", $arrLang["File Editor"], $_POST);

            $contenidoModulo = $htmlFilter;

            break;
        case "regresar":
            return $contenidoModulo;
            break;
        case "new_file":
            $smarty->assign("File",$arrLang['File']);
            $smarty->assign("fichero","<input type='text' name='archivo' />.conf");
            $smarty->assign("Back",$arrLang["Back"]);
            $smarty->assign("Save","<input type='submit' name='guardar' onclick=' ' value='".$arrLang['Save']."'/>");
            $smarty->assign("action","&action=new_file");
            $smarty->assign("contenido", "<textarea cols='100' rows='25' name='archivo_textarea'></textarea>");

            EscribirArchivo($arrOtro,$contenidoModulo,$arrLang,$_GET,$_POST, $smarty);
            $oForm = new paloForm($smarty, $arrFormElements);
            $htmlFilter = $oForm->fetchForm("$local_templates_dir/file_editor.tpl", $arrLang["File Editor"], $_POST);

            $contenidoModulo = $htmlFilter;
            break;
    }

    return $contenidoModulo;
}

function EscribirArchivo($arrOtro,$contenidoModulo,$arrLang,$_GET,$_POST, $smarty){
    $fichero = "";
    if(isset($_GET['archivo']) && $_GET['archivo']!='')
        $fichero = $_GET['archivo'];

    $contenido = '';
    $texto = '';
    if(isset($_POST["archivo_textarea"]))
        $texto = $_POST['archivo_textarea'];

    $msj_no_escritura3="";$msj_no_lectura2="";

    //para el mensaje cuando se guarde
    if(isset($_POST['guardar']) && $_POST['guardar'])
        $se_guardo = $arrLang["The changes was saved in the file"];
    else
        $se_guardo ="";

    if($fichero!=""){
        $ruta_archivo = $arrOtro['etc_asterisk'].$fichero;

        //para saber si es escribible
        if(is_writable($ruta_archivo)){ 
                if($texto != ''){
                if($fp = fopen($ruta_archivo,"w+")){
                        fwrite($fp,stripslashes($texto));
                        $msj_no_escritura3 = "";
                }
                else{
                        $msj_no_escritura3 = $arrLang["This file doesn't have permisses to write"];
                }
                fclose($fp);
                }
        }
        else{
                $msj_no_escritura3 = $arrLang["This file doesn't have permisses to write"];
        }

        //para saber si es de lectura   
        if(is_readable($ruta_archivo)){ 
            if($fp = fopen($ruta_archivo,"r")){ 
                $contenido = fread ($fp, filesize ($ruta_archivo));
                $msj_no_lectura2 = "";
            }
            else{
                $msj_no_lectura2 = $arrLang["This file doesn't have permisses to read"];
            }

            fclose($fp);
        }else{
                //$msj_no_lectura2 = $arrLang["This file doesn't have permisses to read"];
                $msj = $arrLang["Doesn't have permisses to read"];
                $contenidoModulo='<center><table class="message_board" align="center"><tr><td class="mb_message"><b>'.$fichero .' '.$msj.'</b></td></tr>'.$contenidoModulo.'</table></center>';
                return;
        }

        $smarty->assign("se_guardo", $se_guardo);
        $smarty->assign("msj_no_escritura3", $msj_no_escritura3);
        $smarty->assign("msj_no_lectura2", $msj_no_lectura2);
        $smarty->assign("action", "&action=".$_GET['action']."&archivo=".$_GET['archivo']);
        $smarty->assign("contenido", "<textarea cols='100' rows='25' name='archivo_textarea'>".$contenido."</textarea>");
    }
}
?>