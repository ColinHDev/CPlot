<?php

namespace ColinHDev\CPlot\provider\cache;

class Cache {

    private int $size;
    /** @var Cacheable[] */
    private array $cache = [];

    /**
     * @param int $size
     * if $size > 0, cache is enabled and the size of Cache::$cache is limited by $size
     * if $size <= 0, cache is disabled
     */
    public function __construct(int $size) {
        $this->size = $size;
    }

    public function cacheObject(string $key, Cacheable $object) : void {
        // if the cache is disabled, we don't need to save anything
        if ($this->size <= 0) return;

        // if the object is already cached, we remove its old version from the cache
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);

        // if the cache has grown to big, we remove the oldest element from the cache
        // oldest element = first element of the array Cache::$cache
        } else if ($this->size <= count($this->cache)) {
            array_shift($this->cache);
        }

        // adding the object to the end of the cache
        $this->cache[$key] = clone $object;
    }

    public function getObjectFromCache(string $key) : ?Cacheable {
        // if the cache is disabled, we don't need to check if anything is set in Cache::$cache
        if ($this->size <= 0) return null;

        // if no object is saved under $key, return null
        if (!isset($this->cache[$key])) return null;

        // return the cached object
        return $this->cache[$key];
    }

    public function removeObjectFromCache(string $key) : void {
        // if the cache is disabled, we don't need to try to remove an object from Cache::$cache
        if ($this->size <= 0) return;
        unset($this->cache[$key]);
    }
}