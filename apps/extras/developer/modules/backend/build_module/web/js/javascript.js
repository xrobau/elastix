var moduleId = "";

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
    var arrAction              = new Array();
        arrAction["action"]    = "mostrar_menu";
        arrAction["menu"]      = "build_module";
        arrAction["rawmode"]   = "yes";
        arrAction["level"]     =  level;
        arrAction["parent_1_existing"] = parent_1_existing;
        arrAction["parent_2_existing"] = parent_2_existing;
        arrAction["id_parent"] = id_parent;
            request("index.php",arrAction,false,
                function(arrData,statusResponse,error)
                {
                    if(error)
                        alert(error);
                    else{
                        if(arrData["parent_menu_1"] != null)
                            document.getElementById("parent_menu_1").innerHTML = arrData["parent_menu_1"];
                        if(arrData["level2_exist"] != null)
                            document.getElementById("level2_exist").innerHTML = arrData["level2_exist"];
                        if(arrData["parent_menu_2"] != null)
                            document.getElementById("parent_menu_2").innerHTML = arrData["parent_menu_2"];
                        if(arrData["label_level2"] != null)
                            document.getElementById("label_level2").innerHTML = arrData["label_level2"];
                    }
                }
            );
}

function save_module()
{
    var val_module_name = "";
    var val_selected_gp = new Array();
    var group_form_object = new Array();//agregado
    var val_module_type = "";
    var val_level = -1, val_exists_p1 = -1, val_exists_p2 = -1;
    var val_parent_1_name = ""
    var val_parent_2_name = ""
    var val_selected_parent_1 = "", val_selected_parent_2 = "";
    var val_your_name = "";
    var val_your_email = "";
    var val_url = "";
    
    val_your_email = document.getElementById("email_module").value;
    val_module_name = document.getElementById("module_name").value;

    var form_object = document.getElementById("items");
    var value_select_type = document.getElementById("module_type").value;
    for (var i = 0; i < form_object.options.length; i++ )
          group_form_object += form_object.options[ i ].value + "\n";
          
    var group_permissions = document.getElementById("group_permissions");
    for (var i = 0; i < group_permissions.options.length; i++)
        if (group_permissions.options[ i ].selected)
            val_selected_gp += group_permissions.options[ i ].value + "\n";
    var module_type = document.getElementById("module_type");
    
    val_module_type = module_type.value;
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

    var parent_2_name = document.getElementById("parent_2_name");
    if(parent_2_name != null)
        val_parent_2_name = parent_2_name.value;

    var parent_module = document.getElementById("parent_module");
    if(parent_module != null)
        val_selected_parent_1 = parent_module.value;
    var parent_module_2 = document.getElementById("parent_module_2");
    if(parent_module_2 != null)
        val_selected_parent_2 = parent_module_2.value;
    
    $('body').css('cursor','wait');
    $('#save').css('cursor','wait');
    
	var arrAction                                    = new Array();
	arrAction["action"]                   = "save_module";
	arrAction["menu"]                     = "build_module";
	arrAction["rawmode"]                  = "yes";
	arrAction["module_name"]              = val_module_name;
	arrAction["parent_1_name"]            = val_parent_1_name;
	arrAction["parent_2_name"]            = val_parent_2_name;
	arrAction["group_permissions"]        = val_selected_gp;
	arrAction["module_type"]              = val_module_type;
	arrAction["your_name"]                = val_your_name;
	arrAction["module_level_options"]     = val_level;
	arrAction["parent_1_existing_option"] = val_exists_p1;
	arrAction["parent_2_existing_option"] = val_exists_p2;
	arrAction["parent_module"]            = val_selected_parent_1;
	arrAction["parent_module_2"]          = val_selected_parent_2;
	arrAction["arr_form"]                 = group_form_object;
	arrAction["email_module"]             = val_your_email;
	arrAction["valor_url"]                = val_url;
	request("index.php",arrAction,false,
	    function(arrData,statusResponse,error)
	    {
	        $('body').css('cursor','default');
	        $('#save').css('cursor','default');
	        if(error)
	            alert(error);
	        else{
	            document.getElementById("error").innerHTML = arrData["message"];
	            if(statusResponse != "ERROR"){
	                moduleId = arrData["moduleId"];
	                setTimeout("redirectToNewModule()",5000);
	            }
	        }
	    }
	);
}

function redirectToNewModule()
{
    location.href= "?menu="+moduleId;
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
        var items = document.getElementById("items");
        var select_items = "";
        var option = "";
        for(var i=0;i<items.options.length;i++){
            option = items.options[i].value;
            select_items += option+"\n";
        }
        document.getElementById("select_items").value = select_items;
     }
     document.getElementById("valor_item").focus();
}

function quitar_item()
{
    var select_item = document.getElementById("items");
    var items = "";
    var option = "";
    for(var i=0;i<select_item.length;i++){
        if(select_item[i].value == select_item.value){
            select_item.removeChild(select_item[i]);
        }
    }
    for(var i=0;i<select_item.options.length;i++){
        option = select_item.options[i].value;
        items += option+"\n";
    }
    document.getElementById("select_items").value = items;
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

function generateId(element,idElement)
{
    var module_name = element.value;
    var module_id = module_name.replace(/\W/g,"_");
    module_id = module_id.replace(/_+/g,"_");
    module_id = module_id.replace(/_$/g,"");
    document.getElementById(idElement).innerHTML = module_id.toLowerCase();
}