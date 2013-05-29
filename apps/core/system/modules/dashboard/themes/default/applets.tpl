<table width="80%" cellspacing="0" id="columns"  align="center">
    <tr>
            {$AppletsPanels}
    </tr>
<input type="hidden" id="loading" value="{$loading}"/>
</table>
<script language="javascript" type = "text/javascript">
    /*loadAppletData();*/
    {literal}
    $('a[id^="refresh_"]').each(function (i, e) { refresh(e); });
    {/literal}
</script>
