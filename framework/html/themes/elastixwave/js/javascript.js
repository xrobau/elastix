$(document).ready(function() {
	$(window).resize(function() {
		// Ajustar menú de primer nivel
		elxneo_adjust_mmenu();

		elxneo_resize_jcresizer_table();
	});

	// Ajustar menús
	elxneo_adjust_mmenu();

	// Botones para manejo de menú de segundo nivel
	var elxneo_scroll_timer = null;
	$('#smenubox-arrows>img:first-child')
		.mousedown(function() {
			if (elxneo_scroll_timer != null) clearInterval(elxneo_scroll_timer);
			elxneo_scroll_timer = setInterval(elxneo_move_smenu_left, 90)
		}).mouseup(function() {
			if (elxneo_scroll_timer != null) clearInterval(elxneo_scroll_timer);
		});
	$('#smenubox-arrows>img:last-child')
		.mousedown(function() {
			if (elxneo_scroll_timer != null) clearInterval(elxneo_scroll_timer);
			elxneo_scroll_timer = setInterval(elxneo_move_smenu_right, 90)
		}).mouseup(function() {
			if (elxneo_scroll_timer != null) clearInterval(elxneo_scroll_timer);
		});

	// Aplicar los cambios de color a los menús
	$('#cmenubox>div#cpallet').ColorPicker({
		color: '#0000ff',
		onHide: elxneo_upload_colorchange,
		onChange: function (hsb, hex, rgb) {
			$('#userMenuColor').val('#' + hex);
			elxneo_display_colorchange();
		},
		onSubmit: function(hsb, hex, rgb, el) {
			$('#userMenuColor').val('#' + hex);
			elxneo_display_colorchange();
        	$(el).ColorPickerHide();
        	elxneo_upload_colorchange();
        },
		id_colorPicker: 'colorpicker_framework'
	});
	elxneo_display_colorchange();
	$('#cmenubox>div#cpallet').ColorPickerSetColor($('#userMenuColor').val());


	// Manejo de la columna de menú de tercer nivel
	$("#toggleleftcolumn").click(function() {
		request("index.php", {
			menu:		'_elastixutils',
			id_menu:	getCurrentElastixModule(),
			action:		'saveNeoToggleTab',
			statusTab:	$('#neo-lengueta-minimized').is(':visible') ? 'false' : 'true',
			rawmode:	'yes'
		}, false, function(arrData,statusResponse,error) {
			if(statusResponse == "false") {
				elxneo_toggle_3menu_visible();
				alert(error);
			}
		});
	});
	$("#toggleleftcolumn, #neo-lengueta-minimized").click(elxneo_toggle_3menu_visible);

	// Manejo de marcadores
	$("#togglebookmark").click(function() {
		var imgBookmark = $("#togglebookmark").attr('src');
		elastix_blockUI((/bookmarkon.png/.test(imgBookmark))
			? $('#toolTip_removingBookmark').val()
			: $('#toolTip_addingBookmark').val());

		request("index.php", {
			menu:		'_elastixutils',
			id_menu:	getCurrentElastixModule(),
			action:		'addBookmark',
			rawmode:	'yes'
		}, false, function(arrData, statusResponse, error) {
			$.unblockUI();
		    if (statusResponse == "false") {
		    	alert(error);
		    	return;
		    }

		    if (arrData['action'] == 'add') {
		    	$("#togglebookmark")
		    		.attr('src',"themes/"+$('#elastix_theme_name').val()+"/images/bookmarkon.png")
		    		.attr('title', $("#toolTip_removeBookmark").val());
		    	$('<div id="menu'+arrData['idmenu']+'"><a href="index.php?menu='+arrData['menu_session']+'">'+arrData['menu']+'</a><div class="neo-bookmarks-equis"></div></div>')
		    		.insertAfter('#neo-bookmarkID');
		    	$('div#historybox>div[id^=menu]')
		    		.removeClass('neo-historybox-tabmid')
		    		.last().addClass('neo-historybox-tabmid');
		    }
		    if (arrData['action'] == 'delete') {
		    	$("#togglebookmark")
		    		.attr('src',"themes/"+$('#elastix_theme_name').val()+"/images/bookmark.png")
		    		.attr('title', $("#toolTip_addBookmark").val());
		    	elxneo_remove_bookmarktab(arrData['idmenu']);
		    }
		});
	});
	$('#historybox').on('click', 'div>div.neo-bookmarks-equis', function() {
		// Quitar el marcador al hacer clic en la equis
		elastix_blockUI($('#toolTip_removingBookmark').val());
		request("index.php", {
			menu:		'_elastixutils',
			id_menu:	$(this).prev('a').attr('href').split('menu=', 2)[1],
			action:		'deleteBookmark',
			rawmode:	'yes'
		}, false, function(arrData, statusResponse, error) {
			$.unblockUI();
			if (statusResponse == "false") {
				alert(error);
				return;
			}
			if (arrData['action'] != "delete") return;

			// Sólo hacer esto si el menu actual es el que se esta eliminando
			if (arrData['menu_url'] == arrData['menu_session']) {
				$('#togglebookmark')
					.attr('src', "themes/"+$('#elastix_theme_name').val()+"/images/bookmark.png")
					.attr('title', $("#toolTip_addBookmark").val());
			}

			elxneo_remove_bookmarktab(arrData['idmenu']);
		});
	});

	$('a.elxneo-changemenu').click(function() {
	    /* Este manejador hace un trabajo parecido a changeMenu() pero debe
	     * reimplementarse porque el layout con position:absolute impide a los
	     * divs redimensionarse automáticamente. */
	    if ($('div#elxneo-topnav-toolbar').is(':visible')) {
	        // Modo de menú normal, se esconde
	        $('div#elxneo-topnav-toolbar').hide();
	        $('div#elxneo-leftcolumn').addClass('hidden-minimenu');
	        $('div#elxneo-topnav-minitoolbar').show();
	        $('div#elxneo-wrap').addClass('elxneo-wrap-minimenu');
	        $('div#neo-lengueta-minimized').hide();
	        $('div#elxneo-maincolumn').css('margin-left', '0');
	    } else {
	        // Modo de menú mini, se muestra
	        $('div#elxneo-topnav-minitoolbar').hide();
	        $('div#elxneo-topnav-toolbar').show();
	        $('div#elxneo-leftcolumn').removeClass('hidden-minimenu');
	        $('div#elxneo-wrap').removeClass('elxneo-wrap-minimenu');
	        if ($('div#elxneo-leftcolumn').hasClass('hidden-menutab')) {
                $('div#neo-lengueta-minimized').show();
                $('div#elxneo-maincolumn').css('margin-left', '15px'); // TODO: ancho de lengueta
            }
	    }
	    elxneo_resize_jcresizer_table();
	});

    $('div#elxneo-topnav-toolbar > div#cmenubox > div > a#togglesidebar, div#chat > h2.chat-header > a.chat-close').click(function(e) {
        e.preventDefault();
        if ($('body').hasClass('chat-visible'))
            $('body').removeClass('chat-visible');
        else
            $('body').addClass('chat-visible');
        elxneo_adjust_mmenu();
    });

    $('div#chat > div#elastix-panels').accordion({
        heightStyle: 'content',
        icons: null
    });
});

