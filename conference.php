<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: /#login'); });

	// Conference security
	if (!isset($_SESSION['user']) || !$app->is_my_conf($_REQUEST['slug'], $_SESSION['user']['userID'])) throw new Exception('un-authed'); // TODO: fix!

	// Edit Mode
	$is_edit = isset($_REQUEST['edit']);
	if ($is_edit && $_SESSION['user']['admin'] != 'true') header('Location: ?'); // permissions
	$path = $is_edit ? 'conf-edit' : 'conf';
	$wrap = $is_edit ? 'form method="POST" action="./' . $_REQUEST['slug'] . '?edit"' : 'div';

	// Save Data
	if ($is_edit && isset($_REQUEST['conferenceID']) && isset($_REQUEST['locationID'])) $app->save_conf($_REQUEST);

	// Get conference
	if (false === ($event = $app->get_conf($_REQUEST['slug']))) throw new Exception('Un-found event');
	$title = $event['title'];
	include('tpl/head.tpl.html');
?>

<?php if ($is_edit): ?>
	<!-- pre-loading tinymce style + js -->
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/skins/lightgray/skin.min.css">
	<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/tinymce.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/themes/modern/theme.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/plugins/link/plugin.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/plugins/image/plugin.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/4.0.21/plugins/code/plugin.min.js"></script>
	<script src="./jquery.tinymce.min.js"></script>

	<script src="./edit.js"></script>
	<script>
		var MCE_OBJ = {
			selector: 'textarea',
			menubar: false,
			toolbar_items_size: 'small',
			plugins: 'link image code',
			toolbar: "undo redo | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code",
			setup: function () { // cleanup for angular
				delete MCE_OBJ.selector;
				delete MCE_OBJ.setup;
			}
		};
		tinymce.init( MCE_OBJ );
	</script>
<?php endif; ?>

<<?=$wrap; ?> id="wrap">
	<?php if ($is_edit): ?>
		<input type="hidden" id="conferenceID" name="conferenceID" value="<?=$event['conferenceID']; ?>" />
		<input type="hidden" name="locationID" value="<?=$event['locationID']; ?>" />
	<?php endif; ?>

	<?php $panel_include('tpl/' . $path . '/home.frame.html',      'home',      $event); ?>
	<?php $panel_include('tpl/' . $path . '/about.frame.html',     'about',     $event); ?>
	<?php $panel_include('tpl/' . $path . '/location.frame.html',  'location',  $event); ?>
	<?php if (count($event['speakers']) > 0 || $is_edit) $panel_include('tpl/' . $path . '/speakers.frame.html',  'speakers',  $event); ?>
	<?php if (count($event['agenda']) > 0 || $is_edit)   $panel_include('tpl/' . $path . '/agenda.frame.html',    'agenda',    $event); ?>
	<?php $panel_include('tpl/' . $path . '/attendees.frame.html', 'attendees', $event); ?>
	<?php $panel_include('tpl/' . $path . '/sponsors.frame.html',  'sponsors',  $event); ?>
</<?=$wrap; ?>><!-- ./wrap -->


<?php include('tpl/foot.tpl.html'); ?>