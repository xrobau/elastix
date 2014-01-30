<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head><title>{$title}</title></head>
<body>
{if $contacts}
<table border="0">
{foreach from=$contacts key=k item=contact}
<tr>
    <td>{$contact.name|escape:html} {$contact.last_name|escape:html}</td>
    <td><a href="tel://{$contact.work_phone|escape:url}">{$contact.work_phone|escape:html}</a></td>
</tr>
{/foreach}
</table>
{else}
<table border="0">
    <tr><td><a href="directory/internal">{$tag_internal}</a></td></tr>
    <tr><td><a href="directory/external">{$tag_external}</a></td></tr>
    <tr><td><a href="directorysearch/internal">{$search_internal}</a></td></tr>
    <tr><td><a href="directorysearch/internal">{$search_external}</a></td></tr>
</table>
{/if}
</body>
</html>
