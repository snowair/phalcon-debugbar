<?php
/**
 * User: zhuyajie
 * Date: 15/3/6
 * Time: 12:25
 */

namespace Snowair\Debugbar\Phalcon\Db;

use Phalcon\Db\Adapter;
use \Phalcon\Db\Profiler as PhalconProfiler;
use Snowair\Debugbar\Phalcon\Db\Profiler\Item;

class Profiler extends  PhalconProfiler {

	protected $_failedProfiles=array();
	protected $_stoped=false;
	protected $_lastFailed;
	/**
	 * @var  Item $activeProfile
	 */
	protected $_activeProfile;
	/**
	 * @var Adapter  $_db
	 */
	protected $_db;

	/**
	 * Starts the profile of a SQL sentence
	 *
	 * @param string $sqlStatement
	 * @param null|array   $sqlVariables
	 * @param null|array   $sqlBindTypes
	 *
	 * @return PhalconProfiler
	 */
	public function startProfile($sqlStatement, $sqlVariables = null, $sqlBindTypes = null)
	{
		$latest = $this->_activeProfile;
		if ( !$this->_stoped && $latest) {
			if ( $this->_db ) {
				$info = $this->_db->getDescriptor();
				$pdo = $this->_db->getInternalHandler();
				$latest->setExtra(array(
					'err_code'=>$pdo->errorCode(),
					'err_msg'=>$pdo->errorInfo(),
					'connection'=>$info['host'].':'.$info['dbname'],
				));
			}
			$this->_lastFailed = $latest;
			$this->_failedProfiles[] = $latest;
		}

		$activeProfile = new Item();

		$activeProfile->setSqlStatement($sqlStatement);
		$activeProfile->setRealSQL($this->setRealSql($sqlStatement,$sqlVariables));

		if ( is_array($sqlVariables) ) {
			$activeProfile->setSqlVariables($sqlVariables);
		}

		if (is_array($sqlBindTypes)) {
			$activeProfile->setSqlBindTypes($sqlBindTypes);
		}

		$activeProfile->setInitialTime(microtime(true));

		if ( method_exists($this, "beforeStartProfile")) {
			$this->beforeStartProfile($activeProfile);
		}

		$this->_activeProfile = $activeProfile;

		$this->_stoped = false;
		return $this;
	}

	public function setRealSql( $sql, $variables ) {
		if ( !$variables ) {
			return $sql;
		}
		$pdo = $this->_db->getInternalHandler();
		$indexes = array();
		$keys    = array();
		foreach ( $variables as $key=> $value ) {
			if ( is_numeric($key) ) {
				$indexes[$key] = $pdo->quote($value);
			} else {
				if ( is_numeric( substr( $key, 1 ) ) ) {
					$keys[$key] = $pdo->quote($value);
				} else {
					$keys[':'.$key] = $pdo->quote($value);
				}
			}
		}
		$splited = preg_split('/(?=\?)|(?<=\?)/',$sql);

		$result = array();
		foreach ( $splited as $key => $value ) {
			if ( $value=='?' ) {
				$result[$key]=array_shift($indexes);
			} else {
				$result[$key]=$value;
			}
		}
		$result = implode(' ', $result);
		$result = strtr($result,$keys);
		return $result;
	}

	/**
	 * Stops the active profile
	 *
	 * @return PhalconProfiler
	 */
	public function stopProfile()
	{

		$finalTime = microtime(true);
		$activeProfile = $this->_activeProfile;
		$activeProfile->setFinalTime($finalTime);

		$initialTime = $activeProfile->getInitialTime();
		$this->_totalSeconds = $this->_totalSeconds + ($finalTime - $initialTime);

		if ( $this->_db ) {
			$info = $this->_db->getDescriptor();
			$pdo  = $this->_db->getInternalHandler();
			$sql  = $activeProfile->getSQLStatement();
			$data = array( 'last_insert_id'=>0, 'affect_rows'=>0 );
			$data['connection']=$info['host'].':'.$info['dbname'];
			if ( stripos( $sql, 'INSERT' )===0 ) {
				$data['last_insert_id'] =  $pdo->lastInsertId();
			}
			if ( stripos( $sql, 'INSERT')===0  || stripos( $sql, 'UPDATE')===0 || stripos( $sql, 'DELETE')===0) {
				$data['affect_rows'] =  $this->_db->affectedRows();
			}
			$activeProfile->setExtra($data);
		}

		$this->_allProfiles[] = $activeProfile;

		if (method_exists($this, "afterEndProfile")) {
			$this->afterEndProfile($activeProfile);
		}

		$this->_stoped = true;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFailedProfiles() {
		return $this->_failedProfiles;
	}

	/**
	 * @return Item
	 */
	public function getLastFailed() {
		return $this->_lastFailed;
	}

	/**
	 * @param Adapter $db
	 */
	public function setDb( $db ) {
		$this->_db = $db;
	}

	public function setSource( $source ) {
			$this->_activeProfile->setExtra(array('source'=>$source));
	}
}