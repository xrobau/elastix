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

{* Atributos y contadores de la campaña elegida *}
<table width="100%" >
<tr>
<td><b>{$ETIQUETA_CAMPANIA}:</b></td>
<td>{literal}{{view Ember.Select
    contentBinding="App.campaniasDisponibles"
    optionValuePath="content.key_campaign"
    optionLabelPath="content.desc_campaign"
    valueBinding="App.campaniasDisponibles.key_campaign" }}{/literal}</td>
<td><b>{$ETIQUETA_FECHA_INICIO}:</b></td>
<td>{literal}{{App.campaniaActual.fechaInicio}}{/literal}</td>
<td><b>{$ETIQUETA_FECHA_FINAL}:</b></td>
<td>{literal}{{App.campaniaActual.fechaFinal}}{/literal}</td>
</tr>
<tr>
<td><b>{$ETIQUETA_COLA}:</b></td>
<td>{literal}{{App.campaniaActual.cola}}{/literal}</td>
<td><b>{$ETIQUETA_INTENTOS}:</b></td>
<td>{literal}{{App.campaniaActual.maxIntentos}}{/literal}</td>
<td><b>{$ETIQUETA_HORARIO}:</b></td>
<td>{literal}{{App.campaniaActual.horaInicio}} - {{App.campaniaActual.horaFinal}}{/literal}</td>
</tr>
</table>

<table width="100%" >
<tr>
<td><b>{$ETIQUETA_TOTAL_LLAMADAS}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.total}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_COLA}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.encola}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_EXITO}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.conectadas}}{/literal}</td>
</tr>
{literal}{{#if App.campaniaActual.outgoing }}{/literal}
<tr>
<td><b>{$ETIQUETA_LLAMADAS_PENDIENTES}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.pendientes}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_MARCANDO}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.marcando}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_TIMBRANDO}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.timbrando}}{/literal}</td>
</tr>
<tr>
<td><b>{$ETIQUETA_LLAMADAS_FALLIDAS}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.fallidas}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_NOCONTESTA}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.nocontesta}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_ABANDONADAS}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.abandonadas}}{/literal}</td>
</tr>
<tr>
<td><b>{$ETIQUETA_LLAMADAS_CORTAS}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.cortas}}{/literal}</td>
<td colspan="4">&nbsp;</td>
</tr>
{literal}{{else}}{/literal}
<tr>
<td><b>{$ETIQUETA_LLAMADAS_SINRASTRO}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.sinrastro}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_ABANDONADAS}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.abandonadas}}{/literal}</td>
<td><b>{$ETIQUETA_LLAMADAS_TERMINADAS}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.terminadas}}{/literal}</td>
</tr>
{literal}{{/if}}{/literal}
<tr>
<td><b>{$ETIQUETA_PROMEDIO_DURAC_LLAM}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.fmtpromedio}}{/literal}</td>
<td><b>{$ETIQUETA_MAX_DURAC_LLAM}:</b></td>
<td>{literal}{{App.campaniaActual.llamadas.fmtmaxduration}}{/literal}</td>
</tr>
</table>

<table width="100%" ><tr><td width="50%" style="vertical-align: top;">
<b>{$ETIQUETA_LLAMADAS_MARCANDO}:</b>
<table class="titulo">
<tr>
<td width="20%" nowrap="nowrap">{$ETIQUETA_ESTADO}</td>
<td width="30%" nowrap="nowrap">{$ETIQUETA_NUMERO_TELEFONO}</td>
<td width="30%" nowrap="nowrap">{$ETIQUETA_TRONCAL}</td>
<td width="20%" nowrap="nowrap">{$ETIQUETA_DESDE}</td>
</tr>
</table>
<div class="llamadas" {literal}{{bindAttr style="App.campaniaActual.alturaLlamada"}}{/literal}>
<table>
{literal}{{#view tagName="tbody"}}
{{#each App.campaniaActual.llamadasMarcando}}
<tr {{bindAttr class="reciente"}}>
<td width="20%" nowrap="nowrap">{{estado}}</td>
<td width="30%" nowrap="nowrap">{{numero}}</td>
<td width="30%" nowrap="nowrap">{{troncal}}</td>
<td width="20%" nowrap="nowrap">{{desde}}</td>
</tr>
{{/each}}
{{/view}}{/literal}
</table>
</div>
</td>
<td width="50%" style="vertical-align: top;">
<b>{$ETIQUETA_AGENTES}:</b>
<table class="titulo">
<tr>
<td width="20%" nowrap="nowrap">{$ETIQUETA_AGENTE}</td>
<td width="14%" nowrap="nowrap">{$ETIQUETA_ESTADO}</td>
<td width="23%" nowrap="nowrap">{$ETIQUETA_NUMERO_TELEFONO}</td>
<td width="23%" nowrap="nowrap">{$ETIQUETA_TRONCAL}</td>
<td width="20%" nowrap="nowrap">{$ETIQUETA_DESDE}</td>
</tr>
</table>
<div class="llamadas" {literal}{{bindAttr style="App.campaniaActual.alturaLlamada"}}{/literal}>
<table>
{literal}{{#view tagName="tbody"}}
{{#each App.campaniaActual.agentes}}
<tr {{bindAttr class="reciente"}}>
<td width="20%" nowrap="nowrap">{{canal}}</td>
<td width="14%" nowrap="nowrap">{{estado}}</td>
<td width="23%" nowrap="nowrap">{{numero}}</td>
<td width="23%" nowrap="nowrap">{{troncal}}</td>
<td width="20%" nowrap="nowrap">{{desde}}</td>
</tr>
{{/each}}
{{/view}}{/literal}
</table>
</div>
</td></tr></table>

{literal}{{view Ember.Checkbox checkedBinding="App.campaniaActual.registroVisible"}}{/literal}
<b>{$ETIQUETA_REGISTRO}: </b><br/>
{literal}{{#if App.campaniaActual.registroVisible}}
<button class="button" {{action cargarPrevios target="App.campaniaActual"}}>{/literal}{$PREVIOUS_N}{literal}</button>
{{#view App.RegistroView class="registro" messagesBinding="App.campaniaActual.registro" }}
<table>
{{#each App.campaniaActual.registro}}
<tr>
<td>{{timestamp}}</td>
<td>{{mensaje}}</td>
</tr>
{{/each}}
</table>
{{/view}}
{{/if}}{/literal}
</script>
</div>
