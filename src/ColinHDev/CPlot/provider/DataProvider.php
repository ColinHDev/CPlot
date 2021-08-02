<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\worlds\WorldSettings;

abstract class DataProvider {

    /** @var array | WorldSettings[] $worldCache */
    private array $worldCache = [];
    /** @var int $worldCacheSize */
    private int $worldCacheSize = 16;

    abstract public function getWorld(string $name) : ?WorldSettings;
    abstract public function addWorld(string $name, WorldSettings $settings) : bool;

    abstract public function close() : bool;

    protected function getWorldFromCache(string $name) : ?WorldSettings {
        if (isset($this->worldCache[$name])) {
            return $this->worldCache[$name];
        }
        return null;
    }

    protected function cacheWorld(string $name, WorldSettings $settings) : void {
        if (isset($this->worldCache[$name])) {
            unset($this->worldCache[$name]);
        } else if ($this->worldCacheSize <= count($this->worldCache)) {
            array_shift($this->worldCache);
        }
        $this->worldCache = array_merge([$name => clone $settings], $this->worldCache);
    }
}