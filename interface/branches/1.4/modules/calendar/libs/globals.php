<?php
/*
   Copyright 2007 Sean Proctor

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

global $arrLang, $languages, $month_names, $day_names, $short_month_names, $event_types;
global $menu, $options, $view_events, $general, $search_lang;

$languages = array('de', 'en', 'es', 'it', 'ja', 'nl');

$month_names = array(
                1 => _($arrLang['January']),
                _($arrLang['February']),
                _($arrLang['March']),
                _($arrLang['April']),
                _($arrLang['May_']),
                _($arrLang['June']),
                _($arrLang['July']),
                _($arrLang['August']),
                _($arrLang['September']),
                _($arrLang['October']),
                _($arrLang['November']),
                _($arrLang['December']),
                );

$day_names = array(
                _($arrLang['Sunday']),
        _($arrLang['Monday']),
        _($arrLang['Tuesday']),
        _($arrLang['Wednesday']),
        _($arrLang['Thursday']),
        _($arrLang['Friday']),
        _($arrLang['Saturday']),
                );

$short_month_names = array(
        1 => _($arrLang['Jan']),
        _($arrLang['Feb']),
        _($arrLang['Mar']),
        _($arrLang['Apr']),
        _($arrLang['May']),
        _($arrLang['Jun']),
        _($arrLang['Jul']),
        _($arrLang['Aug']),
        _($arrLang['Sep']),
        _($arrLang['Oct']),
        _($arrLang['Nov']),
        _($arrLang['Dec']),
                );

$event_types = array(
                1=> _($arrLang['Normal']),
                /*2=>_($arrLang['Full Day']),
                3=>_($arrLang['To Be Announced']),
                4=>_($arrLang['No Time']),*/
                5=>_($arrLang['Weekly']),
                6=>_($arrLang['Monthly']),
                );

$menu = array(
            'Add Event' => $arrLang['Add Event'],
            'Search' => $arrLang['Search'],
            'Back to Calendar' => $arrLang['Back to Calendar'],
            'View Date' => $arrLang['View Date'],
            );

$options = array(
            'next month' => $arrLang['next month'],
            'next year' => $arrLang['next year'],
            'last year' => $arrLang['last year'],
            'last month' => $arrLang['last month'],
            );

$view_events = array(
            'Title' => $arrLang['Title'],
            'Time' => $arrLang['Time'],
            'Description' => $arrLang['Description'],
            'Delete Selected' => $arrLang['Delete Selected'],
            '(No subject)' => $arrLang['(No subject)'],
            'Modify' => $arrLang['Modify'],
            'No events on this day' => $arrLang['No events on this day'],
            'Date' => $arrLang['Date'],
            'Delete' => $arrLang['Delete'],
            'Error while removing an event' => $arrLang['Error while removing an event'],
            'Removed item' => $arrLang['Removed item'],
            'Could not remove item' => $arrLang['Could not remove item'],
            'No items selected' => $arrLang['No items selected'],
            'Adding event to calendar' => $arrLang['Adding event to calendar'],
            'Date of event' => $arrLang['Date of event'],
            'Date multiple day event ends' => $arrLang['Date multiple day event ends'],
            'Event type' => $arrLang['Event type'],
            'Subject' => $arrLang['Subject'],
            'chars max' => $arrLang['chars max'],
            'Asterisk Call Me' => $arrLang['Asterisk Call Me'],
            'Submit Event' => $arrLang['Submit Event'],
            'No day was given' => $arrLang['No day was given'],
            'No month was given' => $arrLang['No month was given'],
            'No year was given' => $arrLang['No year was given'],
            'No hour was given' => $arrLang['No hour was given'],
            'No minute was given' => $arrLang['No minute was given'],
            'No type of event was given' => $arrLang['No type of event was given'],
            'No end day was given' => $arrLang['No end day was given'],
            'No end month was given' => $arrLang['No end month was given'],
            'No end year was given' => $arrLang['No end year was given'],
            'Your subject was too long' => $arrLang['Your subject was too long'],
            'characters max' => $arrLang['characters max'],
            'The start of the event cannot be after the end of the event' => $arrLang['The start of the event cannot be after the end of the event'],
            'Error processing event' => $arrLang['Error processing event'],
            'No changes were made' => $arrLang['No changes were made'],
            'Date updated' => $arrLang['Date updated'],
            'Editing Event' => $arrLang['Editing Event'],
            'Validation Error' => $arrLang['Validation Error'],
            'yes' => $arrLang['yes'],
            'no' => $arrLang['no'],
            'Recordings' => $arrLang['Recordings'],
            'To create new recordings click' => $arrLang['To create new recordings click'],
            'Here' => $arrLang['Here'],
            'No Recording was given, if no exists you must first create a new recording' => $arrLang['No Recording was given, if no exists you must first create a new recording'],
            'Call to' => $arrLang['Call to'],
            'To add phone number from address book click' => $arrLang['To add phone number from address book click'],
            );

$general = array(
            'year view not yet implemented' => $arrLang['year view not yet implemented'],
            'Error' => $arrLang['Error'],
            'Software Error' => $arrLang['Software Error'],
            'Message:' => $arrLang['Message:'],
            'Backtrace' => $arrLang['Backtrace'],
            'PM' => $arrLang['PM'],
            'AM' => $arrLang['AM'],
            'Error in get_events_by_date' => $arrLang['Error in get_events_by_date'],
            'Error in get_event_by_id' => $arrLang['Error in get_event_by_id'],
            "item doesn't exist!" => $arrLang["item doesn't exist!"],
            'Html is not a valid Html class' => $arrLang['Html is not a valid Html class'],
            'Invalid class' => $arrLang['Invalid class'],
            'Error reading options' => $arrLang['Error reading options'],
            'Updated options' => $arrLang['Updated options'],
            'Invalid action' => $arrLang['Invalid action'],
            'That year is too far in the past' => $arrLang['That year is too far in the past'],
            'That year is too far in the future' => $arrLang['That year is too far in the future'],
            );

$search_lang = array(
            'Start Date' => $arrLang['Start Date'],
            'Subject' => $arrLang['Subject'],
            'Ascending' => $arrLang['Ascending'],
            'Decending' => $arrLang['Decending'],
            'Invalid sort option' => $arrLang['Invalid sort option'],
            'Invalid order option' => $arrLang['Invalid order option'],
            'Encountered an error while searching' => $arrLang['Encountered an error while searching'],
            'No events matched your search criteria' => $arrLang['No events matched your search criteria'],
            'Search Results' => $arrLang['Search Results'],
            'Date Time' => $arrLang['Date Time'],
            'Description' => $arrLang['Description'],
            'Search' => $arrLang['Search'],
            'Submit' => $arrLang['Submit'],
            'Phrase' => $arrLang['Phrase'],
            'From' => $arrLang['From'],
            'To' => $arrLang['To'],
            'Sort By' => $arrLang['Sort By'],
            'Order' => $arrLang['Order'],
            'Validation Error' => $arrLang['Validation Error'],
            );
