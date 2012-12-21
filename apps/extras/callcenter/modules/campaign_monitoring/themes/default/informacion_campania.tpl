{* Este DIV se usa para mostrar los mensajes de error *}
<div
    id="elastix-callcenter-error-message"
    class="ui-state-error ui-corner-all">
    <p>
        <span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
        <span id="elastix-callcenter-error-message-text"></span>
    </p>
</div>
<div id="campaignMonitoringApplication">
<script type="text/x-handlebars" data-template-name="campaignMonitoringView">
<table width="100%"><tr><td>
<table width="100%">
<tr>
<td colspan="2"><b>{$ETIQUETA_CAMPANIA}:</b></td>
<td colspan="2">{literal}{{view Ember.Select
    contentBinding="App.campaniasDisponibles"
    optionValuePath="content.key_campaign"
    optionLabelPath="content.desc_campaign"
    valueBinding="App.campaniasDisponibles.key_campaign" }}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_FECHA_INICIO}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.fechaInicio}}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_FECHA_FINAL}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.fechaFinal}}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_HORARIO}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.horaInicio}} - {{App.campaniaActual.horaFinal}}{/literal}</td>
</tr>
<tr>
<td><b>{$ETIQUETA_COLA}:</b></td>
<td>{literal}{{App.campaniaActual.cola}}{/literal}</td>
<td><b>{$ETIQUETA_INTENTOS}:</b></td>
<td>{literal}{{App.campaniaActual.maxIntentos}}{/literal}</td>
</tr>
</table>
</td>
<td>
<table>
<tr>
<td colspan="2"><b>{$ETIQUETA_TOTAL_LLAMADAS}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.total}}{/literal}</td>
</tr>
{literal}{{#if App.campaniaActual.outgoing }}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_PENDIENTES}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.pendientes}}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_MARCANDO}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.marcando}}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_TIMBRANDO}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.timbrando}}{/literal}</td>
</tr>
{literal}{{/if}}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_COLA}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.encola}}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_EXITO}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.conectadas}}{/literal}</td>
</tr>
{literal}{{#if App.campaniaActual.outgoing }}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_FALLIDAS}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.fallidas}}{/literal}</td>
</tr>
{literal}{{else}}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_TERMINADAS}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.terminadas}}{/literal}</td>
</tr>
{literal}{{/if}}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_ABANDONADAS}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.abandonadas}}{/literal}</td>
</tr>
{literal}{{#if App.campaniaActual.outgoing }}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_NOCONTESTA}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.nocontesta}}{/literal}</td>
</tr>
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_CORTAS}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.cortas}}{/literal}</td>
</tr>
{literal}{{else}}{/literal}
<tr>
<td colspan="2"><b>{$ETIQUETA_LLAMADAS_SINRASTRO}:</b></td>
<td colspan="2">{literal}{{App.campaniaActual.llamadas.sinrastro}}{/literal}</td>
</tr>
{literal}{{/if}}{/literal}
</table>
</td></tr></table>
<table width="100%"><tr><td>
<b>{$ETIQUETA_LLAMADAS_MARCANDO}:</b>
<div id="lista_llamadas_pendientes">
<table width="100%">
<thead>
<tr>
<th>{$ETIQUETA_ESTADO}</th>
<th>{$ETIQUETA_NUMERO_TELEFONO}</th>
<th>{$ETIQUETA_TRONCAL}</th>
<th>{$ETIQUETA_DESDE}</th>
</tr>
</thead>
{literal}{{#view tagName="tbody"}}
{{#each App.campaniaActual.llamadasMarcando}}
<tr>
<td>{{estado}}</td>
<td>{{numero}}</td>
<td>{{troncal}}</td>
<td>{{desde}}</td>
</tr>
{{/each}}
{{/view}}{/literal}
</table>
</div>
</td>
<td>
<b>{$ETIQUETA_AGENTES}:</b>
<div id="lista_agentes_cola">
<table width="100%">
<thead>
<tr>
<th>{$ETIQUETA_AGENTE}</th>
<th>{$ETIQUETA_ESTADO}</th>
<th>{$ETIQUETA_NUMERO_TELEFONO}</th>
<th>{$ETIQUETA_TRONCAL}</th>
<th>{$ETIQUETA_DESDE}</th>
</tr>
</thead>
{literal}{{#view tagName="tbody"}}
{{#each App.campaniaActual.agentes}}
<tr>
<td>{{canal}}</td>
<td>{{estado}}</td>
<td>{{numero}}</td>
<td>{{troncal}}</td>
<td>{{desde}}</td>
</tr>
{{/each}}
{{/view}}{/literal}
</table>
</div>
</td></tr></table>
<b>{$ETIQUETA_REGISTRO}: </b><br/>
<table width="100%" border="1">
{literal}{{#view tagName="tbody"}}
{{#each App.campaniaActual.registro}}
<tr>
<td>{{timestamp}}</td>
<td>{{mensaje}}</td>
</tr>
{{/each}}
{{/view}}{/literal}
</table>
</script>
</div>
<pre>
{$INFO_DEBUG}
</pre>
