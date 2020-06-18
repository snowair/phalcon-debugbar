<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 13:29
 */

namespace Snowair\Debugbar;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use Phalcon\Di;
use Phalcon\Dispatcher;
use Phalcon\Mvc\Application;

class JsRender extends JavascriptRenderer{

	protected $ajaxHandlerBindToJquery = false;
	protected $ajaxHandlerBindToXHR = true;
	protected $url;


	public function __construct(DebugBar $debugBar, $baseUrl = null, $basePath = null)
	{
		parent::__construct($debugBar, $baseUrl, $basePath);

		$this->cssFiles['laravel'] = __DIR__ . '/Resources/laravel-debugbar.css';
		$this->cssVendors['fontawesome'] = __DIR__ . '/Resources/font-awesome/style.css';
	}
	/**
	 * Set the URL Generator
	 */
	public function setUrlGenerator($url)
	{
		$this->url = $url;
	}

    public function renderHead() {
        if (!$this->url) {
            return parent::renderHead();
        }

        $time=time();
        $html = '';
        $di=Di::getDefault();
        /** @var Dispatcher $dispatcher */
        $app = $di['app'];
        if (  $app instanceof Application ) {
            $dispatcher= $di['dispatcher'];
            $m=$dispatcher->getModuleName();
            if(!$m){
                $m=$di['request']->get('m');
            }
            if(!$m){
                $m=$app->getDefaultModule();
            }
        }else{
            $m='';
        }

        $baseuri = rtrim($this->url->getBasePath(),'/').'/';
        $html .= sprintf(
            '<link rel="stylesheet" type="text/css" href="%s?m='.$m.'&%s">' . "\n",
            $baseuri.ltrim($this->url->getStatic(array('for'=>'debugbar.assets.css')),'/'),$time
        );
        $html .= sprintf(
            '<script type="text/javascript" src="%s?m='.$m.'&%s"></script>' . "\n",
            $baseuri.ltrim($this->url->getStatic(array('for'=>'debugbar.assets.js')),'/'),$time
        );

        if ($this->isJqueryNoConflictEnabled()) {
            $html .= '<script type="text/javascript">jQuery.noConflict(true);</script>' . "\n";
        }

        // reset base uri to its default

        return $html;
    }

	/**
	 * Return assets as a string
	 *
	 * @param string $type 'js' or 'css'
	 * @return string
	 */
	public function dumpAssetsToString($type)
	{
		$files = $this->getAssets($type);

		$content = '';
		foreach ($files as $file) {
			$content .= file_get_contents($file) . "\n";
		}

		return $content;
	}
}
