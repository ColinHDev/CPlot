<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\tasks\utils\PlotAreaCalculationTrait;
use ColinHDev\CPlot\tasks\utils\RoadAreaCalculationTrait;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

class PlotBiomeChangeAsyncTask extends ChunkModifyingAsyncTask {
    use PlotAreaCalculationTrait;
    use RoadAreaCalculationTrait;

    /** @phpstan-var BiomeIds::* */
    private int $biomeID;

    /**
     * @phpstan-param BiomeIds::* $biomeID
     */
    public function __construct(Plot $plot, int $biomeID) {
        $this->biomeID = $biomeID;

        $chunks = [];
        $this->getChunksFromAreas("", $this->calculateBasePlotAreas($plot->getWorldSettings(), $plot), $chunks);
        $this->getChunksFromAreas("", $this->calculateMergeRoadAreas($plot->getWorldSettings(), $plot), $chunks);

        $world = $plot->getWorld();
        assert($world instanceof World);
        parent::__construct($world, $chunks);
    }

    public function onRun() : void {
        $world = $this->getChunkManager();

        $finishedChunks = [];
        /** @phpstan-var array<int, array<string, int[]>> $chunkAreas */
        $chunkAreas = unserialize($this->chunkAreas, ["allowed_classes" => false]);
        foreach ($chunkAreas as $chunkHash => $blockHashs) {

            World::getXZ($chunkHash, $chunkX, $chunkZ);
            $chunk = $world->getChunk($chunkX, $chunkZ);
            assert($chunk instanceof Chunk);

            foreach ($blockHashs[""] as $blockHash) {
                World::getXZ($blockHash, $xInChunk, $zInChunk);
                for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                    $chunk->setBiomeId($xInChunk, $y, $zInChunk, $this->biomeID);
                }
            }

            $finishedChunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
        }

        $this->chunks = serialize($finishedChunks);
    }

    /**
     * @phpstan-return BiomeIds::*
     */
    public function getBiomeID() : int {
        return $this->biomeID;
    }
}