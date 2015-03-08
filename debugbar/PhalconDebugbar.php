<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 11:25
 */

namespace Snowair\Debugbar;

use DebugBar\Bridge\SwiftMailer\SwiftLogCollector;
use DebugBar\Bridge\SwiftMailer\SwiftMailCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use Exception;
use Phalcon\Db\Adapter;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\DI;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\ViewInterface;
use Phalcon\Registry;
use Snowair\Debugbar\DataCollector\ConfigCollector;
use Snowair\Debugbar\DataCollector\MessagesCollector;
use Snowair\Debugbar\DataCollector\PhalconRequestCollector;
use Snowair\Debugbar\DataCollector\QueryCollector;
use Snowair\Debugbar\DataCollector\RouteCollector;
use Snowair\Debugbar\DataCollector\SessionCollector;
use Snowair\Debugbar\DataCollector\ViewCollector;
use Snowair\Debugbar\Phalcon\Db\Profiler;

/**
 * Debug bar subclass which adds all without Request and with Collector.
 * Rest is added in Service Provider
 *
 * @method void emergency($message)
 * @method void alert($message)
 * @method void critical($message)
 * @method void error($message)
 * @method void warning($message)
 * @method void notice($message)
 * @method void info($message)
 * @method void debug($message)
 * @method void log($message)
 */
class PhalconDebugbar extends DebugBar {

	/**
	 * @var  DI $di
	 */
	protected $di;
	protected $config;
	protected $booted = false;

	public function __construct($di)
	{
		$this->di = $di;
		$this->config = $di['config.debugbar'];
	}

	public function enable()
	{
		$this->config->enable=true;
		if (!$this->booted) {
			$this->boot();
		}
	}

	public function disable()
	{
		$this->config->enable=false;
	}

	/**
	 * 检查是否启用了debugbar
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->config->enable;
	}

	/**
	 * Check if this is a request to the Debugbar OpenHandler
	 *
	 * @return bool
	 */
	protected function isDebugbarRequest()
	{
		$segment =explode('/', trim($this->di['request']->getUri(),'/'));
		return $segment[0] == '_debugbar';
	}

	public function shouldCollect($name, $default = false)
	{
		return $this->config->collectors->get($name,$default);
	}

	/**
	 * 启动debugbar: 设置collector
	 */
	public function boot() {
		$debugbar = $this;
		if ( !$this->isDataPersisted() ) {
			$this->selectStorage($debugbar); // for normal request and debugbar request both
		}
		if ($this->booted) {
			return;
		}
		$this->booted = true;
		if (!$this->isEnabled() || $this->isDebugbarRequest()) {
			return;
		}
		// only for normal request
		if ($this->shouldCollect('phpinfo', true)) {
			$this->addCollector(new PhpInfoCollector());
		}
		if ($this->shouldCollect('messages', true)) {
			$this->addCollector(new MessagesCollector());
		}
		if ($this->shouldCollect('memory', true)) {
			$this->addCollector(new MemoryCollector());
		}
		if ($this->shouldCollect('default_request', false)) {
			$this->addCollector(new RequestDataCollector());
		}
		if ($this->shouldCollect('exceptions', true)) {
			try {
				$exceptionCollector = new ExceptionsCollector();
				$exceptionCollector->setChainExceptions(
					$this->config->options->exceptions->get('chain', true)
				);
				$this->addCollector($exceptionCollector);
			} catch (\Exception $e) {
				$this->addException($e);
			}
		}
		if ($this->shouldCollect('time', true)) {
			if (defined('PHALCON_START')){
				$startTime = PHALCON_START;
			}else{
				$startTime = isset($_SERVER["REQUEST_TIME_FLOAT"])?$_SERVER["REQUEST_TIME_FLOAT"]:$_SERVER["REQUEST_TIME"];
			}
			$this->addCollector(new TimeDataCollector($startTime));
		}

		if ($this->shouldCollect('route') && $this->di->has('router') && $this->di->has('dispatcher')) {
			try {
				$this->addCollector(new RouteCollector($this->di['router'],$this->di['dispatcher']));
			} catch (\Exception $e) {
				$this->addException(
					new Exception(
						'Cannot add RouteCollector to Phalcon Debugbar: ' . $e->getMessage(),
						$e->getCode(),
						$e
					)
				);
			}
		}
		if ( $this->di->has( 'db' ) ) {
			$this->attachDb( $this->di['db'] );
		}
		if ( $this->di->has( 'view' ) ) {
			$this->attachView( $this->di['view'] );
		}

		if ($this->shouldCollect('mail', true) && $this->di->has('mailer') ) {
			$this->attachMailer( $this->di['mailer'] );
		}

		$renderer = $this->getJavascriptRenderer();
		$renderer->setIncludeVendors($this->config->get('include_vendors', true));
		$renderer->setBindAjaxHandlerToXHR($this->config->get('capture_ajax', true));
	}

