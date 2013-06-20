<?php
    require_once "config.php";     

    function obtener_nombre($ruta){
            return basename($ruta);
    }	

    //pendiente - En que archivo se va a almacenar aahora los logs???
    function faxes_log ($text, $echo = false) {
        $date=date("YMd_His");
        exec("echo '$date - $text' >> /var/spool/hylafax/hylafax_log");
        if ($echo) echo "$text\n";
    }

    function fax_info_insert ($tiff_file,$modemdev,$commID,$errormsg,$company_name,$company_number,$tipo,$faxpath) {
        global $db_object;
        $id_user=obtener_id_destiny($modemdev);
        
        faxes_log("$tiff_file,$modemdev,$commID,$errormsg,$company_name,$company_number,$tipo,$faxpath ");
        if($id_user != -1){
            try{
                $sql = <<<SQL_INSERT_FAXDOCS
INSERT INTO fax_docs
    (pdf_file, modemdev, commID, errormsg, company_name, company_fax, id_user,
    date,type,faxpath)
VALUES (?, ?, ?, ?, ?, ?, ?, DATETIME('now', 'localtime'), ?, ?)
SQL_INSERT_FAXDOCS;
                $sth = $db_object->prepare($sql);
                $result = $sth->execute(array($tiff_file, $modemdev, $commID, ' ',
                    $company_name, $company_number, $id_user, $tipo, $faxpath));
                if(!$result){
                    faxes_log("ERR: PROBLEMA DE CONEXION - ".print_r($sth->errorInfo(),1)."\n");
                }
            }catch(PDOException $e){
                faxes_log("ERR: PROBLEMA DE CONEXION - $e->getMessage()");
            }
        }else{
            faxes_log("Error al Obtener id de destino");
        }
    }

    function obtener_id_destiny($modemdev)
    {
        global $db_object;
        $id = 0;
        $dev_id = str_replace("ttyIAX","",$modemdev);
        try{
            $sql= "select id_user from user_properties where property='dev_id' and value=?";
            $sth = $db_object->prepare($sql);
            $sth->execute(array($dev_id));
            $result = $sth->fetch(PDO::FETCH_NUM);
            if(count($result)>0){
                $id=$result[0][0];
            }
        }catch(PDOException $e){
            faxes_log("ERR: PROBLEMA DE CONEXION - $e->getMessage()");
        }
        return $id;
    }

    function obtenerDomain($modemdev){
        global $db_object;
        $domain = "";
        $dev_id = str_replace("ttyIAX","",$modemdev);
        try{
            $sql="select o.domain from organization o join acl_group g on o.id=g.id_organization where g.id=(SELECT au.id_group from acl_user au join user_properties up on up.id_user=au.id where up.property='dev_id' and up.value=?)";
            $sth = $db_object->prepare($sql);
            $sth->execute(array($dev_id));
            $result = $sth->fetch(PDO::FETCH_NUM);
            if(count($result)>0){
                $domain=$result[0][0];
            }
        }catch(PDOException $e){
            faxes_log("ERR: PROBLEMA DE CONEXION - $e->getMessage()");
        }
        return $domain;
    }


    function obtener_mail_destiny($modemdev)
    {
        global $db_object;
        $username = "";
        $dev_id = str_replace("ttyIAX","",$modemdev);
        try{
            $sql= "select au.username from acl_user au join user_properties up on au.id=up.id_user where up.property='dev_id' and up.value=?";
            $sth = $db_object->prepare($sql);
            $sth->execute(array($dev_id));
            $result = $sth->fetch(PDO::FETCH_NUM);
            if(count($result)>0){
                $username=$result[0][0];
            }
        }catch(PDOException $e){
            faxes_log("ERR: PROBLEMA DE CONEXION - $e->getMessage()");
        }
        return $username;
    }

    function getConfigurationSendingFaxMail($modemdev,$namePDF,$companyNameFrom,$companyNumberFrom)
    {
        global $db_object;
        $idUser=obtener_id_destiny($modemdev);
        $arrData['remite']="elastix@example.com";
        $arrData['remitente']="Fax Elastix";
        $arrData['subject']="Fax $namePDF";
        $arrData['content']="Fax $namePDF of $companyNameFrom - $companyNumberFrom";
        $id_pdf = explode(".",$namePDF);
        if($idUser!=0 && $idUser!=-1){
            //obtenemos el subject y el content del Faxmail
            foreach(array("fax_subject","fax_content") as $property){
                $sql  = "SELECT value from user_properties where property=$property and id_user=$idUser";
                $recordset = $db_object->query($sql);
                while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){
                    $arrData['subject'] = $tupla->subject;
                    $arrData['subject'] = str_replace("{NAME_PDF}",$id_pdf[0],$arrData['subject']);
                    $arrData['subject'] = str_replace("{COMPANY_NAME_FROM}",$companyNameFrom,$arrData['subject']);
                    $arrData['subject'] = utf8_decode(str_replace("{COMPANY_NUMBER_FROM}",$companyNumberFrom,$arrData['subject']));

                    $arrData['content'] = $tupla->content;
                    $arrData['content'] = str_replace("{NAME_PDF}",$id_pdf[0],$arrData['content']);
                    $arrData['content'] = str_replace("{COMPANY_NAME_FROM}",$companyNameFrom,$arrData['content']);
                    $arrData['content'] = utf8_decode(str_replace("{COMPANY_NUMBER_FROM}",$companyNumberFrom,$arrData['content']));
                }
            }
            //obtenemos el remite y el remitente
            $sql  = "SELECT name,username FROM acl_user WHERE id=$idUser";
            $recordset = $db_object->query($sql);
            while($tupla = $recordset->fetch(PDO::FETCH_OBJ)){
                $arrData['remite']    = utf8_decode($tupla->username);
                $arrData['remitente'] = utf8_decode($tupla->name);
            }
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
    function enviar_mail_adjunto($destinatario="test@example.com",$titulo="Prueba de envio de fax",$contenido="Prueba de envio de fax",$remite="test@example.com",$remitente="Fax",$archivo="/path/archivo.pdf",$archivo_name="archivo.pdf")
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

    function createFolder($number, $commID, $type, $domain)
    {
        global $faxes_path;
        
        // Revisar si directorio base es escribible. Debería ser 775 asterisk.uucp
        $sDirBase = ($type == 'in') ? "$faxes_path/$domain/recvd" : "$faxes_path/$domain/sent";
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
     function ps2pdf($fileps, $pathDB, $i, $domain)
     {
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/docq";
        $tmp_file = basename($fileps, ".ps");
        exec("eps2eps $path/$fileps $path/$tmp_file.ps2",$arrConsole,$flagStatus);
        faxes_log( "eps2eps > Transformando de .ps a .ps2 : $path/$fileps $path/$tmp_file.ps2");        
        exec("ps2pdfwr $path/$tmp_file.ps2 $faxes_path/$domain/sent/$pathDB/fax$i.pdf",$arrConsole,$flagStatus2);
        faxes_log("ps2pdfwr > Transformando de .ps2 a .pdf : $path/$tmp_file.ps2 $faxes_path/sent/$pathDB/fax$i.pdf");
        chmod("$faxes_path/$domain/sent/$pathDB/fax$i.pdf",0666);
        faxes_log("chmod > $faxes_path/$domain/sent/$pathDB/fax$i.pdf , 0666");
        $list_pdf .= "$faxes_path/$domain/sent/$pathDB/fax$i.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.ps) que van a ser combinados: $list_pdf");
        if(deletePS2FileFromDocq($tmp_file))
            faxes_log("deletePS2FileFromDocq > Eliminando archivos temporales .ps2 desde docq/");
        return $list_pdf;
    }
    //convierte de tif a pdf
    function tiff2pdf($tiff_file, $pathDB, $i, $domain)
    {
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/docq";
        $tmp_file=basename($tiff_file,".tif");        
        //tiff2pdf -o docq/fax3.pdf docq/doc29.tif        
        exec("tiff2pdf -o $faxes_path/$domain/sent/$pathDB/fax$i.pdf $path/$tmp_file.tif",$arrConsole,$flagStatus2);
        faxes_log("tiff2pdf -o > Tranformando a fax$i.pdf desde $path/$tmp_file.tif  en la ruta $faxes_path/$domain/sent/$pathDB");        
        chmod("$faxes_path/$domain/sent/$pathDB/fax$i.pdf",0666);
        faxes_log("chmod > $faxes_path/sent/$pathDB/fax$i.pdf,0666");
        $list_pdf .= "$faxes_path/$domain/sent/$pathDB/fax$i.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.tif) que van a ser combinados: $list_pdf");        
        return  $list_pdf;
    }
    
    function tiff2pdf_RCVD($tiff_file, $pathDB, $domain)
    {
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/recvq";
        $tmp_file=basename($tiff_file,".tif");
        //tiff2pdf -o docq/fax3.pdf docq/doc29.tif        
        exec("tiff2pdf -o $faxes_path/$domain/recvd/$pathDB/fax.pdf $path/$tmp_file.tif",$arrConsole,$flagStatus2);
        faxes_log("tiff2pdf -o > Tranformando a fax.pdf desde $path/$tmp_file.tif  en la ruta $faxes_path/$domain/recvd/$pathDB");
        chmod("$faxes_path/$domain/recvd/$pathDB/fax.pdf",0666);
        faxes_log("chmod > $faxes_path/$domain/recvd/$pathDB/fax.pdf,0666");
        $list_pdf .= "$faxes_path/$domain/recvd/$pathDB/fax.pdf"." ";
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
   function pdf2pdf($pdf_file, $pathDB, $i, $domain)//puede cambiar de nombre ya que esta funcion solo copia los pdf adjuntos en la ruta correcta
   {   
        global $faxes_path;
        $list_pdf = "";
        $path = "/var/spool/hylafax/docq";        
        copy("$path/$pdf_file", "$faxes_path/$domain/sent/$pathDB/fax$i.pdf");
        faxes_log("copiando $path/$pdf_file");
        chmod("$faxes_path/$domain/sent/$pathDB/fax$i.pdf",0666);
        $list_pdf .= "$faxes_path/$domain/sent/$pathDB/fax$i.pdf"." ";
        faxes_log ("list_pdf > Lista de archivo(s) .pdf de origen (.pdf) que van a ser combinados: $list_pdf");
        exec("rm -rf $path/$pdf_file");
        faxes_log("rm -rf > Una vez copiado se remueven los archivos pdf de la ruta $path");
        return  $list_pdf;   
   }
    //combina los archivos en un solo pdf llamado fax.pdf
    function finalPdf($files_attach, $path, $domain)
    {
        global $GSCMD, $GS, $faxes_path;
        // create the final PDF
        if (isset($files_attach)) {
            $files_attach = trim($files_attach);
            $cnt = explode(" ", $files_attach);
            if (count($cnt) > 1) {		// if multiple PDFs, combine them
                faxes_log("convert final > convirtiendo....");
                $cmd = sprintf($GSCMD, "$faxes_path/$domain/sent/$path/fax.pdf", $files_attach);
                faxes_log("command > $cmd");
                system($cmd, $retval);
                exec("rm -rf $faxes_path/sent/$path/fax[0-9]*.pdf");
                faxes_log("rm -rf > Eliminando archivos tmp fax[0-9]*.pdf");
                return 0;
            }else {			
                copy($files_attach, "$faxes_path/$domain/sent/$path/fax.pdf");
                faxes_log("copy >  en el caso que sea un solo archivo .pdf el adjunto");
                exec("rm -rf $files_attach");
                faxes_log("rm -rf > Eliminando archivo:  $files_attach");
            }
        }
        return 1;
    }
?>
