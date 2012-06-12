{$xajax_javascript}
<script src="modules/{$MODULE_NAME}/libs/js/base.js"></script>
<!-- Message board -->
<div class="mb_title" id="mb_title"></div>
<div class="mb_message" id="mb_message"></div>
<!-- end of Message board -->
<form method="POST" name="form_formulario">
    <table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
{if !$FRAMEWORK_TIENE_TITULO_MODULO}
        <tr class="moduleTitle">
            <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle" />&nbsp;&nbsp;{$title} 
            </td>
        </tr>
{/if}        
        <tr>
            <td>
                <table width="100%" cellpadding="3" cellspacing="0" border="0">
                    <tr>
                        <td align="left">
                        {if $mode eq 'input'}
                        <input class="button" type="button" name="save" value="{$SAVE}"     onclick="guardar_formulario('nuevo');">
                        <input class="button" type="button" name="cancel" value="{$CANCEL}" onclick="javascript:window.open('?menu=form_designer','_parent');"></td>
<!--onclick="cancelar_formulario_ingreso();"-->
                        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
                        {elseif $mode eq 'edit'}
                        <input class="button" type="button" name="apply_changes" value="{$APPLY_CHANGES}" onclick="guardar_formulario('edit');">
                        <input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
                        {else}
                        <input class="button" type="submit" name="edit" value="{$EDIT}">

                        <input class="button" type="button" name="desactivar" value="{$DESCATIVATE}"  onClick="if(confirmSubmit('{$CONFIRM_CONTINUE}'))desactivar_formulario();">

                        <input class="button" type="submit" name="delete" value="{$DELETE}" onClick="return confirmSubmit('{$CONFIRM_DELETE}');">

                        <input class="button" type="button" name="cancel" value="{$CANCEL}" onclick="javascript:window.open('?menu=form_designer','_parent');"></td>
                        </td>                        
                        {/if}          
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                    <tr>
                            <td width="12%" valign="top">{$form_nombre.LABEL}: <span  class="required" {$style_field}>*</span></td>
                            <td width="42%" valign="top">{$form_nombre.INPUT}</td>
                            <td width="9%" valign="top">{$form_description.LABEL}:</td>
                            <td width="45%" valign="top">{$form_description.INPUT}</td>
                    </tr> 
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" {$style_field}>
                    <tr>
                        <td class="moduleTitle" valign="middle" width='11%' id="id_estado_field">{$new_field}</td>
                        <td class="moduleTitle" align="left" width='42%'>
                            <input class="button" type="button" id="add_field" name="add_field" value="{$add_field}" onclick="javascript:agregar_campos_formulario();" />
                            <input class="button" type="button" id="update_field" name="update_field" value="{$update_field}" onclick="javascript:update_campo_formulario();" />
                            <input class="button" type="button" id="cancel_field" name="cancel_field" value="{$CANCEL}" onclick="javascript:cancel_campo_formulario();" />
                        </td>
                        <td class="moduleTitle" width='27%'>&nbsp;</td>
                        <td style='font-size: 12px;background-color: #ffffff;border-color: #999999;background-image: url(/crm/themes/Sugar/images/bgGray.gif); ' width='20%' id='mb_msg_ok'></td>
                    </tr>
                </table>
            </td> 
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" {$style_field}>
                    <tr>
                            <td width="12%" valign="top">{$field_nombre.LABEL}: <span  class="required">*</span></td>
                            <td width="42%" valign="top">{$field_nombre.INPUT}</td>
                            <td width="9%" valign="top">{$order.LABEL}: <span  class="required">*</span></td>
                            <td width="45%" valign="top">{$order.INPUT}</td>
                    </tr>
                    <tr>
                            <td width="12%" valign="top">{$type}: <span  class="required">*</span></td>
                            <td width="28%" valign="top">
                                <select name="{$select_type}" onclick="presentar_select_item()" id="type" style="width:130px">
                                    {html_options values=$option_type.VALUE output=$option_type.NAME selected=$option_type.SELECTED}
                                </select> &nbsp;
                            </td>
                            <td colspan="2" width="60%" style="padding:0px 0px 0px 0px;margin:0px 0px 0px 0px" id='contenedor_select_items' valign="top">
                                <table  width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td rowspan='2' style="font-size:9pt" width='25%' valign="top">{$item_list}:
                                            <span  class="required">*</span></td>
                                        <td rowspan='2' valign="top" width="50"><input type="text" name="valor_item" id='valor_item' value="" /></td>
                                        <td valign="top" width="40"><input class="button" type="button" name="agregar_items" value="{$agregar}" onclick='javascript:agregar_item();'/></td>
                                        <td rowspan='2' valign="top">
                                            <select name="items" size="4" id="items" style="width:120px">
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="40"><input class="button" type="button" name="sacar_item" value="{$quitar}" onclick='javascript:quitar_item();'/></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </td>
                    </tr>
            <!--    <tr >
                        
                </tr>-->
                    <!-- <tr>
                            <td width="20%">{$cvs_column.LABEL}:</td>
                            <td width="80%">{$cvs_column.INPUT}</td>
                </tr>-->
                <!--<tr>
                            <td>{$number_column.LABEL}: <span  class="required">*</span></td>
                            <td>{$number_column.INPUT}</td>
                    </tr>
                <tr>
                            <td>{$number_line.LABEL}: <span  class="required">*</span></td>
                            <td>{$number_line.INPUT}</td>
                    </tr>
                <tr>
                            <td width="20%">{$validation.LABEL}: <span  class="required">*</span></td>
                            <td width="80%">{$validation.INPUT}</td>
                </tr>-->
                
                </table>
            </td>
        </tr>
        <tr>
            <td id="tabla_campos_agregados">
                {$solo_contenido_en_vista}
            </td>
        </tr>
    </table>
    <input type="hidden" name="all_items" id='all_items'  value="" />
    <input type="hidden" name="id_formulario" id='id_formulario'  value="{$id_formulario_actual}" />
    <input type="hidden" name="id_campo_act" id='id_campo_act'  value="{$id_id_campo_actual}" />
</form>
<script type='text/javascript'>
    presentar_select_item();
    visibilidad_botones_campo(2);
</script>