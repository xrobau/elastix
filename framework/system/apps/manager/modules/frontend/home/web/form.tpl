<div id="filterdiv">
    <form id="filterform">
        <input type="search" id="search" placeholder="Search">
    </form>
</div>   
<div id="paneldiv">  
    <div id='list_folders'>
        <div id="leftdiv">
            <div id="b1_1">
                {foreach from=$MAILBOX_FOLDER_LIST key=k item=M_FOLDER}
                    <div class="folder" onclick="show_messages_folder('{$M_FOLDER}');">{$M_FOLDER}</div>
                {/foreach}
            </div>
        </div>
        <div id="display1" class="color1 ra_disp1_10">
            <div id="icn_disp1" class="cont_pic_tag ra_disp1_10" >
                <span class="icn_d">ë</span>
            </div>  
        </div>
    </div>
    <div id="centerdiv">
        <div id="b2_1">
            <div id="paginationdiv">
                <div id="tools-paginationdiv">
                    <div id='elx_email_fil_view' style='padding:10px' class='elx_email_pag_bar'>
                        <div class="btn-group elx_email_pag_btn">
                            <button type="button" class="btn btn-default btn-sm">{$VIEW} : <span id='elx_sel_view_filter'>{$ELX_MAIL_FILTER_OPT[$SELECTED_VIEW_FILTER]}</span></button>
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                            {foreach from=$ELX_MAIL_FILTER_OPT key=k item=view_filter}
                                <li><a href="#" id='elx_email_vsel_{$k}' onclick='search_email_message_view("{$k}")'>{$view_filter}</a></li>
                            {/foreach}
                            </ul>
                        </div>
                        <input type='hidden' name='elx_sel_view_filter_h' value='{$SELECTED_VIEW_FILTER}' data-value='{$ELX_MAIL_FILTER_OPT[$SELECTED_VIEW_FILTER]}'>
                    </div>
                    <div id='elx_email_mv' class='elx_email_pag_bar'>
                        <div class="btn-group elx_email_pag_btn" >
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                <span id='elx_email_mv_wtag'>{$MOVE_TO}:</span><span id='elx_email_mv_itag' class='glyphicon glyphicon-folder-open'></span><span class="caret"></span>
                            </button>
                            <ul id='elx_email_mv_ul' class="dropdown-menu" role="menu">
                            {foreach from=$MOVE_FOLDERS key=k item=mv_folder}
                                <li><a href="#" onclick='mv_msg_to_folder("{$k}")'>{$mv_folder}</a></li>
                            {/foreach}
                            </ul>
                        </div>
                    </div>
                    <div id='elx_email_mark_as' class='elx_email_pag_bar'> 
                        <div class="btn-group elx_email_pag_btn">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                <span id='elx_email_mark_wtag'>{$MARK_AS}:</span><span id='elx_email_mark_itag' class='glyphicon glyphicon-tag'></span> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                            {foreach from=$ELX_MAIL_MARK_OPT key=k item=opt}
                                <li><a href="#" onclick='mark_email_msg_as("{$k}")'>{$opt}</a></li>
                            {/foreach}
                            </ul>
                        </div>
                    </div>
                </div>
                <div id='elx-bodymsg-tools' style='display:none'>
                    <button type="button" class="btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-backward" onclick='return_mailbox()'></span> 
                    </button>
                    <div class="btn-group elx_email_pag_btn">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                            <span >{$ACTION_MSG}:</span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                        {foreach from=$ELX_EMAIL_MSG_ACT key=k item=opt}
                            <li><a href="#" onclick='actions_email_msg("{$k}")'>{$opt}</a></li>
                        {/foreach}
                        </ul>
                    </div>
                    <div id='elx_email_msg_arrows'>
                        <span class="glyphicon glyphicon-arrow-left" onclick='elx_email_pev_msg()'></span> 
                        <span class="glyphicon glyphicon-arrow-right" onclick='elx_email_next_msg()'></span> 
                    </div>
                </div>
            </div>
            <div id="email_contentdiv">
                <div id="elx_bodymail">
                </div>
                <div id="elx_mail_messages">
                    {if empty($MAILS)}
                        <div class="elx_row elx_unseen_email" style="text-align:center">{$NO_EMAIL_MSG}</div>
                    {else}
                        {foreach from=$MAILS key=k item=MAIL}
                            {foreach from=$MAIL key=j item=col_mail}
                                {$col_mail}
                            {/foreach}
                        {/foreach}
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
<input type='hidden' name='current_mailbox' val='{$CURRENT_MAILBOX}'>

