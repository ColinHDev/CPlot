<?php

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

abstract class CPlotAsyncTask extends AsyncTask {

    private int $startTime;

    private int $worldId;
    private int $minY;
    private int $maxY;

    private ?string $chunks;

    protected bool $updateChunks = true;
    protected bool $hasCallback = false;

    protected function startTime() : void {
        $this->startTime = round(microtime(true) * 1000);
    }

    /**
     * @return int
     */
    protected function getElapsedTime() : int {
        return (round(microtime(true) * 1000)) - $this->startTime;
    }

    /**
     * @return string
     */
    protected function getElapsedTimeString() : string {
        $ms = $this->getElapsedTime();
        $min = floor($ms / 60000);
        $ms -= $min * 60000;
        $s = floor($ms / 1000);
        $ms -= $s * 1000;
        $time = "";
        if ($min > 0) {
            $time .= $min . "min";
        }
        if ($s > 0) {
            if ($time !== "") $time .= ", ";
            $time .= $s . "s";
        }
        if ($ms > 0) {
            if ($time !== "") $time .= ", ";
            $time .= $ms . "ms";
        }
        return $time;
    }

    /**
     * @param \Closure | null $closure
     */
    public function setClosure(?\Closure $closure) : void {
        if ($closure !== null) {
            $this->storeLocal("callback", $closure);
            $this->hasCallback = true;
        } else {
            $this->hasCallback = false;
        }
    }

    /**
     * @param World $world
     */
    public function setWorld(World $world) : void {
        $this->worldId = $world->getId();
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
     * @param array | Chunk[] $chunks
     */
    public function setChunks(array $chunks) : void {
        $serializedChunks = [];
        foreach ($chunks as $chunk) {
            $serializedChunks[World::chunkHash($chunk->getX(), $chunk->getZ())] = FastChunkSerializer::serialize($chunk);
        }
        $this->chunks = serialize($serializedChunks);
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
                $chunks[World::chunkHash($chunkX, $chunkZ)] = FastChunkSerializer::serializeWithoutLight($world->getChunk($chunkX, $chunkZ));
            }
        }

        $this->chunks = serialize($chunks);
    }

    public function onCompletion() : void {
        if ($this->updateChunks) {
            $world = Server::getInstance()->getWorldManager()->getWorld($this->worldId);
            foreach (unserialize($this->chunks) as $hash => $chunk) {
                World::getXZ($hash, $chunkX, $chunkZ);
                $world->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserialize($chunk), false);
            }
        }
        if ($this->hasCallback) {
            $this->fetchLocal("callback")($this->getElapsedTime(), $this->getElapsedTimeString(), $this->getResult());
        }
    }
}