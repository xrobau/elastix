<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head><title>{$title}</title></head>
<body>
<form method="post" action="{$baseurl}/directory/{$directorytype}">
    <input type="text" name="search" />
    <input type="submit" name="{$tag_submit|escape:html}" />
</form>
</body>
</html>
