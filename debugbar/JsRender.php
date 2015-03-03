<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 13:29
 */

namespace Snowair;

use DebugBar\JavascriptRenderer;

class JsRender extends JavascriptRenderer{

	protected $url;

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

		$jsModified = $this->getModifiedTime('js');
		$cssModified = $this->getModifiedTime('css');

		$html = '';
		$html .= sprintf(
			'<link rel="stylesheet" type="text/css" href="%s?%s">' . "\n",
			$this->url->get(array('for'=>'debugbar.assets.css')),
			$cssModified
		);
		$html .= sprintf(
			'<script type="text/javascript" src="%s?%s"></script>' . "\n",
			$this->url->get(array('for'=>'debugbar.assets.js')),
			$jsModified
		);

		if ($this->isJqueryNoConflictEnabled()) {
			$html .= '<script type="text/javascript">jQuery.noConflict(true);</script>' . "\n";
		}

		return $html;
	}

	/**
	 * Get the last modified time of any assets.
	 *
	 * @param string $type 'js' or 'css'
	 * @return int
	 */
	protected function getModifiedTime($type)
	{
		$files = $this->getAssets($type);

		$latest = 0;
		foreach ($files as $file) {
			$mtime = filemtime($file);
			if ($mtime > $latest) {
				$latest = $mtime;
			}
		}
		return $latest;
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