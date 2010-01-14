# CouchLog
_Application Logging that Actually Helps_

## What is CouchLog?

An Application Logging tool built atop CouchDB via CouchApp.

## Why should I use CouchLog?

Applications usually needs to maintain activity logs of some sort. Usually this is accomplished through a flat file with each line containing 1 unique entry. If you're really sophisticated, you will have a database (like MySQL) that stores your log information.

Both methods work fine, until of course you need to do some real troubleshooting. How much useful information can you put into 1 line of a text file? (while still maintaining readability) How complex would a relational data-model be if you need to store meta-data that differs in structure for most different entries you make?

### Flexible Meta-Data Storage

CouchLog leverages the schema-less, document-oriented approach that CouchDB uses, enabling you to store useful debugging information into every log entry.

### Cheap in Terms of Performance

 * CouchDB's RESTful API cuts out several layers usually required by Database-Driven Applications.
   * There is no need for a specific driver to communicate with the database (any language that can operate with HTTP will do)
   * Since HTTP is the interface for CouchDB, it is stateless and thus requires no prolonged connection to the database server
 * JSON is a lightweight data-interchange format, greatly reducing necessary bandwidth and overhead

### Intuitive, Yet Powerful User Interface

The CouchApp Interface allows you to quickly and easily filter through many log entries at once, greatly enhancing efficiency and reducing headaches when you need to debug or troubleshoot your applications.

## How does CouchLog work?

Entries are made by sending a new Document to the CouchDB database.

### A Sample Log Entry Documents
	{
		"application": "My Application",
		"section": "User Activity",
		"level": "notice",
		"timestamp": 1263062089.44
		"message": "User Login Recorded",
		"data": {
			"username": "testuser",
			"source": "homepage"
		}
	}

You can use the CouchLog User Interface to browse through your applications log entries, filtering by date, application, section and level.

## Installing CouchLog

 * [Install CouchApp](http://wiki.github.com/couchapp/couchapp/manual-2) (along with CouchDB)
 * Check out the CouchLog Source from GitHub

	$ git clone git://github.com/desdev/CouchLog.git  
	$ cd CouchLog/couchapp  
	$ couchapp init  
	$ couchapp push applog  