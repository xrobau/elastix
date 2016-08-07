<input type="hidden" id="userMenuColor" value="{$MENU_COLOR}" />

<div id="logo"><img src="themes/{$THEMENAME}/images/elastix_logo_mini2.png" width="200" height="59" alt="elastix" longdesc="http://www.elastix.com" /></div>
<div id="mmenubox"> <!-- mostrando contenido del menu principal -->
{foreach from=$arrMainMenu key=idMenu item=menu name=menuMain}
    <div {if $idMenu eq $idMainMenuSelected}class="selected"{/if}><a href="index.php?menu={$idMenu}">{$menu.Name}</a></div>
{/foreach}
    <div class="elxneo-menu">
        <img src="themes/{$THEMENAME}/images/arrowdown.png" width="17" height="15" alt="arrowdown" />
        <div id="elxneo-mmenu-overflow"></div>
    </div>
</div>
<div id="smenubox"> <!-- mostrando contenido del menu secundario -->
{foreach from=$arrSubMenu key=idSubMenu item=subMenu}
    <div {if $idSubMenu eq $idSubMenuSelected}class="selected"{/if}><a href="index.php?menu={$idSubMenu}">{$subMenu.Name}</a></div>
{/foreach}
</div>
<div id="smenubox-arrows">
    <img src="themes/{$THEMENAME}/images/icon_arrowleft.png" width="15" height="17" alt="arrowleft"/>
    <img src="themes/{$THEMENAME}/images/icon_arrowright.png" width="15" height="17" alt="arrowright" id="arrowright"/>
</div>

<div id="cmenubox">
    <div id="user" class="elxneo-menu">
        <img src="themes/{$THEMENAME}/images/user.png" width="19" height="21" alt="user" border="0" />
	    <div>
          <div><a href="#" class="setadminpassword">{$CHANGE_PASSWORD}</a></div>
	      <div><a class="logout" href="?logout=yes">{$LOGOUT} (<font style='color:#FFFFFF;font-style:italic'>{$USER_LOGIN}</font>)</a></div>
	    </div>
    </div>
    <div id="addons">
        <a href="index.php?menu=addons"><img src="themes/{$THEMENAME}/images/toolbar_addons.png" width="19" height="21" alt="elastix_addons" border="0" /></a>
    </div>
    <div id="info" class="elxneo-menu">
        <img src="themes/{$THEMENAME}/images/information.png" width="19" height="21" alt="user_info" border="0" />
        <div>
            <div><a href="#" class="register_link">{$Registered}</a></div>
            <div><a href="#" id="viewDetailsRPMs">{$VersionDetails}</a></div>
            <div><a href="http://www.elastix.com" target="_blank">Elastix Website</a></div>
            <div><a href="#" id="dialogaboutelastix">{$ABOUT_ELASTIX2}</a></div>
        </div>
    </div>
    <div id="search" class="elxneo-menu">
        <img src="themes/{$THEMENAME}/images/searchw.png" width="19" height="21" alt="user_search" border="0" />
        <div>
            <p>{$MODULES_SEARCH}</p>
            <p><input type="search"  id="search_module_elastix" name="search_module_elastix"  value="" autofocus="autofocus" placeholder="search" /></p>
        </div>
    </div>
{if $ELASTIX_PANELS}
    <div id="togglesidebar">
        <a class="fa-stack" href="#"><i class="fa fa-th-list fa-stack-1x fa-lg"></i></a>
    </div>
{/if}
    <div id="cpallet">
        <img src="themes/{$THEMENAME}/images/cpallet.png" width="19" height="21" alt="color" />
    </div>
</div>

</div>

