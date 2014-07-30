<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, target-densitydpi=device-dpi"/>
        <title>Elastix</title>
        <link rel="stylesheet" href="{$WEBCOMMON}css/bootstrap.min.css" />
        <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/styles.css" />
        {$HEADER_LIBS_JQUERY}
        <script type='text/javascript' src="{$WEBCOMMON}js/sip-0.5.0.js"></script>
	<script type='text/javascript' src="{$WEBCOMMON}js/bootstrap.min.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/bootstrap-paginator.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/jquery-title-alert.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/base.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/uf.js"></script>
        <script type="text/javascript" src="web/apps/home/tinymce/js/tinymce/tinymce.min.js"></script>
        {$HEADER}
        {$HEADER_MODULES}
    </head>
    <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="mainBody" {$BODYPARAMS}>
    <input type="hidden" id="elastix_framework_module_id" value="">
    <input type="hidden" id="elastix_framework_webCommon" value="">
    
        <div id='elastix_app_body' class='elx_app_body'>
            {$MENU} <!-- Viene del tpl menu.tlp-->   
            <div id='main_content_elastix'>
                <div id='notify_change_elastix' style='height: 20px;'>
                    <div class="progress progress-striped active">
                        <div class="progress-bar progress-bar-warning progress-bar-elastix" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 100% ">
                            Loading...
                        </div>
                    </div>
                </div>
                <div id="elx_msg_area" class="alert {if $MSG_ERROR_FIELD || $MSG_FIELD}elx_msg_visible {else} elx_msg_oculto{/if} alert-dismissable" style="text-align:center;margin:0;">
                    <button type="button" class="elx-msg-area-close close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p id="elx_msg_area_text" class='{if $MSG_ERROR_FIELD}alert-danger{else}alert-success{/if}'></p> 
                </div>
                <div id='module_content_framework'>
                    {$CONTENT}
                </div>
            </div>
        </div>
        <div id="rightdiv"> <!--Este es el Div que se usa para el chat-->
            <div id="b3_1" style='display:none'>
                <div id='head_rightdiv'>
                    <!--
                    <div id='elx_im_personal_info'>
                        
                    </div>
                    -->
                    <div id='elx_im_contact_search'>
                        <input type='text' maxlength='50' id='im_search_filter' name='im_search_filter' class='im_search_filter form-control input-sm' >
                        <div class='contactSearchResult' class='contactSearchResult'>
                        </div>
                    </div>
                </div>
                <div id='elx_im_list_contacts'>
                    <ul id='elx_ul_list_contacts' class='margin_padding_0'>
                    </ul>
                </div>
            </div>
            <div id='startingSession' style='position:relative'>
                <img id='login_loading_chat' style='display:inline' src='{$WEBCOMMON}images/loading.gif' /><span class='elx_contact_starting'>{$INT_SESSION}<span>
            </div>
        </div>
        <div id='elx_chat_space'>
            <div id='elx_notify_min_chat'  class='elx_nodisplay'>
                <div id='elx_list_min_chat' style='visibility:hidden'> 
                    <div>
                        <ul class='elx_list_min_chat_ul'>
                        </ul>
                    </div>
                </div>
                <input type='hidden' id='elx_hide_min_list' value='no'>
                <a id='elx_notify_min_chat_box' href="#" rel="toggle" role="button">
                    <span class="icn_d elx_icn_notify_chat">h</span>
                    <span id='elx_num_mim_chat'>0</span>
                </a>
            </div>
            <div id='elx_chat_space_tabs'>
            </div>
        </div>
    </body>
</html>
