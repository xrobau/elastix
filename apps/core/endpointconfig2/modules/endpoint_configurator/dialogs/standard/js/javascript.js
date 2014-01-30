endpointConfigDlgInit['standard'] = function() {
	App.EndpointconfigStandardView = JQ.Tabs.extend({
	});
	App.EndpointconfigStandardController = Ember.ObjectController.extend({
		// Network configuration
		isDHCP: function() {
			var dhcp = this.get('details.dhcp');
			return (dhcp != null) ? dhcp : false;
		}.property('details.dhcp'),
		isStatic: function() {
			var dhcp = this.get('details.dhcp');
			return (dhcp != null) ? !dhcp : true;
		}.property('details.dhcp'),
		setDHCP: function() {
			this.set('details.dhcp', true);
		},
		setStatic: function() {
			this.set('details.dhcp', false);
		}
	});
	
	App.NetworkTypeView = JQ.ButtonSet.extend({
		init: function() {
			this._super();
			/* Force dhcp to be requested as null so change to defined value is
			 * noted in observer. */
			this.get('controller.model.details.dhcp');
		},
		reapply: function() {
			// Required to apply the changes after property is updated
			Ember.run.scheduleOnce('afterRender', this, function() {
				this.get('ui').buttonset('refresh');
			});
		}.observes('controller.model.details.dhcp')
	});
	
	App.EndpointconfigPropertylistView = Ember.View.extend({
		templateName: 'endpointconfig-propertylist'
	});
	App.DeletePropertyButtonView = JQ.Button.extend({
		click: function(a, b) {
			this.get('controller').send('deleteProperty', this.get('propkey'));
		}
	});
	App.AddPropertyButtonView = JQ.Button.extend({
		click: function(a, b) {
			this.get('controller').send('addProperty');
		}
	});
	App.EndpointconfigPropertylistController = Ember.ArrayController.extend({
		tempKey		:	'',	// Clave se ingresa aquí hasta que se apruebe
		tempValue	:	'',	// Valor se ingresa aquí hasta que se apruebe
		blacklist	:	[],	// Nombres de propiedades estándar
		deleteProperty: function(propkey) {
			for (var i = 0; i < this.get('length'); i++) {
				if (propkey == this.objectAt(i).key) {
					this.removeAt(i);
					break;
				}
			}
		},
		addProperty: function() {
			var newobj = {'key' : this.get('tempKey'), 'value' : this.get('tempValue')};

			if (newobj.key != '') {
				if (this.get('blacklist').indexOf(newobj.key) != -1) {
					// TODO: i18n
					alert("Conflict with reserved property!");
					return;
				}
				this.set('tempKey', '');
				this.set('tempValue', '');
				for (var i = 0; i < this.get('length'); i++) {
					if (newobj.key == this.objectAt(i).key) {
						this.removeAt(i)
						this.insertAt(i, newobj);
						return;
					}					
				}
				this.addObject(newobj);
			}
		}
	});
	
	App.DragDropAccountsView = Ember.View.extend(JQ.Widget, {
		uiType: 'sortable',
		uiOptions: ['connectWith'],
		tagName: 'ul',
		uiEvents: ['receive', 'start', 'stop', 'beforeStop', 'sort', 'update'],
		classNames: ['dragdropaccountsview'],
		
		connectWith: '.dragdropaccountsview',
		
		getInsertPos: function(item) {
			var dstController = this.get('controller');
			var insertpos = null;
			var place_id = item.nextAll('li').first().attr('id');
			
			if (place_id != null) {
				for (var i = 0; i < dstController.get('length'); i++) {
					if (dstController.objectAt(i).get('idattr') == place_id) {
						insertpos = i;
						break;
					}
				}
			}
			return insertpos;
		}
	});

	App.StandardUnboundAccountsView =  App.DragDropAccountsView.extend({
		attributeBindings: ['style'],
		style: 'height: 360px;',
		update: function (event, ui) {
			var sel = '#' + ui.item.attr('id');
			var li_set = this.get('ui').children(sel);
			
			/* El evento update se ejecuta para todo cambio de DOM en la lista,
			 * pero el item insertado es localizable sólo cuando se arrastra
			 * desde la misma lista.
			 * 
			 * Para la lista de cuentas sin asignar, se cancelan los intentos
			 * de reordenar, ya que no tienen significado alguno.
			 */
			if (li_set.length > 0) this.get('ui').sortable('cancel');
		},
		receive: function (event, ui) {
			//console.debug('App.StandardUnboundAccountsView.receive()');
			var dropped_id = ui.item.attr('id');
			var dstController = this.get('controller');
			
			// Localizar y quitar el objeto account del controlador del endpoint
			var srcController = this.get('parentView.controller.details.accountsController');
			if (srcController == null) {
				//console.error('BUG: failed to fetch controller for bound accounts!');
				ui.sender.sortable('cancel');
				return;
			}
			
			/* El orden de operaciones es importante. Primero hay que calcular
			 * las posiciones fuente y destino de la cuenta a mover, SIN
			 * MODIFICAR LOS CONTROLADORES. Luego de aprobar el movimiento
			 * de la cuenta, y sabiendo la posición de inserción, se cancela
			 * el drag, y a continuación se modifican los controladores para
			 * que estos sean los que modifiquen el UI. 
			 */
			var account = null;
			var accPosition = null;
			for (var i = 0; i < srcController.get('length'); i++) {
				if (srcController.objectAt(i).get('idattr') == dropped_id) {
					account = srcController.objectAt(i);
					accPosition = i;
					break;
				}
			}
			if (account == null) {
				console.error('BUG: failed to find account for ' + dropped_id + ' in controller!');
				ui.sender.sortable('cancel');
				return;
			}
			account.set('id_account', null);
			account.set('priority', null);
			
			// Agregar el account en la posición indicada por el drop
			var insertpos = this.getInsertPos(ui.item);

			ui.sender.sortable('cancel');			

			srcController.removeAt(accPosition);
			if (insertpos != null)
				dstController.insertAt(insertpos, account);
			else dstController.addObject(account);
			srcController.set('selectedAccount', null);

			// Reordenar las prioridades según el orden indicado
			for (var i = 0; i < srcController.get('length'); i++) {
				srcController.objectAt(i).set('priority', i + 1);
			}
		}
	});
	
	App.StandardBoundAccountsView =  App.DragDropAccountsView.extend({
		attributeBindings: ['style'],
		style: 'height: 180px;',		
		update: function (event, ui) {
			/*
			console.debug('App.StandardBoundAccountsView.update()');
			console.debug(ui.item.attr('id'));
			var sel = '#' + ui.item.attr('id');
			console.debug(this.get('ui').children(sel));
			*/
			var sel = '#' + ui.item.attr('id');
			var li_set = this.get('ui').children(sel);
			
			/* El evento update se ejecuta para todo cambio de DOM en la lista,
			 * pero el item insertado es localizable sólo cuando se arrastra
			 * desde la misma lista. 
			 *
			 * Para la lista de cuentas asignadas, se debe buscar la posición
			 * adecuada en la lista para el cambio de posición. Luego se cancela
			 * el movimiento por ratón, y se repite el movimiento a través de
			 * los controladores.
			 */
			if (li_set.length > 0) {
				var controller = this.get('controller');
				
				//console.debug(li_set);
				//console.debug(li_set.nextAll('li').first());
				
				// Véase comentario de orden de operaciones en receive()
				var dropped_id = ui.item.attr('id');
				var removepos = null;
				var insertpos = this.getInsertPos(li_set);
				var account = null;
				for (var i = 0; i < controller.get('length'); i++) {
					if (controller.objectAt(i).get('idattr') == dropped_id) {
						account = controller.objectAt(i);
						removepos = i;
						break;
					}
				}

				this.get('ui').sortable('cancel');
				
				if (removepos == null) {
					console.error('BUG: failed to find account for ' + dropped_id + ' in controller!');
					return;
				}
				controller.removeAt(removepos);
				if (insertpos == null) {
					controller.addObject(account);
				} else {
					if (removepos < insertpos) insertpos--;
					controller.insertAt(insertpos, account);
				}
				
				// Reordenar las prioridades según el orden indicado
				for (var i = 0; i < controller.get('length'); i++) {
					controller.objectAt(i).set('priority', i + 1);
				}
			}
		},
		receive: function (event, ui) {
			//console.debug('App.StandardBoundAccountsView.receive()');
			//console.debug(ui);
			
			var dropped_id = ui.item.attr('id');
			var dstController = this.get('controller');
			
			// Según el ID se obtiene el controlador del cual quitar el item
			var srcController = App.accountsController;

			/* El orden de operaciones es importante. Primero hay que calcular
			 * las posiciones fuente y destino de la cuenta a mover, SIN
			 * MODIFICAR LOS CONTROLADORES. Luego de aprobar el movimiento
			 * de la cuenta, y sabiendo la posición de inserción, se cancela
			 * el drag, y a continuación se modifican los controladores para
			 * que estos sean los que modifiquen el UI. 
			 */

			var account = null;
			var accPosition = null;
			for (var i = 0; i < srcController.get('length'); i++) {
				if (srcController.objectAt(i).get('idattr') == dropped_id) {
					account = srcController.objectAt(i);
					accPosition = i;
					break;
				}
			}
			if (account == null) {
				console.error('BUG: failed to find account for ' + dropped_id + ' in controller!');
				ui.sender.sortable('cancel');
				return;
			}

			if (!this.allowDropOperation(account)) {
				ui.sender.sortable('cancel');
				return;
			}					
			
			account.set('id_account', -1);

			// Agregar el account en la posición indicada por el drop
			var insertpos = this.getInsertPos(ui.item);
			
			ui.sender.sortable('cancel');			

			srcController.removeAt(accPosition);
			if (insertpos != null)
				dstController.insertAt(insertpos, account);
			else dstController.addObject(account);
			
			// Reordenar las prioridades según el orden indicado
			for (var i = 0; i < dstController.get('length'); i++) {
				dstController.objectAt(i).set('priority', i + 1);
			}
		},
		allowDropOperation: function(account) {
			var dstController = this.get('controller');
			var endpointController = this.get('parentView.controller');
			
			// Reordenamiento siempre se acepta
			if (account.get('id_account') != null) return true;
			
			// No se pueden agregar cuentas hasta saber el modelo a programar
			if (endpointController.get('id_model') == 'unknown') {
				//console.debug('Rechazo: no se conoce modelo de teléfono');
				return false;
			}
			
			// El límite máximo de cuentas a agregar según el modelo
			if (dstController.get('length') >= endpointController.get('modelObj.max_accounts')) {
				//console.debug('Rechazo: se excede número total de cuentas');
				return false;
			}
			
			// Preguntar según la tecnología el máximo de cuentas
			var querypath = null;
			if (account.get('tech') == 'sip') querypath = 'details.max_sip_accounts';
			if (account.get('tech') == 'iax2') querypath = 'details.max_iax2_accounts';
			if (querypath == null) {
				//console.error('BUG: account has unimplemented tech');
				return false;
			}
			var max_tech = endpointController.get(querypath);
			if (max_tech == null) {
				//console.error('BUG: controller failed to produce value for ' + querypath);
				return false;
			}
			
			// Contar el número de cuentas con la tecnología a insertar
			var curr_tech = 0;
			for (var i = 0; i < dstController.get('length'); i++) {
				if (dstController.objectAt(i).get('tech') == account.get('tech'))
					curr_tech++;
			}
			//console.info('curr_tech = ' + curr_tech + ' max_tech = ' + max_tech);
			return (curr_tech < max_tech);
		}
	});

	App.StandardBoundAccountsController = Ember.ArrayController.extend({
		content: [],
		selectedAccount: null,
		selectAccount: function(account) {
			account.set('propertiesController', App.EndpointconfigPropertylistController.create({
				content: account.get('properties')
			}));
			this.set('selectedAccount', account);
		}
	});
	
	App.detailClass['standard'] = Ember.Object.extend({
		max_sip_accounts:	1,
		max_iax2_accounts:	0,
		http_username:		null,
		http_password:		null,
		telnet_username:	null,
		telnet_password:	null,
		ssh_username:		null,
		ssh_password:		null,
		dhcp:				true,
		static_ip:			null,
		static_gw:			null,
		static_mask:		null,
		static_dns1:		null,
		static_dns2:		null,
		endpoint_properties:null,
		endpoint_account:	null,
		
		endpointPropertiesController: null,
		accountsController: null,

		stdprops: ['max_sip_accounts', 'max_iax2_accounts', 'http_username',
		            'http_password', 'telnet_username', 'telnet_password',
		            'ssh_username', 'ssh_password', 'dhcp', 'static_ip',
		            'static_gw', 'static_mask', 'static_dns1', 'static_dns2'],

		init: function() {
			if (this.get('endpoint_properties') == null)
				this.set('endpoint_properties', new Array());
			if (this.get('endpoint_account') == null)
				this.set('endpoint_account', new Array());
			
			//console.debug('details.init');
			this.set('endpointPropertiesController', App.EndpointconfigPropertylistController.create({
				content: this.get('endpoint_properties'),
				blacklist: this.stdprops
			}));
			var ea = this.get('endpoint_account');
			for (var i = 0; i < ea.length; i++)
				ea[i] = App.Account.create(ea[i]);
			this.set('accountsController', App.StandardBoundAccountsController.create({
				content: this.get('endpoint_account')
			}));
		},
		getjson: function () {
			var j = {};
			
			// Copy standard scalar properties
			for (var i = 0; i < this.stdprops.length; i++) {
				var k = this.stdprops[i];
				j[k] = this.get(k);
			}
			j['endpoint_properties'] = this.get('endpoint_properties');
			
			// Copy account properties information
			j['endpoint_account'] = [];
			for (var i = 0; i < this.endpoint_account.length; i++) {
				j['endpoint_account'].addObject({
					tech: this.endpoint_account[i].tech,
					account: this.endpoint_account[i].account,
					priority: this.endpoint_account[i].priority,
					properties: this.endpoint_account[i].properties
				});
			}
			
			return j;
		},
		numaccounts: function () {
			return this.endpoint_account.length;
		}
	});
	
}