	public function attachMailer( $mailer ) {
		if (!$this->shouldCollect('mail', false)  ) {
			return;
		}
		static $started;
		if ( !$started ) {
			$started = true;
			try {
				if ( class_exists('\Swifit_Mailer') && ( $mailer instanceof \Swift_Mailer ) ) {
					$this->addCollector(new SwiftMailCollector($mailer));
					if ($this->config->options->mail->get('full_log',false) and $this->hasCollector(
							'messages'
						)
					) {
						$this['messages']->aggregate(new SwiftLogCollector($mailer));
					}
				}
			} catch (\Exception $e) {
				$this->addException(
					new Exception(
						'Cannot add MailCollector to Phalcon Debugbar: ' . $e->getMessage(), $e->getCode(), $e
					)
				);
			}
		}
	}

	public function attachView( ViewInterface $view )
	{
		if (!$this->shouldCollect('view', true)  ) {
			return;
		}
		static $started;
		// You can add only One View instance
		if ( !$started ) {
			$started=true;
			$eventsManager = new Manager();
			$viewProfiler = new Registry();
			$viewProfiler->templates=array();
			$viewProfiler->engines = $view->getRegisteredEngines();
			$config = $this->config;
			$eventsManager->attach('view:beforeRender',function($event,$view) use($viewProfiler)
			{
				$viewProfiler->startRender= microtime(true);

			});
			$eventsManager->attach('view:afterRender',function($event,$view) use($viewProfiler,$config)
			{
				$viewProfiler->stopRender= microtime(true);
				if ( $config->options->views->get( 'data', false ) ) {
					$viewProfiler->params = $view->getParamsToView();
				}else{
					$viewProfiler->params = null;
				}
			});
			$eventsManager->attach('view:beforeRenderView',function($event,$view,$viewFilePath) use($viewProfiler)
			{
				$viewProfiler->templates[$viewFilePath]	= array( 'startTime'=>microtime(true), );
			});
			$eventsManager->attach('view:afterRenderView',function($event,$view) use($viewProfiler)
			{
				$viewFilePath = $view->getActiveRenderPath();
				$viewProfiler->templates[$viewFilePath]['stopTime'] = microtime(true);

			});
			$view->setEventsManager($eventsManager);

			$collector = new ViewCollector($viewProfiler,$view);
			$this->addCollector($collector);
		}
	}

	/**
	 * @param DebugBar $debugbar
	 */
	protected function selectStorage(DebugBar $debugbar)
	{
		$config = $this->config;
		if ($config->storage->enabled) {
			$driver = $config->storage->get('driver','file');
			switch ($driver) {
				//TODO:: other driver
				default:
					$path = $config->storage->path;
					$storage = new FilesystemStorage($path);
					break;
			}

			$debugbar->setStorage($storage);
		}
	}

	/**
	 * Starts a measure
	 *
	 * @param string $name Internal name, used to stop the measure
	 * @param string $label Public name
	 */
	public function startMeasure($name, $label = null)
	{
		if ($this->hasCollector('time')) {
			/** @var \DebugBar\DataCollector\TimeDataCollector $collector */
			$collector = $this->getCollector('time');
			$collector->startMeasure($name, $label);
		}
	}

	/**
	 * Stops a measure
	 *
	 * @param string $name
	 */
	public function stopMeasure($name)
	{
		if ($this->hasCollector('time')) {
			/** @var \DebugBar\DataCollector\TimeDataCollector $collector */
			$collector = $this->getCollector('time');
			try {
				$collector->stopMeasure($name);
			} catch (\Exception $e) {
				  $this->addException($e);
			}
		}
	}

