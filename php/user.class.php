<?php

require_once('myPDO.class.php');

// These constants may be changed without breaking existing hashes.
define("PBKDF2_HASH_ALGORITHM", "sha256");
define("PBKDF2_ITERATIONS", 1000);
define("PBKDF2_SALT_BYTE_SIZE", 24);
define("PBKDF2_HASH_BYTE_SIZE", 24);

// These constants (if changed) will break existing hashes.
define("HASH_SECTIONS", 4);
define("HASH_ALGORITHM_INDEX", 0);
define("HASH_ITERATION_INDEX", 1);
define("HASH_SALT_INDEX", 2);
define("HASH_PBKDF2_INDEX", 3);

/**
* User class: controlls everything that happens with a user
*/
class User {
	const E_RESET_BAD_EMAIL = 'bad-email';
	const E_RESET_BAD_HASH  = 'bad-hash';
	const E_RESET_MISSMATCH = 'missmatch';
	const E_RESET_SHORT     = 'short';

	private $db;
	public function __construct() {
		if ( session_id() === '' ) session_start();
		$this->db = myPDO::getInstance();
	}

	public function login( $email, $pass ) {
		$sth = $this->db->prepare("SELECT * FROM user WHERE email=?;");
		$test = (
			!$sth->execute( $email ) &&
			($user = $sth->fetch()) !== false && 
			validate_password( $pass, $user['pass'])
		);
		if ($test) {
			unset($user['pass']);
			$_SESSION['user'] = $user;
			$this->db->prepare("UPDATE user SET seen=CURRENT_TIMESTAMP, resetHash=NULL, resetExpire=NULL WHERE userID=?;")->execute( $user['userID'] );
		}
		return $test;
	}
	public function logout() {
		unset( $_SESSION['user'] );
	}
	public function save_profile( $data ) {
		// TODO
	}
	public function reset_send( $email ) {
		$sth = $this->db->prepare("SELECT userID, name, email FROM user WHERE email=? LIMIT 1;");
		if (
			!$sth->execute( $email ) ||
			false === ($user = $sth->fetch())
		) throw Exception(self::E_RESET_BAD_EMAIL);

		// DB
		$hash = sha1( $user['email'] . config::encryptSTR . uniqid() );
		$this->db->prepare("UPDATE user SET resetHash=?, resetExpire=date('now','+3 Days') WHERE userID=?;")->execute($hash, $user['userID']);

		// Email
		$mail = new myMail();
		$html = str_replace('{{HASH}}', $hash, file_get_contents('../tpl/reset-pass.email.html'));
		return $mail->sendMsg('Upstream Academy Conference: Password Reset', $html, $user['email'], $user['name']);
	}
	public function reset_valid( $hash ) {
		$STH = $this->db->prepare("SELECT * FROM user WHERE resetHash=? AND resetExpire > CURRENT_TIMESTAMP LIMIT 0,1;");
		if (!$STH->execute( $hash )) throw new Exception(self::E_DB_ERROR);
		return count($STH->fetchAll()) == 1;
	}
	public function reset_pass( $pass, $confirm, $hash ) {
		if (strlen($pass) < 2) throw new Exception(self::E_RESET_SHORT);
		if ($pass != $confirm) throw new Exception(self::E_RESET_MISSMATCH);
		if (!$this->valid_reset($hash)) throw new Exception(self::E_RESET_BAD_HASH);
		return $this->db->prepare("UPDATE user SET pass=?,resetHash=NULL,resetExpires=NULL WHERE resetHash=?;")->execute( create_hash($pass), $hash );
	}

	// https://crackstation.net/hashing-security.htm#phpsourcecode
	private function create_hash($password) {
		$salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM));
		return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" .  $salt . ":" . base64_encode($this->pbkdf2(
			PBKDF2_HASH_ALGORITHM,
			$password,
			$salt,
			PBKDF2_ITERATIONS,
			PBKDF2_HASH_BYTE_SIZE,
			true
		));
	}
	private function validate_password($password, $correct_hash) {
		$params = explode(":", $correct_hash);
		if(count($params) < HASH_SECTIONS) return false;
		$pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
		return $this->slow_equals( $pbkdf2, $this->pbkdf2(
			$params[HASH_ALGORITHM_INDEX],
			$password,
			$params[HASH_SALT_INDEX],
			(int)$params[HASH_ITERATION_INDEX],
			strlen($pbkdf2),
			true
		));
	}
	private function slow_equals($a, $b) {
		$diff = strlen($a) ^ strlen($b);
		for($i = 0; $i < strlen($a) && $i < strlen($b); $i++) $diff |= ord($a[$i]) ^ ord($b[$i]);
		return $diff === 0;
	}
	private function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false) {
		$algorithm = strtolower($algorithm);
		if (!in_array($algorithm, hash_algos(), true)) trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
		if ($count <= 0 || $key_length <= 0) trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
		if (function_exists("hash_pbkdf2")) {
			if (!$raw_output) $key_length = $key_length * 2;
			return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
		}
		$block_count = ceil($key_length / strlen(hash($algorithm, "", true)));
		$output = "";
		for ($i = 1; $i <= $block_count; $i++) {
			$last = $salt . pack("N", $i);
			$last = $xorsum = hash_hmac($algorithm, $last, $password, true);
			for ($j = 1; $j < $count; $j++) $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
			$output .= $xorsum;
		}
		return $raw_output ? substr($output, 0, $key_length) : bin2hex(substr($output, 0, $key_length));
	}
}

$user = new User();
