
<div id="menudiv">		
  <div id="menu_opc" class="cf">
    <nav class="cf">
      <ul  class="cf">
          {foreach from=$arrMainMenu key=idMenu item=menu}
          <li><a href="index.php?menu={$idMenu}">{$menu.description}</a>
             {if count($menu.children) > 0}
                 <ul class="submenu cf">
                     {foreach from=$menu.children key=idSubMenu item=subMenu}
                     <li><a href="index.php?menu={$idSubMenu}">{$subMenu.description}</a></li>
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
       
	      <div id="icn_prof">
		<div id="name">{$USER_NAME}</div>
                {if $ShowImg}
                <img id="photo" alt="image" src="index.php?menu=_elastixutils&action=getImage&ID={$id_user}&rawmode=yes"/>
                {else}
                <img alt="image" style="margin-top: 2px; height: 36px; width: 38px;" src="web/apps/{$MODULE_NAME}/images/Icon-user.png"/>
                {/if}
                <ul>
		 <!-- FALTA IMPLEMENTAR FUNCIONALIDAD
                  <li>Profile
		  <ul>
		   <li>View</li>
		   <li>Edit Photo</li>
		   <li>Color
	        	<ul>
			  <li>Red</li>
		          <li>Blue</li>
			  <li>Green</li>
			</ul>
		    </li>
		    <li>Language
			<ul>
	         	  <li>English</li>
			  <li>Spanish</li>
			</ul>	
         	     </li>	
		  </ul>
	         </li> 
                -->
	         <li><a class="logout" href="?logout=yes">{$LOGOUT}</a></li>
                </ul>
	      </div>
   <div id="main_opc">
		<div class="icn_m"><span class="lp ml10">&#9993;</span></div>
		<div class="icn_m"><span class="lp ml10">&#59158;</span></div>	
		<div class="icn_m"><span class="lp ml10">&#128260;</span></div>	
                <div class="icn_m" id="filter_but"><span class="lp ml10">&#128269;</span></div>		
		 
   </div>
   
			 <div id="icn_disp2" class="icn_m">
			 <span class="icn_d">h</span>
			 </div>	
   		
    </div>
			</div>
               </div>

