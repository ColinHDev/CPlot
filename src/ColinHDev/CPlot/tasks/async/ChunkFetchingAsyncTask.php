<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

abstract class ChunkFetchingAsyncTask extends CPlotAsyncTask {

    private int $minY;
    private int $maxY;

    protected string $chunks;

    /**
     * @phpstan-param array<int, Chunk> $chunks
     */
    public function __construct(World $world, array $chunks) {
        parent::__construct();
        $this->minY = $world->getMinY();
        $this->maxY = $world->getMaxY();
        $serializedChunks = [];
        foreach ($chunks as $hash => $chunk) {
            $serializedChunks[$hash] = FastChunkSerializer::serializeTerrain($chunk);
        }
        $this->chunks = serialize($serializedChunks);
    }

    protected function getChunkManager() : SimpleChunkManager {
        $manager = new SimpleChunkManager($this->minY, $this->maxY);
        /** @phpstan-var array<int, string> $chunks */
        $chunks = unserialize($this->chunks, ["allowed_classes" => false]);
        foreach ($chunks as $hash => $chunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            $manager->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($chunk));
        }
        return $manager;
    }
}