<?php
/**
 * PHP Cushion
 *
 * An interface for a CouchDB database
 * @author Dominic Barnes <mako281@gmail.com>
 * @version 0.1
 * @package cushion 
 */
class Cushion
{
	/**
	 * @var string The protocol being used ('http' or 'https')
	 * @access public
	 */
	public $protocol;

	/**
	 * @var string The hostname for the database ('localhost')
	 * @access public
	 */
	public $host;

	/**
	 * @var integer The port number for the database (normally 5984)
	 * @access public
	 */
	public $port;

	/**
	 * @var string The name of the database being used (to use another database, create another instance of Cushion)
	 * @access public
	 */
	public $database;

	/**
	 * @var boolean Determines whether or not to output useful debugging information
	 * @access public
	 */
	public $debug;

	/**
	 * @var array Stores information about the database that server that is being used
	 * @access public
	 */
	public $info;

	/**
	 * @var string Stores the base URI for the CouchDB database (http://localhost:5984/)
	 * @access private
	 */
	private $uri;


	/**
	 * Constructor Method :: Establishes connection to server (uses defaults) and database (only if $database is defined)
	 * 
	 * @access public
	 * @param string $database The name of the CouchDB database (default: null)
	 * @param string $host The hostname of the CouchDB server (default: 'localhost')
	 * @param string $protocol The protocol being used to connect to connect (default: 'http')
	 * @param integer $port The port number being used (default: 5984)
	 * @return void
	 */
	function __construct($database = null, $host = 'localhost', $protocol = 'http', $port = 5984)
	{
		$this->protocol = $protocol;
		$this->host = $host;
		$this->port = $port;

		$uri = $protocol . '://' . $host . ':' . $port . '/';

		$couch = new Couch();
		$this->info['couch'] = $couch->info($uri);

		$this->uri = $uri;

		if (isset($database))
			$this->info['database'] = $this->db_select($database);
	}

	/**
	 * Selects a CouchDB database to be used
	 * 
	 * @access public
	 * @param string $name The name of the database
	 * @return array Information that will be stored in $this->info['database']
	 */
	public function db_select($name)
	{
		$uri = $this->protocol . '://' . $this->host . ':' . $this->port . '/' . $name . '/';

		$db = new Database();
		$db_info = $db->info($uri);
		
		$this->database = $name;
		$this->uri = $uri;

		return $db_info;
	}

	/**
	 * Creates a new CouchDB Document in the selected database
	 * 
	 * @access public
	 * @param array $data Multi-dimensional array that will be JSON-encoded into a CouchDB Document
	 * @param string $id The _id that will be set on this new document (default: null)
	 * @return Document The CouchDB Document that has been created
	 */
	public function doc_create($data, $id = null)
	{
		if (!isset($this->database))
			throw new Exception('No database selected');
		
		$uri = $this->uri;
		
		$doc = new Document;
		if (isset($this->debug))	$doc->debug = $this->debug;
		$doc->create($uri, $data, $id);

		return $doc;
	}

	/**
	 * Reads an existing CouchDB Document into a Document object
	 * 
	 * @access public
	 * @param string $id The _id for the document being read. If none is supplied, it will retrieve all the documents on this database (default: null)
	 * @param string $rev The _rev for the document being read. Not needed if reading all documents (default: null)
	 * @return Document The CouchDB Document that was requested
	 */
	public function doc_read($id = null, $rev = null)
	{
		if (!isset($this->database))
			throw new Exception('No database selected');
			
		$uri = $this->uri;
		$uri .= (isset($id)) ? $id : '_all_docs';
		if (isset($rev))	$uri .= '?rev=' . $rev;

		$doc = new Document;
		if (isset($this->debug))	$doc->debug = $this->debug;
		$doc->read($uri);

		return $doc;
	}

	/**
	 * Retrieves the results of a particular view
	 * 
	 * @access public
	 * @param string $design The name of the design document that the view resides on, excluding '_design/'
	 * @param string $name The name of the view being queried
	 * @param array $params The additional parameters to be passed to the view
	 * @return array Multi-dimensional array containing response from query
	 */
	public function view_read($design, $name, $params = null)
	{
		if (!isset($this->database))
			throw new Exception('No database selected');
			
		$uri = $this->uri;
		$uri .= '_design/' . $design . '/_view/' . $name;
		if (isset($params))	$uri .= http_build_query($params);

		$view = new View();
		if (isset($this->debug))	$view->debug = $this->debug;
		return $view->read($uri);
	}
}

