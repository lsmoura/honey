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

require_once("dispatch/src/dispatch.php");

require_once("honey.php");

global $webroot;
global $sitedir;

global $honeyRoot;
$honeyRoot = realpath(dirname(__FILE__));

/*
require_once("s3/s3.php");

s3('accessKey', 'asdf');
s3('secretKey', 'secret');
s3('dump');
*/

if (!isset($webroot) || is_null($webroot)) {
	$webroot = "/webroot";
}

on('GET', '/', function() {
	$posts = getPostFileList();
	
	$content = '';
	foreach ($posts as $slug => $post) {
		$postcontent = $post['data'];

		$contents = honeyMarkdown($postcontent);
		$contents = preg_replace('/<h[1-6].*>.*?<\/h[1-6]>/', '', $contents, 1);

		$content .= '<div class="blog-entry">';
		$content .= '<h1 class="blog-entry-title">' . $post['meta']['title'] . '</h1>';
		$content .= '<p class="blog-entry-meta">Published by ' . $post['meta']['author_name'] . ' on <a href="/post/' . $slug . '">' . $post['meta']['published_date'] . '</a></p>';
		$content .= '<div class="blog-contents">';
		$content .= $contents;
		$content .= '</div>';	// blog-contents
		$content .= '</div>';	// blog-entry
	}
	$content .= '</div>';

	honeyContent($content);
});

on('POST', '/editor/update', function() {
	honeyHeader();
	echo("<h1>Blog post</h1>\n");

	$content = params('content');
	$htmlContent = honeyMarkdown($content);

	echo('<div class="content">' . $htmlContent . '</div>');
	honeyFooter();
});


on('GET', '/post/:slug', function($slug) {
	$post = honeyGetPost($slug);
	if ($post == null) {
		error('404', 'Page not found');
		return;
	}

	$content = honeyMarkdown($post['data']);
	$content .= '<p class="blog-entry-meta">Published by ' . $post['meta']['author_name'] . ' on <a href="/post/' . $slug . '">' . $post['meta']['published_date'] . '</a></p>';

	honeyContent($content);
});

// Login/logout procedures
on('GET', '/login', function() {
	$auth = cookie('honey');
	
	if ($auth != null && !empty($auth)) {
		// Check authentication credentials
		if (honeyCheckCredentials($auth) == true) {
			redirect('/admin/settings');
			return;
		}
	}

	honeyHeader();
	honeyMenu();

	$loginerror = flash('loginerror');
	?>
	<div class="container"><form method="post" action="/login" role="form">
		<div class="form-group <?php if($loginerror == 1) echo("has-error has-feedback"); ?>">
			<label for="password">Credentials</label>
			<input id="password" name="password" class="form-control" type="password" placeholder="password" />
			<?php if($loginerror == 1): ?>
				<span class="glyphicon glyphicon-remove form-control-feedback"></span>
			<?php endif; ?>
		</div>
		<button type="submit" class="btn btn-default">Login</button>
	</form></div>
	<?php
	honeyFooter();
});

on('POST', '/login', function() {
	$pw = params('password');
	if (honeyLogin($pw) == true) {
		redirect('/admin/settings');
	}
	else {
		flash('loginerror', '1');
		redirect('/login');
	}
});

on('GET', '/logout', function() {
	cookie('honey', '');
	redirect('/');
});

// === Admin route ===

// Admin security check
before('/^admin\//', function($method, $path) {
	if (honeyPassword('has') == false) {
		if ($path == 'admin/password') {
			// All good!
			return;
		}
		redirect('/admin/password');
		return;
	}

	// Check the credentials
	$auth = cookie('honey');
	if ($auth != null && !empty($auth)) {
		// Check authentication credentials
		if (honeyCheckCredentials($auth) == true) {
			// All good!
			return;
		}
	}

	redirect('/login');
	return;
});

