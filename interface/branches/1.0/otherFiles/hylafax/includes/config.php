<?php	
	require_once 'DB.php';
	dl('sqlite3.so');
    	$sConnStr = "sqlite3:////var/www/db/fax.db";
    	$db_object = DB::connect($sConnStr);	
    	if (DB::isError($db_object))
        	die ($db_object->getMessage());  	
 
        $RESERVED_FAX_NUM = "XXXXXXX";
        // if you need to change the document size (in lowercase)
	if (!isset($PAPERSIZE))
		$PAPERSIZE = 'a4';
	// tiff
	$TIFFPS        = "/usr/bin/tiff2ps -2ap";
	// imagemagick
	$CONVERT       = "/usr/bin/convert"; // a source install may put this in /usr/local/bin/
	// ghostscript
	$GS	       = "/usr/bin/gs";
        $GSR	       = "$GS -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibility=1.3 -sPAPERSIZE=$PAPERSIZE"; // tiff2pdf (faxrcvd)
        // hylafax
	$FAXINFO       = "/usr/sbin/faxinfo";

        //ruta de los faxes en elastic
        $faxes_path = "/var/www/html/faxes";
?>
