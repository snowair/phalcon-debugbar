<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 16:09
 */

namespace Snowair\Debugbar;

use Phalcon\Events\Manager;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Micro;
use Snowair\Debugbar\Controllers\AssetController;
use Snowair\Debugbar\Controllers\OpenHandlerController;
use Phalcon\Config\Adapter\Php;
use Phalcon\DI\Injectable;
use Snowair\Debugbar\Controllers\ToolsController;

class ServiceProvider extends Injectable {

	protected $configPath;

	public function __construct( $configPath=null ){
		$this->configPath = $configPath;
	}

	public function start() {
		$this->register()->boot();
	}

	public function register( ){
		$configPath = $this->configPath;
		$this->di->set('config.debugbar', function() use($configPath){
			$base =new Php(__DIR__ . '/config/debugbar.php');
			if ( is_string( $configPath) && is_file($configPath) ) {
				$config = new Php($configPath);
				$base->merge($config);
			}elseif( is_object($configPath) && $configPath instanceof Php){
				$base->merge($configPath);
			}else{
			}
			return $base;
		},true);

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
		if (  $app instanceof Micro ) {
			$app->get( '/_debugbar/open', function(){
				$controller = new OpenHandlerController();
				$controller->handleAction()->send();
			})->setName('debugbar.openhandler');

			$app->get( '/_debugbar/assets/stylesheets', function(){
				$controller = new AssetController;
				$controller->cssAction()->send();
			})->setName('debugbar.assets.css');

			$app->get( '/_debugbar/assets/javascript', function(){
				$controller = new AssetController;
				$controller->jsAction()->send();
			})->setName('debugbar.assets.js');

			$app->get( '/_debugbar/tools/phpinfo', function(){
				$controller = new ToolsController();
				$controller->phpinfoAction();
			})->setName('debugbar.tools.phpinfo');

		}elseif (  $app instanceof Application ) {
			$router->addGet('/_debugbar/open',array(
				'namespace'=>'Snowair\Debugbar\Controllers',
				'controller'=>'open_handler',
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

			$router->addGet('/_debugbar/tools/phpinfo',array(
				'namespace'=>'Snowair\Debugbar\Controllers',
				'controller'=>'Tools',
				'action'=>'phpinfo',
			))->setName('debugbar.tools.phpinfo');
		}
	}

	public function boot() {
		$app      = $this->di['app'];
		$debugbar = $this->di['debugbar'];
		$router   = $this->di['router'];

		$eventsManager = $app->getEventsManager();
		if ( !is_object( $eventsManager ) ) {
			$eventsManager = new Manager();
		}
		if (  $app instanceof Micro ) {
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
				}elseif(is_object($returned) && ($returned instanceof ResponseInterface)){
					$debugbar->modifyResponse($returned);
				}
			});
		}elseif (  $app instanceof Application ) {
			$eventsManager->attach('application:beforeSendResponse',function($event,$app,$response) use($debugbar){
				$debugbar->modifyResponse($response);
			});
		}
		$eventsManager->attach('application:afterStartModule',function($event,$app,$module) use($debugbar){
			$debugbar->attachServices();
		});
		$app->setEventsManager($eventsManager);

        $this->safeCheck();

		$debugbar->boot();
	}

    protected function safeCheck()
    {
        $config   = $this->di['config.debugbar'];
        $router   = $this->di['router'];
        $debugbar = $this->di['debugbar'];

        if ( $config->get('enabled')) {
            $white_lists = (array)$config->get('white_lists');
            if ( !empty($white_lists) && !in_array($this->di['request']->getClientAddress(true),$white_lists)) {
                $debugbar->disable();
                return;
            }

            $router->handle();
            $deny_routes  = (array)$config->get('deny_routes');
            $allow_routes = (array)$config->get('allow_routes');

            $current = $router->getMatchedRoute();

            if (is_object( $current )) {
                $current = $current->getName();

                if ( strpos($current,'debugbar')===0 ) {
                    $app = $this->di['app'];
                    $app->useImplicitView(false);
                    $debugbar->isDebugbarRequest=true;
                    $debugbar->debugbarRequestCollector();
                    $debugbar->disable();
                    return;
                }

                if(  !empty($allow_routes)  && !in_array( $current,$allow_routes ) ){
                    $debugbar->disable();
                    return;
                }

                if( !empty($deny_routes)  && in_array( $current,$deny_routes )){
                    $debugbar->disable();
                    return;
                }
            }
            return;
        }
    }
}
