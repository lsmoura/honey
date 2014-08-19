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

// Some defaults

// TODO: Move these to their own variable and file.
global $webroot;
global $blog_title;
global $contentdir;

$contentdir = "content";

$blog_title = 'Honey';

// Compare function for sorting posts
function __honeyPostCmp($a, $b) {
    if ($a['meta']['published_date'] == $b['meta']['published_date']) {
        return 0;
    }
    $timeA = strtotime($a['meta']['published_date']);
    $timeB = strtotime($b['meta']['published_date']);

    return ($timeA > $timeB) ? -1 : 1;
}

// Get an array with all posts published
function getPostFileList() {
	global $contentdir;
	$files = array_diff(scandir($contentdir), array('.', '..'));

	$entries = array();

	foreach($files as $file) {
		$filename = $contentdir . '/' . $file;

		$info = pathinfo($filename);
		$name = $info['filename'];
		$ext  = $info['extension'];

		$data = file_get_contents($filename);
		if (!array_key_exists($name, $entries)) {
			$entries[$name] = array('source' => '', 'data' => '', 'meta' => array('title' => ''));
		}
		if ($ext == 'md') {
			$entries[$name]['source'] = $file;
			$entries[$name]['data'] = $data;
		}
		else if ($ext == 'meta') {
			$entries[$name]['meta'] = json_decode($data, true);
		}
		else {
			$entries[$name]['_' . $ext] = $data;
		}
	}

	foreach($entries as &$entry) {
		if ($entry['meta']['title'] == '') {
			// Create a title
			$title = preg_replace("/^ *\#* *(.*)/","$1", strtok($entry['data'], "\n"));
			$entry['meta']['title'] = $title;
		}
	}

	uasort($entries, '__honeyPostCmp');
	return($entries);
}

// Get an specific post based on the slug
function honeyGetPost($slug) {
	global $contentdir;

	$filename = $contentdir . '/' . $slug . '.md';

	if (file_exists($filename) == false) {
		return(null);
	}

	$ret = array();
	$ret['source'] = $slug . '.md';
	$ret['data'] = file_get_contents($filename);

	$meta_fn = $contentdir . '/' . $slug . '.meta';
	if (file_exists($meta_fn) == true) {
		$contents = file_get_contents($meta_fn);
		$ret['meta'] = json_decode($contents, true);
	}

	return($ret);
}

// Turn a given url into a valid url
function getFullUrl($url) {
	global $webroot;
	$ret = $url;

	if ($url[0] != '/') {
		$ret = $webroot . "/" . $url;
	}

	return($ret);
}

// Retrieve the header of the site
function honeyHeader($onload = '') {
	global $webroot;

	$stylesheets = array('bootstrap/bootstrap.min.css', 'bootstrap/bootstrap-theme.min.css');
	$scripts = array('js/jquery-2.1.1.min.js', 'bootstrap/bootstrap.min.js');

	// Epic Editor
	//$scripts[] = '/epiceditor/epiceditor/js/epiceditor.min.js';

	// Marked
	$scripts[] = '/marked/marked.min.js';

	echo("<!doctype html>\n<html lang=\"en\">\n<head>\n");
	echo("\t<meta charset=\"utf-8\">\n");
	echo("\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n");
	echo("\t<title>Honey</title>\n");
	foreach ($stylesheets as $s) {
		echo("\t" . '<link rel="stylesheet" href="' . getFullUrl($s) . '"/>' . "\n");
	}
	foreach ($scripts as $script) {
		echo("\t" . '<script src="' . getFullUrl($script) . '" type="text/javascript"></script>' . "\n");
	}
	if ($onload != '') {
		// CDATA, so we're XHTML compliant
		echo("<script type=\"text/javascript\">\n//<![CDATA[\n\$(document).ready(function() { $onload });\n//]]>\n</script>");
	}
	echo("\n");
	?>
	<style>
		.post-item {
			cursor: pointer;
		}
		.post-action {
			padding: 0 4px;
			cursor: pointer;
		}
		.post-action:hover {
			color: #00f;
		}
	</style>
	<?php
	echo("</head>\n");
	echo("<body>\n");
	?>
	<div></div>
	<?php
}

// Retrieve the site footer
function honeyFooter() {
	echo("</body>\n");
	echo("</html>\n");
}

// Admin menu
function honeyAdminMenu() {
	?>
	<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li><a href="#">Your Blog</a></li>
				<li><a href="#">Content</a></li>
				<li><a href="#">Gallery</a></li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li><a href="#">Settings</a></li>
				<li><a href="#">Log Out</a></li>
			</ul>
		</div>
	</nav>
	<?php
}

// Site menu
function honeyMenu() {
	global $blog_title;
	?>
	<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li><a href="/"><?php echo($blog_title); ?></a></li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li><a href="/posts">Admin</a></li>
			</ul>
		</div>
	</nav>
	<?php	
}

// Full page content editor
function honeyEditor($content = null, $slug = null) {
	$onLoad = "$('#editor textarea').bind('input propertychange', function() {
		$('#preview').html(marked(this.value));
		//console.log(this.value);
	});
	$('#preview').html(marked($('#editor textarea').text()));";

	honeyHeader($onLoad);
	honeyAdminMenu();
	echo("<h1>Editor</h1>\n");
	echo('<form method="post" action="/posts/save">');
	if ($slug != null) {
		echo('<input type="hidden" name="slug" value="' . $slug . '" />');
	}
	echo('<div class="row" id="editor-area">');
	echo('<div id="editor" class="col-md-6"><textarea class="form-control" rows="10" name="content">');
	if ($content != null) echo($content);
	else echo("# Welcome\nWrite your new blog post using __markdown__.");
	echo("</textarea></div>");
	echo('<div id="preview" class="col-md-6"></div>');
	echo('</div>');
	echo('<div class="row">');
	echo('<div class="col-md-6">');
	echo('<button type="submit" class="btn btn-default">Submit</button>');
	echo('</div>');
	echo('</div>');
	echo('</form>');
	honeyFooter();	
}
