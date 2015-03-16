<?php
/**
 * User: zhuyajie
 * Date: 15/3/9
 * Time: 17:15
 */

namespace Snowair\Debugbar\DataCollector;

trait Formatter  {

	protected $_customFormatMap = array(
		// classname = > formatterCallable
		// The callable must return an native array!
	);
	/**
	 * @param          $class
	 * @param callable $callable
	 *
	 * @internal param array $customFormatMap
	 */
	public function setCustomFormatMap(  $class, callable $callable ) {
		$this->_customFormatMap[$class] = $callable;
	}

	public function formatVars( $vars ) {
		$formatter = $this->getDataFormatter();
		$exception = false;
		if ( is_object( $vars ) ) {
			$class = get_class($vars);
			$prefix = '['. $class .'] Convertd To : ';
			if ( method_exists( $vars, 'toArray' ) ) {
				@$vars = $prefix. $formatter->formatVar($vars->toArray());
			}else if ( $vars instanceof \StdClass ) {
				@$vars = $prefix. $formatter->formatVar((array)$vars);
			}else if ( $vars instanceof \Traversable ) {
				$result = array();
				foreach ( $vars as $k=>$v ) {
					$result[$k]=$v;
				}
				@$vars = $prefix. $formatter->formatVar($result);
			}else{
				try{
					if ( isset($this->_customFormatMap[$class])
						&& is_callable($callable = $this->_customFormatMap[$class])
					) {
						$array = call_user_func($callable,$vars);
						if ( is_array( $array ) ) {
							@$vars = $formatter->formatVar($array);
						}elseif(is_string($array)){
							$vars = $array;
						}else{
							throw new \Exception('CustomFormatMap callable must return a native Array or String.');
						}
					}else{
						@$vars = $formatter->formatVar($vars);
					}
				}catch (\Exception $e){
					$vars = 'Can not add Instance of [' . get_class($vars) . '] to Debug bar.';
					$exception = true;
				}
			}
		}else{
			if (!is_string($vars)) {
				try{
					@$vars = $formatter->formatVar($vars);
				}catch (\Exception $e){
					$vars = 'Can not add [' . gettype($vars) . '] Variable to Debug bar.';
					$exception = true;
				}
			}
		}
		return array(
			$vars,
			'exception' => $exception,
		);
	}
}