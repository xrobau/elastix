{* SE GENERA EL AUTO POPUP SI ESTA ACTIVADO *} 
{if $AUTO_POPUP eq '1'}
   {literal}
   	<script type='text/javascript'>
 	$('.togglestickynote').ready(function(e) {
            $("#neo-sticky-note-auto-popup").attr('checked', true);
	    note();
	});
	</script>
   {/literal}
{/if}
<div id="fullMenu">
        <table cellspacing="0" cellpadding="0" width="100%" border="0" class="fondomenu_headertop">
            <tr>
                <td width="20%">
                    <table cellspacing="0" cellpadding="0" border="0" height="65px">
                        <tr>
                            <td class="menulogo"  valign="top">
                                <a href='http://www.elastix.org' target='_blank'>
                                    <img alt="" src="themes/{$THEMENAME}/images/logo_elastix.gif" border='0' />
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="50%" valign="top">
                    <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" height="74">
                        <tr>
                            {foreach from=$arrMainMenu key=idMenu item=menu name=menuMain}
                                {if $idMenu eq $idMainMenuSelected}
									<td width="4px">&nbsp;</td>
									<td align="center" id="menu_selected">
                                         <table cellspacing='0' cellpadding='0' border='0' style='position:relative;top:18px'>
                                            <tr>
                                                <td valign='top'><img alt="" border='0' align='absmiddle' src="themes/{$THEMENAME}/images/fondo_boton_on_left.gif"/></td>
                                                <td class='menutabletabon2' nowrap='nowrap'>
                                                    <a class='menutable2' href="index.php?menu={$idMenu}">{$menu.Name}</a>
                                                </td>
                                                <td valign='top'>
                                                    <img alt="" border='0' align='absmiddle' src="themes/{$THEMENAME}/images/fondo_boton_on_right.gif"/></td>
                                            </tr>
                                        </table>
										<!--<table cellspacing="0" cellpadding="0" border="0" width="69px" id="table_on">
											<tr>
												<td class="menutabletabon">
													<img alt="" src="themes/{$THEMENAME}/images/{$idMenu}_icon.gif" border="0" alt="" />
													<a class="menutableon" href="index.php?menu={$idMenu}">{$menu.Name}</a>
                                                    <input type="hidden" name="desc_menu" id="desc_menu" value="{$THEMENAME},{$idMenu},{$menu.Name}" />
												</td>
											</tr>
										</table>-->
									</td>
                                {else}
                                    <td width="4px">&nbsp;</td>
									<td align="center">
										<table cellspacing="0" cellpadding="0" border="0" style="position:relative;top:18px">
											<tr>
												<td valign="top">
                                                    <div class="div_bar_left">&nbsp;</div>
                                                </td>
												<td class="menutabletaboff" nowrap="nowrap">
													<a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a>
												</td>
												<td valign="top">
                                                    <div class="div_bar_right">&nbsp;</div>
                                                </td>
											</tr>
										</table> 
									</td>
                                 {/if}
                            {/foreach}
                                    <td width="69px">&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td width="30%" nowrap="nowrap">
                    <div id="menu_float" class="background">
                        <div id="logout_in">
			    <span><a class="register_link" style="color: {$ColorRegister}; cursor: pointer; font-weight: bold; font-size: 13px;" onclick="showPopupCloudLogin('{$Register}',540,460)">{$Registered}</a></span>&nbsp;&nbsp;&nbsp;&nbsp;
                            <span><a class="logout" id="viewDetailsRPMs">{$VersionDetails}</a></span>&nbsp;
                            <span class="menuguion">*</span>&nbsp;
                            <span><a class="logout" href="javascript:mostrar();">{$ABOUT_ELASTIX2}</a></span>&nbsp;
                            <span class="menuguion">*</span>&nbsp;
                            <span>{if !empty($idSubMenu2Selected)}
					            <a class="logout" href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
					          {else}
					            <a class="logout" href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
					          {/if}{$HELP}</a></span>&nbsp;
                            <span class="menuguion">*</span>&nbsp;
                            <span><a class="logout" href="?logout=yes">{$LOGOUT} (<font color='#c0d0e0'>{$USER_LOGIN}</font>)</a></span>&nbsp;
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="menudescription" colspan="3">
                  <table cellspacing="0" cellpadding="0" width="100%">
                    <tr>
                        <td><!--{$arrMenuTotalChildren}-->
                            <table cellspacing="1" cellpadding="5" border="0">
                                <tr>
                                {foreach from=$arrSubMenu key=idSubMenu item=subMenu}
                                {if $idSubMenu eq $idSubMenuSelected}
                                <td title="" class="botonon">
                                        <a href="?menu={$idSubMenu}" class="submenu_on">{$subMenu.Name}{if $subMenu.HasChild }<img alt='' src='images/miniArrowDown.png' align='absmiddle' style='border:0;'/>{/if}</a>
                                </td>
                                {else}
                                <td title="" class="botonoff"><a href="index.php?menu={$idSubMenu}">{$subMenu.Name}{if $subMenu.HasChild }<img alt='' src='images/miniArrowDown.png' align='absmiddle' style='border:0;'/>{/if}</a></td>
                                {/if}
                                {/foreach}
                                </tr>
                            </table>
                        </td>
                        <td align="right" valign="middle">
							<img src="themes/{$THEMENAME}/images/tab_notes_bottom.png" alt="tabnotes" id="togglestickynote1" class="togglestickynote" style="cursor: pointer;" />&nbsp;
							{if !empty($idSubMenu2Selected)}
			                    <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
			                    <input type="hidden" id="elastix_framework_module_id" value="{$idSubMenu2Selected}" />
			                {else}
			                    <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
			                    <input type="hidden" id="elastix_framework_module_id" value="{$idSubMenuSelected}" />
			                {/if}<img alt=""
                            src="themes/{$THEMENAME}/images/help_bottom.gif" border="0" /></a>&nbsp;&nbsp;<a href="javascript:changeMenu()"><img alt=""
                            src="themes/{$THEMENAME}/images/arrow_top.gif" border="0" /></a>&nbsp;&nbsp;</td>
                    </tr>
                  </table>
                </td>
            </tr>
        </table>
