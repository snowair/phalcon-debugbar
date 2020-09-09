<?php
/**
 * User: zhuyajie
 * Date: 15/3/14
 * Time: 20:24
 */

namespace Snowair\Debugbar\Phalcon\Cache;

use Phalcon\Cache\Adapter\AdapterInterface as CacheAdapterInterface;
use Phalcon\Exception;
use Phalcon\Storage\Serializer\Base64;
use Phalcon\Storage\Serializer\Msgpack;
use ReflectionClass;
use Snowair\Debugbar\DataCollector\CacheCollector;

trait ProxyTrait
{
    /** @var CacheCollector */
    protected $_collector;
    /** @var CacheAdapterInterface */
    protected $_backend;

    public function __construct(CacheAdapterInterface $backend, CacheCollector $collector)
    {
        $this->_collector = $collector;
        $this->_backend = $backend;
    }

    public function __call($name, $parameters)
    {
        return $this->call($name, $parameters);
    }

    protected function call()
    {
        $parameters = func_get_args();
        $name = (string)$parameters[0];
        $parameters = isset($parameters[1]) ? $parameters[1] : array();
        if (is_callable(array($this->_backend, $name))) {
            $value = call_user_func_array(array($this->_backend, $name), $parameters);

            $reflection = new ReflectionClass($this->_backend);
            // when some cache decorators are used there's no 'serializer' property
            if ($reflection->hasProperty('serializer')) {
                $prop = $reflection->getProperty('serializer');
                $prop->setAccessible(true);
                $serializer = $prop->getValue($this->_backend);
                if ($serializer instanceof Base64 || $serializer instanceof Msgpack) {
                    if (in_array($name, ['save', 'set'])) {
                        $parameters[1] = '[BINARY DATA]';
                    }
                    if ($name == 'get') {
                        $returned = '[BINARY DATA]';
                    }
                }
            }
            if (in_array($name, ['save', 'set']) && is_object($parameters[1])) {
                $parameters[1] = 'Object Of : ' . get_class($parameters[1]);
            }
            if ($name == 'get' && is_object($value)) {
                $returned = 'Object Of : ' . get_class($value);
            }
            $parameters[] = isset($returned) ? $returned : $value;
            if (in_array(strtolower($name), array('save', 'set', 'increment', 'decrement', 'get', 'delete'))) {
                call_user_func_array(array($this->_collector, $name), $parameters);
            }
            return $value;
        }
        throw new Exception("Method '{$name}' not found on " . get_class($this->_backend));
    }

    public function get(string $key, $defaultValue = null)
    {
        return $this->call('get', array($key, $defaultValue));
    }

    /**
     * @param string $keyName
     * @param mixed $content
     * @param int|null $ttl
     * @return bool
     * @deprecated BC for app cache proxy/decorator classes
     */
    public function save(string $keyName, $content, $ttl = null): bool
    {
        return $this->call('save', array($keyName, $content, $ttl));
    }

    public function set(string $keyName, $content, $ttl = null): bool
    {
        return $this->call('set', array($keyName, $content, $ttl));
    }

    public function delete(string $keyName): bool
    {
        return $this->call('delete', array($keyName));
    }

    public function increment(string $key, int $value = 1)
    {
        return $this->call('increment', array($key, $value));
    }

    public function decrement(string $key, int $value = 1)
    {
        return $this->call('decrement', array($key, $value));
    }

    public function getKeys(string $prefix = ''): array
    {
        return $this->_backend->getKeys($prefix);
    }

    /**
     * @param string|null $keyName
     * @return bool
     * @deprecated BC for app cache proxy/decorator classes
     */
    public function exists(string $keyName = null): bool
    {
        return $this->_backend->has($keyName);
    }

    public function clear(): bool
    {
        return $this->_backend->clear();
    }

    public function getAdapter()
    {
        return $this->_backend->getAdapter();
    }

    public function getPrefix(): string
    {
        return $this->_backend->getPrefix();
    }

    public function has(string $key): bool
    {
        return $this->_backend->has($key);
    }
}
