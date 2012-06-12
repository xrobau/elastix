{literal}
<script type="text/javascript">
function ChequearTodos(chkbox)
{
    for (var i=0;i < document.getElementById("backup_form").elements.length;i++)
    {
        var elemento = document.getElementById("backup_form").elements[i];
        if (elemento.type == "checkbox")
        {
            if(!elemento.disabled)
                elemento.checked = chkbox.checked
        }
    }
}
function ChequearTabla(chkbox, id)
{
    //Si desmarco el checkbox desmarcar all options global
    if(!chkbox.checked)
        document.getElementById('backup_total').checked = chkbox.checked;

    var arr_chk = document.getElementById(id).getElementsByTagName("INPUT");
    for(var i=0; i<arr_chk.length; i++)
    {
        if(!arr_chk[i].disabled)
            arr_chk[i].checked = chkbox.checked;
    }
}
function VerificarCheck(chkbox, id)
{
    if(!chkbox.checked)
    {
        //Descarmar all options de la tabla
        document.getElementById(id).checked = chkbox.checked;
        //Descarmar all options global
        document.getElementById('backup_total').checked = chkbox.checked;
    }
}
</script>
{/literal}
<form method="POST" enctype="multipart/form-data" id="backup_form">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/1x1.gif" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
</tr>
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">{$ERROR_MSG}</td>
     </tr>
      <tr>
        <td align="left">
          <input class="button" type="submit" name="back" value="{$BACK}">
          <input class="button" type="submit" name="process" value="{$PROCESS}">
        </td>
     </tr>
     <tr>
        <td>{$WARNING}</td>
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <table width="99%" BORDER=0>
      <tr>
        <td colspan=4><INPUT type="checkbox" name="backup_total" id="backup_total" onClick=ChequearTodos(this);><b>{$LBL_TODOS}</b></td>
      </tr>
      <tr>
        <td width="25%">
            <table id="table_endpoint" width="100%" height="100px" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <th>
                <tr>
                    <td height="10px"><b>{$ENDPOINT}</b></td>
                </tr>
                <tr>
                    <td height="10px"><INPUT type="checkbox" name="backup_endpoint" id="backup_endpoint" onClick="ChequearTabla(this, 'table_endpoint');"><b>{$TODO_ENDPOINT}</b></td>
                </tr>
            </th>
            <tbody>
                {foreach key=key item=item from=$backup_endpoint}
                <tr>
                    <td><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_endpoint');" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>
                </tr>
                {/foreach}
            </tbody>
            </table>
        </td>
        <td width="25%">
            <table id="table_fax" width="100%" height="100px" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <th>
                <tr>
                    <td height="10px"><b>{$FAX}</b></td>
                </tr>
                <tr>
                    <td height="10px"><INPUT type="checkbox" name="backup_fax" id="backup_fax" onClick="ChequearTabla(this, 'table_fax');"><b>{$TODO_FAX}</b></td>
                </tr>
            </th>
            <tbody>
                {foreach key=key item=item from=$backup_fax}
                <tr>
                    <td><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_fax');" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>
                </tr>
                {/foreach}
            </tbody>
            </table>
        </td>
        <td width="25%">
            <table id="table_email" width="100%" height="100px" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <th>
                <tr>
                    <td height="10px"><b>{$EMAIL}</b></td>
                </tr>
                <tr>
                    <td height="10px"><INPUT type="checkbox" name="backup_email" id="backup_email" onClick="ChequearTabla(this, 'table_email');"><b>{$TODO_EMAIL}</b></td>
                </tr>
            </th>
            <tbody>
                {foreach key=key item=item from=$backup_email}
                <tr>
                    <td><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_email');" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>
                </tr>
                {/foreach}
            </tbody>
            </table>
        </td>
      </tr>
      <tr>
        <td width="25%">
            <table id="table_asterisk" width="100%" height="250px" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <th>
                <tr>
                    <td height="10px"><b>{$ASTERISK}</b></td>
                </tr>
                <tr>
                    <td height="10px"><INPUT type="checkbox" name="backup_asterisk" id="backup_asterisk" onClick="ChequearTabla(this, 'table_asterisk');"><b>{$TODO_ASTERISK}</b></td>
                </tr>
            </th>
            <tbody>
                {foreach key=key item=item from=$backup_asterisk}
                <tr>
                    <td><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_asterisk');" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>
                </tr>
                {/foreach}
            </tbody>
            </table>
        </td>
        <td width="25%">
            <table id="table_others" width="100%" height="250px" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <th>
                <tr>
                    <td height="10px"><b>{$OTROS}</b></td>
                </tr>
                <tr>
                    <td height="10px"><INPUT type="checkbox" name="backup_others" id="backup_others" onClick="ChequearTabla(this, 'table_others');"><b>{$TODO_OTROS}</b></td>
                </tr>
            </th>
            <tbody>
                {foreach key=key item=item from=$backup_otros}
                <tr>
                    <td><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_others');" {$item.check}>{$item.desc}&nbsp;{$item.msg}</td>
                </tr>
                {/foreach}
            </tbody>
            </table>
        </td>
      </tr>
    </table>
  </td>
</tr>
</table>
<INPUT type="hidden" name="option_url" id="option_url" value="{$OPTION_URL}">
<input type='hidden' name='backup_file' value='{$BACKUP_FILE}'></td>
</form>