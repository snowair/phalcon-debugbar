<?php

namespace Myphalcon\Frontend;

use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Snowair\Debugbar\PhalconDebugbar;

class Module implements ModuleDefinitionInterface
{

    /**
     * Registers the module auto-loader
     */
    public function registerAutoloaders()
    {

        $loader = new Loader();

        $loader->registerNamespaces(array(
            'Myphalcon\Frontend\Controllers' => __DIR__ . '/controllers/',
            'Myphalcon\Frontend\Models' => __DIR__ . '/models/',
        ));

        $loader->register();
    }

    /**
     * Registers the module-only services
     *
     * @param \Phalcon\DI $di
     */
    public function registerServices($di)
    {

        /**
         * Read configuration
         */
        $config = include __DIR__ . "/config/config.php";
		$di->set('config', $config );

        /**
         * Setting up the view component
         */
        $di['view'] = function () {
            $view = new View();
            $view->setViewsDir(__DIR__ . '/views/');

            return $view;
        };

        /**
         * Database connection is created based in the parameters defined in the configuration file
         */
        $di['db'] = function () use ($config) {
            $db = new DbAdapter(array(
                "host" => $config->database->host,
                "username" => $config->database->username,
                "password" => $config->database->password,
                "dbname" => $config->database->name
            ));

			return $db;
        };
		if ( $di->has('debugbar') ) {
			/** @var PhalconDebugbar $debugbar */
			$debugbar = $di['debugbar'];
			$debugbar->addPdoCollector($di['db']);
		}

    }

}
