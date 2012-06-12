    global $arrConf;
    global $arrConfModule;

    $arrConfModule['module_name']       = '{MODULE_ID}';
    $arrConfModule['templates_dir']     = 'themes';
    //ex1: $arrConfModule['dsn_conn_database'] = "sqlite3:///$arrConf[elastix_dbdir]/base_name.db";
    //ex2: $arrConfModule['dsn_conn_database'] = "mysql://user:password@ip_host_sever_mysql/base_name";
    $arrConfModule['dsn_conn_database'] = '';
