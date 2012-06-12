<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
        <title>Elastix</title>
        <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css" />
        <link rel="stylesheet" href="themes/{$THEMENAME}/help.css" />
		<!--<link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/old.theme.elastixwave.styles.css" />-->
		<link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/header.css" />
		<link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/content.css" />
		<link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/applet.css" />
        <!--[if lte IE 8]><link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/ie.css" /><![endif]-->
	{$HEADER_LIBS_JQUERY}
        <script type='text/javascript' src="libs/js/base.js"></script>
        <script type='text/javascript' src="libs/js/iframe.js"></script>
        {$HEADER}
	{$HEADER_MODULES}
    </head>
    <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="mainBody" {$BODYPARAMS}>
        {$MENU} <!-- Viene del tpl menu.tlp-->
		{if !empty($mb_message)}
		<br />
	  	<div class="div_msg_errors" id="message_error">
                    <div style="float:left;">
                        <b style="color:red;">&nbsp;&nbsp;{$mb_title} </b>
                    </div>
                    <div style="text-align:right; padding:5px">
                        <input type="button" onclick="hide_message_error();" value="{$md_message_title}"/>
                    </div>
		    <div style="position:relative; top:-12px; padding: 0px 5px">
			{$mb_message}
		    </div>
		</div>
		{/if}
				{$CONTENT}
			</div>
		    </div>
		    <div id="neo-lengueta-minimized" class="neo-display-none"></div>
		</div>
		<div align="center" id="neo-footerbox"> <!-- mostrando el footer -->
			<a href="http://www.elastix.org" style="color: #444; text-decoration: none;" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank' style="color: #444; text-decoration: none;" >GPL</a> by <a href="http://www.palosanto.com" target='_blank' style="color: #444; text-decoration: none;">PaloSanto Solutions</a>. 2006 - {$currentyear}.
		</div>
    </body>
</html>
