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

            if ( !$this->session->exists() ) {
                $this->session->start();
            }

            $openHandler = new OpenHandler($debugbar);

            $data = $openHandler->handle(null, false, false);

            $response = new Response( $data, 200);
            $response->setHeader( 'Content-Type', 'application/json' );
            return $response;
        }else{
            $response = new Response( ' <!DOCTYPE html> <html> <head> </head> <body> </body> </html> ', 200);
            $config = $this->di['config.debugbar'];
            $config->merge(new Config(['inject'=>true]));
            if($config->open_handler->get('enable',true)){
                $openHandlerUrl = $this->di['url']->get( array('for'=>'debugbar.openhandler') );
                $renderer = $this->debugbar->getJavascriptRenderer();
                $renderer->setOpenHandlerUrl($openHandlerUrl);
                $response->setContentType('text/html');
                $this->debugbar->enable();
                $this->debugbar->isDebugbarRequest=false;
            }
            return $response;
        }
	}
}