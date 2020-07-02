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
	 */
	protected static function debugbar(){
		if ( !self::$debugbar ) {
			$di = Di::getDefault();
			if ( $di->has( 'debugbar' ) ) {
				return self::$debugbar=$di->getShared('debugbar');
			}else{
                return self::$debugbar= new \Snowair\Debugbar\EmptyDebugbar();
			}
		}
		return self::$debugbar;
	}

	/**
	 * Add a info message to debugbar
	 */
	public static function info(){
		call_user_func_array( [self::debugbar(),'info'] ,func_get_args());
	}

	/**
	 * Add a warning message to debugbar
	 */
	public static function warning(){
		call_user_func_array( [self::debugbar(),'warning'] ,func_get_args());
	}

	/**
	 * Add a debug message to debugbar
	 */
	public static function debug(){
		call_user_func_array( [self::debugbar(),'debug'] ,func_get_args());
	}

	/**
	 * Add a notice message to debugbar
	 */
	public static function notice(){
		call_user_func_array( [self::debugbar(),'notice'] ,func_get_args());
	}

	/**
	 * Add a notice message to debugbar
	 */
	public static function error(){
		call_user_func_array( [self::debugbar(),'error'] ,func_get_args());
	}

	/**
	 * Add a alert message to debugbar
	 */
	public static function alert(){
		call_user_func_array( [self::debugbar(),'alert'] ,func_get_args());
	}

	/**
	 * Add a log message to debugbar
	 */
	public static function log(){
		call_user_func_array( [self::debugbar(),'log'] ,func_get_args());
	}

	/**
	 * Add a emergency message to debugbar
	 */
	public static function emergency(){
		call_user_func_array( [self::debugbar(),'emergency'] ,func_get_args());
	}

	/**
	 * Add a critical message to debugbar
	 */
	public static function critical(){
		call_user_func_array( [self::debugbar(),'critical'] ,func_get_args());
	}

	/**
	 * Add a custom message to debugbar
	 *
	 * @param        $message
	 * @param string $label
	 */
	public static function addMessage($message,$label='INFO'){
		self::debugbar()->addMessage($message,$label);
	}

	/**
	 * Add a custom message to debugbar only when $condition===true
	 * @param        $message
	 * @param        $condition
	 * @param string $label
	 */
	public static function addMessageIfTrue($message,$condition,$label='INFO'){
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
	public static function addMessageIfFalse($message,$condition,$label='INFO'){
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
	public static function addMessageIfNull($message,$condition,$label='INFO'){
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
	public static function addMessageIfEmpty($message,$condition,$label='INFO'){
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
	public static function addMessageIfNotEmpty($message,$condition,$label='INFO'){
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

