<?php
	$title = 'Profile';
	$menu = 'profile.menu.html';
	include('tpl/parts/head.tpl.html');
?>

<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/skins/lightgray/skin.min.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/tinymce.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/themes/modern/theme.min.js"></script>
<script type="text/javascript">
	tinymce.init({
		selector: 'textarea',
		menubar: false,
		toolbar_items_size: 'small'
	});
</script>

<div id="wrap">
	<?php $panel_include('tpl/frames/profile.frame.html', 'home'); ?>
</div><!-- ./wrap -->

<?php include('tpl/parts/foot.tpl.html'); ?>