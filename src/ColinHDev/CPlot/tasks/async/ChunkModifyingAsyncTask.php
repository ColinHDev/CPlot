<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;
use function unserialize;

abstract class ChunkModifyingAsyncTask extends ChunkFetchingAsyncTask {

    private int $worldID;
    private string $worldName;

    public function __construct(World $world, array $chunkAreas) {
        parent::__construct($world, $chunkAreas);
        $this->worldID = $world->getId();
        $this->worldName = $world->getFolderName();
    }

    /**
     * Returns the chunks, that got asynchronously modified by this task, mapped by their hash.
     * Calling this on an unfinished task will result in the method returning the original, unmodified chunks.
     * @return array<int, Chunk>
     */
    public function getModifiedChunks() : array {
        $chunks = [];
        /** @var array<int, string> $serializedChunks */
        $serializedChunks = unserialize($this->chunks, ["allowed_classes" => false]);
        foreach ($serializedChunks as $hash => $chunk) {
            $chunks[$hash] = FastChunkSerializer::deserializeTerrain($chunk);
        }
        return $chunks;
    }

    /**
     * Tries to set the modified chunks back into the world.
     * Returns true if the chunks were successfully set, false otherwise.
     * @return bool
     */
    public function applyModfiedChunksToWorld() : bool {
        $worldManager = Server::getInstance()->getWorldManager();
        $world = $worldManager->getWorld($this->worldID);
        if ($world === null) {
            $worldManager->loadWorld($this->worldName);
            $world = $worldManager->getWorldByName($this->worldName);
        }
        if ($world !== null) {
            foreach ($this->getModifiedChunks() as $hash => $chunk) {
                World::getXZ($hash, $chunkX, $chunkZ);
                $world->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($chunk));
            }
            return true;
        }
        return false;
    }
}