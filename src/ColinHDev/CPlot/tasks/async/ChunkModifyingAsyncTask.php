<?php

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\Server;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

abstract class ChunkModifyingAsyncTask extends ChunkFetchingAsyncTask {

    private int $worldID;
    private string $worldName;

    public function __construct(World $world, array $chunkAreas) {
        parent::__construct($world, $chunkAreas);
        $this->worldID = $world->getId();
        $this->worldName = $world->getFolderName();
    }

    public function onCompletion() : void {
        $worldManager = Server::getInstance()->getWorldManager();
        $world = $worldManager->getWorld($this->worldID);
        if ($world === null) {
            $worldManager->loadWorld($this->worldName);
            $world = $worldManager->getWorldByName($this->worldName);
        }
        if ($world !== null) {
            foreach (unserialize($this->chunks, false) as $hash => $chunk) {
                World::getXZ($hash, $chunkX, $chunkZ);
                $world->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($chunk));
            }
        }
        parent::onCompletion();
    }
}