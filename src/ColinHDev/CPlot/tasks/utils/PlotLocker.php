<?php

namespace ColinHDev\CPlot\tasks\utils;

use pocketmine\utils\SingletonTrait;

class PlotLocker implements Locker {

    use SingletonTrait;

    /** @var array<int | string, string> */
    private array $entries = [];

    public function isEntryLocked(int | string $identifier) : ?string {
        if (!isset($this->entries[$identifier])) return null;
        return $this->entries[$identifier];
    }

    public function addEntry(int | string $identifier, string $cause) : void {
        $this->entries[$identifier] = $cause;
    }

    public function removeEntry(int | string $identifier) : void {
        unset($this->entries[$identifier]);
    }
}