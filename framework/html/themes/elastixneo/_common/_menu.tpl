
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

<div id="boxRPM" style="display:none;">
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
                        <img src="themes/{$THEMENAME}/images/closelabel.gif" title="close" class="close_image_box" />
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
</div>
<div id="fade_overlay" class="black_overlay"></div>

<div id="PopupElastix" style="position: absolute; top: 0px; left: 0px;"></div>

{literal}
<style type='text/css'>
#acerca_de{
    -moz-border-radius: 20px 20px 20px 20px;
    -webkit-border-radius: 20px 20px 20px 20px;
    position:fixed;
    background-color:#D8D8D8;
    width:420px;
    height:190px;
    border:1px solid #9C9C9C;
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
/*newwwww*/
$(document).ready(function(){
	$("#toggleleftcolumn, #neo-lengueta-minimized").click(function(){
	    if($("#neo-contentbox-leftcolumn").data("neo-contentbox-leftcolum-status")=="hidden") {
		  $("#neo-contentbox-leftcolumn").removeClass("neo-contentbox-leftcolumn-minimized");
		  $("#neo-contentbox-maincolumn").css("width", "1025px");
	      $("#neo-contentbox-leftcolumn").data("neo-contentbox-leftcolum-status", "visible");
		  $("#neo-lengueta-minimized").addClass("neo-display-none");
	    } else {
		  $("#neo-contentbox-leftcolumn").addClass("neo-contentbox-leftcolumn-minimized");
		  $("#neo-contentbox-maincolumn").css("width", "1245px");
	      $("#neo-contentbox-leftcolumn").data("neo-contentbox-leftcolum-status", "hidden");
		  $("#neo-lengueta-minimized").removeClass("neo-display-none");
	    }
	});
	$("#neo-cmenu-cpallet").hover(
	  function () {
		$(this).addClass("neo-cmenutableft-hvr");
		$( "#search_module_elastix" ).autocomplete( "close" );
		$( "#search_module_elastix" ).val("");
	  },
	  function () {
		$(this).removeClass("neo-cmenutableft-hvr");
	  }
	);
	$("#neo-cmenu-search").hover(
	  function () {
		$(this).addClass("neo-cmenutab-hvr");
		$("#neo-cmenu-showbox-search").removeClass("neo-display-none");
		$( "#search_module_elastix" ).autocomplete( "close" );
		$( "#search_module_elastix" ).val("");
	  },
	  function () {
		$(this).removeClass("neo-cmenutab-hvr");
		$("#neo-cmenu-showbox-search").addClass("neo-display-none");
	  }
	);
	$("#neo-cmenu-info").hover(
	  function () {
		$(this).addClass("neo-cmenutab-hvr");
		$("#neo-cmenu-showbox-info").removeClass("neo-display-none");
		$( "#search_module_elastix" ).autocomplete( "close" );
		$( "#search_module_elastix" ).val("");
	  },
	  function () {
		$(this).removeClass("neo-cmenutab-hvr");
		$("#neo-cmenu-showbox-info").addClass("neo-display-none");
	  }
	);
	$("#neo-cmenu-user").hover(
	  function () {
		$(this).addClass("neo-cmenutab-hvr");
		$("#neo-cmenu-showbox-user").removeClass("neo-display-none");
		$( "#search_module_elastix" ).autocomplete( "close" );
		$( "#search_module_elastix" ).val("");
	  },
	  function () {
		$(this).removeClass("neo-cmenutab-hvr");
		$("#neo-cmenu-showbox-user").addClass("neo-display-none");
	  }
	);
    
	$("#neo-cmenu-showbox-search").hover(
	  function() {
		$("#neo-cmenu-showbox-search").removeClass("neo-display-none");
	  },
	  function() {
		$("#neo-cmenu-showbox-search").addClass("neo-display-none");
	  }
	);

	$("#neo-cmenu-showbox-info").hover(
	  function() {
		$("#neo-cmenu-showbox-info").removeClass("neo-display-none");
	  },
	  function() {
		$("#neo-cmenu-showbox-info").addClass("neo-display-none");
	  }
	);
	$("#neo-cmenu-showbox-user").hover(
	  function() {
		$("#neo-cmenu-showbox-user").removeClass("neo-display-none");
	  },
	  function() {
		$("#neo-cmenu-showbox-user").addClass("neo-display-none");
	  }
	);

	$('.neo-tabh-rend').live('click', function() {
		if($("#neo-second-showbox-menu").hasClass("neo-display-none"))
			$("#neo-second-showbox-menu").removeClass("neo-display-none");
		else
			$("#neo-second-showbox-menu").addClass("neo-display-none");
		$('body').one('click', function(e) {
			$("#neo-second-showbox-menu").addClass("neo-display-none");
			e.stopPropagation();
		});
	});

	$('#search_module_elastix').bind('click', function(e) {
		//$( "#search_module_elastix" ).autocomplete( "close" );
		$( "#search_module_elastix" ).val("");
	});

	$("#export_button").hover(
	  function () {
	      $(this).addClass("exportBorder");
	  },
	  function () {
	      $(this).removeClass("exportBorder");
	      $(this).attr("aria-expanded","false");
	      $(this).removeClass("exportBackground");
	      $(".letranodec").css("color","#444444");
	      $("#subMenuExport").addClass("neo-display-none");
	  }
	);
	$("#export_button").click(
	  function () {
	      if($(this).attr("aria-expanded") == "false"){
		  var exportPosition = $('#export_button').position();
		  var top = exportPosition.top + 22;
		  var left = exportPosition.left - 2;
		  $("#subMenuExport").css('top',top+"px");
		  $("#subMenuExport").css('left',left+"px");
		  $(this).attr("aria-expanded","true");
		  $(this).addClass("exportBackground");
		  $(".letranodec").css("color","#FFFFFF");
		  $("#subMenuExport").removeClass("neo-display-none");
	      }
	      else{
		  $(".letranodec").css("color","#444444");
		  $("#subMenuExport").addClass("neo-display-none");
		  $(this).removeClass("exportBackground");
		  $(this).attr("aria-expanded","false");
	      }
	  }
	);
	$(".menuItem").hover(
	  function () {
		if($(this).attr("aria-disabled") == "false")
		    $(this).css("background","#F4FA58");
	  },
	  function () {
		$(this).css("background","");
	  }
	);
	$("#subMenuExport").hover(
	  function () {
		$(this).removeClass("neo-display-none");
		$(".letranodec").css("color","#FFFFFF");
		$("#export_button").attr("aria-expanded","true");
		$("#export_button").addClass("exportBackground");
	  },
	  function () {
		$(this).addClass("neo-display-none");
		$(".letranodec").css("color","#444444");
		$("#export_button").removeClass("exportBackground");
		$("#export_button").attr("aria-expanded","false");
	  }
	);
	$('#neo-cmenu-cpallet').ColorPicker({
		color: '#0000ff',
		onShow: function (colpkr) {
			$(colpkr).fadeIn(500);
			return false;
		},
		onHide: function (colpkr) {
			$(colpkr).fadeOut(500);
			changeColorMenu();// lanzar el ajax
			return false;
		},
		onChange: function (hsb, hex, rgb) {
			$('#neo-smenubox').css('backgroundColor', '#' + hex);
			$('.neo-tabhon').css('backgroundColor', '#' + hex);
			$('#userMenuColor').val('#' + hex);
		},
		onSubmit: function(hsb, hex, rgb, el) {
			$('#neo-smenubox').css('backgroundColor', '#' + hex);
			$('.neo-tabhon').css('backgroundColor', '#' + hex);
			$('#userMenuColor').val('#' + hex);
            $(el).ColorPickerHide();
			changeColorMenu();// se lanza la peticion ajax
        }
	});
	var menu_color_user = $('#userMenuColor').val();
	$('#neo-smenubox').css('backgroundColor', menu_color_user);
	$('.neo-tabhon').css('backgroundColor', menu_color_user);
        $('#neo-cmenu-cpallet').ColorPickerSetColor(menu_color_user);
});

