<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

/**
 * @phpstan-type ChunkHash int
 */
abstract class ChunkFetchingAsyncTask extends CPlotAsyncTask {

    private int $minY;
    private int $maxY;

    protected string $chunks;
    protected string $chunkAreas;

    /**
     * @phpstan-param array<ChunkHash, mixed> $chunkAreas
     */
    public function __construct(World $world, array $chunkAreas) {
        parent::__construct();
        $this->minY = $world->getMinY();
        $this->maxY = $world->getMaxY();
        $chunks = [];
        foreach ($chunkAreas as $chunkHash => $data) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);
            $chunk = $world->loadChunk($chunkX, $chunkZ);
            if ($chunk === null) {
                continue;
            }
            $chunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
        }
        $this->chunks = serialize($chunks);
        $this->chunkAreas = serialize($chunkAreas);
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