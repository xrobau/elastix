<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.6                                                  |
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
  $Id: email_functions.lib.php,v 1.0 2011/05/02 11:32:51 Eduardo Cueva <ecueva@palosanto.com> Exp $ */

# funciones para correo

function guardar_dominio_sistema($domain_name,&$errMsg)
{
    $continuar=FALSE;
    global $arrLang;
     //Se debe modificar el archivo /etc/postfix/main.cf para agregar el dominio a la variable
     //mydomain2
    $conf_file=new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
    $contenido=$conf_file->leer_configuracion();
    $valor_anterior=$conf_file->privado_get_valor($contenido,"mydomain2");
    $valor_nuevo=construir_valor_nuevo_postfix($valor_anterior,$domain_name);
    $arr_reemplazos=array('mydomain2'=>$valor_nuevo);
    $bValido=$conf_file->escribir_configuracion($arr_reemplazos);
    if($bValido){
        //Se deben recargar la configuracion de postfix
        $retval=$output="";
        exec("sudo -u root postfix reload",$output,$retval);
        if($retval==0)
            $continuar=TRUE;
        else
            $errMsg=$arrLang["main.cf file was updated successfully but when restarting the mail service failed"];
  
    }
    return $continuar;
}


function construir_valor_nuevo_postfix($valor_anterior,$dominio,$eliminar_dominio=FALSE){
    $valor_nuevo=$valor_anterior;

    if(is_null($valor_anterior)){
        $elemento=(!$eliminar_dominio)?"$dominio":"";
        $valor_nuevo="$elemento";
    }
    else{
        if(preg_match("/^(.*)$/",$valor_anterior,$regs)){
            $arr_valores=explode(',',$regs[1]);
            if(!$eliminar_dominio)
                $arr_valores[]="$dominio";

            $valor_nuevo="";
            for($i=0;$i<count($arr_valores);$i++){
                $valor_nuevo.=$arr_valores[$i];
                if($i<(count($arr_valores)-1))
                    $valor_nuevo.=","; 
            }

            if($eliminar_dominio==TRUE){
                $valor_nuevo=str_replace(",$dominio","",$valor_nuevo);
            }
        }
    }
    return $valor_nuevo;
}

