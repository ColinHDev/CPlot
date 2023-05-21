<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\tasks\utils\DummyTile;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\format\Chunk;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;

class SchematicSaveAsyncTask extends ChunkFetchingAsyncTask {

    private string $file;
    private string $type;

    private int $sizeRoad;
    private int $sizePlot;

    private string $tiles;

    public function __construct(World $world, Vector3 $pos1, Vector3 $pos2, string $file, string $type, int $sizeRoad, int $sizePlot) {
        $this->file = $file;
        $this->type = $type;
        $this->sizeRoad = $sizeRoad;
        $this->sizePlot = $sizePlot;

        $chunks = [];
        $tileTreeRoots = [];
        $minChunkX = min($pos1->x, $pos2->x) >> Chunk::COORD_BIT_SIZE;
        $maxChunkX = max($pos1->x, $pos2->x) >> Chunk::COORD_BIT_SIZE;
        $minChunkZ = min($pos1->z, $pos2->z) >> Chunk::COORD_BIT_SIZE;
        $maxChunkZ = max($pos1->z, $pos2->z) >> Chunk::COORD_BIT_SIZE;
        for ($chunkX = $minChunkX; $chunkX <= $maxChunkX; ++$chunkX) {
            for ($chunkZ = $minChunkZ; $chunkZ <= $maxChunkZ; ++$chunkZ) {
                $chunks[World::chunkHash($chunkX, $chunkZ)] = true;
                $chunk = $world->loadChunk($chunkX, $chunkZ);
                if (!($chunk instanceof Chunk)) {
                    continue;
                }
                foreach ($chunk->getTiles() as $coordinateHash => $tile) {
                    $tileTreeRoots[] = new TreeRoot($tile->saveNBT(), (string) $coordinateHash);
                }
            }
        }
        $tileTreeRootsEncoded = zlib_encode((new BigEndianNbtSerializer())->writeMultiple($tileTreeRoots), ZLIB_ENCODING_GZIP);
        assert(is_string($tileTreeRootsEncoded));
        $this->tiles = $tileTreeRootsEncoded;

        parent::__construct($world, $chunks);
    }

    public function onRun() : void {
        $schematic = new Schematic($this->file);
        $schematic->loadFromWorld($this->getChunkManager(), $this->type, $this->sizeRoad, $this->sizePlot);
        $schematic->save();

        $blocksCount = $schematic->calculateBlockCount();

        $bytes = $fileSize = filesize($this->file);
        assert($bytes !== false);
        $megabytes = floor($bytes / 1048576); // 1.048.576 = 1024 * 1024
        $bytes -= $megabytes * 1048576;
        $kilobytes = floor($bytes / 1024);
        $bytes -= $kilobytes * 1024;
        $fileSizeString = "";
        if ($megabytes > 0) {
            $fileSizeString .= $megabytes . " MiB";
        }
        if ($kilobytes > 0) {
            if ($fileSizeString !== "") $fileSizeString .= ", ";
            $fileSizeString .= $kilobytes . " KiB";
        }
        if ($bytes > 0) {
            if ($fileSizeString !== "") $fileSizeString .= ", ";
            $fileSizeString .= $bytes . " B";
        }

        $this->setResult([$blocksCount, $fileSize, $fileSizeString]);
    }

    protected function getChunkManager() : SimpleChunkManager {
        $manager = parent::getChunkManager();
        $decompressed = zlib_decode($this->tiles);
        if ($decompressed === false) {
            return $manager;
        }
        try {
            $tileTreeRoots = (new BigEndianNbtSerializer())->readMultiple($decompressed);
        } catch (NbtDataException) {
            return $manager;
        }
        foreach ($tileTreeRoots as $tileTreeRoot) {
            try {
                $tile = new DummyTile($tileTreeRoot->mustGetCompoundTag());
            } catch (NbtDataException|\InvalidArgumentException) {
                continue;
            }
            $position = $tile->getPosition();
            $chunk = $manager->getChunk($position->x >> Chunk::COORD_BIT_SIZE, $position->z >> Chunk::COORD_BIT_SIZE);
            assert($chunk instanceof Chunk);
            $chunk->addTile($tile);
        }
        return $manager;
    }
}