<?php
/*
   Copyright 2006 Sean Proctor

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

/*
   This file has the functions for the main displays of the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// picks which view to show based on what data is given
// returns the appropriate view
function display()
{
    global $vars, $day, $month, $year, $general;

    if(isset($vars['id'])) return display_id($vars['id']);
    if(isset($vars['day'])) return display_day($day, $month, $year);
    if(isset($vars['month'])) return display_month($month, $year);
    if(isset($vars['year'])) soft_error($general['year view not yet implemented']);
    return display_month($month, $year);
}

// creates a menu to navigate the month/year
// returns XHTML data for the menu
function month_navbar($month, $year)
{
    global $options;
    $html = tag('div', attributes('class="phpc-navbar"'));
    menu_item_append($html, $options['last year'], 'display&amp;menu=calendar', $year - 1, $month);
    menu_item_append($html, $options['last month'], 'display&amp;menu=calendar', $year, $month - 1);

    for($i = 1; $i <= 12; $i++) {
        menu_item_append($html, short_month_name($i), 'display&amp;menu=calendar', $year,
                $i);
    }
    menu_item_append($html,  $options['next month'], 'display&amp;menu=calendar', $year, $month + 1);
    menu_item_append($html,  $options['next year'], 'display&amp;menu=calendar', $year + 1, $month);

    return $html;
}

// creates a tables of the days in the month
// returns XHTML data for the month
function display_month($month, $year)
{
    global $config, $options, $phpc_script, $month_names, $min_year, $max_year;

    $days = tag('tr');
    for($i = 0; $i < 7; $i++) {
        if($config['start_monday'])
            $d = $i + 1 % 7;
        else
            $d = $i;
        $days->add(tag('th', day_name($d)));
    }

/*
    $html_navbar = month_navbar($month, $year);
    $month_year = tag('div', attributes('style="color: #fbaa3f; font-size: 120%; font-weight: bold; position:absolute; right:50px; top:180px"'), month_name($month)." $year");
    $html_navbar->add(tag('div', $month_year));
    //$html_navbar .= $month_year;
*/
    //$html_navbar = month_navbar($month, $year);

/*
    $html = tag('div', attributes('class="phpc-navbar"'));
    menu_item_append($html, $options['last year'], 'display', $year - 1, $month);
    menu_item_append($html, $options['last month'], 'display', $year, $month - 1);

    for($i = 1; $i <= 12; $i++) {
        menu_item_append($html, short_month_name($i), 'display', $year,
                $i);
    }
    menu_item_append($html,  $options['next month'], 'display', $year, $month + 1);
    menu_item_append($html,  $options['next year'], 'display', $year + 1, $month);
*/
    $month_year = $html = tag('div', attributes('class="month_div"'));

    $last_year  = "<img border='0' src='images/start.gif' />";
    $last_month = "<img border='0' src='images/previous.gif' />";
    $next_month = "<img border='0' src='images/next.gif' />";
    $next_year  = "<img border='0' src='images/end.gif' />";

    menu_item_append($html, $last_year,  'display&amp;menu=calendar', $year - 1, $month);
    menu_item_append($html, $last_month, 'display&amp;menu=calendar', $year, $month - 1);

    $year_sequence = create_sequence($min_year, $max_year);

    $select_year  = "<select id='select_year' class='select_month_year' onchange='display_calendar()'>";
    foreach($year_sequence as $year_n)
    {
        if($year_n==$year)
            $select_year .= "<option value='$year_n' selected='selected'>$year_n</option>";
        else
            $select_year .= "<option value='$year_n'>$year_n</option>";
    }
    $select_year .= "</select>";

    $select_month = "<select id='select_month' class='select_month_year' onchange='display_calendar()'>";
    $i=1;
    foreach($month_names as $month_n)
    {
        if($i==$month)
            $select_month .= "<option value='$i' selected='selected'>$month_n</option>";
        else
            $select_month .= "<option value='$i'>$month_n</option>";
        $i++;
    }
    $select_month .= "</select>";

    $actual_month_year = "&nbsp;&nbsp;$select_month $select_year&nbsp;&nbsp;";
    $html->add($actual_month_year);

    menu_item_append($html, $next_month, 'display&amp;menu=calendar', $year, $month + 1);
    menu_item_append($html, $next_year,  'display&amp;menu=calendar', $year + 1, $month);

    //$month_year = tag('div', attributes('class="month_div"'), month_name($month)." $year");
    //$html_navbar->add(tag('div', $month_year));
    //$html_navbar .= $month_year;
    $html_navbar = $month_year;

    return tag('div',
                        $html_navbar,
                        //$month_year,
                        tag('table', attributes('class="phpc-main"',
                                        'id="calendar"'),
                                //tag('caption', month_name($month)." $year"),
                                tag('colgroup', attributes('span="7"', 'width="1*"')),
                                tag('thead', $days),
                                create_month($month, $year)
                        )
                );
}

