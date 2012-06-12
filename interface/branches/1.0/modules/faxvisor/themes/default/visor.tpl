{$javascript_xajax}
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle" colspan='3'>&nbsp;&nbsp;<img src="images/kfaxview.png" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
    </tr>
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tr>
                    <td>{$name_company.LABEL}:</td>
                    <td>{$name_company.INPUT}</td>	
                    <td>{$fax_company.LABEL}:</td>
                    <td>{$fax_company.INPUT}</td>	
                    <td>{$date_fax.LABEL}:</td>
                    <td>{$date_fax.INPUT}</td>	
                    <td align="center" colspan='2'>
                        <input class="button" type="button" name="buscar" value="{$SEARCH}"  onClick="javascript:buscar_faxes_ajax('search')">
                    </td>	
                </tr>
            </table>
        </td>
    </tr>
    <tr>        
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0"  align="center" height="350">
                <tr class="table_navigation_row">
                    <td id="td_paginacion" class="table_navigation_row" height='28'></td>
                </tr>
                <tr>
                    <td id='td_contenido' vAlign='top'></td>
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
   company_name             = obtener_nodo_por_name('name_company').value;
   company_fax              = obtener_nodo_por_name('fax_company').value;
   fecha_fax                = obtener_nodo_por_name('date_fax').value;

   xajax_faxes(company_name,company_fax,fecha_fax,primer_registro_mostrado,accion);
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

        xajax_deleteFaxes(csv_faxes.substring(0,csv_faxes.length - 1),company_name,company_fax,fecha_fax,primer_registro_mostrado);
    }
}
</script>
{/literal}
{literal}
<script type="text/javascript">
buscar_faxes_ajax('search');
</script>
{/literal}