<!--
<div id="menudiv">      
 <div id="menu_opc" class="cf">
  <nav class="cf elx_nav_main_menu">
   <ul class="cf ul_nav_main_menu">
    {foreach from=$arrMainMenu key=idMenu item=menu}
      <li class='li_nav_main_menu_1'><a href="index.php?menu={$idMenu}">{$menu.description}</a>
        {if count($menu.children) > 0}
          <ul class="ul_nav_main_menu submenu cf">
            {foreach from=$menu.children key=idSubMenu item=subMenu}
              <li class='li_nav_main_menu_2'><a href="index.php?menu={$idSubMenu}">{$subMenu.description}</a>
            {/foreach}
          </ul>
        {/if}
      </li>
    {/foreach}
   </ul>
   <a href="#" id="pull">MENUS</a>  
  </nav>
 </div>
</div>
-->

<div id="menudiv">
    <div id="menu_opc">
        <nav class="navbar navbar-inverse elx_nav_main_menu" role="navigation">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    {foreach from=$arrMainMenu key=idMenu item=menu}
                        {if count($menu.children) > 0}
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">{$menu.description}</a>
                                <ul class="dropdown-menu navbar-inverse">
                                    {foreach from=$menu.children key=idSubMenu item=subMenu}
                                        <li ><a href="index.php?menu={$idSubMenu}">{$subMenu.description}</a>
                                    {/foreach}
                                </ul>
                            </li>
                        {else}
                            <li ><a href="index.php?menu={$idMenu}">{$menu.description}</a>
                        {/if}
                    {/foreach}
                </ul>
            </div>
        </nav>
    </div>
</div>


<div id="tooldiv">
    <div id="cont_logo">
        <div id="logo"></div>
    </div>
    <div id="icn_prof" class='tooldivicons'>
        <div id="name">{$USER_NAME}</div>
        <img id="photo" alt="image" src="index.php?menu=_elastixutils&action=getImage&ID={$ID_ELX_USER}&rawmode=yes"/>
        <ul>
            <li><a class="activator" id="activator" href="javascript:showProfile()">Profile</a></li>
            <li class='icn_prof-li-last' ><a class="logout" href="?logout=yes">{$LOGOUT}</a></li>
        </ul>
    </div>
    <div id="icn_disp2" class="icn_m tooldivicons">
        <span class="icn_d">h</span>
    </div>
    <div id="main_opc">
        {$CONTENT_OPT_MENU}
    </div>
</div>


<!-- The overlay and the box popup user profile -->
  <div class="overlay" id="overlay" style="display:none;"></div>
  <div class="modal fade" id="elx_popup_profile" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" id='elx_profile_content'>
        <!-- se llama al profile_uf.tpl -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->

