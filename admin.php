<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: /#login'); });

	// Admin security
	if (!isset($_SESSION['user']) || $_SESSION['user']['admin'] != 'true') throw new Exception('un-authed'); // TODO: fix!

	$title = 'Event Administration';
	$menu = 'admin.menu.html';
	$ng_app = 'event-admin';
	include('tpl/parts/head.tpl.html');
?>

<script src="./js/admin.js"></script>

<div id="wrap">
	<?php $panel_include('tpl/admin/home.frame.html', 'home'); ?>
	<?php $panel_include('tpl/admin/quiz.frame.html', 'quiz'); ?>
	<?php $panel_include('tpl/admin/conf.frame.html', 'conf'); ?>
	<?php $panel_include('tpl/admin/upld.frame.html', 'upld'); ?>
</div><!-- ./wrap -->


<?php include('tpl/parts/foot.tpl.html'); ?>