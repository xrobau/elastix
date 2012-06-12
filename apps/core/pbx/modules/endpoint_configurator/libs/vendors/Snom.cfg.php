<?php
/*
    The function creates content of phone specific settings file for SNOM 3XX. 
*/
function PrincipalFileSnom($DisplayName, $id_device, $secret, $arrParameters, $ipAdressServer)
{
    $content="
            <html>
                <pre>
                    user_realname1: $DisplayName
                    user_name1: $id_device
                    user_pass1: $secret
                    user_pname1: $id_device
                    user_mailbox1: $id_device
                </pre>
            </html>";

    return $content;
}

/*
    The function creates content of general settings file for SNOM 3XX. 
 */
function generalSettingsFileSnom($ipAdressServer)
{
    $content="
            <html>
                <pre>
                    challenge_response!: off
                    user_phone!: false
                    filter_registrar!: off
                    user_srtp1!: off
                    user_host1: $ipAdressServer
                    timezone!: GBR-0
                    tone_scheme!: USA		
                    ignore_security_warning!: on
                </pre>
            </html>";

    return $content;
}
?>
