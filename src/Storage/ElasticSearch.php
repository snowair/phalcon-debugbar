<?php
/**
 * Created by PhpStorm.
 * User: zhuyajie
 * Date: 17-4-19
 * Time: 下午10:35
 */

namespace Snowair\Debugbar\Storage;

use DebugBar\Storage\StorageInterface;
use Elasticsearch\Client;

use Elasticsearch\ClientBuilder;


class ElasticSearch implements StorageInterface
{

    protected $client;
    protected $di;
    protected $sid;
    protected $config;

    public function __construct( $di, $config  )
    {
        $this->config = $config;

        if ( !$di['session']->isStarted() ) {
            $di['session']->start();
        }
        $this->sid = $di['session']->getId();;

        $factory = ClientBuilder::create();

        if($config->hosts){
            $factory->setHosts((array)$config->hosts);
        }

        if($config->ssl->key && $config->ssl->cert && $config->ssl->verify){
            $factory->setSSLKey($config->ssl->key);
            $factory->setSSLCert($config->ssl->cert);
            $factory->setSSLVerification($config->ssl->verify);
        }

        if($config->connection_params){
            $factory->setConnectionParams((array)$config->connection_params);
        }

        $this->client = $factory->build();
    }

    /**
     * Saves collected data
     *
     * @param string $id
     * @param string $data
     */
    function save( $id, $data )
    {
        $data['__meta']['sid'] = $this->sid;

        $params = [
            'index' => $this->config->index,
            'type' => $this->config->type,
            'id' => $id,
            'body' => $data,
        ];

        $this->client->index($params);
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
        $params = [
            'index' => $this->config->index,
            'type' => $this->config->type,
            'id' => $id,
        ];

        $response = $this->client->get($params);
        return $response['_source'];
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
        $index['index'] = $this->config->index;
        $index['type'] = $this->config->type;
        $index['body']['query']['bool']['must']= [
            ['match_phrase'=>[ '__meta.sid'=>$this->sid, ]]
        ];

        if (isset($filters['method'])) {
            $index['body']['query']['bool']['must'][] = [ 'match_phrase' => [ '__meta.method' => $filters['method'] ]];
        }
        if (isset($filters['uri'])) {
            $index['body']['query']['bool']['must'][] = [ 'match_phrase' => [ '__meta.uri' => $filters['uri'] ]];
        }
        if (isset($filters['ip'])) {
            $index['body']['query']['bool']['must'][] = [ 'match_phrase' => [ '__meta.ip' => $filters['ip'] ]];
        }

        $index['size'] = $max;
        $index['from'] = $offset;

        $response = $this->client->search($index);
        $result = array();
        foreach ($response['hits']['hits'] as $value) {
            if (isset($value['_source']['__meta'])) {
                $result[] = $value['_source']['__meta'];
            }
        }

        return $result;
    }

    /**
     * Clears all the collected data
     */
    function clear()
    {
        $index['index'] = $this->config->index;
        $index['type'] = $this->config->type;
        $index['body']['query']['bool']['must']= [
            ['match_phrase'=>[ '__meta.sid'=>$this->sid, ]]
        ];
        $this->client->deleteByQuery($index);
    }
}