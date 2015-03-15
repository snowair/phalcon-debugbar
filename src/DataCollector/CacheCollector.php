<?php
/**
 * User: zhuyajie
 * Date: 15/3/14
 * Time: 17:49
 */

namespace Snowair\Debugbar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Phalcon\Cache\Backend;

class CacheCollector extends DataCollector implements Renderable{

	use Formatter;

	protected $_mode = 0;
	protected $_saved     = array();
	protected $_fetched   = array();
	protected $_decreased = array();
	protected $_increased = array();
	protected $_deleted   = array();
	protected $_failed    = array('inc'=>array(),'dec'=>array());
	protected $_flushed   = false;
	/**
	 * @var MessagesCollector $_messagesCollector
	 */
	protected $_messagesCollector;

	public function __construct($mode,$messageCollector=null) {
		$this->_mode = (int)$mode;
		$this->_messagesCollector = $messageCollector;
	}

	public function save($key,$content,$lifetime) {
		if ( $this->_mode ) {
			$data = array(
				'key'=>$key,
				'lifetime'=>$lifetime,
				'content'=>$content,
			);
			if ( $lifetime===null ) {
				unset($data['lifetime']);
			}
			$this->_saved[] =$data;
		}else{
			$this->_saved[] = true;
		}
	}

	public function decrement( $key, $step, $value ) {
		$data = array( 'key'=>$key, 'step'=>$step, 'new_value'=>$value, );
		if ( $value===false || $value===null ) {
			$this->_failed['dec'][] = $data;
		}elseif ( $this->_mode ) {
			$this->_decreased[] = $data;
		}else{
			$this->_decreased[] = true;
		}
	}

	public function increment( $key, $step, $value ) {
		$data = array( 'key'=>$key, 'step'=>$step, 'new_value'=>$value, );
		if ( $value===false || $value===null ) {
			$this->_failed['inc'][] = $data;
		}elseif ( $this->_mode ) {
			$this->_increased[] = $data;
		}else{
			$this->_increased[] = true;
		}
	}

	public function get() {
		$params = func_get_args();
		if ( func_num_args()==2 ) {
			$data = array( 'key'=>$params[0], 'value'=>$params[1] );
		}else{
			$data = array( 'key'=>$params[0], 'value'=>$params[2] );
		}
		if ( $this->_mode ) {
			$this->_fetched[$params[0]] = $data;
		}else{
			$this->_fetched[$params[0]] = true;
		}
	}

	public function delete( $key ) {
		$this->_deleted[] = $key;
	}

	public function flush($bool) {
		$this->_flushed = $bool;
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 * @return array Collected data
	 */
	function collect() {
		$inc_failed = count($this->_failed['inc']);
		$dec_failed = count($this->_failed['dec']);
		$n_saved    = count($this->_saved);
		$n_inc      = count($this->_increased);
		$n_dec      = count($this->_decreased);
		$n_fetched  = count($this->_fetched);
		$n_deleted  = count($this->_deleted);
		$data = array(
			'count'       =>0,
			'messages'    =>array(),
		);
		if ( $inc_failed+$dec_failed>0 ) {
			$data['messages'][] =  array(
				'message'  => "Caches Failed: [ Inc:{$inc_failed} , Dec:{$dec_failed} ]",
				'is_string'=> true,
				'label'    => 'Caches Summary'
			);
		}
		$message = "Caches Count: [ Saved:{$n_saved} ; Gets:{$n_fetched}";
		if ( $n_inc>0 ) {
			$message .= " ; Inc:{$n_inc}";
		}
		if ( $n_dec>0 ) {
			$message .= " ; Dec:{$n_dec}";
		}
		if ( $n_deleted>0 ) {
			$message .= " ; Deleted:{$n_deleted}";
		}
		if ( $this->_flushed ) {
			$message .= " ; Have Flushed";
		}
		$data['messages'][] = array(
			'message'  => $message.' ]',
			'is_string'=> true,
			'label'    => 'Caches Summary'
		);
		$messages = $data['messages'];

		if ( !$this->_mode && $this->_messagesCollector ) {
			foreach ( $messages as $value ) {
				$this->_messagesCollector->addMessage($value['message'],$value['label'],$value['is_string']);
			}
			return array();
		}

		foreach ( $this->_saved as $value ) {
			$m = $this->formatVars($value);
			$messages[] = array(
				'message' => substr_replace($m[0],'Saved:',0,7),
				'is_string' => false,
				'label' => 'Saved',
			);
		}
		foreach ( $this->_fetched as $value ) {
			$m = $this->formatVars($value);
			$messages[] = array(
				'message' => substr_replace($m[0],'Gets:',0,7),
				'is_string' => false,
				'label' => 'Gets',
			);
		}
		foreach ( $this->_deleted as $value ) {
			$messages[] = array(
				'message' => 'DeletedKey: [ '.$value.' ]',
				'is_string' => true,
				'label' => 'Deleted',
			);
		}
		foreach ( $this->_increased as $value ) {
			$messages[] = array(
				'message' => "Increased: [ Key:{$value['key']} , Step:{$value['step']} , NewValue:{$value['new_value']}] ",
				'is_string' => true,
				'label' => 'Increased',
			);
		}
		foreach ( $this->_decreased as $value ) {
			$messages[] = array(
				'message' => "Decreased: [ Key:{$value['key']} , Step:{$value['step']} , NewValue:{$value['new_value']}] ",
				'is_string' => true,
				'label' => 'Decreased',
			);
		}
		foreach ( $this->_failed['inc'] as $value ) {
			$messages[] = array(
				'message' => "IncFailed: [ Key:{$value['key']} , Step:{$value['step']}] ",
				'is_string' => true,
				'label' => 'IncFailed',
			);
		}
		foreach ( $this->_failed['dec'] as $value ) {
			$messages[] = array(
				'message' => "DecFailed: [ Key:{$value['key']} , Step:{$value['step']}] ",
				'is_string' => true,
				'label' => 'DecFailed',
			);
		}
		$data['messages'] = $messages;
		$data['count'] = count($messages)-1;
		return $data;
	}

	/**
	 * Returns the unique name of the collector
	 * @return string
	 */
	function getName() {
		return 'caches';
	}

	/**
	 * Returns a hash where keys are control names and their values
	 * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
	 * @return array
	 */
	function getWidgets() {
		if ( !$this->_mode && $this->_messagesCollector ) {
			return array();
		}
		return array(
			"caches" => array(
				'icon' => 'list-alt',
				"widget" => "PhpDebugBar.Widgets.MessagesWidget",
				"map" => "caches.messages",
				"default" => "[]"
			),
			"caches:badge" => array(
				"map" => "caches.count",
				"default" => "null"
			)
		);
	}
}