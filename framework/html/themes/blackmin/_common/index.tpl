<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
  <title>Elastix</title>
  <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css" />
  <link rel="stylesheet" href="themes/{$THEMENAME}/help.css" />
  <link rel="stylesheet" media="screen" type="text/css" href="libs/js/sticky_note/sticky_note.css" />
  {$HEADER_LIBS_JQUERY}
  <script type="text/javascript" src="libs/js/base.js"></script>
  <script type='text/javascript' src="libs/js/sticky_note/sticky_note.js"></script>
  <script type="text/javascript" src="libs/js/iframe.js"></script>
  {$HEADER}
  {$HEADER_MODULES}
</head>
<body {$BODYPARAMS}>
<div id="elx-blackmin-topnav-toolbar">
    <ul class="elx-blackmin-topnav">
        <li class="elx-blackmin-menu">
<img align="absmiddle" src="themes/{$THEMENAME}/images/elastix_logo_mini.png" height="36" alt="elastix" longdesc="http://www.elastix.com" />
{$MENU}
        </li>
        <li id="elx-blackmin-module-title">
<img height="22" src="{if $icon ne null}{$icon}{else}images/list.png{/if}" border="0" align="absmiddle" />&nbsp;{$title}
        </li>
        <!-- Botones y menús a alinear a la derecha -->
        <li class="navButton elx-blackmin-menu">
            <!-- Acciones de usuario: cambio de clave y salir -->
            <img height="20" align="absmiddle" src="themes/{$THEMENAME}/images/user.png" alt="user" />
            <div>
                <ul>
                    <li><a href="#" class="setadminpassword">{$CHANGE_PASSWORD}</a></li>
                    <li><a href="index.php?logout=yes">{$LOGOUT} (<font style='color:#FFFFFF;font-style:italic'>{$USER_LOGIN}</font>)</a></li>
                </ul>
            </div>
        </li>
        <li class="navButton">
            <!-- Enlace a módulo de addons -->
            <a href="index.php?menu=addons"><img height="20" align="absmiddle" src="themes/{$THEMENAME}/images/toolbar_addons.png" alt="elastix_addons" border="0" align="absmiddle" /></a>
        </li>
        <li class="navButton elx-blackmin-menu">
            <!-- Acciones de sistema: registro, versión y mensajes de copyright -->
            <img height="20" align="absmiddle" src="themes/{$THEMENAME}/images/information.png" alt="user" />
            <div>
                <ul>
                    <li><a href="#" class="register_link">{$Registered}</a></li>
                    <li><a href="#" id="viewDetailsRPMs">{$VersionDetails}</a></li>
                    <li><a href="http://www.elastix.com" target="_blank">Elastix Website</a></li>
                    <li><a href="#" id="dialogaboutelastix">{$ABOUT_ELASTIX2}</a></li>
                </ul>
            </div>
        </li>
        <li class="navButton elx-blackmin-menu">
            <!-- Búsqueda de paquetes  -->
            <img height="20" align="absmiddle" src="themes/{$THEMENAME}/images/searchw.png" alt="user_search" />
            <div>
                <p>{$MODULES_SEARCH}</p>
                <p><input type="search"  id="search_module_elastix" name="search_module_elastix"  value="" autofocus="autofocus" placeholder="search" /></p>
            </div>
        </li>
        <li class="navButton">
            <!-- Enlace a la ayuda del módulo -->
{if !empty($idSubMenu2Selected)}
    <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
{else}
    <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
{/if}<img height="20" src="images/icon-help.png" border="0" align="absmiddle"></a>
        </li>
        <li class="navButton">
            <img height="20" align="absmiddle" alt="tabnotes" id="togglestickynote1" class="togglestickynote" src="themes/{$THEMENAME}/images/{if $STATUS_STICKY_NOTE eq 'true'}tab_notes_on.png{else}tab_notes.png{/if}"/>
        </li>
{if $ELASTIX_PANELS}
        <li class="navButton">
            <a class="fa-stack" href="#" id="togglesidebar"><i class="fa fa-th-list fa-stack-1x fa-lg"></i></a>
        </li>
{/if}
    </ul>
