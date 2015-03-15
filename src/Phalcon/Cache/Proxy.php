<?php
/**
 * User: zhuyajie
 * Date: 15/3/14
 * Time: 20:24
 */
namespace Snowair\Debugbar\Phalcon\Cache;

class Proxy {

	protected $_collector;
	protected $_backend;

	public function __construct($backend,$collector ) {
		$this->_collector = $collector;
		$this->_backend = $backend;
	}

	public function __call( $name, $parameters ){
		if ( is_callable(array($this->_backend,$name) ) ) {
			$value = call_user_func_array(array($this->_backend,$name),$parameters);
			$parameters[] = $value;
			if ( in_array( strtolower( $name ), array('save','increment','decrement','get','delete','flush') ) ) {
				call_user_func_array(array($this->_collector,$name),$parameters);
			}
			return $value;
		}
		return false;
	}

}