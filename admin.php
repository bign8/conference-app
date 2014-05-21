<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: /#login'); });

	// Admin security
	if (!isset($_SESSION['user']) || $_SESSION['user']['admin'] != 'true') throw new Exception('un-authed'); // TODO: fix!

	$title = 'Event Administration';
	$menu = 'admin.menu.html';
	include('tpl/parts/head.tpl.html');
?>

<script src="./admin.js"></script>

<div id="wrap">
	<?php $panel_include('tpl/admin/manage.frame.html', 'home'); ?>
</div><!-- ./wrap -->


<?php include('tpl/parts/foot.tpl.html'); ?>