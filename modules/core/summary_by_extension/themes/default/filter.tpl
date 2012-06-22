<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <td width="10%">&nbsp;</td>
        <td align="right" width="25%">{$date_from.LABEL}:</td>
        <td align="left" width="17%">{$date_from.INPUT}</td>
        <td align="right" width="8%">{$date_to.LABEL}:</td>
        <td align="left">{$date_to.INPUT}</td>
        <td>&nbsp;</td>
    </tr>
    <tr class="letra12">
        <td>&nbsp;</td>
        <td align="right">{$option_fil.LABEL}&nbsp;{$option_fil.INPUT}</td>
        <td align="left">{$value_fil.INPUT}</td>
        <td>&nbsp;</td>
        <td align="left"><input class="button" type="submit" name="show" value="{$SHOW}"></td>
        <td>&nbsp;</td>
    </tr>
</table>

{literal}
<script type= "text/javascript">

function popup_ventana(url_popup)
{
    var ancho = 750;
    var alto = 530;
    my_window = window.open(url_popup,"my_window","width="+ancho+",height="+alto+",location=yes,status=yes,resizable=yes,scrollbars=yes,fullscreen=no,toolbar=yes");
    my_window.moveTo((screen.width-ancho)/2,(screen.height-alto)/2);
    my_window.document.close();
}

</script>
{/literal}