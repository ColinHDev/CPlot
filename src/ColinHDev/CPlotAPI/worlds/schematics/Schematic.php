<?php

namespace ColinHDev\CPlotAPI\worlds\schematics;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\world\ChunkManager;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;

class Schematic implements SchematicTypes {

    public const FILE_EXTENSION = "cplot_schematic";

    public const SCHEMATIC_VERSION = 1;

    private string $name;
    private string $file;
    private int $version;
    private int $creationTime;
    private string $type;

    private int $roadSize;
    private int $plotSize;

    /** @var array<int, string> */
    private array $blockStringIDs = [];
    /** @var array<int, int> */
    private array $blockIDs = [];
    /** @var array<int, int> */
    private array $blockMetas = [];

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

        $blockTreeRoots = [];
        foreach ($this->blockStringIDs as $coordinateHash => $blockStringID) {
            $blockNBT = new CompoundTag();

            $blockNBT->setString("StringID", $blockStringID);
            $blockNBT->setInt("ID", $this->blockIDs[$coordinateHash]);
            $blockNBT->setShort("Meta", $this->blockMetas[$coordinateHash]);

            World::getBlockXYZ($coordinateHash, $x, $y, $z);
            $blockNBT->setShort("X", $x);
            $blockNBT->setShort("Y", $y);
            $blockNBT->setShort("Z", $z);

            $blockTreeRoots[] = new TreeRoot($blockNBT, $coordinateHash);
        }
        $nbt->setByteArray("Blocks", zlib_encode((new BigEndianNbtSerializer())->writeMultiple($blockTreeRoots), ZLIB_ENCODING_GZIP));

        file_put_contents($this->file, zlib_encode((new BigEndianNbtSerializer())->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP));
        return true;
    }

    public function loadFromFile() : bool {
        if (!file_exists($this->file)) return false;

        $contents = file_get_contents($this->file);
        if ($contents === false) return false;

        $decompressed = zlib_decode($contents);
        if ($decompressed === false) return false;

        $nbt = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();

        $this->version = $nbt->getShort("Version");
        $this->creationTime = $nbt->getLong("CreationTime");
        $this->type = $nbt->getString("Type");
        $this->roadSize = $nbt->getShort("RoadSize");
        $this->plotSize = $nbt->getShort("PlotSize");

        $decompressed = zlib_decode($nbt->getByteArray("Blocks"));
        if ($decompressed === false) return false;

        $blockTreeRoots = (new BigEndianNbtSerializer())->readMultiple($decompressed);
        foreach ($blockTreeRoots as $blockTreeRoot) {
            $blockNBT = $blockTreeRoot->mustGetCompoundTag();
            $coordinateHash = World::blockHash($blockNBT->getShort("X"), $blockNBT->getShort("Y"), $blockNBT->getShort("Z"));
            $this->blockStringIDs[$coordinateHash] = $blockNBT->getString("StringID");
            $this->blockIDs[$coordinateHash] = $blockNBT->getInt("ID");
            $this->blockMetas[$coordinateHash] = $blockNBT->getShort("Meta");
        }

        return true;
    }

    public function loadFromWorld(ChunkManager $world, string $type, int $roadSize, int $plotSize) : bool {
        $this->version = self::SCHEMATIC_VERSION;
        $this->creationTime = (int) (round(microtime(true) * 1000));
        $this->type = $type;
        $this->roadSize = $roadSize;
        $this->plotSize = $plotSize;

        $idMap = LegacyBlockIdToStringIdMap::getInstance();
        $explorer = new SubChunkExplorer($world);

        switch ($this->type) {

            case SchematicTypes::TYPE_ROAD:
                $totalSize = $this->roadSize + $this->plotSize;
                for ($x = 0; $x < $totalSize; $x++) {
                    $xInChunk = $x & 0x0f;
                    for ($z = 0; $z < $totalSize; $z++) {
                        $zInChunk = $z & 0x0f;
                        if ($x >= $this->roadSize && $z >= $this->roadSize) continue 2;
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
                                    $blockFullID = $explorer->currentSubChunk->getFullBlock($xInChunk, $y & 0x0f, $zInChunk);
                                    $blockID = $blockFullID >> Block::INTERNAL_METADATA_BITS;
                                    if ($blockID === BlockLegacyIds::AIR) {
                                        break;
                                    }
                                    $blockMeta = $blockFullID & Block::INTERNAL_METADATA_MASK;

                                    $coordinateHash = World::blockHash($x, $y, $z);

                                    $this->blockStringIDs[$coordinateHash] = $idMap->legacyToString($blockID) ?? "minecraft:info_update";
                                    $this->blockIDs[$coordinateHash] = $blockID;
                                    $this->blockMetas[$coordinateHash] = $blockMeta;
                                    break;
                            }
                        }
                    }
                }
                break;

            case SchematicTypes::TYPE_PLOT:
                for ($x = 0; $x < $this->plotSize; $x++) {
                    $xInChunk = $x & 0x0f;
                    for ($z = 0; $z < $this->plotSize; $z++) {
                        $zInChunk = $z & 0x0f;
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
                                    $blockFullID = $explorer->currentSubChunk->getFullBlock($xInChunk, $y & 0x0f, $zInChunk);
                                    $blockID = $blockFullID >> Block::INTERNAL_METADATA_BITS;
                                    if ($blockID === BlockLegacyIds::AIR) {
                                        break;
                                    }
                                    $blockMeta = $blockFullID & Block::INTERNAL_METADATA_MASK;

                                    $coordinateHash = World::blockHash($x, $y, $z);

                                    $this->blockStringIDs[$coordinateHash] = $idMap->legacyToString($blockID) ?? "minecraft:info_update";
                                    $this->blockIDs[$coordinateHash] = $blockID;
                                    $this->blockMetas[$coordinateHash] = $blockMeta;
                                    break;
                            }
                        }
                    }
                }
                break;

            default: return false;
        }
        return true;
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

    public function getFullBlock(int $x, int $y, int $z) : int {
        $coordinateHash = World::blockHash($x, $y, $z);
        if (!isset($this->blockStringIDs[$coordinateHash])) {
            return BlockLegacyIds::AIR << Block::INTERNAL_METADATA_BITS;
        }
        return ($this->blockIDs[$coordinateHash] << Block::INTERNAL_METADATA_BITS) | $this->blockMetas[$coordinateHash];
    }

    public function getBlockCount() : int {
        return count($this->blockStringIDs);
    }
}