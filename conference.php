<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: /#login'); });

	if (!isset($_SESSION['user']) || !$app->is_my_conf($_REQUEST['slug'], $_SESSION['user']['userID'])) throw new Exception('un-authed');

	if (false === ($event = $app->get_conf($_REQUEST['slug']))) throw new Exception('Un-found event');
	$title = $event['title'];
	include('tpl/head.tpl.html');

	$is_edit = isset($_REQUEST['edit']);
	$path = $is_edit ? 'conf-edit' : 'conf';
	$wrap = $is_edit ? 'form' : 'div';
?>

<?php if ($is_edit): ?>
	<script src="//tinymce.cachefly.net/4.0/tinymce.min.js"></script>
	<script src="./edit.js"></script>
	<script>
		tinymce.init({
			selector: 'textarea',
			menubar: false,
			toolbar_items_size: 'small',
		});
	</script>
<?php endif; ?>

<<?=$wrap; ?> id="wrap">
	<?php $panel_include('tpl/' . $path . '/home.frame.html',      'home',      $event); ?>
	<?php $panel_include('tpl/' . $path . '/about.frame.html',     'about',     $event); ?>
	<?php $panel_include('tpl/' . $path . '/location.frame.html',  'location',  $event); ?>
	<?php if (count($event['speakers']) > 0 || $is_edit) $panel_include('tpl/' . $path . '/speakers.frame.html',  'speakers',  $event); ?>
	<?php if (count($event['agenda']) > 0 || $is_edit)   $panel_include('tpl/' . $path . '/agenda.frame.html',    'agenda',    $event); ?>
	<?php $panel_include('tpl/' . $path . '/attendees.frame.html', 'attendees', $event); ?>
	<?php $panel_include('tpl/' . $path . '/sponsors.frame.html',  'sponsors',  $event); ?>
</<?=$wrap; ?>><!-- ./wrap -->


<?php include('tpl/foot.tpl.html'); ?>