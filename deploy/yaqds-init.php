<?php

define('APP_BASEDIR','/opt/yaqds');
define('APP_LIBDIR',APP_BASEDIR.'/lib');
define('APP_WEBDIR',APP_BASEDIR.'/www');
define('APP_CACHEDIR',APP_BASEDIR.'/cache');   // dynamic cache
define('APP_CONFIGDIR',APP_BASEDIR.'/etc');    // static configurations
define('APP_DATADIR',APP_BASEDIR.'/data');     // static private file data
define('APP_VARDIR',APP_BASEDIR.'/var');       // dynamic file data
define('APP_LOGDIR',APP_BASEDIR.'/log');       // logs
define('APP_LOCALDIR',APP_BASEDIR.'/local');   // scripts for cron, daemons, scheduler

set_include_path(get_include_path().PATH_SEPARATOR.APP_LIBDIR);

?>
