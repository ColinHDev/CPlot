<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds\schematic;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\NbtException;
use pocketmine\nbt\NoSuchTagException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\UnexpectedTagTypeException;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class Schematic implements SchematicTypes {

    public const FILE_EXTENSION = "cplot_schematic";

    public const SCHEMATIC_VERSION = 3;

    private string $name;
    private string $file;
    private int $version;
    private int $creationTime;
    private string $type;

    private int $roadSize;
    private int $plotSize;

    /** @phpstan-var array<int, BiomeIds::*> */
    private array $biomeIDs = [];

    /** @var array<int, int> */
    private array $blockStateIDs = [];

    /** @phpstan-var array<int, CompoundTag> */
    private array $tiles = [];

    public function __construct(string $name, string $file) {
        $this->name = $name;
        $this->file = $file;
    }

    public function save() : bool {
        $nbt = new CompoundTag();

        $nbt->setShort("Version", $this->version);
        $nbt->setLong("CreationTime", $this->creationTime);
        $nbt->setString("Type", $this->type);
        $nbt->setShort("RoadSize", $this->roadSize);
        $nbt->setShort("PlotSize", $this->plotSize);

        $biomeTreeRoots = [];
        foreach ($this->biomeIDs as $coordinateHash => $biomeID) {
            $biomeNBT = new CompoundTag();
            $biomeNBT->setShort("ID", $biomeID);
            World::getXZ($coordinateHash, $x, $z);
            $biomeNBT->setShort("X", $x);
            $biomeNBT->setShort("Z", $z);
            $biomeTreeRoots[] = new TreeRoot($biomeNBT, (string) $coordinateHash);
        }
        $biomeTreeRootsEncoded = zlib_encode((new BigEndianNbtSerializer())->writeMultiple($biomeTreeRoots), ZLIB_ENCODING_GZIP);
        assert(is_string($biomeTreeRootsEncoded));
        $nbt->setByteArray("Biomes", $biomeTreeRootsEncoded);

        $blockTreeRoots = [];
        $blockStateSerializer = GlobalBlockStateHandlers::getSerializer();
        foreach ($this->blockStateIDs as $coordinateHash => $blockStateID) {
            $blockNBT = new CompoundTag();

            $blockNBT->setTag("NBT", $blockStateSerializer->serialize($blockStateID)->toNbt());

            World::getBlockXYZ($coordinateHash, $x, $y, $z);
            $blockNBT->setShort("X", $x);
            $blockNBT->setShort("Y", $y);
            $blockNBT->setShort("Z", $z);

            $blockTreeRoots[] = new TreeRoot($blockNBT, (string) $coordinateHash);
        }
        $blockTreeRootsEncoded = zlib_encode((new BigEndianNbtSerializer())->writeMultiple($blockTreeRoots), ZLIB_ENCODING_GZIP);
        assert(is_string($blockTreeRootsEncoded));
        $nbt->setByteArray("Blocks", $blockTreeRootsEncoded);

        $tileTreeRoots = [];
        foreach ($this->tiles as $coordinateHash => $tileNBT) {
            $tileTreeRoots[] = new TreeRoot($tileNBT, (string) $coordinateHash);
        }
        $tileTreeRootsEncoded = zlib_encode((new BigEndianNbtSerializer())->writeMultiple($tileTreeRoots), ZLIB_ENCODING_GZIP);
        assert(is_string($tileTreeRootsEncoded));
        $nbt->setByteArray("TileEntities", $tileTreeRootsEncoded);

        file_put_contents($this->file, zlib_encode((new BigEndianNbtSerializer())->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP));
        return true;
    }

    public function loadFromFile() : bool {
        if (!file_exists($this->file)) {
            return false;
        }
        $contents = file_get_contents($this->file);
        if ($contents === false) {
            return false;
        }
        $decompressed = zlib_decode($contents);
        if ($decompressed === false) {
            return false;
        }

        try {
            $nbt = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        } catch (NbtDataException) {
            return false;
        }

        $this->version = $nbt->getShort("Version");
        $this->creationTime = $nbt->getLong("CreationTime");
        $this->type = $nbt->getString("Type");
        $this->roadSize = $nbt->getShort("RoadSize");
        $this->plotSize = $nbt->getShort("PlotSize");

        // Loading biome data
        switch($this->version) {
            // Biome data was not stored in schematic version 1
            case 2:
            case 3:
                foreach ($this->readTreeRoots($nbt, "Biomes") as $biomeTreeRoots) {
                    try {
                        $biomeNBT = $biomeTreeRoots->mustGetCompoundTag();
                        $coordinateHash = World::chunkHash($biomeNBT->getShort("X"), $biomeNBT->getShort("Z"));
                    } catch (NbtException) {
                        continue;
                    }
                    /** @phpstan-var BiomeIds::* $biomeID */
                    $biomeID = $biomeNBT->getShort("ID");
                    $this->biomeIDs[$coordinateHash] = $biomeID;
                }
                break;
        }

        // Loading block data
        $blockDataUpgrader = GlobalBlockStateHandlers::getUpgrader();
        $blockStateDeserializer = GlobalBlockStateHandlers::getDeserializer();
        switch ($this->version) {
            case 1:
                foreach ($this->readTreeRoots($nbt, "Blocks") as $blockTreeRoot) {
                    try {
                        $blockNBT = $blockTreeRoot->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($blockNBT->getShort("X"), $blockNBT->getShort("Y"), $blockNBT->getShort("Z"));
                        $ID = $blockNBT->getInt("ID");
                        $meta = $blockNBT->getShort("Meta");
                    } catch (NbtException) {
                        continue;
                    }
                    $blockStateData = $blockDataUpgrader->upgradeIntIdMeta($ID, $meta);
                    if ($blockStateData instanceof BlockStateData) {
                        try {
                            $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize($blockStateData);
                            continue;
                        } catch(BlockStateDeserializeException) {
                        }
                    }
                    $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
                }
                break;

            case 2:
                foreach ($this->readTreeRoots($nbt, "Blocks") as $blockTreeRoot) {
                    try {
                        $blockNBT = $blockTreeRoot->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($blockNBT->getShort("X"), $blockNBT->getShort("Y"), $blockNBT->getShort("Z"));
                    } catch (NbtException|InvalidArgumentException) {
                        continue;
                    }
                    // Schematic version 2 did not store block IDs or Metas that were 0
                    try {
                        $ID = $blockNBT->getInt("ID");
                    } catch(UnexpectedTagTypeException|NoSuchTagException) {
                        $ID = 0;
                    }
                    try {
                        $meta = $blockNBT->getShort("Meta");
                    } catch(UnexpectedTagTypeException|NoSuchTagException) {
                        $meta = 0;
                    }
                    $blockStateData = $blockDataUpgrader->upgradeIntIdMeta($ID, $meta);
                    if ($blockStateData instanceof BlockStateData) {
                        try {
                            $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize($blockStateData);
                            continue;
                        } catch(BlockStateDeserializeException) {
                        }
                    }
                    $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
                }
                break;

            case 3:
                foreach ($this->readTreeRoots($nbt, "Blocks") as $blockTreeRoot) {
                    try {
                        $blockNBT = $blockTreeRoot->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($blockNBT->getShort("X"), $blockNBT->getShort("Y"), $blockNBT->getShort("Z"));
                        $tag = $blockNBT->getCompoundTag("NBT");
                    } catch (NbtDataException) {
                        continue;
                    }
                    if ($tag instanceof CompoundTag) {
                        try {
                            $blockStateData = BlockStateData::fromNbt($tag);
                            $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize($blockStateData);
                            continue;
                        } catch(BlockStateDeserializeException) {
                        }
                    }
                    $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
                }
                break;
        }

        // Loading tile data
        switch($this->version) {
            // Tile data was not stored in schematic version 1
            case 2:
            case 3:
                foreach ($this->readTreeRoots($nbt, "TileEntities") as $tileTreeRoot) {
                    try {
                        $tileNBT = $tileTreeRoot->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($tileNBT->getInt(Tile::TAG_X), $tileNBT->getInt(Tile::TAG_Y), $tileNBT->getInt(Tile::TAG_Z));
                    } catch (NbtException|InvalidArgumentException) {
                        continue;
                    }
                    $this->tiles[$coordinateHash] = $tileNBT;
                }
                break;
        }
        return true;
    }

    /**
     * @phpstan-return array<int, TreeRoot>
     */
    private function readTreeRoots(CompoundTag $nbt, string $key) : array {
        try {
            $compressed = $nbt->getByteArray($key);
        } catch (UnexpectedTagTypeException|NoSuchTagException) {
            return [];
        }
        $decompressed = zlib_decode($compressed);
        if ($decompressed === false) {
            return [];
        }
        try {
            return (new BigEndianNbtSerializer())->readMultiple($decompressed);
        } catch (NbtDataException) {
            return [];
        }
    }

    public function loadFromWorld(ChunkManager $world, string $type, int $roadSize, int $plotSize) : bool {
        $this->version = self::SCHEMATIC_VERSION;
        $this->creationTime = time();
        $this->type = $type;
        $this->roadSize = $roadSize;
        $this->plotSize = $plotSize;

        $explorer = new SubChunkExplorer($world);

        switch ($this->type) {
            case SchematicTypes::TYPE_ROAD:
                $totalSize = $this->roadSize + $this->plotSize;
                for ($x = 0; $x < $totalSize; $x++) {
                    for ($z = 0; $z < $totalSize; $z++) {
                        if ($x >= $this->roadSize && $z >= $this->roadSize) {
                            continue 2;
                        }
                        $this->loadFromColumn($world, $explorer, $x, $z);
                    }
                }
                break;
            case SchematicTypes::TYPE_PLOT:
                for ($x = 0; $x < $this->plotSize; $x++) {
                    for ($z = 0; $z < $this->plotSize; $z++) {
                        $this->loadFromColumn($world, $explorer, $x, $z);
                    }
                }
                break;
            default:
                return false;
        }
        return true;
    }

    private function loadFromColumn(ChunkManager $world, SubChunkExplorer $explorer, int $x, int $z) : void {
        $xInChunk = $x & SubChunk::COORD_MASK;
        $zInChunk = $z & SubChunk::COORD_MASK;
        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
            $explorer->moveTo($x, $y, $z);
            $coordinateHash = World::blockHash($x, $y, $z);
            if ($explorer->currentSubChunk instanceof SubChunk) {
                $blockStateID = $explorer->currentSubChunk->getFullBlock($xInChunk, $y & SubChunk::COORD_MASK, $zInChunk);
                $blockID = $blockStateID >> Block::INTERNAL_STATE_DATA_BITS;
                $blockMeta = $blockStateID & Block::INTERNAL_STATE_DATA_MASK;
                // We don't store generic air blocks to reduce the size of our array and our schematic file.
                if ($blockID === BlockTypeIds::AIR && $blockMeta === 0) {
                    continue;
                }
                $this->blockStateIDs[$coordinateHash] = $blockStateID;
            }
            if ($explorer->currentChunk instanceof Chunk) {
                $tile = $explorer->currentChunk->getTile($xInChunk, $y, $zInChunk);
                if ($tile instanceof Tile) {
                    $this->tiles[$coordinateHash] = $tile->saveNBT();
                }
            }
        }
        /** @phpstan-var BiomeIds::* $biomeID */
        $biomeID = $explorer->currentChunk->getBiomeId($xInChunk, $zInChunk);
        $this->biomeIDs[World::chunkHash($x, $z)] = $biomeID;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getFile() : string {
        return $this->file;
    }

    public function getVersion() : int {
        return $this->version;
    }

    public function getCreationTime() : int {
        return $this->creationTime;
    }

    public function getType() : string {
        return $this->type;
    }

    public function getRoadSize() : int {
        return $this->roadSize;
    }

    public function getPlotSize() : int {
        return $this->plotSize;
    }

    public function getBiomeID(int $x, int $z) : int {
        return $this->biomeIDs[World::chunkHash($x, $z)] ?? BiomeIds::PLAINS;
    }

    public function getBlockStateID(int $x, int $y, int $z) : int {
        $coordinateHash = World::blockHash($x, $y, $z);
        if (isset($this->blockStateIDs[$coordinateHash])) {
            return $this->blockStateIDs[$coordinateHash];
        }
        static $airBlockStateID = (BlockTypeIds::AIR << Block::INTERNAL_STATE_DATA_BITS) | 0;
        return $airBlockStateID;
    }

    public function getTileCompoundTag(int $x, int $y, int $z) : ?CompoundTag {
        $coordinateHash = World::blockHash($x, $y, $z);
        return isset($this->tiles[$coordinateHash]) ? clone $this->tiles[$coordinateHash] : null;
    }

    public function calculateBiomeCount() : int {
        return match ($this->type) {
            SchematicTypes::TYPE_ROAD => $this->roadSize ** 2 + 2 * $this->roadSize * $this->plotSize,
            SchematicTypes::TYPE_PLOT => $this->plotSize ** 2,
            default => 0
        };
    }

    public function calculateBlockCount() : int {
        return $this->calculateBiomeCount() * (World::Y_MAX - World::Y_MIN);
    }

    public function calculateTileCount() : int {
        return count($this->tiles);
    }
}