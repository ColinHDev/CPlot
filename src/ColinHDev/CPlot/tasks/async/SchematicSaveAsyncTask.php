<?php

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlotAPI\worlds\schematics\Schematic;

class SchematicSaveAsyncTask extends ChunkFetchingAsyncTask {

    private string $name;
    private string $file;
    private string $type;

    private int $sizeRoad;
    private int $sizePlot;

    public function __construct(string $name, string $file, string $type, int $sizeRoad, int $sizePlot) {
        $this->startTime();
        $this->name = $name;
        $this->file = $file;
        $this->type = $type;
        $this->sizeRoad = $sizeRoad;
        $this->sizePlot = $sizePlot;
    }

    public function onRun() : void {
        $schematic = new Schematic($this->name, $this->file);
        $schematic->loadFromWorld($this->getChunkManager(), $this->type, $this->sizeRoad, $this->sizePlot);
        $schematic->save();

        $blocksCount = count($schematic->getFullBlocks());

        $bytes = $fileSize = filesize($this->file);
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