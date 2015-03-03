<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 16:09
 */

namespace Snowair;

use Phalcon\Mvc\Router\Group;
use Snowair\Debugbar\PhalconDebugbar;
use Snowair\Debugbar\PhalconHttpDriver;
use Phalcon\Config\Adapter\Php;
use Phalcon\DI\Injectable;

class ServiceProvider extends Injectable {

	public function register( $configPath = null ){
		$this->di->set('config.debugbar', function() use($configPath){
			if ( $configPath===null ) {
				$configPath = __DIR__ . '/../config/debugbar.php';
			}
			return new Php($configPath);
		});

		$this->di->set('debugbar', function(){
			$debugbar = new PhalconDebugbar($this->di);
			$debugbar->setHttpDriver(new PhalconHttpDriver());
			return $debugbar;
		});
		return $this;
	}

	public function boot() {
		$group = new Group( array('namespace'=>'Snowair\Debugbar\Controllers') );
		$group->setPrefix('_debugbar');

		$group->addGet('open',array(
			'controller'=>'OpenHandlerController',
			'action'=>'handle',
		))->setName('debugbar.openhandler');

		$group->addGet('assets/stylesheets',array(
			'controller'=>'AssetController',
			'action'=>'css',
		))->setName('debugbar.assets.css');

		$group->addGet('assets/javascript',array(
			'controller'=>'AssetController',
			'action'=>'js',
		))->setName('debugbar.assets.js');

		$this->router->mount($group);

		if (! $this->di['config.debugbar']->get('enabled')) {
			return;
		}

		$debugbar = $this->di['debugbar'];
		$debugbar->boot();

		$eventsManager = $this->eventsManager;
		$eventsManager->attach('application:beforeSendResponse',function($app,$response) use($debugbar){
			$debugbar->modifyResponse($response);
		});
	}
}