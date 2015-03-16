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
	protected $_nulls     = 0;

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
				'key'     =>$key,
				'lifetime'=>$lifetime,
				'content' =>$content,
				'time'    =>microtime(true),
			);
			$this->_saved[$key] =$data;
		}else{
			$this->_saved[] = true;
		}
	}

	public function decrement( $key, $step, $value ) {
		$data = array( 'key'=>$key, 'step'=>$step, 'new_value'=>$value,'time'=>microtime(true) );
		if ( $value===false || $value===null ) {
			$this->_failed['dec'][] = $data;
		}elseif ( $this->_mode ) {
			$this->_decreased[] = $data;
		}else{
			$this->_decreased[] = true;
		}
	}

	public function increment( $key, $step, $value ) {
		$data = array( 'key'=>$key, 'step'=>$step, 'new_value'=>$value,'time'=>microtime(true) );
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
			$data = array( 'key'=>$params[0], 'value'=>$params[1],'time'=>microtime(true) );
		}else{
			$data = array( 'key'=>$params[0], 'value'=>$params[2],'time'=>microtime(true) );
		}
		if ( $data['value']===null ) {
			$this->_nulls++;
		}
		if ( $this->_mode ) {
			$this->_fetched[$params[0]] = $data;
		}else{
			$this->_fetched[$params[0]] = true;
		}
	}

	public function delete( $key ) {
		$this->_deleted[] = array( 'key'=>$key,'time'=>microtime(true));
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
		if ( $this->_nulls>0 ) {
			$message .= "(nulls:{$this->_nulls})";
		}
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
			$message .= " ; Has Flushed";
		}
		$data['messages'][] = array(
			'message'  => $message.' ]',
			'is_string'=> true,
			'label'    => 'Caches Summary'
		);

		if ( !$this->_mode && $this->_messagesCollector ) {
			foreach ( $data['messages'] as $value ) {
				$this->_messagesCollector->addMessage($value['message'],$value['label'],$value['is_string']);
			}
			return array();
		}

		$messages = array();
		foreach ( $this->_saved as $key=>$value ) {
			$lifetime = '';
			if ( $value['lifetime']!==null ) {
				$lifetime = "Lifetime=>"."{$value['lifetime']}";
			}
			$content    = $value['content'];
			if ( !is_string( $content ) ) {
				$content = $this->formatVars($content);
				$message= "Saved: [ Key=>\"$key\"  $lifetime  Value=> $content[0] ]";
			}else{
				$message= "Saved: [ Key=>\"$key\"  $lifetime  Value=> \"$content\" ]";
			}
			$messages[] = array(
				'message'   => $message,
				'is_string' => mb_strlen($message)>100?false:true,
				'label'     => 'Saved',
				'time'      =>$value['time'],
			);
		}
		foreach ( $this->_fetched as $key=>$value ) {
			$content = $value['value'];
			if ( !is_string( $value ) ) {
				$content = $this->formatVars($content);
				$message= "Gets: [ Key=>\"$key\"   Value=> $content[0] ]";
			}else{
				$message= "Gets: [ Key=>\"$key\"   Value=> \"$content\" ]";
			}
			$messages[] = array(
				'message'   => $message,
				'is_string' => mb_strlen($message)>100?false:true,
				'label'     => 'Gets',
				'time'      => $value['time'],
			);
		}
		foreach ( $this->_deleted as $value ) {
			$messages[] = array(
				'message'   => 'DeletedKey: [ '.$value['key'].' ]',
				'is_string' => true,
				'label'     => 'Deleted',
				'time'      => $value['time'],
			);
		}
		foreach ( $this->_increased as $value ) {
			$messages[] = array(
				'message'   => "Increased: [ Key:{$value['key']} , Step:{$value['step']} , NewValue:{$value['new_value']}] ",
				'is_string' => true,
				'label'     => 'Increased',
				'time'      => $value['time'],
			);
		}
		foreach ( $this->_decreased as $value ) {
			$messages[] = array(
				'message'   => "Decreased: [ Key:{$value['key']} , Step:{$value['step']} , NewValue:{$value['new_value']}] ",
				'is_string' => true,
				'label'     => 'Decreased',
				'time'      => $value['time'],
			);
		}
		foreach ( $this->_failed['inc'] as $value ) {
			$messages[] = array(
				'message'   => "IncFailed: [ Key:{$value['key']} , Step:{$value['step']}] ",
				'is_string' => true,
				'label'     => 'IncFailed',
				'time'      => $value['time'],
			);
		}
		foreach ( $this->_failed['dec'] as $value ) {
			$messages[] = array(
				'message'   => "DecFailed: [ Key:{$value['key']} , Step:{$value['step']}] ",
				'is_string' => true,
				'label'     => 'DecFailed',
				'time'      => $value['time'],
			);
		}
		$data['messages'] = array_merge($data['messages'],  $this->sort($messages));
		$data['count'] = count($messages);
		return $data;
	}

	public function sort($messages) {
		usort($messages, function ($a, $b) {
			if ($a['time'] === $b['time']) {
				return 0;
			}
			return $a['time'] < $b['time'] ? -1 : 1;
		});
		return $messages;
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
				'icon' => 'star',
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