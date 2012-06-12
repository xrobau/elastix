<div id='error' name='error'></div>
<div>
<table width="99%" cellspacing="0" cellpadding="4" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/conference.png" border="0" align="absmiddle">&nbsp;&nbsp;{$TITLE}</td>
        <td></td>
    </tr>
    <tr>
        <td align="left"><input class="button" type="button" name="save" value="{$SAVE}" onclick="save_module()"></td>
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
         <td align="left"><b>{$module_name_label}: <span  class="required">*</span></b></td>
         <td align="left"><input type='text' name='module_name' id='module_name' value=''></td>
         <td width=10%></td>
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
	<td align="left"><b>{$id_module_label}: <span  class="required">*</span></b></td>
        <td align="left"><input type='text' name='id_module' id='id_module' value=''></td>
        <td width=10%></td>
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
          <td width="15%" align="left"><b>{$module_level}: <span  class="required">*</span></b></td>
          <td width="15%" align="left">
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
          <td align="left">
             <select id='parent_1_existing_option' name='parent_1_existing_option' onchange='mostrar_menu()'>
                <option value='{$peYes}'>{$peYes}</option>
                <option value='{$peNo}' selected="selected">{$peNo}</option>
            </select>
          </td>
          <td></td>
          <td align="left" id='label_level2'></td>
          <td align="left" id='level2_exist'></td>
       </tr>

       <tr class="letra12" id='parent_menu_1'>
          <td align='left'><b>{$level_1_parent_name}: <span  class='required'>*</span></b></td>
          <td align='left'><input type='text' name='parent_1_name' id='parent_1_name' value='' ></td>
          <td></td>
          <td align='left'><b>{$level_1_parent_id}: <span  class='required'>*</span></b></td>
          <td align='left'><input type='text' name='parent_1_id' id='parent_1_id' value='' ></td>
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
        <td width="10%" align="left"><b>{$module_type}: <span  class="required">*</span></b></td>
        <td width="5%" align="left">
            <select id='module_type' name='module_type' onchange="show_field_to_create()">
                <option value='form' >{$type_form}</option>
                <option value='grid' >{$type_grid}</option>
                <option value='framed' >{$type_framed}</option>
            </select>
        </td>
        <td width="10%" align="left" id="field_name" >{$Field_Name}: <span class="required">*</span></td>
        <td width="10%" align="left" id="url" style="display:none;">{$Url}: <span class="required">*</span></td>
        <td width="5%" align="left" id="v_item" ><input name="valor_item" id="valor_item" value="" type="text" size="15"></td>
        <td width="50%" align="left" id="v_url" style="display:none;"><b>{$http}{$ip}</b> <input name="valor_url" id="valor_url" value="" type="text" size="50"></td>
        <td width="5%" align="left"><input class="button" name="add" id ="add" value=">>" onclick="javascript:agregar_item();" type="button"></td>
       <td rowspan="2" width=25%><select name="items" size="4" id="items" style="width: 120px;">
                    </select></td>
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

