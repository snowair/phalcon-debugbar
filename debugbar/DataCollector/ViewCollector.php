<?php
/**
 * User: zhuyajie
 * Date: 15/3/7
 * Time: 10:45
 */

namespace Snowair\Debugbar\DataCollector;

use DebugBar\Bridge\Twig\TwigCollector;
use Phalcon\Mvc\ViewInterface;

class ViewCollector  extends TwigCollector {

	use Formatter;

	protected $viewProfiler;
	protected $view;

	/**
	 * Create a ViewCollector
	 *
	 * @param \DebugBar\Bridge\Twig\TraceableTwigEnvironment $viewProfiler
	 * @param ViewInterface                                  $view
	 */
	public function __construct($viewProfiler,ViewInterface $view)
	{
		$this->viewProfiler = $viewProfiler;
		$this->view = $view;
	}

	public function getName()
	{
		return 'views';
	}

	public function getAssets()
	{
		return array(
			'css' => 'widgets/templates/widget.css',
			'js' => __DIR__ . '/../Resources/templates/widget.js',
		);
	}

	public function getWidgets()
	{
		return array(
			'views' => array(
				'icon' => 'leaf',
				'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
				'map' => 'views',
				'default' => '[]'
			),
			'views:badge' => array(
				'map' => 'views.nb_templates',
				'default' => 0
			)
		);
	}

	public function collect()
	{
		$profiler = $this->viewProfiler;
		$all_templates = $profiler->templates;
		$engines = $profiler->engines;
		$accuRenderTime = $profiler->stopRender - $profiler->startRender;

		$templates = array();
		foreach ($all_templates as $name=> $tpl) {
			if ( isset($tpl['stopTime']) ) {
				$render_time = $tpl['stopTime'] - $tpl['startTime'];
				$templates[] = array(
					'name' => $this->normalizeFilename($name),
					'render_time' => $render_time,
					'render_time_str' => $this->formatDuration($render_time),
					'type' => $this->getEngine($name, $engines ),
				);
			}else{
				$templates[] = array(
					'name' => '[Skiped]'. $this->normalizeFilename($name),
					'type' => $this->getEngine($name, $engines ),
				);
			}
		}

		if(empty($templates)){
			$vars = null;
		}else if (!is_string( $vars = $profiler->params)) {
			if ( !empty($this->_customFormatMap) ) {
				$vars = $this->preFormatVars($vars);
			}
			$formated = $this->formatVars($vars);
			$vars = $formated[0];
		}
		return array(
			'nb_templates' => count($templates),
			'templates' => $templates,
			'vars'=> $vars,
			'accumulated_render_time' => $accuRenderTime,
			'accumulated_render_time_str' => $this->formatDuration($accuRenderTime)
		);
	}

	protected function preFormatVars($vars){
		foreach ( $vars as $key => $value ) {
			if ( is_object( $value ) ){
				$class=get_class($value);
				if ( isset($this->_customFormatMap[$class]) && is_callable($callable = $this->_customFormatMap[$class] )) {
					$array = call_user_func($callable,$vars);
					if ( is_array( $array ) ) {
						$vars[$key] = $array;
					}else{
						throw new \Exception('ViewCollector customFormatMap callable must return a native Array.');
					}
				}
			}
		}
		return $vars;
	}

	protected function getEngine( $path, $engines ) {
		static $cache=array();
		if ( empty($cache) ) {
			foreach ( $engines as $key => $value ) {
				if ( is_string( $value ) ) {
					$cache[$key] = $value;
				}elseif($value instanceof \Closure){
					$cache[$key] = get_class($value($this->view,$this->view->getDi()));
				}else{
					$cache[$key] = get_class($value);
				}
			}
		}
		$extentsion = pathinfo($path,PATHINFO_EXTENSION);
		return $cache['.'.$extentsion];
	}

	protected function normalizeFilename($path)
	{
		if (file_exists($path)) {
			$path = realpath($path);
		}
		return substr($path,mb_strlen(realpath(dirname($_SERVER['DOCUMENT_ROOT']))));
	}
}