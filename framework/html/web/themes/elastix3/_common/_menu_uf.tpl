
<div id="menudiv">		
 <div id="menu_opc" class="cf">
  <nav class="cf">
   <ul  class="cf">
    {foreach from=$arrMainMenu key=idMenu item=menu}
      <li><a href="index.php?menu={$idMenu}">{$menu.description}</a>
      <!-- <li><a href="javascript:changeModuleUF('{$idMenu}')">{$menu.description}</a> -->
        {if count($menu.children) > 0}
          <ul class="submenu cf">
            {foreach from=$menu.children key=idSubMenu item=subMenu}
              <li><a href="index.php?menu={$idSubMenu}">{$subMenu.description}</a>
              <!-- <li><a href="javascript:changeModuleUF('{$idSubMenu}')">{$subMenu.description}</a></li> -->
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
 
<div id="tooldiv">
    <div id="cont_logo">
        <div id="logo"></div>
    </div>
    <div id="icn_prof" class='tooldivicons'>
        <div id="name">{$USER_NAME}</div>
        <img id="photo" alt="image" src="index.php?menu=_elastixutils&action=getImage&ID={$ID_ELX_USER}&rawmode=yes"/>
        <ul>
            <li><a class="activator" id="activator">Profile</a></li>
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

<!-- The overlay and the box -->
  <div class="overlay" id="overlay" style="display:none;"></div>
  <div class="box" id="box">
   <a class="boxclose" id="boxclose"></a>
    <h1>MY PROFILE</h1>
     <p>
        Aqui van campos del perfil de usuario.
     </p>
  </div>



