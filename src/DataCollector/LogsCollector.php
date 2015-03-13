<?php
/**
 * User: zhuyajie
 * Date: 15/3/12
 * Time: 22:27
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\MessagesCollector;
use Phalcon\DI;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\Multiple;
use Snowair\Debugbar\Phalcon\Logger\Adapter\Debugbar;
use Snowair\Debugbar\PhalconDebugbar;

class LogsCollector extends MessagesCollector{

	protected $_logs = array();
	protected $_aggregate = false;
	protected $_di;
	/** @var PhalconDebugbar $_debugbar */
	protected $_debugbar;

	public function __construct( DI $di, $aggregate = false ) {
		$this->_di=$di;
		$this->_debugbar = $this->_di['debugbar'];
		if ( $di->has('log') && $log = $di['log'] ) {
			$debugbar_loger = new Debugbar($di['debugbar']);
			if ( $log instanceof Adapter ) {
				$multiple = new Multiple();
				$multiple->push( clone $log );
				$multiple->push( $debugbar_loger );
				/** @var DI\Service $service */
				$service = $di->getService('log');
				$service->setSharedInstance($multiple);
				$service->setDefinition($multiple);
			}elseif($log instanceof Multiple){
				$log->push( $debugbar_loger );
			}
			$this->_aggregate = $this->isAggregate($aggregate);
		}
	}

	public function log( $message, $type, $time, $context ) {
		$debugbar = $this->_di['debugbar'];
		if ( $this->_aggregate ) {
			/** @var MessagesCollector $message_collector */
			$message_collector = $debugbar->getCollector('messages');
			$message_collector->addMessage($message,$type);
		}else{
			$this->_logs[]=array(
				'message'=>$message,
				'type'=>$type,
				'time'=>$time,
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
		$logs = array();
		foreach ( $this->_logs as $log ) {
			$this->getDataFormatter()->formatVar($log);
		}
		return array(
			'messages'=>$logs,
			'count'=>count($logs),
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
			"log" => array(
				'icon' => 'list-alt',
				"widget" => "PhpDebugBar.Widgets.MessagesWidget",
				"map" => "log.messages",
				"default" => "[]"
			),
			"log:badge" => array(
				"map" => "log.count",
				"default" => "null"
			)
		);
	}
}