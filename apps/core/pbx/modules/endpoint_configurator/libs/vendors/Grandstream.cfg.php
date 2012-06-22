<?php
/*
    PrincipalFileGrandstream nos retorna el contenido del archivo de configuracion de los EndPoint
    Grandstream, para ello es necesario enviarle el DisplayName, id_device, secret, ipAdressServer
*/
function PrincipalFileGrandstream($DisplayName, $id_device, $secret, $arrParameters, $ipAdressServer, $model)
{
    if($model == "GXV3140"){
            $content="
        # Firmware Server Path
        P192 = $ipAdressServer
        
        # Config Server Path
        P237 = $ipAdressServer
        
        # Firmware Upgrade. 0 - TFTP Upgrade,  1 - HTTP Upgrade.
        P212 = 0
        
        # Account Name
        P417 = $DisplayName
        
        # SIP Server
        P402 = $ipAdressServer
        
        # Outbound Proxy
        P403 = $ipAdressServer
        
        # SIP User ID
        P404 = $id_device
        
        # Authenticate ID
        P405 = $id_device
        
        # Authenticate password
        P406 = $secret
        
        # Display Name (John Doe)
        P407 = $DisplayName";
    }
    elseif($model == "GXP2120" || $model == "GXV3175"){
        $content="
    
    # Firmware Server Path
    P192 = $ipAdressServer
    
    # Config Server Path
    P237 = $ipAdressServer
    
    # Firmware Upgrade. 0 - TFTP Upgrade,  1 - HTTP Upgrade.
    P212 = 0
    
    # Account Name
    P270 = $DisplayName
    
    # SIP Server
    P47 = $ipAdressServer
    
    # Outbound Proxy
    P48 = $ipAdressServer
    
    # SIP User ID
    P35 = $id_device
    
    # Authenticate ID
    P36 = $id_device
    
    # Authenticate password
    P34 = $secret
    
    # Display Name (John Doe)
    P3 = $DisplayName";
    }
    else{
        $content="
    
    # Firmware Server Path
    P192 = $ipAdressServer
    
    # Config Server Path
    P237 = $ipAdressServer
    
    # Firmware Upgrade. 0 - TFTP Upgrade,  1 - HTTP Upgrade.
    P212 = 0
    
    # Account Name
    P270 = $DisplayName
    
    # SIP Server
    P47 = $ipAdressServer
    
    # Outbound Proxy
    P48 = $ipAdressServer
    
    # SIP User ID
    P35 = $id_device
    
    # Authenticate ID
    P36 = $id_device
    
    # Authenticate password
    P34 = $secret
    
    # Display Name (John Doe)
    P3 = $DisplayName";
    }
    return $content;
}

function templatesFileGrandstream($ipAdressServer)
{
    $content= <<<TEMP
# SIP Server
P47 = $ipAdressServer

# Outbound Proxy
P48 = $ipAdressServer

# SIP User ID
P35 = 8000

# Authenticate ID
P36 = 8000

# Authenticate password
P34 = 0000

# Display Name (John Doe)
P3 = 

# DHCP support. 0 - yes, 1 - no
P8 = 1
TEMP;
    return $content;
}

/**
 * Procedimiento para codificar la configuración en formato INI en el formato
 * binario que espera el teléfono Grandstream. Este procedimiento reemplaza a
 * la llamada al programa externo GS_CFG_GEN/bin/encode.sh.
 * 
 * @param   string  $sMac MAC del teléfono Grandstream en formato aabbccddeeff
 * @param   string  $sTxtConfig Bloque de configuración en formato INI
 * 
 * @return  string  Bloque binario codificado listo para escribir al archivo
 */
function grandstream_codificar_config($sMAC, $sTxtConfig)
{
    $sBloqueConfig = '';

    // Validar y codificar la MAC del teléfono
    if (!preg_match('/^[[:xdigit:]]{12}$/', $sMAC)) return FALSE;

    // Parsear y codificar las variables de configuración
    $params = array();
    foreach (preg_split("/(\x0d|\x0a)+/", $sTxtConfig) as $s) {
        $s = trim($s);
        if (strpos($s, '#') === 0) continue;
        $regs = NULL;
        if (preg_match('/^(\w+)\s*=\s*(.*)$/', $s, $regs))
            $params[] = $regs[1].'='.rawurlencode($regs[2]);
    }
    $params[] = 'gnkey=0b82';
    $sPayload = implode('&', $params);
    if (strlen($sPayload) & 1) $sPayload .= "\x00";
    //if (strlen($sPayload) & 3) $sPayload .= "\x00\x00";
    
    // Calcular longitud del bloque en words, más el checksum
    $iLongitud = 8 + strlen($sPayload) / 2;
    $sPayload = pack('NxxH*', $iLongitud, $sMAC)."\x0d\x0a\x0d\x0a".$sPayload;
    $iChecksum = 0x10000 - (array_sum(unpack('n*', $sPayload)) & 0xFFFF);

    $sPayload[4] = chr(($iChecksum >> 8) & 0xFF);
    $sPayload[5] = chr(($iChecksum     ) & 0xFF);

    if ((array_sum(unpack("n*", $sPayload)) & 0xFFFF) != 0) 
        die('Suma de verificación inválida');
    return $sPayload;
}

?>