function elxneo_remove_bookmarktab(idmenu)
{
	$('div#historybox>div#menu'+idmenu).remove();
	var bookmarks_left = $('div#historybox>div[id^=menu]');
	if (bookmarks_left.length <= 0) {
		$('#neo-bookmarkID').hide();
	} else {
		bookmarks_left.last().addClass('neo-historybox-tabmid');
	}
}

function elxneo_adjust_mmenu()
{
	// Restaurar todos los items guardados anteriormente en overflow
	$('div#elxneo-mmenu-overflow>div')
		.detach()
		.insertBefore('div#mmenubox>div:last-child');

	// La flecha de overflow debe estar con top == 0, lo que indica que ya no
	// hay elementos de menú que se desparraman.
	while ($('div#mmenubox>div:last-child').position().top > 0)
		elxneo_move_one_mmenu_to_overflow();

	// Verificar si el primer nivel seleccionado está en overflow
	var sel_overflow = $('div#elxneo-mmenu-overflow>div.selected');
	while (sel_overflow.length > 0) {
		// Quitar uno más para hacer espacio
		elxneo_move_one_mmenu_to_overflow();

		sel_overflow.detach().insertBefore('div#mmenubox>div:last-child');

		// Se verifica si selected vuelve a desparramarse al ser agregado.
		while ($('div#mmenubox>div:last-child').position().top > 0)
		    elxneo_move_one_mmenu_to_overflow();
		sel_overflow = $('div#elxneo-mmenu-overflow>div.selected');
	}

	// Mostrar el control de scroll de segundo nivel si el último item
	// se desparrama, ocultarlo si no.
	if ($('div#smenubox>div:last-child').position().top > 0)
		$('div#smenubox-arrows').show();
	else $('div#smenubox-arrows').hide();
}

