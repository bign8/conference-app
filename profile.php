<?php
	$title = 'Profile';
	$menu = 'profile.menu.html';
	include('tpl/parts/head.tpl.html');
?>

<div id="wrap">
	<?php $panel_include('tpl/frames/profile.frame.html', 'home'); ?>
</div><!-- ./wrap -->

<?php include('tpl/parts/foot.tpl.html'); ?>