<?php
/**
 * User: zhuyajie
 * Date: 15/3/9
 * Time: 17:15
 */

namespace Snowair\Debugbar\DataCollector;

use Phalcon\Db\Result\Pdo;
use Phalcon\Forms\Element;
use Phalcon\Forms\Form;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Complex;
use Phalcon\Validation\Message;
use Phalcon\Validation\Message\Group;

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

	protected function _preformat( $var ) {
		if ( is_object( $var )  )
		{
			if ($var instanceof Complex ) {
				$clone = clone $var;
				$clone->setHydrateMode(Complex::HYDRATE_ARRAYS);
				return $clone;
			}
			if ($var instanceof Pdo || $var instanceof \PDOStatement) {
				$clone = clone $var;
				$clone->setFetchMode(\Pdo::FETCH_ASSOC);
				return $clone->fetchAll();
			}
			if ( $var instanceof Form ) {
				$form = [ ];
				$form['#_entity']   = $var->getEntity();
				$form['#_options']  = $var->getUserOptions();
				$form['#_action']   = $var->getAction();
				$form['#_messages'] = array_filter($this->_getMessages($var->getMessages()));
				$form['#_elements'] = [];
				foreach ( $var as $element ) {
					$form['#_elements'][] = array_filter($this->_getElement($element));
				}
				return array_filter($form);
			}
			if ( $var instanceof Element ) {
				return array_filter( $this->_getElement($var) );
			}
			if ( $var instanceof Message ) {
				return $this->_getMessage( $var );
			}
			if ( $var instanceof Group ) {
				return $this->_getMessages( $var );
			}
		}
		return $var;
	}

	protected function _getElement( $element ) {
		return array(
			'#_name'       => $element->getName(),
			'#_value'	   => $element->getValue(),
			'#_label'      => $element->getLabel(),
			'#_options'    => $element->getUserOptions(),
			'#_messages'   => array_filter($this->_getMessages( $element->getMessages() )),
			'#_attributes' => $element->getAttributes(),
			'#_validators' => $this->_getValidatoros($element),
			'#_filters'    => $this->_getFilters($element),
		);
	}

	protected function _getMessages( $messages ) {
		$array =[];
		if ( $messages instanceof Group ) {
			foreach ( $messages as $m ) {
				$array[] = array_filter($this->_getMessage($m));
			}
		}
		return $array;
	}

	protected function _getMessage( $message ) {
		$array = [];
		$array[] = [
			'#_field'  =>$message->getField(),
			'#_message'=>$message->getMessage(),
			'#_type'   =>$message->getType(),
		];
		return $array;
	}

	protected function _getValidatoros( $element ) {
		$names = [];
		foreach ( (array)($element->getValidators()) as $validator) {
			$class = get_class($validator);
			$reflector = new \ReflectionProperty($class,'_options');
			$reflector->setAccessible(true);
			$names[]= [
				'InstanceOf' => $class,
				'#_options' => $reflector->getValue($validator),
			];
		}
		return array_filter($names);
	}

	protected function _getFilters( $element ) {
		$names = [];
		foreach ( (array) $element->getFilters() as $filter) {
			if ( is_object( $filter ) ) {
				$names[] = get_class($filter);
			}
			if ( is_string( $filter ) ) {
				$names[] = $filter;
			}
		}
		return array_filter($names);
	}

	public function formatVars( $vars ) {
		$formatter = $this->getDataFormatter();
		$exception = false;
		$vars = $this->_preformat($vars);
		if ( is_object( $vars ) ) {
			$class = get_class($vars);
			$prefix = '['. $class .'] Converted To: ';
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
