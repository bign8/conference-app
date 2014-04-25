<?php include('tpl/head.tpl.html'); ?>

<div id="wrap">
	<?php $panel_include('tpl/home.frame.html', 'home'); ?>
	<?php $panel_include('tpl/about.frame.html', 'about'); ?>
	<?php if (!$auth) $panel_include('tpl/login.frame.html', 'login'); ?>
	<?php if ($auth) $panel_include('tpl/conf.frame.html', 'conf'); ?>
</div><!-- ./wrap -->

<?php include('tpl/foot.tpl.html'); ?>