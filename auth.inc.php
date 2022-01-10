<?php

/*
 * ViaThinkSoft LogViewer
 * Copyright 2018-2022 Daniel Marschall, ViaThinkSoft
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

// Please reply this code with your actual implementation

function logviewer_check_access()/*: void*/ {
	// Here you can add some code for general access. If you don't want the user to access LogViewer, throw an Exception here.

	// if (check_login()) {
	//	throw new Exception("Please login first");
	// }
}

function logviewer_allow_solvemark(): bool {
	return true; // allow
}

function logviewer_additional_filter(): string {
	$filter = ''; // no filter

	// Example:
	// $filter = '`text` like '%/home/foobar/%'

	return $filter;
}
