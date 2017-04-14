<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 13:29
 */

namespace Snowair\Debugbar;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;

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
        $html .= sprintf(
            '<link rel="stylesheet" type="text/css" href="%s?%s">' . "\n",
            $this->url->get(array('for'=>'debugbar.assets.css')),$time
        );
        $html .= sprintf(
            '<script type="text/javascript" src="%s?%s"></script>' . "\n",
            $this->url->get(array('for'=>'debugbar.assets.js')),$time
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
