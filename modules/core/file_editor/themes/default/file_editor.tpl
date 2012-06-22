<!--Comentario:  He agregado variables para que se muestre la misma vista de la 160-->
<form method="POST" enctype="multipart/form-data" action="{$url_edit}">
<table class="message_board" width="99%" border="0" cellspacing="0" cellpadding="0" >
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;
            <img src="images/user.png" border="0" align="absmiddle" />&nbsp;&nbsp;{$title}
        </td>
    </tr>
    <tr>
        <td class="mb_message">
            <font size="2px">{$msg_status}</font>
        </td>
    </tr>
    <tr>
        <td>
        <a href="{$url_back}" style="text-decoration: none;"><b>&laquo;&nbsp;{$LABEL_BACK}</b></a>
        <b>{$basename.LABEL}:</b>&nbsp;{$basename.INPUT}{$LABEL_COMPLETADO}&nbsp;&nbsp;
        <input type="submit" class="button" name="Guardar" value="{$LABEL_SAVE}" />&nbsp;&nbsp;
        <input type="submit" class="button" name="Reload"  value="{$RELOAD_ASTERISK}" />
        </td>
    </tr>
    <tr>
        <td>{$content.INPUT}</td>
    </tr>
</table>
</form>
