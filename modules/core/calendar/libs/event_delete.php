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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function remove_event($id)
{
	global $db, $view_events, $smarty;

	$sql = 'DELETE FROM '.SQL_PREFIX ."events WHERE id = '$id'";
	$result = $db->genQuery($sql);
    if(!$result)
    {
        require_once "display.php";
        $smarty->assign("mb_title", $view_events["Validation Error"]);
        $smarty->assign("mb_message", $view_events['Error processing event']);
        $vars['id'] = $id;
        return display();
    }

	return ($result);
}

function event_delete()
{
	global $config, $view_events;
    $dir_outgoing = "/var/spool/asterisk/outgoing";

	$del_array = explode('&', $_SERVER['QUERY_STRING']);

	$html = tag('div', attributes('class="box"', 'style="width: 50%"'));

        $ids = 0;

	foreach($del_array as $del_value) {
		list($drop, $id) = explode("=", $del_value);

		if(preg_match('/^id$/', $drop) == 0) continue;
                $ids++;
        /*ELASTIX: REMOVER ARCHIVOS LLAMADAS*/
        $event = get_event_by_id($id);
        if($event['asterisk_call']=='on')
        {
            $start_stamp = strtotime($event['startdate']);
            $end_stamp   = strtotime($event['enddate']);
            if($event['eventtype']==1 || $event['eventtype']==5)
            {
                if($event['eventtype']==1)
                {
                    $segundos = 86400;
                    $num_dias = (($end_stamp-$start_stamp)/$segundos)+1;//Sumo 1 para incluir el ultimo dia
                }else if($event['eventtype']==5)
                {
                    $segundos = 604800;
                    $num_dias = (($end_stamp-$start_stamp)/$segundos)+1;//Sumo 1 para incluir la ultima semana
                    $num_dias = (int)$num_dias;
                }

                for($i=0; $i<$num_dias; $i++)
                {
                    $filename = $dir_outgoing."/event_{$id}_{$i}.call";
                    if(file_exists($filename))
                        unlink($filename);
                }
            }else if($event['eventtype'] ==6)
            {
                $i=0;
                while($start_stamp <= $end_stamp)
                {
                    $filename = $dir_outgoing."/event_{$id}_{$i}.call";
                    $start_stamp = strtotime("+1 months", $start_stamp);
                    if(file_exists($filename))
                        unlink($filename);
                    $i++;
                }
            }
        }
        /*ELASTIX: REMOVER ARCHIVOS LLAMADAS*/

		if(remove_event($id)) {
			$html->add(tag('p', $view_events['Removed item'] . ": $id"));
		} else {
			$html->add(tag('p', $view_events['Could not remove item']
                                                . ": $id"));
		}
	}

	if($ids == 0) {
		$html->add(tag('p', $view_events['No items selected']));
	}

        return $html;
}

?>
