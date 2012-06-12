<?php
include_once("../../../libs/misc.lib.php");
include_once("../../../libs/paloSantoDB.class.php");
require_once("../../../libs/smarty/libs/Smarty.class.php");
include_once("../../../libs/paloSantoConfig.class.php");
include_once "paloSantoAdressBook.class.php";

load_language('../../../');

download_address_book();

function download_address_book()
{
    global $arrLang;
    $smarty = getSmarty();

    $pDB = new paloDB("sqlite3:////var/www/db/address_book.db");
    if(!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", $arrLang["Error when connecting to database"]."<br/>".$pDB->errMsg);
    }

    header("Cache-Control: private");
    header("Pragma: cache");
    header('Content-Type: text/csv; charset=iso-8859-1; header=present');
    header("Content-disposition: attachment; filename=address_book.csv");
    echo backup_contacts($pDB, $smarty);
}

function backup_contacts($pDB, $smarty)
{
    $Messages = "";
    $csv = "";
    $pAdressBook = new paloAdressBook($pDB);
    $fields = "name, last_name, telefono, email";
    $arrResult = $pAdressBook->getAddressBook(null, null, $fields, null);

    if(!$arrResult)
    {
        $Messages .= $arrLang["There aren't contacts"].". ".$pAdressBook->errMsg."<br />";
    }else{
        //cabecera
        $csv .= "\"Name\",\"Last Name\",\"Phone Number\",\"Email\"\n";
        foreach($arrResult as $key => $contact)
        {
            $csv .= "\"{$contact['name']}\",\"{$contact['last_name']}\",".
                    "\"{$contact['telefono']}\",\"{$contact['email']}\"".
                    "\n";
        }
    }
    $smarty->assign("mb_message", $Messages);
    return $csv;
}

function getSmarty() {
    global $arrConf;
    $smarty = new Smarty();
    $smarty->template_dir = "themes/".$arrConf['mainTheme']."/";
    $smarty->compile_dir =  "var/templates_c/";
    $smarty->config_dir =   "configs/";
    $smarty->cache_dir =    "var/cache/";
    return $smarty;
}

?>