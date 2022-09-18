<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\plots\utils\PlotModificationException;
use InvalidArgumentException;
use pocketmine\Server;
use pocketmine\world\ChunkLockId;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;
use function assert;
use function count;
use function unserialize;

abstract class ChunkModifyingAsyncTask extends ChunkFetchingAsyncTask {

    private int $worldID;
    private string $worldName;

    /**
     * @throws PlotModificationException if a required chunk is already locked.
     */
    public function __construct(World $world, array $chunkAreas) {
        $chunkLockId = new ChunkLockId();
        try {
            foreach ($chunkAreas as $chunkHash => $data) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);
                $world->lockChunk($chunkX, $chunkZ, $chunkLockId);
            }
        } catch(InvalidArgumentException $exception) {
            foreach ($chunkAreas as $chunkHash => $data) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);
                $world->unlockChunk($chunkX, $chunkZ, $chunkLockId);
            }
            throw new PlotModificationException(PlotModificationException::CHUNK_LOCKED, $exception->getMessage(), $exception);
        }
        $this->storeLocal("chunkLockId", $chunkLockId);
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

    public function getChunkLockId() : ChunkLockId {
        $chunkLockId = $this->fetchLocal("chunkLockId");
        assert($chunkLockId instanceof ChunkLockId);
        return $chunkLockId;
    }

    /**
     * Tries to set the modified chunks back into the world.
     * Returns true if the chunks were successfully set, false otherwise.
     * @throws PlotModificationException
     */
    public function applyModfiedChunksToWorld() : void {
        $worldManager = Server::getInstance()->getWorldManager();
        $world = $worldManager->getWorld($this->worldID);
        if ($world === null) {
            $worldManager->loadWorld($this->worldName);
            $world = $worldManager->getWorldByName($this->worldName);
        }
        if ($world === null) {
            throw new PlotModificationException(PlotModificationException::WORLD_NOT_LOADABLE, "World with ID {$this->worldID} and folder name {$this->worldName} could not be loaded.");
        }
        $chunkLockId = $this->getChunkLockId();
        $modifiedChunks = $this->getModifiedChunks();
        $alreadyUnlockedChunks = [];
        foreach ($modifiedChunks as $hash => $chunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            if ($world->unlockChunk($chunkX, $chunkZ, $chunkLockId) === false) {
                $alreadyUnlockedChunks[$hash] = true;
            }
        }
        // The exception can't be thrown earlier, in the loop, since that would stop the unlocking of the chunks. This
        // could leave some chunks permanently locked until the server is restarted.
        if (count($alreadyUnlockedChunks) > 0) {
            throw new PlotModificationException(
                PlotModificationException::CHUNK_LOCK_CHANGED,
                "A total of " . count($alreadyUnlockedChunks) . " chunk(s) were no longer locked under the same lock Id."
            );
        }
        foreach ($modifiedChunks as $hash => $chunk) {
            World::getXZ($hash, $chunkX, $chunkZ);
            $world->setChunk($chunkX, $chunkZ, $chunk);
        }
    }
}