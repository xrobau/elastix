<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2008/05/12 15:55:57 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    global $db, $phpc_root_path, $action;
    /*
    $phpc_root_path gives the location of the base calendar install.
    if you move this file to a new location, modify $phpc_root_path to point
    to the location where the support files for the callendar are located.
    */
    /*ELASTIX*/
    require_once "modules/$module_name/configs/default.conf.php";
    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";


    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);
    $phpc_root_path = "modules/$module_name/";

    require_once('libs/paloSantoDB.class.php');
    $db = new paloDB($arrConf['dsn_conn_database']);
    if(!empty($db->errMsg)) {
        echo "ERROR DE DB: $db->errMsg <br>";
    }
    /*
    You can modify the following defines to change the color scheme of the
    calendar
    */
    define('SEPCOLOR',     '#000000');
    define('BG_COLOR1',    '#FFFFFF');
    define('BG_COLOR2',    'gray');
    define('BG_COLOR3',    'silver');
    define('BG_COLOR4',    '#CCCCCC');
    define('BG_PAST',      'silver');
    define('BG_FUTURE',    'white');
    define('TEXTCOLOR1',   '#000000');
    define('TEXTCOLOR2',   '#FFFFFF');

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    if (isset($_GET['action']) && $_GET['action'] == 'phone_numbers') {
        include_once "libs/paloSantoForm.class.php";
        include_once "modules/address_book/libs/paloSantoAdressBook.class.php";
        include_once "libs/paloSantoGrid.class.php";

        // Include language file for EN, then for local, and merge the two.
        $arrLangModule = NULL;
        include_once("modules/address_book/lang/en.lang");
        $lang_file="modules/address_book/lang/$lang.lang";
        if (file_exists("$base_dir/$lang_file")) {
            $arrLanEN = $arrLangModule;
            include_once($lang_file);
            $arrLangModule = array_merge($arrLanEN, $arrLangModule);
        }
        $arrLang = array_merge($arrLang, $arrLangModule);


        //solo para obtener los devices (extensiones) creadas.
        $dsnAsterisk = generarDSNSistema('asteriskuser', 'asterisk');                           

        $pDB = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/address_book.db");
        $html = report_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $arrConf, $dsnAsterisk);
        
        $smarty->assign("CONTENT", $html);
        $smarty->assign("THEMENAME", $arrConf['mainTheme']);
        $smarty->assign("path", "/");
        return $smarty->fetch("$local_templates_dir/address_book_list.tpl");
    }

    /*
    * Do not modify anything under this point
    */

    define('IN_PHPC', true);

    if(!empty($_GET['action']) && $_GET['action'] == 'style') {
        require_once($phpc_root_path . 'libs/style.php');
        exit;
    }

    require_once($phpc_root_path . 'libs/calendar.php');
    require_once($phpc_root_path . 'libs/setup.php');
    require_once($phpc_root_path . 'libs/globals.php');

    $legal_actions = array( 'event_form', 'event_delete', 'display',
                            'event_submit', 'search');

    if(!in_array($action, $legal_actions, true)) {
        //soft_error(_('Invalid action'));
        global $arrLang;
        require_once "display.php";
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang['Invalid action']);
        return display();
    }

    require_once($phpc_root_path . "libs/$action.php");
    eval("\$output = $action();");

    $calendar = create_xhtml($output);

    
    $smarty->assign("IMG", "/modules/$module_name/images/calendar.gif");

    require_once("libs/paloSantoForm.class.php");
    $oForm = new paloForm($smarty, "");
    $oForm->arrFormElements = array();
    $smarty->assign('calendar', $calendar);
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/calendar.tpl", $arrLang["Calendar"]);
    return $contenidoModulo;
}

function report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $arrConf, $dsnAsterisk)
{
    $pDB_2        = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/acl.db");
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);
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

    $arrFormElements = array(   "field" => array(   "LABEL"                  => $arrLang["Filter"],
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

    $allowSelection = array("name", "telefono", "last_name");
    if(isset($_POST['field']) and isset($_POST['pattern'])){
        $field      = $_POST['field'];
        if (!in_array($field, $allowSelection))
            $field = "name";
        $pattern    = $pDB->DBCAMPO('%'.$_POST['pattern'].'%');
    }

    $startDate = $endDate = date("Y-m-d H:i:s");

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter_adress_book.tpl", "", $_POST);

    $padress_book = new paloAdressBook($pDB);

    if($directory_type=='external')
        $total = $padress_book->getAddressBook(NULL,NULL,$field,$pattern,TRUE, $id_user);
    else
        $total = $padress_book->getDeviceFreePBX($dsnAsterisk, NULL,NULL,$field,$pattern,TRUE);

    $total_datos = $total[0]["total"];
    //Paginacion
    $limit  = 20;
    $total  = $total_datos;

    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    //Fin Paginacion

    if($directory_type=='external')
        $arrResult =$padress_book->getAddressBook($limit, $offset, $field, $pattern,FALSE, $id_user);
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

            $arrTmp[0]  = ($directory_type=='external')?htmlspecialchars($adress_book['last_name'],ENT_QUOTES, "UTF-8")." ".htmlspecialchars($adress_book['name'],ENT_QUOTES, "UTF-8"):$adress_book['description'];
            $number = ($directory_type=='external')?htmlspecialchars($adress_book['telefono'], ENT_QUOTES, "UTF-8"):$adress_book['id'];
            $arrTmp[1]  = "<a href='javascript:return_phone_number(\"$number\", \"$directory_type\", \"{$adress_book['id']}\")'>$number</a>";
            $arrTmp[2]  = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
            $arrData[]  = $arrTmp;
        }
    }
    if($directory_type=='external')
        $name = "<input type='submit' name='delete' value='{$arrLang["Delete"]}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to delete the contact."]}');\" />";
    else $name = "";

    $arrGrid = array(   "title"    => $arrLang["Address Book"],
                        "url"      => array('menu' => $module_name, 'action' => 'phone_numbers', 'rawmode' => 'yes', 'filter' => $pattern),
                        "icon"     => "images/list.png",
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
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    return $contenidoModulo;
}

?>
