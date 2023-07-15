<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\tasks\utils\PlotBorderAreaCalculationTrait;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class PlotWallChangeAsyncTask extends ChunkModifyingAsyncTask {
    use PlotBorderAreaCalculationTrait;

    private int $blockFullID;
    private int $groundSize;

    public function __construct(Plot $plot, Block $block) {
        $this->blockFullID = $block->getStateId();
        $worldSettings = $plot->getWorldSettings();
        $this->groundSize = $worldSettings->getGroundSize();

        $chunks = [];
        $this->getChunksFromAreas("", $this->calculatePlotBorderAreas($worldSettings, $plot), $chunks);

        $world = $plot->getWorld();
        assert($world instanceof World);
        parent::__construct($world, $chunks);
    }

    public function onRun() : void {
        $world = $this->getChunkManager();
        $explorer = new SubChunkExplorer($world);
        $finishedChunks = [];
        /** @phpstan-var array<int, array<string, int[]>> $chunkAreas */
        $chunkAreas = unserialize($this->chunkAreas, ["allowed_classes" => false]);
        foreach ($chunkAreas as $chunkHash => $blockHashs) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            foreach ($blockHashs[""] as $blockHash) {
                World::getXZ($blockHash, $xInChunk, $zInChunk);
                $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                for ($y = $world->getMinY() + 1; $y <= $this->groundSize; $y++) {
                    $explorer->moveTo($x, $y, $z);
                    if ($explorer->currentSubChunk instanceof SubChunk) {
                        $explorer->currentSubChunk->setBlockStateId(
                            $xInChunk,
                            ($y & 0x0f),
                            $zInChunk,
                            $this->blockFullID
                        );
                    }
                }
            }

            $chunk = $world->getChunk($chunkX, $chunkZ);
            assert($chunk instanceof Chunk);
            $finishedChunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
        }

        $this->chunks = serialize($finishedChunks);
    }
}