/**
 * Performs the actual work of making the HTTP request to the server, parsing the response and processing errors
 * 
 * @package cushion
 */
class Client
{
	/**
	 * @var boolean Determines whether or not to output debugging information (default: false)
	 * @access public
	 */
	public $debug = false;

	/**
	 * Executes an HTTP request to the specified URI, throws a CouchException if an error is detected in the response.
	 * 
	 * @access protected
	 * @param string $uri The URI for the request
	 * @param array $data The JSON data to be included in the request (default: null)
	 * @param const $method The PECL_HTTP constant defining the HTTP Method (ie. POST, GET, etc.) to be used (default: HTTP_METH_GET)
	 * @param array $options Array of additional options (including additional headers) to be sent with the HTTP request
	 * @return array The json_decoded response received
	 */
	protected function execute($uri, $data = null, $method = HTTP_METH_GET, $options = null)
	{
		$info = Array();

		$defaults = Array(
			'headers' => Array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			)
		);
		$options = (isset($options)) ? array_merge_recursive($defaults, $options) : $defaults;

		$output = trim(http_parse_message(http_request($method, $uri, $data, $options, $info))->body);

		if ($this->debug)
		{
			echo 'Output: ' . $output . "\n\n";
			echo 'HTTP Info: ';
			print_r($info);
		}

		$output = json_decode($output, true);

		if (isset($output['error']))
			throw new CouchException($output['error'], $output['reason'], $info['response_code']);

		return $output;
	}
}


/**
 * Represents a CouchDB Document
 * 
 * @package cushion
 */
class Document extends Client
{
	/**
	 * @var array Array that reflects the Documents structure
	 * @access public
	 */
	public $doc;

	/**
	 * @var uri The URI for the Document
	 * @access private
	 */
	private $uri;

	/**
	 * @var string The _id for the Document
	 * @access private
	 */
	private $id;

	/**
	 * @var string The _rev for the Document
	 * @access private
	 */
	private $rev;

	/**
	 * Creates a new CouchDB document
	 * 
	 * @access public
	 * @param string $baseuri The URI identifying the server and database
	 * @param array $doc The Document data (multi-dimensional array)
	 * @param string $id The _id for this new document (default: null)
	 * @return array The response from the CouchDB server
	 */
	public function create($baseuri, $doc, $id = null)
	{
		$this->uri = $baseuri;
		if (isset($id))	$this->uri .= $id;

		$output = $this->execute($this->uri, json_encode($doc), (isset($id)) ? HTTP_METH_PUT : HTTP_METH_POST);

		$this->id = $output['id'];
		$this->rev = $output['rev'];
		$this->doc = $doc;

		$this->uri .= $output['id'];

		return $output;
	}

	/**
	 * Retrieves an existing document based on an _id and _rev
	 * 
	 * @access public
	 * @param string $uri The full URI for the document
	 * @return array The response from the CouchDB server
	 */
	public function read($uri)
	{
		$this->uri = $uri;
		
		$output = $this->execute($uri);

		$this->id = $output['_id'];
		$this->rev = $output['_rev'];
		$this->doc = $output;

		return $output;
	}

	/**
	 * Takes the data stored in $this->doc and uses it to update the existing document
	 * 
	 * @access public
	 * @return array The response from the CouchDB server
	 */
	public function update()
	{
		$output = $this->execute($this->uri, json_encode($this->doc), HTTP_METH_POST);

		$this->rev = $output['rev'];

		return $output;
	}

	/**
	 * Deletes the document from the server
	 * 
	 * @access public
	 * @return array The response from the CouchDB server
	 */
	public function delete()
	{
		$uri = $this->uri . '?rev=' . $this->rev;

		unset($this->uri);
		unset($this->id);
		unset($this->rev);
		unset($this->doc);
		
		return $this->execute($uri, null, HTTP_METH_DELETE);
	}

