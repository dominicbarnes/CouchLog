<?php

# Load Dependency: PHP Cushion (interface to CouchDB)
require_once('cushion.class.php');

class Log
{
	public $db;

	function __construct($database, $address = 'localhost', $protocol = 'http', $port = 5984)
	{
		$this->db = new Cushion($database, $address, $protocol, $port);
	}

	function entry($application, $section, $message, $level, $data = null)
	{
		return $this->db->create(Array(
			'timestamp' => microtime(true),
			'application' => $application,
			'section' => $section,
			'message' => $message,
			'level' => $level,
			'data' => $data
		));
	}
}

?>