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
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\utils\BinaryStream;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;
use RuntimeException;
use function file_exists;
use function file_put_contents;
use function pathinfo;
use function strlen;
use function zlib_encode;
use const DIRECTORY_SEPARATOR;
use const ZLIB_ENCODING_GZIP;

class Schematic implements SchematicTypes {

    public const FILE_EXTENSION = "cplot_schematic";

    public const SCHEMATIC_VERSION = 5;

    private string $file;
    private int $version;
    private int $creationTime;
    /** @phpstan-var SchematicTypes::* */
    private string $type;

    private int $roadSize;
    private int $plotSize;

    /** @phpstan-var array<int, BiomeIds::*> */
    private array $biomeIDs = [];

    /** @var array<int, int> */
    private array $blockStateIDs = [];

    /** @var array<int, CompoundTag> */
    private array $tiles = [];

    /**
     * @param string $file The file this schematic can be loaded from and /or will be saved to.
     */
    public function __construct(string $file) {
        $this->file = $file;
    }

    /**
     * Saves the schematic to the given file.
     * If the file already exists, it won't be overwritten but instead, the old file will be renamed for backup reasons,
     * e.g. "road.cplot_schematic" will be renamed to "road_old.cplot_schematic".
     */
    public function save() : void {
        $nbt = new CompoundTag();
        $nbtSerializer = new BigEndianNbtSerializer();

        // Schematic versions indicate how the schematic file's contained data should be parsed. By calling the save()
        // method, the schematic file is always written according to the current version, basically upgrading it.
        // Because of that, we need to update its version, in case someone calls this on an old schematic.
        $this->version = self::SCHEMATIC_VERSION;
        
        $nbt->setShort("Version", $this->version);
        $nbt->setLong("CreationTime", $this->creationTime);
        $nbt->setString("Type", $this->type);
        $nbt->setShort("RoadSize", $this->roadSize);
        $nbt->setShort("PlotSize", $this->plotSize);

        $stream = new BinaryStream();
        foreach ($this->biomeIDs as $coordinateHash => $biomeID) {
            World::getBlockXYZ($coordinateHash, $x, $y, $z);
            $stream->putShort($x);
            $stream->putShort($y);
            $stream->putShort($z);
            $stream->putShort($biomeID);
        }
        $nbt->setByteArray("Biomes", $stream->getBuffer());

        $stream = new BinaryStream();
        $blockStateSerializer = GlobalBlockStateHandlers::getSerializer();
        foreach ($this->blockStateIDs as $coordinateHash => $blockStateID) {
            World::getBlockXYZ($coordinateHash, $x, $y, $z);
            $stream->putShort($x);
            $stream->putShort($y);
            $stream->putShort($z);
            $stream->put($nbtSerializer->write(new TreeRoot(
                $blockStateSerializer->serialize($blockStateID)->toNbt()
            )));
        }
        $nbt->setByteArray("Blocks", $stream->getBuffer());

        $stream = new BinaryStream();
        foreach ($this->tiles as $tileNBT) {
            $stream->put($nbtSerializer->write(new TreeRoot($tileNBT)));
        }
        $nbt->setByteArray("TileEntities", $stream->getBuffer());

        if (file_exists($this->file)) {
            $pathInfo = pathinfo($this->file);
            rename(
                $this->file,
                ($pathInfo["dirname"] ?? "") . DIRECTORY_SEPARATOR . $pathInfo["filename"] . "_old." . ($pathInfo["extension"] ?? self::FILE_EXTENSION)
            );
        }
        $fileContents = zlib_encode($nbtSerializer->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);
        if ($fileContents === false) {
            throw new RuntimeException("The schematic data could not be compressed.");
        }
        if (file_put_contents($this->file, $fileContents) === false) {
            throw new RuntimeException("The schematic file \"" . $this->file . "\" could not be written.");
        }
    }

