<?php
/**
 * User: zhuyajie
 * Date: 15/3/8
 * Time: 11:59
 */

namespace Snowair\Debugbar\DataCollector;

use DebugBar\DataCollector\MessagesCollector as Message;

class MessagesCollector extends Message {

	public function addMessage($message, $label = 'info', $isString = true)
	{
		if ( is_object( $message ) ) {
			if ( method_exists( $message, 'toArray' ) ) {
				$message = $message->toArray();
			}
			if ( $message instanceof \StdClass ) {
				$message = (array)$message;
			}
			if ( $message instanceof \Traversable ) {
				$result = array();
				foreach ( $message as $k=>$v ) {
					$result[$k]=$v;
				}
				$message = $result;
			}
		}
		if (!is_string($message)) {
			$message = $this->getDataFormatter()->formatVar($message);
			$isString = false;
		}
		$this->messages[] = array(
			'message' => $message,
			'is_string' => $isString,
			'label' => $label,
			'time' => microtime(true)
		);
	}


}