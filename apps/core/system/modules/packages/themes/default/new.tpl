    <table border='0' cellpadding='0' callspacing='0' width='100%' height='44'>
        <tr class="letra12">
            <td width='70%'>{$Name} &nbsp;
                <input type='text' size='50' id='nombre_paquete' name='nombre_paquete' value='{$nombre_paquete}' /> &nbsp;
                <input type='submit' class='button' name='submit_nombre' value='{$Search}' />                
            </td>
            <td rowspan='2' id='relojArena'> 
            </td>
        </tr>
        <tr class="letra12">
            <td width='200'>{$Status} &nbsp;
                <select name='submitInstalado' onchange='javascript:submit();'> 
                    <option value='installed' {$opcion2}>{$PackageInstalled}</option>
                    <option value='all' {$opcion1}>{$AllPackage}</option>
                </select>&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='button' onclick='mostrarReloj();' class='button' name='update_repositorios' value='{$RepositoriesUpdate}' />
            </td>
        </tr>
    </table>
    <input type='hidden' id='estaus_reloj' value='apagado' />
{literal}
<script type='text/javascript'>
    function mostrarReloj()
    {
        var nodoReloj = document.getElementById('relojArena');
        var estatus   = document.getElementById('estaus_reloj');
        if(estatus.value=='apagado'){
            estatus.value='prendido';
            nodoReloj.innerHTML = "<img src='modules/packages/images/hourglass.gif' align='absmiddle' /> <br /> <font style='font-size:12px; color:red'>{/literal}{$UpdatingRepositories}{literal}...</font>";
            xajax_actualizarRepositorios();
        }
        else alert("{/literal}{$accionEnProceso}{literal}");
    }
    function installPackage(paquete)
    {
        var nodoReloj = document.getElementById('relojArena');
        var estatus   = document.getElementById('estaus_reloj');
        if(estatus.value=='apagado'){
            estatus.value='prendido';
            nodoReloj.innerHTML = "<img src='images/hourglass.gif' align='absmiddle' /> <br /> <font style='font-size:12px; color:red'>{/literal}{$InstallPackage}{literal}: "+ paquete +"...</font>";
            xajax_installPaquete(paquete);
        }
        else alert("{/literal}{$accionEnProceso}{literal}");
    }
</script>
{/literal}
