{literal}
<script>
function reload() {
    xajax_create_report();
    setTimeout("reload()",5000);
}
reload();
</script>

{/literal}
<form  method='POST' style='margin-bottom:0;' action={$url}>
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
    {$filter} <!-- EN EL CODIGO ESTA EL TR Y EL TD -->
    <tr class="letra12">
        <td width="10%" align="left">
            <div id="body_report">
            {$columns}
            </div>
        </td>
    </tr>
</table>
</form>