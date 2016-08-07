<div id="elxneo-topnav-toolbar">
<input type="hidden" id="userMenuColor" value="{$MENU_COLOR}" />

<div id="logo"><img src="images/logo_elastix_new3.gif" alt="elastix" longdesc="http://www.elastix.com" /></div>
<div id="mmenubox"> <!-- mostrando contenido del menu principal -->
{foreach from=$arrMainMenu key=idMenu item=menu name=menuMain}
    <div {if $idMenu eq $idMainMenuSelected}class="selected"{/if}>
        <a href="index.php?menu={$idMenu}">{$menu.Name}</a>
        {if $idMenu ne $idMainMenuSelected}
        <div class="elxneo-menu">
            <img src="themes/{$THEMENAME}/images/corner.gif" />
            <div>
                {foreach from=$menu.children item=menuchild }
                <div><a href="index.php?menu={$menuchild.id}">{$menuchild.Name}{if $menuchild.HasChild}...{/if}</a></div>
                {/foreach}
            </div>
        </div>
        {/if}
    </div>
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
<div id="smenubox-toggleminimenu">
    <img src="themes/{$THEMENAME}/images/tab_notes.png" alt="tabnotes" id="togglestickynote1" class="togglestickynote" style="cursor: pointer;" />&nbsp;
{if !empty($idSubMenu2Selected)}
    <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
{else}
    <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
{/if}<img alt="" src="themes/{$THEMENAME}/images/help_top.gif" border="0" /></a>&nbsp;
    <a class="elxneo-changemenu" href="#"><img alt="" src="themes/{$THEMENAME}/images/arrow_top.gif" border="0" /></a>
</div>

<div id="cmenubox">
    <div><a class="logout" href="?logout=yes">{$LOGOUT} (<font style='color:#FFFFFF;font-style:italic'>{$USER_LOGIN}</font>)</a></div>
    <div>
{if !empty($idSubMenu2Selected)}
        <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&amp;name_nodo={$nameSubMenu2Selected}','1000','460')">
{else}
        <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&amp;name_nodo={$nameSubMenuSelected}','1000','460')">
{/if}
        {$HELP}</a>
    </div>
    <div><a href="#" id="dialogaboutelastix">{$ABOUT_ELASTIX2}</a></div>
    <div><a href="#" id="viewDetailsRPMs">{$VersionDetails}</a></div>
    <div><a href="#" class="register_link">{$Registered}</a></div>
{if $ELASTIX_PANELS}
    <div><a href="#" id="togglesidebar">{$LBL_ELASTIX_PANELS_SIDEBAR|escape:html}</a></div>
{/if}
</div>{* #cmenubox *}
</div>{* #elxneo-topnav-toolbar *}
<div id="elxneo-topnav-minitoolbar" style="display: none;">
    <div id="logo"><img alt="" src="images/logo_elastix_new_mini.png" border="0" /></div>
    <div class="letra_gris">
{$nameMainMenuSelected} &rarr; {$nameSubMenuSelected} {if !empty($idSubMenu2Selected)} &rarr; {$nameSubMenu2Selected} {/if}
          &nbsp;&nbsp;<img src="themes/{$THEMENAME}/images/tab_notes_bottom.png" alt="tabnotes" id="togglestickynote2" class="togglestickynote" style="cursor: pointer;" border="0"
          align="absmiddle" />
          &nbsp;&nbsp;{if !empty($idSubMenu2Selected)}
            <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
          {else}
            <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
          {/if}<img alt="" src="themes/{$THEMENAME}/images/help_bottom.gif" border="0"
          align="absmiddle" /></a>
          &nbsp;&nbsp;<a class="elxneo-changemenu" href="#"><img alt="" src="themes/{$THEMENAME}/images/arrow_bottom.gif" border="0" align="absmiddle" /></a>&nbsp;&nbsp;
    </div>
</div>{* #elxneo-topnav-minitoolbar *}

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

<div id="elxneo-leftcolumn" {if empty($idSubMenu2Selected) or $viewMenuTab eq 'true'}class="hidden-menutab"{/if}>
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
{*    <div class="name-left"></div>
    <span class="name">
*}
&nbsp;&nbsp;{if $icon ne null}<img src="{$icon}" align="absmiddle" />&nbsp;&nbsp;{/if}{$title}
{*
    </span>
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
*}
</div>
{* FINAL: decoraciones de módulo, controles de notas, minimizar menu, bookmark, ayuda *}
<input type="hidden" id="elastix_framework_module_id" value="{if !empty($idSubMenu2Selected)}{$idSubMenu2Selected}{else}{$idSubMenuSelected}{/if}" />
