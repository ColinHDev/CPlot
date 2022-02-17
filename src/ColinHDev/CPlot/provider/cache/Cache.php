<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider\cache;

/**
 * @phpstan-template TIdentifier of string|int
 * @phpstan-template TCacheable
 */
class Cache {

    private int $size;
    /** @phpstan-var array<TIdentifier, TCacheable> */
    private array $cache = [];

    /**
     * @param int $size
     * if $size > 0, cache is enabled and the size of Cache::$cache is limited by $size
     * if $size <= 0, cache is disabled
     */
    public function __construct(int $size) {
        $this->size = $size;
    }

    /**
     * @phpstan-param TIdentifier $identifier
     * @phpstan-param TCacheable $cacheable
     */
    public function cacheObject(mixed $identifier, mixed $cacheable) : void {
        // if the cache is disabled, we don't need to save anything
        if ($this->size <= 0) {
            return;
        }

        // if the object is already cached, we remove its old version from the cache
        if (isset($this->cache[$identifier])) {
            unset($this->cache[$identifier]);

        // if the cache has grown to big, we remove the oldest element from the cache
        // oldest element = first element of the array Cache::$cache
        } else if ($this->size <= count($this->cache)) {
            array_shift($this->cache);
        }

        // adding the object to the end of the cache
        if (is_object($cacheable)) {
            $cacheable = clone $cacheable;
        }
        $this->cache[$identifier] = $cacheable;
    }

    /**
     * @phpstan-param TIdentifier $identifier
     * @phpstan-return TCacheable|null
     */
    public function getObjectFromCache(mixed $identifier) : mixed {
        // if the cache is disabled, we don't need to check if anything is set in Cache::$cache
        if ($this->size <= 0) {
            return null;
        }
        // if no object is saved under $key, return null
        return $this->cache[$identifier] ?? null;
    }

    /**
     * @phpstan-param TIdentifier $identifier
     */
    public function removeObjectFromCache(mixed $identifier) : void {
        // if the cache is disabled, we don't need to try to remove an object from Cache::$cache
        if ($this->size <= 0) {
            return;
        }
        unset($this->cache[$identifier]);
    }
}