// creates a display for a particular month
// return XHTML data for the month
function create_month($month, $year)
{
    return tag('tbody', create_weeks(1, $month, $year));
}

// creates a display for a particular week and the rest of the weeks until the
// end of the month
// returns XHTML data for the weeks
function create_weeks($week_of_month, $month, $year)
{
    if($week_of_month > weeks_in_month($month, $year)) return array();

        $html_week = tag('tr', display_days(1, $week_of_month, $month, $year));

        return array_merge(array($html_week), create_weeks($week_of_month + 1,
                                $month, $year));
}

// displays the day of the week and the following days of the week
// return XHTML data for the days
function display_days($day_count, $week_of_month, $month, $year)
{
    global $db, $phpc_script, $config, $first_day_of_week;

    if($day_count > 7) return array();

    $day_of_month = ($week_of_month - 1) * 7 + $day_count
        - ((7 + day_of_first($month, $year) - $first_day_of_week) % 7);

    if($day_of_month <= 0 || $day_of_month > days_in_month($month, $year)) {
        $html_day = tag('td', attributes('class="none"'));
    } else {
        $currentday = date('j');
        $currentmonth = date('n');
        $currentyear = date('Y');

        // set whether the date is in the past or future/present
        if($currentyear > $year || $currentyear == $year
                && ($currentmonth > $month
                    || $currentmonth == $month 
                    && $currentday > $day_of_month
                   )) {
            $current_era = 'past';
        } else {
            $current_era = 'future';
        }

            $html_day = tag('td', attributes('valign="top"',
                                            "class=\"$current_era\""),
                                    create_date_link('+', 'event_form&amp;menu=calendar',
                                            $year, $month,
                                            $day_of_month,
                                            array('class="phpc-add"')),
                                    create_date_link($day_of_month,
                                            'display&amp;menu=calendar', $year, $month,
                                            $day_of_month,
                                            array('class="date"')));

        $result = get_events_by_date($day_of_month, $month, $year);
        /* Start off knowing we don't need to close the event
         *  list.  loop through each event for the day
         */
                $have_events = false;
        $html_events = tag('ul');
        //while($row = $result->FetchRow($result)) {
        foreach($result as $key => $row)
        {
            $subject = htmlspecialchars(strip_tags(stripslashes(
                            $row['subject'])));

            $event_time = formatted_time_string(
                    $row['starttime'],
                    $row['eventtype']);

            $event = tag('li',
                                        tag('a',
                                                attributes(
                                                        "href=\"$phpc_script"
                                                        ."?menu=calendar&amp;action=display&amp;"
                                                        ."id=$row[id]\""),
                                                ($event_time ? "$event_time - "
                                                 : '')
                                                . $subject));
                        $html_events->add($event);
                        $have_events = true;
        }
        if($have_events) $html_day->add($html_events);
    }

    return array_merge(array($html_day), display_days($day_count + 1,
                $week_of_month, $month, $year));
}

