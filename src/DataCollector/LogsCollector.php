<?php
/**
 * User: zhuyajie
 * Date: 15/3/12
 * Time: 22:27
 */

namespace Snowair\Debugbar\DataCollector;


use Monolog\Logger;
use Phalcon\DI;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Formatter\Syslog;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Multiple;
use Psr\Log\LoggerInterface;
use Snowair\Debugbar\Phalcon\Logger\Adapter\Debugbar;
use Snowair\Debugbar\PhalconDebugbar;

class LogsCollector extends MessagesCollector{

	protected $_logs = array();
	protected $_aggregate = false;
	protected $_di;
	/** @var PhalconDebugbar $_debugbar */
	protected $_debugbar;
	protected $_levelMap = array(
		0=>'EMERGENCY',
		1=>'CRITICAL',
		2=>'ALERT',
		3=>'ERROR',
		4=>'WARNING',
		5=>'NOTICE',
		6=>'INFO',
		7=>'DEBUG',
		8=>'CUSTOM',
		9=>'SPECIAL'
	);
	protected $_formatter;

	public function __construct( DI $di, $aggregate = true,$formatter='line' ) {
		$this->_di=$di;
		$this->_debugbar = $this->_di['debugbar'];
		$this->_formatter = strtolower($formatter);
		if ( $di->has('log') && $log = $di->get('log') ) {
			$debugbar_loger = new Debugbar($di['debugbar']);
			if ( $log instanceof Adapter ) {
                $di->remove('log');
				$multiple = new Multiple();
				$multiple->push( clone $log );
				$multiple->push( $debugbar_loger );
				/** @var DI\Service $service */
				$di->set('log',$multiple);
			}elseif($log instanceof Multiple){
				$log->push( $debugbar_loger );
			}elseif( class_exists('Monolog\Logger') && $log instanceof Logger ){
				$handler = new \Snowair\Debugbar\Monolog\Handler\Debugbar($this->_debugbar);
				$log->pushHandler($handler);
			}

			$this->_aggregate = $this->isAggregate($aggregate);
		}
	}

	public function add( $message, $type, $time, $context ) {
		$debugbar = $this->_di['debugbar'];
		if ( is_scalar($message) && $this->_formatter=='syslog' && $formatter = new Syslog ) {
			$message = $formatter->format($message,$type,$time,$context);
			$message = $message[1];
		}elseif( is_scalar($message) && $this->_formatter=='line' && $formatter = new Line){
			$message = $formatter->format($message,$type,$time,$context);
		}elseif( class_exists($this->_formatter) ){
			$formatter = new $this->_formatter;
			if ($this->_formatter instanceof FormatterInterface) {
  			    $message = $formatter->format($message,$type,$time,$context);
			}
		}
		if ( $this->_aggregate ) {
			/** @var MessagesCollector $message_collector */
			$message_collector = $debugbar->getCollector('messages');
			$message_collector->addMessage($message,$this->_levelMap[$type],true,$time);
		}else{
			$this->_logs[]=array(
				'message'  =>$message,
				'label'    =>$this->_levelMap[$type],
				'time'     =>$time,
				'is_string'=>is_string($message),
				'context'=>$context,
			);
		}
	}

	protected function isAggregate ( $aggregate ){
		if ( $aggregate && $this->_debugbar->hasCollector('messages') && $this->_debugbar->shouldCollect('messages') ) {
			return true;
		}
		return false;
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 * @return array Collected data
	 */
	function collect() {
		foreach ( $this->_logs as &$log ) {
			if (!is_string($log['message'])) {
				$formated = $this->formatVars($log['message']);
				if ( $formated['exception'] ) {
					$log['label'] = '[ERROR]';
				}
				$log['message'] = $formated[0];
			}
		}
		return array(
			'messages'=>$this->_logs,
			'count'=>count($this->_logs),
		);
	}

	/**
	 * Returns the unique name of the collector
	 * @return string
	 */
	function getName() {
		return 'log';
	}

	public function getWidgets()
	{
		if ( $this->_aggregate ) {
			return array();
		}
		return array(
			"logs" => array(
				'icon' => 'pencil',
				"widget" => "PhpDebugBar.Widgets.MessagesWidget",
				"map" => "log.messages",
				"default" => "[]"
			),
			"logs:badge" => array(
				"map" => "log.count",
				"default" => "null"
			)
		);
	}
}
