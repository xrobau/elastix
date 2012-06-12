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
   This file sets up the global variables to be used later
*/
global $day, $month, $year, $vars, $config, $action, $first_day_of_week, $menu;
global $phpc_script, $phpc_protocol, $phpc_server, $phpc_url;
global $calendar_name, $general;
global $min_year, $max_year;

$min_year = 2007;
$max_year = 2020;

// Modify these if you need to
$phpc_script = $_SERVER['PHP_SELF'];
if($_SERVER["HTTPS"] == "on") $phpc_protocol = "https";
else $phpc_protocol = "http";
$phpc_server = $_SERVER['SERVER_NAME'];
$phpc_url = "$phpc_protocol://$phpc_server$phpc_script?"
    . $_SERVER['QUERY_STRING'];

/* FIXME: This file is a fucking mess, clean it up */

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

ini_set('arg_separator.output', '&amp;');

define('SQL_PREFIX',   '');

require_once($phpc_root_path . 'libs/calendar.php');

// Create vars
foreach($_GET as $key => $value) {
    if(!get_magic_quotes_gpc())
        $vars[$key] = addslashes($value);
    else
        $vars[$key] = $value;
}

foreach($_POST as $key => $value) {
    if(!get_magic_quotes_gpc())
        $vars[$key] = addslashes($value);
    else
        $vars[$key] = $value;
}

// Load configuration
if(!empty($vars['calendar_name'])) {
        $calendar_name = $vars['calendar_name'];
} // calendar name is otherwise set to 0

$config = array(    'calendar'          => 0,
                    'hours_24'          => 0,
                    'start_monday'      => 1,
                    'translate'         => 0,
                    'anon_permission'   => 0,
                    'subject_max'       => 32,
                    'contact_name'      => "",
                    'contact_email'     => "",
                    'calendar_title'    => 'Elastix Calendar',
                    'url'               => "",
             );

if($config['start_monday'])
    $first_day_of_week = 1;
else
    $first_day_of_week = 0;

require_once($phpc_root_path . 'libs/globals.php');

// set day/month/year
if (!isset($vars['month'])) {
    $month = date('n');
} else {
    $month = $vars['month'];
    if($month < 1)
    {
        if($vars['year'] == $min_year)
        {
            $smarty->assign("mb_title", $general["Invalid action"]);
            $smarty->assign("mb_message", $general['That year is too far in the past'].": {$vars['year']}");
            $month = $vars['month'] = 1;
        }
    }
    else if($month > 12)
    {
        if($vars['year'] == $max_year)
        {
            $smarty->assign("mb_title", $general["Invalid action"]);
            $smarty->assign("mb_message", $general['That year is too far in the future'].": {$vars['year']}");
            $month = $vars['month'] = 12;
        }
    }
}

if(!isset($vars['year'])) {
    $year = date('Y');
} else {
        if($vars['year'] > $max_year) {
            $smarty->assign("mb_title", $general["Invalid action"]);
            $smarty->assign("mb_message", $general['That year is too far in the future'].": {$vars['year']}");
            $vars['year'] = $max_year;
        } else if($vars['year'] < $min_year) {
            $smarty->assign("mb_title", $general["Invalid action"]);
            $smarty->assign("mb_message", $general['That year is too far in the past'].": {$vars['year']}");
            $vars['year'] = $min_year;
        }
    $year = date('Y', mktime(0, 0, 0, $month, 1, $vars['year']));
}

if(!isset($vars['day'])) {
    if($month == date('n') && $year == date('Y')) {
                $day = date('j');
    } else {
                $day = 1;
        }
} else {
    $day = ($vars['day'] - 1) % date('t', mktime(0, 0, 0, $month, 1, $year))
                + 1;
}

while($month < 1) $month += 12;
$month = ($month - 1) % 12 + 1;

//set action
if(empty($vars['action'])) {
    $action = 'display';
} else {
    $action = $vars['action'];
}
?>