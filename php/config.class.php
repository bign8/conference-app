<?php

class config {

	// For myMail class
	const myMail_from_email   = 'info@upstreamacademy.com';
	const myMail_from_name    = 'Upstream Academy';
	const myMail_notify_email = 'nwoods@azworld.com';
	const myMail_notify_name  = 'Nathan Woods';

	// For database connection scheme
	const myPDO_user = '';
	const myPDO_pass = '';
	static $myPDO_op = array();
	static function myPDO_getDSN() { // mysql: 'mysql:host=%s;dbname=%s'
		return implode(DIRECTORY_SEPARATOR, array('sqlite:' . __DIR__ , 'db.db')) ;
	}
	
	// Misc use
	const encryptSTR   = 'BeiFZOUX74CNdPQeeVFy'; // courtesy of random.org/strings
	const phpLitePass  = 'alabaster'; // password for phpLiteAdmin (used in phpLiteAdmin.config.php)
}