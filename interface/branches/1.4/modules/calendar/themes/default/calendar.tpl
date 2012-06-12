<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/calendar.png" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table class="tabForm" style="font-size: 16px;" width="100%" >
      <tr>
        <td>{$calendar}</td>
      </tr>
    </table>
  </td>
</tr>
</table>
{literal}
<script type="text/javascript">
    function display_calendar()
    {
        var val_month = 1, val_year=2000;

        var select_year = document.getElementById("select_year");
        if(select_year != null)
        {
            for (var i = 0; i < select_year.options.length; i++)
                if (select_year.options[ i ].selected)
                    val_year = select_year.options[ i ].value;
        }

        var select_month = document.getElementById("select_month");
        if(select_month != null)
        {
            for (var i = 0; i < select_month.options.length; i++)
                if (select_month.options[ i ].selected)
                    val_month = select_month.options[ i ].value;
        }

        window.location = "index.php?action=display&year="+val_year+"&month="+val_month;
    }

    function Mostrar_Ocultar_Call_To() {
        var CheckBox = document.getElementById("asterisk_call");
        var HideTRs = document.getElementById("tr_call_to");
        if(CheckBox.checked)
            HideTRs.style.display = "none";
        else{
            HideTRs.style.display = 'table-row';
        }
    }

    function popup_phone_number(url_popup){
        var ancho = 600;
        var alto = 400;
        my_window = window.open(url_popup,"my_window","width="+ancho+",height="+alto+",location=yes,status=yes,resizable=yes,scrollbars=yes,fullscreen=no,toolbar=yes");
        my_window.moveTo((screen.width-ancho)/2,(screen.height-alto)/2);
        my_window.document.close();
        
    }
</script>
{/literal}
