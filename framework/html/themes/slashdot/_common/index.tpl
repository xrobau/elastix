<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
        <title>Elastix</title>
        <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css" />
        <link rel="stylesheet" href="themes/{$THEMENAME}/help.css" />
	{$HEADER_LIBS_JQUERY}
        <script src="libs/js/base.js"></script>
        <script src="libs/js/iframe.js"></script>
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
                    <div align="center" class="copyright"><a href="http://www.elastix.org" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.</div>
                    <br>
                </td>
            </tr>
        </table>
    </body>
</html>
