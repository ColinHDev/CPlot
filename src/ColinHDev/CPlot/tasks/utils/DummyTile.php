<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlot\worlds\schematic\Schematic;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\Position;

/**
 * @internal dummy class to store a tile's data without the need for a world context.
 * This class is used to allow the {@see Schematic::loadFromWorld()} method to be executed from the main thread, as well
 * as in an {@see AsyncTask}, while also providing tile support. Since the {@see FastChunkSerializer::serializeTerrain()}
 * method does not account for tiles, they can be reconstructed with the help of this class by using their NBT.
 */
class DummyTile extends Tile {

    private CompoundTag $nbt;

    public function __construct(CompoundTag $nbt) {
        $this->nbt = $nbt;
        $this->position = new Position($nbt->getInt(self::TAG_X), $nbt->getInt(self::TAG_Y), $nbt->getInt(self::TAG_Z), null);
    }

    public function saveNBT() : CompoundTag {
        return $this->nbt;
    }

    public function readSaveData(CompoundTag $nbt) : void {
    }

    protected function writeSaveData(CompoundTag $nbt) : void {
    }
}