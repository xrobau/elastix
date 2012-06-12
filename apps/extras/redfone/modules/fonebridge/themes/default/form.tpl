<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td>
            <table style="font-size: 16px;" width="100%" border="0" >
                <tr class="letra12">
                    <td align="left"><input class="button" type="submit" name="configure" value="{$CONFIGURE}"></td>
                    <td align="right"><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                            <td width="50%" valign='top'>
                                <table style="font-size: 16px;" border="0" width="99%">
                                    <tr class="letra12">
                                        <td align="left" colspan="2"><b><u>{$general_settings}</u></b></td>
                                    </tr>
                                    <tr class="letra12">
                                        <td align="left"><b>{$status_label}: </b></td>
                                        <td align="left">{$status_info}</td>
                                    </tr>
                                    <tr class="letra12">
                                        <td align="left"><b>{$phone_bridge_ip.LABEL}: </b></td>
                                        <td align="left">{$phone_bridge_ip.INPUT}</td>
                                    </tr>
                                    <tr class="letra12">
                                        <td align="left"><b>{$server_mac.LABEL}: </b></td>
                                        <td align="left">{$server_mac.INPUT}</td>
                                    </tr>
                                    <tr class="letra12">
                                        <td align="left"><b>{$port_for_TDMoE.LABEL}: </b></td>
                                        <td align="left">{$port_for_TDMoE.INPUT}</td>
                                    </tr>
                                    <tr class="letra12">
                                        <td align="left"><b>{$span_timing}: </b></td>
                                        <td align="left"><input name="timing_priority" id='by_spans' value="by_spans" {$by_spans_checked} type="radio" onclick="Activate_Option_Spams()" >&nbsp;{$by_spans}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name="timing_priority" id='internal' value="internal" {$internal_checked} type="radio" onclick="Activate_Option_Spams()">&nbsp;{$internal}</td>
                                    </tr>
                                    <tr class="letra12" id="spans_order">
                                        <td align="left"><b>{$spans}</b>:</td>
                                        <td align="left">{$priority1.INPUT}&nbsp;{$priority2.INPUT}&nbsp;{$priority3.INPUT}&nbsp;{$priority4.INPUT}</td>
                                    </tr>
                                </table>
                            </td>
                            <td width="50%" valign='top'>
                                <table style="font-size: 16px;" border="0" width="80%">
                                    <tr class="letra12">
                                        <td align="left" colspan="5"><b><u>{$span_config}</u></b></td>
                                    </tr>
                                    <tr class="letra12">
                                            <td><b>&nbsp;</b></td>
                                            <td><b>{$span_type}</b></td>
                                            <td><b>{$span_framing}</b></td>
                                            <td><b>{$span_encoding}</b></td>
                                            <td><b>{$span_extra}</b></td>
                                    </tr>
                                    <tr class="letra12">
                                            <td><b>{$span_1}</b></td>
                                            <td align="left">
                                                <select name="span1_type" id="select_span1_type" onchange="span_configuration_update(1)">{$select_span1_type}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span1_framing" id="select_span1_framing">{$select_span1_framing}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span1_encoding" id="select_span1_encoding">{$select_span1_encoding}</select>
                                            </td>
                                            <td align="left">
                                                {$span1_extra.INPUT}
                                            </td>
                                    </tr>
                                    <tr class="letra12">
                                            <td><b>{$span_2}</b></td>
                                            <td align="left">
                                                <select name="span2_type" id="select_span2_type" onchange="span_configuration_update(2)">{$select_span2_type}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span2_framing" id="select_span2_framing">{$select_span2_framing}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span2_encoding" id="select_span2_encoding">{$select_span2_encoding}</select>
                                            </td>
                                            <td align="left">
                                                {$span2_extra.INPUT}
                                            </td>
                                    </td>
                                    </tr>
                                    <tr class="letra12">
                                            <td><b>{$span_3}</b></td>
                                            <td align="left">
                                                <select name="span3_type" id="select_span3_type" onchange="span_configuration_update(3)">{$select_span3_type}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span3_framing" id="select_span3_framing">{$select_span3_framing}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span3_encoding" id="select_span3_encoding">{$select_span3_encoding}</select>
                                            </td>
                                            <td align="left">
                                                {$span3_extra.INPUT}
                                            </td>
                                    </tr>
                                    <tr class="letra12">
                                            <td><b>{$span_4}</b></td>
                                            <td align="left">
                                                <select name="span4_type" id="select_span4_type" onchange="span_configuration_update(4)">{$select_span4_type}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span4_framing" id="select_span4_framing">{$select_span4_framing}</select>
                                            </td>
                                            <td align="left">
                                                <select name="span4_encoding" id="select_span4_encoding">{$select_span4_encoding}</select>
                                            </td>
                                            <td align="left">
                                                {$span4_extra.INPUT}
                                            </td>
                                    </tr>
                            </table>
                            </td>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
      <tr>
</table>
{literal}
<script type="text/javascript">

    function span_configuration_update(row)
    {
        var id_select_type      = "select_span" + row + "_type";
        var id_select_framing   = "select_span" + row + "_framing";
        var id_select_encoding    = "select_span" + row + "_encoding";

        var val_select_type = '';
        var val_select_framing = '';
        var val_select_encoding = '';

        var index = -1;

        if(document.getElementById(id_select_type) !=null)
        {
            index = document.getElementById(id_select_type).selectedIndex;
            if(index>=0)
                val_select_type = document.getElementById(id_select_type).options[index].value;
        }

        if(document.getElementById(id_select_framing) !=null)
        {
            index = document.getElementById(id_select_framing).selectedIndex;
            if(index>=0)
                val_select_framing = document.getElementById(id_select_framing).options[index].value;
        }

        if(document.getElementById(id_select_encoding) !=null)
        {
            index = document.getElementById(id_select_encoding).selectedIndex;
            if(index>=0)
                val_select_encoding = document.getElementById(id_select_encoding).options[index].value;
        }

        xajax_span_configuration_update(id_select_type, val_select_type, id_select_framing, val_select_framing, id_select_encoding, val_select_encoding);
    }

    function Activate_Option_Spams()
    {
        var by_spans = document.getElementById('by_spans');

        if(by_spans.checked==true)
            document.getElementById('spans_order').style.display = "";
        else
            document.getElementById('spans_order').style.display = "none";
    }
    Activate_Option_Spams();
</script>
{/literal}