</div>
<div id="miniMenu" style="display: none;">
  <table cellspacing="0" cellpadding="0" width="100%" class="menumini">
    <tr>
      <td><img alt="" src="images/logo_elastix_new_mini.png" border="0" /></td>
      <td align="right" class="letra_gris" valign="middle">{$nameMainMenuSelected} &rarr; {$nameSubMenuSelected} {if !empty($idSubMenu2Selected)} &rarr; {$nameSubMenu2Selected} {/if}
		  &nbsp;&nbsp;<img src="themes/{$THEMENAME}/images/tab_notes_bottom.png" alt="tabnotes" id="togglestickynote2" class="togglestickynote" style="cursor: pointer;" border="0"
          align="absmiddle" />
          &nbsp;&nbsp;{if !empty($idSubMenu2Selected)}
            <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
          {else}
            <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
          {/if}<img alt="" src="themes/{$THEMENAME}/images/help_bottom.gif" border="0" 
          align="absmiddle" /></a>
          &nbsp;&nbsp;<a href="javascript:changeMenu()"><img alt="" src="themes/{$THEMENAME}/images/arrow_bottom.gif" border="0" align="absmiddle" /></a>&nbsp;&nbsp;
      </td>
    </tr>
  </table>
</div>


<div id='acerca_de'>
    <table border='0' cellspacing="0" cellpadding="2" width='100%'>
        <tr class="moduleTitle">
            <td class="moduleTitle" align="center" colspan='2'>
                {$ABOUT_ELASTIX2}
            </td>
        </tr>
        <tr class="tabForm" >
            <td class="tabForm"  height='120' colspan='2' align='center'>
                {$ABOUT_ELASTIX_CONTENT}<br />
                <a href='http://www.elastix.org' target='_blank'>www.elastix.org</a>
            </td>
        </tr>
        <tr>
            <td class="moduleTitle" align="center" colspan='2'>
                <input type='button' value='{$ABOUT_CLOSED}' onclick="javascript:cerrar();" />
            </td>
        </tr>
    </table> 
</div>


<div id="fade_overlay" class="black_overlay"></div>

<table width="100%" cellpadding="0" cellspacing="0" height="100%">
  <tr>
    {if !empty($idSubMenu2Selected)}
    <td width="200px" align="left" valign="top" bgcolor="#f6f6f6" id="tdMenuIzq">
      <table cellspacing="0" cellpadding="0" width="100%" class="" align="left">
          <tr><td title="" class="menuiz_start">&nbsp;</td></tr>
        {foreach from=$arrSubMenu2 key=idSubMenu2 item=subMenu2}
          {if $idSubMenu2 eq $idSubMenu2Selected}
          <tr><td title="" class="menuiz_botonon"><a href="index.php?menu={$idSubMenu2}">{$subMenu2.Name}</td></tr>
          {else}
          <tr><td title="" class="menuiz_botonoff"><a href="index.php?menu={$idSubMenu2}">{$subMenu2.Name}</a></td></tr>
          {/if}
        {/foreach}
      </table>
    </td>
    {/if}
<!-- Va al tpl index.tlp-->

<div id="PopupElastix" style="position: absolute; top: 0px; left: 0px;">
</div>

