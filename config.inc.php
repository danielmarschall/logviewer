<?php

define('COUNT', 200);

define('MAXYEARS', 1);

#define('apache_log_locations', array('/home/*/log/error*.log*', '/home/*/hosts/*/log/error*.log*', '/daten/admin/_probleme/php_codefixer/all_php_files.out'));
define('apache_log_locations', array('/daten/admin/_probleme/php_codefixer/all_php_files.out'));

define('php_log_locations', array('/home/*/log/php_error*.log*', '/home/*/hosts/*/log/php_error*.log*'/* , '/var/log/php/error.log*' */));

// 0 = No source is displayed
// 1 = Columns "source" (=logfile) and "module" (module)
// 2 = Column "user" which tries to find out the user's home dir from the logfile
define('SOURCE_STYLE', 2);

// Don't look at .gz files. But if you enable it, then it is important that you run insert_logs before log-rotating.
define('IGNORE_GZ', false);

define('MAX_DAYS_LOGFILE', 30); // only read the logfiles of the last 30 days
