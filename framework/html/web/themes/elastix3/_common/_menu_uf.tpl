
<div id="menudiv">		
   <div id="menu_opc" class="cf">
	<nav class="cf">
		<ul class="cf">
                        {foreach from=$arrMainMenu key=idMenu item=menu name=menuMain}
			<li><a href="index.php?menu={$idMenu}">{$menu.description}</a></li>
			{/foreach}
		</ul>
	        <a href="#" id="pull">MENUS</a>	
        </nav>
   </div>
</div>


