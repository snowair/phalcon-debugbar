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

	public function addMessage($message, $label = 'info', $isString = true)
	{
		$formated = $this->formatVars($message);
		if ( $formated['exception'] ) {
			$label = 'error';
		}
		$this->messages[] = array(
			'message' => $formated[0],
			'is_string' => is_string($message),
			'label' => $label,
			'time' => microtime(true)
		);
	}


}