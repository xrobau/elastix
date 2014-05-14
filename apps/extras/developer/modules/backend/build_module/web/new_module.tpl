<div id='error' name='error'></div>
<div>
<table width="99%" cellspacing="0" cellpadding="4" align="center">
    <tr>
        <td align="left"><input  id="save" class="button" type="button" name="save" value="{$SAVE}" onclick="save_module()"></td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
  <tr class="moduleTitle">
    <td class="moduleTitle" valign="middle">{$general_information}</td>
  </tr>
  <tr>
    <td>
      <table class="tabForm" style="font-size: 16px;" width="100%">
        <tr class="letra12">
         <td align="left" width="17%"><b>{$module_name_label}: <span  class="required">*</span></b></td>
         <td align="left" width="22%"><input type='text' name='module_name' id='module_name' value='' onkeyup='generateId(this,"id_module")'></td>
	 <td align="left"><b>{$your_name_label}: <span  class="required">*</span></b></td>
         <td align="left"><input type='text' name='your_name' id='your_name' value=''></td>
	 <td rowspan="2" align="left" valign="top"><b>{$group_permissions.LABEL}:</b></td>
         <td rowspan="2" align="left">
            <select id='group_permissions' name='group_permissions' multiple='multiple' size='3'>
                {foreach key="key" from=$arrGroups item="value"}
                    {if $value=='administrator'}
                        <option value='{$key}' selected="selected">{$value}</option>
                    {else}
                        <option value='{$key}'>{$value}</option>
                    {/if}
                {/foreach}
            </select>
         </td>
        
    </tr>
    <tr class="letra12">
	<td align="left"><b>{$id_module_label}:</b></td>
        <td align="left"><b><i id='id_module'></i></b></td>
	<td align="left"><b>{$email}: <span  class="required">*</span></b></td>
        <td align="left"><input type='text' name='email_module' id='email_module' value=''></td>

    </tr>
  </table>
  </td>
</tr>
</table>
<br/>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
  <tr class="moduleTitle">
    <td class="moduleTitle" valign="middle">{$location}</td>
  </tr>
  <tr>
    <td>
       <table class="tabForm" style="font-size: 16px;" width="100%" >
        <tr class="letra12">
          <td width="17%" align="left"><b>{$module_level}: <span  class="required">*</span></b></td>
          <td align="left">
             <select id='module_level_options' name='module_level_options' onchange='mostrar_menu()'>
                <option value='level_2' >{$level_2}</option>
                <option value='level_3' >{$level_3}</option>
             </select>
          </td>
          <td width=10%></td>
          <td></td>
          <td></td>
        </tr>

        <tr class="letra12">
          <td align="left"><b>{$parent_1_exists}: <span  class="required">*</span></b></td>
          <td align="left" width="22%">
             <select id='parent_1_existing_option' name='parent_1_existing_option' onchange='mostrar_menu()'>
                <option value='{$peYes}'>{$peYes}</option>
                <option value='{$peNo}' selected="selected">{$peNo}</option>
            </select>
          </td>
          <td align="left" id='label_level2' width="14%"></td>
          <td align="left" id='level2_exist'></td>
       </tr>

       <tr class="letra12" id='parent_menu_1'>
          <td align='left'><b>{$level_1_parent_name}: <span  class='required'>*</span></b></td>
          <td align='left' width="22%"><input type='text' name='parent_1_name' id='parent_1_name' value='' onkeyup='generateId(this,"parent_1_id")'></td>
          <td align='left' width="11%"><b>{$level_1_parent_id}: </b></td>
          <td align='left'><i id='parent_1_id'></i></td>
       </tr>

       <tr class="letra12" id='parent_menu_2'></tr>
     </table>
    </td>
   </tr>
</table>
<br/>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
  <tr class="moduleTitle">
    <td class="moduleTitle" valign="middle">{$module_description}</td>
  </tr>
  <tr>
    <td>
       <table class="tabForm" style="font-size: 16px;" width="100%">
     
       <tr class="letra12">
        <td width="17%" align="left"><b>{$module_type}: <span  class="required">*</span></b></td>
        <td width="15%" align="left">
            <select id='module_type' name='module_type' onchange="show_field_to_create()">
                <option value='form' >{$type_form}</option>
                <option value='grid' >{$type_grid}</option>
                <option value='framed' >{$type_framed}</option>
            </select>
        </td>
        <td width="15%" align="left" id="field_name" >{$Field_Name}: <span class="required">*</span></td>
        <td align="left" id="url" style="display:none;" width="35%"><b>{$Url}: <span class="required">*</span></b></td>
        <td width="15%" align="left" id="v_item" ><input name="valor_item" id="valor_item" value="" type="text" size="15"></td>
        <td width="50%" align="left" id="v_url" style="display:none;"><input name="valor_url" id="valor_url" value="" type="text" size="25"></td>
        <td width="8%" align="left"><input class="button" name="add" id ="add" value=">>" onclick="javascript:agregar_item();" type="button"></td>
       <td rowspan="2"><select name="items" size="4" id="items" style="width: 120px;">
                    </select>
      <input type="hidden" id="select_items" name="select_items">
      </td>
      </tr>
      <tr class="letra12">
	<td width="5%"></td>
	<td width="5%"></td>
	<td width="5%" align="left"><span id="label_type">{$Type_Field}:</span></td>
	<td width="5%" align="left">
                   <select name="type" onclick="" id="type_field" style="width: 130px;">
                       {html_options values=$option_type.VALUE output=$option_type.NAME selected=$option_type.SELECTED}
                   </select></td>
	<td width="5%" align="left"><input class="button" name="remove" id="remove" value="<<" onclick="javascript:quitar_item();" type="button"></td>
    </tr> 

    </table>
   </td>
  </tr>
</table>
</div>