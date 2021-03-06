<?php

class GUIException extends Exception {
	function __construct($title = 'Error', $msg = 'Unknown error occured', $type = 'danger') {
		parent::__construct("<div class=\"alert alert-{$type}\"><strong>{$title}</strong> {$msg}</div>");
	}
}

class App {
	private $db;
	public $status = array();

	function __construct() {
		$this->db = new myPDO();
	}

	// Pretty Error Handling
	public function get_error($location) {
		$out = '';
		if (array_key_exists($location, $this->status)) 
			foreach ($this->status[$location] as $value) 
				$out .= $value->getMessage();
		return $out;
	}
	public function set_gui_error($location, GUIException $value) {
		if (array_key_exists($location, $this->status)) {
			array_push($this->status[$location], $value);
		} else {
			$this->status[$location] = array($value);
		}
	}
	public function set_error($location, $title = 'Error', $msg = 'Unknown error occured', $type = 'danger') {
		$this->set_gui_error($location, new GUIException($title, $msg, $type));
	}

	public function get_my_confs($userID) {
		$sth = $this->db->prepare("SELECT * FROM attendee a LEFT JOIN conference c ON a.conferenceID=c.conferenceID WHERE userID=? AND visible='true' ORDER BY start_stamp, title;");
		$sth->execute( $userID );
		return $sth->fetchAll();
	}

	public function is_my_conf($slug, $userID) {
		return true;
		$sth = $this->db->prepare("SELECT * FROM attendee a LEFT JOIN conference c ON a.conferenceID=a.conferenceID WHERE userID=? AND slug=?;");
		$sth->execute($userID, $slug);
		return count($sth->fetchAll()) > 0;
	}

	public function get_conf($slug) {

		// General Conference
		$sth = $this->db->prepare("SELECT * FROM conference WHERE slug=? LIMIT 1;");
		$sth->execute($slug);
		$conf = $sth->fetch();
		if (!$conf) return false;

		// Attendee
		$sth = $this->db->prepare("SELECT * FROM attendee a LEFT JOIN user u ON a.userID = u.userID WHERE a.conferenceID=? ORDER BY name;");
		$sth->execute( $conf['conferenceID'] );
		$conf['attendees'] = $sth->fetchAll();

		// Location
		$sth = $this->db->prepare("SELECT * FROM location WHERE locationID=? LIMIT 1;");
		$sth->execute( $conf['locationID'] );
		$conf['location'] = $sth->fetch();

		// Speakers
		$sth = $this->db->prepare("SELECT DISTINCT u.* FROM session s JOIN speaker p ON p.sessionID = s.sessionID LEFT JOIN user u ON p.userID = u.userID WHERE conferenceID=? AND p.featured='true' ORDER BY u.name;");
		$sth->execute( $conf['conferenceID'] );
		$conf['speakers'] = $sth->fetchAll();

		// Agenda
		$sth1 = $this->db->prepare("SELECT * FROM session s WHERE conferenceID=? ORDER BY \"date\", start, s.title;");
		$sth2 = $this->db->prepare("SELECT u.* FROM speaker s LEFT JOIN user u ON s.userID=u.userID WHERE sessionID=? ORDER BY name;");
		$sth3 = $this->db->prepare("SELECT * FROM file WHERE sessionID=? ORDER BY name;");
		$sth1->execute( $conf['conferenceID'] );
		$conf['agenda'] = array();
		while (false !== ($row = $sth1->fetch())) {
			$sth2->execute( $row['sessionID'] );
			$row['speakers'] = $sth2->fetchAll();
			$sth3->execute( $row['sessionID'] );
			$row['files'] = $sth3->fetchAll();
			array_push($conf['agenda'], $row);
		}

		// Sponsors
		$sth1 = $this->db->prepare("SELECT * FROM sponsor s LEFT JOIN company c ON s.companyID=c.companyID WHERE s.conferenceID=? ORDER BY priority, name;");
		$sth2 = $this->db->prepare("SELECT * FROM rep r LEFT JOIN user u ON r.userID=u.userID WHERE r.sponsorID=? ORDER BY priority, name;");
		$sth1->execute( $conf['conferenceID'] );
		$conf['sponsors'] = array();
		while (false !== ($row = $sth1->fetch())) {
			$sth2->execute( $row['sponsorID'] );
			$row['reps'] = $sth2->fetchAll();
			array_push($conf['sponsors'], $row);
		}

		return $conf;
	}


	public function save_conf( $req ) {
		$map = function () use (&$req) {
			$arr = func_get_args();
			$cb = function ($value) use ($req) { return $req[$value]; };
			return array_map($cb, $arr);
		};

		// Location
		if ($req['locationID'] > 0) {
			$sth = $this->db->prepare("UPDATE location SET name=?,address=?,city=?,state=?,map_options=?,hotel_map=?,location_map=?,\"desc\"=? WHERE locationID=?;");
			$sth->execute($map('loc_name','loc_address','loc_city','loc_state','loc_opt','loc_hotel','loc_loc_map','loc_desc','locationID'));
		} else {
			$sth = $this->db->prepare("INSERT INTO location (name,address,city,state,map_options,hotel_map,location_map,\"desc\") VALUES (?,?,?,?,?,?,?,?);");
			$sth->execute($map('loc_name','loc_address','loc_city','loc_state','loc_opt','loc_hotel','loc_loc_map','loc_desc'));
			$req['locationID'] = $this->db->lastInsertId();
		}

		// Conference
		if ($req['conferenceID'] > 0) {
			$sth = $this->db->prepare("UPDATE conference SET title=?,theme=?,logo=?,\"desc\"=?,faq=?,html_landing=?,html_conference=?,html_location=?,html_speakers=?,html_agenda=?,html_attendees=?,html_sponsors=?,start_stamp=?,end_stamp=?,slug=?,locationID=? WHERE conferenceID=?;");
			$sth->execute($map('conf_title','conf_theme','conf_logo','conf_desc','conf_faq','conf_html_landing','conf_html_conference','html_location','conf_html_speakers','conf_html_agenda','conf_html_attendees','conf_html_sponsors','conf_start_stamp','conf_end_stamp','slug','locationID','conferenceID'));
		} else {
			// Un-tested
			$sth = $this->db->prepare("INSERT INTO conference (title,theme,logo,\"desc\",faq,html_landing,html_conference,html_location,html_speakers,html_agenda,html_attendees,html_sponsors,start_stamp,end_stamp,slug,locationID) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);");
			$sth->execute($map('conf_title','conf_theme','conf_logo','conf_desc','conf_faq','conf_html_landing','conf_html_conference','html_location','conf_html_speakers','conf_html_agenda','conf_html_attendees','conf_html_sponsors','conf_start_stamp','conf_end_stamp','slug','locationID'));
			$req['conferenceID'] = $this->db->lastInsertId();
		}
	}
}
