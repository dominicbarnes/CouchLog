<?php

# Load Dependency: cURL PHP Extension
if (!extension_loaded('curl'))
	exit('Could not find PHP cURL library.');

class Cushion
{
	public $debug = false;
	public $info = Array();

	private $database;
	private $curl;
	private $baseurl;
	private $options;

	function __construct($database, $address = 'localhost', $protocol = 'http', $port = 5984)
	{
		$this->baseurl = "$protocol://$address:$port/";
		$this->testconnection();

		$this->database = $database;
		$this->testdatabase();
	}

	function __destruct()
	{
	}

	private function execute($checkdb = true)
	{
		if ($checkdb && empty($this->database))
		{
			trigger_error('CouchDB Database Not Selected', E_USER_ERROR);
			return false;
		}

		$this->options[CURLOPT_RETURNTRANSFER] = true;

		if ($this->debug)
		{
			$this->options[CURLINFO_HEADER_OUT] = true;
			$this->options[CURLOPT_HEADER] = true;
		}

		curl_setopt_array($this->curl, $this->options);

		$output = curl_exec($this->curl);

		if ($this->debug)
		{
			$output = explode("\r\n\r\n", $output);

			$this->info['request']['header'] = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
			$this->info['response']['header'] = $output[0];

			$output = $output[1];
		}

		$this->info['request']['url'] = curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);

		if ($this->debug)
		{
			echo '<pre>';
			print_r($this->info);
			echo '</pre>';
		}

		return $output;
	}

	private function testconnection()
	{
		$this->curl = curl_init($this->baseurl);

		$output = $this->execute(false);

		$result = json_decode($output, true);

		if (!isset($result['couchdb']) && !isset($result['version']))
		{
			trigger_error('Could not establish connection to CouchDB.', E_USER_ERROR);
		}
	}

	private function testdatabase()
	{
		$this->curl = curl_init($this->baseurl . $this->database);

		$output = $this->execute();

		$result = json_decode($output, true);

		if ($result['error'])
		{
			trigger_error('Error testing database connection. Reason: ' . $result['reason'], E_USER_ERROR);
		}
	}

	function truncate()
	{
		$url = $this->baseurl . $this->database . '/_all_docs';
		$this->curl = curl_init($url);
		$doclist = json_decode($this->execute());

		foreach ($doclist->rows AS $doc)
		{
			# Ignore Design Documents
			if (strpos($doc->id, '_design/') === false)
				$this->delete($doc->id, $doc->value->rev);
		}
	}

	function create($document, $id = null)
	{
		$url = $this->baseurl . $this->database . '/';
		if (isset($id))	$url .= $id;

		$this->curl = curl_init($url);

		$this->options = Array(
			CURLOPT_CUSTOMREQUEST => isset($id) ? 'PUT' : 'POST',
			CURLOPT_HTTPHEADER => Array('Content-Type: application/json'),
			CURLOPT_POSTFIELDS => json_encode($document)
		);

		$output = $this->execute();

		return json_decode($output, true);
	}

	function bulk_create($documents)
	{
		$url = $this->baseurl . $this->database . '/_bulk_docs';

		$data = Array('docs' => $documents);
		$this->curl = curl_init($url);

		$this->options = Array(
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => Array('Content-Type: application/json'),
			CURLOPT_POSTFIELDS => json_encode($data)
		);

		$output = $this->execute();

		return json_decode($output, true);
	}

	function update($document)
	{
		$url = $this->baseurl . $this->database . '/' . $document['_id'];

		$this->curl = curl_init($url);

		$this->options = Array(
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_HTTPHEADER => Array('Content-Type: application/json'),
			CURLOPT_POSTFIELDS => json_encode($document)
		);

		$output = $this->execute();

		$output = curl_exec($this->curl);
	}

	function read($id = null, $data = null)
	{
		$url = $this->baseurl . $this->database . '/';
		$url .= (isset($id)) ? $id : '_all_docs';

		$this->curl = curl_init($url);

		if (isset($data))
		{
			$this->options = Array(
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_HTTPHEADER => Array('Content-Type: application/json'),
				CURLOPT_POSTFIELDS => json_encode($data)
			);
		}

		$output = $this->execute();

		return json_decode($output, true);
	}

	function view($design_doc, $view_name, $params = null)
	{
		foreach ($params AS $key => $value)
		{
			if (is_bool($value))
			{
				$params[$key] = ($value) ? 'true' : 'false';
			}
		}

		$url = $this->baseurl . $this->database . '/_design/' . $design_doc . '/_view/' . $view_name;
		if (isset($params))	$url .= '?' . http_build_query($params);

		$this->curl = curl_init($url);

		$output = $this->execute();

		return json_decode($output, true);
	}

	function delete($id, $rev)
	{
		$url = $this->baseurl . $this->database . "/$id?rev=$rev";

		$this->curl = curl_init($url);

		$this->options = Array(CURLOPT_CUSTOMREQUEST => 'DELETE');

		$output = $this->execute();

		return json_decode($output, true);
	}
}

?>