function eliminar_dominio($db,$arrDominio,&$errMsg)
{ 
    $pEmail = new paloEmail($db);
    $total_cuentas=0;
    $output="";

    global $CYRUS;
    global $arrLang;
    $cyr_conn = new cyradm;
    $continuar=$cyr_conn -> imap_login();

      # First Delete all stuff related to the domain from the database
    if ($continuar){
        $query1 = "SELECT * FROM accountuser WHERE id_domain='$arrDominio[id_domain]' order by username";
        $result=$db->fetchTable($query1,TRUE);

        if(is_array($result) && count($result)>0){
            foreach ($result as $fila){
                $username = $fila['username'];
                $bExito=eliminar_cuenta($db,$username,$errMsg);
                if (!$bExito){
					$output = $errMsg;
				}else{
                    $continuar = TRUE;
                }
            }
        }

        if($output!="" & !$continuar){
            $errMsg=$arrLang["Error deleting user accounts from system"].": $output";
            return FALSE;
        }

        //uso la clase Email
        $bExito=$pEmail->deleteAccountsFromDomain($arrDominio['id_domain']);
        if (!$bExito){
            $errMsg=$arrLang["Error deleting user accounts"].' :'.((isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg);
            return FALSE;
        }
        $bExito=$pEmail->deleteDomain($arrDominio['id_domain']);
        if (!$bExito){
            $errMsg=$arrLang["Error deleting record from table domain"].' :'.((isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg);
            return FALSE;
        }

	    //Se elimina el dominio del archivo main.cf y se recarga la configuracion
        $continuar=FALSE;
       //Se debe modificar el archivo /etc/postfix/main.cf para borrar el dominio a la variable
       //mydomain2
        $conf_file=new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
        $contenido=$conf_file->leer_configuracion();
        $valor_anterior=$conf_file->privado_get_valor($contenido,"mydomain2");
        $valor_nuevo=construir_valor_nuevo_postfix($valor_anterior,$arrDominio['domain_name'],TRUE);
        $arr_reemplazos=array('mydomain2'=>$valor_nuevo);
        $bValido=$conf_file->escribir_configuracion($arr_reemplazos);

        if($bValido){
           //Se deben recargar la configuracion de postfix
            $retval=$output="";
            exec("sudo -u root postfix reload",$output,$retval);
            if($retval==0)
                $continuar=TRUE;
            else
                $errMsg=$arrLang["main.cf file was updated successfully but when restarting the mail service failed"]." : $retval";
       }
    }
    return $continuar;

}
function eliminar_usuario_correo_sistema($username,$email,&$error){
    $output=array();
    exec("sudo -u root /usr/sbin/saslpasswd2 -d $username@".SASL_DOMAIN,$output);
    if(is_array($output) && count($output)>0){
        foreach($output as $linea)
            $error.=$linea."<br>";
    }

    if($error!="")
        return FALSE;

    $bool=eliminar_virtual_sistema($email,$error);

    return $bool;
}

function eliminar_virtual_sistema($email,&$error){
    $config=new paloConfig("/etc/postfix","virtual","\t","[[:space:]?\t[:space:]?]");     
    $arr_direcciones=$config->leer_configuracion();

    $eliminado=FALSE;
    foreach($arr_direcciones as $key=>$fila){
        if(isset($fila['clave']) && $fila['clave']==$email){
             unset($arr_direcciones[$key]);
             $eliminado=TRUE;
        }
        elseif(preg_match("/^$email/",$fila)){
             unset($arr_direcciones[$key]);
             $eliminado=TRUE;
        }
    }
    if($eliminado){
        $bool=$config->escribir_configuracion($arr_direcciones,true);
        if($bool){
            exec("sudo -u root postmap /etc/postfix/virtual",$output);
            if(is_array($output) && count($output)>0)
                foreach($output as $linea)
                    $error.=$linea."<br>";
        }
        else{
            $error.=$config->getMessage();
            return FALSE;
        }
    }

    return TRUE;
}






function crear_usuario_correo_sistema($email,$username,$clave,&$error,$virtual=TRUE){
    $output=array();

    exec("echo \"$clave\" | sudo -u root /usr/sbin/saslpasswd2 -c $username -u ".SASL_DOMAIN,$output);


    if(is_array($output) && count($output)>0){
        foreach($output as $linea_salida)
            $error.=$linea_salida."<br>";
    }
    if($error!="") 
        return FALSE;
    elseif($virtual){
        $bool=crear_virtual_sistema($email,$username,$error);
        if(!$bool)
            return FALSE;
        else
            return TRUE;
    }
    else
        return TRUE;

}


function crear_virtual_sistema($email,$username,&$error){
    $output=array();

    exec("sudo -u root chown asterisk /etc/postfix/virtual");
    $username.='@'.SASL_DOMAIN;
    exec("echo \"$email \t $username\" >> /etc/postfix/virtual",$output);

    if(is_array($output) && count($output)>0){
        foreach($output as $linea)
            $error.=$linea."<br>";
    }   
    exec("sudo -u root chown root /etc/postfix/virtual");

    exec("sudo -u root postmap /etc/postfix/virtual",$output);
    if(is_array($output) && count($output)>0){
         foreach($output as $linea)
            $error.=$linea."<br>";
    }
    if($error!="")
        return FALSE;
    else
        return TRUE;
}
function eliminar_cuenta($db,$username,$errMsg){
    global $CYRUS;
    $arr_alias=array();
    $pEmail = new paloEmail($db);
    //primero se obtienen las direcciones de mail del usuario (virtuales)
    $arrAlias=$pEmail->getAliasAccount($username);
    if (is_array($arrAlias)){
        foreach ($arrAlias as $fila)
            $arr_alias[]=$fila[1];
    }
    //servira hacerlo como transaccion??????
    $bExito = $pEmail->deleteAliasesFromAccount($username);
    if($bExito){
		$bExito = $pEmail->deleteAccount($username);
		if ($bExito){
			$cyr_conn = new cyradm;
			$bValido = $cyr_conn->imap_login();

			if ($bValido ===FALSE){
				$errMsg = $cyr_conn->getMessage();
				return FALSE;
			}

			$bValido=$cyr_conn->deletemb("user/".$username);
			if($bValido===FALSE){
				$errMsg=$cyr_conn->getMessage();
				return FALSE;
			}
			//$cyr_conn->deletemb("user/".$username)."<br>";

			foreach($arr_alias as $alias){
				if(!eliminar_usuario_correo_sistema($username,$alias,$errMsg)){
					return FALSE;
				}
			}

			return TRUE;
		}
	}else{
        $bExito = FALSE;
    }
    return $bExito;
}

?>