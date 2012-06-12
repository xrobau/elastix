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
  $Id: index.php,v 1.1 2008/01/30 15:55:57 bmacias Exp $
  $Id: index.php,v 1.1 2008/06/25 16:51:50 afigueroa Exp $
 */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAdressBook.class.php";
    global $arrConf;
    global $arrLang;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

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
        case "new":
            $content = new_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang);
            break;
        case "cancel":
            header("Location: ?menu=$module_name");
            break;
        case "commit":
            $content = save_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang,true);
            break;
        case "edit":
            $content = view_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang);
            break;
        case "show":
            $content = view_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang);
            break;
        case "save":
            if($_POST['address_book_options']=="address_from_csv")
                $content = save_csv($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk);
            else
                $content = save_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang);
            break;
        case "delete":
            $content = deleteContact($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk);
            break;
        case "call2phone":
            $content = call2phone($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        default:
            $content = report_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk);
            break;
    }

    return $content;
}

function save_csv($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk)
{
    //valido el tipo de archivo
    if (!eregi('.csv$', $_FILES['userfile']['name'])) {
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang["Invalid file extension.- It must be csv"]);
    }else {
        if(is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            $ruta_archivo = "/tmp/".$_FILES['userfile']['name'];
            copy($_FILES['userfile']['tmp_name'], $ruta_archivo);
            //Funcion para cargar las extensiones
            load_address_book_from_csv($smarty, $arrLang, $ruta_archivo, $pDB);
        }else {
            $smarty->assign("mb_title", $arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." :". $_FILES['userfile']['name']);
        }
    }
    $content = new_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang);
    return $content;
}

function load_address_book_from_csv($smarty, $arrLang, $ruta_archivo, $pDB)
{
    $Messages = "";
    $arrayColumnas = array();

    $result = isValidCSV($arrLang, $ruta_archivo, $arrayColumnas);
    if($result != 'true'){
        $smarty->assign("mb_message", $result);
        return;
    }

    $hArchivo = fopen($ruta_archivo, 'rt');
    $cont = 0;
    $pAdressBook = new paloAdressBook($pDB);

    if ($hArchivo) {
        //Linea 1 header ignorada
        $tupla = fgetcsv($hArchivo, 4096, ",");
        //Desde linea 2 son datos
        while ($tupla = fgetcsv($hArchivo, 4096, ","))
        {
            if(is_array($tupla) && count($tupla)>=3)
            {
                $data = array();

                $data['name']       = $pDB->DBCAMPO($tupla[$arrayColumnas[0]]);
                $data['last_name']  = $pDB->DBCAMPO($tupla[$arrayColumnas[1]]);
                $data['telefono']   = $pDB->DBCAMPO($tupla[$arrayColumnas[2]]);

                $Email = isset($arrayColumnas[3])?$tupla[$arrayColumnas[3]]:"";
                $data['email']      = $pDB->DBCAMPO($Email);

                //Paso 1: verificar que no exista un usuario con los mismos datos
                $result = $pAdressBook->existContact($data);
                if(!$result)
                    $Messages .= "{$arrLang["ERROR"]}:" . $pAdressBook->errMsg . "  <br />";
                else if($result['total']>0)
                    $Messages .= "{$arrLang["ERROR"]}: {$arrLang["Contact Data already exists"]}: {$data['name']} <br />";
                else{
                    //Paso 2: creando en la contact data
                    if(!$pAdressBook->addContact($data))
                        $Messages .= $arrLang["ERROR"] . $pDB->errMsg . "<br />";

                    $cont++;
                }
            }
        }

        $Messages .= $arrLang["Total contacts created"].": $cont<br />";
        $smarty->assign("mb_message", $Messages);
    }

    unlink($ruta_archivo);
}

