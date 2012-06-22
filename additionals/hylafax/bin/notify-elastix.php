#!/usr/bin/php
<?php
    require_once "includes/functions.php";
    require_once "includes/config.php";
 
    if ($_SERVER['argc'] < 2 ) {
        echo $_SERVER['argv'][0]." falta el minimo de argumentos\n";
        exit;
    }

    for($i=1; $i<$_SERVER['argc']; $i++){
	if(eregi("doneq/(q[[:digit:]]+)",$_SERVER['argv'][$i],$arrReg)){
	     //qxx... file que contiene la info del fax enviado que esta en /var/spool/hylafax/sendq/
	     //ahora en esta ruta este(os) archivo(s) son movidos cuando el(los) fax(es) ha(han) sido enviado(s)
             //ruta donde son movidos es /var/spool/hylafax/doneq/ y el archivo del fax en /var/spool/hylafax/docq/
             //en formato ps.
             global $faxes_path;
             $file_name = NULL;
             $tmp_file = "";
	     $commID = getCommID($arrReg[1]);
	     $company_name = getNameCompany($arrReg[1]);
	     $number = getNumberCompany($arrReg[1]);
	     $modemdev = obtener_modem_destiny($number);
	     faxes_log("Informacion > Obteniendo datos commID:$commID, company_name:$company_name, number:$number, modemdev:$modemdev");
             $pathDB = createFolder($number, $commID, 'out');
             faxes_log("createFolder > Creando carpetas para alojar el archivo fax.pdf: $pathDB");
             $file = getTotalFiles($arrReg[1], $pathDB); // retorna el nombre de un unico archivo asi se 1 o mas adjuntados   
             faxes_log("getTotalFiles > Obtiene el nombre del archivo, ya sea .ps .tif o .pdf el cual se va a mostrar en el modulo y adjuntado en el mail: $file");
             //Esto es en el caso de que solo se adjunta un docxx.tif entonces la validacion del
             //str_replace no funciona ya que al no poder reemplazar ps x pdf devuelve
             //la misma cadena en este caso docxx.tif y esto se inserta en al base de datos
             //en el caso de los pdf no habri problema que se inserte con el mismo nombre
             //docxx.pdf
             if(eregi("(doc[[:digit:]]+.tif)",$file, $arrReg))
                $tmp_file = basename($arrReg[0],".tif").".pdf";
             else
                $tmp_file = str_replace("ps","pdf",$file);

             if(!existeFile($arrReg[1])){
		  fax_info_insert ($tmp_file,$modemdev,$commID,"",$company_name,$number,'out',"sent/$pathDB");	
                  faxes_log ("notify  > fax .pdf  en la ruta $faxes_path/sent/$pathDB y se grabo en la BD.");                  
             }

	     /**********************************************
              *         3) ENVIO EMAIL                     *
              **********************************************/

            $destinatario = obtener_mail_destiny($modemdev);
            $arrConfig    = getConfigurationSendingFaxMail($tmp_file,$company_name,$number);
            $titulo       = $arrConfig['subject'];
            $contenido    = $arrConfig['content'];
            $remite       = $arrConfig['remite'];
            $remitente    = $arrConfig['remitente'];
            $archivo      = "$faxes_path/sent/$pathDB/fax.pdf";
            $archivo_name = $tmp_file;

            print_r($arrConfig);
            echo $destinatario;
            enviar_mail_adjunto($destinatario,$titulo,$contenido,$remite,$remitente,$archivo,$archivo_name);

	}
    }
//*****************************************************************//
//*********************** FUNCIONES *******************************//
//*****************************************************************//
function getCommID($file)
{
	return trim(`grep '^commid' /var/spool/hylafax/doneq/$file | cut -b 8-100`);
}

function getNumberCompany($file)
{
        return trim(`grep '^number' /var/spool/hylafax/doneq/$file | cut -b 8-100`);
}


function getTotalFiles($file, $path)
{
    $list_final = "";    
    $line = `grep '^!' /var/spool/hylafax/doneq/$file`;
    faxes_log("files > grep '^!' /var/spool/hylafax/doneq/$file");
    $total_files = explode("\n",$line);    
    $arrFiles2Convert = array();//array con \n como elemento, tengo que depurarlo
    for ($i = 0; $i < count($total_files); $i++){
        if(eregi("docq/(doc[[:digit:]]+.ps)",$total_files[$i],$arrReg) || eregi("docq/(doc[[:digit:]]+.tif)",$total_files[$i],$arrReg) || eregi("docq/(doc[[:digit:]]+.pdf)",$total_files[$i],$arrReg)){
            $arrFiles2Convert[$i] = trim($arrReg[1]);//arry depurado, sin  \n
            faxes_log("arrFiles2Convert > archivos encontrados {$arrFiles2Convert[$i]}");
        } 
    }
    //convertir de acuerdo al tipo de archivo que sea .ps, .tif o .pdf
    faxes_log("ps2pdf - tiff2pdf - pdf2pdf function > Procesamiento de  cada tipo de archivo encontrado");
    for ($i = 0 ; $i < count($arrFiles2Convert); $i++){        
        if(eregi("(doc[[:digit:]]+.ps)",$arrFiles2Convert[$i], $arrReg)){
            $list_final .= ps2pdf($arrReg[1], $path,$i) ;
        }else if(eregi("(doc[[:digit:]]+.tif)",$arrFiles2Convert[$i], $arrReg)){
            $list_final .= tiff2pdf($arrReg[1], $path, $i);
        }else if(eregi("(doc[[:digit:]]+.pdf)",$arrFiles2Convert[$i], $arrReg)){
            $list_final .= pdf2pdf($arrReg[1], $path, $i);
        }
    }

    //Una vez transformado los archivos a sus respectivos formato .pdf, procedemos a unificarlos en uno solo llamado fax.pdf
    //list_pdf contiene /ruta/file0.pdf /ruta/file1.pdf ....  separados por un espacio en blanco    
    finalPdf($list_final, $path);
    faxes_log("finalPdf > Lista Final de archivos a convertir : $list_final");
    //siempre el elemento 0 va a estar ocupado,es decir en el caso de que sea un unico 
    //archivo entonces este 'nombre' estara en la posicion 0, en el caso
    //de varios archivos por ej. doc20.ps doc2x.tif. doc2x.pdf acordamos que en la
    //base de datos asi como en el faxvisor module se mostrara un solo archivo en
    //referencia a los 3 -->> doc20.pdf por ende este estara en la posicion 0 tambien
    return isset($arrFiles2Convert[0])?$arrFiles2Convert[0]:"";
}
/*
function getPdfDocument($file)
{
        $line = `grep '^!pdf' /var/spool/hylafax/doneq/$file`;
        if(eregi("docq/(doc[[:digit:]]+.pdf)",$line,$arrReg))
                return trim($arrReg[1]);
        else return "";
}

function getTiffDocument($file)
{
	$line = `grep '^!tiff' /var/spool/hylafax/doneq/$file`;
        if(eregi("docq/(doc[[:digit:]]+.tif)",$line,$arrReg))
                return trim($arrReg[1]);
        else return "";
}*/
function getNameCompany($file)
{
        return trim(`grep '^sender' /var/spool/hylafax/doneq/$file | cut -b 8-100`);
}
function existeFile($file)
{
	$existe = `sqlite3 /var/www/db/fax.db "select count(*) existe from info_fax_recvq where pdf_file='$file'"`;
	if($existe > 0) return true;
	else return false;
}
?>
