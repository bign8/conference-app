<?php
	require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
	set_exception_handler(function () { header('Location: .'); });
	if (false === ($event = $app->get_conf($_REQUEST['slug']))) throw new Exception('Un-found event');
	$title = $event['title'];
	include('tpl/head.tpl.html');
?>

<div id="wrap">
	<?php $panel_include('tpl/conf/home.frame.html',      'home',      $event); ?>
	<?php $panel_include('tpl/conf/about.frame.html',     'about',     $event); ?>
	<?php $panel_include('tpl/conf/location.frame.html',  'location',  $event); ?>
	<?php $panel_include('tpl/conf/speakers.frame.html',  'speakers',  $event); ?>
	<?php $panel_include('tpl/conf/agenda.frame.html',    'agenda',    $event); ?>
	<?php $panel_include('tpl/conf/attendees.frame.html', 'attendees', $event); ?>
	<?php $panel_include('tpl/conf/sponsors.frame.html',  'sponsors',  $event); ?>
</div><!-- ./wrap -->


<?php include('tpl/foot.tpl.html'); ?>