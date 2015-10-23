<div id="fullMenu">
        <table cellspacing="0" cellpadding="0" width="100%" border="0" class="fondomenu_headertop">
            <tr>
                <td width="20%">
                    <table cellspacing="0" cellpadding="0" border="0" height="65px">
                        <tr>
                            <td class="menulogo"  valign="top">
                                <a href='http://www.elastix.com' target='_blank'>
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
			    <span><a href="#" class="register_link">{$Registered}</a></span>&nbsp;&nbsp;&nbsp;&nbsp;
                            <span><a class="logout" id="viewDetailsRPMs">{$VersionDetails}</a></span>&nbsp;
                            <span class="menuguion">*</span>&nbsp;
                            <span><a class="logout" href="#" id="dialogaboutelastix">{$ABOUT_ELASTIX2}</a></span>&nbsp;
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

{literal}
<script type='text/javascript'>
//<![CDATA[
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

<input type="hidden" id="lblRegisterCm"   value="{$lblRegisterCm}" />
<input type="hidden" id="lblRegisteredCm" value="{$lblRegisteredCm}" />
<input type="hidden" id="amount_char_label" value="{$AMOUNT_CHARACTERS}" />
<input type="hidden" id="save_note_label" value="{$MSG_SAVE_NOTE}" />
<input type="hidden" id="get_note_label" value="{$MSG_GET_NOTE}" />
<input type="hidden" id="elastix_theme_name" value="{$THEMENAME}" />
<input type="hidden" id="lbl_no_description" value="{$LBL_NO_STICKY}" />
