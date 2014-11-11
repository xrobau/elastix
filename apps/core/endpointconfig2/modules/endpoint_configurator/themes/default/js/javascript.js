// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Function/bind
if (!Function.prototype.bind) {
	Function.prototype.bind = function (oThis) {
		if (typeof this !== "function") {
			// closest thing possible to the ECMAScript 5 internal IsCallable function
			throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
		}

		var aArgs = Array.prototype.slice.call(arguments, 1), 
        	fToBind = this, 
        	fNOP = function () {},
        	fBound = function () {
        		return fToBind.apply(this instanceof fNOP && oThis
                                 ? this
                                 : oThis,
                               aArgs.concat(Array.prototype.slice.call(arguments)));
        };

        fNOP.prototype = this.prototype;
        fBound.prototype = new fNOP();

        return fBound;
	};
}

var module_name = 'endpoint_configurator';
var App = null;

// Arreglo asociativo de funciones de inicialización de diálogos
var endpointConfigDlgInit = {};

$(document).ready(function() {
    $('.neo-module-content').css("padding-bottom","0px");
	
    if (lastop_error_message != null)
    	mostrar_mensaje_error(lastop_error_message);
    
	// Inicialización de Ember.js
	App = Ember.Application.create({
		/*
		LOG_TRANSITIONS: true,
		LOG_ACTIVE_GENERATION: true,
		LOG_VIEW_LOOKUPS: true,
		*/
		rootElement:	'#endpointConfigApplication'
	});
	initializeJQueryUIViews();

	App.Endpoint = Ember.Object.extend({
		// Los siguientes parámetros son campos de la base de datos
		id_endpoint: null,
		id_manufacturer : null,
		id_model: 'unknown',
		mac_address: null,
		last_known_ipv4: null,
		last_scanned: null,
		last_modified: null,
		last_configured: null,
		name_manufacturer: null,
		num_accounts: 0,
		detail_dialog: null,

		selectchanged: function() {
			//console.debug('Valor de isSelected: ' + this.get('isSelected'));
		}.observes('isSelected'),
		
		
		is_missing: false,	// TODO: implementar uso de bandera
		
		isSelected: false,	// Bandera seteada si se selecciona el checkbox
		modelSelect: null,	// Lista de modelos aplicables a este endpoint
		modelObj: null,		// Objeto del modelo seleccionado
		details: null, 		// Detalles del endpoint
		
		modelos: null,		// Referencia a App.EndpointsController.modelos
		
		init: function() {
			this.vendorChanged();
			this.refreshModelObj();
		},		
		
		// URL de administración HTTP del teléfono
		adminUrl: function() {
			return 'http://'+ this.get('last_known_ipv4')+'/';
		}.property('last_known_ipv4'),
		
		// VERDADERO si el endpoint se define de archivo en lugar de escaneo
		isFromBatch: function() {
			return (this.get('last_scanned') == null);
		}.property('last_scanned'),
		
		// VERDADERO si hay cambios no aplicados en el endpoint
		isModified: function() {
			if (this.get('last_modified') == null) return false;
			if (this.get('last_configured') != null)
				return (this.get('last_modified') > this.get('last_configured'));
			/*
			else if (this.get('last_scanned') != null) {
				return (this.get('last_modified') > this.get('last_scanned'));
			*/
			else
				return true;
		}.property('last_modified', 'last_configured'),
		
		// VERDADERO si hay al menos una extensión
		hasExtensions: function() {
			return (this.get('num_accounts') > 0);
		}.property('num_accounts'),
		
		// Observador que actualiza la DB si se elige otro modelo
		modelChanged: function() {
			this.refreshModelObj();			
			$.post('index.php?menu=' + module_name + '&rawmode=yes', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'setEndpointModel',
				id_endpoint:this.get('id_endpoint'),
				id_model:	this.get('id_model')
			},
			function(respuesta) {
				if (respuesta.status == 'error') {
					mostrar_mensaje_error(respuesta.message);
					return;
				}
				if (respuesta.last_modified != null)
					this.set('last_modified', respuesta.last_modified);
			}.bind(this));
			
		}.observes('id_model'),
		
		// Observador que actualiza el modelo si se elige otro vendedor.
		vendorChanged: function() {
			this.set('modelSelect', this.modelos[this.get('id_manufacturer')]);
		}.observes('id_manufacturer'),
		
		// Seleccionar el nuevo objeto de modelo
		refreshModelObj: function() {
			var ms = this.get('modelSelect');
			var m = ms.findProperty('id_model', this.get('id_model'));
			if (m != null && m.get('id_model') == 'unknown')
				m = null;
			this.set('modelObj', m);
		},
		
		// Iniciar la carga de los detalles del endpoint
		setDetails: function(respuesta) {
			if (respuesta.status == 'error') {
				mostrar_mensaje_error_dialog(respuesta.message);
				return;
			}
			this.set('details', App.detailClass[this.get('detail_dialog')].create(respuesta.details));
		},
		saveDetails: function()
		{
			var bExito = true;
			var json = this.get('details').getjson();
			json['action'] = this.get('detail_dialog') + '_saveDetails';
			json['id_endpoint'] = this.get('id_endpoint');
			$.ajax({
				url:	'index.php?menu=' + module_name + '&rawmode=yes',
				type:	'POST',
				async:	false,
				data:	json
			}).done(function(respuesta) {
				if (respuesta.status == 'error') {
					mostrar_mensaje_error_dialog(respuesta.message);
					bExito = false;
				} else {
					this.set('last_modified', respuesta.last_modified);
					this.set('num_accounts', this.get('details').numaccounts());
				}
			}.bind(this)).fail(function(jqXHR, textStatus, errorThrown) {
				bExito = false;
				mostrar_mensaje_error_dialog(textStatus);
			});
			return bExito;
		}
	}); 
	
	App.Account = Ember.Object.extend({
		id_account:		null,
		tech:			null,
		account:		null,
		extension:		null,
		priority:		null,
		description:	null,
		registerip:		null,
		properties:		null,
		
		propertiesController: null,
		
		init: function() {
			if (this.get('properties') == null)
				this.set('properties', new Array());
		},
		
		idattr: function() {
			return ((this.get('id_account') != null) ? 'bound' : 'unbound') +
				'-' + this.get('tech') + '-' + this.get('account');
		}.property('tech', 'account', 'id_account')
	});
	
	// Arreglo de objetos de clases para los detalles del endpoint
	App.detailClass = {}
	
	App.EndpointsController = Ember.ArrayController.extend({
		modelos: null,
		
		loading: true,
		completeList:[
		     /*
		     App.Endpoint.create({
		    	 id_endpoint: 1,
		    	 id_manufacturer: 1,
		    	 id_model: 1,
		    	 mac_address: '00:11:22:33:44:55',
		    	 last_known_ipv4:	'10.0.0.1',
		    	 last_scanned:		'2012-12-31',
		    	 last_configured:	null
		     }),
		     */
		],
		content:	[],
		offset:	null,
		limit:	10,
		
		displaySlice: function(offset) {
			if (offset >= this.completeList.length)
				offset = this.completeList.length - 1;
			if (offset < 0) offset = 0;
			offset = offset - (offset % this.get('limit'));
			if (this.get('offset') == null || offset != this.offset) {
				var slice = this.completeList.slice(offset, offset + this.get('limit'));
				this.set('offset', offset);
				this.set('content', slice);				
			}
		},
		displayStart: function() { this.displaySlice(0); },
		displayPrevious: function() { this.displaySlice(this.offset - this.limit); },
		displayNext: function() { this.displaySlice(this.offset + this.limit); },
		displayEnd: function() { this.displaySlice(this.completeList.length); },
		startPosition: function() {
			return this.get('offset') + 1;
		}.property('offset'),
		endPosition: function() {
			var pos = this.get('offset') + this.get('limit');
			if (pos > this.completeList.length)
				pos = this.completeList.length;
			return pos;
		}.property('offset', 'limit', 'completeList.@each'),
		displayRefresh: function() {
			var offset = this.get('offset');
			if (offset >= this.completeList.length) {
				this.set('offset', null);
				this.displaySlice(offset);
			} else {
				var slice = this.completeList.slice(offset, offset + this.get('limit'));
				while (this.get('length') > slice.length) this.popObject();
				for (var i = 0; i < this.get('length'); i++) {
					if (this.objectAt(i).get('id_endpoint') != slice[i].get('id_endpoint')) {
						this.replace(i, 1, [slice[i]]);
					}
				}
				for (var i = this.get('length'); i < slice.length; i++) {
					this.pushObject(slice[i]);
				}
			}
		}.observes('completeList.@each'),
		seleccionTodos: false,
		seleccionarTodos: function() {
			var b = this.get('seleccionTodos');
			for (var i = 0; i < this.completeList.length; i++) {
				this.completeList[i].set('isSelected', b);
			}
		}.observes('seleccionTodos'),
		forgetSelected: function () {
			if (!confirm(arrLang_main['CONFIRM_FORGET'])) return;

			this.set('unsetInProgress', true);
			$.post('index.php?menu=' + module_name + '&rawmode=yes', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'forgetSelected',
				selection:  this.completeList.filterProperty('isSelected').mapProperty('id_endpoint')
			},
			function(respuesta) {
				if (respuesta.status == 'error') {
					mostrar_mensaje_error(respuesta.message);
				} else {
					this.set('completeList', this.completeList.filterProperty('isSelected', false));
					this.displayStart();
				}
				this.set('unsetInProgress', false);
			}.bind(this));
		},
		setEndpoints: function(respuesta) {
			this.completeList.clear();
			if (respuesta.status == 'error') {
				mostrar_mensaje_error(respuesta.message);
			} else {
				var temp = [];
				for (i = 0; i < respuesta.endpoints.length; i++) {
					respuesta.endpoints[i].modelos = this.modelos;
					temp.addObject(App.Endpoint.create(respuesta.endpoints[i]));
				}
				this.set('completeList', temp);
				this.set('offset', null);
				this.displayStart();
			}
			this.set('loading', false);
			
			$('#loadAnimation').hide();
		},

		setModels: function(respuesta) {
			this.modelos = {};
			for (var id_manufacturer in respuesta.models) {
				var modelContent = [];
				for (id_model in respuesta.models[id_manufacturer]) {
					modelContent.addObject(Ember.Object.create(respuesta.models[id_manufacturer][id_model]));
				}
				this.modelos[id_manufacturer] = Ember.ArrayController.create({content: modelContent});
			}
		},
		
		
		longPoll:	null,		// Objeto de POST largo
		evtSource:	null,		// Objeto EventSource, si está soportado por el navegador
		
		scanInProgress: false,
		unsetInProgress: false,
		configInProgress: false,
		scanMask: null,
		estadoClienteHash: null,
		
		completedsteps : 0,
		totalsteps: 0,
		founderror: false,
		
		uploadInProgress: false,
		totalupload: 100,
		completedupload: 0,

		uiblock: function() {
			return this.get('scanInProgress')
				|| this.get('unsetInProgress')
				|| this.get('configInProgress');
		}.property('scanInProgress', 'unsetInProgress', 'configInProgress'),		
		
		loadStatus: function() {
			$.get('index.php', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'loadStatus'
			},
			function(respuesta) {
				this.set('scanMask', respuesta.scanMask);
				this.set('scanInProgress', respuesta.scanInProgress);
				this.set('configInProgress', respuesta.configInProgress);
				this.set('estadoClienteHash',
					(respuesta.scanInProgress || respuesta.configInProgress) ? respuesta.estadoClienteHash : null);
				if (respuesta.scanInProgress) setTimeout(this.scanStatus.bind(this), 1);
				if (respuesta.configInProgress) setTimeout(this.configStatus.bind(this), 1);
			}.bind(this));
		},
		scanStart: function() {
			var scanMask = this.get('scanMask');
			var maskRegex = new RegExp("^\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}/\\d{1,2}$");
			
			if (this.evtSource != null || this.longPoll != null) {
				mostrar_mensaje_error(arrLang_main['SCANCONFIG_INPROGRESS']);
				return;
			}
			if (!maskRegex.test(scanMask)) {
				mostrar_mensaje_error(arrLang_main['INVALID_SCANMASK']);
				return;
			}
			this.set('scanInProgress', true);
			$.post('index.php?menu=' + module_name + '&rawmode=yes', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'scanStart',
				scanMask:	scanMask
			},
			function(respuesta) {
				if (respuesta.status == 'error') {
					this.set('scanInProgress', false);
					mostrar_mensaje_error(respuesta.message);
					return;
				}
				this.set('estadoClienteHash', respuesta.estadoClienteHash);
				setTimeout(this.scanStatus.bind(this), 1);
			}.bind(this));
		},
		scanCancel: function() {
			$.post('index.php?menu=' + module_name + '&rawmode=yes', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'scanCancel'
			},
			function(respuesta) {
				if (respuesta.status == 'error') {
					mostrar_mensaje_error(respuesta.message);
					return;
				}
				// La revisión de eventos debería enterarse que terminó el escaneo
			});
		},
		scanStatus: function() {
			var params = {
					menu:		module_name, 
					rawmode:	'yes',
					action:		'scanStatus',
					clientstatehash: this.get('estadoClienteHash')
				};

			if (window.EventSource) {
				params['serverevents'] = true;
				this.evtSource = new EventSource('index.php?' + $.param(params));
				this.evtSource.onmessage = function(event) {
					this.manejarRespuestaStatus($.parseJSON(event.data));
				}.bind(this);
			} else {
				this.longPoll = $.post('index.php?menu=' + module_name + '&rawmode=yes', params,
				function (respuesta) {
					if (this.manejarRespuestaStatus(respuesta)) {
						// Lanzar el método de inmediato
						setTimeout(this.scanStatus.bind(this), 1);
					}
				}.bind(this));
			}
		},
		configStart: function() {
			if (this.evtSource != null || this.longPoll != null) {
				mostrar_mensaje_error(arrLang_main['SCANCONFIG_INPROGRESS2']);
				return;
			}
			this.set('configInProgress', true);
			$.post('index.php?menu=' + module_name + '&rawmode=yes', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'configStart',
				selection:  this.completeList.filterProperty('isSelected').mapProperty('id_endpoint')
			},
			function(respuesta) {
				if (respuesta.status == 'error') {
					this.set('configInProgress', false);
					mostrar_mensaje_error(respuesta.message);
					return;
				}
				this.set('estadoClienteHash', respuesta.estadoClienteHash);
				setTimeout(this.configStatus.bind(this), 1);
			}.bind(this));
		},
		configStatus: function() {
			var params = {
					menu:		module_name, 
					rawmode:	'yes',
					action:		'configStatus',
					clientstatehash: this.get('estadoClienteHash')
				};

			if (window.EventSource) {
				params['serverevents'] = true;
				this.evtSource = new EventSource('index.php?' + $.param(params));
				this.evtSource.onmessage = function(event) {
					this.manejarRespuestaStatus($.parseJSON(event.data));
				}.bind(this);
			} else {
				this.longPoll = $.post('index.php?menu=' + module_name + '&rawmode=yes', params,
				function (respuesta) {
					if (this.manejarRespuestaStatus(respuesta)) {
						// Lanzar el método de inmediato
						setTimeout(this.scanStatus.bind(this), 1);
					}
				}.bind(this));
			}
		},
		manejarRespuestaStatus: function(respuesta) {
			//console.debug(respuesta);
			
			// Intentar recargar la página en caso de error
			if (respuesta.error != null) {
				window.alert(respuesta.error);
				location.reload();
				return false;
			}

			if (respuesta.estadoClienteHash == 'mismatch') {
				/* Ha ocurrido un error y se ha perdido sincronía. Si el hash que 
				 * recibió es distinto a estadoClienteHash entonces esta es una petición
				 * vieja. Si es idéntico debe de recargase la página.
				 */
				if (respuesta.hashRecibido == this.get('estadoClienteHash')) {
					// Realmente se ha perdido sincronía
					//console.error("Lost synchronization with server, reloading page...");
					location.reload();
				} else {
					// Se ha recibido respuesta luego de que supuestamente se ha parado
					//console.warn("Received mismatch from stale SSE session, ignoring...");
				}
				return false;
			}
			this.set('estadoClienteHash', respuesta.estadoClienteHash);
			
			if (respuesta.totalsteps != null) this.set('totalsteps', respuesta.totalsteps);
			if (respuesta.completedsteps != null) this.set('completedsteps', respuesta.completedsteps);
			if (respuesta.founderror != null) this.set('founderror', respuesta.founderror);
			
			if (respuesta.endpointchanges != null) for (var i = 0; i < respuesta.endpointchanges.length; i++) {
				epinfo = respuesta.endpointchanges[i][1];
				switch (respuesta.endpointchanges[i][0]) {
				case 'insert':
					// Se ha descubierto un nuevo endpoint
					epinfo.modelos = this.modelos;
					this.completeList.addObject(App.Endpoint.create(epinfo));
					break;
				case 'update':
					// Se ha actualizado información sobre un endpoint
					for (var j = 0; j < this.completeList.length; j++) {
						if (epinfo['id_endpoint'] == this.completeList[j].get('id_endpoint')) {
							for (var k in epinfo) this.completeList[j].set(k, epinfo[k]);
							break;
						}
					}
					break;
				case 'delete':
					// Se ha removido un endpoint de la base
					for (var j = 0; j < this.completeList.length; j++) {
						if (epinfo['id_endpoint'] == this.completeList[j].get('id_endpoint')) {
							this.completeList.removeAt(j);
							break;
						}
					}
					break;
				case 'quit':
					// Final del escaneo
					var isConfig = this.get('configInProgress');
					this.set('scanInProgress', false);
					this.set('configInProgress', false);
					this.set('totalsteps', 0);
					this.set('completedsteps', 0);
					this.set('founderror', false);
					if (this.evtSource != null) {
						this.evtSource.onmessage = function(event) {
							//console.warn("This evtSource was closed but still receives messages!");
						}
						this.evtSource.close();
						this.evtSource = null;
					}
					if (this.longPoll != null) {
						this.longPoll.abort();
						this.longPoll = null;
					}
					
					// Mostrar posible mensaje de error
					if (isConfig) {
						if (epinfo != null) {
							mostrar_mensaje_error(epinfo);
						} else {
							mostrar_mensaje_info(arrLang_main['CONFIGURATION_SUCCESS']);
						}
					}
					
					return false;	// Se aborta la petición periódica
				}
			}
			return true;
		},
	
		fileUploadSupported: function() {
			return ((typeof File) != 'undefined');
		}.property()
	});


	App.AccountsController = Ember.ArrayController.extend({
		content: null,
		setUnassignedAccounts: function(respuesta) {
			if (respuesta.status == 'error') {
				mostrar_mensaje_error_dialog(respuesta.message);
				return;
			}
			//console.debug(respuesta);
			for (var i = 0; i < respuesta.accounts.length; i++)
				respuesta.accounts[i] = App.Account.create(respuesta.accounts[i]);
			this.set('content', respuesta.accounts);
		}
	});
	App.accountsController = App.AccountsController.create({
		content: [],
	});

	App.NetmaskView = Ember.TextField.extend({
		attributeBindings: ['style'],
		style: "height:22px; width: 120px",
		maxlength: 18,
		pattern: "^\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}/\\d{1,2}$"
	});
	
	App.NeoNavigationView = Ember.View.extend({
		templateName: 'neo-navigation',
		classNames: ['neo-endpointconfig-header-row-navigation']
	});
	App.ConfigProgressView = JQ.ProgressBar.extend({
	});

	App.Router.map(function() {
		this.resource('endpoints', { path: '/' }, function () {
			this.route('getconfiglog');
			this.route('endpointconfig', { path: '/endpointconfig/:id_endpoint' });
		});
	});
	
	App.EndpointsRoute = Ember.Route.extend({
		model: function () {
			return Ember.RSVP.hash({
				'models'	:	Ember.$.get('index.php', {
					menu:		module_name, 
					rawmode:	'yes',
					action:		'loadModels'
				}),
				'endpoints'	:	Ember.$.get('index.php', {
					menu:		module_name, 
					rawmode:	'yes',
					action:		'loadEndpoints'
				})
			});
		},
		
		setupController: function(controller, model) {
			controller.setModels(model.models);
			controller.setEndpoints(model.endpoints);
			controller.loadStatus();
		}
	});
	
	App.EndpointsEndpointconfigRoute = Ember.Route.extend({
		model: function(params) {
			controller = this.controllerFor('endpoints');
			
			if (controller.get('modelos') == null) {
	        	this.transitionTo('endpoints.index');
	        	return null;
			}

			for (var j = 0; j < controller.completeList.length; j++) {
				if (params.id_endpoint == controller.completeList[j].get('id_endpoint')) {
					return controller.completeList[j];
				}
			}
			return null;
		},
		setupController: function(controller, model) {
			controller.set('content', model);
			var childController = this.controllerFor('endpointconfig-' + model.get('detail_dialog'));
			childController.set('content', model);
		},
		afterModel: function(model, transition, queryparams) {
			return model ? Ember.RSVP.hash({
				'unassigned':	Ember.$.get('index.php', {
					menu:		module_name, 
					rawmode:	'yes',
					action:		'loadUnassignedAccounts'
				}).then(function (respuesta) {
					App.accountsController.setUnassignedAccounts(respuesta);
				}),
				'details'	:	Ember.$.get('index.php', {
					menu:		module_name, 
					rawmode:	'yes',
					action:		model.get('detail_dialog') + '_loadDetails',
					id_endpoint:model.get('id_endpoint')
				}).then (function(respuesta) {
					model.setDetails(respuesta);
				})
			}) : null;
		},
		serialize: function(model, parameters) {
			return { id_endpoint: model.get('id_endpoint') };
		},
		renderTemplate: function(controller, model) {
			this.render({ into: 'endpoints' });
			this.render('endpointconfig-' + model.get('detail_dialog'), {
				into: 'endpoints/endpointconfig'
			});
		}
	});

	App.EndpointsGetconfiglogRoute = Ember.Route.extend({
		model: function (params) {
			return Ember.$.get('index.php', {
				menu:		module_name, 
				rawmode:	'yes',
				action:		'getConfigLog'
			}).then(function(respuesta) {
				if (respuesta.status == 'error') {
					mostrar_mensaje_error(respuesta.message);
					return null;
				}
				return respuesta;
			});
		},
		renderTemplate: function(controller, model) {
			this.render({ into: 'endpoints'});
		}
	});

	App.EndpointsEndpointconfigView = JQ.Dialog.extend({
	//App.EndpointsEndpointconfigView = Ember.View.extend({
		attributeBindings: ['title'],
		title: function() {
			return arrLang_main['TITLE_ENDPOINT_CONFIG'] + ' ' + this.get('controller.last_known_ipv4');
		}.property('controller.last_known_ipv4'),
		width: 900,
		height: 575,
		modal: true,
		//autoOpen: true,
		forceroot: '#endpointConfigApplication',
        buttons: [
            {
                text: arrLang_main['LBL_APPLY'],
                click: function() {
                	var thisview = Ember.View.views[$(this).attr('id')];
                	var endpoint = thisview.get('controller.content');
                	if (endpoint.saveDetails()) {
                		$(this).dialog('close');
                	}
                }
            },
            {
                text: arrLang_main['LBL_DISMISS'],
                click: function() { $(this).dialog('close');  }
            }
        ],
        close: function (event, ui) {
        	var router = this.get('controller.target.router');
        	router.transitionTo('endpoints.index');
        }
	});
	
	App.EndpointsGetconfiglogView = JQ.Dialog.extend({
		title: arrLang_main['TITLE_LASTLOG'],
		width: 1024,
		height: 400,
		modal: true,
		//autoOpen: true,
		forceroot: '#endpointConfigApplication',
		buttons: [
            {
                text: arrLang_main['LBL_DISMISS'],
                click: function() { $(this).dialog('close');  }
            }
		],
        close: function (event, ui) {
        	var router = this.get('controller.target.router');
        	router.transitionTo('endpoints.index');
        }
	});
	App.FullTextArea = Ember.TextArea.extend({
		attributeBindings: ['style'],
		style:  "width: 100%; height: 100%; box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; font-family: \"Courier New\", Courier, monospace;"
	});
	App.SubMenuView = Ember.View.extend({
		attributeBindings: ['style'],
		style: 'float: left; position: relative; display: inline-block;',

		menuEnabled: false,
		menuEnabledManual: false,
		menuStyle: function() {
			return this.get('menuEnabled') ? '' : 'display: none';
		}.property('menuEnabled'),
		menuStyleManual: function() {
			return this.get('menuEnabledManual') ? '' : 'display: none';
		}.property('menuEnabledManual'),
		
		toggleMenu: function() {
			this.set('menuEnabled', !this.get('menuEnabled'));
			this.set('menuEnabledManual', !this.get('menuEnabledManual'));
		},
		mouseLeave: function() {
			this.set('menuEnabled', false);
		}
	});
	App.EndpointUploadView = JQ.LiteUploader.extend({
		name: 'endpointfile',
		script: '?menu=' + module_name + '&rawmode=yes&action=upload',
		before: function() {
			this.get('controller')
				.set('uploadInProgress', true)
				.set('completedupload', 0);
			return true;
		},
		progress: function(percent) {
			this.get('controller')
				.set('completedupload', percent);
		},
		success: function(respuesta) {
			var controller = this.get('controller');
			controller
				.set('uploadInProgress', false)
				.set('completedupload', 0);
			if (respuesta.status == 'error') {
				mostrar_mensaje_error(respuesta.message);
				return;
			}
			controller.manejarRespuestaStatus(respuesta);
		},
		fail: function(jqXHR) {
			this.get('controller')
			.set('uploadInProgress', false)
			.set('completedupload', 0);
		}
	});

	// Iniciar todos los diálogos de endpoints especiales
	for (var k in endpointConfigDlgInit) {
		endpointConfigDlgInit[k]();
	}
});

