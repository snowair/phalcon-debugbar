<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 17:23
 */

namespace Snowair\Debugbar\Controllers;

use Phalcon\Http\Response;

class AssetController extends BaseController {

	public function jsAction()
	{
		$renderer = $this->debugbar->getJavascriptRenderer();

		$content = $renderer->dumpAssetsToString('js');

		$response = new Response( $content, 200);
		$response->setHeader( 'Content-Type', 'text/javascript' );

		return $this->cacheResponse($response);
	}

	public function cssAction()
	{
		$renderer = $this->debugbar->getJavascriptRenderer();

		$content = $renderer->dumpAssetsToString('css');

		$response = new Response( $content, 200);
		$response->setHeader( 'Content-Type', 'text/css' );

		return $this->cacheResponse($response);
	}

	/**
	 * Cache the response 1 year (31536000 sec)
	 */
	protected function cacheResponse(Response $response)
	{
		$response->setHeader('Cache-Control', 'public, max-age=31536000, s-maxage=31536000');
		$response->setExpires(new \DateTime('+1 year'));
		return $response;
	}

}