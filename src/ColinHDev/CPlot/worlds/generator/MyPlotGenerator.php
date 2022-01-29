<?php

namespace ColinHDev\CPlot\worlds\generator;

use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class MyPlotGenerator extends Generator {

    public const GENERATOR_NAME = "myplot";

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}