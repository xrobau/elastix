<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        {if !$EDIT}
        <td align="left"><input class="button" type="submit" name="upload" value="{$UPLOAD}"></td>
        {else}
        <td align="left"><input class="button" type="submit" name="edit" value="{$EDIT_PARAMETERS}"></td>
        {/if}
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="100%" >
    <tr class="letra12">
        <td align="left"><b>{$organization.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$organization.INPUT}</td>
        <td align="left"><b>{$country.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$country.INPUT}</td>

    </tr>
    <tr class="letra12">
        <td align="left"><b>{$department.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$department.INPUT}</td>
        <td align="left"><b>{$email.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$email.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left"><b>{$locality.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$locality.INPUT}</td>
        <td align="left"><b>{$phone.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$phone.INPUT}</td>

    </tr>
    <tr class="letra12">
        <td align="left"><b>{$state.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$state.INPUT}</td>
    </tr>


    <tr class="letra12">
    </tr>
    <tr class="letra12">
    </tr>
    <tr class="letra12">
    </tr>
<input type='hidden' name='command' value='{$command}'/>
</table>