function isValidCSV($arrLang, $sFilePath, &$arrayColumnas){
    $hArchivo = fopen($sFilePath, 'rt');
    $cont = 0;
    $ColName = -1;

    //Paso 1: Obtener Cabeceras (Minimas las cabeceras: Display Name, User Extension, Secret)
    if ($hArchivo) {
        $tupla = fgetcsv($hArchivo, 4096, ",");
        if(count($tupla)>=3)
        {
            for($i=0; $i<count($tupla); $i++)
            {
                if($tupla[$i] == 'Name')
                    $arrayColumnas[0] = $i;
                else if($tupla[$i] == 'Last Name')
                    $arrayColumnas[1] = $i;
                else if($tupla[$i] == 'Phone Number')
                    $arrayColumnas[2] = $i;
                else if($tupla[$i] == 'Email')
                    $arrayColumnas[3] = $i;
            }
            if(isset($arrayColumnas[0]) && isset($arrayColumnas[1]) && isset($arrayColumnas[2]))
            {
                //Paso 2: Obtener Datos (Validacion que esten llenos los mismos de las cabeceras)
                $count = 2;
                while ($tupla = fgetcsv($hArchivo, 4096,","))
                {
                    if(is_array($tupla) && count($tupla)>=3)
                    {
                            $Name           = $tupla[$arrayColumnas[0]];
                            if($Name == '')
                                return $arrLang["Can't exist a Name empty. Line"].": $count. - ". $arrLang["Please read the lines in the footer"];

                            $LastName       = $tupla[$arrayColumnas[1]];
                            if($LastName == '')
                                return $arrLang["Can't exist a Last Name empty. Line"].": $count. - ". $arrLang["Please read the lines in the footer"];

                            $PhoneNumber    = $tupla[$arrayColumnas[2]];
                            if($PhoneNumber == '')
                                return $arrLang["Can't exist a Phone Number empty. Line"].": $count. - ". $arrLang["Please read the lines in the footer"];
                    }
                    $count++;
                }
                return true;
            }else return $arrLang["Verify the header"] ." - ". $arrLang["At minimum there must be the columns"].": \"Name\", \"Last Name\", \"Phone Number\"";
        }else return $arrLang["Verify the header"] ." - ". $arrLang["Incomplete Columns"];
    }else return $arrLang["The file is incorrect or empty"] .": $sFilePath";
}

function new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang)
{
    $arrFormadress_book = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormadress_book);

    $smarty->assign("Show", 1);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("TITLE", $arrLang["Address Book"]);

    $smarty->assign("new_contact", $arrLang["New Contact"]);
    $smarty->assign("address_from_csv", $arrLang["Address Book from CSV"]);

    if(isset($_POST['address_book_options']) && $_POST['address_book_options']=='address_from_csv')
        $smarty->assign("check_csv", "checked");
    else $smarty->assign("check_new_contact", "checked");

    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("label_file", $arrLang["File"]);
    $smarty->assign("DOWNLOAD", $arrLang["Download Address Book"]);
    $smarty->assign("HeaderFile", $arrLang["Header File Address Book"]);
    $smarty->assign("AboutContacts", $arrLang["About Address Book"]);
    $smarty->assign("LINK", "modules/$module_name/libs/download_csv.php");


    $padress_book = new paloAdressBook($pDB);

    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_adress_book.tpl", "", $_POST);

    $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

/*
******** Funciones del modulo
*/
function report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk)
{
    if(getParametro('select_directory_type') != null && getParametro('select_directory_type')=='external')
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
                                                    "INPUT_EXTRA_PARAM"      => array('onKeyPress' => 'handleEnter(this, event)')),
                                );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SHOW", $arrLang["Show"]);
    $smarty->assign("NEW_adress_book", $arrLang["New Contact"]);
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

    $url = "?menu=$module_name&filter=$pattern&select_directory_type=$directory_type";
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

            $arrTmp[0]  = ($directory_type=='external')?"<input type='checkbox' name='contact_{$adress_book['id']}'  />":'';
            $arrTmp[1]  = ($directory_type=='external')?"<a href='?menu=$module_name&action=show&id=".$adress_book['id']."'>{$adress_book['last_name']} {$adress_book['name']}</a>":$adress_book['description'];
            $arrTmp[2]  = ($directory_type=='external')?$adress_book['telefono']:$adress_book['id'];
            $arrTmp[3]  = $email;
            $arrTmp[4]  = "<a href='?menu=$module_name&action=call2phone&id=".$adress_book['id']."&type=".$directory_type."'><img border=0 src='images/call.png' /></a>";
            $arrData[]  = $arrTmp;
        }
    }
    if($directory_type=='external')
        $name = "<input type='submit' name='delete' value='{$arrLang["Delete"]}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to delete the contact."]}');\" />";
    else $name = "";

    $arrGrid = array(   "title"    => $arrLang["Address Book"],
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "columns"  => array(0 => array("name"      => $name,
                                                    "property1" => ""),
                                            1 => array("name"      => $arrLang["Name"],
                                                    "property1" => ""),
                                            2 => array("name"      => $arrLang["Phone Number"],
                                                    "property1" => ""),
                                            3=> array("name"      => $arrLang["Email"],
                                                    "property1" => ""),
                                            4=> array("name"      => $arrLang["Call"],
                                                    "property1" => "")
                                        )
                    );

    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = "<form method='post' style='margin-bottom: 0pt;' action='?menu=$module_name'>".$oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";
    return $contenidoModulo;
}

