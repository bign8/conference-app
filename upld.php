<?php

print_r($_REQUEST);

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
		// $this->db = new PDO('sqlite:..' . DIRECTORY_SEPARATOR . 'db.sqlite3');
		if ( ($this->handle = fopen($upload_name, 'r')) === FALSE ) die('cannot open stream');
	}

	public function process() {
		// $col_to_db_map = $this->processTitles();
		echo stream_get_contents( $this->handle );
		// // Setup queries
		// $uGetSTH = $this->db->prepare("SELECT accountno FROM user WHERE accountno=?;");
		// $uAddSTH = $this->db->prepare("INSERT INTO user (first,last,company,title,city,state,bio,gradyear,phone,email,\"user\",img,guide,accountno,pass) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);");
		// $uModSTH = $this->db->prepare("UPDATE \"user\" SET first=?,last=?,company=?,title=?,city=?,state=?,bio=?,gradyear=?,phone=?,email=?,\"user\"=?,img=?,guide=? WHERE accountno=?;");
		// $eGetSTH = $this->db->prepare("SELECT eventID FROM event WHERE name=? AND programYearID=?;");
		// $eAddSTH = $this->db->prepare("INSERT INTO event (name, programYearID) VALUES (?,?);");

		// $aGetSTH = $this->db->prepare("SELECT attendeeID FROM attendee WHERE userID=? AND yearID=?;");
		// $aAddSTH = $this->db->prepare("INSERT INTO attendee (userID, eventID, yearID) vALUES (?,?,?);");
		// $aModSTH = $this->db->prepare("UPDATE attendee SET eventID=? WHERE attendeeID=?;");


		// // process rows
		// $notified = array();
		// while (($data = fgetcsv($this->handle, 1000, ",")) !== FALSE) {
		// 	$data = array_map('trim', $data);

		// 	// Insert user
		// 	$user_data = array(
		// 		$data[ $this->titles['first'] ],
		// 		$data[ $this->titles['last'] ],
		// 		$data[ $this->titles['company'] ],
		// 		$data[ $this->titles['title'] ],
		// 		$data[ $this->titles['city'] ],
		// 		$data[ $this->titles['state'] ],
		// 		@iconv("SHIFT_JIS", "UTF-8", $data[ $this->titles['bio'] ]), // microsoft :( http://i-tools.org/charset
		// 		$data[ $this->titles['program'] ], // no grad-year yet
		// 		$data[ $this->titles['phone1'] ],
		// 		$data[ $this->titles['contsupref'] ], // email
		// 		$data[ $this->titles['username'] ],
		// 		$data[ $this->titles['photo link'] ],
		// 		$data[ $this->titles['guide'] ],
		// 		$data[ $this->titles['accountno'] ],
		// 	);
		// 	if (
		// 		!$uGetSTH->execute(array( $data[ $this->titles['accountno'] ] )) ||
		// 		$uGetSTH->fetchColumn() === FALSE
		// 	) {
		// 		array_push($user_data, create_hash($data[ $this->titles['password'] ]));
		// 		$uAddSTH->execute( $user_data );
		// 	} else {
		// 		$uModSTH->execute( $user_data );
		// 	}

		// 	// Add events and attendees for each user
		// 	foreach ($col_to_db_map as $key => $value) {
		// 		// echo $data[ $key ] . ' ' . print_r($value, true);

		// 		// Should we insert event?
		// 		$eventID = false;
		// 		switch ( strtolower($data[$key]) ) { // just check if key has numbers (ie: year)
		// 			case 'deferred':
		// 			case 'none':
		// 			case '': 
		// 				if (!in_array($data[$key], $notified)) {
		// 					array_push($notified, $data[$key]);
		// 					echo 'Skipped insert event "' . $data[$key] . '"<br/>' . "\r\n";
		// 				}
		// 				break;

		// 			case 'undecided': $eventID = null; break;

		// 			default:
		// 				// Insert event if not already there
		// 				$event_data = array( $data[$key], $value['pyID'] );
		// 				$eGetSTH->execute( $event_data );
		// 				$eventID = $eGetSTH->fetchColumn();
		// 				if ( $eventID === FALSE ) {
		// 					$eAddSTH->execute( $event_data );
		// 					$eventID = $this->db->lastInsertId();
		// 				}
		// 		}

		// 		// If we should do some sort of attendee add
		// 		if ($eventID !== false) {
		// 			$aGetSTH->execute(array( $data[ $this->titles['accountno'] ], $value['yrID'] ));
		// 			$attendeeID = $aGetSTH->fetchColumn();
		// 			if ($attendeeID) {
		// 				$aModSTH->execute(array( $eventID, $attendeeID )); // update attendee if it exists
		// 			} else {
		// 				$aAddSTH->execute(array( $data[ $this->titles['accountno'] ], $eventID, $value['yrID'] )); // insert attendee
		// 			}
		// 		}
		// 	}
		// }

		// Final cleanup
		fclose($this->handle);
	}

	// Deal with titles and insert programYear and year entries as necessary
	private function processTitles() {
		// $titles = fgetcsv($this->handle);
		// $this->titles = array_flip(array_map('strtolower', $titles));
		// $this->validateTitles();

		// // Process program years
		// $title_pattern = "/^event ([0-9]{2}-[0-9]{2})$/i";
		// $event_titles = preg_grep($title_pattern, $titles); // Parse for matching titles
		// $event_titles = preg_replace($title_pattern, "$1", $event_titles); // cleanup

		// // Insert and store ID's for Program Year
		// $programYearIDs = array();
		// $getSTH = $this->db->prepare("SELECT programYearID FROM programYear WHERE programYear=?;");
		// $addSTH = $this->db->prepare("INSERT INTO programYear (programYear) VALUES (?);");

		// foreach ($event_titles as $key => $value) {
		// 	$temp_arr = array( $value );

		// 	$getSTH->execute( $temp_arr );
		// 	$programYearID = $getSTH->fetchColumn();
		// 	if ($programYearID === FALSE) {
		// 		$addSTH->execute( $temp_arr );
		// 		$programYearID = $this->db->lastInsertId();
		// 	}
		// 	$programYearIDs[$value] = array(
		// 		'dbID' => $programYearID,
		// 		'colID' => $key
		// 	);
		// }

		// // Figure out Which Year each program year is / insert and store
		// sort($event_titles); // sorting values
		// $yearIDs = array();
		// $getSTH = $this->db->prepare("SELECT yearID FROM year WHERE programYearID=? AND year=?;");
		// $addSTH = $this->db->prepare("INSERT INTO year (programYearID, year) VALUES (?,?);");
		// foreach ($event_titles as $key => $value) {
		// 	$temp_arr = array( $programYearIDs[$value]['dbID'], $key+1 );

		// 	$getSTH->execute( $temp_arr );
		// 	$yearID = $getSTH->fetchColumn();
		// 	if ($yearID === FALSE) {
		// 		$addSTH->execute( $temp_arr );
		// 		$yearID = $this->db->lastInsertId();
		// 	}
		// 	$yearIDs[ $programYearIDs[$value]['colID'] ] = array(
		// 		'pyID' => $programYearIDs[$value]['dbID'],
		// 		'yrID' => $yearID,
		// 		'year' => $value
		// 	);
		// }
		// return $yearIDs;
	}

	// Ensure we have the required titles
	private function validateTitles() {
		// echo '<p>Column titles as the script sees them.</p><pre>';
		// $titles = array_flip($this->titles);
		// print_r($titles);
		// echo '</pre>';

		// $event_titles = preg_grep("/^event ([0-9]{2}-[0-9]{2})$/i", $titles); // Parse for matching titles
		
		// if ( sizeof($event_titles) < 1 ) die("No event titles matching the pattern /^event ([0-9]{2}-[0-9]{2})$/i.");
		// if ( !in_array('accountno', $titles) ) die("No accountno title.");
		// if ( !in_array('first', $titles) ) die("No first title.");
		// if ( !in_array('last', $titles) ) die("No last title.");
		// if ( !in_array('company', $titles) ) die("No company title.");
		// if ( !in_array('title', $titles) ) die("No title title.");
		// if ( !in_array('title', $titles) ) die("No title title.");
		// if ( !in_array('city', $titles) ) die("No city title.");
		// if ( !in_array('state', $titles) ) die("No state title.");
		// if ( !in_array('bio', $titles) ) die("No bio title.");
		// if ( !in_array('program', $titles) ) die("No program title.");
		// if ( !in_array('guide', $titles) ) die("No guide title.");
		// if ( !in_array('phone1', $titles) ) die("No phone1 title.");
		// if ( !in_array('contsupref', $titles) ) die("No contsupref title.");
		// if ( !in_array('username', $titles) ) die("No username title.");
		// if ( !in_array('password', $titles) ) die("No password title.");
	}
}

//* Process CSV Data
$processor = new PROCESSOR( $upload_name );
$processor->process();
// */
