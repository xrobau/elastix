//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSanto{NAME_CLASS}.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    //$pDB = new paloDB($arrConf['dsn_conn_database']);
    $pDB = "";


    //actions
    $action = getAction();
    $content = "";

    switch($action){
        default:
            $content = report{NAME_CLASS}($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
    }
    return $content;
}

function report{NAME_CLASS}($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $p{NAME_CLASS} = new paloSanto{NAME_CLASS}($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");
    $action = getParameter("nav");
    $start  = getParameter("start");
    $as_csv = getParameter("exportcsv");

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $total{NAME_CLASS} = $p{NAME_CLASS}->getNum{NAME_CLASS}($filter_field, $filter_value);

    $limit  = 20;
    $total  = $total{NAME_CLASS};
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->enableExport();   // enable csv export.
    $oGrid->pagingShow(true); // show paging section.

    $oGrid->calculatePagination($action,$start);
    $offset = $oGrid->getOffsetValue();
    $end    = $oGrid->getEnd();
    $url    = "?menu=$module_name&filter_field=$filter_field&filter_value=$filter_value";

    $arrData = null;
    $arrResult =$p{NAME_CLASS}->get{NAME_CLASS}($limit, $offset, $filter_field, $filter_value);

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){ {ARR_DATA_ROWS}
            $arrData[] = $arrTmp;
        }
    }


    $arrGrid = array("title"    => $arrLang["{NEW_MODULE_NAME}"],
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array({ARR_NAME_COLUMNS}
                                        )
                    );


    //begin section filter
    $arrFormFilter{NAME_CLASS} = createFieldFilter($arrLang);
    $oFilterForm = new paloForm($smarty, $arrFormFilter{NAME_CLASS});
    $smarty->assign("SHOW", $arrLang["Show"]);

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    if($as_csv == 'yes'){
        $name_csv = "{NAME_CLASS}_".date("d-M-Y").".csv";
        header("Cache-Control: private");
        header("Pragma: cache");
        header("Content-Type: application/octec-stream");
        header("Content-disposition: inline; filename={$name_csv}");
        header("Content-Type: application/force-download");
        $content = $oGrid->fetchGridCSV($arrGrid, $arrData);
    }
    else{
        $oGrid->showFilter(trim($htmlFilter));
        $content = "<form  method='POST' style='margin-bottom:0;' action=\"$url\">".$oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";
    }
    //end grid parameters

    return $content;
}


function createFieldFilter($arrLang){
    $arrFilter = array({ARR_FILTERS}
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => $arrLang["Search"],
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function getParameter($parameter)
{
    if(isset($_POST[$parameter]))
        return $_POST[$parameter];
    else if(isset($_GET[$parameter]))
        return $_GET[$parameter];
    else
        return null;
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_open")) 
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else
        return "report"; //cancel
}
