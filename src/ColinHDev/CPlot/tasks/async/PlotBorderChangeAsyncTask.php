<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\tasks\utils\PlotBorderAreaCalculationTrait;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class PlotBorderChangeAsyncTask extends ChunkModifyingAsyncTask {
    use PlotBorderAreaCalculationTrait;

    private string $worldSettings;
    private int $blockFullID;

    public function __construct(World $world, WorldSettings $worldSettings, Plot $plot, Block $block) {
        $this->worldSettings = serialize($worldSettings->toArray());
        $this->blockFullID = $block->getFullId();

        $chunks = [];
        $this->getChunksFromAreas("borderChange", $this->calculatePlotBorderAreas($worldSettings, $plot), $chunks);
        $this->getChunksFromAreas("borderReset", $this->calculatePlotBorderExtensionAreas($worldSettings, $plot), $chunks);

        parent::__construct($world, $chunks);
    }

    public function onRun() : void {
        $worldSettings = WorldSettings::fromArray(unserialize($this->worldSettings, ["allowed_classes" => false]));

        $schematicRoad = null;
        if ($worldSettings->getRoadSchematic() !== "default") {
            $schematicRoad = new Schematic($worldSettings->getRoadSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getRoadSchematic() . "." . Schematic::FILE_EXTENSION);
            if (!$schematicRoad->loadFromFile()) {
                $schematicRoad = null;
            }
        }

        $world = $this->getChunkManager();
        $explorer = new SubChunkExplorer($world);
        $finishedChunks = [];
        $y = $worldSettings->getGroundSize() + 1;
        $yInChunk = $y & 0x0f;
        foreach (unserialize($this->chunkAreas, ["allowed_classes" => false]) as $chunkHash => $blockHashs) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            if (isset($blockHashs["borderChange"])) {
                foreach ($blockHashs["borderChange"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    $explorer->moveTo($x, $y, $z);
                    if ($explorer->currentSubChunk instanceof SubChunk) {
                        $explorer->currentSubChunk->setFullBlock(
                            $xInChunk,
                            $yInChunk,
                            $zInChunk,
                            $this->blockFullID
                        );
                    }
                }
            }

            if (isset($blockHashs["borderReset"])) {
                foreach ($blockHashs["borderReset"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    if ($schematicRoad !== null) {
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        $explorer->moveTo($x, $y, $z);
                        if ($explorer->currentSubChunk instanceof SubChunk) {
                            $explorer->currentSubChunk->setFullBlock(
                                $xInChunk,
                                $yInChunk,
                                $zInChunk,
                                $schematicRoad->getFullBlock($xRaster, $y, $zRaster)
                            );
                        }
                    } else {
                        $explorer->moveTo($x, $y, $z);
                        if ($explorer->currentSubChunk instanceof SubChunk) {
                            $explorer->currentSubChunk->setFullBlock(
                                $xInChunk,
                                $yInChunk,
                                $zInChunk,
                                0
                            );
                        }
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