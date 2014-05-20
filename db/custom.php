<?php

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