function createFieldForm($arrLang)
{
    $arrFields = array(
                "name"          => array(   "LABEL"                 => $arrLang["Name"],
                                            "REQUIRED"              => "yes",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => array("style" => "width:300px;"),
                                            "VALIDATION_TYPE"       => "text",
                                            "VALIDATION_EXTRA_PARAM"=> ""),
                "last_name"     => array(   "LABEL"                 => $arrLang["Last Name"],
                                            "REQUIRED"              => "yes",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => array("style" => "width:300px;"),
                                            "VALIDATION_TYPE"       => "text",
                                            "VALIDATION_EXTRA_PARAM"=> ""),
                "telefono"      => array(   "LABEL"                 => $arrLang["Phone Number"],
                                            "REQUIRED"              => "yes",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => "",
                                            "VALIDATION_TYPE"       => "ereg",
                                            "VALIDATION_EXTRA_PARAM"=> "^[[:digit:]]*$"),
                "email"         => array(   "LABEL"                 => $arrLang["Email"],
                                            "REQUIRED"              => "no",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => "",
                                            "VALIDATION_TYPE"       => "ereg",
                                            "VALIDATION_EXTRA_PARAM"=> "([[:alnum:]]|.|_|-){1,}@([[:alnum:]]|.|_|-){1,}"),
                );
    return $arrFields;
}

function save_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang,$update=FALSE)
{
    $arrForm = createFieldForm($arrLang);
    $oForm = new paloForm($smarty, $arrForm);

    $bandera = true;

    if(!$oForm->validateForm($_POST)) {
        // Falla la validación básica del formulario
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
        }

        $smarty->assign("mb_message", $strErrorMsg);

        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $smarty->assign("SAVE", $arrLang["Save"]);
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("TITLE", $arrLang["Address Book"]);

        $smarty->assign("new_contact", $arrLang["New Contact"]);
        $smarty->assign("address_from_csv", $arrLang["Address Book from CSV"]);

        if(isset($_POST['address_book_options']) && $_POST['address_book_options']=='address_from_csv')
            $smarty->assign("check_csv", "checked");
        else $smarty->assign("check_new_contact", "checked");

        $smarty->assign("SAVE", $arrLang["Save"]);
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $smarty->assign("label_file", $arrLang["File"]);
        $smarty->assign("DOWNLOAD", $arrLang["Download Address Book"]);
        $smarty->assign("HeaderFile", $arrLang["Header File Address Book"]);
        $smarty->assign("AboutContacts", $arrLang["About Address Book"]);
        $smarty->assign("LINK", "modules/$module_name/libs/download_csv.php");

        if($update)
        {
            $_POST["edit"] = 'edit';
            return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang);
        }else{
            $smarty->assign("Show", 1);
            $htmlForm = $oForm->fetchForm("$local_templates_dir/new_adress_book.tpl", $arrLang, $_POST);
            $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
            return $contenidoModulo;
        }
    }else{
        $data = array();

        $data['name']       = $pDB->DBCAMPO($_POST['name']);
        $data['last_name']  = $pDB->DBCAMPO($_POST['last_name']);
        $data['telefono']   = $pDB->DBCAMPO($_POST['telefono']);
        $data['email']      = $pDB->DBCAMPO($_POST['email']);

        $padress_book = new paloAdressBook($pDB);
        if($update)
            $result = $padress_book->updateContact($data,array("id"=>$_POST['id']));
        else
            $result = $padress_book->addContact($data);

        if(!$result)
            return($pDB->errMsg);

        //'?menu=$module_name&action=show&id=".$adress_book['id']."'
        if($_POST['id'])
                header("Location: ?menu=$module_name&action=show&id=".$_POST['id']);
        else
            header("Location: ?menu=$module_name");
    }
}

function deleteContact($smarty, $module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk)
{
    $padress_book = new paloAdressBook($pDB);

    foreach($_POST as $key => $values){
        if(substr($key,0,8) == "contact_")
        {
            $tmpBookID = substr($key, 8);
            $result = $padress_book->deleteContact($tmpBookID);
        }
    }
    $content = report_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk);

    return $content;
}

