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
require_once("parsedown/Parsedown.php");

require_once("honey.php");

global $webroot;
global $blog_title;
global $blog_slogan;
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
	global $blog_title;
	global $blog_slogan;

	$posts = getPostFileList();
	$Parsedown = new Parsedown();
	
	honeyHeader();
	honeyMenu();
	echo('<div class="container">');
	echo('<div class="blog-header">');
	echo('<div class="blog-title">' . $blog_title . '</div>');
	echo('<p class="lead blog-description">' . $blog_slogan . '</p>');
	echo('</div>');
	foreach ($posts as $slug => $post) {
		$content = $post['data'];

		$contents = $Parsedown->text($content);
		$contents = preg_replace('/<h[1-6]>.*?<\/h[1-6]>/', '', $contents, 1);

		echo('<div class="blog-entry">');
		echo('<h1 class="blog-entry-title">' . $post['meta']['title'] . '</h1>');
		echo('<p class="blog-entry-meta">Published by ' . $post['meta']['author_name'] . ' on <a href="/post/' . $slug . '">' . $post['meta']['published_date'] . '</a></p>');
		echo('<div class="blog-contents">');
		echo($contents);
		echo('</div>');	// blog-contents
		echo('</div>');	// blog-entry
	}
	echo('</div>');
	honeyFooter();
});

on('GET', '/posts', function() {
	$allPosts = getPostFileList();

	$onLoad = '$(".post-item").click(function() {
		content = $(this).data("content");
		$("#post-preview").html(marked(content));
		$(".post-item").removeClass("active");
		$(this).addClass("active");
		$("#post-edit").data("slug", $(this).data("slug"));
	});
	$("#post-edit").click(function() {
		slug = $(this).data("slug");
		//console.log("Current slug:" + slug);
		window.location = "/posts/edit/" + slug;
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
				<div class="panel-heading">Posts<a href="/posts/new"><span class="glyphicon glyphicon-plus pull-right"></span></a></div>
				<ul class="list-group">
				<?php foreach($allPosts as $slug => $post):
					$search = array("\n", '"', "'");
					$replace = array("&#10;", "&#34;", "&#39;");
					$safe_content = str_replace($search, $replace, $post['data']);
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
					Preview
					<span class="pull-right">
						<span class="glyphicon glyphicon-pencil post-action" id="post-edit"></span>
						<span class="glyphicon glyphicon-trash post-action"></span>
						<span class="glyphicon glyphicon-cog post-action"></span>
					</span>
				</div>
				<div id="post-preview" class="panel-body"></div>
			</div>
		</div>
	</div>
	</div>
	<?php
	//echo("<pre>" . print_r($allPosts, true) . "</pre>");
	honeyFooter();
});

on('POST', '/editor/update', function() {
	honeyHeader();
	echo("<h1>Blog post</h1>\n");

	$Parsedown = new Parsedown();
	$content = params('content');
	$htmlContent = $Parsedown->text($content);

	echo('<div class="content">' . $htmlContent . '</div>');
	honeyFooter();
});


on('GET', '/post/:slug', function($slug) {
	$data = honeyGetPost($slug);
	if ($data == null) {
		error('404', 'Page not found');
		return;
	}

	honeyHeader();
	honeyMenu();
	echo('<div class="container-fluid">');

	$Parsedown = new Parsedown();
	$htmlContent = $Parsedown->text($data['data']);
	echo($htmlContent);

	echo('</div>');
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


// All done. Let's load honey up!
dispatch();
