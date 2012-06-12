<?php
global $path, $template_module, $module_calendar;
$path = "/var/www/html";
//$path = "";
$module_name = "address_book";
$module_calendar = "calendar";

include_once("$path/libs/misc.lib.php");
include_once "$path/configs/default.conf.php";

// Load smarty
require_once("$path/libs/smarty/libs/Smarty.class.php");
$smarty = new Smarty();

$smarty->template_dir = "$path/themes/" . $arrConf['mainTheme'];
$smarty->compile_dir =  "$path/var/templates_c/";
$smarty->config_dir =   "$path/configs/";
$smarty->cache_dir =    "$path/var/cache/";
//$smarty->debugging =    true;

$html = _moduleContent($smarty, $module_name);
$smarty->assign("CONTENT", $html);
$smarty->assign("THEMENAME", $arrConf['mainTheme']);
$smarty->assign("path", "../../../");
$smarty->display("$path/modules/$module_calendar/$template_module/address_book_list.tpl");
	
function _moduleContent(&$smarty, $module_name)
{
	global $path, $template_module, $module_calendar;
	//include elastix framework
	include_once "$path/libs/paloSantoGrid.class.php";
	include_once "$path/libs/paloSantoValidar.class.php";
	include_once "$path/libs/paloSantoConfig.class.php";
	include_once "$path/libs/misc.lib.php";
	include_once "$path/libs/paloSantoForm.class.php";
	
	//include module files
    include_once "$path/modules/$module_name/configs/default.conf.php";
    include_once "$path/modules/$module_name/libs/paloSantoAdressBook.class.php";
    global $arrConf;
    load_language("../../../");
    global $arrLang;
	
	//folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$path/modules/$module_calendar/".$templates_dir.'/'.$arrConf['theme'];

    $template_module = $templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);
	
    $dsn_agi_manager['password'] = $arrConfig['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrConfig['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = 'admin';
	
	//solo para obtener los devices (extensiones) creadas.
    $dsnAsterisk = $arrConfig['AMPDBENGINE']['valor']."://".
                   $arrConfig['AMPDBUSER']['valor']. ":".
                   $arrConfig['AMPDBPASS']['valor']. "@".
                   $arrConfig['AMPDBHOST']['valor']."/asterisk";

	$pDB = new paloDB("sqlite3:////var/www/db/address_book.db");

    $action = getAction();
	
	$content = "";
    switch($action)
    {
        default:
            $content = report_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk);
            break;
	}
	
	return $content;
}

function report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk)
{
    if(isset($_POST['select_directory_type']) && $_POST['select_directory_type']=='External')
    {
        $smarty->assign("external_sel",'selected=selected');
        $directory_type = 'external';
    }
    else{
        $smarty->assign("internal_sel",'selected=selected');
        $directory_type = 'internal';
    }

    $arrComboElements = array(  "name"        =>$arrLang["Name"],
                                "telefono"    =>$arrLang["Phone Number"]);

    if($directory_type=='external')
        $arrComboElements["last_name"] = $arrLang["Last Name"];

    $arrFormElements = array(   "field" => array(   "LABEL"                  => "filtro",//$arrLang["Filter"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrComboElements,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),

                                "pattern" => array( "LABEL"          => "",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "INPUT_EXTRA_PARAM"      => ""),
                                );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SHOW", $arrLang["Show"]);
    $smarty->assign("CSV", $arrLang["CSV"]);
    $smarty->assign("module_name", $module_name);

    $smarty->assign("Phone_Directory",$arrLang["Phone Directory"]);
    $smarty->assign("Internal",$arrLang["Internal"]);
    $smarty->assign("External",$arrLang["External"]);

    $field   = NULL;
    $pattern = NULL;

    if(isset($_POST['field']) and isset($_POST['pattern'])){
        $field      = $_POST['field'];
        $pattern    = $_POST['pattern'];
    }

    $startDate = $endDate = date("Y-m-d H:i:s");

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter_adress_book.tpl", "", $_POST);

    $padress_book = new paloAdressBook($pDB);

    if($directory_type=='external')
        $total = $padress_book->getAddressBook(NULL,NULL,$field,$pattern,TRUE);
    else
        $total = $padress_book->getDeviceFreePBX($dsnAsterisk, NULL,NULL,$field,$pattern,TRUE);

    $total_datos = $total[0]["total"];
    //Paginacion
    $limit  = 20;
    $total  = $total_datos;

    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $url = "?menu=$module_name&filter=$pattern";
    $smarty->assign("url", $url);
    //Fin Paginacion

    if($directory_type=='external')
        $arrResult =$padress_book->getAddressBook($limit, $offset, $field, $pattern);
    else
        $arrResult =$padress_book->getDeviceFreePBX($dsnAsterisk, $limit,$offset,$field,$pattern);

    $arrData = null;
    if(is_array($arrResult) && $total>0){
        $arrMails = array();

        if($directory_type=='internal')
            $arrMails = $padress_book->getMailsFromVoicemail();

        foreach($arrResult as $key => $adress_book){
            if($directory_type=='external')
                $email = $adress_book['email'];
            else if(isset($arrMails[$adress_book['id']]))
                $email = $arrMails[$adress_book['id']];
            else $email = '';

            $arrTmp[0]  = ($directory_type=='external')?"{$adress_book['last_name']} {$adress_book['name']}":$adress_book['description'];
            $number = ($directory_type=='external')?$adress_book['telefono']:$adress_book['id'];
            $arrTmp[1]  = "<a href='javascript:return_phone_number(\"$number\", \"$directory_type\", \"{$adress_book['id']}\")'>$number</a>";
            $arrTmp[2]  = $email;
            $arrData[]  = $arrTmp;
        }
    }
    if($directory_type=='external')
        $name = "<input type='submit' name='delete' value='{$arrLang["Delete"]}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to delete the contact."]}');\" />";
    else $name = "";

    $arrGrid = array(   "title"    => $arrLang["Address Book"],
                        "icon"     => "../../../images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "columns"  => array(0 => array("name"      => $arrLang["Name"],
                                                    "property1" => ""),
                                            1 => array("name"      => $arrLang["Phone Number"],
                                                    "property1" => ""),
                                            2=> array("name"      => $arrLang["Email"],
                                                    "property1" => ""),
                                        )
                    );

    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = "<form method='post' style='margin-bottom: 0pt;' action='?menu=$module_name'>".$oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";
    return $contenidoModulo;
}

function getAction()
{
	return "report";
}
?>
