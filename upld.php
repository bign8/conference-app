<?php

require_once('php' . DIRECTORY_SEPARATOR . 'index.php');
set_exception_handler(function () { header('Location: /#login'); });

// Admin security
if (!isset($_SESSION['user']) || $_SESSION['user']['admin'] != 'true') throw new Exception('un-authed'); // TODO: fix!

// Uploader class (uses content after __hault_compiler)
class UPLOADER {
	const field_name = 'up_file';

	public function execute() {
		$ret = null;

		// Verify and upload file
		try {
			if ( 
				!isset($_FILES[ $this::field_name ]['error']) || 
				is_array($_FILES[ $this::field_name ]['error'])
			) throw new RuntimeException('Invalid parameters');

			// Check $_FILES[ $fieldName ]['error'] value.
			switch ($_FILES[ $this::field_name ]['error']) {
				case UPLOAD_ERR_OK: break;
				case UPLOAD_ERR_NO_FILE: throw new RuntimeException('No file sent');
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE: throw new RuntimeException('Exceeded filesize limit');
				default: throw new RuntimeException('Unknown errors');
			}

			if ($_FILES[ $this::field_name ]['size'] > 1000000)
				throw new RuntimeException('Exceeded filesize limit');

			$ret = $_FILES[ $this::field_name ]['tmp_name'];
		} catch (RuntimeException $e) {
			print_r($_FILES);
			die( $e->getMessage() );
		}
		return $ret;
	}
}

//* Upload CSV Data
$uploader = new UPLOADER();
$upload_name = $uploader->execute();
// */

// Processor class (requires $upload_name's file)
class PROCESSOR {
	function __construct($upload_name = null) {
		$this->upload_name = $upload_name;
		$this->db = new PDO('sqlite:php' . DIRECTORY_SEPARATOR . 'db.db');
		if ( ($this->handle = fopen($upload_name, 'r')) === FALSE ) die('cannot open stream');
	}

	public function process() {
		$col_to_db_map = $this->processTitles();
		$usr = new User();

		// Setup queries
		$uGetSTH = $this->db->prepare("SELECT userID FROM user WHERE accountno=?;");
		$uAddSTH = $this->db->prepare("INSERT INTO user (name,firm,title,city,state,bio,phone,email,photo,member,accountno,pass) VALUES (?,?,?,?,?,?,?,?,?,?,?,?);");
		$uModSTH = $this->db->prepare("UPDATE user SET name=?,firm=?,title=?,city=?,state=?,bio=?,phone=?,email=?,photo=?,member=? WHERE accountno=?;");

		$aGetSTH = $this->db->prepare("SELECT attendeeID FROM attendee WHERE userID=? AND conferenceID=?;");
		$aAddSTH = $this->db->prepare("INSERT INTO attendee (userID, conferenceID) vALUES (?,?);");


		// process rows
		while (($data = fgetcsv($this->handle, 1000, ",")) !== FALSE) {
			$data = array_map('trim', $data);

			// Insert user
			if ($data == array('') || $data[ $this->titles['accountno'] ] == '') continue;
			echo '<pre>';
			print_r($data);
			echo '</pre><br/><br/>';

			$user_data = array(
				$data[ $this->titles['name'] ],
				$data[ $this->titles['firm'] ],
				$data[ $this->titles['title'] ],
				$data[ $this->titles['city'] ],
				$data[ $this->titles['state'] ],
				@iconv("SHIFT_JIS", "UTF-8", $data[ $this->titles['bio'] ]), // microsoft :( http://i-tools.org/charset
				$data[ $this->titles['phone'] ],
				$data[ $this->titles['email'] ],
				$data[ $this->titles['photo link'] ],
				$data[ $this->titles['memberships'] ],
				$data[ $this->titles['accountno'] ],
			);
			if (
				!$uGetSTH->execute(array( $data[ $this->titles['accountno'] ] )) ||
				($userID = $uGetSTH->fetchColumn()) === FALSE
			) {
				array_push($user_data, $usr->create_hash($data[ $this->titles['password'] ]));
				$uAddSTH->execute( $user_data );
				$userID = $this->db->lastInsertId();
			} else {
				$uModSTH->execute( $user_data );
			}

			// If we should do some sort of attendee add
			$conferenceID = $_REQUEST['up_evt'];
			if (
				!$aGetSTH->execute(array( $userID, $conferenceID )) ||
				$aGetSTH->fetchColumn() === FALSE
			) {
				$aAddSTH->execute(array( $userID, $conferenceID )); // insert attendee
			}
		}

		// Final cleanup
		fclose($this->handle);
	}

	// Deal with titles
	private function processTitles() {
		$titles = fgetcsv($this->handle);
		$this->titles = array_flip(array_map('strtolower', $titles));
		$this->validateTitles();
	}

	// Ensure we have the required titles
	private function validateTitles() {
		echo '<p>Column titles as the script sees them.</p><pre>';
		$titles = array_flip($this->titles);
		print_r($titles);
		echo '</pre>';

		if ( !in_array('accountno', $titles) ) die("No accountno title.");
		if ( !in_array('name', $titles) ) die("No name title.");
		if ( !in_array('firm', $titles) ) die("No firm title.");
		if ( !in_array('title', $titles) ) die("No title title.");
		if ( !in_array('city', $titles) ) die("No city title.");
		if ( !in_array('state', $titles) ) die("No state title.");
		if ( !in_array('bio', $titles) ) die("No bio title.");
		if ( !in_array('phone', $titles) ) die("No phone title.");
		if ( !in_array('email', $titles) ) die("No email title.");
		if ( !in_array('password', $titles) ) die("No password title.");
		if ( !in_array('photo link', $titles) ) die("No guide title.");
		if ( !in_array('memberships', $titles) ) die("No username title.");
	}
}

//* Process CSV Data
$processor = new PROCESSOR( $upload_name );
$processor->process();
// */