{literal}
<script type="text/javascript">
    function mostrar_menu()
    {
        var level = document.getElementById("module_level_options").selectedIndex;
        var parent_1_existing = document.getElementById("parent_1_existing_option").selectedIndex;

        var parent_2_existing;
        if(document.getElementById("parent_2_existing_option") != null)
            parent_2_existing = document.getElementById("parent_2_existing_option").selectedIndex;
        else parent_2_existing = -1;

        var id_parent = '';
        if(document.getElementById("parent_module") !=null)
        {
            var index = document.getElementById("parent_module").selectedIndex;
            id_parent = document.getElementById("parent_module").options[index].value;
        }

        xajax_mostrar_menu(level, parent_1_existing, parent_2_existing, id_parent);
    }

    function save_module()
    {
        var val_module_name = "", val_id_module = "";
        var val_selected_gp = new Array();
        var group_form_object = new Array();//agregado
        var val_module_type = "";
        var val_level = -1, val_exists_p1 = -1, val_exists_p2 = -1;
        var val_parent_1_name = "", val_parent_1_id = "";
        var val_parent_2_name = "", val_parent_2_id = "";
        var val_selected_parent_1 = "", val_selected_parent_2 = "";
        var val_your_name = "";
        var val_your_email = "";
        var val_url = "";
        
        val_your_email = document.getElementById("email_module").value;
        val_module_name = document.getElementById("module_name").value;
        val_id_module = document.getElementById("id_module").value;

        var form_object = document.getElementById("items");
        var value_select_type = document.getElementById("module_type").value;
        for (var i = 0; i < form_object.options.length; i++ )
              group_form_object.push(form_object.options[ i ].value);
              
	var group_permissions = document.getElementById("group_permissions");
        for (var i = 0; i < group_permissions.options.length; i++)
            if (group_permissions.options[ i ].selected)
                val_selected_gp.push(group_permissions.options[ i ].value);

        var module_type = document.getElementById("module_type");
        for (var i = 0; i < module_type.options.length; i++)
                if (module_type.options[ i ].selected)
                    val_module_type = module_type.options[ i ].value;
        if(val_module_type == "framed" )
           val_url = document.getElementById("valor_url").value;
        else
           val_url = "";

        val_your_name = document.getElementById("your_name").value;

        val_level = document.getElementById("module_level_options").selectedIndex;

        val_exists_p1 = document.getElementById("parent_1_existing_option").selectedIndex;

        var exits_p2_option = document.getElementById("parent_2_existing_option");
        if(exits_p2_option != null)
            val_exists_p2 = exits_p2_option.selectedIndex;

        var parent_1_name = document.getElementById("parent_1_name");
        if(parent_1_name != null)
            val_parent_1_name = parent_1_name.value;

        var parent_1_id = document.getElementById("parent_1_id");
        if(parent_1_id != null)
            val_parent_1_id = parent_1_id.value;

        var parent_2_name = document.getElementById("parent_2_name");
        if(parent_2_name != null)
            val_parent_2_name = parent_2_name.value;

        var parent_2_id = document.getElementById("parent_2_id");
        if(parent_2_id != null)
            val_parent_2_id = parent_2_id.value;

        var parent_module = document.getElementById("parent_module");
        if(parent_module != null)
        {
            for (var i = 0; i < parent_module.options.length; i++)
                if (parent_module.options[ i ].selected)
                    val_selected_parent_1 = parent_module.options[ i ].value;
        }

        var parent_module_2 = document.getElementById("parent_module_2");
        if(parent_module_2 != null)
        {
            for (var i = 0; i < parent_module_2.options.length; i++)
                if (parent_module_2.options[ i ].selected)
                    val_selected_parent_2 = parent_module_2.options[ i ].value;
        }
        xajax_save_module(val_module_name, val_id_module, val_selected_gp, val_module_type, val_your_name, val_level, val_exists_p1, val_exists_p2, val_parent_1_name, val_parent_1_id, val_parent_2_name, val_parent_2_id, val_selected_parent_1, val_selected_parent_2, group_form_object, val_your_email,val_url);
    }

function agregar_item()
{
     var value_item = document.getElementById("valor_item").value;
     var select_item = document.getElementById("items");
     var option_tmp = document.createElement("option");
     var type_field = document.getElementById("type_field").value;
     var value_select_type = document.getElementById("module_type").value;

      

     if(value_item != "" )
     {
        
        if (value_select_type == "form"){
            option_tmp.value = value_item+"/"+type_field;
            option_tmp.label = value_item;
            option_type = value_item + "/" +type_field;
        }else{
            // select_item.innerHTML = "";
            option_tmp.value = value_item;
            option_tmp.label = value_item;
            option_type = value_item
            }

        option_tmp.appendChild(document.createTextNode(option_type));
        select_item.appendChild(option_tmp);
        document.getElementById("valor_item").value = "";
     }
     document.getElementById("valor_item").focus();
}

function quitar_item()
{
    var select_item = document.getElementById("items");

    for(var i=0;i<select_item.length;i++){
        if(select_item[i].value == select_item.value){
            select_item.removeChild(select_item[i]);
        }
    }
}
function show_field_to_create()
{
    var value_select_type = document.getElementById("module_type").value;
    
        document.getElementById("add").style.display="";
        document.getElementById("remove").style.display="";
        document.getElementById("items").innerHTML = "";
    if(value_select_type=='form'){
        document.getElementById("type_field").style.display="";    
        document.getElementById("label_type").style.display="";
        document.getElementById("url").style.display="none";        
        document.getElementById("items").style.display = "";
        document.getElementById("field_name").style.display="";
        document.getElementById("v_item").style.display="";
        document.getElementById("v_url").style.display="none";
    } else if(value_select_type=='grid'){
        document.getElementById("type_field").style.display="none";
        document.getElementById("label_type").style.display="none";
        document.getElementById("items").style.display = "";
        document.getElementById("url").style.display="none";        
        document.getElementById("field_name").style.display="";
        document.getElementById("v_item").style.display="";
        document.getElementById("v_url").style.display="none";
    } else if(value_select_type=='framed'){
        document.getElementById("type_field").style.display="none";
        document.getElementById("label_type").style.display="none";
        document.getElementById("items").style.display = "none";
        document.getElementById("add").style.display="none";
        document.getElementById("remove").style.display="none";
        document.getElementById("field_name").style.display="none";
        document.getElementById("url").style.display="";        
        document.getElementById("v_item").style.display="none";
        document.getElementById("v_url").style.display="";
    }
}

</script>
{/literal}
