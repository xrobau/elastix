<?php
/*
   Copyright 2002 - 2005 Sean Proctor, Nathan Poiro

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
   this file contains all the re-usable functions for the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

include($phpc_root_path . 'libs/html.php');
include($phpc_root_path . 'libs/url_match.php');
global $urlmatch;
$urlmatch = new urlmatch;

// make sure that we have _ defined
if(!function_exists('_')) {
    function _($str) { return $str; }
    $no_gettext = 1;
}

// called when some error happens
function soft_error($str)
{
    global $general;
    echo '<html><head><title>'.$general['Error']."</title></head>\n"
        .'<body><h1>'.$general['Software Error']."</h1>\n"
                ."<h2>".$general['Message:']."</h2>\n"
        ."<pre>$str</pre>\n";
        if(version_compare(phpversion(), '4.3.0', '>=')) {
                echo "<h2>".$general['Backtrace']."</h2>\n";
                echo "<ol>\n";
                foreach(debug_backtrace() as $bt) {
                        echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
                }
                echo "</ol>\n";
        }
        echo "</body></html>\n";
    exit;
}

// called when there is an error involving the DB
function db_error($str, $query = "")
{
        global $db;

        $string = "$str<br />".$db->errMsg;
        if($query != "") {
                $string .= "<br />"._('SQL query').": $query";
        }
        soft_error($string);
}

// takes a number of the month, returns the name
function month_name($month)
{
        global $month_names;

    $month = ($month - 1) % 12 + 1;
        return $month_names[$month];
}

//takes a day number of the week, returns a name (0 for the beginning)
function day_name($day)
{
    global $day_names;

    $day = $day % 7;

        return $day_names[$day];
}

function short_month_name($month)
{
        global $short_month_names;

    $month = ($month - 1) % 12 + 1;
        return $short_month_names[$month];
}

// takes a time string, and formats it according to type
// returns the formatted string
function formatted_time_string($time, $type)
{
    global $config, $general;

    preg_match('/(\d+):(\d+)/', $time, $matches);
    $hour = $matches[1];
    $minute = $matches[2];

    if(!$config['hours_24']) {
        if($hour >= 12) {
                                if($hour != 12) {
                                        $hour -= 12;
                                }
            $pm = ' '.$general['PM'];
                        } else {
                                if($hour == 0) {
                                        $hour = 12;
                                }
            $pm = ' '.$general['AM'];
        }
    } else {
        $pm = '';
    }

    return sprintf('%d:%02d%s', $hour, $minute, $pm);
}

// takes start and end dates and returns a nice display
function formatted_date_string($startyear, $startmonth, $startday, $endyear,
        $endmonth, $endday)
{
    $str = month_name($startmonth) . " $startday, $startyear";

    if($startyear != $endyear || $startmonth != $endmonth || $startday !=
            $endday) {
        $str .= " - " . month_name($endmonth) . " $endday, $endyear";
    }
    return $str;
}

// takes some xhtml data fragment and adds the calendar-wide menus, etc
// returns a string containing an XHTML document ready to be output
function create_xhtml($rest)
{
    global $config, $phpc_script, $year, $month;

    $output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        ."<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n"
        ."\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
    $html = tag('html', attributes("xmlns=\"http://www.w3.org/1999/xhtml\"",
                "xml:lang=\"en\"", "lang=\"en\""),
            tag('head',
                tag('title', $config['calendar_title']),
                tag('link',
                    attributes('rel="stylesheet"'
                        .' type="text/css" href="'
                        .$phpc_script
                        .'?action=style"'))),
            tag('body',
                //tag('h1', $config['calendar_title']),
                tag('table', attributes( 'width="99%"'),
                    tag('tr',
                        tag('td', navbar())/*,
                        tag('td'),
                        tag('td',
                            tag('h1',
                                month_name($month)." $year")
                            )
                            //tag('div', navbar()),
                            //tag('caption', month_name($month)." $year")*/
                    )
                ),
                $rest
            ));

    return $output . $html->toString();
}

