<?php

return array(

	/*
	 |--------------------------------------------------------------------------
	 | Debugbar Settings
	 |--------------------------------------------------------------------------
	 |
	 | Debugbar is enabled by default, when debug is set to true in app.php.
	 |
	 */

	'enabled' => true,

	/*
	 |--------------------------------------------------------------------------
	 | Storage settings
	 |--------------------------------------------------------------------------
	 |
	 | DebugBar stores data for session/ajax requests.
	 | You can disable this, so the debugbar stores data in headers/session,
	 | but this can cause problems with large data collectors.
	 | By default, file storage (in the storage folder) is used. Redis and PDO
	 | can also be used. For PDO, run the package migrations first.
	 |
	 */
	'storage' => array(
		'enabled' => true,
		'driver' => 'file',
		'path' => '../Runtime/debugbar', // For file driver
	),

	/*
	 |--------------------------------------------------------------------------
	 | Vendors
	 |--------------------------------------------------------------------------
	 |
	 | Vendor files are included by default, but can be set to false.
	 | This can also be set to 'js' or 'css', to only include javascript or css vendor files.
	 | Vendor files are for css: font-awesome (including fonts) and highlight.js (css files)
	 | and for js: jquery and and highlight.js
	 | So if you want syntax highlighting, set it to true.
	 | jQuery is set to not conflict with existing jQuery scripts.
	 |
	 */

	'include_vendors' => true,

	/*
	 |--------------------------------------------------------------------------
	 | Capture Ajax Requests
	 |--------------------------------------------------------------------------
	 |
	 | The Debugbar can capture Ajax requests and display them. If you don't want this (ie. because of errors),
	 | you can use this option to disable sending the data through the headers.
	 |
	 */

	'capture_ajax' => true,

	/*
	 |--------------------------------------------------------------------------
	 | DataCollectors
	 |--------------------------------------------------------------------------
	 |
	 | Enable/disable DataCollectors
	 |
	 */

	'collectors' => array(
		'phpinfo'         => true,  // Php version
		'messages'        => true,  // Messages
		'time'            => true,  // Time Datalogger
		'memory'          => true,  // Memory usage
		'exceptions'      => true,  // Exception displayer
		'default_request' => false, // Regular or special Symfony request logger
		'phalcon_request' => true,  // Only one can be enabled..
		'session'         => true, // Display session data in a separate tab
		'config'          => true,
		'route'           => true,
		'db'              => true,
	),

	/*
	 |--------------------------------------------------------------------------
	 | Extra options
	 |--------------------------------------------------------------------------
	 |
	 | Configure some DataCollectors
	 |
	 */

	'options' => array(
		'exceptions'=>array(
			'chain'=>true,
		),
		'db' => array(
			'with_params'       => false,   // Render SQL with the parameters substituted
			'backtrace' => true,  // EXPERIMENTAL: Use a backtrace to find the origin of the query in your files.
			'explain'   => true,  // EXPLAIN select statement
			'error_mode'=> \PDO::ERRMODE_SILENT, // \PDO::ERRMODE_SILENT , \PDO::ERRMODE_WARNING, \PDO::ERRMODE_EXCEPTION
			'show_conn'=>false, // IF show connection info
		),
	),

	/*
	 |--------------------------------------------------------------------------
	 | Inject Debugbar in Response
	 |--------------------------------------------------------------------------
	 |
	 | Usually, the debugbar is added just before <body>, by listening to the
	 | Response after the App is done. If you disable this, you have to add them
	 | in your template yourself. See http://phpdebugbar.com/docs/rendering.html
	 |
	 */

	'inject' => true,

);