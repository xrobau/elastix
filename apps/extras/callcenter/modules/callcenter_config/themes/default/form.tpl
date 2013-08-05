<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
{if !$FRAMEWORK_TIENE_TITULO_MODULO}
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle" />&nbsp;&nbsp;{$title}</td>
        <td></td>
    </tr>
{/if}    
    <tr class="letra12">
        <td align="left"><input class="button" type="submit" name="save" value="{$SAVE}"></td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table width="100%">
    <tr><td width="100%">
        <table class="tabForm">
			<tr class="letra12">
				<td align="center" colspan="2"><b>{$ASTERISK_CONNECT_PARAM}</b></td>
				<td align="center" colspan="2"><b>{$DIALER_PARAM}</b></td>
			</tr>
			<tr class="letra12">
				<td align="right">{$asterisk_asthost.LABEL}:</td><td align="left">{$asterisk_asthost.INPUT}</td>
				<td align="right">{$dialer_llamada_corta.LABEL}:</td><td align="left">{$dialer_llamada_corta.INPUT}</td>				
			</tr>
			<tr class="letra12">
				<td align="right">{$asterisk_astuser.LABEL}:</td><td align="left">{$asterisk_astuser.INPUT}</td>
				<td align="right">{$dialer_tiempo_contestar.LABEL}:</td><td align="left">{$dialer_tiempo_contestar.INPUT}</td>				
			</tr>
			<tr class="letra12">
				<td align="right">{$asterisk_astpass_1.LABEL}:</td><td align="left">{$asterisk_astpass_1.INPUT}</td>
				<td align="right">{$dialer_qos.LABEL}:</td><td align="left">{$dialer_qos.INPUT}</td>								
			</tr>
			<tr class="letra12">
				<td align="right">{$asterisk_astpass_2.LABEL}:</td><td align="left">{$asterisk_astpass_2.INPUT}</td>
                <td align="right">{$dialer_timeout_originate.LABEL}:</td><td align="left">{$dialer_timeout_originate.INPUT}</td>
			</tr>
            <tr class="letra12">
                <td align="right">{$asterisk_duracion_sesion.LABEL}:</td><td align="left">{$asterisk_duracion_sesion.INPUT}</td>
                <td align="right">{$dialer_timeout_inactivity.LABEL}:</td><td align="left">{$dialer_timeout_inactivity.INPUT}</td>
            </tr>
            <tr class="letra12">
                <td colspan="2">&nbsp;</td>
                <td align="right">{$dialer_debug.LABEL}:</td><td align="left">{$dialer_debug.INPUT}</td>
            </tr>
            <tr class="letra12">
                <td colspan="2">&nbsp;</td>
                <td align="right">{$dialer_allevents.LABEL}:</td><td align="left">{$dialer_allevents.INPUT}</td>                
            </tr>
            <tr class="letra12">
                <td colspan="2">&nbsp;</td>
                <td align="right">{$dialer_overcommit.LABEL}:</td><td align="left">{$dialer_overcommit.INPUT}</td>                
            </tr>
            <tr class="letra12">
                <td colspan="2">&nbsp;</td>
                <td align="right">{$dialer_predictivo.LABEL}:</td><td align="left">{$dialer_predictivo.INPUT}</td>                
            </tr>
        </table>
    </td></tr>
    <tr><td align="center">
        <table class="tabForm" style="font-size: 16px;" >
			<tr class="letra12">
				<td align="center" colspan="2"><b>{$DIALER_STATUS_MESG}</b></td>
			</tr>
			<tr class="letra12">
				<td align="right">{$CURRENT_STATUS}:</td><td>{$DIALER_STATUS}</td>
			</tr>
			<tr class="letra12">
				<td align="center" colspan="2"><input class="button" type="submit" name="dialer_action" value="{$DIALER_ACTION}"></td>
			</tr>
		</tr>
    </td></tr>
</table>
