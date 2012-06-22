<table width="100%" border="0">
<tr>
    <td align="right" class="letra12" width="20%" ><b>{$LABEL_CAMPAIGN_STATE}:</b></td>
    <td>{html_options name=cbo_estado id=cbo_estado options=$estados selected=$estado_sel onchange='submit();'}</td>
    <td align="right"><a href="?menu={$MODULE_NAME}&amp;action=new_campaign"><b>{$LABEL_CREATE_CAMPAIGN}&nbsp;&raquo;</b></a></td>
</tr>
<tr>
    <td align="right" class='letra12' width='20%'><b>{$LABEL_WITH_SELECTION}:</b></td>
    <td colspan='2'><input class="button" type="submit" name="activate" value="{$LABEL_ACTIVATE}" />&nbsp;
        <input class="button" type="submit" name="deactivate" value="{$LABEL_DEACTIVATE}" onclick="return confirmSubmit('{$MESSAGE_CONTINUE_DEACTIVATE}')" />&nbsp;
        <input class="button" type="submit" name="delete" value="{$LABEL_DELETE}" onclick="return confirmSubmit('{$MESSAGE_CONTINUE_DELETE}')" />
     </td>
</tr>
</table>

