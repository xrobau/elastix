<form class="elastix-standard-formgrid" method="POST" action="{$url}">
{* Botón invisible al inicio del form que impide que el primer botón visible del filtro, frecuentemente Borrar, sea default *}
<input type="submit" name="" value="" style="height: 0; min-height: 0; font-size: 0; width: 0; border: none; outline: none; padding: 0px; margin: 0px; box-sizing: border-box; float: left;" />
    {* INICIO: Bloque del filtro del contenido *}
    <div class="controls">
    {foreach from=$arrActions key=k item=accion name=actions}
        {if $accion.type eq 'link'}
            <a href="{$accion.task}" class="neo-table-action" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} >
                <div class="toolcell toolaction">
                    {if !empty($accion.icon)}
                        <img border="0" src="{$accion.icon}" align="absmiddle"  />&nbsp;
                    {/if}
                    {$accion.alt}
                </div>
            </a>
        {elseif $accion.type eq 'button'}
            <div class="toolcell toolaction">
                {if !empty($accion.icon)}
                    <img border="0" src="{$accion.icon}" align="absmiddle"  />
                {/if}
                <input type="button" name="{$accion.task}" value="{$accion.alt}" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="neo-table-action" />
            </div>
        {elseif $accion.type eq 'submit'}
            <div class="toolcell toolaction">
                {if !empty($accion.icon)}
                    <input type="image" src="{$accion.icon}" align="absmiddle" name="{$accion.task}" value="{$accion.alt}" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="neo-table-action" />
                {/if}
                <input type="submit" name="{$accion.task}" value="{$accion.alt}" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="neo-table-action" />
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
            {if $AS_OPTION eq 0} <img src="images/filter.png" align="absmiddle" /> {/if}
            <label id="neo-table-label-filter" style="cursor:pointer">{if $AS_OPTION} {$MORE_OPTIONS} {else} {$FILTER_GRID_SHOW} {/if}</label>
            <img src="images/icon_arrowdown2.png" align="absmiddle" id="neo-tabla-img-arrow" />
        </div>
{/if}

{if $enableExport==true}
        <div class="toolcell toolaction exportmenu" >
            <img src="images/download2.png" align="absmiddle" /> {$DOWNLOAD_GRID} <img src="images/icon_arrowdown2.png" align="absmiddle" />
            <div>
                <ul>
                    <li><a href="{$url}&exportcsv=yes&rawmode=yes"><img src="images/csv.gif" border="0" align="absmiddle" title="CSV" />&nbsp;CSV</a></li>
                    <li><a href="{$url}&exportspreadsheet=yes&rawmode=yes"><img src="images/spreadsheet.gif" border="0" align="absmiddle" title="SPREAD SHEET" />&nbsp;Spreadsheet</a></li>
                    <li><a href="{$url}&exportpdf=yes&rawmode=yes"><img src="images/pdf.png" border="0" align="absmiddle" title="PDF" />&nbsp;PDF</a></li>
                </ul>
            </div>
        </div>
{/if}

        <div class="toolcell navigation">
            {if $pagingShow}
                {if $start<=1}
                    <i class="fa fa-step-backward" style="color:#ccc;"></i>&nbsp;<i class="fa fa-backward" style="color:#ccc"></i>
                {else}
                    <a href="{$url}&nav=start&start={$start}" class="fa fa-step-backward"></a>
                    <a href="{$url}&nav=previous&start={$start}" class="fa fa-backward"></a>
                {/if}
                &nbsp;{$lblPage}&nbsp;
                <input type="text"  value="{$currentPage}" size="2" align="absmiddle" name="page" id="pageup" />&nbsp;{$lblof}&nbsp;{$numPage}
                <input type="hidden" value="bypage" name="nav" />
                {if $end==$total}
                    <i class="fa fa-forward" style="color:#ccc;"></i>&nbsp;<i class="fa fa-step-forward" style="color:#ccc"></i>
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
                <a href="{$url}&name_delete_filters={$filterc.filters}"><img src='images/bookmarks_equis.png' width="18" height="16" align='absmiddle' border="0" /></a>
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
                    <i class="fa fa-step-backward" style="color:#ccc;"></i>&nbsp;<i class="fa fa-backward" style="color:#ccc"></i>
                {else}
                    <a href="{$url}&nav=start&start={$start}" class="fa fa-step-backward"></a>
                    <a href="{$url}&nav=previous&start={$start}" class="fa fa-backward"></a>
                {/if}
                &nbsp;{$lblPage}&nbsp;
                <input  type=text  value="{$currentPage}" size="2" align="absmiddle" name="page" id="pagedown" />&nbsp;{$lblof}&nbsp;{$numPage}&nbsp;({$total}&nbsp;{$lblrecords})
                {if $end==$total}
                    <i class="fa fa-forward" style="color:#ccc;"></i>&nbsp;<i class="fa fa-step-forward" style="color:#ccc"></i>
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
            $(this).find("#neo-tabla-img-arrow").attr("src","images/icon_arrowdown2.png");
            $(this).find("#neo-table-label-filter").text(filter_show);
            $(this).removeClass("export-background");
        } else {
            filterrow.show();
            $(this).find("#neo-tabla-img-arrow").attr("src","images/icon_arrowup2.png");
            $(this).find("#neo-table-label-filter").text(filter_hide);
            $(this).addClass("export-background");
        }
    });

    elxneo_apply_jresizer_table();
});
</script>
{/literal}