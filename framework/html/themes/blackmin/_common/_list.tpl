<br/>
<form class="elastix-standard-formgrid" method="POST" style="margin-bottom:0;" action="{$url}">
{* Botón invisible al inicio del form que impide que el primer botón visible del filtro, frecuentemente Borrar, sea default *}
<input type="submit" name="" value="" style="height: 0; min-height: 0; font-size: 0; width: 0; border: none; outline: none; padding: 0px; margin: 0px; box-sizing: border-box; float: left;" />
    {* INICIO: Bloque del filtro del contenido *}
    <div class="controls">
    {foreach from=$arrActions key=k item=accion name=actions}
        {if $accion.type eq 'link'}
            <a href="{$accion.task}" class="neo-table-action" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} >
                <div class="toolcell toolaction" {if !empty($accion.ocolor)} style="background-color:#{$accion.ocolor};" {/if}>
                    <button type="button" name="{$accion.task}" value="{$accion.alt}" class="neo-table-toolbar-button">
                       {if !empty($accion.iconclass)}<i class="{$accion.iconclass}"></i> {elseif !empty($accion.icon)}<img border="0" src="{$accion.icon}" align="absmiddle"  />{/if}{$accion.alt}
                    </button>
                </div>
            </a>
        {elseif $accion.type eq 'button'}
            <div class="toolcell toolaction" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="neo-table-action" {if !empty($accion.ocolor)} style="background-color:#{$accion.ocolor};" {/if}>
                <button type="button" name="{$accion.task}" value="{$accion.alt}">
                    {if !empty($accion.iconclass)}<i class="{$accion.iconclass}"></i> {elseif !empty($accion.icon)}<img border="0" src="{$accion.icon}" align="absmiddle"  />{/if}{$accion.alt}
                </button>
            </div>
        {elseif $accion.type eq 'submit'}
            <div class="toolcell toolaction" {if !empty($accion.ocolor)} style="background-color:#{$accion.ocolor};" {/if}>
                <button type="submit" name="{$accion.task}" value="{$accion.alt}" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="neo-table-action">
                   {if !empty($accion.iconclass)}<i class="{$accion.iconclass}"></i> {elseif !empty($accion.icon)}<img border="0" src="{$accion.icon}" align="absmiddle"  />{/if}{$accion.alt}
                </button>
            </div>
        {elseif $accion.type eq 'text'}
            <div class="toolcell toolaction" style="cursor:default">
                <input type="text"   id="{$accion.name}" name="{$accion.name}" value="{$accion.value}" {if !empty($accion.onkeypress)} onkeypress="{$accion.onkeypress}" {/if} style="height:22px" />
                <input type="submit" name="{$accion.task}" value="{$accion.alt}" class="neo-table-action" />
            </div>
        {elseif $accion.type eq 'combo'}
            <div class="toolcell toolaction" style="cursor:default">
                <select name="{$accion.name}" id="{$accion.name}" {if !empty($accion.onchange)} onchange="{$accion.onchange}" {/if}>
                    {if !empty($accion.selected)}
                        {html_options options=$accion.arrOptions selected=$accion.selected}
                    {else}
                        {html_options options=$accion.arrOptions}
                    {/if}
                </select>
                {if !empty($accion.task)}
                    <input type="submit" name="{$accion.task}" value="{$accion.alt}" class="neo-table-action" />
                {/if}
            </div>
        {elseif $accion.type eq 'html'}
            <div class="toolcell toolaction">
                {$accion.html}
            </div>
        {/if}
    {/foreach}

{if !empty($contentFilter)}
        <div class="toolcell toolaction" id="toggle-filter">
            {if $AS_OPTION eq 0} <i class='fa fa-filter'></i> {/if}
            <label id="neo-table-label-filter" style="cursor:pointer">{if $AS_OPTION} {$MORE_OPTIONS} {else} {$FILTER_GRID_SHOW} {/if}</label>
            <i class="fa fa-caret-down" id="neo-tabla-img-arrow"></i>
        </div>
{/if}

