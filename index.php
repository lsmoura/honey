<?php
/*
Copyright (c) 2014 Sergio Moura sergio@moura.us

This software is provided 'as-is', without any express or implied
warranty.  In no event will the authors be held liable for any damages
arising from the use of this software.
Permission is granted to anyone to use this software for any purpose,
including commercial applications, and to alter it and redistribute it
freely, subject to the following restrictions:
1. The origin of this software must not be misrepresented; you must not
   claim that you wrote the original software. If you use this software
   in a product, an acknowledgment in the product documentation would be
   appreciated but is not required.
2. Altered source versions must be plainly marked as such, and must not be
   misrepresented as being the original software.
3. This notice may not be removed or altered from any source distribution.
*/

include_once("dispatch/src/dispatch.php");

function honeyHeader() {
	echo("<!doctype html>\n<html lang=\"en\">\n<head>\n");
	echo("\t<meta charset=\"utf-8\">\n");
	echo("\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n");
	echo("\t<title>Honey</title>\n");
	echo('	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>');
	echo("\n");
	echo("</head>\n");
	echo("<body>\n");
}

function honeyFooter() {
	echo("</body>\n");
	echo("</html>\n");
}

on('GET', '/', function() {
	honeyHeader();
	echo("Hello world!\n");
	honeyFooter();
});

dispatch();