	/**
	 * Creates a copy of the existing document to another specified ID
	 * 
	 * @access public
	 * @param string $to_id The _id for the new document you will be creating
	 * @return array The response from the CouchDB server
	 */
	public function copy($to_id)
	{
		return $this->execute($this->uri, null, HTTP_METH_COPY, Array(
			'headers' => Array('Destination' => $to_id)
		));
	}
}

/**
 * Interface to check the connection to the CouchDB Server
 * 
 * @package cushion
 */
class Couch extends Client
{
	/**
	 * @var string The full URI for the CouchDB server being requested
	 * @access private
	 */
	private $uri;

	/**
	 * Retrieves information about a CouchDB server (also to test for existence)
	 * 
	 * @access public
	 * @param type $uri The full URI for the server
	 * @return array The response from the CouchDB server
	 */
	public function info($uri)
	{
		$this->uri = $uri;

		return $this->execute($uri);
	}
}

/**
 * Interface to check the connection to the CouchDB Database
 * 
 * @package cushion
 */
class Database extends Client
{
	/**
	 * @var string The full URI for the database being requested
	 * @access private
	 */
	private $uri;

	/**
	 * Retrieves information about a database (also to test for existence)
	 * 
	 * @access public
	 * @param type $uri The full URI for the database
	 * @return array The response from the CouchDB server
	 */
	public function info($uri)
	{
		$this->uri = $uri;

		return $this->execute($uri);
	}
}

/**
 * Interface to retrieve the results of a view query
 * 
 * @package cushion
 */
class View extends Client
{
	/**
	 * @var string The full URI for the view being queried
	 * @access private
	 */
	private $uri;

	/**
	 * Retrieves the results of a query to a view
	 * 
	 * @access public
	 * @param type $uri The full URI for the view
	 * @param array $params The extra parameters being used for the query
	 * @return array The response from the CouchDB server
	 */
	public function read($uri, $params = null)
	{
		$this->uri = $uri;
		if (isset($params))	$this->uri .= http_build_query($params);

		return $this->execute($uri);
	}
}

/**
 * Custom Exception for CouchDB errors
 * 
 * @package cushion
 */
class CouchException extends Exception
{
	/**
	 * @var string Additional information about this Exception. The type of error specified by the CouchDB response {"error": "[type]", "reason": "[message]"}
	 * @access private
	 */
	private $type;

	/**
	 * Constructor Method :: Takes in the information given, assigns the internal properties and gets the Status Code message for the HTTP response
	 * Typical Error Respons: {"error": "[type]", "reason": "[message]"}
	 * 
	 * @param string $type The type of error according to the CouchDB response
	 * @param string $message The reason for the error according to the CouchDB response
	 * @param integer $code The HTTP Response Code
	 */
	function __construct($type, $message, $code)
	{
		// make sure everything is assigned properly
		parent::__construct($message, $code);
		
		$this->type = $type;
		$this->message = $message;
		$this->code = $code;
		$this->code_message = StatusCodes::getMessageForCode($code);
	}

	/**
	 * Custom string representation of the exception
	 * 
	 * @access public
	 * @return string Formatted exception message
	 */
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code_message}] [{$this->type}]: {$this->message} \n";
	}
}

/**
 * StatusCodes provides named constants for
 * HTTP protocol status codes. Written for the
 * Recess Framework (<a class="linkclass" href="http://www.recessframework.com/">http://www.recessframework.com/</a>)
 *
 * @author Kris Jordan
 * @license MIT
 * @package recess.http
 */
