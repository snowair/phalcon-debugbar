<?php
/**
 * User: zhuyajie
 * Date: 15-6-6
 * Time: 下午10:36
 */

namespace Snowair\Debugbar\Whoops;

use Phalcon\DI;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run;

class WhoopsServiceProvider
{
    /**
     * @param DI $di
     */
    public function __construct(DI $di = null)
    {
        if(!class_exists('\\Whoops\\Run')){
            return;
        }

        if (!$di) {
            $di = DI::getDefault();
        }

        // There's only ever going to be one error page...right?
        $di->setShared('whoops.pretty_page_handler', function () use($di) {
            return (new DebugbarHandler())->setDi($di);
        });

        // There's only ever going to be one error page...right?
        $di->setShared('whoops.json_response_handler', function () {
            $jsonHandler = new JsonResponseHandler();
            $jsonHandler->onlyForAjaxRequests(true);
            return $jsonHandler;
        });


        $di->setShared('whoops', function () use ($di) {
            $run = new Run();
            $run->silenceErrorsInPaths(array(
                '/phalcon-debugbar/'
            ),E_ALL);
            $run->pushHandler($di['whoops.pretty_page_handler']);
            $run->pushHandler($di['whoops.json_response_handler']);
            return $run;
        });

        $di['whoops']->register();
    }

}