<input type="hidden" id="lblRegisterCm"   value="{$lblRegisterCm}" />
<input type="hidden" id="lblRegisteredCm" value="{$lblRegisteredCm}" />
<input type="hidden" id="userMenuColor" value="{$MENU_COLOR}" />
<input type="hidden" id="lblSending_request" value="{$SEND_REQUEST}" />
<input type="hidden" id="toolTip_addBookmark" value="{$ADD_BOOKMARK}" />
<input type="hidden" id="toolTip_removeBookmark" value="{$REMOVE_BOOKMARK}" />
<input type="hidden" id="toolTip_addingBookmark" value="{$ADDING_BOOKMARK}" />
<input type="hidden" id="toolTip_removingBookmark" value="{$REMOVING_BOOKMARK}" />
<input type="hidden" id="toolTip_hideTab" value="{$HIDE_IZQTAB}" />
<input type="hidden" id="toolTip_showTab" value="{$SHOW_IZQTAB}" />
<input type="hidden" id="toolTip_hidingTab" value="{$HIDING_IZQTAB}" />
<input type="hidden" id="toolTip_showingTab" value="{$SHOWING_IZQTAB}" />
<input type="hidden" id="amount_char_label" value="{$AMOUNT_CHARACTERS}" />
<input type="hidden" id="save_note_label" value="{$MSG_SAVE_NOTE}" />
<input type="hidden" id="get_note_label" value="{$MSG_GET_NOTE}" />
<input type="hidden" id="elastix_theme_name" value="{$THEMENAME}" />
<input type="hidden" id="lbl_no_description" value="{$LBL_NO_STICKY}" />

<div id="elxneo-wrap">

<div id="elxneo-leftcolumn" {if empty($idSubMenu2Selected) or $viewMenuTab eq 'true'}style="display: none;"{/if}>
    {if !empty($idSubMenu2Selected)}
    <div id="menubox">  <!-- mostrando contenido del menu tercer nivel -->
        {foreach from=$arrSubMenu2 key=idSubMenu2 item=subMenu2}
        <div {if $idSubMenu2 eq $idSubMenu2Selected}class="selected"{/if}><a href="index.php?menu={$idSubMenu2}">{$subMenu2.Name}</a></div>
        {/foreach}
    </div>
    {/if}
    <div id="historybox">
        {$SHORTCUT}
    </div>
</div>


<div id="elxneo-maincolumn">
{* INICIO: decoraciones de módulo, controles de notas, minimizar menu, bookmark, ayuda *}
<div class="elxneo-module-title">
    <div class="name-left"></div>
    <span class="name">
          {if $icon ne null}
          <img src="{$icon}" width="22" height="22" align="absmiddle" />
          {/if}
          &nbsp;{$title}</span>
    <div class="name-right"></div>
    <div class="buttonstab-right"></div>
    <span class="buttonstab">
        <img
          {if $STATUS_STICKY_NOTE eq 'true'}
          src="themes/{$THEMENAME}/images/tab_notes_on.png"
          {else}
          src="themes/{$THEMENAME}/images/tab_notes.png"{/if}
          width="23" height="21" alt="tabnotes" id="togglestickynote1" class="togglestickynote" />
        <img
          {if empty($idSubMenu2Selected) or $viewMenuTab eq 'true'}
          src="images/expandOut.png" title="{$SHOW_IZQTAB}"
          {else}
          src="images/expand.png"  title="{$HIDE_IZQTAB}"
          {/if}
          width="24" height="24" alt="expand" id="toggleleftcolumn" border="0" />
        <img src="themes/{$THEMENAME}/images/{$IMG_BOOKMARKS}" width="24" height="24" alt="bookmark" {if $IMG_BOOKMARKS eq 'bookmark.png'}title="{$ADD_BOOKMARK}"{else}title="{$REMOVE_BOOKMARK}"{/if} id="togglebookmark" />
{if !empty($idSubMenu2Selected)}
        <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&amp;name_nodo={$nameSubMenu2Selected}','1000','460')">
{else}
        <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&amp;name_nodo={$nameSubMenuSelected}','1000','460')">
{/if}
            <img src="images/icon-help.png" width="24" height="24" alt="help" title="{$HELP}" border="0"/>
        </a>
    </span>
    <div class="buttonstab-left"></div>
</div>
{* FINAL: decoraciones de módulo, controles de notas, minimizar menu, bookmark, ayuda *}
<input type="hidden" id="elastix_framework_module_id" value="{if !empty($idSubMenu2Selected)}{$idSubMenu2Selected}{else}{$idSubMenuSelected}{/if}" />
<div id="elxneo-content">
