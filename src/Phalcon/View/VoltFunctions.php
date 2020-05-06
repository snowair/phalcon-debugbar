<?php
/**
 * User: zhuyajie
 * Date: 15/3/23
 * Time: 11:31
 */

namespace Snowair\Debugbar\Phalcon\View;

use Phalcon\DI;

class VoltFunctions {

	protected $di;

	public function __construct($di ){
		$this->di = $di;
	}

	public function compileFunction( $name, $arguments, $funcArguments ) {
		$di = $this->di;
		if ( $di->has( 'debugbar' ) ) {
			if ( method_exists( '\PhalconDebug', $name ) )
			{
				return "\\PhalconDebug::$name($arguments)";
			}
		}
	}
}