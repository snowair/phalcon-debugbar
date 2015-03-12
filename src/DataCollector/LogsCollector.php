<?php
/**
 * User: zhuyajie
 * Date: 15/3/12
 * Time: 22:27
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\DataCollector;
use Phalcon\DI;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\Multiple;
use Snowair\Debugbar\Phalcon\Logger\Adapter\Debugbar;

class LogsCollector extends DataCollector{

	protected $_logs = array();

	public function __construct( DI $di ) {
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
		}
	}

	public function log( $message, $type, $time, $context ) {
		$this->_logs[]=array(
			'message'=>$message,
			'type'=>$type,
			'time'=>$time,
			'context'=>$context,
		);
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
			'logs'=>$logs,
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
}