// returns all the events for a particular day
function get_events_by_date($day, $month, $year)
{
    global $calendar_name, $db, $general;

/* event types:
1 - Normal event
2 - full day event
3 - unknown time event
4 - reserved
5 - weekly event
6 - monthly event
*/
        //$startdate = $db->SQLDate('Y-m-d', 'startdate');
        $startdate = "strftime('%Y-%m-%d', startdate)";
        //$enddate = $db->SQLDate('Y-m-d', 'enddate');
        $enddate = "strftime('%Y-%m-%d', enddate)";
        $date = "'" . date('Y-m-d', mktime(0, 0, 0, $month, $day, $year))
                . "'";
        // day of week
        //$dow_startdate = $db->SQLDate('w', 'startdate');
        $dow_startdate = "strftime('%w', startdate)";
        //$dow_date = $db->SQLDate('w', $date);
        $dow_date = "strftime('%w', $date)";
        // day of month
        //$dom_startdate = $db->SQLDate('d', 'startdate');
        $dom_startdate = "strftime('%d', startdate)";
        //$dom_date = $db->SQLDate('d', $date);
        $dom_date = "strftime('%d', $date)";

    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $uid = Obtain_UID_From_User($user);

    $query = 'SELECT * FROM '.SQL_PREFIX."events\n"
        ."WHERE $date >= $startdate AND $date <= $enddate\n"
                // find normal events
                ."AND (eventtype = 1 OR eventtype = 2 OR eventtype = 3 "
                ."OR eventtype = 4\n"
                // find weekly events
        ."OR (eventtype = 5 AND $dow_startdate = $dow_date)\n"
                // find monthly events
        ."OR (eventtype = 6 AND $dom_startdate = $dom_date)\n"
                .")\n"
        ."AND uid = $uid "
        ."ORDER BY starttime";

    $result = $db->fetchTable($query, true);
    if($result==null)
        return array();
    return $result;
}

// returns the event that corresponds to $id
function get_event_by_id($id)
{
    global $calendar_name, $db, $general;

    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $uid = Obtain_UID_From_User($user);

    $events_table = SQL_PREFIX . 'events';
    $users_table = SQL_PREFIX . 'users';

    $query = "SELECT $events_table.*,\n"
        ."strftime('%Y', $events_table.startdate) AS year,\n"
        ."strftime('%m', $events_table.startdate) AS month,\n"
        ."strftime('%d', $events_table.startdate) AS day,\n"
        ."strftime('%Y', $events_table.enddate) AS end_year,\n"
        ."strftime('%m', $events_table.enddate) AS end_month,\n"
        ."strftime('%d', $events_table.enddate) AS end_day\n"
        ."FROM $events_table\n"
        ."WHERE $events_table.id = '$id'\n"
        ."AND $events_table.uid=$uid";

    $result = $db->getFirstRowQuery($query, true);

    if($result == FALSE) {
        db_error($general['Error in get_event_by_id'], $query);
        return array();
    }

    if(!is_array($result) || count($result) == 0) {
        soft_error($general["item doesn't exist!"]);
        return array();
    }
    return $result;
}

// parses a description and adds the appropriate mark-up
function parse_desc($text)
{
    global $urlmatch;

    // get out the crap, put in breaks
        $text = strip_tags($text);
    // if you want to allow some tags, change the previous line to:
    // $text = strip_tags($text, "a"); // change "a" to the list of tags
        $text = htmlspecialchars($text, ENT_NOQUOTES);
    // then uncomment the following line
    // $text = preg_replace("/&lt;(.+?)&gt;/", "<$1>", $text);
        $text = nl2br($text);

    //urls
    $text = $urlmatch->match($text);

    // emails
    $text = preg_replace("/([a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*"
            ."[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z])/",
            "<a href=\"mailto:$1\">$1</a>", $text );

    return $text;
}

// returns the day of week number corresponding to 1st of $month
function day_of_first($month, $year)
{
    return date('w', mktime(0, 0, 0, $month, 1, $year));
}

// returns the number of days in $month
function days_in_month($month, $year)
{
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year)
{
    global $first_day_of_week;

    return ceil((days_in_month($month, $year) + (7 + day_of_first($month, $year) - $first_day_of_week) % 7) / 7);
}

// creates a link with text $text and GET attributes corresponding to the rest
// of the arguments.
// returns XHTML data for the link
function create_id_link($text, $action, $id = false, $attribs = false)
{
    global $phpc_script;

    $url = "href=\"$phpc_script?action=$action";
    if($id !== false) $url .= "&amp;id=$id";
    $url .= '"';

        if($attribs !== false) {
                $as = attributes($url, $attribs);
        } else {
                $as = attributes($url);
        }
    return tag('a', $as, $text);
}

function create_date_link($text, $action, $year = false, $month = false,
                $day = false, $attribs = false, $lastaction = false)
{
        global $phpc_script;

    $url = "href=\"$phpc_script?action=$action";
    if($year !== false) $url .= "&amp;year=$year";
    if($month !== false) $url .= "&amp;month=$month";
    if($day !== false) $url .= "&amp;day=$day";
        if($lastaction !== false) $url .= "&amp;lastaction=$lastaction";
    $url .= '"';

        if($attribs !== false) {
                $as = attributes($url, $attribs);
        } else {
                $as = attributes($url);
        }
    return tag('a', $as, $text);
}

// takes a menu $html and appends an entry
function menu_item_append(&$html, $name, $action, $year = false, $month = false,
        $day = false, $lastaction = false)
{
        global $general;
        if(!is_object($html)) {
                soft_error($general['Html is not a valid Html class.']);
        }
    $html->add(create_date_link($name, $action, $year, $month,
                                        $day, false, $lastaction));
        $html->add("\n");
}

