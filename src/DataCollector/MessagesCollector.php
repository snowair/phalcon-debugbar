<?php
/**
 * User: zhuyajie
 * Date: 15/3/8
 * Time: 11:59
 */

namespace Snowair\Debugbar\DataCollector;

use DebugBar\DataCollector\MessagesCollector as Message;

class MessagesCollector extends Message {

	use Formatter;

	public function addMessage($message, $label = 'info', $isString = true, $time=null)
	{
		$formated = $this->formatVars($message);
		if ( $formated['exception'] ) {
			$label = 'error';
		}
		$this->messages[] = array(
			'message' => $formated[0],
			'is_string' => is_scalar($message),
			'label' => $label,
			'time' => $time?$time:microtime(true)
		);
	}


}