// Admin functions
prefix('admin', function() {
	on('GET', '/', function() {
		redirect('/admin/posts');
	});

	on('GET', '/posts', function() {
		$allPosts = getPostFileList();

		$onLoad = '$(".post-item").click(function() {
			content = $(this).data("content");
			$("#post-preview").html(marked(content));
			$(".post-item").removeClass("active");
			$(this).addClass("active");
			$("#post-edit").data("slug", $(this).data("slug"));
			$("#post-title").html($(this).data("title"));
		});
		$("#post-edit").click(function() {
			slug = $(this).data("slug");
			window.location = "/admin/posts/edit/" + slug;
		});
		$("#post-listing-1").click();';

		honeyHeader($onLoad, true);
		honeyAdminMenu();
		$i = 1;
		?>
		<div class="container-fluid">
		<div id="content" class="row">
			<div class="col-md-2">
				<div class="panel panel-default">
					<div class="panel-heading">Posts<a href="/admin/posts/new"><span class="glyphicon glyphicon-plus pull-right"></span></a></div>
					<ul class="list-group">
					<?php foreach($allPosts as $slug => $post):
						$search = array("\n", '"', "'");
						$replace = array("&#10;", "&#34;", "&#39;");
						$safe_content = str_replace($search, $replace, $post['data']);
						$safe_content = str_replace("\r", "", $safe_content);
					?>
						<li class="list-group-item post-item" id="post-listing-<?php echo($i++); ?>" data-slug="<?php echo($slug); ?>" data-title="<?php echo($post['meta']['title']); ?>" data-content="<?php echo($safe_content); ?>">
							<h4 class="list-group-item-heading"><?php echo($post['meta']['title']); ?></h4>
						    <p class="list-group-item-text">&nbsp;<small class="pull-right"><?php echo($post['meta']['published_date']); ?></small></p>
						</li>
					<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<div class="col-md-10">
				<div class="panel panel-default">
					<div class="panel-heading">
						<span id="post-title">Preview</span>
						<span class="pull-right">
							<a id="post-edit" class="post-action" href="#"><span class="glyphicon glyphicon-pencil"></span></a>
							<a id="post-trash" class="post-action" href="#"><span class="glyphicon glyphicon-trash post-action"></span></a>
							<a id="post-settings" class="post-action" href="#"><span class="glyphicon glyphicon-cog post-action"></span></a>
						</span>
					</div>
					<div id="post-preview" class="panel-body"></div>
				</div>
			</div>
		</div>
		</div>
		<?php
		honeyFooter();
	});

	on('POST', '/posts/save', function() {
		global $sitedir;
		global $honeyRoot;
		
		$title = null;
		$content = params('content');
		$meta = array();

		// Setup our title
		$line = strtok($content, "\n");
		while ($line != '' && $title == null) {
			if ($line[0] == '#') {
				$title = honeyTitleCleanup($line);
			}

			$line = strtok("\n");
		}
		if ($title == null) {
			$title = honeyTitleCleanup(strtok($content, "\n"));
		}

		if (params('slug') == '') {
			if ($title == null) {
				// TODO: Properly handle this
				die("Invalid post!");
			}
		}

		// Setup some metadata
		if (params('slug') == '') {

			// create a filename
			$filename = honeyFilenameFromTitle($title);
			$fn = $filename;
			$i = 1;

			// Check for a suitable filename
			while (file_exists($honeyRoot . '/' . $sitedir . '/content/' . $fn . '.markdown')) {
				$fn = $filename . '-' . $i++;
			}

			$meta['published_date'] = date(DATE_ATOM);
			$meta['title'] = $title;
		}
		else {
			$fn = params('slug');
			$post = honeyGetPost($fn);
			$meta = $post['meta'];
			$meta['updated_date'] = date(DATE_ATOM);

			if ($title != null)
				$meta['title'] = $title;
		}

		// Save our post
		file_put_contents($honeyRoot . '/' . $sitedir . '/content/' . $fn . '.markdown', $content);
		file_put_contents($honeyRoot . '/' . $sitedir . '/content/' . $fn . '.meta', json_encode($meta));

		redirect("/posts");
	});

	on('GET', '/posts/edit/:slug', function($slug) {
		$data = honeyGetPost($slug);
		honeyEditor($data['data'], $slug);
	});

	on('GET', '/posts/new', function() {
		honeyEditor();
	});

	on('GET', '/settings', function(){
		honeyHeader(null, true);
		honeyAdminMenu();
		?>
		<div class="container"><form method="post" action="/admin/settings" role="form">
			<div class="form-group">
				<label for="sitename">Site name</label>
				<input id="sitename" name="sitename" type="text" placeholder="blog name" class="form-control" value="<?php echo(honeyGetConfig('sitename')) ?>" />
			</div>
			<div class="form-group">
				<label form="siteslogan">Slogan</label>
				<input id="siteslogan" name="siteslogan" type="text" placeholder="your slogan here" class="form-control" value="<?php echo(honeyGetConfig('siteslogan')) ?>" />
			</div>
			<button type="submit" class="btn btn-default">Save</button>
		</form></div>
		<?php
		honeyFooter();
	});

	on('POST', '/settings', function() {
		honeySetConfig('sitename', params('sitename'));
		honeySetConfig('siteslogan', params('siteslogan'));
		redirect('/admin/settings');
	});

	on('GET', '/password', function() {
		honeyHeader(null, true);
		honeyAdminMenu();
		?>
		<div class="container"><form method="post" action="/admin/password" role="form">
			<div class="form-group">
				<label for="password1">Password</label>
				<input type="password" id="password1" name="password1" type="text" placeholder="password" class="form-control" />
				<span class="help-block"><?php echo(flash('message')); ?></span>
			</div>
			<div class="form-group">
				<label form="password2">Confirm password</label>
				<input type="password" id="password2" name="password2" type="text" placeholder="confirm your password" class="form-control" />
			</div>
			<button type="submit" class="btn btn-default">Save</button>
		</form></div>
		<?php
		honeyFooter();
	});

	on('POST', '/password', function() {
		$pw = params('password1');
		if ($pw == null || $pw == '') {
			flash('message', 'Password may not be empty');
			redirect('/admin/password');
			return;
		}

		if ($pw != params('password2')) {
			flash('message', 'Passwords does not match');
			redirect('/admin/password');
			return;
		}

		honeyPassword('set', $pw);
		cookie('honey', '');

		redirect('/login');
	});

	on('GET', '/info', function() {
		honeyHeader(null, true);
		honeyAdminMenu();
		?>
		<div class="container"><pre><?php print_r($_SERVER); ?></pre></div>
		<?php
		honeyFooter();
	});
});

// All done. Let's load honey up!
dispatch();
