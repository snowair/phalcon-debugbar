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
		return $this;
	}

	public function boot() {
		$group = new Group( array('namespace'=>'Snowair\Debugbar\Controllers') );
		$group->setPrefix('/_debugbar/');
		$group->addGet('open',array(
			'controller'=>'OpenHandler',
			'action'=>'handle',
		))->setName('debugbar.openhandler');

		$group->addGet('assets/stylesheets',array(
			'controller'=>'Asset',
			'action'=>'css',
		))->setName('debugbar.assets.css');

		$group->addGet('assets/javascript',array(
			'controller'=>'Asset',
			'action'=>'js',
		))->setName('debugbar.assets.js');

		$this->router->mount($group);

		if (! $this->di['config.debugbar']->get('enabled')) {
			return;
		}

		$debugbar = $this->di['debugbar'];
		$debugbar->boot();

		$eventsManager = new Manager();
		$eventsManager->attach('application:beforeSendResponse',function($event,$app,$response) use($debugbar){
			$debugbar->modifyResponse($response);
		});
		$this->di['app']->setEventsManager($eventsManager);
	}
}