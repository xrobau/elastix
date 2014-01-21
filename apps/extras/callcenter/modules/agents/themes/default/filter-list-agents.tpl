<table width="100%" border="0">
<tr>
	<td class="letra12" width="20%" align="right"><b>{$LABEL_STATE}:</b></td>
    <td>{html_options name=cbo_estado id=cbo_estado options=$estados selected=$estado_sel onchange='submit();'}</td>
    <td align="right"><a href="?menu={$MODULE_NAME}&amp;action=new_agent"><b>{$LABEL_CREATE_AGENT}&nbsp;&raquo;</b></a></td>
</tr>
<tr>
    <td class='letra12' width='20%' align="right"><b>{$LABEL_WITH_SELECTION}:</b></td>
    <td colspan='2'>
        <input class="button" type="submit" name="disconnect" value="{$LABEL_DISCONNECT}" />&nbsp;
        <input class="button" type="submit" name="delete" value="{$LABEL_DELETE}" onclick="return confirmSubmit('{$MESSAGE_CONTINUE_DELETE}')" />
     </td>
</tr>
</table>
<input type="hidden" name="reparar_file" id="reparar_file" value="" />
<input type="hidden" name="reparar_db" id="reparar_db" value="" />
<script language='JavaScript' type='text/javascript'>
var pregunta_borrar_agente_conf = "{$PREGUNTA_BORRAR_AGENTE_CONF}";
var pregunta_agregar_agente_conf = "{$PREGUNTA_AGREGAR_AGENTE_CONF}";
</script>