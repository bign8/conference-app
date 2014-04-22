<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL ^ E_STRICT);

function here($file) {
	return __DIR__ . DIRECTORY_SEPARATOR . $file;
}

if (file_exists( here('config.class.php') )) {
	require_once( here('config.class.php') );
} else {
	die('Please rename "config_blank.class.php" to "config.class.php" and configure the appropriate variables');
}

require_once( here('myPDO.class.php') );
require_once( here('myMail.class.php') );
require_once( here('user.class.php') );
require_once( here('app.class.php') );

$auth = isset($_SESSION['user']);
$app = new App();

// For perfect striped frames
$panel_include = function($path, $id = '', $event = null) use ($app, &$dumb_counter) {
	$dumb_counter++;
	echo "<div class=\"color color-$dumb_counter\">\n\t<div class=\"container\" id=\"{$id}\">";
	require($path);
	echo "\t</div>\n</div>";
	$dumb_counter %= 2;
};

if (isset($_REQUEST['action'])) switch ($_REQUEST['action']) {
	case 'logout':
		// TODO
		break;
	case 'login':
		// TODO
		break;
	case 'register':
		// TODO
		break;
	case 'profile':
		// TODO
		break;
}