<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\worlds\schematic\Schematic;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class SchematicSaveAsyncTask extends ChunkFetchingAsyncTask {

    private string $name;
    private string $file;
    private string $type;

    private int $sizeRoad;
    private int $sizePlot;

    public function __construct(World $world, Vector3 $pos1, Vector3 $pos2, string $name, string $file, string $type, int $sizeRoad, int $sizePlot) {
        $this->name = $name;
        $this->file = $file;
        $this->type = $type;
        $this->sizeRoad = $sizeRoad;
        $this->sizePlot = $sizePlot;

        $chunks = [];
        $minChunkX = min($pos1->x, $pos2->x) >> 4;
        $maxChunkX = max($pos1->x, $pos2->x) >> 4;
        $minChunkZ = min($pos1->z, $pos2->z) >> 4;
        $maxChunkZ = max($pos1->z, $pos2->z) >> 4;
        for ($chunkX = $minChunkX; $chunkX <= $maxChunkX; ++$chunkX) {
            for ($chunkZ = $minChunkZ; $chunkZ <= $maxChunkZ; ++$chunkZ) {
                $chunks[World::chunkHash($chunkX, $chunkZ)] = true;
            }
        }

        parent::__construct($world, $chunks);
    }

    public function onRun() : void {
        $schematic = new Schematic($this->name, $this->file);
        $schematic->loadFromWorld($this->getChunkManager(), $this->type, $this->sizeRoad, $this->sizePlot);
        $schematic->save();

        $blocksCount = $schematic->getBlockCount();

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
}