{literal}
<style type='text/css'>
#acerca_de{
    position:fixed;
    background-color:#FFFFFF; 
    width:420px;
    height:190px;
    border:1px solid #800000;
    z-index: 10000;
}
</style>
<script type='text/javascript'>
//<![CDATA[
cerrar();
function cerrar()
{
    var div_contenedor = document.getElementById('acerca_de');
    div_contenedor.style.display = 'none';
}

function mostrar()
{
    var ancho = 440;
    var div_contenedor = document.getElementById('acerca_de');
    var eje_x=(screen.width - ancho) / 2;
    div_contenedor.setAttribute("style","left:"+ eje_x + "px; top:123px");
    div_contenedor.style.display = 'block';
}

function mostrar_Menu(element)
{
    var subMenu;

    var idMenu = document.getElementById("idMenu");
    if(idMenu.value!="")
    {
        subMenu = document.getElementById(idMenu.value);
        subMenu.setAttribute("class", "vertical_menu_oculto");
    }
    if(element != idMenu.value)
    {
        subMenu = document.getElementById(element);
        subMenu.setAttribute("class", "vertical_menu_visible");
        idMenu.setAttribute("value", element);
    }
    else idMenu.setAttribute("value", "");
}


// function createMenuSelectedSplash()
// {
//     var desc_menu = document.getElementById("desc_menu").value;
//     var arrDesc = desc_menu.split(",");
// 
//     var html  = "<table cellspacing='0' cellpadding='0' border='0' style='position:relative;top:17px'>";
//         html += "   <tr>";
//         html += "       <td valign='top'><img alt='' border='0' align='absmiddle' src='themes/" + arrDesc[0] + "/images/fondo_boton_on_left.gif'/></td>";
//         html += "       <td class='menutabletabon2' nowrap='nowrap'>";
//         html += "           <a class='menutable2' href='javascript:openMenu(\"" + arrDesc[1] + "\");'>" + arrDesc[2] + "</a>";
//         html += "       </td>";
//         html += "       <td valign='top'><img alt='' border='0' align='absmiddle' src='themes/" + arrDesc[0] + "/images/fondo_boton_on_right.gif'/></td>";
//         html += "   </tr>";
//         html += "</table>";
// 
//     var menu_selected = document.getElementById("menu_selected");
//     menu_selected.innerHTML = html;
// }
// setTimeout("createMenuSelectedSplash()",1400);

// var cnt = 0;
// function load()
// {
//     if(cnt > 1)
//         createMenuSelectedSplash();
//     else {
//         setTimeout("load()",500);
//         cnt++;
//     }
// }
// load();

//]]>
</script>

<script type="text/javascript">
//<![CDATA[
    $(".menutabletaboff").mouseover(function(){ 
        var source_img = $('.menulogo').find('a:first').find('img:first').attr("src");
        var themeName = source_img.split("/",2);
        $(this).css("background-image","url(themes/"+themeName[1]+"/images/fondo_boton_center2.gif)");
        $(this).css("height","47px");
        $(this).find('a:first').css("bottom","6px");
        $(this).parent().find('div:first').css("background-image","url(themes/"+themeName[1]+"/images/fondo_boton_left2.gif)");
        $(this).parent().find('div:last').css("background-image","url(themes/"+themeName[1]+"/images/fondo_boton_right2.gif)");
        $(this).parent().find('div:first').css("height","38px");
        $(this).parent().find('div:last').css("height","38px");
    });

    $(".menutabletaboff").mouseout(function(){
        var source_img = $('.menulogo').find('a:first').find('img:first').attr("src");
        var themeName = source_img.split("/",2);
        $(this).css("background-image","url(themes/"+themeName[1]+"/images/fondo_boton_center.gif)");
        $(this).css("height","37px");
        $(this).find('a:first').css("bottom","0px");
        $(this).parent().find('div:first').css("background-image","url(themes/"+themeName[1]+"/images/fondo_boton_left.gif)");
        $(this).parent().find('div:last').css("background-image","url(themes/"+themeName[1]+"/images/fondo_boton_right.gif)");
        $(this).parent().find('div:first').css("height","35px");
        $(this).parent().find('div:last').css("height","35px");
    });

//]]>
</script>
{/literal}

<input type="hidden" id="lblTextMode" value="{$textMode}" />
<input type="hidden" id="lblHtmlMode" value="{$htmlMode}" />
<input type="hidden" id="lblRegisterCm"   value="{$lblRegisterCm}" />
<input type="hidden" id="lblRegisteredCm" value="{$lblRegisteredCm}" />
<input type="hidden" id="amount_char_label" value="{$AMOUNT_CHARACTERS}" />
<input type="hidden" id="save_note_label" value="{$MSG_SAVE_NOTE}" />
<input type="hidden" id="get_note_label" value="{$MSG_GET_NOTE}" />
<input type="hidden" id="elastix_theme_name" value="{$THEMENAME}" />
<input type="hidden" id="lbl_no_description" value="{$LBL_NO_STICKY}" />
