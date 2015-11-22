<html>
  <head>
	<title>Elastix - {$PAGE_NAME}</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="themes/{$THEMENAME}/styles.css">
    {$HEADER_LIBS_JQUERY}
    {literal}
    <script type="text/javascript">
        $(document).ready(function() {
             $("#neo-login-box").draggable();
             $("#input_user").focus();
        });
    </script>
    {/literal}
  </head>
  <body>
  <table cellspacing="0" cellpadding="0" width="100%" border="0" class="menulogo2" height="74">
    <tr>
       <td class="menulogo" valign="top">
           <a target="_blank" href="http://www.elastix.com">
               <img border="0" src="themes/{$THEMENAME}/images/logo_elastix.gif"/>
           </a>
       </td>
    </tr>
  </table>
<form method="POST">
<p>&nbsp;</p>
<p>&nbsp;</p>
<table width="400" border="0" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td width="498" class="menudescription2">
      <table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
        <tr>
          <td>
              <div align="left"><font color="#ffffff">&nbsp;&raquo;&nbsp;{$WELCOME}</font></div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td width="498" bgcolor="#ffffff">
      <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tabForm">
        <tr>
          <td colspan="2">
            <div align="center">{$ENTER_USER_PASSWORD}<br><br></div>
          </td>
        </tr>
        <tr>
          <td>
              <div align="right">{$USERNAME}:</div>
          </td>
          <td>
            <input type="text" id="input_user" name="input_user" style="color:#000000; FONT-FAMILY: verdana, arial, helvetica, sans-serif; FONT-SIZE: 8pt;
             font-weight: none; text-decoration: none; background: #fbfeff; border: 1 solid #000000;">
          </td>
        </tr>
        <tr>
          <td>
              <div align="right">{$PASSWORD}:</div>
          </td>
          <td>
            <input type="password" name="input_pass" style="color:#000000; FONT-FAMILY: verdana, arial, helvetica, sans-serif; FONT-SIZE: 8pt;
             font-weight: none; text-decoration: none; background: #fbfeff; border: 1 solid #000000;">
          </td>
        </tr>
        <tr>
          <td colspan="2" align="center">
            <input type="submit" name="submit_login" value="{$SUBMIT}" class="button">
          </td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;&nbsp;</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<br>
<div align="center" class="copyright"><a href="http://www.elastix.com" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.</div>
<br>
<script type="text/javascript">
    document.getElementById("input_user").focus();
</script>
  </body>
</html>