//]]>
</script>
{/literal}

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

<div id="neo-headerbox">
	<div id="neo-logobox"><img src="themes/{$THEMENAME}/images/elastix_logo_mini2.png" width="200" height="59" alt="elastix" longdesc="http://www.elastix.org" /></div>
	<div id="neo-mmenubox"> <!-- mostrando contenido del menu principal -->
	  {foreach from=$arrMainMenu key=idMenu item=menu name=menuMain}
		{if $idMenu eq $idMainMenuSelected && $smarty.foreach.menuMain.iteration lt 8}
		  <div class="neo-tabhon"><a class='menutable2' href="index.php?menu={$idMenu}">{$menu.Name}</a></div>
		{elseif $smarty.foreach.menuMain.first}
		  <div class="neo-tabh-lend2"><a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></div>
		{elseif $smarty.foreach.menuMain.iteration lt 8 && $smarty.foreach.menuMain.last}
		  <div class="neo-tabh-lend3"><a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></div>
		{elseif $smarty.foreach.menuMain.iteration lt 8}
		  <div class="neo-tabh"><a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></div>
		{elseif $smarty.foreach.menuMain.iteration eq 8}
		  <div class="neo-tabh-rend"><img src="themes/{$THEMENAME}/images/arrowdown.png" width="17" height="15" alt="arrowdown" /></div>
		  <div id="neo-second-showbox-menu" class="neo-second-showbox-menu neo-display-none">
			<p><a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></p>
		{elseif $smarty.foreach.menuMain.iteration ge 8 && $smarty.foreach.menuMain.last}
			<p><a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></p>
		  </div>
		{elseif $smarty.foreach.menuMain.iteration ge 8}
			<p><a class="menutable" href="index.php?menu={$idMenu}">{$menu.Name}</a></p>
		{/if}
	  {/foreach}
		  
	</div>
	<div id="neo-smenubox"> <!-- mostrando contenido del menu secundario -->
	  {foreach from=$arrSubMenuByParents key=idSubMenu item=subMenu}
		{if $idSubMenu eq $idSubMenuSelected}
		  <div class="neo-tabvon"><a href="?menu={$idSubMenu}" class="submenu_on">{$subMenu.Name}</a></div>
		{else}
		  <div class="neo-tabv"><a href="index.php?menu={$idSubMenu}">{$subMenu.Name}</a></div>
		{/if}
	  {/foreach}
	</div>
	<div id="neo-topbar">
	  <div id="neo-cmenubox">
		<div id="neo-cmenu-cpallet" class="neo-cmenutableft"><img src="themes/{$THEMENAME}/images/cpallet.png" width="19" height="21" alt="color" /></div>
		<!--<div id="neo-cmenu-help" class="neo-cmenutableft"><a class="logout" href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&amp;name_nodo={$nameSubMenuSelected}','1000','460')"><img src="themes/{$THEMENAME}/images/helpw.png" width="19" height="21" alt="user_help" border="0" /></a></div>-->
		<div id="neo-cmenu-search" class="neo-cmenutab"><img src="themes/{$THEMENAME}/images/searchw.png" width="19" height="21" alt="user_search" border="0" /></div>
		<div id="neo-cmenu-info" class="neo-cmenutab"><img src="themes/{$THEMENAME}/images/information.png" width="19" height="21" alt="user_info" border="0" /></div>
		<div id="neo-cmenu-user" class="neo-cmenutab"><img src="themes/{$THEMENAME}/images/user.png" width="19" height="21" alt="user" border="0" /></div>
	  </div>
	</div>
	<div id="neo-cmenu-showbox-search" class="neo-cmenu-showbox neo-display-none">
	  <p>{$MODULES_SEARCH}</p>
	  <p><input type="search"  id="search_module_elastix" name="search_module_elastix"  value="" autofocus="autofocus" placeholder="search" /></p>
	</div>
	<div id="neo-cmenu-showbox-info" class="neo-cmenu-showbox neo-display-none">
	  <p><span><a class="register_link" style="color: {$ColorRegister}; cursor: pointer; font-weight: bold; font-size: 13px;" onclick="showPopupElastix('registrar','{$Register}',538,370)">{$Registered}</a></span></p>
	  <p><span><a id="viewDetailsRPMs">{$VersionDetails}</a></span></p>
	  <p><span><a href="http://www.elastix.org" target="_blank">Elastix Website</a></span></p>
	  <p><span><a href="javascript:mostrar();">{$ABOUT_ELASTIX2}</a></span></p>
	</div>
	<div id="neo-cmenu-showbox-user" class="neo-cmenu-showbox neo-display-none">
	  <p><span><a style="cursor: pointer;" onclick="setAdminPassword();">{$CHANGE_PASSWORD}</a></span></p>
	  <p><span><a class="logout" href="?logout=yes">{$LOGOUT}</a> (<font color='#FFFFFF'><b>{$USER_LOGIN}</b></font>)</span></p>
	</div>
