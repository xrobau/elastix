var module_name = 'campaign_monitoring';
var App = null;

// Objeto de POST largo
var longPoll = null;

//Objeto EventSource, si está soportado por el navegador
var evtSource = null;

// Timer para refresco de estado de cambio reciente de llamadas y agentes
var timerReciente = null;

$(document).ready(function() {
	$('#elastix-callcenter-error-message').hide();
	
	// Inicialización de Ember.js
	App = Ember.Application.create({
		rootElement:	'#campaignMonitoringApplication'
	});
	App.campaniasDisponibles = Ember.ArrayController.create({
		content:[
		// Ember.Object.create({id_campaign: 1, desc_campaign: "Campaña de gatos"}),
		],
		key_campaign: null
	});
	App.campaniasDisponibles.addObserver('key_campaign', do_loadCampaign);
	App.StatLlamadas = Ember.Object.extend({
		// Estados en común para todas las campañas
		total:		0,
		encola:		0,
		conectadas:	0,
		abandonadas:0,
		max_duration:0,
		total_sec:  0,
		fmttime: function(p) {
			var tiempo = [0, 0, 0];
			tiempo[0] = p;
			tiempo[1] = (tiempo[0] - (tiempo[0] % 60)) / 60;
			tiempo[0] %= 60;
			tiempo[2] = (tiempo[1] - (tiempo[1] % 60)) / 60;
			tiempo[1] %= 60;
			var i = 0;
			for (i = 0; i < 3; i++) { if (tiempo[i] <= 9) tiempo[i] = "0" + tiempo[i]; }
			return tiempo[2] + ':' + tiempo[1] + ':' + tiempo[0];
		},
		fmtpromedio: function() {
			var p, s;
			if (this.get('terminadas') > 0)
				p = this.get('total_sec') / this.get('terminadas');
			else if (this.get('conectadas') > 0)
				p = this.get('total_sec') / this.get('conectadas');
			else p = 0;
			p = Math.round(p);
			return this.fmttime(p);
			
		}.property('total_sec', 'terminadas', 'conectadas'),
		fmtmaxduration: function() {
			return this.fmttime(this.get('max_duration'));
		}.property('max_duration'),

		// Estados válidos sólo para campañas salientes
		pendientes:	0,
		marcando:	0,
		timbrando:	0,
		fallidas:	0,
		nocontesta: 0,
		cortas:		0,
		
		// Estados válidos sólo para campañas entrantes
		terminadas: 0,
		sinrastro:	0
	});
	App.campaniaActual = Ember.Object.create({
		estadoClienteHash: null,		
		outgoing:		false,
		fechaInicio:	'...',
		fechaFinal:		'...',
		horaInicio:		'...',
		horaFinal:		'...',
		cola:			'...',
		maxIntentos:	'...',
		llamadas:       App.StatLlamadas.create(),
		llamadasMarcando:	[
			/*
				Ember.Object.create({
					callid: 875,
					numero: '11111',
					troncal: 'SIP/gato',
					estado: 'Dialing',
					desde: '00:01:02',
					rtime: new Date(),
					reciente: true})
			*/
		],
		agentes:	[
			/*
				Ember.Object.create({
					canal: 'Agent/9000',
					estado: 'No logon',
					numero: '???',
					troncal: 'SIP/gato',
					desde: '00:01:02',
					rtime: new Date(),
					reciente: true})
			*/
		],
		registroVisible: false,
		registro:	[
		    // No es necesario Ember.Object porque no se espera modificar los valores
			//{timestamp: '10:59:00', mensaje: 'Esta es una prueba'}
		],
		alturaLlamada: function() {
			return this.get('registroVisible') ? 'height: 180px;' : 'height: 400px;';
		}.property('registroVisible'),
		cargarPrevios: function() {
			var beforeid = (this.registro.length > 0) ? this.registro[0].id : null; 
			
			var key_campaign = App.campaniasDisponibles.get('key_campaign');
			if (key_campaign != null) {
				for (i = 0; i < App.campaniasDisponibles.content.length; i++) {
					if (App.campaniasDisponibles.content[i].get('key_campaign') == key_campaign) {
						var campaign_type = App.campaniasDisponibles.content[i].get('type');
						var campaign_id = App.campaniasDisponibles.content[i].get('id_campaign');

						//alert('cargarPrevios campaign_type='+campaign_type+' campaign_id='+campaign_id+' beforeid='+beforeid);

						$.post('index.php?menu=' + module_name + '&rawmode=yes', {
							menu:			module_name, 
							rawmode:		'yes',
							action:			'loadPreviousLogEntries',
							campaigntype:	campaign_type,
							campaignid:		campaign_id,
							beforeid:		beforeid
						},
						function(respuesta) {
							if (respuesta.status == 'error') {
								mostrar_mensaje_error(respuesta.message);
							} else {
								for (var i = respuesta.log.length - 1; i >= 0; i--) {
									var registro = respuesta.log[i];
									App.campaniaActual.registro.insertAt(0, {
										id:			registro.id,
										timestamp:	registro.timestamp,
										mensaje: 	registro.mensaje
									});
								}
							}
						});
					}
				}
			}

		}
	});
	App.ApplicationView = Ember.View.extend({
		templateName:	'campaignMonitoringView'
		
	});
	App.RegistroView = Ember.View.extend({
		didInsertElement: function() {
			this.scroll();
		},
		
		registroChanged: function() {
			var s = this;
			Ember.run.next(function() { s.scroll(); });
		}.observes('App.campaniaActual.registro.@each'),
		
		scroll: function() {
			// Forzar a mostrar el último registro
			var r = this.$();
			r.scrollTop(r.prop('scrollHeight'));
		}
	});

	App.Router = Ember.Router.extend({
		root: Ember.Route.extend({
			index: Ember.Route.extend({
				route: '/'
			})
		})
	});
	App.initialize();
	
	// Iniciar llenado de campañas
	do_getCampaigns();
	timerReciente = setInterval(do_actualizarReciente, 500);
});

