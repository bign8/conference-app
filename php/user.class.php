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
	private $db;
	public function __construct() {
		if ( session_id() === '' ) session_start();
		$this->db = myPDO::getInstance();
	}

	public function login( $email, $pass ) {
		$sth = $this->db->prepare("SELECT * FROM user WHERE email=?;");
		$test = (
			$sth->execute( $email ) &&
			($user = $sth->fetch()) !== false && 
			$this->validate_password( $pass, $user['pass'] )
		);
		if ($test) {
			$_SESSION['user'] = $user;
			$this->db->prepare("UPDATE user SET seen=CURRENT_TIMESTAMP, resetHash=NULL, resetExpire=NULL WHERE userID=?;")->execute( $user['userID'] );
		}
		return $test;
	}
	public function logout() {
		unset( $_SESSION['user'] );
	}
	public function register( $name, $email, $pass, $conf ) {
		if (strlen($pass) < 3) throw new GUIException('Password is short', 'Please choose a password at least 3 characters long.');
		if ($pass != $conf) throw new GUIException('Password missmatch', 'Try typing them again');

		$this->db->prepare("INSERT INTO user (name,email,pass) VALUES (?,?,?);")->execute( $name, $email, $this->create_hash( $pass ) );
		return $this->login($email, $pass);
	}
	public function save_profile( $data ) {
		$mail = new myMail();
		if ($_SESSION['user']['userID'] != $data['userID']) throw new GUIException('Saving issue', 'We were unable to save your profile changes');

		// Old user data
		$user = $this->db->prepare("SELECT * FROM user WHERE userID=? LIMIT 1;");
		if (!$user->execute( $data['userID'] )) throw new GUIException('Database error', 'We cannot save your profile data currently');
		$old_user = $user->fetch();

		// validate + change passwords
		if ( isset($data['pass']) && $data['pass'] != '' ) {
			if ( !$this->validate_password($data['old_pass'], $old_user['pass']) ) throw new GUIException('Old Password Invalid', 'You must enter password');
			if ($data['pass'] == $data['confirm']) throw new GUIException('Password missmatch', 'We were unable to change your password');
			if (strlen($data['pass']) < 3) throw new GUIException('Password is short', 'Please choose a password at least 3 characters long.');
			$sth = $this->db->prepare("UPDATE user SET pass=? WHERE userID=?;");
			if (!$sth->execute($data['pass'], $data['userID'])) throw new GUIException('Database error', 'We cannot save you profile data currently');
		}

		// Upload photo
		if (isset($_FILES['image']) && !$_FILES['image']['error']) {
			if (false === $ext = array_search(trim($_FILES['image']['type']), array(
				'gif0' => "image/gif",
				'jpg1' => "image/jpg",
				'jpg2' => "image/jpeg",
				'jpg3' => "image/pjpeg",
				'png4' => "image/x-png",
				'png5' => "image/png"
			))) throw new GUIException('Invalid Image Type', '<br/>Currently we only accept PNGs, JPGs, and GIFs.');
			$ext = substr($ext, 0, -1);

			$basename = implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'img', 'usr')) . DIRECTORY_SEPARATOR;
			$filename = $data['userID'] . '_' . str_replace(' ', '_', $data['name']) . '.' . $ext;
			print_r($basename . $filename);
			if (file_exists($basename . $filename)) unlink($basename . $filename);
			if (file_exists($basename . $old_user['photo'])) unlink($basename . $old_user['photo']);
			if (!move_uploaded_file( $_FILES['image']['tmp_name'], $basename . $filename )) throw new GUIException('Server Error', 'Unable to upload image');
			$mail->addAttachment( $basename . $filename, $filename );
			$sth = $this->db->prepare("UPDATE user SET photo=? WHERE userID=?;");
			if (!$sth->execute($filename, $data['userID'])) throw new GUIException('Database error', 'We cannot save you profile data currently');
		}

		// Update Settings
		$sth = $this->db->prepare("UPDATE user SET name=?, title=?, firm=?, phone=?, email=?, bio=? WHERE userID=?;");
		if (!$sth->execute($data['name'], $data['title'], $data['firm'], $data['phone'], $data['email'], $data['bio'], $data['userID']))
			throw new GUIException('Something went wrong', 'and we were unable to save your changes');

		// Re-assign sesion data
		$user->execute( $data['userID'] );
		$_SESSION['user'] = $user->fetch();

		// Generate Email
		$fields = array(
			'Name' => 'name',
			'Title' => 'title',
			'Firm' => 'firm',
			'Phone' => 'phone',
			'Email' => 'email',
			'Bio' => 'bio',
		);
		function table_row_compare($title, $old, $new) {
			$str = "<tr";
			if ($old != $new) $str .= ' style="background-color:red"';
			return $str . "><td>$title</td><td>$old</td><td>$new</td></tr>";
		}
		$html = "<p>The following changes have been made to a user</p><table><tr><th>Attribute</th><th>Old Version</th><th>New version</th></tr>";
		foreach ($fields as $key => $value) $html .= table_row_compare( $key, $old_user[$value], $_SESSION['user'][$value] );
		return $mail->notify('Upsteram Academy Event Profile Update', $html . '</table>');
	}
	public function reset_send( $email ) {
		$sth = $this->db->prepare("SELECT userID, name, email FROM user WHERE email=? LIMIT 1;");
		if (
			!$sth->execute( $email ) ||
			false === ($user = $sth->fetch())
		) throw new GUIException('Invalid Email', 'This email has not been registered');

		// DB
		$hash = sha1( $user['email'] . config::encryptSTR . uniqid() );
		$this->db->prepare("UPDATE user SET resetHash=?, resetExpire=date('now','+3 Days') WHERE userID=?;")->execute($hash, $user['userID']);

		// Email
		$mail = new myMail();
		$mail->STMPDebug = 2;
		$html = str_replace('{{HASH}}', $hash, file_get_contents(__DIR__ . '/../tpl/reset-pass.email.html'));
		return $mail->sendMsg('Upstream Academy Conference: Password Reset', $html, $user['email'], $user['name']);
	}
	public function reset_valid( $hash ) {
		$STH = $this->db->prepare("SELECT * FROM user WHERE resetHash=? AND resetExpire > CURRENT_TIMESTAMP LIMIT 0,1;");
		if (!$STH->execute( $hash )) throw new Exception();
		return count($STH->fetchAll()) == 1;
	}
	public function reset_pass( $pass, $confirm, $hash ) {
		if (strlen($pass) < 3) throw new GUIException('Password is short', 'Please choose a password at least 3 characters long.');
		if ($pass != $confirm) throw new GUIException('Password missmatch', 'Try typing them again');
		if (!$this->reset_valid($hash)) throw new Exception();
		return $this->db->prepare("UPDATE user SET pass=?,resetHash=NULL,resetExpire=NULL WHERE resetHash=?;")->execute( $this->create_hash($pass), $hash ); // TODO: auto login
	}
	public function reset_pass_direct( $userID, $pass ) {
		return $this->db->prepare("UPDATE user SET pass=?,resetHash=NULL,resetExpire=NULL WHERE userID=?;")->execute( $this->create_hash($pass), $userID );
	}

	public function send_comment( $comment ) {
		$mail = new myMail();
		return $mail->notify('Event App Comment', $comment);
	}

	// https://crackstation.net/hashing-security.htm#phpsourcecode
	public function create_hash($password) {
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
if (isset($_REQUEST['gen'])) echo $user->create_hash($_REQUEST['gen']);
