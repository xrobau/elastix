/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function validateRegister()
{
    var selec = document.getElementById("type_provider");

    if(selec.value=="none"){
        alert("Please select a type VoIp Provider");
        document.getElementById("type_provider").focus();
        return false;
    }else
        return true;
}
