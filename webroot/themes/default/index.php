<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Honey</title>
	<?php honey_stylesheets(); ?>
	<?php honey_javascript(); ?>
	<?php if (isset($onload) && $onload != null): ?>
	<script type="text/javascript">
	//<![CDATA[
	$(document).ready(function() {
		<?php echo($onload); ?>
	});
	//]]>
	</script>		
	<?php endif; ?>
</head>
<body class="<?php if(honeyGlobal('admin') == true) echo("admin"); ?>">
<?php if(honeyGlobal('admin') == true): ?>
	<div class="honey-head">
		<div class="container-fluid">
			<nav class="honey-nav">
				<h1>Honey</h1>
				<a href="<?php echo(getFullUrl('/')); ?>"><span class="glyphicon glyphicon-home"></span> Your Blog</a>
				<a href="<?php echo(getFullUrl('/admin/posts')); ?>"><span class="glyphicon glyphicon-edit"></span> Content</a>
				<a href="#"><span class="glyphicon glyphicon-picture"></span> Gallery</a>
				<span class="pull-right">
					<a href="<?php echo(getFullUrl('/admin/password')); ?>"><span class="glyphicon glyphicon-lock"></span></a>
					<a href="<?php echo(getFullUrl('/admin/settings')); ?>"><span class="glyphicon glyphicon-cog"></span></a>
					<a href="<?php echo(getFullUrl('/logout')); ?>"><span class="glyphicon glyphicon-log-out"></span></a>
				</span>
			</ul>
			</nav>
		</div>
	</div>
<?php else: ?>
	<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li><a href="<?php echo(getFullUrl('/')); ?>"><?php echo(honeyGetConfig('sitename')); ?></a></li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li><a href="<?php echo(getFullUrl('/admin/posts')); ?>">Admin</a></li>
			</ul>
		</div>
	</nav>
<?php endif; ?>
	<div class="container<?php if(honeyGlobal('admin') == true) echo('-fluid'); ?>">
		<?php if(honeyGlobal('admin') != true): ?>
		<div class="blog-header">
			<div class="blog-title"><?php echo(honeyGetConfig('sitename')); ?></div>
			<p class="lead blog-description"><?php echo(honeyGetConfig('siteslogan')); ?></p>
		</div>
		<?php endif; ?>
		<?php echo($contents); ?>
	</div>
	<div class="honey-footer">
		<p>The Honey blog platform by <a href="https://twitter.com/lsmoura">@lsmoura</a>.</p>
		<p><a href="#">Back to top</a></p>
    </div>
</body>
</html>