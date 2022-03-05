<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\block\tile\TileFactory;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\event\Listener;
use pocketmine\event\world\ChunkPopulateEvent;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class ChunkPopulateListener implements Listener {

    public function onChunkPopulate(ChunkPopulateEvent $event) : void {
        Await::f2c(
            static function() use($event) : \Generator {
                $world = $event->getWorld();
                $worldName = $world->getFolderName();
                $worldSettings = yield from DataProvider::getInstance()->awaitWorld($worldName);
                if (!($worldSettings instanceof WorldSettings)) {
                    return;
                }
                $file = "worlds" . DIRECTORY_SEPARATOR . $worldName . DIRECTORY_SEPARATOR . World::chunkHash($event->getChunkX(), $event->getChunkZ()) . ".cplot_tile_entities";
                if (!(file_exists($file))) {
                    return;
                }
                $contents = file_get_contents($file);
                if ($contents === false) {
                    return;
                }
                $decompressed = zlib_decode($contents);
                if ($decompressed === false) {
                    return;
                }

                try {
                    $tiles = (new BigEndianNbtSerializer())->readMultiple($decompressed);
                } catch (NbtDataException) {
                    return;
                }
                $tileFactory = TileFactory::getInstance();
                foreach ($tiles as $coordinateHash => $tileTreeRoot) {
                    try {
                        $tileNBT = $tileTreeRoot->mustGetCompoundTag();
                        $tile = $tileFactory->createFromData($world, $tileNBT);
                    } catch(NbtDataException|SavedDataLoadingException $e) {
                        $world->getLogger()->error("Bad tile entity data at list position $coordinateHash: " . $e->getMessage());
                        $world->getLogger()->logException($e);
                        continue;
                    }
                    if ($tile === null) {
                        $world->getLogger()->warning("Deleted unknown tile entity type " . $tileNBT->getString("id", "<unknown>"));
                    } else if (!$world->isChunkLoaded($tile->getPosition()->getFloorX() >> Chunk::COORD_BIT_SIZE, $tile->getPosition()->getFloorZ() >> Chunk::COORD_BIT_SIZE)) {
                        $world->getLogger()->error("Found tile saved on wrong chunk - unable to fix due to correct chunk not loaded");
                    } else if ($world->getTile($tilePosition = $tile->getPosition()) !== null) {
                        $world->getLogger()->error("Cannot add tile at x=$tilePosition->x,y=$tilePosition->y,z=$tilePosition->z: Another tile is already at that position");
                    } else {
                        $world->addTile($tile);
                    }
                }
            }
        );
    }
}