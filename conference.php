<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: /#login'); });

	if (!isset($_SESSION['user']) || !$app->is_my_conf($_REQUEST['slug'], $_SESSION['user']['userID'])) throw new Exception('un-authed');

	if (false === ($event = $app->get_conf($_REQUEST['slug']))) throw new Exception('Un-found event');
	$title = $event['title'];
	include('tpl/head.tpl.html');
?>

<div id="wrap">
	<?php $panel_include('tpl/conf/home.frame.html',      'home',      $event); ?>
	<?php $panel_include('tpl/conf/about.frame.html',     'about',     $event); ?>
	<?php $panel_include('tpl/conf/location.frame.html',  'location',  $event); ?>
	<?php if (count($event['speakers']) > 0) $panel_include('tpl/conf/speakers.frame.html',  'speakers',  $event); ?>
	<?php if (count($event['agenda']) > 0)   $panel_include('tpl/conf/agenda.frame.html',    'agenda',    $event); ?>
	<?php $panel_include('tpl/conf/attendees.frame.html', 'attendees', $event); ?>
	<?php $panel_include('tpl/conf/sponsors.frame.html',  'sponsors',  $event); ?>
</div><!-- ./wrap -->


<?php include('tpl/foot.tpl.html'); ?>