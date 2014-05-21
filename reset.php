<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: .'); });
	if (!isset($_REQUEST['hash']) || !(new User())->reset_valid($_REQUEST['hash'])) throw new Exception();
	$title = 'Reset Password';
	include('tpl/parts/head.tpl.html');
?>

<div id="wrap">
	<?php $panel_include('tpl/frames/reset.frame.html', 'home'); ?>
</div><!-- ./wrap -->

<?php include('tpl/parts/foot.tpl.html'); ?>
