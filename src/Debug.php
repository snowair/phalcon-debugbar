<?php
/**
 * User: zhuyajie
 * Date: 15/3/11
 * Time: 20:50
 */

use Phalcon\Di;

class PhalconDebug{

	/**
	 * @var  \Snowair\Debugbar\PhalconDebugbar
	 */
	static public $debugbar;

	/**
	 * GET the debugbar service Instance
	 * @return \Snowair\Debugbar\PhalconDebugbar
	 * @throws \DebugBar\DebugBarException
	 */
	protected static function debugbar(){
		if ( !self::$debugbar ) {
			$di = Di::getDefault();
			if ( $di->has( 'debugbar' ) ) {
				return self::$debugbar=$di->getShared('debugbar');
			}else{
				throw new \DebugBar\DebugBarException('Can not get "debugbar" service from the default DI instance.');
			}
		}
		return self::$debugbar;
	}

	/**
	 * Add a info message to debugbar
	 * @param $message
	 */
	public static function info($message){
		self::debugbar()->info($message);
	}

	/**
	 * Add a warning message to debugbar
	 * @param $message
	 */
	public static function warning($message){
		self::debugbar()->warning($message);
	}

	/**
	 * Add a debug message to debugbar
	 * @param $message
	 */
	public static function debug($message){
		self::debugbar()->debug($message);
	}

	/**
	 * Add a notice message to debugbar
	 * @param $message
	 */
	public static function notice($message){
		self::debugbar()->notice($message);
	}

	/**
	 * Add a notice message to debugbar
	 * @param $message
	 */
	public static function error($message){
		self::debugbar()->error($message);
	}

	/**
	 * Add a alert message to debugbar
	 * @param $message
	 */
	public static function alert($message){
		self::debugbar()->alert($message);
	}

	/**
	 * Add a log message to debugbar
	 * @param $message
	 */
	public static function log($message){
		self::debugbar()->log($message);
	}

	/**
	 * Add a emergency message to debugbar
	 * @param $message
	 */
	public static function emergency($message){
		self::debugbar()->emergency($message);
	}

	/**
	 * Add a critical message to debugbar
	 * @param $message
	 */
	public static function critical($message){
		self::debugbar()->critical($message);
	}

	/**
	 * Add a custom message to debugbar
	 *
	 * @param        $message
	 * @param string $label
	 */
	public static function addMessage($message,$label='info'){
		self::debugbar()->addMessage($message,$label);
	}

	/**
	 * Add a custom message to debugbar only when $condition===true
	 * @param        $message
	 * @param        $condition
	 * @param string $label
	 */
	public static function addMessageIfTrue($message,$condition,$label='info'){
		if ( $condition===true ) {
			self::debugbar()->addMessage($message,$label);
		}
	}

	/**
	 * Add a custom message to debugbar only when $condition===false
	 * @param        $message
	 * @param        $condition
	 * @param string $label
	 */
	public static function addMessageIfFalse($message,$condition,$label='info'){
		if ( $condition===false ) {
			self::debugbar()->addMessage($message,$label);
		}
	}

	/**
	 * Add a custom message to debugbar only when $condition===null
	 * @param        $message
	 * @param        $condition
	 * @param string $label
	 */
	public static function addMessageIfNull($message,$condition,$label='info'){
		if ( $condition===null ) {
			self::debugbar()->addMessage($message,$label);
		}
	}

	/**
	 * Add a custom message to debugbar only when empty($condition)===true
	 * @param        $message
	 * @param        $condition
	 * @param string $label
	 */
	public static function addMessageIfEmpty($message,$condition,$label='info'){
		if ( empty($condition)===true ) {
			self::debugbar()->addMessage($message,$label);
		}
	}

	/**
	 * Add a custom message to debugbar only when empty($condition)===true
	 * @param        $message
	 * @param        $condition
	 * @param string $label
	 */
	public static function addMessageIfNotEmpty($message,$condition,$label='info'){
		if ( empty($condition)===false ) {
			self::debugbar()->addMessage($message,$label);
		}
	}

	/**
	 * Measure time between $stop and $start
	 * @param $label
	 * @param float $start  microseconds timestamp
	 * @param float $stop   microseconds timestamp
	 */
	public static function addMeasure($label,$start,$stop){
		self::debugbar()->addMeasure($label,$start,$stop);
	}

	/**
	 * Measure time between nowtime and previous time measure
	 * @param $label
	 */
	public static function addMeasurePoint($label){
		self::debugbar()->addMeasurePoint($label);
	}

	/**
	 * Add a Exception Instance to Debugbar
	 * @param Exception $e
	 */
	public static function addException(\Exception $e){
		self::debugbar()->addException($e);
	}

	/**
	 * Start a new timeline measure with a given name
	 * @param string $name internal name, use to stop measure
	 * @param null $label
	 */
	public static function startMeasure( $name,$label=null ){
		self::debugbar()->startMeasure($name,$label);
	}

	/**
	 * Stop a measure
	 * @param $name
	 */
	public static function stopMeasure( $name ){
		self::debugbar()->stopMeasure($name);
	}

}