function elxneo_move_one_mmenu_to_overflow()
{
	$('div#mmenubox>div:last-child').prev()
		.detach()
		.prependTo('div#elxneo-mmenu-overflow');
}

function elxneo_display_colorchange()
{
//	$('#mmenubox>div.selected, #smenubox').css('background-color', $("#userMenuColor").val());
}

function elxneo_upload_colorchange()
{
	var color = $('#userMenuColor').val();
	if(color == ""){
		color = "#454545";
	}

	request("index.php", {
		menu:	'_elastixutils',
		action:	'changeColorMenu',
		menuColor:	color
	}, false, function(arrData,statusResponse,error) {
		if(statusResponse == "false") alert(error);
	});
}

function elxneo_move_smenu_right()
{
	$('div#smenubox>div:first-child').detach().appendTo('div#smenubox');
}

function elxneo_move_smenu_left()
{
	$('div#smenubox>div:last-child').detach().prependTo('div#smenubox');
}

function elxneo_toggle_3menu_visible()
{
	if ($('#neo-lengueta-minimized').is(':visible')) {
		$('#neo-lengueta-minimized').hide();
		$('#elxneo-leftcolumn').removeClass('hidden-menutab');
		/*
		$('#toggleleftcolumn')
			.attr('src',"images/expand.png")
			.attr('title', $('#toolTip_hideTab').val());
		*/
	} else {
		$('#elxneo-leftcolumn').hide();
		$('#neo-lengueta-minimized').addClass('hidden-menutab');
		/*
		$('#toggleleftcolumn')
			.attr('src',"images/expandOut.png")
			.attr('title', $('#toolTip_showTab').val());
		*/
	}

	elxneo_resize_jcresizer_table();
}

function elxneo_resize_jcresizer_table()
{
	var elxtables = $('form.elastix-standard-formgrid>table.elastix-standard-table');

	// Desactivar y volver a aplicar colResizable luego de quitar width
	elxtables.colResizable({disable: true});
	elxtables.find('thead>tr>th').css('width', '');
	elxneo_apply_jresizer_table();
}

function elxneo_apply_jresizer_table()
{
    $('form.elastix-standard-formgrid>table.elastix-standard-table').each(function() {
        var wt = $(this).find('thead>tr').width();
        $(this).find('thead>tr>th').each(function () {
            var wc = $(this).width();
            var pc = 100.0 * wc / wt;
            $(this).width(pc + "%");
        });
        $(this).colResizable({
            liveDrag:   true,
            marginLeft: "0px"
        });
    });
}
