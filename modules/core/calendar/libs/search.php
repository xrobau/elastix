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

if(!defined('IN_PHPC')) {
        die("Hacking attempt");
}
global $search_lang, $sort_options, $order_options;

$sort_options = array(
                'startdate' => $search_lang['Start Date'],
                'subject' => $search_lang['Subject']
                );
$order_options = array(
                'ASC' => $search_lang['Ascending'],
                'DESC' => $search_lang['Decending']
                );

function search_results()
{
	global $vars, $db, $calendar_name, $sort_options, $order_options, $search_lang;

	$searchstring = $vars['searchstring'];

    if($vars['smonth']<10)  $vars['smonth'] = "0".$vars['smonth'];
    if($vars['sday']<10)    $vars['sday']   = "0".$vars['sday'];
    if($vars['emonth']<10)  $vars['emonth'] = "0".$vars['emonth'];
    if($vars['eday']<10)    $vars['eday']   = "0".$vars['eday'];

	$start = "strftime('%Y-%m-%d', '$vars[syear]-$vars[smonth]-$vars[sday]')";
	$end = "strftime('%Y-%m-%d', '$vars[eyear]-$vars[emonth]-$vars[eday]')";

    // make sure sort is valid
	$sort = $vars['sort'];
    if(array_search($sort, array_keys($sort_options)) === false) {
        $smarty->assign("mb_title", $search_lang["Validation Error"]);
        $strErrorMsg = $search_lang['Invalid sort option'] . ": $sort";
        $smarty->assign("mb_message", $strErrorMsg);
        return search_form();
    }

    // make sure order is valid
	$order = $vars['order'];
    if(array_search($order, array_keys($order_options)) === false) {
        $smarty->assign("mb_title", $search_lang["Validation Error"]);
        $strErrorMsg = $search_lang['Invalid order option'] . ": $order";
        $smarty->assign("mb_message", $strErrorMsg);
        return search_form();
    }

	$keywords = explode(" ", $searchstring);

	$words = array();
	foreach($keywords as $keyword) {
		$words[] = "(subject LIKE '%$keyword%' "
                        ."OR description LIKE '%$keyword%')\n";
	}
        $where = implode(' AND ', $words);

    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $uid = Obtain_UID_From_User($user);

	$query = 'SELECT * FROM '.SQL_PREFIX."events "
		."WHERE ($where) "
		."AND enddate >= $start "
		."AND startdate <= $end "
        ////////////////////AGREGADO PARA ELASTIX
        ."AND uid = $uid "
        ////////////////////
		."ORDER BY $sort $order";

	$result = $db->fetchTable($query, true);
    if(!is_array($result) && $result==FALSE)
    {
        $smarty->assign("mb_title", $search_lang["Validation Error"]);
        $smarty->assign("mb_message", $search_lang['Encountered an error while searching']);
        return search_form();
    }

    $tags = array();
    foreach($result as $key => $row)
    {
		$name = stripslashes($row['uid']);
		$subject = stripslashes($row['subject']);
		$desc = nl2br(stripslashes($row['description']));
		$desc = parse_desc($desc);

		$tags[] = tag('tr',
				tag('td', attributes('class="phpc-list"'),
					tag('strong',
						create_id_link($subject,
							'display', $row['id'])
					   )),
				tag('td', attributes('class="phpc-list"'),
                                        $row['startdate'] . ' ' .
					formatted_time_string($row['starttime'],
						$row['eventtype'])),
				tag('td', attributes('class="phpc-list"'),
                                        $desc));
	}

	if(sizeof($tags) == 0) {
		$html = tag('div', tag('strong', $search_lang['No events matched your search criteria']));
	} else {
                $html = tag('table',
                                attributes('class="phpc-main"'),
                                tag('caption', $search_lang['Search Results']),
                                tag('thead',
                                        tag('tr',
                                                tag('th', $search_lang['Subject']),
                                                tag('th', $search_lang['Date Time']),
                                                tag('th', $search_lang['Description']))));
                foreach($tags as $tag) $html->add($tag);
        }

	return $html;
}

function search_form()
{
	global $day, $month, $year, $phpc_script, $month_names, $sort_options,
               $order_options, $search_lang;

        $day_sequence = create_sequence(1, 31);
        $month_sequence = create_sequence(1, 12);
        $year_sequence = create_sequence(1970, 2037);
	$html_table = tag('table', attributes('class="phpc-main"'),
			tag('caption', $search_lang['Search']),
			tag('tfoot',
				tag('tr',
					tag('td', attributes('colspan="2"'),
						create_submit($search_lang['Submit'])))),
			tag('tr',
				tag('td', $search_lang['Phrase'] . ': '),
				tag('td', tag('input', attributes('type="text"',
							'name="searchstring"',
							'size="32"')),
					create_hidden('action', 'search'))),
			tag('tr',
				tag('td', $search_lang['From'] . ': '),
				tag('td',
					create_select('sday', $day_sequence,
                                                $day),
					create_select('smonth', $month_names,
                                                $month),
					create_select('syear', $year_sequence,
                                                $year))),
			tag('tr',
				tag('td', $search_lang['To'] . ': '),
				tag('td',
					create_select('eday', $day_sequence,
                                                $day),
					create_select('emonth', $month_names,
                                                $month),
					create_select('eyear', $year_sequence,
                                                $year))),
			tag('tr',
				tag('td', $search_lang['Sort By'] . ': '),
				tag('td',
                                        create_select('sort', $sort_options,
                                                false))),
			tag('tr',
				tag('td', $search_lang['Order'] . ': '),
				tag('td',
                                        create_select('order', $order_options,
                                                false))));

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'), $html_table);
}

function search()
{
	global $vars;

	if(isset($vars['searchstring'])) return search_results();
	return search_form();
}

?>