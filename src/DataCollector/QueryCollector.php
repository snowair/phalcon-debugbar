<?php
/**
 * User: zhuyajie
 * Date: 15/3/5
 * Time: 11:31
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\PDO\PDOCollector;
use Phalcon\Db\Profiler;
use Snowair\Debugbar\Phalcon\Db\Profiler\Item;

class QueryCollector extends PDOCollector{
	/**
	 * @var \Snowair\Debugbar\Phalcon\Db\Profiler $profiler
	 */
	protected $profiler;
	protected $showConnection=false;

	protected $findSource = false;

	public function __construct( Profiler $profiler)
	{
		$this->profiler = $profiler;
	}

	public function collect()
	{
		/** @var Item[] $succeed */
		$succeed = (array)$this->profiler->getProfiles();
		/** @var Item[] $failed */
		$failed = (array)$this->profiler->getFailedProfiles();
		$data = array(
			'nb_statements'        => count($succeed) +count($failed),
			'nb_failed_statements' => count($failed),
			'accumulated_duration' => $this->profiler->getTotalElapsedSeconds(),
			'statements' => array()
		);
		$renderOrNot = $this->renderSqlWithParams;
		$show_conn = $this->showConnection;
		foreach ( $failed as $profile ) {
			$data['statements'][] = array(
				'sql'          => $renderOrNot?$profile->getRealSQL():$profile->getSQLStatement(),
				'params'       => $profile->getSqlVariables(),
				'is_success'    => false,
				'stmt_id'       => $profile->source,
                'error_code'    => $profile->err_code,
                'error_message' => $profile->err_msg,
				'connection'    => $show_conn?$profile->connection:null,
			);
		}
		foreach ( $succeed as $profile ) {
			$data['statements'][] = array(
				'sql'          => $renderOrNot?$profile->getRealSQL():$profile->getSQLStatement(),
				'params'       => $profile->getSqlVariables(),
				'row_count'    => $profile->affect_rows,
				'stmt_id'      => $profile->source,
				'connection'    => $show_conn?$profile->connection:null,
				'is_success'   => true,
				'duration'     => $profile->getTotalElapsedSeconds(),
				'duration_str' => $this->getDataFormatter()->formatDuration($profile->getTotalElapsedSeconds()),
			);
			if ( $explains = $profile->explain ) {
				foreach ( $explains as $explain ) {
					$data['statements'][] = array(
						'sql' => ' - EXPLAIN #' . $explain->id . ': `' . $explain->table . '` (' . $explain->select_type . ')',
						'params' => (array)$explain,
						'row_count' => $explain->rows,
						'stmt_id' => $explain->id,
						'is_success'   => true,
					);
				}
			}
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
		return substr($path,mb_strlen(realpath(dirname($_SERVER['DOCUMENT_ROOT']))));
	}

	/**
	 * @param boolean $showConnection
	 */
	public function setShowConnection( $showConnection ) {
		$this->showConnection = (bool)$showConnection;
	}

	/**
	 * @return \Snowair\Debugbar\Phalcon\Db\Profiler
	 */
	public function getProfiler() {
		return $this->profiler;
	}

	public function getWidgets()
	{
		return array(
			"database" => array(
				"icon" => "inbox",
				"widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
				"map" => "pdo",
				"default" => "[]"
			),
			"database:badge" => array(
				"map" => "pdo.nb_statements",
				"default" => 'null'
			)
		);
	}
}