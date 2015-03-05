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
                'error_code'    => $profile['err_code'],
                'error_message' => $profile['err_msg'],
			);
		}
		foreach ( $succeed as $profile ) {
			$data['statements'][] = array(
				'sql'          => $profile['item']->getSQLStatement(),
				'params'       => $profile['item']->getSqlVariables(),
				'row_count'    => $profile['affect_rows'],
				'is_success'   => true,
				'duration'     => $profile['item']->getTotalElapsedSeconds(),
				'duration_str' => $this->getDataFormatter()->formatDuration($profile['item']->getTotalElapsedSeconds()),
			);
		}

		$data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);
		return $data;
	}


}