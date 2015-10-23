<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
        <title>Elastix</title>
        <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css" />
        <link rel="stylesheet" href="themes/{$THEMENAME}/help.css" />
		<link rel="stylesheet" media="screen" type="text/css" href="libs/js/sticky_note/sticky_note.css" />
	{$HEADER_LIBS_JQUERY}
        <script src="libs/js/base.js"></script>
        <script src="libs/js/iframe.js"></script>
		<script type='text/javascript' src="libs/js/sticky_note/sticky_note.js"></script>
        {$HEADER}
	{$HEADER_MODULES}
    </head>
    <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" {$BODYPARAMS}>
        {$MENU} <!-- Viene del tpl menu.tlp-->
                <td align="left" valign="top">
                    {if !empty($mb_message)}
                        <!-- Message board -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="message_board">
                            <tr>
                                <td valign="middle" class="mb_title">&nbsp;{$mb_title}</td>
                            </tr>
                            <tr>
                                <td valign="middle" class="mb_message">{$mb_message}</td>
                            </tr>
                        </table><br />
                        <!-- end of Message board -->
                    {/if}
                    <table border="0" cellpadding="6" width="100%">
			<tr class="moduleTitle">
			  <td class="moduleTitle" valign="middle" colspan='2'>&nbsp;&nbsp;{if $icon ne null}<img src="{$icon}" border="0" align="absmiddle">&nbsp;&nbsp;{/if}{$title}</td>
			</tr>
                        <tr>
                            <td>
                            {$CONTENT}
                            </td>
                        </tr>
                    </table><br />
                    <div align="center" class="copyright"><a href="http://www.elastix.com" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.</div>
                    <br>
                </td>
            </tr>
        </table>
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
		<div class="neo-modal-elastix-popup-box">
			<div class="neo-modal-elastix-popup-title"></div>
			<div class="neo-modal-elastix-popup-close"></div>
			<div class="neo-modal-elastix-popup-content"></div>
		</div>
		<div class="neo-modal-elastix-popup-blockmask"></div>
    </body>
</html>