	/**
	 * Adds an exception to be profiled in the debug bar
	 *
	 * @param Exception $e
	 */
	public function addException(Exception $e)
	{
		if ($this->hasCollector('exceptions')) {
			/** @var \DebugBar\DataCollector\ExceptionsCollector $collector */
			$collector = $this->getCollector('exceptions');
			$collector->addException($e);
		}
	}
	/**
	 * Returns a JavascriptRenderer for this instance
	 *
	 * @param string $baseUrl
	 * @param null   $basePath
	 *
	 * @return JsRender
	 */
    public function getJavascriptRenderer($baseUrl = null, $basePath = null)
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JsRender($this, $baseUrl, $basePath);
            $this->jsRenderer->setUrlGenerator($this->di['url']);
        }
        return $this->jsRenderer;
    }

	/**
	 * @param  Response $response
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function modifyResponse($response){
		$request = $this->di['request'];
		$config  = $this->config;

		if (!$this->isEnabled() || $this->isDebugbarRequest()) {
			return $response;
		}

		if ($this->shouldCollect('config', false) && $this->di->has('config')) {
			try {
				$config_data = $this->di['config']->toArray();
				$protect = $config->options->config->get('protect');
				$configCollector = new ConfigCollector($config_data);
				$configCollector->setProtect($protect);
				$this->addCollector($configCollector);
			} catch (\Exception $e) {
				$this->addException(
					new Exception(
						'Cannot add ConfigCollector to Phalcon Debugbar: ' . $e->getMessage(),
						$e->getCode(),
						$e
					)
				);
			}
		}

		if ($this->shouldCollect('session')   && $this->di->has('session') ) {
			try {
				$this->addCollector(new SessionCollector($this->di['session']));
			} catch (\Exception $e) {
				$this->addException(
					new Exception(
						'Cannot add SessionCollector to Phalcon Debugbar: ' . $e->getMessage(),
						$e->getCode(),
						$e
					)
				);
			}
		}

		if ($this->shouldCollect('phalcon_request', true) and !$this->hasCollector('request')) {
			try {
				$this->addCollector(new PhalconRequestCollector($this->di['request'],$response,$this->di));
			} catch (\Exception $e) {
				$this->addException(
					new Exception(
						'Cannot add PhalconRequestCollector to Phalcon Debugbar: ' . $e->getMessage(),
						$e->getCode(),
						$e
					)
				);
			}
		}

		if( $this->hasCollector('pdo') ){
			/** @var Profiler $profiler */
			$profiler = $this->getCollector('pdo')->getProfiler();
			$profiler->handleFailed();
		};


		try {
			if ($this->isRedirection($response)) {
					$this->stackData();
			}
			elseif ( $this->isJsonRequest($request) && $this->config->get('capture_ajax', true) )
			{
					$this->sendDataInHeaders(true);
			} elseif (
				( ($content_type = $response->getHeaders()->get('Content-Type')) and
					strpos($response->getHeaders()->get('Content-Type'), 'html') === false)
			) {
					$this->collect();
			} elseif ($this->config->get('inject', true)) {
					$this->injectDebugbar($response);
			}
		} catch (\Exception $e) {
			$this->addException($e);
		}

		// Stop further rendering (on subrequests etc)
		$this->disable();

		return $response;
	}

	/**
	 * @param Adapter $db
	 */
	public function attachDb( $db ) {
		if ($this->shouldCollect('db', true)  ) {
			static $profiler,$eventsManager,$queryCollector;
			$config = $this->config;
			if ( !$profiler ) {
				$profiler = new Profiler();
			}
			try {
				if ( !$queryCollector ) {
					$queryCollector = new QueryCollector($profiler);
					if ( $config->options->db->get( 'with_params', false ) ) {
						$queryCollector->setRenderSqlWithParams();
					}
					if ( $config->options->db->backtrace ) {
						$queryCollector->setFindSource( true );
					}
					if ( $config->options->db->get('show_conn',false) ) {
						$queryCollector->setShowConnection( true );
					}
					if ( $config->options->db->get( 'explain', false ) ) {
						$profiler->setExplainQuery(true);
					}
					$this->addCollector($queryCollector);
				}
			} catch (\Exception $e) {
				$this->addException(
					new Exception(
						'Cannot add listen to Queries for Phalcon Debugbar: ' . $e->getMessage(),
						$e->getCode(),
						$e
					)
				);
			}
			$pdo = $db->getInternalHandler();
			$pdo->setAttribute(\PDO::ATTR_ERRMODE, $config->options->db->error_mode);
			if ( !$eventsManager ) {
				$eventsManager = new Manager();
				$eventsManager->attach('db', function(Event $event, Adapter $db, $params)  use (
					$profiler,$queryCollector
				) {
					$profiler->setDb($db);
					if ($event->getType() == 'beforeQuery') {
						$sql = $db->getRealSQLStatement();
						if ( stripos( $sql, 'SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES`' )===false
							&& stripos( $sql, 'DESCRIBE')!==0) {
							$profiler->startProfile($sql,$params);
							if ($queryCollector->getFindSource()) {
								try {
									$source = $queryCollector->findSource();
									$profiler->setSource($source);
								} catch (\Exception $e) {
								}
							}
						}
					}
					if ($event->getType() == 'afterQuery') {
						$sql = $db->getRealSQLStatement();
						if ( stripos( $sql, 'SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES`' )===false
							&& stripos( $sql, 'DESCRIBE')!==0) {
							$profiler->stopProfile();
						}
					}
				});
			}
			$db->setEventsManager($eventsManager);
		}
	}

	/**
	 * @param Response $response
	 *
	 * @return bool
	 */
	public function isRedirection($response) {
		$status = $response->getHeaders()->get('Status');
		$code   = (int)strstr($status,' ',true);
		return $code >= 300 && $code < 400;
	}

	/**
	 * Collects the data from the collectors
	 *
	 * @return array
	 */
	public function collect()
	{
		/** @var Request $request */
		$request = $this->di['request'];

		$this->data = array(
			'__meta' => array(
				'id' => $this->getCurrentRequestId(),
				'datetime' => date('Y-m-d H:i:s'),
				'utime' => microtime(true),
				'method' => $request->getMethod(),
				'uri' => $request->getURI(),
				'ip' => $request->getClientAddress()
			)
		);

		foreach ($this->collectors as $name => $collector) {
			$this->data[$name] = $collector->collect();
		}

		// Remove all invalid (non UTF-8) characters
		array_walk_recursive(
			$this->data,
			function (&$item) {
				if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
					$item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
				}
			}
		);

		if ($this->storage !== null) {
			$this->storage->save($this->getCurrentRequestId(), $this->data);
		}

		return $this->data;
	}

	/**
	 * Injects the web debug toolbar into the given Response.
	 * Based on https://github.com/symfony/WebProfilerBundle/blob/master/EventListener/WebDebugToolbarListener.php
	 *
	 * @param Response $response
	 */
	public function injectDebugbar(Response $response)
	{
		$content = $response->getContent();

		$renderer = $this->getJavascriptRenderer();
		if ($this->getStorage()) {
			$openHandlerUrl = $this->di['url']->get( array('for'=>'debugbar.openhandler') );
			$renderer->setOpenHandlerUrl($openHandlerUrl);
		}

		$renderedContent = $renderer->renderHead() . $renderer->render();

		$pos = strripos($content, '</body>');
		if (false !== $pos) {
			$content = substr($content, 0, $pos) . $renderedContent . substr($content, $pos);
		} else {
			$content = $content . $renderedContent;
		}

		$response->setContent($content);
	}


	/**
	 * @return bool
	 */
	protected function isJsonRequest()
	{
		// If XmlHttpRequest, return true
		if ($this->di['request']->isAjax()) {
			return true;
		}

		// Check if the request wants Json
		$acceptable = $this->di['request']->getAcceptableContent();
		return (isset($acceptable[0]) && $acceptable[0]['accept'] == 'application/json');
	}



	/**
	 * Adds a measure
	 *
	 * @param string $label
	 * @param float $start
	 * @param float $end
	 */
	public function addMeasure($label, $start, $end)
	{
		if ($this->hasCollector('time')) {
			/** @var \DebugBar\DataCollector\TimeDataCollector $collector */
			$collector = $this->getCollector('time');
			$collector->addMeasure($label, $start, $end);
		}
	}

	public function addMeasurePoint( $label , $start =null ) {
		if ($this->hasCollector('time')) {
			/** @var \DebugBar\DataCollector\TimeDataCollector $collector */
			$collector = $this->getCollector('time');
			if ( !$start && $measures = $collector->getMeasures() ) {
				$latest = end($measures);
				$start = $latest['end'];
			}elseif (defined('PHALCON_START')){
				$start = PHALCON_START;
			}else{
				$start = isset($_SERVER["REQUEST_TIME_FLOAT"])?$_SERVER["REQUEST_TIME_FLOAT"]:$_SERVER["REQUEST_TIME"];
			}
			$collector->addMeasure($label, $start, microtime(true));
		}
	}

	/**
	 * Utility function to measure the execution of a Closure
	 *
	 * @param string $label
	 * @param \Closure $closure
	 */
	public function measure($label, \Closure $closure)
	{
		if ($this->hasCollector('time')) {
			/** @var \DebugBar\DataCollector\TimeDataCollector $collector */
			$collector = $this->getCollector('time');
			$collector->measure($label, $closure);
		} else {
			$closure();
		}
	}

	/**
	 * Adds a message to the MessagesCollector
	 *
	 * A message can be anything from an object to a string
	 *
	 * @param mixed $message
	 * @param string $label
	 */
	public function addMessage($message, $label = 'info')
	{
		if ($this->hasCollector('messages')) {
			/** @var \DebugBar\DataCollector\MessagesCollector $collector */
			$collector = $this->getCollector('messages');
			$collector->addMessage($message, $label);
		}
	}

	/**
	 * Magic calls for adding messages
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed|void
	 */
	public function __call($method, $args)
	{
		$messageLevels = array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log');
		if (in_array($method, $messageLevels)) {
			foreach($args as $arg) {
				$this->addMessage($arg, $method);
			}
		}
	}

}