function initializeJQueryUIViews()
{
	// Put jQuery UI inside its own namespace
	JQ = {};

	// Create a new mixin for jQuery UI widgets using the Ember
	// mixin syntax.
	JQ.Widget = Em.Mixin.create({
	  // When Ember creates the view's DOM element, it will call this
	  // method.
	  didInsertElement: function() {
		// Make jQuery UI options available as Ember properties
	    var options = this._gatherOptions();

	    // Make sure that jQuery UI events trigger methods on this view.
	    this._gatherEvents(options);

	    // Create a new instance of the jQuery UI widget based on its `uiType`
	    // and the current element.
	    //var ui = jQuery.ui[this.get('uiType')](options, this.get('element'));
	    var uiType = this.get('uiType');
	    var ui = this.$();
	    if (typeof uiType == 'object') {
	    	for (var i = 0; i < uiType.length; i++)
	    		ui = ui[uiType[i]](options);
	    } else {
	    	//ui = this.$()[uiType](options);
	    	ui = ui[uiType](options);
	    }

	    // Save off the instance of the jQuery UI widget as the `ui` property
	    // on this Ember view.
	    this.set('ui', ui);
	  },

	  // When Ember tears down the view's DOM element, it will call
	  // this method.
	  willDestroyElement: function() {
		var ui = this.get('ui');
	    if (ui) {
	      // Tear down any observers that were created to make jQuery UI
	      // options available as Ember properties.
	      var observers = this._observers;
	      for (var prop in observers) {
	        if (observers.hasOwnProperty(prop)) {
	          this.removeObserver(prop, observers[prop]);
	        }
	      }
	      //ui._destroy();
		  var uiType = this.get('uiType');
          if (typeof uiType == 'object') {
		  	for (var i = 0; i < uiType.length; i++)
		  		ui = ui[uiType[i]]('destroy');
		  } else {
			  ui = ui[uiType]('destroy');
		  }
	    }
	  },

	  // Each jQuery UI widget has a series of options that can be configured.
	  // For instance, to disable a button, you call
	  // `button.options('disabled', true)` in jQuery UI. To make this compatible
	  // with Ember bindings, any time the Ember property for a
	  // given jQuery UI option changes, we update the jQuery UI widget.
	  _gatherOptions: function() {
	    var uiOptions = this.get('uiOptions'), options = {};

	    // The view can specify a list of jQuery UI options that should be treated
	    // as Ember properties.
	    if (uiOptions != null) uiOptions.forEach(function(key) {
	      options[key] = this.get(key);

	      // Set up an observer on the Ember property. When it changes,
	      // call jQuery UI's `setOption` method to reflect the property onto
	      // the jQuery UI widget.
	      var observer = function() {
	        var value = this.get(key);
	        var uiType = this.get('uiType');
	        this.get('ui')[uiType]('option', key, value);
	        //this.get('ui')._setOption(key, value);
	      };

	      this.addObserver(key, observer);

	      // Insert the observer in a Hash so we can remove it later.
	      this._observers = this._observers || {};
	      this._observers[key] = observer;
	    }, this);

	    return options;
	  },

	  // Each jQuery UI widget has a number of custom events that they can
	  // trigger. For instance, the progressbar widget triggers a `complete`
	  // event when the progress bar finishes. Make these events behave like
	  // normal Ember events. For instance, a subclass of JQ.ProgressBar
	  // could implement the `complete` method to be notified when the jQuery
	  // UI widget triggered the event.
	  _gatherEvents: function(options) {
	    var uiEvents = this.get('uiEvents') || [], self = this;

	    uiEvents.forEach(function(event) {
	      var callback = self[event];

	      if (callback) {
	        // You can register a handler for a jQuery UI event by passing
	        // it in along with the creation options. Update the options hash
	        // to include any event callbacks.
	        options[event] = function(event, ui) { return callback.call(self, event, ui); };
	      }
	    });
	  }
	});

	// Create a new Ember view for the jQuery UI Button widget
	JQ.Button = Em.View.extend(JQ.Widget, {
	  uiType: 'button',
	  uiOptions: ['label', 'disabled'],

	  tagName: 'button'
	});
	
	// Create a new Ember view for the jQuery UI Buttonset widget
	JQ.ButtonSet = Em.View.extend(JQ.Widget, {
	  uiType: 'buttonset'
	  //uiOptions: ['label', 'disabled']
	});
	// Create a new Ember view for the jQuery UI Progress Bar widget
	JQ.ProgressBar = Em.View.extend(JQ.Widget, {
	  uiType: 'progressbar',
	  uiOptions: ['value', 'max'],
	  uiEvents: ['change', 'complete']
	});

	JQ.Dialog = Em.View.extend(JQ.Widget, {
	    uiType: 'dialog',
	    uiOptions: 'autoOpen height width modal buttons title'.w(),
	    uiEvents: 'beforeClose close create drag dragStart dragStop focus open resize resizeStart resizeStop'.w(),
	    
	    autoOpen: true,

	    dialogopen: function() {
	        this.get('ui').dialog('open');
	    },
	    dialogclose: function() {
	        this.get('ui').dialog('close');
	    },
	    didInsertElement: function() {
			var appdiv = this.get('forceroot');

			// These nodes are the metamorph tags that delimit the outlet
			var prevbefore = this.$().prev();
			var nextbefore = this.$().next();
			
	    	this._super();
		    
		    /* Some widgets, notably Dialog, uproot the target node from its current
		     * position, and paste it at the end of the document. Since Ember.js will
		     * only handle events inside its own rootElement, this behavior results
		     * in broken bindings unless corrected here. */
		    if (appdiv != null) {
		    	/*
		    	this.$().parent().insertBefore(prevbefore);
		    	this.$().before(prevbefore);
		    	this.$().after(nextbefore);
		    	*/
		    	
		    	/* Stick the entire div and its parent decoration inside the 
		    	 * metamorph tags. At destroy time, this will remove all 
		    	 * decoration inside the tags. */
		    	this.$().parent().insertAfter(prevbefore);
		    }
	    }
	});
	
	JQ.Tabs = Em.View.extend(JQ.Widget, {
		uiType: 'tabs',
		uiEvents: 'add create disable enable load remove select show'.w(),
		uiOptions: 'ajaxOptions cache collapsible cookie disabled event fx idPrefix panelTemplate selected spinner tabTemplate'.w()
	});
	
	JQ.LiteUploader = Em.View.extend({
		tagName: 'input',
		attributeBindings: ['type', 'name'],
		type: 'file',
		didInsertElement: function() {
			var options = {};
			//var uiEvents = 'before each progress success fail'.w();
			var uiOptions = 'script allowedFileTypes maxSizeInBytes customParams'.w();
			var self = this;
			
			uiOptions.forEach(function(key) {
				if (this.get(key) != null) options[key] = this.get(key);
			}, this);
			if (self.get('before') != null) options['before'] = function() {
				var f = self.get('before');
				return f.call(self);
			}
			if (self.get('each') != null) options['each'] = function(file, errors) {
				var f = self.get('each');
				return f.call(self, file, errors);
			}
			if (self.get('progress') != null) options['progress'] = function(percentage) {
				var f = self.get('progress');
				return f.call(self, percentage);
			}
			if (self.get('success') != null) options['success'] = function(response) {
				var f = self.get('success');
				return f.call(self, response);
			}
			if (self.get('fail') != null) options['fail'] = function(jqXHR) {
				var f = self.get('fail');
				return f.call(self, jqXHR);
			}
			this.$().liteUploader(options);
		}
	});
}

function mostrar_mensaje_error(s)
{
	$('#elastix-module-error-message-text').text(s);
	$('#elastix-module-error-message').show('slow', 'linear', function() {
		setTimeout(function() {
			$('#elastix-module-error-message').fadeOut();
		}, 10000);
	});
}

function mostrar_mensaje_info(s)
{
	$('#elastix-module-info-message-text').text(s);
	$('#elastix-module-info-message').show('slow', 'linear', function() {
		setTimeout(function() {
			$('#elastix-module-info-message').fadeOut();
		}, 10000);
	});
}

function mostrar_mensaje_error_dialog(s)
{
	$('#elastix-module-error-message-text-dialog').text(s);
	$('#elastix-module-error-message-dialog').show('slow', 'linear', function() {
		setTimeout(function() {
			$('#elastix-module-error-message-dialog').fadeOut();
		}, 10000);
	});
}
