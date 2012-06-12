<?php
/*
    PrincipalFileGrandstream nos retorna el contenido del archivo de configuracion de los EndPoint
    Grandstream, para ello es necesario enviarle el DisplayName, id_device, secret, ipAdressServer
*/
function PrincipalFileGrandstream($DisplayName, $id_device, $secret, $ipAdressServer, $model)
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
?>