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
global $webroot;
global $sitedir;

// php-marked
require_once("php-marked/src/Marked/RegExp.php");
require_once("php-marked/src/Marked/Utils.php");
require_once("php-marked/src/Marked/Renderer.php");
require_once("php-marked/src/Marked/Lexer.php");
require_once("php-marked/src/Marked/InlineLexer.php");
require_once("php-marked/src/Marked/Parser.php");
require_once("php-marked/src/Marked/Marked.php");


config('honey.salt', 'ada15bd1a5ddf0b790ae1dcfd05a1e70');

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

			if ($honeyConfig['password'] == md5($param))
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
		elseif (strtolower($key) == 'credentials') {
			$info = $_SERVER['HTTP_USER_AGENT'];
			$ip = ip();

			$outputstring = $honeyConfig['password'] . '|' . $info . '|' . $ip . '|' . config('honey.salt');
			$credentials = md5($outputstring);

			return($credentials);
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

// Honey password handler
function honeyPassword($action, $param = null) {
	return(__honeyConfig('password', $action, $param));
}

function honeyCheckCredentials($credentials) {
	if (honeyPassword('credentials') == $credentials)
		return(true);

	return(false);
}

/* Handle global instance values.
   These settings are valid only for the current run.
 */
function honeyGlobal($key, $value = null) {
	static $globalValues = array();

	if ($value === null) {
		if (array_key_exists($key, $globalValues)) {
			return($globalValues[$key]);
		}
		return(null);
	}

	$globalValues[$key] = $value;
}

function honeyLogin($password) {
	if (honeyPassword('check', $password) == false) {
		return(false);
	}

	$credentials = honeyPassword('credentials');

	cookie('honey', $credentials);
	return(true);
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
	static $prefix = null;

	if ($prefix == null) {
		$prefix = str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
	}

	global $webroot;
	$ret = $url;

	if ($url[0] == '/') {
		$ret = $prefix . $url;
	}

	return(str_replace('//', '/', $ret));
}

function honey_stylesheets($stylesheets = array()) {
	foreach ($stylesheets as $s) {
		echo("\t" . '<link rel="stylesheet" href="' . getFullUrl($s) . '"/>' . "\n");
	}

	// Add theme stylesheets
	$cssarray = null;

	if (honeyGlobal('admin') == true) {
		$admin = honeyThemeSetting('admin');
		$cssarray = $admin['css'];
	}
	else {
		$cssarray = honeyThemeSetting('css');
	}

	if ($cssarray == null) {
		$cssarray = array();
	}

	if (is_array($cssarray) == false) {
		$cssarray = [ $cssarray ];
	}

	foreach($cssarray as $cssfile) {
		echo("\t" . '<link rel="stylesheet" href="' . honeyThemeURL($cssfile) . '"/>' . "\n");
	}
}

function honey_javascript($scripts = array()) {
	foreach ($scripts as $script) {
		echo("\t" . '<script src="' . getFullUrl($script) . '" type="text/javascript"></script>' . "\n");
	}


	$jsarray = null;

	if (honeyGlobal('admin') == true) {
		$admin = honeyThemeSetting('admin');
		$jsarray = $admin['js'];
	}
	else {
		$jsarray = honeyThemeSetting('js');
	}

	if ($jsarray == null) {
		$jsarray = array();
	}

	if (is_array($jsarray) == false) {
		$jsarray = [ $jsarray ];
	}

	foreach($jsarray as $jsfile) {
		echo("\t" . '<script src="' . honeyThemeURL($jsfile) . '" type="text/javascript"></script>' . "\n");
	}

}

function honeyContent($contents, $onload = null) {
	include(honeyThemeFile('index.php'));
}

/*
	Retrieve the filesystem path to a theme file. Useful for including the file or getting its contents.
 */
function honeyThemeFile($filename) {
	$theme = 'default';

	if (honeyGlobal('admin') != true) {
		$theme = honeyGetConfig('theme');
	}
	else {
		$theme = honeyGetConfig('admin_theme');
	}

	$fn = dirname(__FILE__) . '/webroot/themes/' . $theme . '/' . $filename;
	if (file_exists($fn) == false) {
		$fn = dirname(__FILE__) . '/webroot/themes/default/' . $filename;
	}
	return($fn);
}

/*
	Retrieves the http-accessible url for a given theme filename. Useful for referencing stylesheets, images or javascript files from inside the theme
	file structure.
 */
function honeyThemeURL($filename) {
	static $prefix = null;

	if ($prefix == null) {
		$prefix = str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
		$prefix = str_replace('//', '/', $prefix);
	}

	$theme = 'default';

	if (honeyGlobal('admin') != true) {
		$theme = honeyGetConfig('theme');
	}
	else {
		$theme = honeyGetConfig('admin_theme');
	}

	$fn = $prefix . '/themes/' . $theme . '/' . $filename;
	if (file_exists($fn) == false) {
		$fn = $prefix . '/themes/default/' . $filename;
	}

	$fn = str_replace('//', '/', $fn);

	return($fn);
}

function honeyThemeSetting($key) {
	static $themeSettings = null;

	if ($themeSettings == null) {
		if (file_exists(honeyThemeFile('theme.json')) == false) {
			die("invalid theme");
		}
		$themefile = honeyThemeFile('theme.json');
		$themedata = file_get_contents($themefile);
		$themeSettings = json_decode($themedata, true);
	}

	if (array_key_exists($key, $themeSettings)) {
		return($themeSettings[$key]);
	}

	return(null);
}

// Full page content editor
function honeyEditor($content = null, $slug = null) {
	$onLoad = "$('#editor textarea').bind('input propertychange', function() {
		$('#preview').html(marked(this.value));
	});
	$('#preview').html(marked($('#editor textarea').text()));
	$('.full-height').autosize();";

	ob_start();

	echo("<h1>Editor</h1>\n");
	echo('<form method="post" action="/admin/posts/save">');
	if ($slug != null) {
		echo('<input type="hidden" name="slug" value="' . $slug . '" />');
	}
	echo('<div class="row" id="editor-area">');
	echo('<div id="editor" class="col-md-6"><textarea class="form-control full-height" rows="10" name="content">');
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

	$htmlContent = ob_get_contents();
	ob_end_clean();

	honeyContent($htmlContent);
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

class myRenderer extends \Marked\Renderer {
	public function image($href, $title, $text) {
		global $webroot;

		$imghref = getFullUrl('/img/' . $href);

        $out = '<img src="' . $imghref . '" alt="' . $text . '"';
        if (strlen($title) > 0) {
            $out .= ' title="' . $title . '"';
        }
        $out .= $this->options['xhtml'] ? '/>' : '>';
        return $out;
	}
}

function honeyMarkdown($text) {
	$renderer = new myRenderer();
	$marked = new \Marked\Marked();
	$marked->setOptions([ 'renderer' => $renderer ]);

	$html = $marked->render($text);


	return($html);
}
