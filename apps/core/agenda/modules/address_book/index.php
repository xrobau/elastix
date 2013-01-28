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
  $Id: index.php,v 1.1 2010/02/04 09:20:00 onavarrete@palosanto.com Exp $
 */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoACL.class.php";


    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAdressBook.class.php";
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

    $smarty->assign('MODULE_NAME', $module_name);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn_agi_manager['password'] = $arrConfig['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrConfig['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = 'admin';

    //solo para obtener los devices (extensiones) creadas.
    $dsnAsterisk = generarDSNSistema('asteriskuser', 'asterisk');
    $pDB   = new paloDB($arrConf['dsn_conn_database']); // address_book
    $pDB_2 = new paloDB($arrConf['dsn_conn_database2']); // acl

    $action = getAction();

    $content = "";
    switch($action)
    {
        case "new":
            $content = new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "cancel":
            header("Location: ?menu=$module_name");
            break;
        case "commit":
            $content = save_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk, true);
            break;
        case "edit":
            $content = view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "show":
            $content = view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "save":
            if($_POST['address_book_options']=="address_from_csv")
                $content = save_csv($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            else
                $content = save_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "delete":
            $content = deleteContact($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "call2phone":
            $content = call2phone($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "transfer_call":
            $content = transferCALL($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case 'download_csv':
            $content = download_address_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        case 'getImage':
            $content = getImageContact($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
        default:
            $content = report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            break;
    }

    return $content;
}

function save_csv($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{
    //valido el tipo de archivo
    if (!preg_match('/\.csv$/i', $_FILES['userfile']['name'])) {
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang["Invalid file extension.- It must be csv"]);
    }else {
        if(is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            $ruta_archivo = "/tmp/".$_FILES['userfile']['name'];
            copy($_FILES['userfile']['tmp_name'], $ruta_archivo);
            //Funcion para cargar las extensiones
            load_address_book_from_csv($smarty, $arrLang, $ruta_archivo, $pDB, $pDB_2);
        }else {
            $smarty->assign("mb_title", $arrLang["Error"]);
            $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." :". $_FILES['userfile']['name']);
        }
    }
    $content = new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
    return $content;
}

function load_address_book_from_csv($smarty, $arrLang, $ruta_archivo, $pDB, $pDB_2)
{
    $Messages = "";
    $arrayColumnas = array();
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);

    $result = isValidCSV($arrLang, $ruta_archivo, $arrayColumnas);
    if($result != 'true'){
        $smarty->assign("mb_title",$arrLang["Error"]);
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

                $namedb       = $tupla[$arrayColumnas[0]];
                $last_namedb  = $tupla[$arrayColumnas[1]];
                $telefonodb   = $tupla[$arrayColumnas[2]];
                $emaildb      = isset($arrayColumnas[3])?$tupla[$arrayColumnas[3]]:"";
                $addressdb    = isset($arrayColumnas[4])?$tupla[$arrayColumnas[4]]:"";
                $companydb    = isset($arrayColumnas[5])?$tupla[$arrayColumnas[5]]:"";
                $statusdb     = "isPrivate";
                $iduserdb     = $id_user;

                $data = array($namedb, $last_namedb, $telefonodb, $emaildb, $iduserdb, $addressdb, $companydb, $statusdb);
                //Paso 1: verificar que no exista un usuario con los mismos datos
                $result = $pAdressBook->existContact($namedb, $last_namedb, $telefonodb);
                if(!$result)
                    $Messages .= "{$arrLang["ERROR"]}:" . $pAdressBook->errMsg . "  <br />";
                else if($result['total']>0)
                    $Messages .= "{$arrLang["ERROR"]}: {$arrLang["Contact Data already exists"]}: {$data['name']} <br />";
                else{
                    //Paso 2: creando en la contact data
                    if(!$pAdressBook->addContactCsv($data))
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
                else if($tupla[$i] == 'Address')
                    $arrayColumnas[4] = $i;
                else if($tupla[$i] == 'Company')
                    $arrayColumnas[5] = $i;
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

function new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{
    $arrFormadress_book = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormadress_book);

    $smarty->assign("Show", 1);
    $smarty->assign("ShowImg",0);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("title", $arrLang["Address Book"]);
    $smarty->assign("icon", "modules/$module_name/images/address_book.png");

    $smarty->assign("new_contact", $arrLang["New Contact"]);
    $smarty->assign("address_from_csv", $arrLang["Address Book from CSV"]);
    $smarty->assign("private_contact", $arrLang["Private Contact"]);
    $smarty->assign("public_contact", $arrLang["Public Contact"]);

    if(isset($_POST['address_book_options']) && $_POST['address_book_options']=='address_from_csv')
        $smarty->assign("check_csv", "checked");
    else $smarty->assign("check_new_contact", "checked");


    $smarty->assign("check_isPrivate", "checked");


    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("label_file", $arrLang["File"]);
    $smarty->assign("DOWNLOAD", $arrLang["Download Address Book"]);
    $smarty->assign("HeaderFile", $arrLang["Header File Address Book"]);
    $smarty->assign("AboutContacts", $arrLang["About Address Book"]);


    $padress_book = new paloAdressBook($pDB);

    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_adress_book.tpl", $arrLang["Address Book"], $_POST);

    $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}



/*
******** Funciones del modulo
*/
function report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{
    $padress_book = new paloAdressBook($pDB);
    $pACL         = new paloACL($pDB_2);
    $user	  = $_SESSION["elastix_user"];
    $id_user      = $pACL->getIdUser($user);
    $extension	  = $pACL->getUserExtension($user);
    if(is_null($extension) || $extension==""){
	if($pACL->isUserAdministratorGroup($user)){
            $smarty->assign("mb_title", _tr("MESSAGE"));
	    $smarty->assign("mb_message", "<b>".$arrLang["You don't have extension number associated with user"]."</b>");
	}else
	    $smarty->assign("mb_message", "<b>".$arrLang["contact_admin"]."</b>");
    }
    if(getParameter('select_directory_type') != null && getParameter('select_directory_type')=='external')
    {
        $smarty->assign("external_sel",'selected=selected');
        $directory_type = 'external';
    }
    else{
        $smarty->assign("internal_sel",'selected=selected');
        $directory_type = 'internal';
    }
    $_POST['select_directory_type'] = $directory_type;
    

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
                                                    "INPUT_EXTRA_PARAM"      => array('id' => 'filter_value')),
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
    $namePattern = NULL;
    $allowSelection = array("name", "telefono", "last_name");
    if(isset($_POST['field']) and isset($_POST['pattern']) and ($_POST['pattern']!="")){
        $field      = $_POST['field'];
        if (!in_array($field, $allowSelection))
            $field = "name";
        $pattern    = "%$_POST[pattern]%";
        $namePattern = $_POST['pattern'];
        $nameField=$arrComboElements[$field];
    }

    $arrFilter = array("select_directory_type"=>$directory_type,"field"=>$field,"pattern" =>$namePattern);

    $startDate = $endDate = date("Y-m-d H:i:s");
    $oGrid  = new paloSantoGrid($smarty);

    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Phone Directory")." =  $directory_type ", $arrFilter, array("select_directory_type" => "internal"),true);
    $oGrid->addFilterControl(_tr("Filter applied ").$field." = $namePattern", $arrFilter, array("field" => "name","pattern" => ""));

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter_adress_book.tpl", "", $arrFilter);

    if($directory_type=='external')
        $total = $padress_book->getAddressBook(NULL,NULL,$field,$pattern,TRUE,$id_user);
    else
        $total = $padress_book->getDeviceFreePBX($dsnAsterisk, NULL,NULL,$field,$pattern,TRUE);

    $total_datos = $total[0]["total"];
    //Paginacion
    $limit  = 20;
    $total  = $total_datos;

    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

    $offset = $oGrid->calculateOffset();

    $inicio = ($total == 0) ? 0 : $offset + 1;

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    //Fin Paginacion

    if($directory_type=='external')
        $arrResult = $padress_book->getAddressBook($limit, $offset, $field, $pattern, FALSE, $id_user);
    else
        $arrResult = $padress_book->getDeviceFreePBX($dsnAsterisk, $limit,$offset,$field,$pattern);

    $arrData = null; //echo print_r($arrResult,true);
    if(is_array($arrResult) && $total>0){
        $arrMails = array();
        $typeContact = "";
        if($directory_type=='internal')
            $arrMails = $padress_book->getMailsFromVoicemail();

        foreach($arrResult as $key => $adress_book){
            if($directory_type == 'external'){
                $exten   = explode(".",$adress_book["picture"]);
                if(isset($exten[count($exten)-1]))
                    $exten   = $exten[count($exten)-1];
                $picture = "/var/www/address_book_images/$adress_book[id]_Thumbnail.$exten";
                if(file_exists($picture))
                    $arrTmp[1] = "<a href='?menu=$module_name&action=show&id=".$adress_book['id']."'><img alt='image' border='0' src='index.php?menu=$module_name&action=getImage&idPhoto=$adress_book[id]&thumbnail=yes&rawmode=yes'/></a>";
                else{
                    $defaultPicture = "modules/$module_name/images/Icon-user_Thumbnail.png";
                    $arrTmp[1] = "<a href='?menu=$module_name&action=show&id=".$adress_book['id']."'><img border='0' alt='image' src='$defaultPicture'/></a>";
                }
            }
            $arrTmp[0]  = ($directory_type=='external')?"<input type='checkbox' name='contact_{$adress_book['id']}'  />":'';
            if($directory_type=='external'){
                $email = $adress_book['email'];
                if($adress_book['status']=='isPublic'){
                    if($id_user == $adress_book['iduser']){
                        $typeContact = "<div><div style='float: left;'><a href='?menu=$module_name&action=show&id=".$adress_book['id']."'><img alt='public' style='padding: 5px;' title='".$arrLang['Public Contact']."' border='0' src='modules/$module_name/images/public_edit.png' /></a></div><div style='padding: 16px 0px 0px 5px; text-align:center;'><span style='visibility: hidden;'>".$arrLang['Public editable']."</span></div></div>";
                        $arrTmp[0]  = "<input type='checkbox' name='contact_{$adress_book['id']}'  />";
                    }else{
                        $typeContact = "<div><div style='float: left;'><a href='?menu=$module_name&action=show&id=".$adress_book['id']."'><img alt='public' style='padding: 5px;' title='".$arrLang['Public Contact']."' border='0' src='modules/$module_name/images/public.png' /></a></div><div style='padding: 16px 0px 0px 5px; text-align:center;'><span style='visibility: hidden;'>".$arrLang['Public not editable']."</span></div></div>";
                        $arrTmp[0]  = "";
                    }
                }else
                    $typeContact = "<div><div style='float: left;'><a href='?menu=$module_name&action=show&id=".$adress_book['id']."'><img alt='private' style='padding: 5px;' title='".$arrLang['Private Contact']."' border='0' src='modules/$module_name/images/contact.png' /></a></div><div style='padding: 16px 0px 0px 5px; text-align:center;'><span style='visibility: hidden;'>".$arrLang['Private']."</span></div></div>";
            }else if(isset($arrMails[$adress_book['id']])){
                $email = $arrMails[$adress_book['id']];
                $typeContact = "<div><div style='float: left;'><img alt='public' title='".$arrLang['Public Contact']."' src='modules/$module_name/images/public.png' /></div><div style='padding: 16px 0px 0px 5px; text-align:center;'><span style='visibility: hidden;'>".$arrLang['Public not editable']."</span></div></div>";
            }else{ 
                $email = '';
                $typeContact = "<div><div style='float: left;'><img alt='public' title='".$arrLang['Public Contact']."' src='modules/$module_name/images/public.png' /></div><div style='padding: 16px 0px 0px 5px; text-align:center;'><span style='visibility: hidden;'>".$arrLang['Public not editable']."</span></div></div>";
            }


            $arrTmp[2]  = ($directory_type=='external')?"<a href='?menu=$module_name&action=show&id=".$adress_book['id']."'>".htmlspecialchars($adress_book['last_name'], ENT_QUOTES, "UTF-8")." ".htmlspecialchars($adress_book['name'], ENT_QUOTES, "UTF-8")."</a>":$adress_book['description'];
            $arrTmp[3]  = ($directory_type=='external')?$adress_book['telefono']:$adress_book['id'];
            $arrTmp[4]  = $email;
            $arrTmp[5]  = "<a href='?menu=$module_name&action=call2phone&id=".$adress_book['id']."&type=".$directory_type."'><img border=0 src='/modules/$module_name/images/call.png' /></a>";
            $arrTmp[6]  = "<a href='?menu=$module_name&action=transfer_call&id=".$adress_book['id']."&type=".$directory_type."'>{$arrLang["Transfer"]}</a>";
            $arrTmp[7]  = $typeContact;
            $arrData[]  = $arrTmp;
        }
    }
    if($directory_type=='external'){
	$name = "";
        $picture = $arrLang["picture"];
        $oGrid->deleteList(_tr("Are you sure you wish to delete the contact."),"delete",_tr("Delete"));
    }
    else {
        $name = "";
        $picture = "";
    }

    $arrGrid = array(   "title"    => $arrLang["Address Book"],
                        "url"      => array('menu' => $module_name, 'filter' => $pattern, 'select_directory_type' => $directory_type),
                        "icon"     => "modules/$module_name/images/address_book.png",
                        "width"    => "99%",
                        "start"    => $inicio,
                        "end"      => $end,
                        "total"    => $total,
                        "columns"  => array(0 => array("name"      => $name,
                                                    "property1" => ""),
                                            1 => array("name"      => $picture,
                                                    "property1" => ""),
                                            2 => array("name"      => $arrLang["Name"],
                                                    "property1" => ""),
                                            3 => array("name"      => $arrLang["Phone Number"],
                                                    "property1" => ""),
                                            4 => array("name"      => $arrLang["Email"],
                                                    "property1" => ""),
                                            5 => array("name"      => $arrLang["Call"],
                                                    "property1" => ""),
                                            6 => array("name"      => $arrLang["Transfer"],
                                                    "property1" => ""),
                                            7 => array("name"      => $arrLang["Type Contact"],
                                                    "property1" => "")
                                        )
                    );
    $oGrid->addNew("new",_tr("New Contact"));
    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    return $contenidoModulo;
}

function createFieldForm($arrLang)
{
    $arrFields = array(
                "name"          => array(   "LABEL"                 => $arrLang["First Name"],
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
                                            "VALIDATION_EXTRA_PARAM"=> "^[\*|#]*[[:digit:]]*$"),
                "email"         => array(   "LABEL"                 => $arrLang["Email"],
                                            "REQUIRED"              => "no",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => "",
                                            "VALIDATION_TYPE"       => "ereg",
                                            "VALIDATION_EXTRA_PARAM"=> "([[:alnum:]]|.|_|-){1,}@([[:alnum:]]|.|_|-){1,}"),
                "picture"   => array(      "LABEL"                  => $arrLang["picture"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "FILE",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "picture"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
                "address"     => array(     "LABEL"                 => $arrLang["Address"],
                                            "REQUIRED"              => "no",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => array("style" => "width:300px;"),
                                            "VALIDATION_TYPE"       => "text",
                                            "VALIDATION_EXTRA_PARAM"=> ""),
                "company"     => array(     "LABEL"                 => $arrLang["Company"],
                                            "REQUIRED"              => "no",
                                            "INPUT_TYPE"            => "TEXT",
                                            "INPUT_EXTRA_PARAM"     => array("style" => "width:300px;"),
                                            "VALIDATION_TYPE"       => "text",
                                            "VALIDATION_EXTRA_PARAM"=> ""),
                "notes"   => array(         "LABEL"                  => $arrLang["Notes"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXTAREA",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "notes"),
                                            "VALIDATION_TYPE"        => "text",
                                            "EDITABLE"               => "si",
                                            "COLS"                   => "40",
                                            "ROWS"                   => "4",
                                            "VALIDATION_EXTRA_PARAM" => ""),
                "status"   => array(        "LABEL"                  => $arrLang["Status"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "status"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
                );
    return $arrFields;
}

function save_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk, $update=FALSE)
{
    $arrForm = createFieldForm($arrLang);
    $oForm = new paloForm($smarty, $arrForm);
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);
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
        $smarty->assign("title", $arrLang["Address Book"]);

        $smarty->assign("new_contact", $arrLang["New Contact"]);
        $smarty->assign("address_from_csv", $arrLang["Address Book from CSV"]);
        $smarty->assign("private_contact", $arrLang["Private Contact"]);
        $smarty->assign("public_contact", $arrLang["Public Contact"]);

        if(isset($_POST['address_book_options']) && $_POST['address_book_options']=='address_from_csv')
            $smarty->assign("check_csv", "checked");
        else $smarty->assign("check_new_contact", "checked");

        if(isset($_POST['address_book_status']) && $_POST['address_book_status']=='isPrivate')
            $smarty->assign("check_isPrivate", "checked");
        else $smarty->assign("check_isPublic", "checked");

        $smarty->assign("SAVE", $arrLang["Save"]);
        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $smarty->assign("label_file", $arrLang["File"]);
        $smarty->assign("DOWNLOAD", $arrLang["Download Address Book"]);
        $smarty->assign("HeaderFile", $arrLang["Header File Address Book"]);
        $smarty->assign("AboutContacts", $arrLang["About Address Book"]);

        if($update)
        {
            $_POST["edit"] = 'edit';
            return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
        }else{
            $smarty->assign("Show", 1);
            $smarty->assign("ShowImg",1);
            $htmlForm = $oForm->fetchForm("$local_templates_dir/new_adress_book.tpl", $arrLang["Address Book"], $_POST);
            $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
            return $contenidoModulo;
        }
    }else{
        $pictureUpload = $_FILES['picture']['name'];
        $file_upload = "";
        $ruta_destino = "/var/www/address_book_images";
        $idPost = $_POST['id'];
        $data = array();
        $padress_book = new paloAdressBook($pDB);
        $contactData = $padress_book->contactData($idPost, $id_user);
        $lastId = 0;
        if($update)
            $idImg = $contactData['id'];
        else{
            $idImg = date("Ymdhis");
        }
        //valido el tipo de archivo
        if(isset($pictureUpload) && $pictureUpload != ""){
            // \w cualquier caracter, letra o guion bajo
            // \s cualquier espacio en blanco
            if (!preg_match("/^(\w|-|\.|\(|\)|\s)+\.(png|PNG|JPG|jpg|JPEG|jpeg)$/",$pictureUpload)) {
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", $arrLang["Invalid file extension.- It must be png or jpg or jpeg"]);
                if($update)
                    return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk, TRUE);
                else
                    return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            }else {
                if(is_uploaded_file($_FILES['picture']['tmp_name'])) {
                    $file_upload = basename($_FILES['picture']['tmp_name']); // verificando que solo tenga la ruta al archivo
                    $file_name = basename("/tmp/".$_FILES['picture']['name']);
                    $ruta_archivo = "/tmp/$file_upload";
                    $arrIm = explode(".",$pictureUpload);
                    $renameFile = "$ruta_destino/$idImg.".$arrIm[count($arrIm)-1];
                    $file_upload = $idImg.".".$arrIm[count($arrIm)-1];
                    $filesize = $_FILES['picture']['size'];
                    $filetype = $_FILES['picture']['type'];

                    $sizeImgUp=getimagesize($ruta_archivo);
                    if(!$sizeImgUp){
                         $smarty->assign("mb_title", $arrLang["ERROR"]);
                         $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." : ". $pictureUpload);
                        if($update)
                            return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk,TRUE);
                        else
                            return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
                    }
                    //realizar acciones
                    if(!rename($ruta_archivo, $renameFile)){
                        $smarty->assign("mb_title", $arrLang["ERROR"]);
                        $smarty->assign("mb_message", $arrLang["Error to Upload"] ." : ". $pictureUpload);
                        if($update)
                            return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk,TRUE);
                        else
                            return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
                    }else{ //redimensiono la imagen
                        $ancho_thumbnail = 48;
                        $alto_thumbnail = 48;
                        $thumbnail_path = $ruta_destino."/$idImg"."_Thumbnail.".$arrIm[count($arrIm)-1];
                        if(is_file($renameFile)){
                            if(!redimensionarImagen($renameFile,$thumbnail_path,$ancho_thumbnail,$alto_thumbnail)){
                                $smarty->assign("mb_title", $arrLang["ERROR"]);
                                $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." : ". $pictureUpload);
                                if($update)
                                    return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk,TRUE);
                                else
                                    return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
                            }
                        }

                        $ancho = 280;
                        $alto = 200;
                        if(is_file($renameFile)){
                            if(!redimensionarImagen($renameFile,$renameFile,$ancho,$alto)){
                                $smarty->assign("mb_title", $arrLang["ERROR"]);
                                $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." : ". $pictureUpload);
                                if($update)
                                    return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk,TRUE);
                                else
                                    return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
                            }
                        }
                    }
                }else {
                    $smarty->assign("mb_title", $arrLang["ERROR"]);
                    $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"] ." : ". $pictureUpload);
                    if($update)
                        return view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk,TRUE);
                    else
                        return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
                }
            }
        }

        $namedb       = isset($_POST['name'])?$_POST['name']:"";
        $last_namedb  = isset($_POST['last_name'])?$_POST['last_name']:"";
        $telefonodb   = isset($_POST['telefono'])?$_POST['telefono']:"";
        //$extensiondb  = isset($_POST['extension'])?$_POST['extension']:"";
        $emaildb      = isset($_POST['email'])?$_POST['email']:"";;
        $iduserdb     = isset($id_user)?"$id_user":"";
        $picturedb    = isset($file_upload)?"$file_upload":"";
        $addressdb    = isset($_POST['address'])?$_POST['address']:"";
        $companydb    = isset($_POST['company'])?$_POST['company']:"";
        $notesdb      = isset($_POST['notes'])?$_POST['notes']:"";
        $statusdb     = isset($_POST['address_book_status'])?$_POST['address_book_status']:"";
        $data = array($namedb, $last_namedb, $telefonodb, $emaildb, $iduserdb, $picturedb, $addressdb, $companydb, $notesdb, $statusdb);
        if($update){ // actualizacion del contacto
            if($contactData){
                if($file_upload == ""){
                    $data[5] = $contactData['picture'];
                }
                $result = $padress_book->updateContact($data,$_POST['id']);
                if(!$result){
                    $smarty->assign("mb_title", $arrLang["Validation Error"]);
                    $smarty->assign("mb_message", $arrLang["Internal Error"]);
                    return report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
                }
            }else{
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", $arrLang["Internal Error"]);
                return report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            }
        }else{ //// creacion de contacto
            $result = $padress_book->addContact($data);
            if(!$result){
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", $arrLang["Internal Error"]);
                return new_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
            }
            $lastId = $pDB->getLastInsertId();
            $contactData2 = $padress_book->contactData($lastId, $id_user);
            if($contactData2['picture']!="" && isset($contactData2['picture'])){
                $arrIm = explode(".",$contactData2['picture']);
                $renameFile = "$ruta_destino/".$lastId.".".$arrIm[count($arrIm)-1];
                $file_upload = $lastId.".".$arrIm[count($arrIm)-1];
                rename($ruta_destino."/".$contactData2['picture'], $renameFile);
                rename($ruta_destino."/".$idImg."_Thumbnail.".$arrIm[count($arrIm)-1], $ruta_destino."/".$lastId."_Thumbnail.".$arrIm[count($arrIm)-1]);
                $data[5] = $file_upload;
                $padress_book->updateContact($data,$lastId);
            }
        }

        if(!$result)
            return($pDB->errMsg);

        //'?menu=$module_name&action=show&id=".$adress_book['id']."'
        if($_POST['id'])
            header("Location: ?menu=$module_name&action=show&id=".$_POST['id']);
        else
            header("Location: ?menu=$module_name");
    }
}

