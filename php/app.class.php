<?php

class App {
	private $db;

	function __construct() {
		$this->db = new myPDO();
	}

	public function get_conf($slug) {

		// General Conference
		$sth = $this->db->prepare("SELECT * FROM conference WHERE slug=? LIMIT 1;");
		$sth->execute($slug);
		$conf = $sth->fetch();
		if (!$conf) return false;

		// Attendee
		$sth = $this->db->prepare("SELECT * FROM attendee a LEFT JOIN user u ON a.userID = u.userID WHERE a.conferenceID=?;");
		$sth->execute( $conf['conferenceID'] );
		$conf['attendees'] = $sth->fetchAll();

		// Location
		$sth = $this->db->prepare("SELECT * FROM location WHERE locationID=? LIMIT 1;");
		$sth->execute( $conf['locationID'] );
		$conf['location'] = $sth->fetch();

		// Agenda
		$sth = $this->db->prepare("SELECT * FROM session s LEFT JOIN speaker p ON p.sessionID=s.sessionID LEFT JOIN user u ON p.userID=u.userID WHERE conferenceID=? ORDER BY \"date\", start, s.title;");
		$sth->execute( $conf['conferenceID'] );
		$conf['agenda'] = $sth->fetchAll();

		// Speakers
		$sth = $this->db->prepare("SELECT DISTINCT u.* FROM session s LEFT JOIN speaker p ON p.sessionID=s.sessionID LEFT JOIN user u ON p.userID=u.userID WHERE conferenceID=? ORDER BY u.name;");
		$sth->execute( $conf['conferenceID'] );
		$conf['speakers'] = $sth->fetchAll();

		// Sponsors
		$sth = $this->db->prepare("SELECT * FROM sponsor s LEFT JOIN company c ON s.companyID=c.companyID LEFT JOIN rep r ON s.sponsorID=r.sponsorID LEFT JOIN user u ON r.userID=u.userID WHERE s.conferenceID=? ORDER BY s.priority, r.priority;");
		$sth->execute( $conf['conferenceID'] );
		$conf['sponsors'] = $sth->fetchAll();

		return $conf;
	}
}
