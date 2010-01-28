<?php

require_once('log.class.php');

$log = new Log('applog');
$log->db->debug = true;

# Create a single entry in the database
$log->entry(
	'My Application',
	'User Activity',
	'User Login Recorded',
	'Notice',
	Array(
		'username' => 'testuser',
		'source' => 'homepage'
	)
);

?>