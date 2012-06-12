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
<div id="elx-blackmin-content-menu">
<img align="absmiddle" src="themes/{$THEMENAME}/images/elastix_logo_mini.png" height="36" alt="elastix" longdesc="http://www.elastix.org" />&nbsp;{if $icon ne null}<img src="{$icon}" border="0" align="absmiddle" />&nbsp;&nbsp;{/if}{$title}
<span id="elx-blackmin-quicklink">
&nbsp;<a class="register_link" style="color: {$ColorRegister}; cursor: pointer; font-weight: bold; font-size: 13px;" onclick="showPopupElastix('registrar','{$Register}',538,370)">{$Registered}</a>
&nbsp;<a id="viewDetailsRPMs">{$VersionDetails}</a>
&nbsp;<!--<a id="about_elastix2">{$ABOUT_ELASTIX}</a>--> <a href="javascript:mostrar();">{$ABOUT_ELASTIX}</a>
&nbsp;<a href="index.php?logout=yes">{$LOGOUT}</a>
</span>
</div>
<div id="elx-blackmin-wrap">
{$MENU}
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
{* Lista de RPMS instalados *}
<!--<div id="boxRPM" style="display:none;">
    <div class="popup">
        <table>
            <tr>
                <td class="tl"/>
                <td class="b"/>
                <td class="tr"/>
            </tr>
            <tr>
                <td class="b"/>
                <td class="body">
                    <div class="content_box">
                        <div id="table_boxRPM">
                           <table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
                                <tr class="moduleTitle">
                                    <td class="moduleTitle">
                                        <div>
                                            <div style="float: left;">&nbsp;&nbsp;{$VersionPackage}&nbsp;</div>
                                            <div align="right" style="padding-top: 5px;"><a id="changeMode" style="visibility: hidden;">({$textMode})</a></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="moduleTitle" id="loadingRPM" align="center" style="display: block;">
                                        <img class="loadingRPMimg" alt="loading" src="images/loading.gif"  />
                                    </td>
                                </tr>
                                <tr>
                                    <td id="tdRpm" style="display: block;">
                                        <table  id="tableRMP" width="100%" border="1" cellspacing="0" cellpadding="4" align="center">

                                        </table> 
                                    </td>
                                </tr>
                                <tr>
                                    <td id="tdTa" style="display: none;">
                                        <textarea  id="txtMode" value="" rows="60" cols="60"></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="footer">
                        <a class="close_box_RPM">
                        <img src="images/closelabel.gif" title="close" class="close_image_box" />
                        </a>
                    </div>
                </td>
                <td class="b"/>
            </tr>
            <tr>
                <td class="bl"/>
                <td class="b"/>
                <td class="br"/>
            </tr>
        </table>
    </div>
</div>-->
<div id="fade_overlay" class="black_overlay"></div>

</body>
{literal}
<script language="javascript" type="text/javascript">
$(document).ready(function() {
    $('#elx-blackmin-content-menu>img').click(elx_blackmin_menu_mostrar);
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

function elx_blackmin_menu_ocultar(event)
{
    $('#elx-blackmin-menu').unbind('click');
    $('html').unbind('click', elx_blackmin_menu_ocultar);
    $('#elx-blackmin-menu').hide();
}

function elx_blackmin_menu_mostrar(event)
{
    if ($('#elx-blackmin-menu').is(':visible')) {
        elx_blackmin_menu_ocultar();
    } else {
        event.stopPropagation();

        // Operaciones para cerrar menú cuando se hace clic fuera
        $('#elx-blackmin-menu').click(function(event) {
            event.stopPropagation();
        });
        $('html').click(elx_blackmin_menu_ocultar);

        $('#elx-blackmin-menu').show();
        $('#elx-blackmin-menu').position({
            of: $(this),
            my: "left bottom",
            at: "left top"
        });
    }
}
{/literal}
</script>
<input type="hidden" id="lblTextMode" value="{$textMode}" />
<input type="hidden" id="lblHtmlMode" value="{$htmlMode}" />
</html>
