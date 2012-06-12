<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.3                                                |
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
  $Id: paloSantoGrid.class.php, bmacias@palosanto.com Exp $ */
global $arrConf;
require_once "{$arrConf['basePath']}/libs/paloSantoPDF.class.php";


class paloSantoGrid {

    var $title;
    var $icon;
    var $width;
    var $enableExport;
    var $limit;
    var $total;
    var $offset;
    var $start;
    var $end;
    var $tplFile;
    var $pagingShow;
    var $nameFile_Export;
    var $arrHeaders;
    var $arrData;
    var $url;

    function paloSantoGrid($smarty)
    {
        $this->title  = "";
        $this->icon   = "images/list.png";
        $this->width  = "99%";
        $this->smarty = $smarty;
        $this->enableExport = false;
        $this->offset = 0;
        $this->start  = 0;
        $this->end    = 0;
        $this->limit  = 0;
        $this->total  = 0;
        $this->pagingShow = 1;
        $this->tplFile    = "_common/_list.tpl";
        $this->nameFile_Export = "Report-".date("YMd.His");
        $this->arrHeaders = array();
        $this->arrData    = array();
        $this->url        = "";
    }

    function pagingShow($show)
    {
        $this->pagingShow = (int)$show;
    }

    function setTplFile($tplFile)
    {
        $this->tplFile  = $tplFile;
    }

    function getTitle()
    {
        return $this->title;
    }

    function setTitle($title)
    {
        $this->title = $title;
    }

    function getIcon()
    {
        return $this->icon;
    }

    function setIcon($icon)
    {
        $this->icon = $icon;
    }

    function getWidth()
    {
        return $this->width;
    }

    function setWidth($width)
    {
        $this->width = $width;
    }

    function setURL($arrURL)
    {
        if (is_array($arrURL))
            $this->url = construirURL($arrURL, array('nav', 'start', 'logout'));
        else
            $this->url = $arrURL;
    }

    function getColumns()
    {
        return $this->arrHeaders;
    }

    function setColumns($arrColumns)
    {
        $arrHeaders = array();

        if(is_array($arrColumns) && count($arrColumns)>0){
            foreach($arrColumns as $k => $column){
                $arrHeaders[] = array(
                    "name"      => $column,
                    "property1" => "");
            }
        }
        $this->arrHeaders = $arrHeaders;
    }

    function getData()
    {
        return $this->arrData;
    }

    function setData($arrData)
    {
        if(is_array($arrData) && count($arrData)>0)
            $this->arrData = $arrData;
    }

    function fetchGrid($arrGrid=array(), $arrData=array(), $arrLang=array())
    {
        if(isset($arrGrid["title"]))
            $this->title = $arrGrid["title"];
        if(isset($arrGrid["icon"]))
            $this->icon  = $arrGrid["icon"];
        if(isset($arrGrid["width"]))
            $this->width = $arrGrid["width"];

        if(isset($arrGrid["start"]))
            $this->start = $arrGrid["start"];
        if(isset($arrGrid["end"]))
            $this->end   = $arrGrid["end"];
        if(isset($arrGrid["total"]))
            $this->total = $arrGrid["total"];

        if(isset($arrGrid['url'])) {
            if (is_array($arrGrid['url']))
                $this->url = construirURL($arrGrid['url'], array('nav', 'start', 'logout'));
            else
                $this->url = $arrGrid["url"];
        }

        if(isset($arrGrid["columns"]) && count($arrGrid["columns"]) > 0)
            $this->arrHeaders = $arrGrid["columns"];
        if(isset($arrData) && count($arrData) > 0)
            $this->arrData = $arrData;


        $export = $this->exportType();

        switch($export){
            case "csv":
                $content = $this->fetchGridCSV($arrGrid, $arrData);
                break;
            case "pdf":
                $content = $this->fetchGridPDF();
                break;
            case "xls":
                $content = $this->fetchGridXLS();
                break;
            default: //html
                $content = $this->fetchGridHTML();
                break;
        }
        return $content;
    }

    function fetchGridCSV($arrGrid=array(), $arrData=array())
    {
        if(isset($arrGrid["columns"]) && count($arrGrid["columns"]) > 0)
            $this->arrHeaders = $arrGrid["columns"];
        if(isset($arrData) && count($arrData) > 0)
            $this->arrData = $arrData;

        header("Cache-Control: private");
        header("Pragma: cache");    // Se requiere para HTTPS bajo IE6
        header('Content-Disposition: attachment; filename="'."{$this->nameFile_Export}.csv".'"');
        header("Content-Type: text/csv; charset=UTF-8");

        $numColumns = count($this->getColumns());
        $this->smarty->assign("numColumns", $numColumns);
        $this->smarty->assign("header",     $this->getColumns());
        $this->smarty->assign("arrData",    $this->getData());

        return $this->smarty->fetch("_common/listcsv.tpl");
    }

    function fetchGridPDF()
    {
        $pdf= new paloPDF();
        $pdf->setOrientation("L");
        $pdf->setFormat("A3");            
        //$pdf->setLogoHeader("themes/elastixwave/images/logo_elastix.gif");
        $pdf->setColorHeader(array(5,68,132));
        $pdf->setColorHeaderTable(array(227,83,50));
        $pdf->setFont("Verdana");
        $pdf->printTable("{$this->nameFile_Export}.pdf", $this->getTitle(), $this->getColumns(), $this->getData());
        return "";
    }

