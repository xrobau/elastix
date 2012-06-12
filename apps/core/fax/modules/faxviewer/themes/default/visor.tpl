{$javascript_xajax}
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tr>
                    <td width="10%">&nbsp;</td>
                    <td width="15%" align="right">{$name_company.LABEL}:</td>
                    <td width="20%">{$name_company.INPUT}</td>	
                    <td width="15%" align="right">{$fax_company.LABEL}:</td>
                    <td width="20%">{$fax_company.INPUT}</td>
                    <td>&nbsp;</td>	
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td align="right">{$date_fax.LABEL}:</td>
                    <td>{$date_fax.INPUT}</td>
                    <td align="right">{$filter.LABEL}</td> 
                    <td>{$filter.INPUT}</td>
                    <td align="left">
                        <input class="button" type="button" name="buscar" value="{$SEARCH}"  onClick="javascript:buscar_faxes_ajax('search')">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
	    <br />
            <table class="table_data" width="100%" border="0" cellspacing="0" cellpadding="0"  align="center">
                <tr class="table_navigation_row">
		    <td id="td_paginacion" class="table_navigation_row"></td>
                </tr>
                <tr>
                    <td id='td_contenido' vAlign='top'></td>
                </tr>
                <tr class="table_navigation_row">
		    <td id="td_paginacion1" class="table_navigation_row"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{literal}
<script type="text/javascript">

function buscar_faxes_ajax(accion)
{   
    if(existen_nodos()){
        primer_registro_mostrado = document.getElementById('primer_registro_mostrado_paginacion').value;
        ultimo_registro_mostrado = document.getElementById('ultimo_registro_mostrado_paginacion').value;
        total_registros          = document.getElementById('total_registros_paginacion').value;      
    }
    else{
        primer_registro_mostrado = 0;
        ultimo_registro_mostrado = 0;
        total_registros          = 0;
    }

    company_name = obtener_nodo_por_name('name_company').value;
    company_fax  = obtener_nodo_por_name('fax_company').value;
    fecha_fax    = obtener_nodo_por_name('date_fax').value;
    type_filter  = obtener_nodo_por_name('filter').value;
    
    xajax_faxes(company_name,company_fax,fecha_fax,primer_registro_mostrado,accion,type_filter);
}

function existen_nodos()
{
    primer_registro_mostrado = document.getElementById('primer_registro_mostrado_paginacion');
    ultimo_registro_mostrado = document.getElementById('ultimo_registro_mostrado_paginacion');
    total_registros          = document.getElementById('total_registros_paginacion'); 
   
    if(primer_registro_mostrado!=null && ultimo_registro_mostrado!=null && total_registros!=null){
        return true; 
    }else
        return false;
}

function obtener_nodo_por_name(nodo_name)
{
    nodos = document.getElementsByName(nodo_name);
    
    if(nodos)
        return nodos[0];
    else return null;
}

function elimimar_faxes()
{
    nodos = document.getElementsByTagName('input');
    csv_faxes = "";
    bandera = "";
    for(i=0; i<nodos.length; i++){
        nodoName = nodos[i].name;
        if(nodoName.substring(0,7)=='faxpdf_' && nodos[i].checked){
            csv_faxes += nodoName.substring(7) + ",";
            bandera ="seleccionado";
        }
    }
    if(bandera == "seleccionado"){
        if(existen_nodos()){
            primer_registro_mostrado = document.getElementById('primer_registro_mostrado_paginacion').value;
            ultimo_registro_mostrado = document.getElementById('ultimo_registro_mostrado_paginacion').value;
            total_registros          = document.getElementById('total_registros_paginacion').value;      
        }
        else{
            primer_registro_mostrado = 0;
            ultimo_registro_mostrado = 0;
            total_registros          = 0;
        }
        company_name             = obtener_nodo_por_name('name_company').value;
        company_fax              = obtener_nodo_por_name('fax_company').value;
        fecha_fax                = obtener_nodo_por_name('date_fax').value;
        type_filter              = obtener_nodo_por_name('filter').value;

        xajax_deleteFaxes(csv_faxes.substring(0,csv_faxes.length - 1),company_name,company_fax,fecha_fax,primer_registro_mostrado,type_filter);
    }
}
</script>
{/literal}
{literal}
<script type="text/javascript">
buscar_faxes_ajax('search');
</script>
{/literal}
