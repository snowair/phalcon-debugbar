<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 11:25
 */

namespace Snowair\Debugbar;

use DebugBar\Bridge\DoctrineCollector;
use DebugBar\Bridge\SwiftMailer\SwiftLogCollector;
use DebugBar\Bridge\SwiftMailer\SwiftMailCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use Exception;
use Phalcon\Cache\Backend;
use Phalcon\Cache\Multiple;
use Phalcon\Db\Adapter;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\DI;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View\Simple;
use Phalcon\Registry;
use Phalcon\Version;
use Snowair\Debugbar\DataCollector\CacheCollector;
use Snowair\Debugbar\DataCollector\ConfigCollector;
use Snowair\Debugbar\DataCollector\LogsCollector;
use Snowair\Debugbar\DataCollector\MessagesCollector;
use Snowair\Debugbar\DataCollector\PhalconRequestCollector;
use Snowair\Debugbar\DataCollector\QueryCollector;
use Snowair\Debugbar\DataCollector\RouteCollector;
use Snowair\Debugbar\DataCollector\SessionCollector;
use Snowair\Debugbar\DataCollector\ViewCollector;
use Snowair\Debugbar\Phalcon\Db\Profiler;
use Snowair\Debugbar\Phalcon\View\VoltFunctions;
use Snowair\Debugbar\Storage\ElasticSearch;
use Snowair\Debugbar\Storage\Filesystem;
use Snowair\Debugbar\Storage\MongoDB;

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
    public    $isDebugbarRequest=false;

    public function __construct($di)
    {
        $this->di = $di;
        $this->config = $di['config.debugbar'];
    }

    /**
     * Manual boot debugbar:
     * $this->>di['debugbar']->enable()->boot();
     * @return $this
     */
    public function enable()
    {
        $this->config->enabled=true;
        return $this;
    }

    public function disable()
    {
        $this->config->enabled=false;
    }

    /**
     * 检查是否启用了debugbar
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->config->enabled;
    }

    public function shouldCollect($name, $default = false)
    {
        return $this->config->collectors->get($name,$default);
    }

    public function initCollectors(  )
    {
        // only for normal request
        if ($this->shouldCollect('phpinfo', true)) {
            $php = new PhpInfoCollector();
            if (!isset($this->collectors[$php->getName()])) {
                $this->addCollector($php);
            }
        }
        if ($this->shouldCollect('messages', true)) {
            $msg = new MessagesCollector();
            if (!isset($this->collectors[$msg->getName()])) {
                $this->addCollector($msg);
            }
        }
        if ($this->shouldCollect('memory', true)) {
            $mem=new MemoryCollector();
            if (!isset($this->collectors[$mem->getName()])) {
                $this->addCollector($mem);
            }
        }
        if ($this->shouldCollect('default_request', false)) {
            $req = new RequestDataCollector();
            if (!isset($this->collectors[$req->getName()])) {
                $this->addCollector($req);
            }
        }
        if ($this->shouldCollect('exceptions', true)) {
            try {
                $exceptionCollector = new ExceptionsCollector();
                $exceptionCollector->setChainExceptions(
                    $this->config->options->exceptions->get('chain', true)
                );
                if (!isset($this->collectors[$exceptionCollector->getName()])) {
                    $this->addCollector($exceptionCollector);
                }
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
            $timer = new TimeDataCollector($startTime);
            if (!isset($this->collectors[$timer->getName()])) {
                $this->addCollector($timer);
            }
        }

        if ($this->shouldCollect('route') && $this->di->has('router') && $this->di->has('dispatcher')) {
            try {
                $routeCollector = new RouteCollector($this->di);
                if (!isset($this->collectors[$routeCollector->getName()])) {
                    $this->addCollector($routeCollector);
                }
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
        if ($this->shouldCollect('log', false) && $this->di->has('log')) {
            $logs = new LogsCollector($this->di,
                $this->config->options->log->get('aggregate',false),
                $this->config->options->log->get('formatter','line')
            );
            if (!isset($this->collectors[$logs->getName()])) {
                $this->addCollector($logs);
            }
        }

        $this->attachServices();

    }

    /**
     * 启动debugbar: 设置collector
     */
    public function boot() {

        if (!$this->isEnabled() ) {
            return;
        }

        $debugbar = $this;
        if ( !$this->isDataPersisted() ) {
            $this->selectStorage($debugbar); // for normal request and debugbar request both
        }
        if ($this->booted) {
            return;
        }
        $this->booted = true;
        if ( isset($_REQUEST['_url']) and $_REQUEST['_url']=='/favicon.ico'
            || isset($_SERVER['REQUEST_URI']) and $_SERVER['REQUEST_URI']=='/favicon.ico'|| !$this->isEnabled()) {
            return;
        }

        $this->initCollectors();

        $renderer = $this->getJavascriptRenderer();
        $renderer->setIncludeVendors($this->config->get('include_vendors', true));
        $renderer->setBindAjaxHandlerToXHR($this->config->get('capture_ajax', true));
        $renderer->setAjaxHandlerAutoShow($this->config->get('ajax_handler_auto_show', true));
    }

    public function attachServices() {

        if (!$this->isEnabled() ) {
            return;
        }

        $services = array_keys($this->di->getServices());
        foreach ( $services as $name ) {
            if ( stripos( $name, 'cache' )!==false ) {
                $this->attachCache( $name );
            }
            if ( stripos($name,'db')===0 || strtolower(substr($name,-2)) =='db' ) {
                $this->attachDb( $name );
            }
        }
        if ( $this->di->has( 'view' ) ) {
            $this->attachView( $this->di['view'] );
        }
        if ($this->shouldCollect('mail', true) && $this->di->has('mailer') ) {
            $this->attachMailer( $this->di['mailer'] );
        }
        if ($this->shouldCollect('doctrine', false) &&!$this->hasCollector('doctrine') && !$this->hasCollector('pdo') ) {
            $debugStack = new \Doctrine\DBAL\Logging\DebugStack();
            $entityManager = $this->di['entityManager'];
            $entityManager->getConnection()->getConfiguration()->setSQLLogger($debugStack);
            $doctrine = new DoctrineCollector($debugStack);
            if (!isset($this->collectors[$doctrine->getName()])) {
                $this->addCollector($doctrine);
            }
        }
    }

    public function attachCache($cacheService) {
        static $mode,$collector,$hasAttachd = array();
        if ( in_array( $cacheService, $hasAttachd ) ) {
            return;
        }
        $hasAttachd[] = $cacheService;
        if ( !$this->shouldCollect( 'cache',false ) ) {
            return;
        }
        if ( !is_string( $cacheService ) ) {
            throw new \Exception('The parameter must be a cache service name.');
        }
        if ( !$mode ) {
            $mode  = $this->config->options->cache->get('mode',0);
        }
        if ( !$collector ) {
            $mc = null;
            if ( $this->hasCollector( 'messages' ) ) {
                $mc = $this->getCollector('messages');
            }
            $collector = new CacheCollector($mode,$mc);
            $this->addCollector($collector);
        }
        $backend = $this->di->get($cacheService);
        if ( $backend instanceof Multiple || $backend instanceof Backend ) {
            if ($this->shouldCollect('cache',false)) {
                $this->di->remove($cacheService);
                $self = $this;
                $this->di->set($cacheService, function()use($self,$backend,$collector){
                    return $self->createProxy(clone $backend,$collector);
                });
            }
        }
    }

    protected function createProxy( $backend,$collector ) {
        $base_class = get_class($backend);
        $prefix = ltrim(strrchr($base_class,'\\'),'\\');
        $namespace = __NAMESPACE__ .'\\Phalcon\\Cache';
        $classname = $prefix.'Proxy';
        $full_class = $namespace.'\\'.$classname;
        if (!class_exists($full_class)) {
            $class =<<<"class"
namespace $namespace;

class $classname extends \\$base_class
{
	use ProxyTrait;

	public function __construct(\$backend,\$collector ) {
		\$this->_collector = \$collector;
		\$this->_backend = \$backend;
	}
}
class;
            eval($class);
        }
        return new $full_class($backend,$collector);
    }

    public function attachMailer( $mailer ) {
        if (!$this->shouldCollect('mail', false)  ) {
            return;
        }
        static $started;
        if ( !$started ) {
            $started = true;
            if ( is_string( $mailer ) ) {
                $mailer = $this->di[$mailer];
            }
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

    /**
     * @param $view
     *
     * @throws \DebugBar\DebugBarException
     */
    public function attachView( $view )
    {
        // You can add only One View instance
        if ( isset($this->collectors['views']) ) {
            return;
        }
        if ( is_string( $view ) ) {
            $view = $this->di[$view];
        }

        // try to add PhalconDebugbar VoltFunctions
        $engins =$view->getRegisteredEngines();
        if ( isset($engins['.volt']) ) {
            $volt = $engins['.volt'];
            if ( is_object( $volt ) ) {
                if ( $volt instanceof \Closure ) {
                    $volt = $volt($view,$this->di);
                }
            }elseif(is_string($volt)){
                if ( class_exists( $volt ) ) {
                    $volt = new Volt( $view, $this->di );
                }elseif( $this->di->has($volt)){
                    $volt = $this->di->getShared($volt,array($view,$this->di));
                }
            }
            $engins['.volt'] = $volt;
            $view->registerEngines($engins);
            $volt->getCompiler()->addExtension(new VoltFunctions($this->di));
        }

        // attach the ViewCollector
        if ( !$this->shouldCollect('view', true) ) {
            return;
        }

        $viewProfiler = new Registry();
        $viewProfiler->templates=array();
        $viewProfiler->engines = $view->getRegisteredEngines();
        $config = $this->config;

        $eventsManager = $view->getEventsManager();
        if ( !is_object( $eventsManager ) ) {
            $eventsManager = new Manager();
        }

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
        $eventsManager->attach('view:beforeRenderView',function($event,$view) use($viewProfiler)
        {
            $viewFilePath = $view->getActiveRenderPath();
            if (Version::getId()>=2000140) {
                if ( !$view instanceof \Phalcon\Mvc\ViewInterface && $view instanceof \Phalcon\Mvc\ViewBaseInterface) {
                    $viewFilePath = realpath($view->getViewsDir()).DIRECTORY_SEPARATOR.$viewFilePath;
                }
            }elseif( $view instanceof Simple){
                $viewFilePath = realpath($view->getViewsDir()).DIRECTORY_SEPARATOR.$viewFilePath;
            }

            $templates = $viewProfiler->templates;
            $templates[$viewFilePath]['startTime'] = microtime(true);
            $viewProfiler->templates =  $templates;
        });
        $eventsManager->attach('view:afterRenderView',function($event,$view) use($viewProfiler)
        {
            $viewFilePath = $view->getActiveRenderPath();
            if (Version::getId()>=2000140) {
                if ( !$view instanceof \Phalcon\Mvc\ViewInterface && $view instanceof \Phalcon\Mvc\ViewBaseInterface) {
                    $viewFilePath = realpath($view->getViewsDir()).DIRECTORY_SEPARATOR.$viewFilePath;
                }
            }elseif( $view instanceof Simple){
                $viewFilePath = realpath($view->getViewsDir()).DIRECTORY_SEPARATOR.$viewFilePath;
            }

            $templates = $viewProfiler->templates;
            $templates[$viewFilePath]['stopTime'] = microtime(true);
            $viewProfiler->templates =  $templates;

        });
        $view->setEventsManager($eventsManager);

        $collector = new ViewCollector($viewProfiler,$view);
        $this->addCollector($collector);
    }

    /**
     * @param DebugBar $debugbar
     */
    public function selectStorage(DebugBar $debugbar)
    {
        $config = $this->config;
        if ($config->storage->enabled) {
            $driver = $config->storage->get('driver','file');
            if ($driver=='mongodb') {
                $connection = $config->storage->mongodb->connection;
                $db         = $config->storage->mongodb->db;
                $collection = $config->storage->mongodb->collection;
                $options    = $config->storage->mongodb->options;
                $dirveropts = $config->storage->mongodb->driver_options;
                $storage    = new MongoDB( $this->di, $connection, $db, $collection, $options,$dirveropts );
            }elseif ($driver=='elastic') {
                $storage    = new ElasticSearch( $this->di,$config->storage->elastic );
            } else {
                $path    = $config->storage->path;
                $storage = new Filesystem( $path, $this->di );
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
     * @param Exception|\Throwable $e
     */
    public function addException($e)
    {
        $this->addThrowable($e);
    }

    /**
     * Adds an exception to be profiled in the debug bar
     *
     * @param Exception $e
     */
    public function addThrowable($e)
    {
        if ($this->hasCollector('exceptions')) {
            /** @var \DebugBar\DataCollector\ExceptionsCollector $collector */
            $collector = $this->getCollector('exceptions');
            $collector->addThrowable($e);
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
        $config  = $this->config;

        if (!$this->isEnabled() ) {
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
        }

        if ( $this->isDebugbarRequest ) {
            // Notice: All Collectors must be added before check if is debugbar request.
            return $response;
        }

        try {
            if ($this->isRedirection($response)) {
                $this->stackData();
            }
            elseif ( $this->isJsonRequest() && $config->get('capture_ajax', true) )
            {
                $this->sendDataInHeaders(true);
            } elseif (
                ($content_type = $response->getHeaders()->get('Content-Type'))
                &&
                strpos($response->getHeaders()->get('Content-Type'), 'html') !== false
                && $config->get('inject', true)
            ) {
                $response->setHeader('Phalcon-Debugbar','on');
                $this->injectDebugbar($response);
            } elseif (
                ($content_type = $response->getHeaders()->get('Content-Type'))
                &&
                strpos($response->getHeaders()->get('Content-Type'), 'html') === false
            ) {
                $this->collect();
            } elseif($config->get('inject', true)) {
                $response->setHeader('Phalcon-Debugbar','on');
                $this->injectDebugbar($response);
            }else{
                $this->collect();
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
            if ( is_string( $db ) ) {
                $db = $this->di[$db];
            }
            $pdo = $db->getInternalHandler();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, $config->options->db->error_mode);
            if ( !$eventsManager ) {

                $eventsManager = $db->getEventsManager();
                if ( !is_object( $eventsManager ) ) {
                    $eventsManager = new Manager();
                }
                $eventsManager->attach('db', function(Event $event, Adapter $db, $params)  use (
                    $profiler,$queryCollector
                ) {
                    $profiler->setDb($db);
                    if ($event->getType() == 'beforeQuery') {
                        $sql = $db->getRealSQLStatement();
                        $bindTypes = $db->getSQLBindTypes();
                        if ( stripos( strtr($sql,[' '=>'']), 'SELECTIF(COUNT(*)>0,1,0)FROM`INFORMATION_SCHEMA`.`TABLES`' )===false
                            && stripos( $sql, 'DESCRIBE')!==0) {
                            $profiler->startProfile($sql,$params,$bindTypes);
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
                        if ( stripos( strtr($sql,[' '=>'']), 'SELECTIF(COUNT(*)>0,1,0)FROM`INFORMATION_SCHEMA`.`TABLES`' )===false
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
        $this->sortCollectors();
        /** @var Request $request */
        $request = $this->di['request'];

        $this->data = array(
            '__meta' => array(
                'id' => $this->getCurrentRequestId(),
                'datetime' => date('Y-m-d H:i:s'),
                'utime' => microtime(true),
                'method' => $request->getMethod(),
                'uri' => $request->getURI(),
                'ip' => $request->getClientAddress(true)
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

        $openHandlerUrl = $this->di['url']->getStatic( array('for'=>'debugbar.openhandler') );
        $renderer->setOpenHandlerUrl($openHandlerUrl);


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

    public function sortCollectors() {
        // move message collectors to end, so other collectors can add message to it at the end time.
        $this->collectors;
        if ( isset($this->collectors['messages']) ) {
            $m = $this->collectors['messages'];
            $t = $this->collectors['time'];
            unset($this->collectors['messages']);
            unset($this->collectors['time']);
            $this->collectors['time'] = $t;
            $this->collectors['messages'] = $m;
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
                $this->addMessage($arg, strtoupper($method));
            }
        }
    }

}