// displays a single day in a verbose way to be shown singly
// returns the XHTML data for the day
function display_day($day, $month, $year)
{
    global $db, $config, $phpc_script, $view_events;

    $tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
    $monthname = month_name($month);

    $result = get_events_by_date($day, $month, $year);

    $today_epoch = mktime(0, 0, 0, $month, $day, $year);

    if(is_array($result) && count($result)>0) {

        $html_table = tag('table', attributes('class="phpc-main"'),
                tag('caption', "$day $monthname $year"),
                tag('thead',
                    tag('tr',
                        tag('th', $view_events['Title']),
                        tag('th', $view_events['Time']),
                        tag('th', $view_events['Description'])
                         )));

        $html_table->add(tag('tfoot',
                                            tag('tr',
                                                    tag('td',
                                                            attributes('colspan="4"'),
                                                            create_hidden('action', 'event_delete'),
                                                            create_hidden('day', $day),
                                                            create_hidden('month', $month),
                                                            create_hidden('year', $year),
                                                            create_submit($view_events['Delete Selected'])))));

        $html_body = tag('tbody');

        //for(; $row; $row = $result->FetchRow()) {
        foreach($result as $key => $row){
            $subject = htmlspecialchars(strip_tags(stripslashes(
                            $row['subject'])));
            if(empty($subject)) $subject = $view_events['(No subject)'];
            $desc = parse_desc($row['description']);
            $time_str = formatted_time_string($row['starttime'],
                    $row['eventtype']);

            $html_subject = tag('td',
                                        attributes('class="phpc-list"'));

            $html_subject->add(create_checkbox('id',
                                    $row['id']));

            $html_subject->add(create_id_link(tag('strong',
                                                        $subject),
                                                'display', $row['id']));

            $html_subject->add(' (');
            $html_subject->add(create_id_link($view_events['Modify'],
                                            'event_form', $row['id']));
            $html_subject->add(')');

            $html_body->add(tag('tr',
                                        $html_subject,
                                        tag('td',
                                                attributes('class="phpc-list"'),
                                                $time_str),
                                        tag('td',
                                                attributes('class="phpc-list"'),
                                                $desc)));
        }

        $html_table->add($html_body);

        $output = tag('form', attributes("action=\"$phpc_script\""), $html_table);

    } else {
        $output = tag('h2', $view_events['No events on this day']);
    }

    return $output;
}

// displays a particular event to be show singly
// returns XHTML data for the event
function display_id($id)
{
    global $db, $year, $month, $day, $config, $view_events, $event_types;

    $row = get_event_by_id($id);

    $year = $row['year'];
    $month = $row['month'];
    $day = $row['day'];

    $time_str = formatted_time_string($row['starttime'], $row['eventtype']);
    $date_str = formatted_date_string($row['year'], $row['month'],
            $row['day'], $row['end_year'], $row['end_month'],
            $row['end_day']);
    $typeofevent = $event_types[$row['eventtype']];
    $asterisk_call = ($row['asterisk_call']=='on')?$view_events['yes']:$view_events['no'];
    $call_to = $row['call_to'];
    $recording = $row['recording'];

    $subject = htmlspecialchars(strip_tags(stripslashes($row['subject'])));
    if(empty($subject)) $subject = $view_events['(No subject)'];
    $desc = parse_desc($row['description']);

    return tag('div', attributes('class="phpc-main"'),
                    tag('h2', $subject),
                    tag('div', create_id_link($view_events['Modify'], 'event_form',
                                    $id), "\n", create_id_link($view_events['Delete'],
                                            'event_delete', $id)),
                    tag('table',
                        tag('tr'),
                            tag('td', $view_events['Event type'].": "),
                            tag('td', $typeofevent),
                        tag('tr'),
                            tag('td', $view_events['Date'].": "),
                            tag('td', $date_str),
                        tag('tr'),
                            tag('td', $view_events['Time'].": "),
                            tag('td', $time_str),
                        tag('tr'),
                            tag('td', $view_events['Asterisk Call Me'].": "),
                            tag('td', $asterisk_call),
                        tag('tr'),
                            tag('td', $view_events['Call to'].": "),
                            tag('td', $call_to),
                        tag('tr'),
                            tag('td', $view_events['Recordings'].": "),
                            tag('td', $recording)
                    ),
                    tag('p',
                        tag('div', $view_events['Description']),
                        tag('p', $desc)
                    )
                );
}
?>
