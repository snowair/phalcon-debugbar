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
		$formatter = $this->getDataFormatter();
		if ( is_object( $message ) ) {
			$prefix = '['. get_class($message) .'] Convertd To : ';
			if ( method_exists( $message, 'toArray' ) ) {
				$message = $prefix. $formatter->formatVar($message->toArray());
			}else if ( $message instanceof \StdClass ) {
				$message = (array)$message;
			}else if ( $message instanceof \Traversable ) {
				$result = array();
				foreach ( $message as $k=>$v ) {
					$result[$k]=$v;
				}
				$message = $prefix. $formatter->formatVar($result);
			}else{
				try{
					$message = $formatter->formatVar($message);
				}catch (\Exception $e){
					$label = 'error';
					$message = 'Can not add Instance of [' . get_class($message) . '] to Debug bar.';
				}
			}
			$isString = false;
		}else{
			if (!is_string($message)) {
				try{
					$message = $formatter->formatVar($message);
				}catch (\Exception $e){
					$label = 'error';
					$message = 'Can not add [' . gettype($message) . '] Variable to Debug bar.';
				}
				$isString = false;
			}
		}
		$this->messages[] = array(
			'message' => $message,
			'is_string' => $isString,
			'label' => $label,
			'time' => microtime(true)
		);
	}


}