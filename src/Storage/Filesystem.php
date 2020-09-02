<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 14:51
 */

namespace Snowair\Debugbar\Storage;

use DebugBar\Storage\StorageInterface;
use Phalcon\Di;

class Filesystem implements  StorageInterface
{
	protected $dirname;
	protected $gc_lifetime = 24;     // Hours to keep collected data;
	protected $gc_probability = 5;   // Probability of GC being run on a save request. (5/100)

	/**
	 * @param string $dirname 存放文件的目录
     * @param DI $di
	 */
	public function __construct($dirname,$di)
	{
//        if ( !$di['session']->isStarted() ) {
//            $di['session']->start();
//        }
        $sid = $di['session']->getId();

		$this->dirname = rtrim($dirname, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR.$sid.DIRECTORY_SEPARATOR;
	}

	/**
	 * Saves collected data
	 *
	 * @param string $id
	 * @param string $data
	 *
	 * @throws \Exception
	 */
	function save( $id, $data ) {
		if (!is_dir($this->dirname)) {
            if (mkdir($this->dirname, 0777, true)) {
				file_put_contents($this->dirname . '.gitignore', "*\n!.gitignore");
			} else {
				throw new \Exception("Cannot create directory '$this->dirname'..");
			}
		}

		file_put_contents($this->makeFilename($id), json_encode($data));
		touch($this->dirname);

		// Randomly check if we should collect old files
		if (mt_rand(1, 100) <= $this->gc_probability) {
			$this->garbageCollect();
		}
	}

	/**
	 * Create the filename for the data, based on the id.
	 *
	 * @param $id
	 * @return string
	 */
	public function makeFilename($id)
	{
		return $this->dirname . basename($id) . ".json";
	}

	/**
	 * Delete files older then a certain age (gc_lifetime)
	 */
	protected function garbageCollect()
	{
		$lifetime = $this->gc_lifetime*60*60;
		$Finder = new \RecursiveDirectoryIterator(dirname($this->dirname),
		      \FilesystemIterator::KEY_AS_FILENAME
			| \FilesystemIterator::CURRENT_AS_FILEINFO
			| \FilesystemIterator::SKIP_DOTS);
		$now = time();
		/** @var \SplFileInfo $value */
		foreach ( $Finder as $key => $value ) {
			if ( pathinfo($key,PATHINFO_EXTENSION)=='json' && ( $value->getMtime() + $lifetime < $now) ) {
				@unlink($value->getRealPath());
			} elseif($value->isDir() && ( $value->getMtime() + $lifetime < $now)  ){
			    $path = $value->getRealPath();
			    $dir = dir($path);
			    while($f=$dir->read()){
			        if(!in_array($f,['.','..']) && is_file($path.'/'.$f)){
                        @unlink($path.'/'.$f);
                    }
                }
                $dir->close();
                @rmdir($value->getRealPath());
            }
		}
	}

	/**
	 * Returns collected data with the specified id
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	function get( $id ) {
		return json_decode(file_get_contents($this->makeFilename($id)), true);
	}

	/**
	 * Returns a metadata about collected data
	 *
	 * @param array   $filters
	 * @param integer $max
	 * @param integer $offset
	 *
	 * @return array
	 */
	function find( array $filters = array(), $max = 20, $offset = 0 ) {
		// Sort by modified time, newest first
		$sort = function (\SplFileInfo $a, \SplFileInfo $b) {
			return strcmp($b->getMTime(), $a->getMTime());
		};

		// Loop through .json files, filter the metadata and stop when max is found.
		$i = 0;
		$results = array();

		$Finder = new \FilesystemIterator($this->dirname,
			\FilesystemIterator::KEY_AS_FILENAME
			| \FilesystemIterator::CURRENT_AS_FILEINFO
			| \FilesystemIterator::SKIP_DOTS);

		$files = array();
		foreach ( $Finder as $key=>$value ) {
			if ( pathinfo($key,PATHINFO_EXTENSION)=='json' ){
				$files[]= $value;
			}
		}
		usort($files,$sort);

		foreach ($files as $file) {
			if ($i++ < $offset && empty($filters)) {
				$results[] = null;
				continue;
			}
			$data = json_decode( file_get_contents($file->getRealPath()), true);
			$meta = $data['__meta'];
			unset($data);
			if ($this->filter($meta, $filters)) {
				$results[] = $meta;
			}
			if (count($results) >= ($max + $offset)) {
				break;
			}
		}

			$array = array_slice($results, $offset, $max);
		return $array;
	}

	/**
	 * Filter the metadata for matches.
	 *
	 * @param $meta
	 * @param $filters
	 * @return bool
	 */
	protected function filter($meta, $filters)
	{
		foreach ($filters as $key => $value) {
			if (!isset($meta[$key]) || fnmatch($value, $meta[$key]) === false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Clears all the collected data
	 */
	function clear() {
		$Finder = new \FilesystemIterator($this->dirname,
			\FilesystemIterator::KEY_AS_FILENAME
			| \FilesystemIterator::CURRENT_AS_FILEINFO
			| \FilesystemIterator::SKIP_DOTS);
		foreach ( $Finder as $key => $value ) {
			if ( pathinfo($key,PATHINFO_EXTENSION)=='json' ) {
				unlink($value->getRealPath());
			}
		}
	}
}
