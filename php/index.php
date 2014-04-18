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