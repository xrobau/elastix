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
	     $file = geDocument($arrReg[1]);
	     $commID = getCommID($arrReg[1]);
	     $company_name = getNameCompany($arrReg[1]);
	     $number = getNumberCompany($arrReg[1]);
	     $modemdev = obtener_modem_destiny($number);
             faxes_log ("notify > Obteniendo informacion  file:$file, id:$commID, company name:$company_name, number:$number, device:$modemdev \n");
             $pathDB = createFolder($number, $commID, 'out');
             faxes_log("notify > Transformando el archivo $file a formato .pdf......\n");
	     $file_name =  ps2pdf($file,$pathDB);
	     if(deletePS2FileFromDocq($file_name));
             faxes_log("deletePS2 > Eliminando archivo $file_name.ps2\n");
              if(!existeFile($arrReg[1])){
		fax_info_insert (str_replace("ps","pdf",$file),$modemdev,$commID,"",$company_name,$number,'out',"sent/$pathDB");	
                faxes_log ("notify  > fax .pdf  en la ruta $faxes_path/sent/$pathDB y se grabo en la BD.");
             }

	     /**********************************************
              *         3) ENVIO EMAIL                     *
              **********************************************/

            $destinatario = obtener_mail_destiny($modemdev);
            $arrConfig    = getConfigurationSendingFaxMail(str_replace("ps","pdf",$file),$company_name,$number);
            $titulo       = $arrConfig['subject'];
            $contenido    = $arrConfig['content'];
            $remite       = $arrConfig['remite'];
            $remitente    = $arrConfig['remitente'];
            $archivo      = "$faxes_path/sent/$pathDB/fax.pdf";
            $archivo_name = str_replace("ps","pdf",$file);

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

function geDocument($file)
{
        $line = `grep '^!postscript' /var/spool/hylafax/doneq/$file`;
	if(eregi("docq/(doc[[:digit:]]+.ps)",$line,$arrReg))
		return trim($arrReg[1]);
	else return "";
}

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
