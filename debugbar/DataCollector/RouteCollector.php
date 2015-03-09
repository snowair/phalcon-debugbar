<?php
/**
 * User: zhuyajie
 * Date: 15/3/4
 * Time: 21:25
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Phalcon\Mvc\Router;
use Phalcon\Dispatcher;

class RouteCollector extends DataCollector implements Renderable {

	/**
	 * @var  Router $router
	 */
	protected $router;

	public function __construct( Router $router, Dispatcher $dispatcher )
	{
		$this->router = $router;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 * @return array Collected data
	 */
	function collect() {
		$route = $this->router->getMatchedRoute();
		$dispatcher = $this->dispatcher;
		if ( !$route) {
			return array();
		}

		$uri   = $route->getPattern();
		$paths = $route->getPaths();

		$result = array(
			'uri' => $uri ?: '-',
			'paths'=> $this->formatVar( $paths),
		);
		($verbs = $route->getHttpMethods())? $result['HttpMethods'] = $verbs : null;
		($name  = $route->getName())? $result['RouteName'] = $name : null;
		($hostname  = $route->getHostname())? $result['hostname'] = $hostname : null;

		$result['Moudle']     = $this->router->getModuleName();
		$result['Controller'] = get_class( $controller_instance = $dispatcher->getActiveController());
		$result['Action']     = $dispatcher->getActiveMethod();

		$reflector = new \ReflectionMethod($controller_instance, $result['Action']);

		if (isset($reflector)) {
			$filename = substr($reflector->getFileName(),mb_strlen(realpath(dirname($_SERVER['DOCUMENT_ROOT']))));
			$result['file'] = $filename . ':' . $reflector->getStartLine() . '-' . $reflector->getEndLine();
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