<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
        <title>Elastix</title>
        <link rel="stylesheet" href="{$WEBCOMMON}css/bootstrap.css" />
        <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/styles.css" />
        {$HEADER_LIBS_JQUERY}
        <script type='text/javascript' src="{$WEBCOMMON}js/jssip-0.3.0.min.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/base.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/uf.js"></script>
        {$HEADER}
        {$HEADER_MODULES}
    </head>
    <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="mainBody" {$BODYPARAMS}>
        <div id='elastix_app_body' class='elx_app_body'>
            {$MENU} <!-- Viene del tpl menu.tlp-->   
            <div id='main_content_elastix'>
                <div id='notify_change_elastix'>
                    <div class="progress progress-striped active">
                        <div class="progress-bar progress-bar-warning progress-bar-elastix" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 100% ">
                            Loading...
                        </div>
                    </div>
                </div>
                <div id='module_content_framework'>
                    {$CONTENT}
                </div>
            </div>
        </div>
        <div id="rightdiv"> <!--Este es el Div que se usa para el chat-->
            <div id="b3_1" style='display:none'>
                <div id='elx_im_personal_info'>
                    
                </div>
                <div id='elx_im_contact_search'>
                    <input type='text' maxlength='50' id='im_search_filter' name='im_search_filter' class='im_search_filter' >
                    <div class='contactSearchResult' class='contactSearchResult'>
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
            
        </div>
    </body>
</html>
