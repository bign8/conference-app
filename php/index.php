<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL ^ E_STRICT);

date_default_timezone_set('UTC');

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

if (isset($_REQUEST['action'])) {
	$user = new User();
	
	switch ($_REQUEST['action']) {
		case 'logout':
			$user->logout();
			die(header('Location: index.php')); // lose query string
			break;
		case 'login':
			if ($user->login($_POST['email'], $_POST['pass'])) die(header('Location: index.php#')); // TODO: direct to conference
			$app->set_error('login', 'Invalid Credentials', 'Change a few things up and try authenticating again');
			break;
		case 'reset':
			try {
				if ($user->reset_send($_POST['email'])) {
					$app->set_error('login', 'Reset Sent', 'Check your inbox for password reset instructions', 'success');
				} else {
					$app->set_error('login', 'Reset Error', 'We encountered an error processing your reset request');
				}
			} catch (GUIException $e) {
				$app->set_gui_error('login', $e);
			}
			break;
		case 'reset_pw':
			try {
				if ( $user->reset_pass($_POST['pass'], $_POST['conf'], $_POST['hash']) ) {
					die(header('Location: index.php#login')); // lose query string
				} else {
					$app->set_error('reset', 'Reset Error', 'We have encountered an unrecoverable error');
				}
			} catch (GUIException $e) {
				$app->set_gui_error('reset', $e);
			}
			break;
		case 'register':
			$user = new User();
			try {
				$res = $user->register( $_POST['name'], $_POST['email'], $_POST['pass'], $_POST['conf'] );
				if ($res) die(header('Location: index.php#'));
			} catch (PDOException $e) {
				$user->reset_send( $_POST['email'] );
				$app->set_error('register', 'Duplicate Email', 'Password reset instructions have been sent to the provided email');
			} catch (GUIException $e) {
				$app->set_gui_error('register', $e);
			}
			break;
		case 'profile':
			try {
				$res = $user->save_profile( $_POST );
				if ($res) throw new GUIException('Changes Saved', 'True story', 'success');
			} catch(GUIException $e) {
				$app->set_gui_error('profile', $e);
			}
			break;
	}
}

// $app->set_gui_error('login', new GUIException());