function deleteContact($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{
    $padress_book = new paloAdressBook($pDB);
    $ruta_destino = "/var/www/address_book_images/";
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);
    $result = "";
    foreach($_POST as $key => $values){
        if(substr($key,0,8) == "contact_")
        {
            $tmpBookID = substr($key, 8);
            if($padress_book->isEditablePublicContact($tmpBookID, $id_user)){
                $contactTmp = $padress_book->contactData($tmpBookID, $id_user);
                $result = $padress_book->deleteContact($tmpBookID, $id_user);
                if($contactTmp['picture']!="" && isset($contactTmp['picture'])){
                    if(is_file($ruta_destino."/".$contactTmp['picture']))
                        unlink($ruta_destino."/".$contactTmp['picture']);
                }
            }
        }
    }
    $content = report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);

    return $content;
}

function view_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk, $update=FALSE)
{
    $arrFormadress_book = createFieldForm($arrLang);
    $pACL    = new paloACL($pDB_2);
    $id_user = $pACL->getIdUser($_SESSION["elastix_user"]);
    $padress_book = new paloAdressBook($pDB);
    $oForm = new paloForm($smarty,$arrFormadress_book);
    $id = isset($_GET['id'])?$_GET['id']:(isset($_POST['id'])?$_POST['id']:"");
    if(isset($_POST["edit"]) || $update==TRUE){
        $oForm->setEditMode();
        if($padress_book->isEditablePublicContact($id, $id_user)){
            $smarty->assign("Commit", 1);
            $smarty->assign("SAVE",$arrLang["Save"]);
        }else{
            $smarty->assign("Commit", 0);
            $smarty->assign("SAVE",$arrLang["Save"]);
        }
    }else{
        $oForm->setViewMode();
        $smarty->assign("Edit", 1);
        if($padress_book->isEditablePublicContact($id, $id_user)){
            $smarty->assign("Edit", 1);
            $smarty->assign("EditW", 0);
        }else{
            $smarty->assign("Edit", 0);
            $smarty->assign("EditW", 0);
        }
    }

    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("title", $arrLang["Address Book"]);
    $smarty->assign("FirstName",$arrLang["First Name"]);
    $smarty->assign("LastName",$arrLang["Last Name"]);
    $smarty->assign("PhoneNumber",$arrLang["Phone Number"]);
    $smarty->assign("Email",$arrLang["Email"]);
    $smarty->assign("address",$arrLang["Address"]);
    $smarty->assign("company",$arrLang["Company"]);
    $smarty->assign("notes",$arrLang["Notes"]);
    $smarty->assign("picture",$arrLang["picture"]);
    $smarty->assign("private_contact", $arrLang["Private Contact"]);
    $smarty->assign("public_contact", $arrLang["Public Contact"]);

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

    $smarty->assign("style_address_options", "style='display:none'");

    $smarty->assign("idPhoto",$id);

    $contactData = $padress_book->contactData($id, $id_user);
    if($contactData){
        $smarty->assign("ID",$id);
    }else{
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang["Not_allowed_contact"]); 
        return report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
    }

    if($contactData['status']=='isPrivate')
       $smarty->assign("check_isPrivate", "checked"); 
    else if($contactData['status']=='isPublic')
        $smarty->assign("check_isPublic", "checked");
    else
        $smarty->assign("check_isPrivate", "checked");


    $arrData['name']          = isset($_POST['name'])?$_POST['name']:$contactData['name'];
    $arrData['last_name']     = isset($_POST['last_name'])?$_POST['last_name']:$contactData['last_name'];
    $arrData['telefono']      = isset($_POST['telefono'])?$_POST['telefono']:$contactData['telefono'];
    $arrData['email']         = isset($_POST['email'])?$_POST['email']:$contactData['email'];
    $arrData['address']       = isset($_POST['address'])?$_POST['address']:$contactData['address'];
    $arrData['company']       = isset($_POST['company'])?$_POST['company']:$contactData['company'];
    $arrData['notes']         = isset($_POST['notes'])?$_POST['notes']:$contactData['notes'];
    $arrData['picture']       = isset($_POST['picture'])?$_POST['picture']:$contactData['picture'];
    $arrData['status']        = isset($_POST['status'])?$_POST['status']:$contactData['status'];

    $smarty->assign("ShowImg",1);
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_adress_book.tpl",  $arrLang["Address Book"], $arrData);

    $contenidoModulo = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function call2phone($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{


    $padress_book = new paloAdressBook($pDB);
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);
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
                    $contactData = $padress_book->contactData($id, $id_user);
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

    $content = report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
    return $content;
}

