<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0                                                  |
  | http://www.elastix.com                                               |
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
*/

$documentRoot = "/var/www/html";
$databaseRoot = "/var/www/db";
require_once "$documentRoot/libs/paloSantoDB.class.php";

if(existsDBTable($databaseRoot,"provider_account","trunk.db")){
    $pDBtrunk = new paloDB("sqlite3:///$databaseRoot/trunk.db");
    $pDBvoipprovider = new paloDB("sqlite3:///$databaseRoot/voipprovider.db");
    $query = "SELECT * FROM provider_account";
    $result = $pDBtrunk->fetchTable($query,TRUE);
    if($result === FALSE){
        echo "Database Error: ".$pDBtrunk->errMsg;
        exit(1);
    }
    foreach($result as $account){
        if(isset($account["id"])){
            $arrValues = array();
            $arrValues[]  = $account["id"];
            $arrValues[]  = (isset($account["account_name"])) ?$account["account_name"]   :"";
            $arrValues[]  = (isset($account["username"]))     ?$account["username"]       :"";
            $arrValues[]  = (isset($account["password"]))     ?$account["password"]       :"";
            $arrValues[]  = (isset($account["callerID"]))     ?$account["callerID"]       :"";
            $arrValues[]  = (isset($account["type"]))         ?$account["type"]           :"";
            $arrValues[]  = (isset($account["qualify"]))      ?$account["qualify"]        :"";
            $arrValues[]  = (isset($account["insecure"]))     ?$account["insecure"]       :"";
            $arrValues[]  = (isset($account["host"]))         ?$account["host"]           :"";
            $arrValues[]  = (isset($account["fromuser"]))     ?$account["fromuser"]       :"";
            $arrValues[]  = (isset($account["fromdomain"]))   ?$account["fromdomain"]     :"";
            $arrValues[]  = (isset($account["dtmfmode"]))     ?$account["dtmfmode"]       :"";
            $arrValues[]  = (isset($account["disallow"]))     ?$account["disallow"]       :"";
            $arrValues[]  = (isset($account["context"]))      ?$account["context"]        :"";
            $arrValues[]  = (isset($account["allow"]))        ?$account["allow"]          :"";
            $arrValues[]  = (isset($account["trustrpid"]))    ?$account["trustrpid"]      :"";
            $arrValues[]  = (isset($account["sendrpid"]))     ?$account["sendrpid"]       :"";
            $arrValues[]  = (isset($account["canreinvite"]))  ?$account["canreinvite"]    :"";
            $arrValues[]  = (isset($account["type_trunk"]))   ?$account["type_trunk"]     :"";
            $arrValues[]  = (isset($account["status"]))       ?$account["status"]         :"";
            $arrValues[]  = (isset($account["id_provider"]))  ?$account["id_provider"]    :"";
            $arrValues[]  = (isset($account["id_trunk"]))     ?$account["id_trunk"]       :"";

            $query = "INSERT INTO provider_account (id,account_name,username,password,callerID,type,qualify,insecure,host,fromuser,fromdomain,dtmfmode,disallow,context,allow,trustrpid,sendrpid,canreinvite,type_trunk,status,id_provider,id_trunk) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $result = $pDBvoipprovider->genQuery($query,$arrValues);
            if($result === FALSE){
                echo "Database Error: ".$pDBvoipprovider->errMsg;
                exit(1);
            }
        }
    }
    dropTable("provider_account",$pDBtrunk);
    if(existsDBTable($databaseRoot,"attribute","trunk.db"))
        dropTable("attribute",$pDBtrunk);
    if(existsDBTable($databaseRoot,"provider","trunk.db"))
        dropTable("provider",$pDBtrunk);
}

function dropTable($table,&$pDB)
{
    $query = "DROP TABLE $table";
    $result = $pDB->genExec($query);
    if($result === FALSE){
        echo "Database Error: ".$pDB->errMsg;
        exit(1);
    }

}

function existsDBTable($databaseRoot,$table,$dbName)
{
    exec("sqlite3 $databaseRoot/$dbName '.tables $table'",$arrConsole,$flagStatus);
    if(isset($arrConsole[0]) && $arrConsole[0] == $table)
        return TRUE;
    else
        return FALSE;
}

?>
