<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
  <title>Elastix</title>
  <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css" />
  <link rel="stylesheet" href="themes/{$THEMENAME}/help.css" />
  <link rel="stylesheet" media="screen" type="text/css" href="libs/js/sticky_note/sticky_note.css" />
  {$HEADER_LIBS_JQUERY}
  <script type="text/javascript" src="libs/js/base.js"></script>
  <script type='text/javascript' src="libs/js/sticky_note/sticky_note.js"></script>
  <script type="text/javascript" src="libs/js/iframe.js"></script>
  <script type='text/javascript' src="themes/{$THEMENAME}/js/javascript.js"></script>
  {$HEADER}
  {$HEADER_MODULES}
</head>
<body {$BODYPARAMS}>
<div id="elxneo-topnav-toolbar">
{$MENU}
{if !empty($mb_message)}
<br />
<div class="div_msg_errors" id="message_error">
    <div style="height:24px">
        <div class="div_msg_errors_title" style="padding-left:5px"><b style="color:red;">&nbsp;{$mb_title}</b></div>
        <div class="div_msg_errors_dismiss"><input type="button" onclick="hide_message_error();" value="{$md_message_title}"/></div>
    </div>
    <div style="padding:2px 10px 2px 10px">{$mb_message}</div>
</div>
{/if}
{$CONTENT}
</div>{* #elxneo-content *}
</div>{* #elxneo-maincolumn *}
<div id="neo-lengueta-minimized" {if $isThirdLevel eq 'on' and $viewMenuTab ne 'true'}style="display: none;"{/if}></div>
</div>{* #elxneo-wrap *}

{* Pie de página con copyright de Elastix *}
<div id="elxneo-footerbox">
    <a href="http://www.elastix.org" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.
</div>

{* Popup de Sticky Note *}
<div id="neo-sticky-note">
    <div id="neo-sticky-note-text"></div>
    <div id="neo-sticky-note-text-edit">
        <textarea id="neo-sticky-note-textarea"></textarea>
        <div id="neo-sticky-note-text-char-count"></div>
        <input type="button" value="{$SAVE_NOTE}" id="neo-submit-button" />
        <div id="auto-popup">AutoPopUp <input type="checkbox" id="neo-sticky-note-auto-popup" value="1"></div>
    </div>
    <div id="neo-sticky-note-text-edit-delete"></div>
</div>
{* SE GENERA EL AUTO POPUP SI ESTA ACTIVADO *}
{if $AUTO_POPUP eq '1'}{literal}
<script type='text/javascript'>
$(document).ready(function(e) {
    $("#neo-sticky-note-auto-popup").prop('checked', true);
    $('#togglestickynote1').click();
});
</script>
{/literal}{/if}

<!-- Neo Progress Bar -->
<div class="neo-modal-elastix-popup-box">
    <div class="neo-modal-elastix-popup-title"></div>
    <div class="neo-modal-elastix-popup-close"></div>
    <div class="neo-modal-elastix-popup-content"></div>
</div>
<div class="neo-modal-elastix-popup-blockmask"></div>
</body>
</html>
