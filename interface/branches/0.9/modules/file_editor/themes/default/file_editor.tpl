<!--Comentario:  He agregado variables para que se muestre la misma vista de la 160-->
<form method="POST" enctype="multipart/form-data" action="?menu=file_editor{$action}">
<table class="message_board" width="99%" border="0" cellspacing="0" cellpadding="0" >
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;
            <img src="images/user.png" border="0" align="absmiddle">&nbsp;&nbsp;{$title}
        </td>
    </tr>
    <tr>
        <td class="mb_message">
            <font size="2px">{$se_guardo}<br>{$msj_no_escritura3}<br>{$msj_no_lectura2}</font>
        </td>
    </tr>
    <tr>
        <td>
            <center>
                <b>{$File}:</b> {$fichero}
            </center>
        </td>
    </tr>
    <tr>
        <td>
            <center>
                {$contenido}
            </center>
        </td>
    </tr>
    <tr>
        <td>
            <center>
                <input type="submit" name="back" id="back"  onclick="" value="<<{$Back}">
                {$Save}
            </center>
        </td>
    </tr>
</table>
<input type="hidden" name="url_get" id="url_get"  onclick="" value="{$url_get}">
</form>