function transferCALL($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{


    $padress_book = new paloAdressBook($pDB);
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);
    if($id_user != FALSE)
    {
        $user = $pACL->getUsers($id_user);
        if($user != FALSE)
        {
            $extension = $user[0][3];
            if($extension != "")
            {
                $id = isset($_GET['id'])?$_GET['id']:(isset($_POST['id'])?$_POST['id']:"");

                $phone2tranfer = '';
                if(isset($_GET['type']) && $_GET['type']=='external')
                {
                    $contactData   = $padress_book->contactData($id, $id_user);
                    $phone2tranfer = $contactData['telefono'];
                }else
                    $phone2tranfer = $id;

                $result = $padress_book->Obtain_Protocol_from_Ext($dsnAsterisk, $extension);
                if($result != FALSE)
                {
                    $result = $padress_book->TranferCall($dsn_agi_manager, $extension, $phone2tranfer, $result['dial'], $result['description']);
                    if(!$result)
                    {
                        $smarty->assign("mb_title", $arrLang['ERROR'].":");
                        $smarty->assign("mb_message", $arrLang["The transfer couldn't be realized, maybe you don't have any conversation now."]);
                    }
                }
                else {
                    $smarty->assign("mb_title", $arrLang["Validation Error"]);
                    $smarty->assign("mb_message", $padress_book->errMsg);
                }
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

    $content = report_adress_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk);
    return $content;
}
/*
******** Fin
*/

function getAction()
{
    if(getParameter("edit"))
        return "edit"; 
    else if(getParameter("commit"))
        return "commit";
    else if(getParameter("show"))
        return "show";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("save"))
        return "save";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("action")=="show")
        return "show";
    else if(getParameter("action")=="download_csv")
        return "download_csv";
    else if(getParameter("action")=="call2phone")
        return "call2phone";
    else if(getParameter("action")=="transfer_call")
        return "transfer_call";
    else if(getParameter("action")=="getImage")
        return "getImage";
    else
        return "report";
}

