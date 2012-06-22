<?php
	require_once "config.php";     

    function obtener_nombre($ruta){
            return basename($ruta);
    }	

	function faxes_log ($text, $echo = false) {
        global $db_object;
		$db_object->query ("INSERT INTO SysLog (logtext,logdate) VALUES ('$text',datetime('now','localtime'))");
		if ($echo) echo "$text\n";
	}

    function fax_info_insert ($tiff_file,$modemdev,$commID,$errormsg,$company_name,$company_number,$tipo,$faxpath) {
        global $db_object;
             
        $id_destiny=obtener_id_destiny($modemdev);
        if($id_destiny != -1)
        {
            $db_object->query("INSERT INTO info_fax_recvq (pdf_file,modemdev,commID,errormsg,company_name,company_fax,fax_destiny_id,date,type,faxpath) ". 
                              "VALUES ('$tiff_file','$modemdev','$commID','$errormsg','$company_name','$company_number',$id_destiny,datetime('now','localtime'),'$tipo','$faxpath')");
        }
        else{
            faxes_log("Error al Obtener id de destino");
        }
    }

	function obtener_id_destiny($modemdev)
	{
		global $db_object;
                $id = 1;
                $dev_id = str_replace("ttyIAX","",$modemdev);
		$sql= "select id from fax where dev_id=$dev_id";
		$recordset =& $db_object->query($sql);
        	while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){ 
			$id = $tupla->id;
		}
		return $id;
	}

	function obtener_modem_destiny($extension)
        {
                global $db_object;
                $dev_id = 1;
                /*$sql= "select dev_id from fax where extension='$extension'";
                $recordset =& $db_object->query($sql);
        	while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){
                        $dev_id = $tupla->dev_id;
                }*/
		$sql="select dev_id from fax where id=1";
                $recordset =& $db_object->query($sql);
                while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){
                        $dev_id = $tupla->dev_id;
                }

                return "ttyIAX$dev_id";
        }

    
    function obtener_mail_destiny($modemdev)
	{
		global $db_object;
                $id = "";
                $dev_id = str_replace("ttyIAX","",$modemdev);
		$sql= "select email from fax where dev_id=$dev_id";
		$recordset = $db_object->query($sql);
        while($tupla = $recordset->fetch(PDO::FETCH_OBJ))
        		$id = $tupla->email;
		return $id;
	}

    function getConfigurationSendingFaxMail($namePDF,$companyNameFrom,$companyNumberFrom)
	{
		global $db_object;
        $arrData['remite']="elastix@example.com";
        $arrData['remitente']="Fax Elastix";
        $arrData['subject']="Fax $namePDF";
        $arrData['content']="Fax $namePDF of $companyNameFrom - $companyNumberFrom";

        $id_pdf = explode(".",$namePDF);

        $sql  = "SELECT remite,remitente,subject,content ".
                "FROM configuration_fax_mail ".
                "WHERE id=1";
        $recordset = $db_object->query($sql);
        while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){
            $arrData['remite']    = utf8_decode($tupla->remite);
            $arrData['remitente'] = utf8_decode($tupla->remitente);

            $arrData['subject'] = $tupla->subject;
            $arrData['subject'] = str_replace("{NAME_PDF}",$id_pdf[0],$arrData['subject']);
            $arrData['subject'] = str_replace("{COMPANY_NAME_FROM}",$companyNameFrom,$arrData['subject']);
            $arrData['subject'] = utf8_decode(str_replace("{COMPANY_NUMBER_FROM}",$companyNumberFrom,$arrData['subject']));

            $arrData['content'] = $tupla->content;
            $arrData['content'] = str_replace("{NAME_PDF}",$id_pdf[0],$arrData['content']);
            $arrData['content'] = str_replace("{COMPANY_NAME_FROM}",$companyNameFrom,$arrData['content']);
            $arrData['content'] = utf8_decode(str_replace("{COMPANY_NUMBER_FROM}",$companyNumberFrom,$arrData['content']));
        }
		return $arrData;
	}

	function clean_faxnum ($fnum)
    {
		if (get_magic_quotes_gpc())
			$fnum = stripslashes($fnum);
	
		$fnum = preg_replace ("/\W/", "", $fnum); // strip non alpha num
		return $fnum;
	}
	
	// return faxinfo from tiff file, return false on error
	function faxinfo ($path, &$sender, &$pages, &$date, &$format)
    {
	    global $FAXINFO, $RESERVED_FAX_NUM;
		
		//  /\s*(\w*): (.*)/
		exec ("$FAXINFO -n $path", $array);
		
		$values = array ();
		
		foreach ($array as $key=>$val) {
            $exp = explode (": ", $val);
            if( count($exp) == 1  )
                $exp[]="";

     	    list ($left, $right) = $exp;
		    $values[trim ($left)] = trim ($right);
		}
		
		if (isset($values['Sender'])) $sender = strtolower (clean_faxnum ($values['Sender']));
		if (isset($values['Pages'])) $pages = $values['Pages'];
		if (isset($values['Received'])) $date = $values['Received'];
		if (isset($values['Page'])) $format = $values['Page'];
		
		if (preg_match ("/unknown/i", $sender) or preg_match ("/unspecified/i", $sender) or !$sender) {
			$sender = $RESERVED_FAX_NUM;
			faxes_log ("faxinfo> XDEBUG CHECK sender '$sender' in faxfile '$path'");
		}
		
		if ($sender && $pages && $date) return true;
		return false;
	}
	
	// enviar_mail_adjunto ()
	function enviar_mail_adjunto($destinatario="test@example.com",$titulo="Prueba de envio de fax",$contenido="Prueba de envio de fax",
                                 $remite="test@example.com",$remitente="Fax",$archivo="/path/archivo.pdf",
                                 $archivo_name="archivo.pdf")
    {

        require_once("/var/www/html/libs/phpmailer/class.phpmailer.php");
	    $mail = new PHPMailer();

        $mail->From = $remite;
        $mail->FromName = $remitente;
        $mail->AddAddress($destinatario);
        $mail->WordWrap = 50;                                 // set word wrap to 50 characters
        $mail->AddAttachment($archivo);
        $mail->IsHTML(false);                                  // set email format to TEXT
            
        $mail->Subject = $titulo;
        $mail->Body    = $contenido;
        $mail->AltBody = "This is the body in plain text for non-HTML mail clients";
            
        // envio del mensaje
        if($mail->Send())
            faxes_log ("enviar_mail_adjunto> SE envio correctamenete el mail ".$titulo);
        else 
            faxes_log ("enviar_mail_adjunto> Error al enviar el mail ".$titulo);
    }

    function createFolder($number, $commID, $type)
    {
    	global $faxes_path;
        
        // Revisar si directorio base es escribible. Debería ser 775 asterisk.uucp
        $sDirBase = ($type == 'in') ? "$faxes_path/recvd" : "$faxes_path/sent";
        if (!is_writable($sDirBase)) {
            faxes_log("createFolder > ruta $sDirBase no se puede escribir");
            return NULL;
        }
        
        // Ruta final se construye con la fecha del sistema
        $fecha = getdate();
        $sRutaFinal = "{$fecha['year']}/{$fecha['mon']}/{$fecha['mday']}/$number/$commID";
        $umaskAnterior = umask(0);
        $bExito = mkdir("$sDirBase/$sRutaFinal", 0777, TRUE);
        umask($umaskAnterior);
        if ($bExito) {
            faxes_log("createFolder > ruta $sRutaFinal creada correctamente");
        	return $sRutaFinal;
        } else {
            faxes_log("createFolder > ruta $sRutaFinal no puede crearse");
        	return NULL;
        }
    }

     //convierte de  ps a ps2  y de ps2 a pdf
     function ps2pdf($fileps, $pathDB, $i)
     {
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/docq";
        $tmp_file = basename($fileps, ".ps");
        exec("eps2eps $path/$fileps $path/$tmp_file.ps2",$arrConsole,$flagStatus);
        faxes_log( "eps2eps > Transformando de .ps a .ps2 : $path/$fileps $path/$tmp_file.ps2");        
        exec("ps2pdfwr $path/$tmp_file.ps2 $faxes_path/sent/$pathDB/fax$i.pdf",$arrConsole,$flagStatus2);
        faxes_log("ps2pdfwr > Transformando de .ps2 a .pdf : $path/$tmp_file.ps2 $faxes_path/sent/$pathDB/fax$i.pdf");
        chmod("$faxes_path/sent/$pathDB/fax$i.pdf",0666);
        faxes_log("chmod > $faxes_path/sent/$pathDB/fax$i.pdf , 0666");
        $list_pdf .= "$faxes_path/sent/$pathDB/fax$i.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.ps) que van a ser combinados: $list_pdf");
        if(deletePS2FileFromDocq($tmp_file))
            faxes_log("deletePS2FileFromDocq > Eliminando archivos temporales .ps2 desde docq/");
        return $list_pdf;
    }
    //convierte de tif a pdf
    function tiff2pdf($tiff_file, $pathDB, $i)
    {
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/docq";
        $tmp_file=basename($tiff_file,".tif");        
        //tiff2pdf -o docq/fax3.pdf docq/doc29.tif        
        exec("tiff2pdf -o $faxes_path/sent/$pathDB/fax$i.pdf $path/$tmp_file.tif",$arrConsole,$flagStatus2);
        faxes_log("tiff2pdf -o > Tranformando a fax$i.pdf desde $path/$tmp_file.tif  en la ruta $faxes_path/sent/$pathDB");        
        chmod("$faxes_path/sent/$pathDB/fax$i.pdf",0666);
        faxes_log("chmod > $faxes_path/sent/$pathDB/fax$i.pdf,0666");
        $list_pdf .= "$faxes_path/sent/$pathDB/fax$i.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.tif) que van a ser combinados: $list_pdf");        
        return  $list_pdf;
    }
    
    function tiff2pdf_RCVD($tiff_file, $pathDB)
    {
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/recvq";
        $tmp_file=basename($tiff_file,".tif");
        //tiff2pdf -o docq/fax3.pdf docq/doc29.tif        
        exec("tiff2pdf -o $faxes_path/recvd/$pathDB/fax.pdf $path/$tmp_file.tif",$arrConsole,$flagStatus2);
        faxes_log("tiff2pdf -o > Tranformando a fax.pdf desde $path/$tmp_file.tif  en la ruta $faxes_path/recvd/$pathDB");
        chmod("$faxes_path/recvd/$pathDB/fax.pdf",0666);
        faxes_log("chmod > $faxes_path/recvd/$pathDB/fax.pdf,0666");
        $list_pdf .= "$faxes_path/recvd/$pathDB/fax.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.tif) que van a ser combinados: $list_pdf");
        return  $list_pdf;
    }
    

    //elimana archivos tmp .ps2
    function deletePS2FileFromDocq($ps2file)
    {
	$path = "/var/spool/hylafax/docq";
        $file = "$path/$ps2file.ps2";
	if(unlink($file))
              return true;
    }
   //copia archivos pdf desde docq/ hacia faxes/2009/xxx/xxx/xxx/9999999/
   function pdf2pdf($pdf_file, $pathDB, $i)//puede cambiar de nombre ya que esta funcion solo copia los pdf adjuntos en la ruta correcta
   {   
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/docq";        
        copy("$path/$pdf_file", "$faxes_path/sent/$pathDB/fax$i.pdf");
        faxes_log("copiando $path/$pdf_file");
        chmod("$faxes_path/sent/$pathDB/fax$i.pdf",0666);
        $list_pdf .= "$faxes_path/sent/$pathDB/fax$i.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.pdf) que van a ser combinados: $list_pdf");
        exec("rm -rf $path/$pdf_file");
        faxes_log("rm -rf > Una vez copiado se remueven los archivos pdf de la ruta $path");
        return  $list_pdf;   
   }
    //combina los archivos en un solo pdf llamado fax.pdf
    function finalPdf($files_attach, $path)
    {
        global $GSCMD, $GS, $faxes_path;
        // create the final PDF
	if (isset($files_attach)) {
		$files_attach = trim($files_attach);
		$cnt = explode(" ", $files_attach);
		
		if (count($cnt) > 1) {		// if multiple PDFs, combine them
			faxes_log("convert final > convirtiendo....");
			$cmd = sprintf($GSCMD, "$faxes_path/sent/$path/fax.pdf", $files_attach);
                        faxes_log("command > $cmd");
		        system($cmd, $retval);
                        exec("rm -rf $faxes_path/sent/$path/fax[0-9]*.pdf");
                        faxes_log("rm -rf > Eliminando archivos tmp fax[0-9]*.pdf");
		        return 0;
			
		}else {			
			copy($files_attach, "$faxes_path/sent/$path/fax.pdf");
                        faxes_log("copy >  en el caso que sea un solo archivo .pdf el adjunto");
                        exec("rm -rf $files_attach");
                        faxes_log("rm -rf > Eliminando archivo:  $files_attach");
		}
	}
        return 1;

    }
?>
