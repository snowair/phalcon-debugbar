<?php
/**
 * User: zhuyajie
 * Date: 15/3/4
 * Time: 21:25
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Router;

class RouteCollector extends DataCollector implements Renderable {

	/**
	 * @var  Router $router
	 */
	protected $router;
	protected $di;

	public function __construct( $di )
	{
		$this->di = $di;
		$this->router = $di['router'];
		$this->dispatcher = $di['dispatcher'];
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 * @return array Collected data
	 */
	function collect() {
		$router = $this->router;
		$route = $router->getMatchedRoute();
		$dispatcher = $this->dispatcher;
		if ( !$route) {
			return array();
		}

		$uri   = $route->getPattern();
		$paths = $route->getPaths();
		$result['uri']    = $uri ?: '-';
		$result['paths']  = $this->formatVar( $paths);
		$result['params'] = $this->formatVar( $router->getParams());

		($verbs = $route->getHttpMethods())? $result['HttpMethods'] = $verbs : null;
		($name  = $route->getName())? $result['RouteName'] = $name : null;
		($hostname  = $route->getHostname())? $result['hostname'] = $hostname : null;
		if ( $this->di->has('app') && ($app=$this->di['app']) instanceof  Micro ) {
				if ( ($handler=$app->getActiveHandler()) instanceof \Closure  ||  is_string($handler) ) {
					$reflector = new \ReflectionFunction($handler);
				}elseif(is_array($handler)){
					$reflector = new \ReflectionMethod($handler[0], $handler[1]);
				}
		}else{
			( $module=$this->router->getModuleName() )? $result['Moudle']=$module:null;
			$result['Controller'] = get_class( $controller_instance = $dispatcher->getActiveController());
			$result['Action']     = $dispatcher->getActiveMethod();
			$reflector = new \ReflectionMethod($controller_instance, $result['Action']);
		}

		if (isset($reflector)) {
			$start = $reflector->getStartLine()-1;
			$stop  = $reflector->getEndLine();
			$filename = substr($reflector->getFileName(),mb_strlen(realpath(dirname($_SERVER['DOCUMENT_ROOT']))));
			$code = array_slice( file($reflector->getFileName()),$start, $stop-$start );
			$result['file'] = $filename . ':' . $reflector->getStartLine() . '-' . $reflector->getEndLine() . "  [CODE]: \n". implode("",$code);
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'route';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getWidgets()
	{
		$widgets = array(
			"route" => array(
				"icon" => "share",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => "route",
				"default" => "{}"
			)
		);
		return $widgets;
	}

}