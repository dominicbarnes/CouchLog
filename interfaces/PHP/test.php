<?php

require_once('log.class.php');

$log = new Log('applog');
$log->debug = true;
#$log->db->truncate();

# Create a single entry in the database
$log->entry(
	'My Application',
	'User Activity',
	'Notice',
	'User Login Recorded',
	Array(
		'username' => 'testuser',
		'source' => 'homepage'
	)
);

?>