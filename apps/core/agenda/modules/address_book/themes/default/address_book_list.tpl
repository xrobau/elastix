<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <title>Elastix</title>
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/styles.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/help.css">
{if $THEMENAME eq "tenant"}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/bootstrap.css">
    <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/neon-core.css">
    <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/neon-theme.css">
    <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/neon-forms.css">
    <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/custom.css">

    <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/widgets.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="{$WEBPATH}themes/{$THEMENAME}/header.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="{$WEBPATH}themes/{$THEMENAME}/content.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="{$WEBPATH}themes/{$THEMENAME}/applet.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="{$WEBPATH}libs/js/sticky_note/sticky_note.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="{$WEBPATH}themes/{$THEMENAME}/table.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="{$WEBPATH}themes/{$THEMENAME}/rightbar.css" />
{/if}
        {$HEADER_LIBS_JQUERY}
        <script src="{$path}libs/js/base.js"></script>
        <script src="{$path}modules/{$module_name}/themes/default/js/javascript.js"></script>
    </head>
    <body {$BODYPARAMS}>
        {if $THEMENAME eq "tenant"}
            <div class="neo-module-content">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" class="message_board">
              <tbody style="display:none" id="table_error"><tr>
                <td valign="middle" class="mb_title" id="mb_title"></td>
                </tr>
                <tr>
                    <td valign="middle" class="mb_message" id="mb_message"></td>
                </tr>
            </tbody></table>
            <div class="moduleTitle">
              &nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}
            </div>
            {$CONTENT}
            </div>
        {elseif $THEMENAME eq "elastixneo"}
          <div>
            <div class="elxneo-module-title">
                <div class="name-left"></div>
                <span class="name">
                      {if $icon ne null}
                      <img src="{$icon}" width="22" height="22" align="absmiddle" />
                      {/if}
                      &nbsp;{$title}</span>
                <div class="name-right"></div>
            </div>
           <input type="hidden" id="elastix_framework_module_id" value="{$module_name}" />
           <div id="elxneo-content">
              <div class="div_msg_errors" id="message_error" style="display:none;">
              <div style="float:left;">
                  <span id="mb_title" style="color:red;font-weight:bold"></span>
                  <br />
                  <span id="mb_message"></span>
              </div>
              <div style="text-align:right; padding:5px">
                  <input type="button" onclick="hide_message_error();" value="{$md_message_title}"/>
              </div>
            </div>
              {$CONTENT}
           </div>
        {else}
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" class="message_board">
              <tbody style="display:none" id="table_error"><tr>
                <td valign="middle" class="mb_title" id="mb_title"></td>
                </tr>
                <tr>
                    <td valign="middle" class="mb_message" id="mb_message"></td>
                </tr>
            </tbody></table>
            <div class="moduleTitle">
              &nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}
            </div>
            {$CONTENT}
        {/if}
{if $THEMENAME eq "tenant"}
        <!-- Bottom Scripts -->
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/gsap/main-gsap.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/bootstrap.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/joinable.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/resizeable.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/neon-api.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/jquery.validate.min.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/neon-login.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/neon-custom.js"></script>
        <script type='text/javascript' src="{$WEBPATH}themes/{$THEMENAME}/js/neon-demo.js"></script>
{/if}
    </body>
</html>
