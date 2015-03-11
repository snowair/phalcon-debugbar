<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 17:24
 */

namespace Snowair\Debugbar\Controllers;

use DebugBar\OpenHandler;
use Phalcon\Http\Response;
use Phalcon\Http\Request\Exception;

class OpenHandlerController extends BaseController {

	public function handleAction()
	{
		$debugbar = $this->debugbar;

		if (!$debugbar->isEnabled()) {
			throw new Exception('Debugbar is not enabled',500);
		}

		$openHandler = new OpenHandler($debugbar);

		$data = $openHandler->handle(null, false, false);

		$response = new Response( $data, 200);
		$response->setHeader( 'Content-Type', 'application/json' );
		return $response;
	}
}