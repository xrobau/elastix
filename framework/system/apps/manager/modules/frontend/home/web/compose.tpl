<div id='elx-compose-email'>
    <div id='compose-headers-div'>
        <table id='compose-headers-table'>
            <tr id='compose-to'>
                <td></td>
                <td class='elx_compose_htd'><label for='compose_to'>{$TO}:</label><textarea name='compose_to' class='elx_compose_textarea'></textarea></td>
            </tr>
            <tr id='compose-cc' style='display:none'>
                <td class='elx-del-compose-header'> <span class='glyphicon glyphicon-minus-sign'></span></td>
                <td class='elx_compose_htd'><label for='compose_cc'>{$CC}:</label><textarea name='compose_cc' class='elx_compose_textarea' ></textarea></td>
            </tr>
            <tr id='compose-bcc' style='display:none'>
                <td class='elx-del-compose-header'> <span class='glyphicon glyphicon-minus-sign'></span></td>
                <td class='elx_compose_htd'><label for='compose_cco'>{$BCC}:</label><textarea name='compose_bcc' class='elx_compose_textarea'></textarea></td>
            </tr>
            <tr id='compose-reply_to' style='display:none'>
                <td class='elx-del-compose-header'> <span class='glyphicon glyphicon-minus-sign'></span></td>
                <td class='elx_compose_htd'><label for='compose_replay_to'>{$REPLYTO}:</label><textarea name='compose_replay_to' class='elx_compose_textarea'></textarea></td>
            </tr>
            <tr id='compose-extra-headers'>
                <td></td>
                <td class='elx_compose_htd'>
                <a href="#cc" id='elx_link_cc' onclick='showComposeHeader("cc")' class='elx_compose_header_link'>{$CC}</a>
                <span> | </span>
                <a href="#bcc" id='elx_link_bcc' onclick='showComposeHeader("bcc")' class='elx_compose_header_link'>{$BCC}</a>
                <span> | </span>
                <a href="#reply_to" id='elx_link_reply_to' onclick='showComposeHeader("reply_to")' class='elx_compose_header_link'>{$REPLYTO}</a>
                </td>
            </tr> 
            <tr id='compose-subject' style='margin:5px 0'>
                <td></td>
                <td class='elx_compose_htd'><label for='compose_to'>{$SUBJECT}:</label><input name='compose-subject' style='width:100%; border:1px solid #999;'></input><td>
            </tr>
        </table>
    </div>
    <div id='elx-compose-msg-attach' style='margin-bottom: 2px;'>
        <div class='elx-compose-msg-attachitem' id='login_loading_attach' style='display:none'>
            <img src='{$WEBCOMMON}images/loading.gif' /> {$TEXT_UPLOADING}
        </div>
    </div>
    <div id='elx-compose-msg'>
    </div>
</div>
<input type='hidden' name='elx_language' value="{$USER_LANG}">
<input type='hidden' name='msg_emptyto' value="{$MSG_EMPTYTO}">
<input type='hidden' name='msg_emptysubject' value="{$MSG_SUBJECT}">
<input type='hidden' name='msg_emptycontent' value="{$MSG_CONTENT}">
<input type='hidden' name='elx_txtuploading' value='{$TEXT_UPLOADING}'>
{literal}
    <style type="text/css">
        .mce-widget button {
            height: 28px;
        }
    </style>
{/literal}