function view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $arrLang)
{
    $arrFormadress_book = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormadress_book);

    if(isset($_POST["edit"])){
        $oForm->setEditMode();
        $smarty->assign("Commit", 1);
        $smarty->assign("SAVE",$arrLang["Save"]);
    }else{
        $oForm->setViewMode();
        $smarty->assign("Edit", 1);
    }

    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("TITLE", $arrLang["Address Book"]);
    $smarty->assign("FirstName",$arrLang["First Name"]);
    $smarty->assign("LastName",$arrLang["Last Name"]);
    $smarty->assign("PhoneNumber",$arrLang["Phone Number"]);
    $smarty->assign("Email",$arrLang["Email"]);

    if(isset($_POST['address_book_options']) && $_POST['address_book_options']=='address_from_csv')
        $smarty->assign("check_csv", "checked");
    else $smarty->assign("check_new_contact", "checked");

    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("label_file", $arrLang["File"]);
    $smarty->assign("DOWNLOAD", $arrLang["Download Address Book"]);
    $smarty->assign("HeaderFile", $arrLang["Header File Address Book"]);
    $smarty->assign("AboutContacts", $arrLang["About Address Book"]);
    $smarty->assign("LINK", "modules/$module_name/libs/download_csv.php");

    $smarty->assign("style_address_options", "style='display:none'");

    $padress_book = new paloAdressBook($pDB);
    $id = isset($_GET['id'])?$_GET['id']:(isset($_POST['id'])?$_POST['id']:"");

    $contactData = $padress_book->contactData($id);

    $smarty->assign("ID",$id);

    $arrData['name']          = isset($_POST['name'])?$_POST['name']:$contactData['name'];
    $arrData['last_name']     = isset($_POST['last_name'])?$_POST['last_name']:$contactData['last_name'];
    $arrData['telefono']      = isset($_POST['telefono'])?$_POST['telefono']:$contactData['telefono'];
    $arrData['email']         = isset($_POST['email'])?$_POST['email']:$contactData['email'];

    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_adress_book.tpl", "", $arrData);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function call2phone($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $arrConf, $dsn_agi_manager)
{
    require_once "libs/paloSantoACL.class.php";
    require_once "libs/paloSantoConfig.class.php";

    $padress_book = new paloAdressBook($pDB);

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsnAsterisk = $arrConfig['AMPDBENGINE']['valor']."://".
                   $arrConfig['AMPDBUSER']['valor']. ":".
                   $arrConfig['AMPDBPASS']['valor']. "@".
                   $arrConfig['AMPDBHOST']['valor']."/asterisk";


    $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);

    $pACL = new paloACL($pDB_acl);
    $id_user = $pACL->getIdUser($_SESSION["elastix_user"]);
    if($id_user != FALSE)
    {
        $user = $pACL->getUsers($id_user);
        if($user != FALSE)
        {
            $extension = $user[0][3];
            if($extension != "")
            {
                $id = isset($_GET['id'])?$_GET['id']:(isset($_POST['id'])?$_POST['id']:"");

                $phone2call = '';
                if(isset($_GET['type']) && $_GET['type']=='external')
                {
                    $contactData = $padress_book->contactData($id);
                    $phone2call = $contactData['telefono'];
                }else
                    $phone2call = $id;

                $result = $padress_book->Obtain_Protocol_from_Ext($dsnAsterisk, $extension);
                if($result != FALSE)
                {
                    $result = $padress_book->Call2Phone($dsn_agi_manager, $extension, $phone2call, $result['dial'], $result['description']);
                    if(!$result)
                    {
                        $smarty->assign("mb_title", $arrLang['ERROR'].":");
                        $smarty->assign("mb_message", $arrLang["The call couldn't be realized"]);
                    }
                }
                else {
                    $smarty->assign("mb_title", $arrLang["Validation Error"]);
                    $smarty->assign("mb_message", $padress_book->errMsg);
                }
            }
            else {
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", $arrLang["You don't have extension number associated with user"]);
            }
        }
        else{
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $smarty->assign("mb_message", $padress_book->errMsg);
        }
    }
    else{
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $padress_book->errMsg);
    }

    $content = report_adress_book($smarty,$module_name, $local_templates_dir, $pDB, $arrLang, $dsnAsterisk);
    return $content;
}
/*
******** Fin
*/

function getAction()
{
    if(getParametro("edit"))
        return "edit"; 
    else if(getParametro("commit"))
        return "commit";
    else if(getParametro("show"))
        return "show";
    else if(getParametro("delete"))
        return "delete";
    else if(getParametro("new"))
        return "new";
    else if(getParametro("save"))
        return "save";
    else if(getParametro("delete"))
        return "delete";
    else if(getParametro("action")=="show")
        return "show";
    else if(getParametro("action")=="call2phone")
        return "call2phone";
    else
        return "report";
}

function getParametro($parametro)
{
    if(isset($_POST[$parametro]))
        return $_POST[$parametro];
    else if(isset($_GET[$parametro]))
        return $_GET[$parametro];
    else
        return null;
}
?>