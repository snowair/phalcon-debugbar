<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 16:09
 */

namespace Snowair\Debugbar;

use Phalcon\Events\Manager;
use Phalcon\Mvc\Router\Group;
use Snowair\Debugbar\PhalconDebugbar;
use Snowair\Debugbar\PhalconHttpDriver;
use Phalcon\Config\Adapter\Php;
use Phalcon\DI\Injectable;

class ServiceProvider extends Injectable {

	protected $configPath;

	public function __construct( $configPath=null ){
		$this->configPath = $configPath;
	}

	public function register( ){
		$configPath = $this->configPath;
		$this->di->set('config.debugbar', function() use($configPath){
			if ( $configPath===null ) {
				$configPath = __DIR__ . '/config/debugbar.php';
			}
			return new Php($configPath);
		});

		$this->di->set('debugbar', function(){
			$debugbar = new PhalconDebugbar($this->di);
			$debugbar->setHttpDriver(new PhalconHttpDriver());
			return $debugbar;
		});
		$this->setRoute();
		return $this;
	}

	protected function setRoute(){
		$app= $this->di['app'];
		$router = $this->di['router'];
		if (  $app instanceof \Phalcon\Mvc\Micro ) {
			$app->get( '/_debugbar/open', function(){
				$controller = new \Snowair\Debugbar\Controllers\OpenHandlerController();
				$controller->handleAction()->send();
			})->setName('debugbar.openhandler');

			$app->get( '/_debugbar/assets/stylesheets', function(){
				$controller = new \Snowair\Debugbar\Controllers\AssetController;
				$controller->cssAction()->send();
			})->setName('debugbar.assets.css');

			$app->get( '/_debugbar/assets/javascript', function(){
				$controller = new \Snowair\Debugbar\Controllers\AssetController;
				$controller->jsAction()->send();
			})->setName('debugbar.assets.js');

		}elseif (  $app instanceof \Phalcon\Mvc\Application ) {
			$router->addGet('/_debugbar/open',array(
				'namespace'=>'Snowair\Debugbar\Controllers',
				'controller'=>'\OpenHandler',
				'action'=>'handle',
			))->setName('debugbar.openhandler');

			$router->addGet('/_debugbar/assets/stylesheets',array(
				'namespace'=>'Snowair\Debugbar\Controllers',
				'controller'=>'Asset',
				'action'=>'css',
			))->setName('debugbar.assets.css');

			$router->addGet('/_debugbar/assets/javascript',array(
				'namespace'=>'Snowair\Debugbar\Controllers',
				'controller'=>'Asset',
				'action'=>'js',
			))->setName('debugbar.assets.js');
		}
	}

	public function boot() {
		$app      = $this->di['app'];
		$debugbar = $this->di['debugbar'];
		$router   = $this->di['router'];
		if (! $this->di['config.debugbar']->get('enabled')) {
			return;
		}
		$eventsManager = new Manager();
		if (  $app instanceof \Phalcon\Mvc\Micro ) {
			$eventsManager->attach('micro:beforeExecuteRoute', function() use($router) {
				ob_start();
			});
			$eventsManager->attach('micro:afterExecuteRoute',function($event,$app) use($debugbar){
				$response = $app->response;
				if ( null=== $returned=$app->getReturnedValue() ) {
					$buffer = ob_get_clean();
					$response->setContent($buffer);
					$response = $debugbar->modifyResponse($response);
					$response->send();
				}elseif(is_object($returned) && ($returned instanceof \Phalcon\Http\ResponseInterface)){
					$debugbar->modifyResponse($returned);
				}
			});
		}elseif (  $app instanceof \Phalcon\Mvc\Application ) {
			$eventsManager->attach('application:beforeSendResponse',function($event,$app,$response) use($debugbar){
				$debugbar->modifyResponse($response);
			});
		}
		$app->setEventsManager($eventsManager);
		$debugbar->boot();
	}
}