</div>

<div id="neo-contentbox">
	{if !empty($idSubMenu2Selected)}
	<div id="neo-contentbox-leftcolumn">
		<div id="neo-3menubox">  <!-- mostrando contenido del menu tercer nivel -->
			{foreach from=$arrSubMenu2 key=idSubMenu2 item=subMenu2}
			  {if $idSubMenu2 eq $idSubMenu2Selected}
				<div class="neo-3mtabon"><a href="index.php?menu={$idSubMenu2}" style="text-decoration: none;">{$subMenu2.Name}</a></div>
			  {else}
				<div class="neo-3mtab"><a href="index.php?menu={$idSubMenu2}" style="text-decoration: none;">{$subMenu2.Name}</a></div>
			  {/if}
			{/foreach}
		</div>
	</div>
	<div id="neo-contentbox-maincolumn" style="width: 1025px;">
	    <div class="neo-module-title"><div class="neo-module-name-left"></div><span class="neo-module-name">
	      {if $icon ne null}
	      <img src="{$icon}" width="22" height="22" align="absmiddle" />
	      {/if}
	      &nbsp;{$title}</span><div class="neo-module-name-right"></div>
	      <div class="neo-module-title-buttonstab-right"></div><span class="neo-module-title-buttonstab">
	      <img src="images/expand.png" width="24" height="24" alt="expand" id="toggleleftcolumn" class="neo-picker" border="0"/>
	      <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&amp;name_nodo={$nameSubMenuSelected}','1000','460')">
	      <img src="images/icon-help.png" width="24" height="24" alt="help" class="neo-picker" border="0"/></a></span><div class="neo-module-title-buttonstab-left"></div></div>
	      <div class="neo-module-content">
	{else}
	<div id="neo-contentbox-maincolumn" style="width: 1228px;">
	    <div class="neo-module-title"><div class="neo-module-name-left"></div><span class="neo-module-name">
	      {if $icon ne null}
	      <img src="{$icon}" width="22" height="22" align="absmiddle" />
	      {/if}
	      &nbsp;{$title}</span><div class="neo-module-name-right"></div>
	      <div class="neo-module-title-buttonstab-right"></div><span class="neo-module-title-buttonstab">
	      <a href="javascript:popUp('help/?id_nodo={$idSubMenuSelected}&amp;name_nodo={$nameSubMenuSelected}','1000','460')">
	      <img src="images/icon-help.png" width="24" height="24" alt="help" border="0"/></a></span><div class="neo-module-title-buttonstab-left"></div></div>
	 <div class="neo-module-content">
	{/if}


