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
global $sitedir;

$sitedir = "site";

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
	global $honeyRoot;
	global $sitedir;
	$files = array_diff(scandir($honeyRoot . '/' . $sitedir . '/content/'), array('.', '..'));

	$entries = array();

	foreach($files as $file) {
		$filename = $honeyRoot . '/' . $sitedir . '/content/' . $file;

		$info = pathinfo($filename);
		$name = $info['filename'];
		$ext  = $info['extension'];

		$data = file_get_contents($filename);
		if (!array_key_exists($name, $entries)) {
			$entries[$name] = array('source' => '', 'data' => '', 'meta' => array('title' => ''));
		}
		if ($ext == 'markdown') {
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

function __honeyDefaultConfigValue($key) {
	static $honeyDefaultValues = array(
		'sitename' => 'Honey',
		'siteslogan' => 'Lightweight, database-free blogging platform.',
		'sitedir' => 'site',
		'theme' => 'default',
		'admin_theme' => 'default'
	);

	if (array_key_exists($key, $honeyDefaultValues))
		return($honeyDefaultValues[$key]);

	return(null);
}

function __honeyConfig($action, $key, $param = null) {
	// Need to find an alternative for all these globals...
	global $sitedir;
	global $honeyRoot;

	// Stores all our static information here
	static $honeyConfig = null;

	// If not already, reads config information from disk (if any)
	if ($honeyConfig == null) {
		$fn = $honeyRoot . '/' . $sitedir . '/config.meta';
		if (file_exists($fn) == true) {
			$data = file_get_contents($fn);
			$honeyConfig = json_decode($data, true);
		}
		else {
			$honeyConfig = array();
		}
	}

	if (strtolower($action) == 'get') {
		// No returning password hash for anyone!
		if ($key == 'password') {
			return(null);
		}
		if (array_key_exists($key, $honeyConfig)) {
			return($honeyConfig[$key]);
		}
		elseif ($param != null) {
			return($param);
		}
		else {
			return(__honeyDefaultConfigValue($key));
		}
	}
	elseif (strtolower($action) == 'set') {
		if ($param == null) {
			// Delete value
			if (array_key_exists($key, $honeyConfig)) {
				unset($honeyConfig[$key]);
			}
		}
		else {
			$honeyConfig[$key] = $param;
		}

		// Save settings to file
		$fn = $honeyRoot . '/' . $sitedir . '/config.meta';
		file_put_contents($fn, json_encode($honeyConfig));
	}
	elseif (strtolower($action) == 'password') {
		if (strtolower($key) == 'set') {
			$honeyConfig['password'] = md5($param);

			// Save settings to file
			$fn = $honeyRoot . '/' . $sitedir . '/config.meta';
			file_put_contents($fn, json_encode($honeyConfig));
		}
		elseif (strtolower($key) == 'check') {
			if (array_key_exists('password', $honeyConfig) == false)
				return(false);

			if ($honeyConfig['password'] == $param)
				return(true);

			return(false);
		}
		elseif (strtolower($key) == 'has') {
			if (array_key_exists('password', $honeyConfig)) {
				if (!empty($honeyConfig['password']))
					return(true);
			}
			return(false);
		}
	}
	else {
		die("__honeyConfig() invalid action: '$action'\n");
	}
}

// Set a value to the config file. A "null" value will remove the item from the config file
function honeySetConfig($key, $value = null) {
	__honeyConfig('set', $key, $value);
}

// Get the $key value from the config file, or returns $default if not set.
function honeyGetConfig($key, $default = null) {
	return(__honeyConfig('get', $key, $default));
}

// Get an specific post based on the slug
function honeyGetPost($slug) {
	global $honeyRoot;
	global $sitedir;

	$filename = $honeyRoot . '/' . $sitedir . '/content/' . $slug . '.markdown';

	if (file_exists($filename) == false) {
		return(null);
	}

	$ret = array();
	$ret['source'] = $slug . '.markdown';
	$ret['data'] = file_get_contents($filename);

	$meta_fn = $sitedir . '/content/' . $slug . '.meta';
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

	if ($url[0] == '/') {
		if ($webroot != '.')
			$ret = $webroot . "/" . $url;
	}

	return($ret);
}

// Retrieve the header of the site
function honeyHeader($onload = '', $admin = false) {
	$stylesheets = array('/bootstrap/bootstrap.min.css', '/bootstrap/bootstrap-theme.min.css');
	$scripts = array('/js/jquery-2.1.1.min.js', '/bootstrap/bootstrap.min.js');

	// Marked
	$scripts[] = '/js/marked.min.js';

	// Honey stylesheets
	$stylesheets[] = '/css/honey.css';
	if ($admin)
		$stylesheets[] = '/css/admin.css';

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
	</head>
	<?php if($admin == true): ?>
		<body class="admin">
	<?php else: ?>
		<body>
	<?php endif;
}

// Retrieve the site footer
function honeyFooter() {
	?>
    <div class="honey-footer">
		<p>The Honey blog platform by <a href="https://twitter.com/lsmoura">@lsmoura</a>.</p>
		<p><a href="#">Back to top</a></p>
    </div>
    </body>
    </html>
    <?php
}

// Admin menu
function honeyAdminMenu() {
	?>
	<div class="honey-head">
		<div class="container-fluid">
			<nav class="honey-nav">
				<h1>Honey</h1>
				<a href="/"><span class="glyphicon glyphicon-home"></span> Your Blog</a>
				<a href="/admin/posts"><span class="glyphicon glyphicon-edit"></span> Content</a>
				<a href="#"><span class="glyphicon glyphicon-picture"></span> Gallery</a>
				<span class="pull-right">
					<a href="#"><span class="glyphicon glyphicon-cog"></span></a>
					<a href="/logout"><span class="glyphicon glyphicon-log-out"></span></a>
				</span>
			</ul>
			</nav>
		</div>
	</div>
	<?php
}

// Site menu
function honeyMenu() {
	global $blog_title;
	?>
	<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li><a href="/"><?php echo(honeyGetConfig('sitename')); ?></a></li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li><a href="/admin/posts">Admin</a></li>
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

	honeyHeader($onLoad, true);
	honeyAdminMenu();
	echo('<div class="container-fluid">');
	echo("<h1>Editor</h1>\n");
	echo('<form method="post" action="/admin/posts/save">');
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
	echo("<div>");	
	honeyFooter();	
}

function honeyTitleCleanup($val) {
	$ret = $val;

	$ret = preg_replace("/^ *\#* *(.*)/","$1", $ret);
	$ret = trim($ret);

	if ($ret == '')
		$ret = null;

	return($ret);
}

function honeyFilenameFromTitle($title) {
	$ret = strtolower($title);

	$list = array(" ", '.', '/', '\\', '!', '?', '^', '&', ',', '%', '$');
	$ret = str_replace($list, "-", $ret);
	$ret = preg_replace("([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})", '', $ret);	// Source: http://stackoverflow.com/a/2021729/488212

	return($ret);
}
