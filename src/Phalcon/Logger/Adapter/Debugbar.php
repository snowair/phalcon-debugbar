<?php
/**
 * User: zhuyajie
 * Date: 15/3/12
 * Time: 22:03
 */

namespace Snowair\Debugbar\Phalcon\Logger\Adapter;

use Phalcon\Version;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\Formatter\Line;
use Snowair\Debugbar\PhalconDebugbar;

class Debugbar extends Adapter implements AdapterInterface{

	/**
	 * @var PhalconDebugbar $_debugbar
	 */
	protected $_debugbar;

	public function __construct( PhalconDebugbar $debugbar ) {
		$this->_debugbar = $debugbar;
	}

	protected function logInternal( $message, $type, $time, $context ) {
		if ($this->_debugbar->hasCollector('log') && $this->_debugbar->shouldCollect('log') ) {
			// Phalcon\Logger\Adapter::log方法调用logInternal时传入的时间精确到秒,精确度太低,因此此处提高精确度
			$this->_debugbar->getCollector('log')->add($message,$type,microtime(true),$context);
		}
	}

    public function log($type, $message = NULL, array $context = NULL){
        if (Version::getId()<'2000000') {
            $this->logInternal($type,$message,microtime(true),$context);
        }else{
            $this->logInternal($message,$type,microtime(true),$context);
        }
        return $this;
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