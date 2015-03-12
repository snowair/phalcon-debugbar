<?php
/**
 * User: zhuyajie
 * Date: 15/3/12
 * Time: 23:10
 */

namespace Snowair\Debugbar\Controllers;


class ToolsController extends BaseController{

	public function phpinfoAction() {
		phpinfo();
	}

}