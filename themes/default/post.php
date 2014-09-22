<div class="blog-entry">
	<h1 class="blog-entry-title"><?php echo($post['meta']['title']); ?></h1>
	<p class="blog-entry-meta">Published by <?php echo($post['meta']['author_name']); ?> on <a href="/post/<?php echo($slug); ?>"><?php echo($post['meta']['published_date']); ?></a></p>
	<div class="blog-contents"><?php echo($post['htmlContents']); ?></div>
</div>
