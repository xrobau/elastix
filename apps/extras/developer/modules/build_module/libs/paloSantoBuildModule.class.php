<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/05/16 17:31:55 afigueroa Exp $ */

class paloSantoBuildModule {
    var $_DB;
    var $errMsg;

    function paloSantoBuildModule(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    function Existe_Id_Module($id_module)
    {
        $query = "SELECT count(*) FROM menu WHERE id=?";
        $result = $this->_DB->getFirstRowQuery($query,false,array($id_module));
        if($result[0] > 0)
            return true;
        else return false;
    }

    function Insertar_Menu($id_module, $parent, $module_name, $module_type, $url="")
    {
        $type = "";
        if($module_type == "form" || $module_type == "grid")
           $type = "module";
        else
           $type = "framed";
                   
        $query = "INSERT INTO menu values(?,?,?,?,?,'')";
        $result = $this->_DB->genQuery($query,array($id_module,$parent,$url,$module_name,$type));
        if($result)
            return true;
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function Insertar_Resource($id_module, $module_name)
    {
        $query = "Insert into  acl_resource (name, description) values(?,?)";
        $result = $this->_DB->genQuery($query,array($id_module,$module_name));
        if($result)
        {
            $result = $this->_DB->getLastInsertId();
            return $result;
        }
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function Insertar_Group_Permissions($selected_gp, $id_resource)
    {
        $error = false;
        foreach($selected_gp as $value)
        {
            $query = "Insert into acl_group_permission (id_action, id_group, id_resource) values(1,?,?)";
            $result = $this->_DB->genQuery($query,array($value,$id_resource));
            if(!$result)
            {
                $error = true;
                $this->errMsg = $this->_DB->errMsg;
            }
        }

        if($error) return false;
        else return true;
    }

    function Query_Elastix_Version()
    {
        $query = "SELECT value FROM settings WHERE key='elastix_version_release'";
        $result = $this->_DB->getFirstRowQuery($query);
        return $result[0];
    }

    function Create_File_Config($new_id_module, $your_name, $ruta, $elastix_Version, $arrLang, $this_module_name, $email_module)
    {
        $filename = 'default.conf.php';
        $date = date('Y-m-d h:m:s');

        $file = "$ruta/$this_module_name/libs/sources/comment.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));
        $content = str_replace("{ELASTIX_VERSION}", "$elastix_Version", $content);
        $content = str_replace("{DATE}", "$date", $content);
        $content = str_replace("{YOUR_NAME}", "$your_name", $content);
        $content = str_replace("{FILE_NAME}", "$filename", $content);
        $content = str_replace("{YOUR_EMAIL}", "$email_module", $content);
        fclose($gestor);

        $contenido = "<?php\n$content";

        $file = "$ruta/$this_module_name/libs/sources/default.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));
        $content = str_replace("{MODULE_ID}", "$new_id_module", $content);
        fclose($gestor);

        $contenido .= "$content?>";

        $ruta .= "/$new_id_module/configs";
        return $this->Create_File($ruta, $filename, $contenido, $arrLang);
    }

    function Create_File_Help($new_id_module, $your_name, $ruta, $elastix_Version, $arrLang, $this_module_name)
    {
        $filename = "$new_id_module.hlp";

        $file = "$ruta/$this_module_name/libs/sources/help.tpl";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $contenido = fread($gestor, filesize($file));
        fclose($gestor);

        $ruta .= "/$new_id_module/help";
        return $this->Create_File($ruta, $filename, $contenido, $arrLang);
    }

    function Create_File_Lang($new_module_name, $new_id_module, $your_name, $ruta, $elastix_Version, $arrLang, $this_module_name, $arrForm, $email_module)
    {
        $filename = 'en.lang';
        $date = date('Y-m-d h:m:s');

        $file = "$ruta/$this_module_name/libs/sources/comment.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));
        $content = str_replace("{ELASTIX_VERSION}", "$elastix_Version", $content);
        $content = str_replace("{DATE}", "$date", $content);
        $content = str_replace("{YOUR_NAME}", "$your_name", $content);
        $content = str_replace("{FILE_NAME}", "$filename", $content);
        $content = str_replace("{YOUR_EMAIL}", "$email_module", $content);
        fclose($gestor);

        $contenido = "<?php\n$content";

        $file = "$ruta/$this_module_name/libs/sources/lang.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));

        if(is_array($arrForm) && count($arrForm) >0){
	    $lang_module_name = str_replace('"','\"',$new_module_name);
	    $lang_module_name = str_replace('$','\$',$lang_module_name);
            $tmpLanguage = "\"$lang_module_name\" => \"$lang_module_name\",";
            foreach($arrForm as $key => $names){
                $arrName = explode("/",$names);
		$translation = str_replace('"','\"',$arrName[0]);
		$translation = str_replace('$','\$',$translation);
                $tmpLanguage .= "\n\"$translation\" => \"$translation\",";
            }
        }
        $content = str_replace("{LANG_CONTENT}",$tmpLanguage, $content);
        fclose($gestor);

        $contenido .= "$content?>";

        $ruta .= "/$new_id_module/lang";
        return $this->Create_File($ruta, $filename, $contenido, $arrLang);
    }

    function Create_Module_Class_File($new_module_name, $new_id_module, $your_name, $ruta, $elastix_Version, $arrLang, $this_module_name, $email_module)
    {
	$name_class = preg_replace("/\W/","_",$new_module_name);
	$name_class = preg_replace("/_+/","_",$name_class);
	$name_class = preg_replace("/_$/","",$name_class);
        $filename = "paloSanto$name_class.class.php";
        $date = date('Y-m-d h:m:s');

        $file = "$ruta/$this_module_name/libs/sources/comment.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));
        $content = str_replace("{ELASTIX_VERSION}", "$elastix_Version", $content);
        $content = str_replace("{DATE}", "$date", $content);
        $content = str_replace("{YOUR_NAME}", "$your_name", $content);
        $content = str_replace("{FILE_NAME}", "$filename", $content);
        $content = str_replace("{YOUR_EMAIL}", "$email_module", $content);
        fclose($gestor);

        $contenido = "<?php\n$content";

        $file = "$ruta/$this_module_name/libs/sources/lib_class.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));
        $content = str_replace("{NAME_CLASS}", "$name_class", $content);
        fclose($gestor);

        $contenido .= "$content?>";

        $ruta .= "/$new_id_module/libs";
        return $this->Create_File($ruta, $filename, $contenido, $arrLang);
    }

    function Create_Index_File($new_module_name, $new_id_module, $your_name, $ruta, $elastix_Version, $arrLang, $type, $this_module_name,$arrForm, $email_module)
    {
        $name_class = preg_replace("/\W/","_",$new_module_name);
	$name_class = preg_replace("/_+/","_",$name_class);
	$name_class = preg_replace("/_$/","",$name_class);
	$translateModuleName = str_replace('"','\"',$new_module_name);
	$translateModuleName = str_replace('$','\$',$translateModuleName);
        $filename = "index.php";
        $date = date('Y-m-d h:m:s');
        $field = array();
        $content_form = "";
        $content_file = "";

        $file = "$ruta/$this_module_name/libs/sources/comment.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));
        $content = str_replace("{ELASTIX_VERSION}", "$elastix_Version", $content);
        $content = str_replace("{DATE}", "$date", $content);
        $content = str_replace("{YOUR_NAME}", "$your_name", $content);
        $content = str_replace("{FILE_NAME}", "$filename", $content);
        $content = str_replace("{YOUR_EMAIL}", "$email_module", $content);
        fclose($gestor);

        $contenido = "<?php\n$content";

        $file = "$ruta/$this_module_name/libs/sources/index_$type.s";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $content = fread($gestor, filesize($file));

        if($type == "grid"){
            $blockRows    ="";
            $blockColumns ="";
            $blockFilters ="";
            if(is_array($arrForm) && count($arrForm)>0){
                foreach($arrForm as $key => $column){
                    //Para crear las lineas de los datos rows en el grid.
                    $tmpNameColumn = preg_replace("/\W/","_",strtolower($column));
		    $tmpNameColumn = preg_replace("/_+/","_",$tmpNameColumn);
		    $tmpNameColumn = preg_replace("/_$/","",$tmpNameColumn);
		    $translateColumn = str_replace('"','\"',$column);
		    $translateColumn = str_replace('$','\$',$translateColumn);
                    $blockRows .= "\n\t    \$arrTmp[$key] = \$value['$tmpNameColumn'];";
                    //Para crear las lineas de las columnas en el grid.
                    $blockColumns .= "_tr(\"$translateColumn\"),";
                    //Para crear las lineas del arreglo para el fitrado o busqueda.
                    $blockFilters .= "\n\t    \"$tmpNameColumn\" => _tr(\"$translateColumn\"),";
                }
                $content = str_replace("{ARR_DATA_ROWS}",$blockRows,$content);
                $content = str_replace("{ARR_NAME_COLUMNS}",$blockColumns,$content);
                $content = str_replace("{ARR_FILTERS}",$blockFilters,$content);
            }
        }else{
             if(is_array($arrForm) && count($arrForm) >0){
                    foreach($arrForm as $key => $value){
                        $field = explode("/",$value);
			$field[1] = rtrim($field[1]);
                        if(file_exists("$ruta/$this_module_name/libs/sources/fields_form/$field[1].s")){
                            $file_form = "$ruta/$this_module_name/libs/sources/fields_form/$field[1].s";
                                if (!$gestor_form = fopen($file_form, 'r')) {
                                    $this->errMsg = $arrLang["It isn't possible to open file FORM for reading"]. ": $file";
                                    return false;
                                }
                                $content_file = fread($gestor_form, filesize($file_form));
				$tmpfield = str_replace('"','\"',$field[0]);
				$tmpfield = str_replace('$','\$',$tmpfield);
				$tmpNameField =  preg_replace("/\W/","_",strtolower(trim($field[0])));
				$tmpNameField =  preg_replace("/_+/","_",$tmpNameField);
				$tmpNameField =  preg_replace("/_$/","",$tmpNameField);
                                $content_file = str_replace("{LABEL_FIELD}", $tmpfield, $content_file);
                                $content_file = str_replace("{NAME_FIELD}", $tmpNameField, $content_file);
                                $content_form .= $content_file;
                                fclose($gestor_form);
                            }
                    }
                    $content = str_replace("{ARR_FIELDS_FORM}", "$content_form", $content);
              }
        }


        $content = str_replace("{NAME_CLASS}", "$name_class", $content);
        $content = str_replace("{NEW_MODULE_NAME}", "$translateModuleName", $content);

        fclose($gestor);

        $contenido .= "$content?>";

        $ruta .= "/$new_id_module";
        return $this->Create_File($ruta, $filename, $contenido, $arrLang);
    }

    function Create_tpl_File($new_id_module, $ruta, $arrLang, $type, $this_module_name, $arrForm)
    {
        $field = array();
        $content_form = "";
		$content_file = "";
        $filename = "";
        //primero hay q leer el archivo form.tpl y asignar a alguna variable despues a esta varaible
       //concatenamos el cuerpo es decir los tr que contienen los elementos form
        //$content_form .= $content_file;
        if($type=="grid"){
            $filename = "filter.tpl";
            $file = "$ruta/$this_module_name/libs/sources/filter.tpl";
        }
        else /*if($type == "form")*/{
            $filename = "form.tpl";
            $file = "$ruta/$this_module_name/libs/sources/form.tpl";
            if(is_array($arrForm) && count($arrForm) >0){
			   if(file_exists("$ruta/$this_module_name/libs/sources/fields_form/fields_form.tpl")){
		               	$file_form = "$ruta/$this_module_name/libs/sources/fields_form/fields_form.tpl";					

                foreach($arrForm as $key => $value){
                        $field = explode("/",$value);
                        if (!$gestor_form = fopen($file_form, 'r')) {
                            $this->errMsg = $arrLang["It isn't possible to open file FORM for reading"]. ": $file";
                            return false;
                        }
			$tmpFieldLabel = preg_replace("/\W/","_",strtolower(trim($field[0])));
			$tmpFieldLabel = preg_replace("/_+/","_",$tmpFieldLabel);
			$tmpFieldLabel = preg_replace("/_$/","",$tmpFieldLabel);
                        $content_file = fread($gestor_form, filesize($file_form));
                        $content_file = str_replace("{FIELD_LABEL}", $tmpFieldLabel, $content_file);
			$content_form .= $content_file;
                        fclose($gestor_form);
                    }
                }
            }
        }
        $form_tpl_file = "$ruta/$this_module_name/libs/sources/index_$type.tpl";
        if (!$gestor = fopen($file, 'r')) {
            $this->errMsg = $arrLang["It isn't possible to open file for reading"]. ": $file";
            return false;
        }
        $contenido = fread($gestor, filesize($file));
        $contenido = str_replace("{FIELDS_FORM}",$content_form, $contenido);
        fclose($gestor);

        $ruta .= "/$new_id_module/themes/default";
        return $this->Create_File($ruta, $filename, $contenido, $arrLang);
    }

    function Create_File($ruta, $filename, $contenido, $arrLang)
    {
        if (!$gestor = fopen("$ruta/$filename", 'w')) {
            $this->errMsg = $arrLang["It isn't possible to open file for writing"]. ": $filename";
            return false;
        }
        // Escribir $contenido a nuestro archivo abierto.
        if (fwrite($gestor, $contenido) === FALSE) {
            $this->errMsg = $arrLang["Error when writing file"]. ": $filename";
            return false;
        }
        fclose($gestor);
        return true;
    }
}
?>
