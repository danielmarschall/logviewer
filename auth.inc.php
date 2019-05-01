<?php

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
