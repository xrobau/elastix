<div id="div_applet_admin">
    <table width="240" border="0" cellspacing="0" cellpadding="4">
        <tr class="moduleTitle">
            <td class="moduleTitle" valign="middle" colspan='2'>&nbsp;&nbsp;<img src="{$IMG}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
        </tr>
        <tr class="letra12">
            <td align="right">
                <input class="button" type="submit" name="save_new" value="{$SAVE}">&nbsp;&nbsp;
                <input class="button" type="button" name="cancel" value="{$CANCEL}" id="close_applet_admin">
            </td>
        </tr>
    </table>
    <table class="tabForm" style="font-size: 16px;" width="240" border="0">
        <tr class="letra12">
            <td align="left"><b>{$Applet}</b></td>
            <td align="left"><b>{$Activated}</b></td>
        </tr>
        {foreach from=$applets key=q item=applet name=appletrow}
            <tr class="letra12">
                <td align="left">
                    <b>{$applet.name}:</b>
                </td>
                <td align="center">
                    <input name="chkdau_{$applet.id}" type="checkbox" {if $applet.activated} checked="checked" {/if}> 
                </td>
            </tr>
        {/foreach}
    </table>
</div>
{literal}
<script type='text/javascript'>
var statusDivAppletAdmin='closed';
document.getElementById('div_applet_admin').style.display = 'none';
</script>
{/literal}
