<?php
ini_set('include_path', '/var/www/html:'.ini_get('include_path'));
include_once "libs/misc.lib.php";
try{
    $arrDBConn=parseDSN(generarDSNSistema('asteriskuser', 'elxpbx'));
    $db_object = new PDO($arrDBConn["dsn"],$arrDBConn["user"],$arrDBConn["passwd"]);
}
catch (PDOException $e){
    die ($e->getMessage());
}
$RESERVED_FAX_NUM = "XXXXXXX";

// if you need to change the document size (in lowercase)
if (!isset($PAPERSIZE))
   $PAPERSIZE = 'a4';

// tiff
$TIFFPS  = "/usr/bin/tiff2ps -2ap";

// imagemagick
$CONVERT = "/usr/bin/convert"; // a source install may put this in /usr/local/bin/

// ghostscript
$GS    = "/usr/bin/gs";
$GSR   = "$GS -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibility=1.3      -sPAPERSIZE=$PAPERSIZE"; // tiff2pdf (faxrcvd)
$GSCMD = "$GS -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sPAPERSIZE=$PAPERSIZE -dSAFER -sOutputFile=%s  -f %s";

// hylafax
$FAXINFO       = "/usr/sbin/faxinfo";

//ruta de los faxes en elastix
$faxes_path = "/var/www/elastixdir/faxdocs";

function parseDSN($dsn){
    //$dsn => databasemotor://username:password@hostspec/database
    //mysql => mysql://username:password@hostspec/database
    //squlite => sqlite:///database
    $database=$username=$password=$hostspec=$dbname=false;
    //get the technology
    if(($pos = strpos($dsn, '://')) !== false) {
        $database = substr($dsn, 0, $pos);
        $dsn = substr($dsn, $pos + 3);
    } else {
        return array("dsn"=>$dsn,"user"=>$username,"passwd"=>$password);
    }
    //username y password en caso de haberlos
    if (($at = strrpos($dsn,'@')) !== false) {
        $str = substr($dsn, 0, $at);
        $dsn = substr($dsn, $at + 1);
        if (($pos = strpos($str, ':')) !== false) {
            $username = rawurldecode(substr($str, 0, $pos));
            $password = rawurldecode(substr($str, $pos + 1));
        } else {
            $username = rawurldecode($str);
        }
    }
    //hostspec 
    if (strpos($dsn, '/') !== false) {
        list($hostspec, $dbname) = explode('/', $dsn, 2);
    }   
    if($database=="squlite" || $database=="squlite3"){
        $dsn="squlite:$dbname";
    }elseif($database=="mysql"){
        $dsn="$database:dbname=$dbname;host=$hostspec";
    }
    return array("dsn"=>$dsn,"user"=>$username,"passwd"=>$password);
}
?>