{if $enableExport==true}
        <div class="toolcell toolaction exportmenu" >
            <i class="fa fa-download"></i> {$DOWNLOAD_GRID} <i class="fa fa-caret-down"></i>
            <div>
                <ul>
                    <li><a href="{$url}&exportcsv=yes&rawmode=yes"><i style="color:#99c" class="fa fa-file-text-o"></i>&nbsp;CSV</a></li>
                    <li><a href="{$url}&exportspreadsheet=yes&rawmode=yes"><i style="color:green;" class="fa fa-file-excel-o"></i>&nbsp;Spreadsheet</a></li>
                    <li><a href="{$url}&exportpdf=yes&rawmode=yes"><i style="color:red;" class="fa fa-file-pdf-o"></i>&nbsp;PDF</a></li>
                </ul>
            </div>
        </div>
{/if}

        <div class="toolcell navigation">
            {if $pagingShow}
                {if $start<=1}
                    <i class="fa fa-step-backward" style="color:#aaa;"></i>&nbsp;<i class="fa fa-backward" style="color:#aaa"></i>
                {else}
                    <a href="{$url}&nav=start&start={$start}" class="fa fa-step-backward"></a>
                    <a href="{$url}&nav=previous&start={$start}" class="fa fa-backward"></a>
                {/if}
                &nbsp;{$lblPage}&nbsp;
                <input type="text"  value="{$currentPage}" size="2" align="absmiddle" name="page" id="pageup" />&nbsp;{$lblof}&nbsp;{$numPage}
                <input type="hidden" value="bypage" name="nav" />
                {if $end==$total}
                    <i class="fa fa-forward" style="color:#aaa;"></i>&nbsp;<i class="fa fa-step-forward" style="color:#aaa"></i>
                {else}
                    <a href="{$url}&nav=next&start={$start}" class="fa fa-forward"></a>
                    <a href="{$url}&nav=end&start={$start}" class="fa fa-step-forward"></a>
                {/if}
            {/if}
        </div>
    </div>
{if !empty($contentFilter)}
    <div id="filter-row">
        {$contentFilter}
    </div>
{/if}
{if !empty($arrFiltersControl)}
    <div class="appliedfilters">
        {foreach from=$arrFiltersControl key=k item=filterc name=filtersctrl}
            <div>{$filterc.msg}&nbsp;
            {if $filterc.defaultFilter eq no}
                <a href="{$url}&name_delete_filters={$filterc.filters}" style="color:#aaa;text-decoration:none;"><i class="fa fa-remove"></i></a>
            {/if}
            </div>
        {/foreach}
    </div>
{/if}
    {* FINAL: Bloque del filtro del contenido *}
        <table class="elastix-standard-table">
        <thead>
            <tr>
                {section name=columnNum loop=$numColumns start=0 step=1}
                <th>{$header[$smarty.section.columnNum.index].name}&nbsp;</th>
                {/section}
            </tr>
        </thead>
        <tbody>
            {if $numData > 0}
            {foreach from=$arrData key=k item=data name=filas}
                {if $data.ctrl eq 'separator_line'}
                    <tr>
                        {if $data.start > 0}
                            <td colspan="{$data.start}"></td>
                        {/if}
                        {assign var="data_start" value="`$data.start`"}
                        <td colspan="{$numColumns-$data.start}" style='background-color:#AAAAAA;height:1px;'></td>
                    </tr>
                {else}
                    <tr>
                        {if $smarty.foreach.filas.last}
                            {section name=columnNum loop=$numColumns start=0 step=1}
                            <td class="table_data_last_row">{if $data[$smarty.section.columnNum.index] eq ''}&nbsp;{/if}{$data[$smarty.section.columnNum.index]}</td>
                            {/section}
                        {else}
                            {section name=columnNum loop=$numColumns start=0 step=1}
                            <td class="table_data">{if $data[$smarty.section.columnNum.index] eq ''}&nbsp;{/if}{$data[$smarty.section.columnNum.index]}</td>
                            {/section}
                        {/if}
                    </tr>
                {/if}
            {/foreach}
            {else}
                <tr><td class="table_data" colspan="{$numColumns}" align="center">{$NO_DATA_FOUND}</td></tr>
            {/if}
        </tbody>
            {if $numData > 3}
        <tfoot>
            <tr>
                {section name=columnNum loop=$numColumns start=0 step=1}
                <th>{$header[$smarty.section.columnNum.index].name}&nbsp;</th>
                {/section}
            </tr>
        </tfoot>
            {/if}
    </table>
    <div class="controls">
        <div class="toolcell navigation">
            {if $pagingShow}
                {if $start<=1}
                    <i class="fa fa-step-backward" style="color:#aaa;"></i>&nbsp;<i class="fa fa-backward" style="color:#aaa"></i>
                {else}
                    <a href="{$url}&nav=start&start={$start}" class="fa fa-step-backward"></a>
                    <a href="{$url}&nav=previous&start={$start}" class="fa fa-backward"></a>
                {/if}
                &nbsp;{$lblPage}&nbsp;
                <input  type=text  value="{$currentPage}" size="2" align="absmiddle" name="page" id="pagedown" />&nbsp;{$lblof}&nbsp;{$numPage}{*&nbsp;({$total}&nbsp;{$lblrecords})*}
                {if $end==$total}
                    <i class="fa fa-forward" style="color:#aaa;"></i>&nbsp;<i class="fa fa-step-forward" style="color:#aaa"></i>
                {else}
                    <a href="{$url}&nav=next&start={$start}" class="fa fa-forward"></a>
                    <a href="{$url}&nav=end&start={$start}" class="fa fa-step-forward"></a>
                {/if}
            {/if}
        </div>
    </div>
</form>
{literal}
<script type="text/javascript">
$(document).ready(function() {
    // Sincronizar los dos cuadros de texto de navegación al escribir
    $("[id^=page]").keyup(function(event) {
        var id  = $(this).attr("id");
        var val = $(this).val();

        if(id == "pageup")
            $("#pagedown").val(val);
        else if(id == "pagedown")
            $("#pageup").val(val);
    });

    $("form.elastix-standard-formgrid #toggle-filter").click(function() {
{/literal}
    {if $AS_OPTION}
        var filter_show = "{$MORE_OPTIONS}";
        var filter_hide = "{$MORE_OPTIONS}";
    {else}
        var filter_show = "{$FILTER_GRID_SHOW}";
        var filter_hide = "{$FILTER_GRID_HIDE}";
    {/if}
{literal}

        var filterrow = $(this).parents('form.elastix-standard-formgrid')
            .first().find('#filter-row');
        if (filterrow.is(':visible')) {
            filterrow.hide();
            $(this).find("#neo-tabla-img-arrow").removeClass('fa-caret-up').addClass('fa-caret-down');
            $(this).find("#neo-table-label-filter").text(filter_show);
            $(this).removeClass("export-background");
        } else {
            filterrow.show();
            $(this).find("#neo-tabla-img-arrow").removeClass('fa-caret-down').addClass('fa-caret-up');
            $(this).find("#neo-table-label-filter").text(filter_hide);
            $(this).addClass("export-background");
        }
    });

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
});
</script>
{/literal}