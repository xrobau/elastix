var module_name = 'campaign_monitoring';
var App = null;

//ENV = {FORCE_JQUERY: true};

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
	App.campaniaActual = Ember.Object.create({
		outgoing:		false,
		fechaInicio:	'...',
		fechaFinal:		'...',
		horaInicio:		'...',
		horaFinal:		'...',
		cola:			'...',
		maxIntentos:	'...',
		llamadas:		Ember.Object.create({
			// Estados en común para todas las campañas
			total:		0,
			encola:		0,
			conectadas:	0,
			abandonadas:0,

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
		}),
		llamadasMarcando:	[
			//Ember.Object.create({numero: '11111', troncal: 'SIP/gato', estado: 'Dialing', desde: '2012-12-19 00:01:02'})
		],
		agentes:	[
			//Ember.Object.create({canal: 'Agent/9000', estado: 'No logon', numero: '???', troncal: 'SIP/gato', desde: 'ayer'})
		],
		registro:	[
		    // No es necesario Ember.Object porque no se espera modificar los valores
			//{timestamp: '10:59:00', mensaje: 'Esta es una prueba'}
		]
	});
	App.ApplicationView = Ember.View.extend({
		templateName:	'campaignMonitoringView'
		
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
					key_campaign:	cp.type + '-' + cp.id_campaign,
				}));
			}
			App.campaniasDisponibles.set('key_campaign',
				App.campaniasDisponibles.content[0].get('key_campaign'));
		}
	});
}

function do_loadCampaign()
{
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
						
						// Estado de los contadores de la campaña
						App.campaniaActual.llamadas.set('total',		respuesta.update.statuscount.total);
						App.campaniaActual.llamadas.set('encola',		respuesta.update.statuscount.onqueue);
						App.campaniaActual.llamadas.set('conectadas',	respuesta.update.statuscount.success);
						App.campaniaActual.llamadas.set('abandonadas',	respuesta.update.statuscount.abandoned);
						if (campaign_type == 'outgoing') {
							App.campaniaActual.llamadas.set('pendientes',	respuesta.update.statuscount.pending);
							App.campaniaActual.llamadas.set('fallidas',		respuesta.update.statuscount.failure);
							App.campaniaActual.llamadas.set('cortas',		respuesta.update.statuscount.shortcall);
							App.campaniaActual.llamadas.set('marcando',		respuesta.update.statuscount.placing);
							App.campaniaActual.llamadas.set('timbrando',	respuesta.update.statuscount.ringing);
							App.campaniaActual.llamadas.set('nocontesta',	respuesta.update.statuscount.noanswer);
							App.campaniaActual.llamadas.set('terminadas',	0);
							App.campaniaActual.llamadas.set('sinrastro',	0);
						} else {
							App.campaniaActual.llamadas.set('pendientes',	0);
							App.campaniaActual.llamadas.set('fallidas',		0);
							App.campaniaActual.llamadas.set('cortas',		0);
							App.campaniaActual.llamadas.set('marcando',		0);
							App.campaniaActual.llamadas.set('timbrando',	0);
							App.campaniaActual.llamadas.set('nocontesta',	0);
							App.campaniaActual.llamadas.set('terminadas',	respuesta.update.statuscount.finished);
							App.campaniaActual.llamadas.set('sinrastro',	respuesta.update.statuscount.losttrack);
						}
						/*
						for (i = 0; i < respuesta.campaigns.length; i++) {
							cp = respuesta.campaigns[i];
							App.campaniasDisponibles.addObject(Ember.Object.create({
								id_campaign:	cp.id_campaign,
								desc_campaign:	cp.desc_campaign,
								type:			cp.type,
								status:			cp.status,
								key_campaign:	cp.type + '-' + cp.id_campaign,
							}));
						}
						App.campaniasDisponibles.set('key_campaign',
							App.campaniasDisponibles.content[0].get('key_campaign'));
						 */
					}
				});
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

