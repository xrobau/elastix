<br />
<table class="tabForm" style="font-size: 16px;" width="100%">
    <tr>
        <td width="10%" align="left" valign="top" style="font-size:64%;">
            <div style="margin: 0px 10px 6px 10px;" valign="middle">
                <div class='fc-button-add ui-state-default  ui-corner-left ui-corner-right' id="btnNewEvent" style="height: 25px;" align="center">
                    <a id='add_news' onclick='displayNewEvent(event);'>
                        {$CreateEvent}
                    </a>
                </div>
            </div>
            <div id="datepicker"></div>
            <div id="icals" class="ui-datepicker-inline ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all">
                <div class="ui-datepicker-header ui-widget-header ui-helper-clearfix ui-corner-all title_size">{$Export_Calendar}</div>
                <div class="content_ical">
                    <a href="index.php?menu={$module_name}&action=download_icals&rawmode=yes">
                            <span>{$ical}</span>
                    </a>
                </div>
            </div>
        </td>
        <td align="right" width="90%" >
            <div id='calendar'></div>
        </td>
    </tr>
</table>
<div id="facebox_form">
</div>
<div id="box" style="display:none;">
    <div class="popup">
        <table>
            <tr>
                <td class="tl"/>
                <td class="b"/>
                <td class="tr"/>
            </tr>
            <tr>
                <td class="b"/>
                <td class="body">
                    <div class="content_box">
                        <div id="table_box">
                            <table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
                                <tr height="40px" class="moduleTitle titleBox">
                                    <td class="moduleTitle" valign="middle" colspan='2'>&nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle">&nbsp;&nbsp;<span id="title_box"></span></td>
                                </tr>
                                <tr class="letra12">
                                    <td align="left">
                                        <div id="new_box" style="display:none">
                                            <input id="save" class="button" type="submit" name="save_new" value="{$SAVE}">&nbsp;&nbsp;
                                            <input id="cancel" class="button cancel" type="button" name="cancel" value="{$CANCEL}">
                                        </div>
                                        <div id="view_box" style="display:none">
                                            <input id="edit" class="button" type="button" name="edit" value="{$EDIT}">
                                            <input id="delete" class="button" type="button" name="delete" value="{$DELETE}">
                                            <input id="cancel" class="button cancel" type="button" name="cancel" value="{$CANCEL}">
                                        </div>
                                        <div id="edit_box" style="display:none">
                                            <input id="save" class="button" type="submit" name="save_edit" value="{$SAVE}">&nbsp;&nbsp;
                                            <input id="cancel" class="button cancel" type="button" name="cancel" value="{$CANCEL}">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <table style="font-size: 16px;" width="99%" border="0">
                                <tr class="letra12" height="30px" >
                                    <td align="left" width="90px"><b>{$event.LABEL}: <span  class="required">*</span></b></td>
                                    <td align="left" colspan="3">{$event.INPUT}</td>
                                </tr>
                                <tr class="letra12" id="desc" style="display: none;">
                                    <td align="left"><b>{$description.LABEL}: </b></td>
                                    <td align="left" colspan="3">{$description.INPUT}</td>
                                </tr>
                                <tr class="letra12" height="30px">
                                    <td align="left" width="90px"><b>{$Start_date}: <span  class="required">*</span></b></td>
                                    <td align="left" width="175px">{$date.INPUT}</td>
                                    <td align="left"><b>{$Color}:</b></td>
                                    <td align="left">
                                        <div id="colorSelector"><div style="background-color: #3366CC"></div></div>
                                    </td>
                                </tr>
                                <tr class="letra12">
                                    <td align="left" width="90px"><b>{$End_date}: <span  class="required">*</span></b></td>
                                    <td align="left" width="175px" colspan="3">{$to.INPUT}</td>
                                </tr>
                                <tr>
                                    <td align="left" colspan="4">
                                        <div class="divCorners" style="-moz-border-radius: 10px; -webkit-border-radius: 10px;  border-radius: 10px;" id="divReminder">
                                            <div class="sombreado">
                                                <input id="CheckBoxRemi" type="checkbox" class="CheckBoxClass"/>
                                                <label id="lblCheckBoxRemi" for="CheckBoxRemi" class="CheckBoxLabelClass">{$Call_alert}</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" colspan="4">
                                        <table style="font-size: 16px; margin-left:10px;" width="99%" border="0">
                                            <tr class="letra12" style="display: none;"> 
                                                <td align="left" width="90px"><b>{$reminder.LABEL}: </b></td>
                                                <td align="left" id="remi">{$reminder.INPUT}</td>
                                            </tr>
                                            <tr class="letra12 remin"  style="{$visibility_alert}">
                                                <td align="right" colspan="2"><div id="label_call"></td>
                                            </tr>
                                            <tr class="letra12 remin" height="30px" id="check" style="{$visibility_alert}">
                                                <td align="left"><b>{$call_to.LABEL}: <span  class="required">*</span></b></td>
                                                <td align="left">{$call_to.INPUT}&nbsp;&nbsp;
                                                    <span id="add_phone">
                                                            <a href="javascript: popup_phone_number('?menu={$module_name}&amp;action=phone_numbers&amp;rawmode=yes');">{$add_phone}</a>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr class="letra12 remin" height="30px" style="{$visibility_alert}">
                                                <td align="left"><b>{$ReminderTime.LABEL}: <span  class="required">*</span></b></td>
                                                <td align="left">{$ReminderTime.INPUT}&nbsp;&nbsp;</td>
                                            </tr>
                                            <tr class="letra12 remin" height="30px" style="{$visibility_alert}">
                                                <td align="left" colspan="2" height="80px">
                                                    <div>
                                                        <div style="float: left;">
                                                            <b>{$tts.LABEL}: <span  class="required">*</span>&nbsp;&nbsp;&nbsp;</b>
                                                        </div>
                                                        <div align="right">
                                                            <b><span class="counter">140</span></b>
                                                            <a id="listenTTS" style="cursor: pointer;">
                                                                <img src="modules/{$module_name}/images/speaker.png" style="position: relative; right: 30px;" alt="{$Listen}" title="{$Listen_here}"/>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div>{$tts.INPUT}&nbsp;&nbsp;</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" colspan="4">
                                        <div class="divCorners" style="-moz-border-radius: 10px; -webkit-border-radius: 10px;  border-radius: 10px;" id="divNotification">
                                            <div class="sombreado">
                                                <input id="CheckBoxNoti" type="checkbox" class="CheckBoxClass"/>
                                                <label id="lblCheckBoxNoti" for="CheckBoxNoti" class="CheckBoxLabelClass">{$Notification_Alert}</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" colspan="4">
                                        <table style="font-size: 16px; margin-left:10px;" width="99%" border="0">
                                            <tr class="letra12" style="display: none;">
                                                <td align="left" width="90px"><b>{$notification.LABEL}: </b></td>
                                                <td align="left" id="noti">{$notification.INPUT}</td>
                                            </tr>
                                            <tr class="letra12" id="notification_email" style="display: none;">
                                                <td align="left" colspan="2">
                                                    <div>
                                                        <b id="notification_email_label">{$notification_email}: <span  class="required">*</span></b>
                                                    </div>
                                                    <div class="ui-widget">
                                                        <textarea id="tags" cols="48px" rows="2" style="color: #333333; font-size:12px; width: 352px;"></textarea>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="letra12" id="email_to" style="{$visibility_emails}">
                                                <td align="center" class="noti_email" colspan="2">
                                                    <table id="grilla" style="font-size: 16px;" width="90%" border="0">
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <input class="button" type="hidden" name="id_event" value="" id="id_event" />
                            <input type="hidden" id="phone_type" name="phone_type" value="" />
                            <input type="hidden" id="phone_id" name="phone_id" value="" />
                            <input type="text" id="emails" name="emails" value="" style="display: none;" />
                        </div>
                    </div>
                    <div class="footer">
                        <a class="close_box" style="cursor: pointer;">
                        <img src="modules/{$module_name}/images/closelabel.gif" title="close" class="close_image" />
                        </a>
                    </div>
                </td>
                <td class="b"/>
            </tr>
            <tr>
                <td class="bl"/>
                <td class="b"/>
                <td class="br"/>
            </tr>
        </table>
    </div>
</div>
<input class="button" type="hidden" name="id" value="{$ID}" id="id" />
<input class="button" type="hidden" name="lblEdit" value="{$LBL_EDIT}" id="lblEdit" />
<input class="button" type="hidden" name="lblLoading" value="{$LBL_LOADING}" id="lblLoading">
<input class="button" type="hidden" name="lblDeleting" value="{$LBL_DELETING}" id="lblDeleting">
<input class="button" type="hidden" name="lblSending" value="{$LBL_SENDING}" id="lblSending">
<input class="button" type="hidden" name="typeen" value="{$START_TYPE}...." id="typeen" />
<input class="button" type="hidden" name="dateServer" value="{$DATE_SERVER}" id="dateServer" />
<input class="button" type="hidden" name="colorHex" id="colorHex" value="#3366CC" />
