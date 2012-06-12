<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/conference.png" border="0" align="absmiddle">&nbsp;&nbsp;{$TITLE}</td>
    </tr>
    <tr>
        <td align="left">
            {if $Show}
                <input class="button" type="submit" name="add_conference" value="{$SAVE}">&nbsp;&nbsp;&nbsp;&nbsp;
            {/if}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>
    </tr>
    <tr>
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    </tr>
    <tr>
        <table width="100%" cellpadding="4" cellspacing="0" border="0" class="tabForm">
            <tr>
                <td align="left" width="20%"><b>{$conference_name.LABEL}: <span  class="required">*</span></b></td>
                <td class="required" align="left">{$conference_name.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$conference_owner.LABEL}: </b></td>
                <td align="left">{$conference_owner.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$conference_number.LABEL}: <span  class="required">*</span></b></td>
                <td align="left">{$conference_number.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$moderator_pin.LABEL}: </b></td>
                <td align="left">{$moderator_pin.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$moderator_options_1.LABEL}</b></td>
                <td align="left">
                    {$moderator_options_1.INPUT}{$announce}&nbsp;&nbsp;&nbsp;
                    {$moderator_options_2.INPUT}{$record}
                </td>
            </tr>
            <tr>
                <td align="left"><b>{$user_pin.LABEL}: </b></td>
                <td align="left">{$user_pin.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$user_options_1.LABEL}: </b></td>
                <td align="left">
                    {$user_options_1.INPUT}{$announce}&nbsp;&nbsp;&nbsp;
                    {$user_options_2.INPUT}{$listen_only}&nbsp;&nbsp;&nbsp;
                    {$user_options_3.INPUT}{$wait_for_leader}
                </td>
            </tr>
            <tr>
                <td align="left"><b>{$start_time.LABEL}: <span  class="required">*</span></b></td>
                <td align="left">{$start_time.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$duration.LABEL}: </b></td>
                <td align="left">
                    {$duration.INPUT}&nbsp;:
                    {$duration_min.INPUT}
                </td>
            </tr>
<!--
            <tr>
                <td align="left"><b>{$recurs.LABEL}: </b></td>
                <td align="left">
                    {$recurs.INPUT}&nbsp;&nbsp;&nbsp;
                    {$reoccurs_period.LABEL}:
                    {$reoccurs_period.INPUT}
                    {$reoccurs_days.LABEL}
                    {$reoccurs_days.INPUT}
                </td>
            </tr>
-->
            <tr>
                <td align="left"><b>{$max_participants.LABEL}: <span  class="required">*</span></b></td>
                <td align="left">{$max_participants.INPUT}</td>
            </tr>
        </table>
    </tr>
</table>