<?php

global $webroot;

$webroot = '.';

require_once('../dispatch/src/dispatch.php');

if (config('dispatch.url') == '') {
	$dispatch_url = 'http://' . $_SERVER['HTTP_HOST'];
	$strip_url = str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);

	$dispatch_url .= $strip_url;

	config('dispatch.url', $dispatch_url);
}


require_once('../index.php');