</div>

<!-- Textos de diversos diálogos de funcionalidades, tomado de elastixneo -->
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


<div id="elx-blackmin-wrap">
<div id="elx-blackmin-content">
{if !empty($mb_message)}
<div class="ui-state-highlight ui-corner-all" id="message_error">
    <p>
        <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <span id="elastix-callcenter-info-message-text">{if !empty($mb_title)}{$mb_title} - {/if}{$mb_message}</span>
    </p>
</div>
{/if}
{$CONTENT}
</div>
</div>

{* Pie de página con copyright de Elastix *}
<div id="elx-blackmin-footerbox">
    <a href="http://www.elastix.com" target='_blank'>Elastix</a> is licensed under <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a> by <a href="http://www.palosanto.com" target='_blank'>PaloSanto Solutions</a>. 2006 - {$currentyear}.
</div>

{if $ELASTIX_PANELS}
{* Panel derecho con paneles de plugines *}
<div id="chat">
    <h2 class="chat-header">
        <a href="#" class="chat-close"><i class="entypo-cancel"></i></a>
        <i class="entypo-users"></i>
        <span id="panel-header-text">{$LBL_ELASTIX_PANELS_SIDEBAR|escape:html}</span>
    </h2>
    <div id="elastix-panels">
        {foreach from=$ELASTIX_PANELS key=panelname item=paneldata name=elastixpanel}
            <h3>
                {if $paneldata.iconclass}
                <i class="{$paneldata.iconclass}"></i>
                {elseif $paneldata.icon}
                <div style="display: inline-block; min-width: 15px; min-height: 15px; padding-right: 5px;">
                <img alt="" src="{$paneldata.icon}" width="15" />
                </div>
                {else}
                <i class="fa fa-file-o"></i>
                {/if}
                <span>{$paneldata.title|escape:html}</span>
            </h3>
            <div id="elastix-panel-{$panelname}">
                <div class="panel-body">{$paneldata.content}</div>
            </div>
        {/foreach}
    </div>
</div>
{/if}

{* Popup de Sticky Note *}
<div id="neo-sticky-note">
    <div id="neo-sticky-note-text"></div>
    <div id="neo-sticky-note-text-edit">
        <textarea id="neo-sticky-note-textarea"></textarea>
        <div id="neo-sticky-note-text-char-count"></div>
        <input type="button" value="{$SAVE_NOTE}" id="neo-submit-button" />
        <div id="auto-popup">AutoPopUp <input type="checkbox" id="neo-sticky-note-auto-popup" value="1"></div>
    </div>
    <div id="neo-sticky-note-text-edit-delete"></div>
</div>
{* SE GENERA EL AUTO POPUP SI ESTA ACTIVADO *}
{if $AUTO_POPUP eq '1'}{literal}
<script type='text/javascript'>
$(document).ready(function(e) {
    $("#neo-sticky-note-auto-popup").prop('checked', true);
    $('#togglestickynote1').click();
});
</script>
{/literal}{/if}

<!-- Neo Progress Bar -->
		<div class="neo-modal-elastix-popup-box">
			<div class="neo-modal-elastix-popup-title"></div>
			<div class="neo-modal-elastix-popup-close"></div>
			<div class="neo-modal-elastix-popup-content"></div>
		</div>
		<div class="neo-modal-elastix-popup-blockmask"></div>
<script language="javascript" type="text/javascript">
{literal}
$(document).ready(function() {
    $('ul.elx-blackmin-topnav > li.navButton > a#togglesidebar, div#chat > h2.chat-header > a.chat-close').click(function(e) {
        e.preventDefault();
        if ($('body').hasClass('chat-visible'))
            $('body').removeClass('chat-visible');
        else
            $('body').addClass('chat-visible');
    });

    $('div#chat > div#elastix-panels').accordion({
        heightStyle: 'content',
        icons: null
    });
});
{/literal}
</script>
</body>
</html>
