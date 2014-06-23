<?php

// Users can be authenticated other ways, but this uses sesisons (will need to modify ArrestDB_Security::whitelist(...) if want to change)
if (session_id() == '') session_start();

$dsn = 'sqlite:' . implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'php', 'db.db'));

class ArrestDB_Custom {
	public static function GET_DELETE() {
		ArrestDB::Serve('GET', '/(atten)/(#num)', function ($table, $id) {
			$querys = array( // take conferenceID as arg; return userID, speakerID, repID
				'SELECT userID, NULL as speakerID, NULL as repID FROM attendee WHERE conferenceID=?',
				'SELECT userID, speakerID, NULL as repID FROM session s JOIN speaker p ON p.sessionID=s.sessionID WHERE s.conferenceID=?',
				'SELECT userID, NULL as speakerID, repID FROM sponsor s JOIN rep r ON r.sponsorID=s.sponsorID  WHERE s.conferenceID=?'
			);
			$union = implode(' UNION ', $querys);
			$result = ArrestDB::Query(
				"SELECT u.*, max(speakerID) AS speakerID, max(repID) AS repID FROM ($union) x LEFT JOIN user u ON x.userID=u.userID GROUP BY userID;",
				array_fill(0, count($querys), $id)
			);

			// $result = ArrestDB::Query("SELECT * FROM attendee a LEFT JOIN user u ON a.userID = u.userID WHERE a.conferenceID=? ORDER BY name;", $id);
			if ($result === false) {
				$result = ArrestDB::$NOT_FOUND;
			} else if (empty($result) === true) {
				$result = ArrestDB::$NO_CONTENT;
			}
			return ArrestDB::Reply($result);
		});
	}
	public static function PUT_POST() {
		ArrestDB::Serve('POST', '/(reset)/(#num)', function($table, $userID) {
			include_once('user.class.php');
			$user = new User();
			$result = $user->reset_pass_direct( $userID, $_POST['pass'] );

			if ($result === false) {
				$response = ArrestDB::$CONFLICT;
			} else {
				$response = ArrestDB::$OK;
			}
			return ArrestDB::Reply( ArrestDB::$OK );
		});
	}
}

// Proposed user table column (store authenticated in session): access, type int
// My Convention (can be changed as desired)
//     0: Global
//     1: User
//     2: Admin
class ArrestDB_Security {

	// No value in whitelist assumes disabled call
	private static $WHITELIST = array(
		'note' => array(
			'1' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('noteID', 'userID', 'dest_userID', 'dest_sessionID', 'note', 'stamp'),
			),
		),
		'atten' => array(
			'1' => array(
				'actions' => array('GET'),
				'fields'  => array('*'),
			),
		),
		'user' => array(
			'1' => array(
				'actions' => array('GET'),
				'fields'  => array('userID', 'name', 'title', 'firm', 'phone', 'photo', 'bio', 'email', 'seen', 'city', 'state', 'member'),
			),
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('userID', 'name', 'title', 'firm', 'phone', 'photo', 'bio', 'email', 'seen', 'admin', 'city', 'state', 'member'),
			),
		),
		'file' => array(
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('fileID', 'name', 'file', 'sessionID'),
			),
		),
		'reset' => array(
			'2' => array(
				'actions' => array('POST'),
				'fields'  => array('pass'),
			),
		),
		'session' => array(
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('sessionID', 'title', 'desc', 'date', 'start', 'end', 'logo', 'conferenceID'),
			),
		),
		'speaker' => array(
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('speakerID', 'userID', 'sessionID', 'featured'),
			),
		),
		'attendee' => array(
			'2' => array(
				'actions' => array('GET', 'POST', 'DELETE'),
				'fields'  => array('attendeeID', 'userID', 'conferenceID'),
			),
		),
		'company' => array(
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST'),
				'fields'  => array('companyID', 'name', 'bio', 'logo', 'site'),
			),
		),
		'sponsor' => array(
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('sponsorID', 'conferenceID', 'companyID', 'priority', 'advert'),
			),
		),
		'rep' => array(
			'2' => array(
				'actions' => array('GET', 'POST', 'DELETE'),
				'fields'  => array('repID', 'sponsorID', 'userID'),
			),
		),
		'conference' => array(
			'2' => array(
				'actions' => array('GET', 'PUT', 'POST', 'DELETE'),
				'fields'  => array('conferenceID', 'title', 'theme', 'slug', 'display_pictures', 'start_stamp'),
			),
		),
	);

	public static function whitelist($table, $area) {
		try {
			$access = isset($_SESSION['user']) ? 1 : 0 ;
			$access = (isset($_SESSION['user']['admin']) && $_SESSION['user']['admin'] == 'true') ? 2 : 1;
		} catch (Exception $e) {
			$access = 0;
		}

		$result = array();
		if (array_key_exists($table, self::$WHITELIST)) {
			$access = isset($access) ? intval($access) : 0 ;

			// decrementing until we find 0 or some form of security
			while ( !array_key_exists($access, self::$WHITELIST[ $table ]) && $access > 0 ) $access--;

			// Make sure we didn't hit rock bottom
			if ( array_key_exists($access, self::$WHITELIST[ $table ]) ) $result = self::$WHITELIST[ $table ][ $access ][ $area ];
		}
		return $result;
	}
}
