<?php

/*
 * ViaThinkSoft LogViewer
 * Copyright 2018-2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

define('COUNT', 200);

define('MAXYEARS', 1);

define('apache_log_locations', array('/home/*/log/error*.log*', '/home/*/hosts/*/log/error*.log*', '/var/log/apache2/error*.log*'));

define('php_log_locations', array('/home/*/log/php_error*.log*', '/home/*/hosts/*/log/php_error*.log*', '/var/log/php/error.log*'));

// 0 = No source is displayed
// 1 = Columns "source" (=logfile) and "module" (module)
// 2 = Column "user" which tries to find out the user's home dir from the logfile
define('SOURCE_STYLE', 2);

// Don't look at .gz files. But if you enable it, then it is important that you run insert_logs before log-rotating.
define('IGNORE_GZ', false);

define('MAX_DAYS_LOGFILE', 30); // only read the logfiles of the last 30 days