$(window).unload(function() {
	if (timerReciente != null) {
		clearInterval(timerReciente);
		timerReciente = null;
	}
	if (evtSource != null) {
		evtSource.close();
		evtSource = null;
	}
	if (longPoll != null) {
		longPoll.abort();
		longPoll = null;
	}
});

function do_getCampaigns()
{
	$.post('index.php?menu=' + module_name + '&rawmode=yes', {
		menu:		module_name, 
		rawmode:	'yes',
		action:		'getCampaigns'
	},
	function(respuesta) {
		App.campaniasDisponibles.clear();
		if (respuesta.status == 'error') {
			mostrar_mensaje_error(respuesta.message);
		} else {
			for (i = 0; i < respuesta.campaigns.length; i++) {
				cp = respuesta.campaigns[i];
				App.campaniasDisponibles.addObject(Ember.Object.create({
					id_campaign:	cp.id_campaign,
					desc_campaign:	cp.desc_campaign,
					type:			cp.type,
					status:			cp.status,
					key_campaign:	cp.type + '-' + cp.id_campaign
				}));
			}
			App.campaniasDisponibles.set('key_campaign',
				App.campaniasDisponibles.content[0].get('key_campaign'));
		}
	});
}

function do_loadCampaign()
{
	// Cancelar Server Sent Events de campaña anterior
	if (evtSource != null) {
		evtSource.onmessage = function(event) {
			console.warn("This evtSource was closed but still receives messages!");
		}
		evtSource.close();
		evtSource = null;
	}
	if (longPoll != null) {
		longPoll.abort();
		longPoll = null;
	}

	var key_campaign = App.campaniasDisponibles.get('key_campaign');
	if (key_campaign != null) {
		for (i = 0; i < App.campaniasDisponibles.content.length; i++) {
			if (App.campaniasDisponibles.content[i].get('key_campaign') == key_campaign) {
				var campaign_type = App.campaniasDisponibles.content[i].get('type');
				var campaign_id = App.campaniasDisponibles.content[i].get('id_campaign');
				
				$.post('index.php?menu=' + module_name + '&rawmode=yes', {
					menu:			module_name, 
					rawmode:		'yes',
					action:			'getCampaignDetail',
					campaigntype:	campaign_type,
					campaignid:		campaign_id
				},
				function(respuesta) {
					if (respuesta.status == 'error') {
						mostrar_mensaje_error(respuesta.message);
					} else {
						App.campaniaActual.set('outgoing', (campaign_type == 'outgoing'));
						
						// Información básica de la campaña
						App.campaniaActual.set('fechaInicio',	respuesta.campaigndata.startdate);
						App.campaniaActual.set('fechaFinal',	respuesta.campaigndata.enddate);
						App.campaniaActual.set('horaInicio',	respuesta.campaigndata.working_time_starttime);
						App.campaniaActual.set('horaFinal',		respuesta.campaigndata.working_time_endtime);
						App.campaniaActual.set('cola',			respuesta.campaigndata.queue);
						App.campaniaActual.set('maxIntentos',	respuesta.campaigndata.retries);
						
						App.campaniaActual.llamadasMarcando.clear();
						App.campaniaActual.agentes.clear();
						App.campaniaActual.registro.clear();

						App.campaniaActual.llamadas.set('terminadas',	0);
						App.campaniaActual.llamadas.set('sinrastro',	0);
						App.campaniaActual.llamadas.set('pendientes',	0);
						App.campaniaActual.llamadas.set('fallidas',		0);
						App.campaniaActual.llamadas.set('cortas',		0);
						App.campaniaActual.llamadas.set('marcando',		0);
						App.campaniaActual.llamadas.set('timbrando',	0);
						App.campaniaActual.llamadas.set('nocontesta',	0);

						manejarRespuestaStatus(respuesta);

						// Lanzar el callback que actualiza el estado de la llamada
					    setTimeout(do_checkstatus, 1);
					}
				});
			}
		}
	}
}

