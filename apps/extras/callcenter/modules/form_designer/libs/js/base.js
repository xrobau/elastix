function presentar_select_item()
{
    var value_select_type = document.getElementById("type").value;
    
    if(value_select_type=='LIST'){
        document.getElementById("contenedor_select_items").style.display="";    
    }
    else{
        document.getElementById("contenedor_select_items").style.display="none";
    }
}

function agregar_item()
{
     var value_item = document.getElementById("valor_item").value;
     var select_item = document.getElementById("items");
     var option_tmp = document.createElement("option");

     if(value_item != "")
     {
        option_tmp.value = value_item;
        option_tmp.label = value_item;
        option_tmp.appendChild(document.createTextNode(value_item));
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

function coger_values_option_items()
{
   var select_item = document.getElementById("items");
   var cadena = "";

    for(var i=0;i<select_item.length;i++){
        cadena = cadena + select_item[i].value + ",";
    }
    return cadena;
}

function guardar_formulario(lugar)
{
    var id_formulario = document.getElementById("id_formulario").value;
    var form_description = document.getElementsByName("form_description")[0].value;
    var form_name = document.getElementsByName("form_nombre")[0].value;
	
    xajax_guardar_formulario(id_formulario,form_name,form_description,lugar)
}

function agregar_campos_formulario()
{
    var id_formulario = document.getElementById("id_formulario").value;
    var form_description = document.getElementsByName("form_description")[0].value;
    var form_name = document.getElementsByName("form_nombre")[0].value;
    var field_nombre = document.getElementsByName("field_nombre")[0].value;
    var order_field = document.getElementsByName("order")[0].value;
    var type_field = document.getElementById("type").value;
    var items_field = "";
    if(type_field=='LIST')
        items_field = coger_values_option_items();
    
    xajax_agregar_campos_formulario(id_formulario,form_name,form_description,field_nombre,items_field,type_field,order_field); 
}

function limpiar_campos()
{
    document.getElementsByName("field_nombre")[0].value = "";
    document.getElementsByName("order")[0].value = "";
    document.getElementById("type").value = "";
    document.getElementById("items").innerHTML = "";
}

function cancelar_formulario_ingreso()
{
    var id_formulario = document.getElementById("id_formulario").value;
    xajax_cancelar_formulario_ingreso(id_formulario); 
}

function eliminar_campo()
{
    var arr_field_chk = document.getElementsByName("field_chk");
    var id_formulario = document.getElementById("id_formulario").value;
    var arr_field = new Array();
    var cont = 0;

     for(var i=0;i<arr_field_chk.length;i++) 
     {  
        if(arr_field_chk[i].checked)
        {
            arr_field[cont] = obtener_id("field-",arr_field_chk[i].id);
            cont++;
        }
    }
    xajax_eliminar_campos_formulario(id_formulario,arr_field); 
}

function obtener_id(formato,id_field)
{    
     var id = id_field.split(formato);
     return(id[1]);
}
function editar_campo(id)
{
    var id_formulario = document.getElementById("id_formulario").value;
    xajax_editar_campo_formulario(id_formulario,id);
}

function visibilidad_botones_campo(accion)
{
    if(accion==1){ //se desaparece le boton agregar el resto se ven
        document.getElementById("add_field").style.display="none";
        document.getElementById("update_field").style.display=""; 
        document.getElementById("cancel_field").style.display=""; 
    }

    if(accion==2){ //desaparecer cancel y actualizar solo mostrar el boton agregar
        document.getElementById("add_field").style.display="";
        document.getElementById("update_field").style.display="none"; 
        document.getElementById("cancel_field").style.display="none"; 
    }
}

function update_campo_formulario()
{
    var id_formulario = document.getElementById("id_formulario").value;
    var form_description = document.getElementsByName("form_description")[0].value;
    var form_name = document.getElementsByName("form_nombre")[0].value;
    var id_campo_act = document.getElementById("id_campo_act").value;
    var field_nombre = document.getElementsByName("field_nombre")[0].value;
    var order_field = document.getElementsByName("order")[0].value;
    var type_field = document.getElementById("type").value;
    var items_field = "";
    if(type_field=='LIST')
        items_field = coger_values_option_items();
    
    xajax_update_campo_formulario(id_formulario,form_name,form_description,id_campo_act,field_nombre,items_field,type_field,order_field); 
}

function cancel_campo_formulario()
{
    xajax_cancel_campo_formulario();
}

function desactivar_formulario()
{
    var id_formulario = document.getElementById("id_formulario").value;
    xajax_desactivar_formulario(id_formulario);
}