<form id="setup_monitor" method='POST' action='?menu={$module_name}' enctype="multipart/form-data">
    <table class="setupbuttons">
{*    
        <tr class="moduleTitle">
            <td class="moduleTitle" colspan='2'>&nbsp;&nbsp;<img src="{$IMG}" alt=""/>&nbsp;&nbsp;{$title}</td>
        </tr>
*}        
        <tr class="letra12">
            <td style='text-align:left'>
            {if $code_status neq 'nf' && $code_status neq 'nre'}
                <input class="button" type="submit" name="action_{$code_status}" value="{$SERVICE_VALUE}">
            {/if}
            </td>
        </tr>
    </table>
    <table class="tabForm data" >
        <tr class="letra12">
            <td>{$status.LABEL}:</td>
            <td><span class="status_{$class_status}">{$status.INPUT}</span></td>
        </tr>
        <tr class="letra12">
            <td>{$server_key.LABEL}:</td>
            <td>{$server_key.INPUT}</td>
        </tr>
    </table>
</form>