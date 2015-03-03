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

	'enabled' => false,

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
		'path' => './Runtime/debugbar', // For file driver
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
		'request'         => true, // Regular or special Symfony request logger
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
