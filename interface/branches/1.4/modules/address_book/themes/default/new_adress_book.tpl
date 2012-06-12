<input type='hidden' name='id' value='{$ID}'>
<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/list.png" border="0" align="absmiddle">&nbsp;&nbsp;{$TITLE}</td>
        <td></td>
    </tr>
    <tr>
        <td align="left">
            {if $Show}
                <input class="button" type="submit" name="save" value="{$SAVE}">&nbsp;&nbsp;&nbsp;&nbsp;
            {elseif $Edit}
                <input class="button" type="submit" name="edit" value="{$EDIT}">&nbsp;&nbsp;&nbsp;&nbsp;
            {elseif $Commit}
                <input class="button" type="submit" name="commit" value="{$SAVE}">&nbsp;&nbsp;&nbsp;&nbsp;
            {/if}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
    <tr>
        <td colspan=2>
            <table width="100%" cellpadding="4" cellspacing="0" border="0" class="tabForm">
                <tr class="letra12" {$style_address_options}>
                    <td colspan='2'>
                        <input type="radio" name="address_book_options" id="new_contact" value="new_contact" {$check_new_contact} onclick="Activate_Option_Address_Book()" />
                        {$new_contact} &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="address_book_options" id="address_from_csv" value="address_from_csv" {$check_csv} onclick="Activate_Option_Address_Book()" />
                        {$address_from_csv}
                    </td>
                </tr>
                <tr id="tr_new_contact">
                    <td>
                        <table class="letra12" width="100%" cellpadding="4" cellspacing="0" border="0">
                            <tr>
                                <td align="left" width="20%"><b>{$name.LABEL}: <span  class="required">*</span></b></td>
                                <td class="required" align="left">{$name.INPUT}</td>
                            </tr>
                            <tr>
                                <td align="left" width="20%"><b>{$last_name.LABEL}: <span  class="required">*</span></b></td>
                                <td class="required" align="left">{$last_name.INPUT}</td>
                            </tr>
                            <tr id='tr_phone'>
                                <td align="left" width="20%"><b>{$telefono.LABEL}: <span id="span_phone" class="required">*</span></b></td>
                                <td class="required" align="left">{$telefono.INPUT}</td>
                            </tr>
                            <tr>
                                <td align="left"><b>{$email.LABEL}: </b></td>
                                <td align="left">{$email.INPUT}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr id="tr_from_csv">
                    <td>{$label_file}&nbsp;(file.csv):<span  class="required">*</span></td>
                    <td><input type='file' id='userfile' name='userfile'></td>
                    <td><a href="{$LINK}" name="link_download">{$DOWNLOAD}</a></td>
                </tr>
            </table>
            <table id="table_from_csv" width="100%" cellpadding="4" cellspacing="0" border="0" class="tabForm">
                <tr>
                    <td colspan='3'>{$HeaderFile}</td>
                </tr>
                <tr>
                    <td colspan='3'>{$AboutContacts}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{literal}
    <script type="text/javascript">
        Activate_Option_Address_Book();

        function Activate_Option_Address_Book()
        {
            var new_contact = document.getElementById('new_contact');
            var address_from_csv = document.getElementById('address_from_csv');
            if(new_contact.checked==true)
            {
                document.getElementById('tr_new_contact').style.display = '';
                document.getElementById('tr_from_csv').style.display = 'none';
                document.getElementById('table_from_csv').style.display = 'none';
            }
            else
            {
                document.getElementById('tr_new_contact').style.display = 'none';
                document.getElementById('tr_from_csv').style.display = '';
                document.getElementById('table_from_csv').style.display = '';
            }
        }
    </script>
{/literal}