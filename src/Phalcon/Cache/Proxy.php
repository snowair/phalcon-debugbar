<?php
/**
 * User: zhuyajie
 * Date: 15/3/14
 * Time: 20:24
 */
namespace Snowair\Debugbar\Phalcon\Cache;

use Phalcon\Cache\Exception;
use Phalcon\Cache\Frontend\Base64;

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
			$frontend = $this->_backend->getFrontend();
			if ( is_object($frontend) && $frontend instanceof Base64 ) {
				if ( $name=='save' ) {
					$parameters[1] = '[BINARY DATA]';
				}
				if ( $name=='get' ) {
					$value =  '[BINARY DATA]';
				}
			}
			if ( in_array( strtolower( $name ), array('save','increment','decrement','get','delete','flush') ) ) {
				call_user_func_array(array($this->_collector,$name),$parameters);
			}
			return $value;
		}
		throw new Exception("Method '{$name}' not found on ".get_class($this->_backend) );
	}

}