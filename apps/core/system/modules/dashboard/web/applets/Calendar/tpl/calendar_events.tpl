{foreach from=$EVENTOS_DIAS item=evento}
<a href='?menu=calendar&amp;action=display&amp;id={$evento.id}&amp;event_date={$evento.dateshort}'>{$evento.subject|escape:html}</a>&nbsp;&nbsp;&nbsp;Date: {$evento.date} - Call: {$evento.call}<br/>
{foreachelse}
<p>{$NO_EVENTOS}</p>
{/foreach}