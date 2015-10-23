<div id="fullMenu">
  <table cellspacing=0 cellpadding=0 width="100%" border=0>
    <tr>
      <td>
        <table cellSpacing="0" cellPadding="0" width="100%" border="0">
          <tr>
            <td class="menulogo" width=380><a href='http://www.elastix.com' target='_blank'><img src="images/logo_elastix.png" width="233" height="75" border='0' /></a></td>
            {foreach from=$arrMainMenu key=idMenu item=menu}
            {if $idMenu eq $idMainMenuSelected}
            <td class="headlinkspacer"><IMG src="images/1x1.gif"></td>
            <td class="headlinkon" valign="bottom">
              <table cellSpacing="0" cellPadding="2" height="30" border="0">
                <tr><td class="menutabletabon" title="" nowrap><a
                        class="menutableon" href="index.php?menu={$idMenu}">{$menu.Name}</a></td>
                </tr>
              </table>
            </td>
            <td class="headlinkspacer"><IMG src="images/1x1.gif"></td>
            {else}
            <td class="headlink" valign="bottom">
              <table cellSpacing="0" cellPadding="2" height="29" border="0">
                <tr><td class="menutabletaboff" title="" nowrap><a
                        class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></td>
                </tr>
              </table>
            </td>
            {/if}
            {/foreach}
	    <td class="menuaftertab" align="right"><span><a href="#" class="register_link">{$Registered}</a></span>&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td class="menuaftertab" width="40%" align="right">&nbsp;<a class="logout" id="viewDetailsRPMs">{$VersionDetails}</a></td>
            <td class="menuaftertab" width="40%" align="right">&nbsp;<a href="#" id="dialogaboutelastix">{$ABOUT_ELASTIX}</a></td>
            <td class="menuaftertab" width="20%" align="right">&nbsp;<a href="index.php?logout=yes">{$LOGOUT}</a></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="menudescription">
        <table cellspacing="0" cellpadding="2" width="100%">
          <tr>
            <td>
              <table cellspacing="2" cellpadding="4" border="0">
                <tr>
                  {foreach from=$arrSubMenu key=idSubMenu item=subMenu}
                  {if $idSubMenu eq $idSubMenuSelected}
                  <td title="" class="botonon"><a href="index.php?menu={$idSubMenu}" class="submenu_on">{$subMenu.Name}</td>
                  {else}
                  <td title="" class="botonoff"><a href="index.php?menu={$idSubMenu}">{$subMenu.Name}</a></td>
                  {/if}
                  {/foreach}
                </tr>
              </table>
            </td>
            <td align="right" valign="middle">
				<img src="themes/{$THEMENAME}/images/tab_notes.png" alt="tabnotes" id="togglestickynote1" class="togglestickynote" style="cursor: pointer;" />&nbsp;
				{if !empty($idSubMenu2Selected)}
                    <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
                    <input type="hidden" id="elastix_framework_module_id" value="{$idSubMenu2Selected}" />
                {else}
                    <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
                    <input type="hidden" id="elastix_framework_module_id" value="{$idSubMenuSelected}" />
                {/if}<img
                src="images/help_top.gif" border="0"></a>&nbsp;&nbsp;<a href="javascript:changeMenu()"><img
                src="themes/{$THEMENAME}/images/arrow_top.gif" border="0"></a>&nbsp;&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
<div id="miniMenu" style="display: none;">
  <table cellspacing="0" cellpadding="0" width="100%" class="menumini">
    <tr>
      <td><img src="images/logo_elastix_new_mini.png" border="0"></td>
      <td align="right" class="letra_gris" valign="middle">{$nameMainMenuSelected} &rarr; {$nameSubMenuSelected} {if !empty($idSubMenu2Selected)} &rarr; {$nameSubMenu2Selected} {/if}
		  &nbsp;&nbsp;<img src="themes/{$THEMENAME}/images/tab_notes_bottom.png" alt="tabnotes" id="togglestickynote2" class="togglestickynote" style="cursor: pointer;" border="0"
          align="absmiddle" />
          &nbsp;&nbsp;{if !empty($idSubMenu2Selected)}
            <a href="javascript:popUp('help/?id_nodo={$idSubMenu2Selected}&name_nodo={$nameSubMenu2Selected}','1000','460')">
          {else}
            <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&name_nodo={$nameSubMenuSelected}','1000','460')">
          {/if}<img src="images/help_bottom.gif" border="0"
          align="absmiddle"></a>
          &nbsp;&nbsp;<a href="javascript:changeMenu()"><img src="images/arrow_bottom.gif" border="0" align="absmiddle"></a>&nbsp;&nbsp;
      </td>
    </tr>
  </table>
</div>

<table width="100%" cellpadding="0" cellspacing="0" height="100%">
  <tr>
    {if !empty($idSubMenu2Selected)}
    <td width="200px" align="left" valign="top" bgcolor="#f6f6f6" id="tdMenuIzq">
      <table cellspacing="0" cellpadding="0" width="100%" class="" align="left">
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
<input type="hidden" id="lblRegisterCm"   value="{$lblRegisterCm}" />
<input type="hidden" id="lblRegisteredCm" value="{$lblRegisteredCm}" />
<input type="hidden" id="amount_char_label" value="{$AMOUNT_CHARACTERS}" />
<input type="hidden" id="save_note_label" value="{$MSG_SAVE_NOTE}" />
<input type="hidden" id="get_note_label" value="{$MSG_GET_NOTE}" />
<input type="hidden" id="elastix_theme_name" value="{$THEMENAME}" />
<input type="hidden" id="lbl_no_description" value="{$LBL_NO_STICKY}" />