    function fetchGridXLS()
    {
        header ("Cache-Control: private");
        header ("Pragma: cache");    // Se requiere para HTTPS bajo IE6
        header ('Content-Disposition: attachment; filename="'."{$this->nameFile_Export}.xls".'"');
        header ("Content-Type: application/vnd.ms-excel; charset=UTF-8");

        $tmp = $this->xlsBOF();
        # header
        $headers = $this->getColumns();
        foreach($headers as $i => $header)
            $tmp .= $this->xlsWriteCell(0,$i,$header["name"]);

        #data
        $data = $this->getData();
        foreach($data as $k => $row) {
            foreach($row as $i => $cell){
                $tmp .= $this->xlsWriteCell($k+1,$i,$cell);
            }
        }
        $tmp .= $this->xlsEOF();
        echo $tmp;
    }

    function fetchGridHTML($arrLang=array())
    {
        $this->smarty->assign("pagingShow",$this->pagingShow);

        $this->smarty->assign("title", $this->getTitle());
        $this->smarty->assign("icon",  $this->getIcon());
        $this->smarty->assign("width", $this->getWidth());

        $this->smarty->assign("start", $this->start);
        $this->smarty->assign("end",   $this->end);
        $this->smarty->assign("total", $this->total);

        if(!empty($this->url))
            $this->smarty->assign("url",   $this->url);

        $numColumns = count($this->getColumns());
        $this->smarty->assign("numColumns", $numColumns);
        $this->smarty->assign("header",     $this->getColumns());
        $this->smarty->assign("arrData",    $this->getData());

        $this->smarty->assign("enableExport", $this->enableExport);

        //dar el valor a las etiquetas segun el idioma
        $etiquetas = array('Export','Start','Previous','Next','End');
        foreach ($etiquetas as $etiqueta)
            $this->smarty->assign("lbl$etiqueta", _tr($etiqueta));

        return $this->smarty->fetch($this->tplFile);
    }

    function showFilter($htmlFilter)
    {
        $this->smarty->assign("contentFilter", $htmlFilter);
    }

    function calculatePagination()
    {
        $accion = getParameter("nav");
        $start  = getParameter("start");

        $this->setOffsetValue($this->getOffSet($this->getLimit(),$this->getTotal(),$accion,$start));
        $this->setEnd(($this->getOffsetValue() + $this->getLimit()) <= $this->getTotal() ? $this->getOffsetValue() + $this->getLimit() : $this->getTotal());
        $this->setStart(($this->getTotal()==0) ? 0 : $this->getOffsetValue() + 1);
    }

    function calculateOffset()
    {
        $this->calculatePagination();
        return $this->getOffsetValue();
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

    function enableExport()
    {
        $this->enableExport = true;
    }

    function setLimit($limit)
    {
        $this->limit = $limit;
    }

    function setTotal($total)
    {
        $this->total = $total;
    }

    function setOffsetValue($offset)
    {
        $this->offset = $offset;
    }

    function setStart($start)
    {
        $this->start = $start;
    }

    function setEnd($end)
    {
        $this->end = $end;
    }

    function getLimit()
    {
        return $this->limit;
    }

    function getTotal()
    {
        return $this->total;
    }

    function getOffsetValue()
    {
        return $this->offset;
    }

    function getEnd()
    {
        return $this->end;
    }

    function exportType()
    {
        if(getParameter("exportcsv") == "yes")
            return "csv";
        else if(getParameter("exportpdf") == "yes")
            return "pdf";
        else if(getParameter("exportspreadsheet") == "yes")
            return "xls";
        else
            return "html";
    }

    function isExportAction()
    {
        if(getParameter("exportcsv") == "yes")
            return true;
        else if(getParameter("exportpdf") == "yes")
            return true;
        else if(getParameter("exportspreadsheet") == "yes")
            return true;
        else
            return false;
    }

    function setNameFile_Export($nameFile)
    {
        $this->nameFile_Export = "$nameFile-".date("YMd.His");
    }

    function xlsBOF()
    {
        $data = pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
        return $data;
    }

    function xlsEOF()
    {
        $data = pack("ss", 0x0A, 0x00);
        return $data;
    }

    function xlsWriteNumber($Row, $Col, $Value)
    {
        $data  = pack("sssss", 0x203, 14, $Row, $Col, 0x0);
        $data .= pack("d", $Value);
        return $data;
    }

    function xlsWriteLabel($Row, $Col, $Value )
    {
        $Value2UTF8=utf8_decode($Value);
        $L = strlen($Value2UTF8);
        $data  = pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x0, $L);
        $data .= $Value2UTF8;
        return $data;
    }

    function xlsWriteCell($Row, $Col, $Value )
    {
        if(is_numeric($Value))
            return $this->xlsWriteNumber($Row, $Col, $Value);
        else
            return $this->xlsWriteLabel($Row, $Col, $Value);
    }
}
?>