<?php

function Obtain_Recordings_Current_User()
{
    global $phpc_root_path;

    $archivos = array();

    global $arrConf;
    $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pDB_acl);
    $username = $_SESSION["elastix_user"];
    $ext = $pACL->getUserExtension($username);
    if($ext)
    {
        $folder_path = "/var/lib/asterisk/sounds/custom";
        $path = "$folder_path/$ext";

        $retval = 0;
        if(!file_exists($path))
        {
            $comando = "mkdir -p $path";
            exec($comando, $output, $retval);
            if ($retval==0){
                $comando = "ln -s $folder_path/calendarEvent.gsm $path/calendarEvent.gsm";
                exec($comando, $output, $retval);
            }
        }

        if(!$retval)
        {
            if ($handle = opendir($path)) {
                while (false !== ($dir = readdir($handle))) {
                    if (ereg("(.*)\.[gsm$|wav$]", $dir, $regs)) {
                        $archivos[$regs[1]] = $regs[1];
                    }
                }
            }
        }
    }
    return $archivos;
}

function Obtain_Protocol_Current_User()
{
    global $arrConf;
    $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pDB_acl);
    $username = $_SESSION["elastix_user"];
    $extension = $pACL->getUserExtension($username);

    if($extension)
    {
        require_once "libs/paloSantoConfig.class.php";
        $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
        $arrConfig = $pConfig->leer_configuracion(false);

        $dsnAsterisk =  $arrConfig['AMPDBENGINE']['valor']."://".
                        $arrConfig['AMPDBUSER']['valor']. ":".
                        $arrConfig['AMPDBPASS']['valor']. "@".
                        $arrConfig['AMPDBHOST']['valor']."/asterisk";

        $pDB = new paloDB($dsnAsterisk);

        $query = "SELECT dial, description, id FROM devices WHERE id=$extension";
        $result = $pDB->getFirstRowQuery($query, TRUE);
        if($result != FALSE)
            return $result;
        else return FALSE;
    }else return FALSE;
}

function Obtain_Protocol($current_user, $extension="")
{
    if($current_user)
    {
        global $arrConf;
        $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
        $pACL = new paloACL($pDB_acl);
        $username = $_SESSION["elastix_user"];
        $extension = $pACL->getUserExtension($username);
    }

    if($extension)
    {
        require_once "libs/paloSantoConfig.class.php";
        $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
        $arrConfig = $pConfig->leer_configuracion(false);

        $dsnAsterisk =  $arrConfig['AMPDBENGINE']['valor']."://".
                        $arrConfig['AMPDBUSER']['valor']. ":".
                        $arrConfig['AMPDBPASS']['valor']. "@".
                        $arrConfig['AMPDBHOST']['valor']."/asterisk";

        $pDB = new paloDB($dsnAsterisk);

        $query = "SELECT dial, description, id FROM devices WHERE id=$extension";
        $result = $pDB->getFirstRowQuery($query, TRUE);
        if($result != FALSE)
            return $result;
        else return FALSE;
    }else return FALSE;
}
?>
