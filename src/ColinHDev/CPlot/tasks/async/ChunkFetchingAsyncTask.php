<?php

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\math\Vector3;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

abstract class ChunkFetchingAsyncTask extends CPlotAsyncTask {

    private int $minY;
    private int $maxY;

    protected ?string $chunks;

    /**
     * @param World $world
     */
    public function setWorld(World $world) : void {
        $this->minY = $world->getMinY();
        $this->maxY = $world->getMaxY();
    }

    /**
     * @return SimpleChunkManager
     */
    protected function getChunkManager() : SimpleChunkManager {
        $manager = new SimpleChunkManager($this->minY, $this->maxY);
        foreach (unserialize($this->chunks) as $hash => $serializedChunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            $manager->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserialize($serializedChunk));
        }
        return $manager;
    }

    /**
     * @param SimpleChunkManager    $world
     * @param Vector3               $pos1
     * @param Vector3               $pos2
     */
    public function saveChunks(ChunkManager $world, Vector3 $pos1, Vector3 $pos2) : void {
        $minChunkX = min($pos1->x, $pos2->x) >> 4;
        $maxChunkX = max($pos1->x, $pos2->x) >> 4;
        $minChunkZ = min($pos1->z, $pos2->z) >> 4;
        $maxChunkZ = max($pos1->z, $pos2->z) >> 4;

        $chunks = [];

        for ($chunkX = $minChunkX; $chunkX <= $maxChunkX; ++$chunkX) {
            for ($chunkZ = $minChunkZ; $chunkZ <= $maxChunkZ; ++$chunkZ) {
                if ($world instanceof World) {
                    $chunk = $world->loadChunk($chunkX, $chunkZ);
                } else {
                    $chunk = $world->getChunk($chunkX, $chunkZ);
                }
                $chunks[World::chunkHash($chunkX, $chunkZ)] = FastChunkSerializer::serializeWithoutLight($chunk);
            }
        }

        $this->chunks = serialize($chunks);
    }
}