function download_address_book($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{
    header("Cache-Control: private");
    header("Pragma: cache");
    header('Content-Type: text/csv; charset=iso-8859-1; header=present');
    header("Content-disposition: attachment; filename=address_book.csv");
    echo backup_contacts($pDB, $pDB_2, $arrLang);
}

function getImageContact($smarty, $module_name, $local_templates_dir, $pDB, $pDB_2, $arrLang, $arrConf, $dsn_agi_manager, $dsnAsterisk)
{
    $contact_id = getParameter('idPhoto'); 
    $thumbnail  = getParameter("thumbnail");
    $pACL       = new paloACL($pDB_2);
    $id_user    = $pACL->getIdUser($_SESSION["elastix_user"]);
    $ruta_destino = "/var/www/address_book_images";
    $imgDefault = $_SERVER['DOCUMENT_ROOT']."/modules/$module_name/images/Icon-user.png";
    $padress_book = new paloAdressBook($pDB);
    $contactData = $padress_book->contactData($contact_id, $id_user);

    $arrIm = explode(".",$contactData['picture']);
    $typeImage = $arrIm[count($arrIm)-1];
    if($thumbnail=="yes")
        $image = $ruta_destino."/".$contact_id."_Thumbnail.$typeImage";
    else
        $image = $ruta_destino."/".$contactData['picture'];
    // Creamos la imagen a partir de un fichero existente 
    if(is_file($image)){
        if(strtolower($typeImage) == "png"){
            Header("Content-type: image/png"); 
            $im = imagecreatefromPng($image); 
            ImagePng($im); // Mostramos la imagen 
            ImageDestroy($im); // Liberamos la memoria que ocupaba la imagen 
        }else{
            Header("Content-type: image/jpeg"); 
            $im = imagecreatefromJpeg($image); 
            ImageJpeg($im); // Mostramos la imagen 
            ImageDestroy($im); // Liberamos la memoria que ocupaba la imagen 
        }
    }else{
        Header("Content-type: image/png"); 
        $image = file_get_contents($imgDefault); 
        echo $image;
    }
    return;
}

function backup_contacts($pDB, $pDB_2, $arrLang)
{
    $Messages = "";
    $csv = "";
    $pAdressBook = new paloAdressBook($pDB);
    $fields = "name, last_name, telefono, email";
    $pACL         = new paloACL($pDB_2);
    $id_user      = $pACL->getIdUser($_SESSION["elastix_user"]);
    $arrResult = $pAdressBook->getAddressBookByCsv(null, null, $fields, null, null, $id_user);

    if(!$arrResult)
    {
        $Messages .= $arrLang["There aren't contacts"].". ".$pAdressBook->errMsg;
        echo $Messages;
    }else{
        //cabecera
        $csv .= "\"Name\",\"Last Name\",\"Phone Number\",\"Email\",\"Address\",\"Company\"\n";
        foreach($arrResult as $key => $contact)
        {
            $csv .= "\"{$contact['name']}\",\"{$contact['last_name']}\",".
                    "\"{$contact['telefono']}\",\"{$contact['email']}\",".
                    "\"{$contact['address']}\",\"{$contact['company']}\"".
                    "\n";
        }
    }
    return $csv;
}

function redimensionarImagen($ruta1,$ruta2,$ancho,$alto)
{

    # se obtene la dimension y tipo de imagen
    $datos=getimagesize($ruta1);

    if(!$datos)
        return false;

    $ancho_orig = $datos[0]; # Anchura de la imagen original
    $alto_orig = $datos[1];    # Altura de la imagen original
    $tipo = $datos[2];
    $img = "";
    if ($tipo==1){ # GIF
        if (function_exists("imagecreatefromgif"))
            $img = imagecreatefromgif($ruta1);
        else
            return false;
    }
    else if ($tipo==2){ # JPG
        if (function_exists("imagecreatefromjpeg"))
            $img = imagecreatefromjpeg($ruta1);
        else
            return false;
    }
    else if ($tipo==3){ # PNG
        if (function_exists("imagecreatefrompng"))
            $img = imagecreatefrompng($ruta1);
        else
            return false;
    }

    $anchoTmp = imagesx($img);
    $altoTmp = imagesy($img);
    if(($ancho > $anchoTmp || $alto > $altoTmp)){
        ImageDestroy($img);
        return true;
    }

    # Se calculan las nuevas dimensiones de la imagen
    if ($ancho_orig>$alto_orig){
        $ancho_dest=$ancho;
        $alto_dest=($ancho_dest/$ancho_orig)*$alto_orig;
    }else{
        $alto_dest=$alto;
        $ancho_dest=($alto_dest/$alto_orig)*$ancho_orig;
    }
    
    // imagecreatetruecolor, solo estan en G.D. 2.0.1 con PHP 4.0.6+
    $img2=@imagecreatetruecolor($ancho_dest,$alto_dest) or $img2=imagecreate($ancho_dest,$alto_dest);
    
    // Redimensionar
    // imagecopyresampled, solo estan en G.D. 2.0.1 con PHP 4.0.6+
    @imagecopyresampled($img2,$img,0,0,0,0,$ancho_dest,$alto_dest,$ancho_orig,$alto_orig) or imagecopyresized($img2,$img,0,0,0,0,$ancho_dest,$alto_dest,$ancho_orig,$alto_orig);
    
    // Crear fichero nuevo, según extensión.
    if ($tipo==1) // GIF
    if (function_exists("imagegif"))
        imagegif($img2, $ruta2);
    else
        return false;
    
    if ($tipo==2) // JPG
    if (function_exists("imagejpeg"))
        imagejpeg($img2, $ruta2);
    else
        return false;
    
    if ($tipo==3)  // PNG
    if (function_exists("imagepng"))
        imagepng($img2, $ruta2);
    else
        return false;
    
    return true;

}

?>
