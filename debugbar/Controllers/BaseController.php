<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 17:19
 */

namespace Snowair\Debugbar\Controllers;

use Phalcon\Mvc\Controller;
use Snowair\Debugbar\PhalconDebugbar;

class BaseController extends Controller{

	/**
	 * @var  PhalconDebugbar $debugbar
	 */
	protected $debugbar;

	public function initialize( PhalconDebugbar $debugbar ){
		$this->debugbar = $debugbar;
	}

}