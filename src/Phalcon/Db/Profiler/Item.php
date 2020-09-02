<?php

/**
 * User: zhuyajie
 * Date: 15/3/6
 * Time: 13:06
 */
namespace Snowair\Debugbar\Phalcon\Db\Profiler;

use Phalcon\Db\Profiler\Item as ProflierItem;

class Item extends ProflierItem {

	protected $_extra = array();
	protected $_realSQL;

	/**
	 * @return mixed
	 */
	public function getExtra() {
		return $this->_extra;
	}

	/**
	 * @param mixed $extra
	 */
	public function setExtra( $extra ) {
		$this->_extra = array_merge($this->_extra,$extra);
	}

	public function __get( $var ) {
		return isset($this->_extra[$var])?$this->_extra[$var]:null;
	}

	/**
	 * @return mixed
	 */
	public function getRealSQL() {
		return $this->_realSQL;
	}

	/**
	 * @param mixed $realSQL
	 */
	public function setRealSQL( $realSQL ) {
		$this->_realSQL = $realSQL;
	}
}