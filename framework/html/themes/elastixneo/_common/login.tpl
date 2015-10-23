<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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
	<form method="POST">
	  <div id="neo-login-box">
		<div id="neo-login-logo"><img src="themes/{$THEMENAME}/images/elastix_logo_mini.png" width="200" height="62" alt="elastix logo" /></div>
		<div>
		  <div>{$USERNAME}:</div>
		  <div class="inputbox"><input type="text" id="input_user" name="input_user" /></div>
		</div>
		<div>
		  <div>{$PASSWORD}:</div>
		  <div class="inputbox"><input type="password" name="input_pass" /></div>
		</div>
		<div>
		  <div></div>
		  <div class="inputbox"><input type="submit" name="submit_login" value="{$SUBMIT}" /></div>
		</div>
		<div class="neo-footernote"><a href="http://www.elastix.com" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" style="text-decoration: none;" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.</div>
		<br/>
	  </div>
	</form>
  </body>
</html>
