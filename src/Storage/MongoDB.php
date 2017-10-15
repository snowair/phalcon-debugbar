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
    protected $di;
    protected $sid;

    public function __construct($di, $connection, $db, $collection, $options, $dirveropts)
    {
        if (!$di['session']->isStarted()) {
            $di['session']->start();
        }
        $this->sid = $di['session']->getId();;
        $client = new \MongoDB\Client($connection, (array)$options, (array)$dirveropts);
        $this->collection = $client->{$db}->{$collection};
    }

    /**
     * Saves collected data
     *
     * @param string $id
     * @param array  $data
     */
    function save($id, $data)
    {
        $data['_id'] = $id;
        $data['__meta']['sid'] = $this->sid;
        $this->collection->insertOne($data);
    }

    /**
     * Returns collected data with the specified id
     *
     * @param string $id
     *
     * @return array
     */
    function get($id)
    {
        return (array)$this->collection->findOne(array('_id' => $id));
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
    function find(array $filters = array(), $max = 20, $offset = 0)
    {
        $criteria = [
            '$and' => [
                ['__meta.sid' => $this->sid],
            ],
        ];
        if (isset($filters['method'])) {
            $criteria['$and'][]['__meta.method'] = $filters['method'];
        }
        if (isset($filters['uri'])) {
            $criteria['$and'][]['__meta.uri'] = $filters['uri'];
        }
        if (isset($filters['ip'])) {
            $criteria['$and'][]['__meta.ip'] = $filters['ip'];
        }
        $iterator = $this->collection->find(
            $criteria,
            [
                'skip'  => (int)$offset,
                'limit' => (int)$max,
                'sort'  => ['__meta.utime' => -1],
            ]);
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
        $this->collection->deleteMany(['__meta.sid' => $this->sid]);
    }
}
