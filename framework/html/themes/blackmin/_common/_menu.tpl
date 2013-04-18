<div id="elx-blackmin-menu">
<hr />
<ul>
{foreach from=$arrMainMenu key=idMenu item=menu}
<li {if $idMenu eq $idMainMenuSelected}class="selected"{/if} ><a href="index.php?menu={$idMenu}">{$menu.description}</a>
{if $idMenu eq $idMainMenuSelected}
    <ul>
    {foreach from=$arrSubMenu key=idSubMenu item=subMenu}
    <li {if $idSubMenu eq $idSubMenuSelected}class="selected"{/if} ><a href="index.php?menu={$idSubMenu}">{$subMenu.description}</a>
    {if $idSubMenu eq $idSubMenuSelected}    
        {if !empty($idSubMenu2Selected)}
            <ul>
	        {foreach from=$arrSubMenu2 key=idSubMenu2 item=subMenu2}
                <li {if $idSubMenu2 eq $idSubMenu2Selected}class="selected"{/if}><a href="index.php?menu={$idSubMenu2}">{$subMenu2.description}</a>
				{if $idSubMenu2 eq $idSubMenu2Selected}
				{else}
				{/if}
                </li>
	        {/foreach}
	        </ul>
        {/if}
    {else}
    {/if}
    </li>
    {/foreach}
    </ul>
{else}
{/if}
</li>
{/foreach}
</ul>
<hr />
<ul>
<li class="selected"><a href='http://www.elastix.org' target='_blank'>Website</a></li>
<li class="selected"><a href="index.php?logout=yes">{$LOGOUT}</a></li>
</ul>
<hr />
</div>
