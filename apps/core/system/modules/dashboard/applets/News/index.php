<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp
  $Id: index.php,v 1.2 2015/02/04 16:27:54 achuto,dpanchana Exp $
*/
if (file_exists('/usr/share/php/php-simplepie/autoloader.php'))
    require_once 'php-simplepie/autoloader.php';
else
    require_once 'php-simplepie/simplepie.inc';

class Applet_News
{
	function handleJSON_getContent($smarty, $module_name, $appletlist)
    {
        /* Se cierra la sesión para quitar el candado sobre la sesión y permitir
         * que otras operaciones ajax puedan funcionar. */
	    $infoRSS = new SimplePie();
        session_commit();

        $respuesta = array(
            'status'    =>  'success',
            'message'   =>  '(no message)',
        );
        $lang=get_language();
        $cachedir = '/tmp/rss-cache';
        if($lang=="es")$infoRSS->set_feed_url("http://elx.ec/newses");
        else $infoRSS->set_feed_url("http://elx.ec/newsen");
        $infoRSS->enable_order_by_date(TRUE);
        $infoRSS->set_output_encoding('UTF-8');
        $infoRSS->enable_cache(TRUE);
        $infoRSS->set_cache_location($cachedir);
        if (!is_dir($cachedir) && ! @mkdir($cachedir)) {
            $infoRSS->enable_cache(FALSE);
        }
        $infoRSS->init();
        $infoRSS->handle_content_type(); //This method ensures that the SimplePie-enabled page is being served with the correct mime-type and character encoding HTTP headers

        if (strpos($infoRSS->error(), 'HTTP Error: connection failed') !== FALSE) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Could not get web server information. You may not have internet access or the web server is down');
        } else {
            $news = array();

            // Formato de fecha y hora
            foreach ($infoRSS->get_items() as $item) {
                $news[] = array(
                    'title'       =>  $item->get_title(),
                    'link'        =>  $item->get_link(),
                    'date_format' =>  date('Y.m.d', $item->get_date('U')),
                );
            }

            $smarty->assign(array(
                'WEBSITE'   =>  'http://www.elastix.com',
                'NO_NEWS'   =>  _tr('No News to display'),
                'NEWS_LIST' =>  array_slice($news, 0, 7),
            ));
            $local_templates_dir = dirname($_SERVER['SCRIPT_FILENAME'])."/modules/$module_name/applets/News/tpl";
            $respuesta['html'] = $smarty->fetch("$local_templates_dir/rssfeed.tpl");
        }

        $json = new Services_JSON();
        Header('Content-Type: application/json');
        return $json->encode($respuesta);
    }
}
