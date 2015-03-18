<?php
/**
 * User: zhuyajie
 * Date: 15/3/14
 * Time: 20:24
 */
namespace Snowair\Debugbar\Phalcon\Cache;

use Phalcon\Cache\Exception;
use Phalcon\Cache\Frontend\Base64;
use Phalcon\Cache\Frontend\Output;

trait ProxyTrait {

	protected $_collector;
	protected $_backend;

	public function __construct($backend,$collector ) {
		$this->_collector = $collector;
		$this->_backend = $backend;
	}

	public function __call( $name, $parameters ){
		return $this->call($name,$parameters);
	}

	protected function call() {
		$parameters = func_get_args();
		$name       = $parameters[0];
		$parameters = isset($parameters[1])?$parameters[1]:array();
		if ( is_callable(array($this->_backend,$name) ) ) {
			$value = call_user_func_array(array($this->_backend,$name),$parameters);
			$frontend = $this->_backend->getFrontend();
			if ( is_object($frontend) && $frontend instanceof Base64 ) {
				if ( $name=='save' ) {
					$parameters[1] = '[BINARY DATA]';
				}
				if ( $name=='get' ) {
					$returned =  '[BINARY DATA]';
				}
			}
			if ( $name=='save' && is_object($parameters[1]) ) {
				$parameters[1] = 'Object Of : '. get_class($parameters[1]);
			}
			if ( $name=='get' && is_object($value) ) {
				$returned = 'Object Of : '. get_class($value);
			}
			$parameters[] = isset($returned)?$returned:$value;
			if ( in_array( strtolower( $name ), array('save','increment','decrement','get','delete','flush') ) ) {
				call_user_func_array(array($this->_collector,$name),$parameters);
			}
			return $value;
		}
		throw new Exception("Method '{$name}' not found on ".get_class($this->_backend) );
	}

	public function get($keyName, $lifetime=null){
		return $this->call('get', array($keyName, $lifetime));
	}

	public function start( $keyName, $lifetime = null ) {
		static $reflector;
		if ( ! $this->_backend->getFrontend() instanceof Output ) {
			return null;
		}
		if ( !$reflector ) {
			$reflector = new \ReflectionObject($this->_backend);
		}
		$existingCache = $this->get($keyName, $lifetime);
		if( $existingCache === null) {
			$fresh = true;
			$this->_backend->getFrontend()->start();
		} else {
			$fresh = false;
		}

		$_fresh = $reflector->getProperty('_fresh');
		$_fresh->setAccessible(true);
		$_fresh->setValue($this->_backend,$fresh);

		$_started = $reflector->getProperty('_started');
		$_started->setAccessible(true);
		$_started->setValue($this->_backend,true);

		/**
		 * Update the last lifetime to be used in save()
		 */
		if ( $lifetime !== null ) {
			$_lastLifetime = $reflector->getProperty('_lastLifetime');
			$_lastLifetime->setAccessible(true);
			$_lastLifetime->setValue($this->_backend,$lifetime);
		}

		return $existingCache;
	}

	public function stop( $stopBuffer = true ) {
		return $this->_backend->stop($stopBuffer);
	}


	public function save($keyName=null, $content=null, $lifetime=null, $stopBuffer=true)
	{
		if ( $keyName===null ) {
			$keyName=$this->getLastKey();
		}
		if ( $content===null ) {
			if ( !$this->_backend->getFrontend() instanceof Output ) {
				return null;
			}else{
				$content = $this->_backend->getFrontend()->getContent();
			}
		}
		return $this->call('save', array($keyName, $content, $lifetime, $stopBuffer));
	}

	public function delete($keyName)
	{
		return $this->call('delete',array($keyName));
	}

	public function increment($key_name=null, $value=null)
	{
		return $this->call('increment',array($key_name, $value));
	}

	public function decrement($key_name=null, $value=null)
	{
		return $this->call('decrement',array($key_name, $value));
	}

	public function flush()
	{
		return $this->call('flush');
	}

	public function queryKeys($prefix=null) {
		return $this->_backend->queryKeys($prefix);
	}

	public function exists($keyName=null,$lifetime=null) {
		return $this->_backend->exists($keyName,$lifetime);
	}

	public function getKey( $key ) {
		return $this->_backend->getKey($key);
	}

	public function useSafeKey( $useSafeKey ) {
		return $this->_backend->useSafeKey($useSafeKey);
	}

	public function _connect() {
		$this->_backend->_connect();
	}

	public function _getCollection() {
		return $this->_backend->_getCollection();
	}

	public function gc() {
		return $this->_backend->gc();
	}

	public function getLastKey() {
		return $this->_backend->getLastKey();
	}

	public function setLastKey($lastKey) {
		return $this->_backend->setLastKey($lastKey);
	}

	public function isStarted() {
		return $this->_backend->isStarted();
	}

	public function isFresh() {
		return $this->_backend->isFresh();
	}

	public function getOptions() {
		return $this->_backend->getOptions();
	}

	public function getFrontend() {
		return $this->_backend->getFrontend();
	}

	public function getLifetime() {
		return $this->_backend->getLifetime();
	}

}