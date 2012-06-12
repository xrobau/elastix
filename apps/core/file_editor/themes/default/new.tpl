<!--Comentario:  He agregado variables para que se muestre la misma vista de la 160-->
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center" >
    <tr class="moduleTitle">
    </tr>
    <tr>
        <td width="12%" align="right">{$file.LABEL}: </td>
        <td width="12%" align="left" nowrap>{$file.INPUT}</td>
        <td width="24%" align="left"><input class="button" type="submit" name="filter" value="{$Filter}" /></td>
        <td align="right"><a href="{$url_new}" style="text-decoration: none;"><button class="button">{$NEW_FILE}&nbsp;&raquo;</button></a>
    </tr>
    <tr width="99%" border="0" cellspacing="0" cellpadding="0" >
        <!--Mensaje de error si no es un directorio válido-->
        <td class="mb_message"><b>{$msj_err}</b></td>
	</tr>
</table>

