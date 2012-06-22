#!/usr/bin/php
<?php
    require_once "includes/functions.php";
    require_once "includes/config.php";

    /**********************************************
     *         1) OBTENGO INFORMACION             *
     **********************************************/

    // check for proper arguments
    if ($_SERVER['argc'] < 3) {
        echo $_SERVER['argv'][0]." file devID commID error-msg CIDNumber CIDName\n";
        exit;
    }

    $tiff_file = $_SERVER['argv'][1];
    $modemdev  = $_SERVER['argv'][2];
	
    $commID    = ($_SERVER['argc'] >= 4) ? $_SERVER['argv'][3] : "";
    $errormsg  = ($_SERVER['argc'] >= 5) ? $_SERVER['argv'][4] : "";
    $CIDNumber = ($_SERVER['argc'] >= 6) ? $_SERVER['argv'][5] : "";
    $CIDName   = ($_SERVER['argc'] == 7) ? $_SERVER['argv'][6] : "";
    
    faxes_log ("faxrcvd> Obteniendo informacion del tiff ".obtener_nombre($tiff_file)." CIDNumber: ".$CIDNumber." CIDName: ".$CIDName);
   
    // OBTENGO INFORMACION DEL FAX
    if (!faxinfo ($tiff_file, $sender, $pages, $date, $fax_papersize)) {
        faxes_log ("faxrcvd> Failed: $tiff_file $modemdev corrupted");
        exit;
    }
    $company_name = ($CIDName) ? $CIDName : $sender;
    $company_number  = ($CIDNumber) ? clean_faxnum ($CIDNumber) : $sender;
    faxes_log ("faxrcvd> Processing FAX from company_name: $company_name, company_number: $company_number");


    /**********************************************
     *         2) ALMACENO FAX: HD Y DB           *
     **********************************************/
    // copy tiff file to new dir
    $name_pdf = str_replace("tif","pdf",obtener_nombre($tiff_file));
    global $faxes_path;
    $pathDB = createFolder($company_number, $commID, "in");
    $pdffile= "$faxes_path/recvd/$pathDB/fax.pdf";

    //El usuario actual es uucp
    // create pdf in new dir
    tiff2pdf_RCVD($tiff_file, $pathDB);
    // METO EL FAX EN LA BASE DE DATOS?
    fax_info_insert($name_pdf,$modemdev,$commID,$errormsg,$company_name,$company_number,'in',"recvd/$pathDB");
    faxes_log ("faxrcvd> Se copio el $name_pdf en la ruta $faxes_path/recvd/$pathDB y se grabo en la BD.");
    /**********************************************
     *         3) ENVIO EMAIL                     *
     **********************************************/

    $destinatario = obtener_mail_destiny($modemdev);
    $arrConfig    = getConfigurationSendingFaxMail($name_pdf,$company_name,$company_number);
    $titulo       = $arrConfig['subject'];
    $contenido    = $arrConfig['content'];
    $remite       = $arrConfig['remite'];
    $remitente    = $arrConfig['remitente'];
    $archivo      = $pdffile;
    $archivo_name = $name_pdf;

    print_r($arrConfig);
    echo $destinatario;
    enviar_mail_adjunto($destinatario,$titulo,$contenido,$remite,$remitente,$archivo,$archivo_name);

     /*
	recvq/fax000000003.tif (ftp://elastix.palosanto.com:4559/recvq/fax000000003.tif):
	          Sender: 
	           Pages: 1
	         Quality: Normal
	            Size: ISO A4
	        Received: 2007:06:11 16:30:20
	 Time To Receive: 0:38
	     Signal Rate: 9600 bit/s
	     Data Format: 2-D MR
	   Error Correct: No
	         CallID1: 
	         CallID2: 
	     Received On: ttyIAX1
	          CommID: 000000003 (ftp://elastix.palosanto.com:4559/log/c000000003)
    */
?>
