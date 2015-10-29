<form  method='POST' style='margin-bottom:0;' name='formCalendar' id='formCalendar'>
<br />
<table class="tabForm" width="100%"><tr>
	<td id="calendar_toolbar" width="10%" align="left" valign="top">
	    <div id="calendar_buttonbox" style="margin: 0px 10px 6px 10px; text-align: center;" valign="middle">
	        <button type='button' class='button' id="calendar_newevent" ><i class="fa fa-plus"></i>&nbsp;{$LBL_CREATE_EVENT}</button>
	    </div>
	    <div id="calendar_datepick">
	    </div>
	    <div id="calendar_ical_links" class="ui-widget ui-widget-content ui-helper-clearfix ui-corner-all">
            <div class="ui-datepicker-header ui-widget-header ui-helper-clearfix ui-corner-all title_size">{$LBL_EXPORT_CALENDAR}</div>
            <div class="content_ical">
                <a href="/rest.php/{$module_name}/CalendarEvent?format=ical">
                    <span><i class="fa fa-download"></i>&nbsp;{$LBL_LINK_ICAL}</span>
                </a>
            </div>
	    </div>
	</td>
	<td align="right" width="90%">
	   <div id="calendar_main"></div>
	</td>
</tr></table>
{* Los siguientes campos de formulario definen la fecha de inicio del calendario *}
<input type="hidden" name="server_year" value="{$SERVER_YEAR}" />
<input type="hidden" name="server_month" value="{$SERVER_MONTH}" />
{* El siguiente campo de formulario sólo se asigna al pedir action=display *}
<input type="hidden" name="event_id" value="{$EVENT_ID}" />
</form>

<div id="calendar_eventdialog" style="display: none;">
{$EVENT_DIALOG}
</div>

<script type="text/javascript">
var arrLang_main = {$ARRLANG_MAIN};
</script>
