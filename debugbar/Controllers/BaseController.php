<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 17:19
 */

use Phalcon\Mvc\Controller;
use Snowair\PhalconDebugbar;

class BaseController extends Controller{

	/**
	 * @var  PhalconDebugbar $debugbar
	 */
	protected $debugbar;

	public function initilazie( PhalconDebugbar $debugbar ){
		$this->debugbar = $debugbar;
	}

}