class StatusCodes {
	// [Informational 1xx]
	const HTTP_CONTINUE = 100;
	const HTTP_SWITCHING_PROTOCOLS = 101;
	// [Successful 2xx]
	const HTTP_OK = 200;
	const HTTP_CREATED = 201;
	const HTTP_ACCEPTED = 202;
	const HTTP_NONAUTHORITATIVE_INFORMATION = 203;
	const HTTP_NO_CONTENT = 204;
	const HTTP_RESET_CONTENT = 205;
	const HTTP_PARTIAL_CONTENT = 206;
	// [Redirection 3xx]
	const HTTP_MULTIPLE_CHOICES = 300;
	const HTTP_MOVED_PERMANENTLY = 301;
	const HTTP_FOUND = 302;
	const HTTP_SEE_OTHER = 303;
	const HTTP_NOT_MODIFIED = 304;
	const HTTP_USE_PROXY = 305;
	const HTTP_UNUSED= 306;
	const HTTP_TEMPORARY_REDIRECT = 307;
	// [Client Error 4xx]
	const errorCodesBeginAt = 400;
	const HTTP_BAD_REQUEST = 400;
	const HTTP_UNAUTHORIZED  = 401;
	const HTTP_PAYMENT_REQUIRED = 402;
	const HTTP_FORBIDDEN = 403;
	const HTTP_NOT_FOUND = 404;
	const HTTP_METHOD_NOT_ALLOWED = 405;
	const HTTP_NOT_ACCEPTABLE = 406;
	const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
	const HTTP_REQUEST_TIMEOUT = 408;
	const HTTP_CONFLICT = 409;
	const HTTP_GONE = 410;
	const HTTP_LENGTH_REQUIRED = 411;
	const HTTP_PRECONDITION_FAILED = 412;
	const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
	const HTTP_REQUEST_URI_TOO_LONG = 414;
	const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
	const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const HTTP_EXPECTATION_FAILED = 417;
	// [Server Error 5xx]
	const HTTP_INTERNAL_SERVER_ERROR = 500;
	const HTTP_NOT_IMPLEMENTED = 501;
	const HTTP_BAD_GATEWAY = 502;
	const HTTP_SERVICE_UNAVAILABLE = 503;
	const HTTP_GATEWAY_TIMEOUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;

	private static $messages = array(
		// [Informational 1xx]
		100=>'100 Continue',
		101=>'101 Switching Protocols',
		// [Successful 2xx]
		200=>'200 OK',
		201=>'201 Created',
		202=>'202 Accepted',
		203=>'203 Non-Authoritative Information',
		204=>'204 No Content',
		205=>'205 Reset Content',
		206=>'206 Partial Content',
		// [Redirection 3xx]
		300=>'300 Multiple Choices',
		301=>'301 Moved Permanently',
		302=>'302 Found',
		303=>'303 See Other',
		304=>'304 Not Modified',
		305=>'305 Use Proxy',
		306=>'306 (Unused)',
		307=>'307 Temporary Redirect',
		// [Client Error 4xx]
		400=>'400 Bad Request',
		401=>'401 Unauthorized',
		402=>'402 Payment Required',
		403=>'403 Forbidden',
		404=>'404 Not Found',
		405=>'405 Method Not Allowed',
		406=>'406 Not Acceptable',
		407=>'407 Proxy Authentication Required',
		408=>'408 Request Timeout',
		409=>'409 Conflict',
		410=>'410 Gone',
		411=>'411 Length Required',
		412=>'412 Precondition Failed',
		413=>'413 Request Entity Too Large',
		414=>'414 Request-URI Too Long',
		415=>'415 Unsupported Media Type',
		416=>'416 Requested Range Not Satisfiable',
		417=>'417 Expectation Failed',
		// [Server Error 5xx]
		500=>'500 Internal Server Error',
		501=>'501 Not Implemented',
		502=>'502 Bad Gateway',
		503=>'503 Service Unavailable',
		504=>'504 Gateway Timeout',
		505=>'505 HTTP Version Not Supported'
	);

	public static function httpHeaderFor($code) {
		return 'HTTP/1.1 ' . self::$messages[$code];
	}

	public static function getMessageForCode($code) {
		return self::$messages[$code];
	}

	public static function isError($code) {
		return is_numeric($code) && $code >= self::HTTP_BAD_REQUEST;
	}

	public static function canHaveBody($code) {
		return
			// True if not in 100s
			($code < self::HTTP_CONTINUE || $code >= self::HTTP_OK)
			&& // and not 204 NO CONTENT
			$code != self::HTTP_NO_CONTENT
			&& // and not 304 NOT MODIFIED
			$code != self::HTTP_NOT_MODIFIED;
	}
}

?>