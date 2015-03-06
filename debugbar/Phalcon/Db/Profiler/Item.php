<?php

/**
 * User: zhuyajie
 * Date: 15/3/6
 * Time: 13:06
 */
namespace Snowair\Debugbar\Phalcon\Db\Profiler;

use Phalcon\Db\Profiler\Item as ProflierItem;

class Item extends ProflierItem {

	protected $extra;

	/**
	 * @return mixed
	 */
	public function getExtra() {
		return $this->extra;
	}

	/**
	 * @param mixed $extra
	 */
	public function setExtra( $extra ) {
		$this->extra = $extra;
	}

	public function __get( $var ) {
		return isset($this->extra[$var])?$this->extra[$var]:null;
	}
}