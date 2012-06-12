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

        $sql  = "SELECT remite,remitente,subject,content ".
                "FROM configuration_fax_mail ".
                "WHERE id=1";
        $recordset = $db_object->query($sql);
        while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){
            $arrData['remite'] = utf8_decode($tupla->remite);
            $arrData['remitente'] = utf8_decode($tupla->remitente);
            $arrData['subject'] = utf8_decode(str_replace("{NAME_PDF}",$namePDF,$tupla->subject));
            $arrData['content'] = $tupla->content;
            $arrData['content'] = str_replace("{NAME_PDF}",$namePDF,$arrData['content']);
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

        require_once("phpmailer/class.phpmailer.php");
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

	// -- convert tiff to pdf and check for corruption
       function tiff2pdf ($tiff_file, $pdf)
       {
		global $CONVERT, $TIFFPS, $GSR;

		// start timing how long it takes to convert faxes
		$time_start = microtime(true);

		chmod ($tiff_file, 0666);
		
		// run tiff file through convert in order to remove any weird stuff
		//print "Convert is rotating file $tiff_file to $tiff_file.tif\n";
		system ("$CONVERT -rotate 0 $tiff_file $tiff_file.tif");
		//print "Renaming $tiff_file.tif to $tiff_file\n";
		rename ("$tiff_file.tif", $tiff_file);
                faxes_log ("tiff2pdf> rename $tiff_file.tif to $tiff_file");

		// check for corruption
		if (!faxinfo ($tiff_file, $sender, $pages, $date, $format)) {
			echo "tiff2pdf:  Found corrupted fax\n";
			faxes_log ("tiff2pdf> failed: $tiff_file corrupted");
			exit;
		}
		
		system ("$TIFFPS $tiff_file | $GSR -sOutputFile=$pdf - -c quit 2>/dev/null");

		$time_end = microtime(true);
		chmod ($pdf, 0666);
		
		if (!is_file ($pdf)) { faxes_log ("tiff2pdf> failed to create $pdf"); }
		
		$time = $time_end - $time_start;
		return $time;
	}

      function createFolder($number, $commID, $type)
      {
         $arrDate = getdate();
         global $faxes_path;
         $path = "";
         $day = $arrDate['mday'];
         $month = $arrDate['mon'];
         $year = $arrDate['year'];
         if($type == "in")
           $path="recvd";
         else
           $path="sent";
         exec("sudo chmod 777 -R $faxes_path/$path",$arrConsole,$flagStatus);
         if($flagStatus == 0)
         {
           exec("mkdir -p $faxes_path/$path/$year/$month/$day/$number/$commID/",$arrConsole,$flagStatus2);
           faxes_log("createFolder > Creando carpetas : $faxes_path/$path/$year/$month/$day/$number/$commID");           
           if($flagStatus2 == 0){         
              exec("sudo chmod 777 -R $faxes_path/$path",$arrConsole,$flagStatus);
              faxes_log("sudo chmod 777 -R  > carpetas : $faxes_path/$path/$year/$month/$day/$number/$commID status: $flagStatus");           
             return $year.'/'.$month.'/'.$day.'/'.$number.'/'.$commID;
           }else
             return "Error MKDIR";
         }else
           return "Error CMODD";
      }

     function ps2pdf($fileps, $pathDB)
     {
        global $faxes_path;
        $path = "/var/spool/hylafax/docq";
        $tmp_file=basename($fileps,".ps");
        exec("eps2eps $path/$fileps $path/$tmp_file.ps2",$arrConsole,$flagStatus);
        exec("ps2pdfwr $path/$tmp_file.ps2 $faxes_path/sent/$pathDB/fax.pdf",$arrConsole,$flagStatus2);
        chmod("$faxes_path/sent/$pathDB/fax.pdf",0666);
        faxes_log("ps2pdfwr > Tranformando $path/$tmp_file.ps2 a fax.pdf en la ruta $faxes_path/sent/$pathDB");
        return  $tmp_file;
    }

    function deletePS2FileFromDocq($ps2file)
    {
	$path = "/var/spool/hylafax/docq";
	$file = "$path/$ps2file.ps2";
	return unlink($file);
    }
?>
