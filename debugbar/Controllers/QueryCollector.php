<?php
/**
 * User: zhuyajie
 * Date: 15/3/5
 * Time: 11:31
 */

namespace Snowair\Debugbar\Controllers;


use DebugBar\DataCollector\PDO\PDOCollector;
use Phalcon\Db\Profiler;

class QueryCollector extends PDOCollector{

	protected $profiler;

	public function __construct(Profiler $profiler,\ArrayObject $failed_sql )
	{
		$this->profiler = $profiler;
		$this->failed_sql = $failed_sql;
	}

	public function collect()
	{
		$profiles = $this->profiler->getProfiles();
		$failed = $this->failed_sql->count();
		$data = array(
			'nb_statements' => count($profiles) + $failed,
			'nb_failed_statements' => $failed,
			'accumulated_duration' => $this->profiler->getTotalElapsedSeconds(),
			'statements' => array()
		);
		foreach ( $profiles as $item ) {
			$data['statements'][] = array(
				'sql'=> $item->getSQLStatement(),
				'duration'=>$item->getTotalElapsedSeconds(),
				'duration_str'=>$this->getDataFormatter()->formatDuration($item->getTotalElapsedSeconds())
			);
		}

		$data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);
		return $data;
	}


}