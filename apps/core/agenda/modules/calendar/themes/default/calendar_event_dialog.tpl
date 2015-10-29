<table width="99%" border="0" style="padding:10px;">
    <tr class="letra12" height="40px" >
        <td align="left" width="90px"><b>{$event.LABEL}: <span  class="required">*</span></b></td>
        <td align="left" colspan="2">{$event.INPUT}</td>
    </tr>
    <tr class="letra12" id="desc">
        <td align="left"><b>{$description.LABEL}: </b></td>
        <td align="left" colspan="2">{$description.INPUT}</td>
    </tr>
    <tr class="letra12" height="40px">
        <td align="left" width="90px"><b>{$date.LABEL}: <span  class="required">*</span></b></td>
        <td align="left" width="175px" colspan=2 nowrap="nowrap">{$date.INPUT}</td>
    </tr>
    <tr class="letra12">
        <td align="left" width="90px"><b>{$to.LABEL}: <span  class="required">*</span></b></td>
        <td align="left" width="175px" colspan=2 nowrap="nowrap">{$to.INPUT}</td>
    </tr>
    <tr class="letra12" height="40px">
        <td align="left" width="90px"><b>{$Color}: <span  class="required">*</span></b></td>
        <td align="left" width="175px" colspan=2 nowrap="nowrap">
<!-- Start of Ed Color Picker -->
<span id="colorSelector" style="padding-top: 11px; display: inline;">
</span>
<!-- End of Ed Color Picker -->
        </td>
    </tr>
    <tr id="rowReminderEvent">
        <td align="left" colspan="3">
            <div id="divReminder">
                <input id="CheckBoxRemi" type="checkbox" class="CheckBoxClass" />
                <label id="lblCheckBoxRemi" for="CheckBoxRemi" class="CheckBoxLabelClass">{$Call_alert}</label>
            </div>
        </td>
    </tr>
    <tr class="letra12 remin"  colspan="3">
        <td align="right" colspan="3"><div id="label_call"></td>
    </tr>
    <tr class="letra12 remin" height="40px" id="check">
        <td align="left"><b>{$call_to.LABEL}: <span  class="required">*</span></b></td>
        <td align="left" colspan="2">{$call_to.INPUT}&nbsp;&nbsp;
            <span id="add_phone"><a href="#">{$add_phone}</a></span>
        </td>
    </tr>
    <tr class="letra12 remin subElemento" height="40px">
        <td align="left"><b>{$ReminderTime.LABEL}: <span  class="required">*</span></b></td>
        <td align="left">{$ReminderTime.INPUT}&nbsp;&nbsp;</td>
    </tr>
    <tr class="letra12 remin subElemento" height="40px">
        <td align="left" colspan="3">
            <b>{$tts.LABEL}: <span  class="required">*</span>&nbsp;&nbsp;&nbsp;</b>
            <b><span class="counter">140</span></b>
            <a id="listenTTS" style="cursor: pointer;">
                <img src="modules/{$module_name}/images/speaker.png" style="position: relative; right: 0px;" alt="{$Listen}" title="{$Listen_here}"/>
            </a>
            <div>{$tts.INPUT}</div>
        </td>
    </tr>
    <tr id="rowNotificateEmail">
        <td align="left" colspan="3">
            <div id="divNotification">
                <input id="CheckBoxNoti" type="checkbox" class="CheckBoxClass" />
                <label id="lblCheckBoxNoti" for="CheckBoxNoti" class="CheckBoxLabelClass">{$Notification_Alert}</label>
            </div>
        </td>
    </tr>
    <tr class="letra12 notif" id="notification_email">
        <td align="left" colspan="3">
            <div>
                <b id="notification_email_label">{$notification_email}: <span  class="required">*</span></b>
            </div>
            <div class="ui-widget">
                <textarea id="tags" cols="48px" rows="2" style="margin:6px; color: #333333; width: 365px; height: 36px; "></textarea>
            </div>
        </td>
    </tr>
</table>
<div class="letra12 notif" id="email_to" align="center">
    <table id="grilla" width="90%" border="0">
	    <thead>
	       <tr>
	           <td>&nbsp;</td>
	           <td class="letra12" align="center" style="color:#666666; font-weight:bold;">{$LBL_CONTACT_NAME}</td>
               <td class="letra12" align="center" style="color:#666666; font-weight:bold;">{$LBL_CONTACT_EMAIL}</td>
               <td>&nbsp;</td>
	       </tr>
	    </thead>
	    <tbody>
	    </tbody>
    </table>
</div>
<script>
   $('#colorSelector').ed_colorpicker();
</script>
