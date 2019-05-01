<?php

define('COUNT', 200);

define('MAXYEARS', 1);

#define('apache_log_locations', array('/home/*/log/error*.log*', '/home/*/hosts/*/log/error*.log*'));
define('apache_log_locations', array());

define('php_log_locations', array('/home/*/log/php_error*.log*', '/home/*/hosts/*/log/php_error*.log*'/* , '/var/log/php/error.log*' */));

// 0 = No source is displayed
// 1 = Columns "source" (=logfile) and "module" (module)
// 2 = Column "user" which tries to find out the user's home dir from the logfile
define('SOURCE_STYLE', 2);

