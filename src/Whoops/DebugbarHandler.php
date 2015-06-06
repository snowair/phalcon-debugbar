<?php
/**
 * User: zhuyajie
 * Date: 15-6-6
 * Time: 下午10:44
 */

namespace Snowair\Debugbar\Whoops;


use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Util\Misc;
use Whoops\Util\TemplateHelper;

class DebugbarHandler extends PrettyPageHandler {

    protected $customCss = null;
    protected $di = null;

    public function setDi( $di )
    {
        $this->di = $di;
        return $this;
    }

    /**
     * @return int|null
     */
    public function handle()
    {
        if (!$this->handleUnconditionally()) {
            // Check conditions for outputting HTML:
            // @todo: Make this more robust
            if (php_sapi_name() === 'cli') {
                // Help users who have been relying on an internal test value
                // fix their code to the proper method
                if (isset($_ENV['whoops-test'])) {
                    throw new \Exception(
                        'Use handleUnconditionally instead of whoops-test'
                        .' environment variable'
                    );
                }

                return Handler::DONE;
            }
        }

        $exception = func_get_arg(0);

        // @todo: Make this more dynamic
        $helper = new TemplateHelper();

        $templateFile = $this->getResource("views/layout.html.php");
        $cssFile      = $this->getResource("css/whoops.base.css");
        $zeptoFile    = $this->getResource("js/zepto.min.js");
        $jsFile       = $this->getResource("js/whoops.base.js");

        if ($this->customCss) {
            $customCssFile = $this->getResource($this->customCss);
        }

        $inspector = $this->getInspector();
        $frames    = $inspector->getFrames();

        $code = $inspector->getException()->getCode();

        if ($inspector->getException() instanceof \ErrorException) {
            // ErrorExceptions wrap the php-error types within the "severity" property
            $code = Misc::translateErrorCode($inspector->getException()->getSeverity());
        }

        // List of variables that will be passed to the layout template.
        $vars = array(
            "page_title" => $this->getPageTitle(),

            // @todo: Asset compiler
            "stylesheet" => file_get_contents($cssFile),
            "zepto"      => file_get_contents($zeptoFile),
            "javascript" => file_get_contents($jsFile),

            // Template paths:
            "header"      => $this->getResource("views/header.html.php"),
            "frame_list"  => $this->getResource("views/frame_list.html.php"),
            "frame_code"  => $this->getResource("views/frame_code.html.php"),
            "env_details" => $this->getResource("views/env_details.html.php"),

            "title"          => $this->getPageTitle(),
            "name"           => explode("\\", $inspector->getExceptionName()),
            "message"        => $inspector->getException()->getMessage(),
            "code"           => $code,
            "plain_exception" => Formatter::formatExceptionPlain($inspector),
            "frames"         => $frames,
            "has_frames"     => !!count($frames),
            "handler"        => $this,
            "handlers"       => $this->getRun()->getHandlers(),

            "tables"      => array(
                "Server/Request Data"   => $_SERVER,
                "GET Data"              => $_GET,
                "POST Data"             => $_POST,
                "Files"                 => $_FILES,
                "Cookies"               => $_COOKIE,
                "Session"               => isset($_SESSION) ? $_SESSION :  array(),
                "Environment Variables" => $_ENV,
            ),
        );

        if (isset($customCssFile)) {
            $vars["stylesheet"] .= file_get_contents($customCssFile);
        }

        // Add extra entries list of data tables:
        // @todo: Consolidate addDataTable and addDataTableCallback
        $extraTables = array_map(function ($table) {
            return $table instanceof \Closure ? $table() : $table;
        }, $this->getDataTables());
        $vars["tables"] = array_merge($extraTables, $vars["tables"]);

        $helper->setVariables($vars);
        ob_start();
        $helper->render($templateFile);
        $content = ob_get_clean();
        $pathMatches = (bool) preg_match('/phalcon-debugbar/', $exception->getFile());
        if ( is_object( $this->di ) && !$pathMatches ) {
            try{
                $response = $this->di['response']->setContent($content);
                $response = $this->di['debugbar']->modifyResponse($response);
                $content = $response->getContent();
            }catch (\Exception $e){

            }
        }
        echo $content;
        return Handler::QUIT;
    }

    public function addCustomCss($name)
    {
        $this->customCss = $name;
    }
}