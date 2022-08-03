<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\Server;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;
use RuntimeException;

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
        if ($world === null) {
            // We throw an exception here, since we can't let the task continue as being successful, because it
            // certainly is not if the world could not be loaded to update the chunks.
            throw new RuntimeException("Could not load world \"" . $this->worldName . "\" after completion of " . static::class . " for setting back the modified chunks.");
        }
        /** @phpstan-var array<int, string> $chunks */
        $chunks = unserialize($this->chunks, ["allowed_classes" => false]);
        foreach ($chunks as $hash => $chunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            $world->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($chunk));
        }
        parent::onCompletion();
    }
}