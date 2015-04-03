<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF8" />
  <title>Elastix</title>
  <link rel="stylesheet" href="themes/{$THEMENAME}/styles.css" />
  <link rel="stylesheet" href="themes/{$THEMENAME}/help.css" />
  {$HEADER_LIBS_JQUERY}
  <script type="text/javascript" src="libs/js/base.js"></script>
  <script type="text/javascript" src="libs/js/iframe.js"></script>
  {$HEADER}
  {$HEADER_MODULES}
</head>
<body {$BODYPARAMS}>
<div id="elx-blackmin-topnav-toolbar">
    <ul class="elx-blackmin-topnav">
        <li class="elx-blackmin-menu">
<img align="absmiddle" src="themes/{$THEMENAME}/images/elastix_logo_mini.png" height="36" alt="elastix" longdesc="http://www.elastix.org" />
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
                    <li><a href="#" onclick="setAdminPassword();">{$CHANGE_PASSWORD}</a></li>
                    <li><a href="index.php?logout=yes">{$LOGOUT} (<font style='color:#FFFFFF;font-style:italic'>{$USER_LOGIN}</font>)</a></li>
                </ul>
            </div>
        </li>
        <li class="navButton">
            <!-- Enlace a módulo de addons -->
            <a href="index.php?menu=addons"><img height="20" align="absmiddle" src="themes/{$THEMENAME}/images/toolbar_addons.png" alt="elastix_addons" /></a>
        </li>
        <li class="navButton elx-blackmin-menu">
            <!-- Acciones de sistema: registro, versión y mensajes de copyright -->
            <img height="20" align="absmiddle" src="themes/{$THEMENAME}/images/information.png" alt="user" />
            <div>
                <ul>
                    <li><a class="register_link" style="color: {$ColorRegister}; cursor: pointer; font-weight: bold; font-size: 13px;" onclick="showPopupCloudLogin('',540,335)">{$Registered}</a></li>
                    <li><a href="#" id="viewDetailsRPMs">{$VersionDetails}</a></li>
                    <li><a href="http://www.elastix.org" target="_blank">Elastix Website</a></li>
                    <li><a href="javascript:mostrar();">{$ABOUT_ELASTIX2}</a></li>
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
    </ul>
</div>

<!-- Textos de diversos diálogos de funcionalidades, tomado de elastixneo -->
<input type="hidden" id="lblTextMode" value="{$textMode}" />
<input type="hidden" id="lblHtmlMode" value="{$htmlMode}" />
<input type="hidden" id="lblRegisterCm"   value="{$lblRegisterCm}" />
<input type="hidden" id="lblRegisteredCm" value="{$lblRegisteredCm}" />
<input type="hidden" id="lblCurrentPassAlert" value="{$CURRENT_PASSWORD_ALERT}" />
<input type="hidden" id="lblNewRetypePassAlert"   value="{$NEW_RETYPE_PASSWORD_ALERT}" />
<input type="hidden" id="lblPassNoTMatchAlert" value="{$PASSWORDS_NOT_MATCH}" />
<input type="hidden" id="lblChangePass" value="{$CHANGE_PASSWORD}" />
<input type="hidden" id="lblCurrentPass" value="{$CURRENT_PASSWORD}" />
<input type="hidden" id="lblRetypePass" value="{$RETYPE_PASSWORD}" />
<input type="hidden" id="lblNewPass" value="{$NEW_PASSWORD}" />
<input type="hidden" id="btnChagePass" value="{$CHANGE_PASSWORD_BTN}" />
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

{* Diálogo de Acerca De *}
<div id="acerca_de" title="{$ABOUT_ELASTIX}">
    {$ABOUT_ELASTIX_CONTENT}<br />
    <a href='http://www.elastix.org' target='_blank'>www.elastix.org</a>
</div>

{* Popup genérico *}
<div id="PopupElastix" style="position: absolute; top: 0px; left: 0px;">
</div>
<!-- Neo Progress Bar -->
		<div class="neo-modal-elastix-popup-box">
			<div class="neo-modal-elastix-popup-title"></div>
			<div class="neo-modal-elastix-popup-close"></div>
			<div class="neo-modal-elastix-popup-content"></div>
		</div>
		<div class="neo-modal-elastix-popup-blockmask"></div>
<div id="fade_overlay" class="black_overlay"></div>
</body>
<script language="javascript" type="text/javascript">
{literal}
$(document).ready(function() {
    $('#about_elastix2').click(function() { $('#acerca_de').dialog('open'); });
    $('#acerca_de').dialog({
        autoOpen: false,
        width: 500,
        height: 220,
        modal: true,
        buttons: [
            {
                text: "{/literal}{$ABOUT_CLOSED}{literal}",
                click: function() { $(this).dialog('close'); }
            }
        ]
    });
});
{/literal}
</script>
<input type="hidden" id="lblTextMode" value="{$textMode}" />
<input type="hidden" id="lblHtmlMode" value="{$htmlMode}" />
</html>
