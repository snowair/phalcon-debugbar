<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 17:24
 */

namespace Snowair\Debugbar\Controllers;

use DebugBar\OpenHandler;
use Phalcon\Config;
use Phalcon\Http\Response;
use Phalcon\Http\Request\Exception;
use Snowair\Debugbar\PhalconDebugbar;

/**
 * @property PhalconDebugbar debugbar
 */
class OpenHandlerController extends BaseController {

	public function handleAction()
	{
	    if($this->request->isAjax()){
            $debugbar = $this->debugbar;
            $debugbar->enable()->boot();

            if ( !$this->session->isStarted() ) {
                $this->session->start();
            }

            $openHandler = new OpenHandler($debugbar);

            $data = $openHandler->handle(null, false, false);

            $response = new Response( $data, 200);
            $response->setHeader( 'Content-Type', 'application/json' );
            return $response;
        }else{
            $response = new Response( '<div style="display: none">hidden</div>', 200);
            $response->setContentType('text/html');
            $this->debugbar->enable();
            $this->debugbar->isDebugbarRequest=false;
            $this->di['config.debugbar']->merge(new Config(['inject'=>true]));
            return $response;
        }
	}
}