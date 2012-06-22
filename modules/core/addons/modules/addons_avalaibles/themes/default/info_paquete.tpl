<div class="divTD">
    <div style="padding-bottom: 5px;">
	<div style="float: left; width: 41%;">
	    <b class="title_package">{$PAQUETE_NOMBRE}</b><span class="title_version">&nbsp;v{$PAQUETE_VERSION}-{$PAQUETE_RELEASE}</span><br/>
	</div>
	<div style="height: 30px;">
	    {$ACTION_INSTALL}
	</div>
    </div>
    <div class="contentStyle">
        <b>Developed by:</b>&nbsp;{$PAQUETE_CREADOR}<br/>
    </div>
    <div  class="contentStyle">
        <b>Description:</b>&nbsp;{$DESCRIPCION_PAQUETE|escape}
		<input style='display:none;' class='{$PAQUETE_RPM}' value='{$PAQUETE_NOMBRE}|{$PAQUETE_RPM}|{$PAQUETE_VERSION}|{$PAQUETE_RELEASE}' />
    </div>
    <div  class="contentStyle">
	<b>Location:</b>&nbsp;{$LOCATION|escape}
    </div>
    <input type="hidden" id="{$PAQUETE_RPM}_link" value="{$URL_BUY}"/>
</div>

