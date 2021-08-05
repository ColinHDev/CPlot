<?php

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\Server;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

abstract class ChunkModifyingAsyncTask extends ChunkFetchingAsyncTask {

    private int $worldId;

    /**
     * @param World $world
     */
    public function setWorld(World $world) : void {
        $this->worldId = $world->getId();
        parent::setWorld($world);
    }

    /**
     * @param mixed $progress
     */
    public function onProgressUpdate(mixed $progress) : void {
        $world = Server::getInstance()->getWorldManager()->getWorld($this->worldId);

        $chunks = [];

        foreach ($progress as $chunkHash => $data) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);
            $chunk = $world->loadChunk($chunkX, $chunkZ);
            if ($chunk === null) continue;
            $chunks[$chunkHash] = FastChunkSerializer::serializeWithoutLight($chunk);
        }

        $this->chunks = serialize($chunks);
    }

    public function onCompletion() : void {
        $world = Server::getInstance()->getWorldManager()->getWorld($this->worldId);
        foreach (unserialize($this->chunks) as $hash => $chunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            $world->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserialize($chunk), false);
        }
        parent::onCompletion();
    }
}