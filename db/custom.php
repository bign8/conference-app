<?php

ArrestDB::Serve('GET', '/(atten)/(#num)', function ($table, $id) {
	$result = ArrestDB::Query("SELECT * FROM attendee a LEFT JOIN user u ON a.userID = u.userID WHERE a.conferenceID=? ORDER BY name;", $id);
	if ($result === false) {
		$result = ArrestDB::$NOT_FOUND;
	} else if (empty($result) === true) {
		$result = ArrestDB::$NO_CONTENT;
	}
	return ArrestDB::Reply($result);
});
