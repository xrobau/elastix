<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
  <title>Elastix</title>
  <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css">
  <link rel="stylesheet" href="themes/{$THEMENAME}/help.css">
  <script src="libs/js/base.js"></script>
  <script src="libs/js/iframe.js"></script>
  {$HEADER}
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" {$BODYPARAMS}>
{$MENU}
<td align="left" valign="top">{if !empty($mb_message)}
<!-- Message board -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="message_board">
  <tr>
    <td valign="middle" class="mb_title">&nbsp;{$mb_title}</td>
  </tr>
  <tr>
    <td valign="middle" class="mb_message">{$mb_message}</td>
  </tr>
</table><br>
<!-- end of Message board -->
{/if}
<table border="0" cellpadding="6" width="100%">
  <tr>
    <td>
    {$CONTENT}
    </td>
  </tr>
</table>
<br>
<div align="center" class="copyright"><a href="http://www.elastix.org" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - 2008.</div>
<br>
</td></tr></table>
</body>
</html>