function do_checkstatus()
{
	var params = {
			menu:		module_name, 
			rawmode:	'yes',
			action:		'checkStatus',
			clientstatehash: App.campaniaActual.get('estadoClienteHash')
		};

	if (window.EventSource) {
		params['serverevents'] = true;
		evtSource = new EventSource('index.php?' + $.param(params));
		evtSource.onmessage = function(event) {
			manejarRespuestaStatus($.parseJSON(event.data));
		}
	} else {
		longPoll = $.post('index.php?menu=' + module_name + '&rawmode=yes', params,
		function (respuesta) {
			if (manejarRespuestaStatus(respuesta)) {
				// Lanzar el método de inmediato
				setTimeout(do_checkstatus, 1);
			}
		});
	}
}

function manejarRespuestaStatus(respuesta)
{
	// Intentar recargar la página en caso de error
	if (respuesta.error != null) {
		window.alert(respuesta.error);
		location.reload();
		return false;
	}

	// Verificar el hash del estado del cliente
	if (respuesta.estadoClienteHash == 'invalidated') {
		// Espera ha sido invalidada por cambio de campaña a monitorear
		return false;
	}
	if (respuesta.estadoClienteHash == 'mismatch') {
		/* Ha ocurrido un error y se ha perdido sincronía. Si el hash que 
		 * recibió es distinto a App.campaniaActual.get('estadoClienteHash') 
		 * entonces esta es una petición vieja. Si es idéntico debe de recargase
		 * la página.
		 */
		if (respuesta.hashRecibido == App.campaniaActual.get('estadoClienteHash')) {
			// Realmente se ha perdido sincronía
			console.error("Lost synchronization with server, reloading page...");
			location.reload();
		} else {
			// Se ha recibido respuesta luego de que supuestamente se ha parado
			console.warn("Received mismatch from stale SSE session, ignoring...");
		}
		return false;
	}
	App.campaniaActual.set('estadoClienteHash', respuesta.estadoClienteHash);
	
	// Estado de los contadores de la campaña
	var mapStatusCount = {
		'total':		'total',
		'onqueue':		'encola',
		'success':		'conectadas',
		'abandoned':	'abandonadas',
		'pending':		'pendientes',
		'failure':		'fallidas',
		'shortcall':	'cortas',
		'placing':		'marcando',
		'ringing':		'timbrando',
		'noanswer':		'nocontesta',
		'finished':		'terminadas',
		'losttrack':	'sinrastro'
	};
	
	if (respuesta.statuscount != null && respuesta.statuscount.update != null)
	for (var k in respuesta.statuscount.update) {
		if (mapStatusCount[k] != null) App.campaniaActual.llamadas.set(
			mapStatusCount[k], respuesta.statuscount.update[k]);
	}
	
	// Lista de las llamadas activas sin agente asignado
	if (respuesta.activecalls != null && respuesta.activecalls.add != null)
	for (var i = 0; i < respuesta.activecalls.add.length; i++) {
		var llamada = respuesta.activecalls.add[i];
		App.campaniaActual.llamadasMarcando.addObject(Ember.Object.create({
			callid:		llamada.callid,
			numero:		llamada.callnumber,
			troncal:	llamada.trunk,
			estado:		llamada.callstatus,
			desde:		llamada.desde,
			rtime:		new Date(),
			reciente:	true
		}));
	}
	if (respuesta.activecalls != null && respuesta.activecalls.update != null)
	for (var i = 0; i < respuesta.activecalls.update.length; i++) {
		var llamada = respuesta.activecalls.update[i];
		for (var j = 0; j < App.campaniaActual.llamadasMarcando.length; j++) {
			if (App.campaniaActual.llamadasMarcando[j].get('callid') == llamada.callid) {
				App.campaniaActual.llamadasMarcando[j].set('numero', llamada.callnumber);
				App.campaniaActual.llamadasMarcando[j].set('troncal', llamada.trunk);
				App.campaniaActual.llamadasMarcando[j].set('estado', llamada.callstatus);
				App.campaniaActual.llamadasMarcando[j].set('desde', llamada.desde);
				App.campaniaActual.llamadasMarcando[j].set('rtime', new Date());
				App.campaniaActual.llamadasMarcando[j].set('reciente', true);
			}
		}
	}
	if (respuesta.activecalls != null && respuesta.activecalls.remove != null)
	for (var i = 0; i < respuesta.activecalls.remove.length; i++) {
		var callid = respuesta.activecalls.remove[i].callid;
		for (var j = 0; j < App.campaniaActual.llamadasMarcando.length; j++) {
			if (App.campaniaActual.llamadasMarcando[j].get('callid') == callid) {
				App.campaniaActual.llamadasMarcando.removeAt(j);
			}
		}
	}
	
	// Lista de los agentes que atienden llamada
	if (respuesta.agents != null && respuesta.agents.add != null)
	for (var i = 0; i < respuesta.agents.add.length; i++) {
		var agente = respuesta.agents.add[i];
		App.campaniaActual.agentes.addObject(Ember.Object.create({
			canal:		agente.agent,
			numero:		agente.callnumber,
			troncal:	agente.trunk,
			estado:		agente.status,
			desde:		agente.desde,
			rtime:		new Date(),
			reciente:	true
		}));
	}
	if (respuesta.agents != null && respuesta.agents.update != null)
	for (var i = 0; i < respuesta.agents.update.length; i++) {
		var agente = respuesta.agents.update[i];
		for (var j = 0; j < App.campaniaActual.agentes.length; j++) {
			if (App.campaniaActual.agentes[j].get('canal') == agente.agent) {
				App.campaniaActual.agentes[j].set('numero', agente.callnumber);
				App.campaniaActual.agentes[j].set('troncal', agente.trunk);
				App.campaniaActual.agentes[j].set('estado', agente.status);
				App.campaniaActual.agentes[j].set('desde', agente.desde);
				App.campaniaActual.agentes[j].set('rtime', new Date());
				App.campaniaActual.agentes[j].set('reciente', true);
			}
		}
	}
	if (respuesta.agents != null && respuesta.agents.remove != null)
	for (var i = 0; i < respuesta.agents.remove.length; i++) {
		var agentchannel = respuesta.agents.remove[i].agent;
		for (var j = 0; j < App.campaniaActual.agentes.length; j++) {
			if (App.campaniaActual.agentes[j].get('canal') == agentchannel) {
				App.campaniaActual.agentes.removeAt(j);
			}
		}
	}
	
	// Registro de los eventos de la llamada
	if (respuesta.log != null)
	for (var i = 0; i < respuesta.log.length; i++) {
		var registro = respuesta.log[i];
		App.campaniaActual.registro.addObject({
			id:			registro.id,	// <--- id puede ser null en caso de link/unlink
			timestamp:	registro.timestamp,
			mensaje: 	registro.mensaje
		});
	}
	
	// Estadísticas de la campaña
	if (respuesta.stats != null) {
		App.campaniaActual.llamadas.set('max_duration', respuesta.stats.update.max_duration);
		App.campaniaActual.llamadas.set('total_sec', respuesta.stats.update.total_sec);
	} else if (respuesta.duration != null) {
		var m = App.campaniaActual.llamadas.get('max_duration');
		if (m < respuesta.duration)
			App.campaniaActual.llamadas.set('max_duration', respuesta.duration);
		App.campaniaActual.llamadas.set('total_sec', App.campaniaActual.llamadas.get('total_sec') + respuesta.duration);
	}
	
	return true;
}

function do_actualizarReciente()
{
	var fechaDiff = new Date();
	var fechaInicio = null;
	
	for (var i = 0; i < App.campaniaActual.llamadasMarcando.length; i++) {
		if (App.campaniaActual.llamadasMarcando[i].get('reciente')) {
			fechaInicio = App.campaniaActual.llamadasMarcando[i].get('rtime');
			if (fechaDiff.getTime() - fechaInicio.getTime() > 2000) {
				App.campaniaActual.llamadasMarcando[i].set('reciente', false);
			}
		}
	}

	for (var i = 0; i < App.campaniaActual.agentes.length; i++) {
		if (App.campaniaActual.agentes[i].get('reciente')) {
			fechaInicio = App.campaniaActual.agentes[i].get('rtime');
			if (fechaDiff.getTime() - fechaInicio.getTime() > 2000) {
				App.campaniaActual.agentes[i].set('reciente', false);
			}
		}
	}
}

function mostrar_mensaje_error(s)
{
	$('#elastix-callcenter-error-message-text').text(s);
	$('#elastix-callcenter-error-message').show('slow', 'linear', function() {
		setTimeout(function() {
			$('#elastix-callcenter-error-message').fadeOut();
		}, 5000);
	});
}
