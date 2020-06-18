<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 18:04
 */

namespace Snowair\Debugbar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;

class SessionCollector extends DataCollector implements DataCollectorInterface, Renderable
{

	protected $session;

	public function __construct($session)
	{
		$this->session = $session;
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 * @return array Collected data
	 */
	function collect() {
        $data = array();
        if ( !empty($_SESSION)  ) {
            $opt = $this->session->getOptions();
            $prefix = 0;
            if (isset($opt['uniqueId'])) {
                $prefix = strlen($opt['uniqueId']);
            }
            foreach ($_SESSION as $key => $value) {
                if (strpos( $key, 'PHPDEBUGBAR_STACK_DATA' )===false) {
                    @$data[ substr_replace($key,'',0,$prefix)] = is_string($value) ? $value : $this->formatVar($value);
                }
            }
        }
		return $data;
	}

	/**
	 * Returns the unique name of the collector
	 * @return string
	 */
	function getName() {
		return 'session';
	}

	/**
	 * Returns a hash where keys are control names and their values
	 * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
	 * @return array
	 */
	function getWidgets() {
		return array(
			"session" => array(
				"icon" => "archive",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => "session",
				"default" => "{}"
			)
		);
	}
}