    /**
     * Loads the schematic from the given file.
     * @throws RuntimeException if the schematic could not be loaded
     */
    public function loadFromFile() : void {
        if (!file_exists($this->file)) {
            throw new RuntimeException("Schematic file \"" . $this->file . "\" does not exist.");
        }
        $contents = file_get_contents($this->file);
        if ($contents === false) {
            throw new RuntimeException("Schematic file \"" . $this->file . "\" could not be read.");
        }
        $decompressed = zlib_decode($contents);
        if ($decompressed === false) {
            throw new RuntimeException("The data in the schematic file \"" . $this->file . "\" could not be decoded.");
        }
        try {
            $nbt = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        } catch (NbtDataException $e) {
            throw new RuntimeException("The data in the schematic file \"" . $this->file . "\" could not be deserialized into an NBT tag.", 0, $e);
        }

        $this->version = $nbt->getShort("Version");
        if ($this->version < 1 || $this->version > self::SCHEMATIC_VERSION) {
            throw new RuntimeException("The given version \"" . $this->version . "\" of the schematic \"" . $this->getName() . "\" is not supported.");
        }
        $this->creationTime = $nbt->getLong("CreationTime");
        $type = $nbt->getString("Type");
        if ($type !== SchematicTypes::TYPE_ROAD && $type !== SchematicTypes::TYPE_PLOT) {
            throw new RuntimeException("The given type \"" . $type . "\" of the schematic \"" . $this->getName() . "\" is not supported.");
        }
        $this->type = $type;
        $this->roadSize = $nbt->getShort("RoadSize");
        $this->plotSize = $nbt->getShort("PlotSize");

        // Loading biome data
        switch($this->version) {
            // Biome data was not stored in schematic version 1
            case 2:
            case 3:
                foreach (self::readTreeRoots($nbt, "Biomes") as $biomeTreeRoots) {
                    try {
                        $biomeNBT = $biomeTreeRoots->mustGetCompoundTag();
                        $x = $biomeNBT->getShort("X");
                        $z = $biomeNBT->getShort("Z");
                    } catch (NbtException) {
                        continue;
                    }
                    /** @phpstan-var BiomeIds::* $biomeID */
                    $biomeID = $biomeNBT->getShort("ID");
                    // version 2 and 3 only saved one biome ID for each x,z column
                    for ($y = World::Y_MIN; $y < World::Y_MAX; $y++) {
                        $this->biomeIDs[World::blockHash($x, $y, $z)] = $biomeID;
                    }
                }
                break;
            case 4:
                foreach (self::readTreeRoots($nbt, "Biomes") as $biomeTreeRoots) {
                    try {
                        $biomeNBT = $biomeTreeRoots->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($biomeNBT->getShort("X"), $biomeNBT->getShort("Y"), $biomeNBT->getShort("Z"));
                    } catch (NbtException) {
                        continue;
                    }
                    /** @phpstan-var BiomeIds::* $biomeID */
                    $biomeID = $biomeNBT->getShort("ID");
                    $this->biomeIDs[$coordinateHash] = $biomeID;
                }
                break;
            case 5:
                $buffer = $nbt->getByteArray("Biomes");
                $bufferLength = strlen($buffer);
                $stream = new BinaryStream($buffer);
                while ($stream->getOffset() < $bufferLength) {
                    $coordinateHash = World::blockHash($stream->getShort(), $stream->getSignedShort(), $stream->getShort());
                    /** @phpstan-var BiomeIds::* $biomeID */
                    $biomeID = $stream->getShort();
                    $this->biomeIDs[$coordinateHash] = $biomeID;
                }
                break;
        }

        // Loading block data
        $blockDataUpgrader = GlobalBlockStateHandlers::getUpgrader();
        $blockStateDeserializer = GlobalBlockStateHandlers::getDeserializer();
        switch ($this->version) {
            case 1:
                foreach (self::readTreeRoots($nbt, "Blocks") as $blockTreeRoot) {
                    try {
                        $blockNBT = $blockTreeRoot->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($blockNBT->getShort("X"), $blockNBT->getShort("Y"), $blockNBT->getShort("Z"));
                        $ID = $blockNBT->getInt("ID");
                        $meta = $blockNBT->getShort("Meta");
                    } catch (NbtException) {
                        continue;
                    }
                    try {
                        $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(
                            $blockDataUpgrader->upgradeIntIdMeta($ID, $meta)
                        );
                        continue;
                    } catch(BlockStateDeserializeException) {
                    }
                    $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
                }
                break;

            case 2:
                foreach (self::readTreeRoots($nbt, "Blocks") as $blockTreeRoot) {
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
                    try {
                        $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(
                            $blockDataUpgrader->upgradeIntIdMeta($ID, $meta)
                        );
                        continue;
                    } catch(BlockStateDeserializeException) {
                    }
                    $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
                }
                break;

            case 3:
            case 4:
                foreach (self::readTreeRoots($nbt, "Blocks") as $blockTreeRoot) {
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
                
            case 5:
                $buffer = $nbt->getByteArray("Blocks");
                $bufferLength = strlen($buffer);
                $stream = new BinaryStream($buffer);
                while ($stream->getOffset() < $bufferLength) {
                    $coordinateHash = World::blockHash($stream->getShort(), $stream->getSignedShort(), $stream->getShort());
                    $offset = $stream->getOffset();
                    $blockTreeRoot = (new BigEndianNbtSerializer())->read($stream->getBuffer(), $offset);
                    $stream->setOffset($offset);
                    try {
                        $blockStateData = BlockStateData::fromNbt($blockTreeRoot->mustGetCompoundTag());
                        $this->blockStateIDs[$coordinateHash] = $blockStateDeserializer->deserialize($blockStateData);
                        continue;
                    } catch(NbtDataException|BlockStateDeserializeException) {
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
            case 4:
                foreach (self::readTreeRoots($nbt, "TileEntities") as $tileTreeRoot) {
                    try {
                        $tileNBT = $tileTreeRoot->mustGetCompoundTag();
                        $coordinateHash = World::blockHash($tileNBT->getInt(Tile::TAG_X), $tileNBT->getInt(Tile::TAG_Y), $tileNBT->getInt(Tile::TAG_Z));
                    } catch (NbtException|InvalidArgumentException) {
                        continue;
                    }
                    $this->tiles[$coordinateHash] = $tileNBT;
                }
                break;
            case 5:
                foreach (self::readTreeRoots($nbt, "TileEntities", false) as $tileTreeRoot) {
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
        // To ensure that all schematics use the best available format, we always upgrade them to the latest version.
        if ($this->version !== self::SCHEMATIC_VERSION) {
            $this->save();
        }
    }

    /**
     * @phpstan-return array<int, TreeRoot>
     */
    private static function readTreeRoots(CompoundTag $nbt, string $key, bool $isCompressed = true) : array {
        try {
            $compressed = $nbt->getByteArray($key);
        } catch (UnexpectedTagTypeException|NoSuchTagException) {
            return [];
        }
        if ($isCompressed) {
            try {
                $decompressed = ZlibCompressor::getInstance()->decompress($compressed);
            } catch(DecompressionException) {
                return [];
            }
        } else {
            $decompressed = $compressed;
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
        if ($type !== SchematicTypes::TYPE_ROAD && $type !== SchematicTypes::TYPE_PLOT) {
            return false;
        }
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
                $blockStateID = $explorer->currentSubChunk->getBlockStateId($xInChunk, $y & SubChunk::COORD_MASK, $zInChunk);
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
                /** @phpstan-var BiomeIds::* $biomeID */
                $biomeID = $explorer->currentChunk->getBiomeId($xInChunk, $y, $zInChunk);
                $this->biomeIDs[$coordinateHash] = $biomeID;
            }
        }
    }

    /**
     * Returns the name of this schematic. The schematic name matches the name of the schematic file and is solely based on it.
     */
    public function getName() : string {
        return pathinfo($this->file, PATHINFO_FILENAME);
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

    public function getBiomeID(int $x, int $y, int $z) : int {
        return $this->biomeIDs[World::blockHash($x, $y, $z)] ?? BiomeIds::PLAINS;
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
                SchematicTypes::TYPE_PLOT => $this->plotSize ** 2
        } * (World::Y_MAX - World::Y_MIN);
    }

    public function calculateBlockCount() : int {
        return $this->calculateBiomeCount();
    }

    public function calculateTileCount() : int {
        return count($this->tiles);
    }
}