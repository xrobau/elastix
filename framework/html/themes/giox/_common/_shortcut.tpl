<div id='neo-bookmarkID' class='neo-historybox-tabon' {if !$SHORTCUT_BOOKMARKS}style='display: none'{/if}>{$SHORTCUT_BOOKMARKS_LABEL}</div>
{foreach from=$SHORTCUT_BOOKMARKS item=shortcut name=shortcut}
<div {if $smarty.foreach.shortcut.last}class='neo-historybox-tabmid'{/if} id='menu{$shortcut.id_menu}' >
    <a href='index.php?menu={$shortcut.namemenu}'>{$shortcut.name}</a>
    <div class='neo-bookmarks-equis'></div>
</div>
{/foreach}
<div id='neo-historyID' class='neo-historybox-tabon'>{$SHORTCUT_HISTORY_LABEL}</div>
{foreach from=$SHORTCUT_HISTORY item=shortcut}
<div><a href='index.php?menu={$shortcut.namemenu}'>{$shortcut.name}</a></div>
{/foreach}