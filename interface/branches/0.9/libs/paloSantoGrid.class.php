<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.org                                               |
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
  $Id: paloSantoGrid.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class paloSantoGrid {

    var $enableExport;
    function paloSantoGrid($smarty)
    {
        $this->smarty = $smarty;
        $this->enableExport = false;
    }

    function enableExport()
    {
        $this->enableExport = true;
    }

    function fetchGrid($arrGrid, $arrData,$arrLang=array())
    {
        $numColumns=count($arrGrid["columns"]);

        $this->smarty->assign("title", $arrGrid['title']);
        $this->smarty->assign("icon",  $arrGrid['icon']);
        $this->smarty->assign("width", $arrGrid['width']);

        $this->smarty->assign("start", $arrGrid['start']);
        $this->smarty->assign("end",   $arrGrid['end']);
        $this->smarty->assign("total", $arrGrid['total']);

        $this->smarty->assign("header",  $arrGrid["columns"]);

        $this->smarty->assign("arrData", $arrData);
        $this->smarty->assign("numColumns", $numColumns);

        $this->smarty->assign("enableExport", $this->enableExport);
        //dar el valor a las etiquetas segun el idioma
        $etiquetas=array('Export','Start','Previous','Next','End');
        foreach ($etiquetas as $etiqueta)
        {
            $this->smarty->assign("lbl$etiqueta", (isset($arrLang[$etiqueta])?$arrLang[$etiqueta]:$etiqueta));
        }
        return $this->smarty->fetch("_common/_list.tpl");
    }

    function fetchGridCSV($arrGrid, $arrData)
    {
        $numColumns=count($arrGrid["columns"]);

        $this->smarty->assign("title", $arrGrid['title']);
        $this->smarty->assign("icon",  $arrGrid['icon']);
        $this->smarty->assign("width", $arrGrid['width']);

        $this->smarty->assign("start", $arrGrid['start']);
        $this->smarty->assign("end",   $arrGrid['end']);
        $this->smarty->assign("total", $arrGrid['total']);

        $this->smarty->assign("header",  $arrGrid["columns"]);

        $this->smarty->assign("arrData", $arrData);
        $this->smarty->assign("numColumns", $numColumns);
        return $this->smarty->fetch("_common/listcsv.tpl");
    }

    function showFilter($htmlFilter)
    {
        $this->smarty->assign("contentFilter", $htmlFilter);
    }

    function getOffSet($limit,$total,$accion,$start)
    {
        // Si se quiere avanzar a la sgte. pagina
        if(isset($accion) && $accion=="next") {
            $offset = $start + $limit - 1;
        }
        // Si se quiere retroceder
        else if(isset($accion) && $accion=="previous") {
            $offset = $start - $limit - 1;
        }
        else if(isset($accion) && $accion=="end") {
            if(($total%$limit)==0) 
                $offset = $total - $limit;
            else 
                $offset = $total - $total%$limit;
        }
        else if(isset($accion) && $accion=="start") {
            $offset = 0;
        }
        else $offset = 0;
        return $offset;
    }
}
?>