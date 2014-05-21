<?php include('tpl/parts/head.tpl.html'); ?>

<div id="wrap">
	<?php $panel_include('tpl/frames/home.frame.html', 'home'); ?>
	<?php $panel_include('tpl/frames/about.frame.html', 'about'); ?>
	<?php if (!$auth) $panel_include('tpl/frames/login.frame.html', 'login'); ?>
	<?php if ($auth) $panel_include('tpl/frames/conf.frame.html', 'conf'); ?>
</div><!-- ./wrap -->

<?php include('tpl/parts/foot.tpl.html'); ?>