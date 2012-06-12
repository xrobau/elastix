<!--Comentario:  He agregado variables para que se muestre la misma vista de la 160-->
<form method="POST" enctype="multipart/form-data" action="?menu=file_editor{$action}">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center" >
    <tr class="moduleTitle">
    </tr>
    <tr>
        <td width="12%" align="left"><input class="button" type="submit" name="submit_new_file" value="{$NEW_FILE}"></td>
        <td width="12%" align="right">{$file.LABEL}: </td>
        <td width="12%" align="left" nowrap>{$file.INPUT}</td>
        <td width="24%" align="left"><input class="button" type="submit" name="filter" value="{$Filter}" ></td>
    </tr>
    <tr width="99%" border="0" cellspacing="0" cellpadding="0" >
        <!--Mensaje de error si no es un directorio válido-->
        <td class="mb_message"><b>{$msj_err}</b></td>
	</tr>
</table>
</form>
