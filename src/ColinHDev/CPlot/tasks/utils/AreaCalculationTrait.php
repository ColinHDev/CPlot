<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlot\math\Area;
use pocketmine\world\World;

trait AreaCalculationTrait {

    /**
     * @phpstan-param array<string, Area> $areas
     * @phpstan-param array<int, array<string, int[]>> $chunks
     */
    private function getChunksFromAreas(string $identifier, array $areas, array &$chunks) : void {
        foreach ($areas as $area) {
            for ($x = $area->getXMin(); $x <= $area->getXMax(); $x++) {
                for ($z = $area->getZMin(); $z <= $area->getZMax(); $z++) {
                    $chunkHash = World::chunkHash($x >> 4, $z >> 4);
                    $blockHash = World::chunkHash($x & 0x0f, $z & 0x0f);
                    if (!isset($chunks[$chunkHash])) {
                        $chunks[$chunkHash] = [];
                        $chunks[$chunkHash][$identifier] = [];
                    } else if (!isset($chunks[$chunkHash][$identifier])) {
                        $chunks[$chunkHash][$identifier] = [];
                    } else if (in_array($blockHash, $chunks[$chunkHash][$identifier], true)) {
                        continue;
                    }
                    $chunks[$chunkHash][$identifier][] = $blockHash;
                }
            }
        }
    }
}