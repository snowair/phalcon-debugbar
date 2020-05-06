<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 17:23
 */

namespace Snowair\Debugbar\Controllers;

use Phalcon\Http\Response;
use Snowair\Debugbar\PhalconDebugbar;

/**
 * @property PhalconDebugbar debugbar
 */
class AssetController extends BaseController {

    public function jsAction()
    {
        $renderer = $this->debugbar->getJavascriptRenderer();

        $content = $renderer->dumpAssetsToString('js');

        $response = new Response( $content, 200);
        $response->setHeader( 'Content-Type', 'text/javascript' );

        return $response;
    }

    public function cssAction()
    {
        $renderer = $this->debugbar->getJavascriptRenderer();

        $content = $renderer->dumpAssetsToString('css');

        $response = new Response( $content, 200);
        $response->setHeader( 'Content-Type', 'text/css' );

        return $response;
    }

}