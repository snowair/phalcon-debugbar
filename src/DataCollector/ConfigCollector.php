<?php
/**
 * User: zhuyajie
 * Date: 15/3/8
 * Time: 15:45
 */

namespace Snowair\Debugbar\DataCollector;

use DebugBar\DataCollector\ConfigCollector as DefalutConfig;


class ConfigCollector extends DefalutConfig{

	protected $_protect = array();


	public function collect()
	{
		foreach ( $this->_protect as $value ) {
			$keys = explode('.', $value);
			switch(count($keys)){
				case 1:
					$this->data[$value]='******';
					break;
				case 2:
					$this->data[$keys[0]][$keys[1]]='******';
					break;
				case 3:
					$this->data[$keys[0]][$keys[1]][$keys[2]]='******';
					break;
				default:
			}
		}
		$data = array();
		foreach ($this->data as $k => $v) {
			if (!is_string($v)) {
				$v = $this->getDataFormatter()->formatVar($v);
			}
			$data[$k] = $v;
		}
		return $data;
	}

	/**
	 * @param array $protect
	 */
	public function setProtect( $protect ) {
		$this->_protect = $protect;
	}
}