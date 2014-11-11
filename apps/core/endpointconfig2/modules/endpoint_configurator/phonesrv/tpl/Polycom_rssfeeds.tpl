<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head><title>{$title}</title></head>
<body>
{if $rssfeeds}
<table border="0">
{foreach from=$rssfeeds key=k item=rssrow name=rss}
<tr><td><a href="rssfeeds/{$k|escape:url}">{$rssrow[0]|escape:html}</a></td></tr>
{/foreach}
</table>
{else}
<b>{$rsstitle|escape:html} - {$rsslink|escape:html}<br/></b>
<br/>
<table border="0">
{foreach from=$rssitems item=rssitem}
<tr>
    <td>{$rssitem.date_timestamp|date_format:"%Y.%m.%d"}</td>
    <td>{$rssitem.title|escape:html}</td>
</tr>
<tr>
    <td colspan="2">{$rssitem.summary|strip_tags|escape:html}</td>
</tr>
{/foreach}
</table>
{/if}
</body>
</html>
