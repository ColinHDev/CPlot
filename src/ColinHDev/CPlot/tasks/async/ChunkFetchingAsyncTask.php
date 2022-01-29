<?php

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

abstract class ChunkFetchingAsyncTask extends CPlotAsyncTask {

    private int $minY;
    private int $maxY;

    protected string $chunks;
    protected string $chunkAreas;

    /**
     * @phpstan-param array<int, array<string, int[]>> $chunkAreas
     */
    public function __construct(World $world, array $chunkAreas) {
        parent::__construct();
        $this->minY = $world->getMinY();
        $this->maxY = $world->getMaxY();
        foreach ($chunkAreas as $chunkHash => $data) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);
            $chunk = $world->loadChunk($chunkX, $chunkZ);
            if ($chunk === null) {
                continue;
            }
            $chunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
        }
        $this->chunkAreas = serialize($chunkAreas);
    }

    protected function getChunkManager() : SimpleChunkManager {
        $manager = new SimpleChunkManager($this->minY, $this->maxY);
        foreach (unserialize($this->chunks, false) as $hash => $serializedChunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            $manager->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($serializedChunk));
        }
        return $manager;
    }
}