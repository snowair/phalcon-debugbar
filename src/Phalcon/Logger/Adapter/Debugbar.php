<?php
/**
 * User: zhuyajie
 * Date: 15/3/12
 * Time: 22:03
 */

namespace Snowair\Debugbar\Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Formatter\Line;
use Snowair\Debugbar\PhalconDebugbar;

class Debugbar extends Adapter{

	/**
	 * @var PhalconDebugbar $_debugbar
	 */
	protected $_debugbar;

	public function __construct( PhalconDebugbar $debugbar ) {
		$this->_debugbar = $debugbar;
	}

	protected function logInternal( $message, $type, $time, $context ) {
		if ($this->_debugbar->hasCollector('log') && $this->_debugbar->shouldCollect('log') ) {
			$this->_debugbar->getCollector('log')->log($message,$type,$time,$context);
		}
	}

	/**
	 * Returns the internal formatter
	 * @return \Phalcon\Logger\FormatterInterface
	 */
	public function getFormatter() {
		if ( !is_object($this->_formatter) ){
			$this->_formatter = new Line();
		}
		return $this->_formatter;
	}

	/**
	 * Closes the logger
	 * @return boolean
	 */
	public function close() {
		return true;
	}
}