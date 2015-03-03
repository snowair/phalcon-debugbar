<?php

use Phalcon\Mvc\Application;

define('PHALCON_START', microtime(true));
error_reporting(E_ALL);

try {
	/**
	 * Include composer autoload
	 */
	require_once __DIR__ . '/../vendor/autoload.php';

    /**
     * Include services
     */
    require __DIR__ . '/../config/services.php';

    /**
     * Handle the request
     */
    $application = new Application($di);

    /**
     * Include modules
     */
    require __DIR__ . '/../config/modules.php';

    echo $application->handle()->getContent();

} catch (Exception $e) {
	$whoops = new \Whoops\Run;
	$whoops->handleError( E_USER_NOTICE, $e->getMessage(),$e->getFile(),$e->getLine());
}
