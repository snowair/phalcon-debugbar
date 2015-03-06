<?php
/**
 * User: zhuyajie
 * Date: 15/3/5
 * Time: 11:31
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\PDO\PDOCollector;
use Phalcon\Db\Profiler;

class QueryCollector extends PDOCollector{

	protected $succeed;
	protected $failed;
	protected $findSource = false;

	public function __construct(\ArrayObject $succeed,\ArrayObject $failed, Profiler $profiler)
	{
		$this->succeed = $succeed;
		$this->failed = $failed;
		$this->profiler = $profiler;
	}

	public function collect()
	{
		$succeed = $this->succeed;
		$failed = $this->failed;
		$data = array(
			'nb_statements'        => $succeed->count() + $failed->count(),
			'nb_failed_statements' => $failed->count(),
			'accumulated_duration' => $this->profiler->getTotalElapsedSeconds(),
			'statements' => array()
		);
		foreach ( $failed as $profile ) {
			$data['statements'][] = array(
				'sql'           => $profile['sql'],
				'params'        => $profile['params'],
				'is_success'    => false,
				'stmt_id'      =>  null,
                'error_code'    => $profile['err_code'],
                'error_message' => $profile['err_msg'],
			);
		}
		foreach ( $succeed as $profile ) {
			$data['statements'][] = array(
				'sql'          => $profile['item']->getSQLStatement(),
				'params'       => $profile['item']->getSqlVariables(),
				'row_count'    => $profile['affect_rows'],
				'stmt_id'      => $profile['source'],
				'is_success'   => true,
				'duration'     => $profile['item']->getTotalElapsedSeconds(),
				'duration_str' => $this->getDataFormatter()->formatDuration($profile['item']->getTotalElapsedSeconds()),
			);
		}

		$data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);
		return $data;
	}

	public function setFindSource($value = true)
	{
		$this->findSource = (bool) $value;
	}

	public function getFindSource() {
		return $this->findSource;
	}
	/**
	 * Use a backtrace to search for the origin of the query.
	 */
	public function findSource()
	{
		$traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
		foreach ($traces as $trace) {
			if (isset($trace['class']) && isset($trace['file']) && isset($trace['line']) ) {
				if ( strpos( $trace['file'], DIRECTORY_SEPARATOR .'vendor'. DIRECTORY_SEPARATOR ) === false
					&& $trace['class']!= get_class($this)
					&& strpos($trace['class'],'PhalconDebugbar')===false
				) {
					$file = $trace['file'];
					$line = $trace['line'];
					return $this->normalizeFilename($file) . ':' . $line;
				}
			}
		}
		return null;
	}

	/**
	 * Shorten the path by removing the relative links and base dir
	 *
	 * @param string $path
	 * @return string
	 */
	protected function normalizeFilename($path)
	{
		if (file_exists($path)) {
			$path = realpath($path);
		}
		return str_replace( realpath(dirname($_SERVER['DOCUMENT_ROOT'])), '', $path);
	}


}