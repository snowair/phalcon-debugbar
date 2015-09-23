<?php
/**
 * User: zhuyajie
 * Date: 15-9-22
 * Time: 下午10:24
 */
namespace Snowair\Debugbar\Storage;

use DebugBar\Storage\StorageInterface;
use Phalcon\Exception;

class MongoDB implements StorageInterface
{
    protected $collection;

    public function __construct( $connection, $db, $collection,$options  )
    {
        $client = new \MongoClient($connection,(array)$options);
        $this->collection = $client->{$db}->{$collection};
    }

    /**
     * Saves collected data
     *
     * @param string $id
     * @param string $data
     */
    function save( $id, $data )
    {
        $data['_id'] = $id;
        $this->collection->insert($data);
    }

    /**
     * Returns collected data with the specified id
     *
     * @param string $id
     *
     * @return array
     */
    function get( $id )
    {
        return (array)$this->collection->findOne(array('_id'=>$id));
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
    function find( array $filters = array(), $max = 20, $offset = 0 )
    {
        $criteria = array();
        if (isset($filters['method'])) {
            $criteria['__meta.method'] = $filters['method'];
        }
        if (isset($filters['uri'])) {
            $criteria['__meta.uri'] = $filters['uri'];
        }
        if (isset($filters['ip'])) {
            $criteria['__meta.ip'] = $filters['ip'];
        }
        $iterator =$this->collection->find($criteria)
            ->sort(array('__meta.utime'=>-1))
            ->skip($offset)->limit($max);
        $array = iterator_to_array($iterator);
        $result = array();
        foreach ($array as $value) {
            if (isset($value['__meta'])) {
                $result[] = $value['__meta'];
            }
        }

        return $result;
    }

    /**
     * Clears all the collected data
     */
    function clear()
    {
        $this->collection->drop();
    }
}
