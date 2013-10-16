<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
        <title>Elastix</title>
        <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/styles.css" />
	<!--<link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/old.theme.elastixwave.styles.css" />--> 
        <!--[if lte IE 8]><link rel="stylesheet" media="screen" type="text/css" href="themes/{$THEMENAME}/ie.css" /><![endif]-->
	{$HEADER_LIBS_JQUERY}
        <script type='text/javascript' src="{$WEBCOMMON}js/base.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/jsvalidator.js"></script>
        <script type='text/javascript' src="{$WEBCOMMON}js/uf.js"></script>
        {$HEADER}
	{$HEADER_MODULES}
    </head>
    <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="mainBody" {$BODYPARAMS}>
        {$MENU} <!-- Viene del tpl menu.tlp-->
	{$CONTENT}
		<!-- <div align="center" id="neo-footerbox"> mostrando el footer 
			<a href="http://www.elastix.org" style="color: #444; text-decoration: none;" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank' style="color: #444; text-decoration: none;" >GPL</a> by <a href="http://www.palosanto.com" target='_blank' style="color: #444; text-decoration: none;">PaloSanto Solutions</a>. 2006 - {$currentyear}.
		</div> -->
    </body>
</html>