// same as above, but prepends the entry
function menu_item_prepend(&$html, $name, $action, $year = false,
        $month = false, $day = false, $lastaction = false)
{
        $html->prepend("\n");
    $html->prepend(create_date_link($name, $action, $year, $month,
                                $day, false, $lastaction));
}

// creates a hidden input for a form
// returns XHTML data for the input
function create_hidden($name, $value)
{
    return tag('input', attributes("name=\"$name\"", "value=\"$value\"",
                'type="hidden"'));
}

// creates a submit button for a form
// return XHTML data for the button
function create_submit($value)
{
    return tag('input', attributes('name="submit"', "value=\"$value\"",
                                'type="submit"'));
}

// creates a text entry for a form
// returns XHTML data for the entry
function create_text($name, $value = false)
{
    $attributes = attributes("name=\"$name\"", 'type="text"');
    if($value !== false) {
        $attributes->add("value=\"$value\"");
    }
    return tag('input', $attributes);
}

// creates a checkbox for a form
// returns XHTML data for the checkbox
function create_checkbox($name, $value = false, $checked = false)
{
    $attributes = attributes("name=\"$name\"", 'type="checkbox"');
    if($value !== false) $attributes->add("value=\"$value\"");
    if(!empty($checked)) $attributes->add('checked="checked"');
    return tag('input', $attributes);
}

// creates the navbar for the top of the calendar
// returns XHTML data for the navbar
function navbar()
{
    global $vars, $action, $config, $year, $month, $day, $menu;

    $html = tag('div', attributes('class="phpc-navbar"'));

    if($action != 'add') {
        menu_item_append($html, $menu['Add Event'], 'event_form', $year,
                $month, $day);
    }

    if($action != 'search') {
        menu_item_append($html, $menu['Search'], 'search', $year, $month,
                $day);
    }

    if(!empty($vars['day']) || !empty($vars['id']) || $action != 'display') {
        menu_item_append($html, $menu['Back to Calendar'], 'display',
                $year, $month);
    }

    if($action != 'display' || !empty($vars['id'])) {
        menu_item_append($html, $menu['View Date'], 'display', $year,
                $month, $day);
    }

    if(isset($var['display']) && $var['display'] == 'day') {
        $monthname = month_name($month);

        $lasttime = mktime(0, 0, 0, $month, $day - 1, $year);
        $lastday = date('j', $lasttime);
        $lastmonth = date('n', $lasttime);
        $lastyear = date('Y', $lasttime);
        $lastmonthname = month_name($lastmonth);

        $nexttime = mktime(0, 0, 0, $month, $day + 1, $year);
        $nextday = date('j', $nexttime);
        $nextmonth = date('n', $nexttime);
        $nextyear = date('Y', $nexttime);
        $nextmonthname = month_name($nextmonth);

        menu_item_prepend($html, "$lastmonthname $lastday",
                    'display', $lastyear, $lastmonth,
                    $lastday);
        menu_item_append($html, "$nextmonthname $nextday",
                'display', $nextyear, $nextmonth, $nextday);
    }

    return $html;
}

// creates an array from $start to $end, with an $interval
function create_sequence($start, $end, $interval = 1, $display = NULL)
{
        $arr = array();
        for ($i = $start; $i <= $end; $i += $interval){
            if($display) {
                    $arr[$i] = call_user_func($display, $i);
            } else {
                    $arr[$i] = $i;
            }
        }
        return $arr;
}

function minute_pad($minute)
{
        return sprintf('%02d', $minute);
}

function get_day_of_month_sequence($month, $year)
{
        $end = date('t', mktime(0, 0, 0, $month, 1, $year, 0));
        return create_sequence(0, $end);
}

// creates a select element for a form of pre-defined $type
// returns XHTML data for the element
function create_select($name, $type, $select)
{
    $html = tag('select', attributes('size="1"', "name=\"$name\""));

        foreach($type as $value => $text) {
        $attributes = attributes("value=\"$value\"");
        if ($select == $value) {
                        $attributes->add('selected="selected"');
                }
        $html->add(tag('option', $attributes, $text));
    }

    return $html;
}

function redirect($page) {
    global $phpc_script, $phpc_server, $phpc_protocol;

    if($page{0} == "/") {
        $dir = '';
    } else {
        $dir = dirname($phpc_script) . "/";
    }

    header("Location: $phpc_protocol://$phpc_server$dir$page");
}

/*FUNCIONES AÑADIDAS PARA ELASTIX*/

function Obtain_UID_From_User($user)
{
    global $arrConf;

    require_once $arrConf['basePath']."/libs/paloSantoACL.class.php";
    $pdbACL = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);
    if($uid!=FALSE)
        return $uid;
    else return -1;
}
?>