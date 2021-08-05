<?php

namespace ColinHDev\CPlotAPI\worlds\schematics;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;

class Schematic {

    public const FILE_EXTENSION = "cplot_schematic";

    public const SCHEMATIC_VERSION = 1;

    public const TYPE_ROAD = 0;
    public const TYPE_PLOT = 1;

    private string $name;
    private string $file;
    private int $version;
    private int $creationTime;
    private int $type;

    private int $sizeRoad;
    private int $sizePlot;

    /** @var int[] */
    private array $blocks;

    /**
     * Schematic constructor.
     * @param string    $name
     * @param string    $file
     */
    public function __construct(string $name, string $file) {
        $this->name = $name;
        $this->file = $file;
    }

    /**
     * @return bool
     */
    public function save() : bool {
        $nbt = new CompoundTag();
        $nbt->setShort("Version", $this->version);
        $nbt->setLong("CreationTime", $this->creationTime);
        $nbt->setByte("Type", $this->type);
        $nbt->setShort("SizeRoad", $this->sizeRoad);
        $nbt->setShort("SizePlot", $this->sizePlot);
        $nbt->setByteArray("Blocks", json_encode($this->blocks));
        $nbtSerializer = new BigEndianNbtSerializer();
        file_put_contents($this->file, zlib_encode($nbtSerializer->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP));
        return true;
    }

    /**
     * @return bool
     */
    public function loadFromFile() : bool {
        if (!file_exists($this->file)) return false;

        $contents = file_get_contents($this->file);
        if ($contents === false) return false;

        $decompressed = @zlib_decode($contents);
        if ($decompressed === false) return false;

        $nbt = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        $this->version = $nbt->getShort("Version");
        $this->creationTime = $nbt->getLong("CreationTime");
        $this->type = $nbt->getByte("Type");
        $this->sizeRoad = $nbt->getShort("SizeRoad");
        $this->sizePlot = $nbt->getShort("SizePlot");
        $this->blocks = json_decode($nbt->getByteArray("Blocks"), true);
        return true;
    }

    /**
     * @param ChunkManager  $world
     * @param int           $type
     * @param int           $sizeRoad
     * @param int           $sizePlot
     * @return bool
     */
    public function loadFromWorld(ChunkManager $world, int $type, int $sizeRoad, int $sizePlot) : bool {
        $this->version = self::SCHEMATIC_VERSION;
        $this->creationTime = time();
        $this->type = $type;
        $this->sizeRoad = $sizeRoad;
        $this->sizePlot = $sizePlot;

        $explorer = new SubChunkExplorer($world);

        switch ($this->type) {

            case self::TYPE_ROAD:
                $totalSize = $this->sizeRoad + $this->sizePlot;
                for ($x = 0; $x < $totalSize; $x++) {
                    $xInChunk = $x & 0x0f;
                    for ($z = 0; $z < $totalSize; $z++) {
                        $zInChunk = $z & 0x0f;
                        if ($x >= $this->sizeRoad && $z >= $this->sizeRoad) continue 2;
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
                                    $fullBlock = $explorer->currentSubChunk->getFullBlock($xInChunk, $y & 0x0f, $zInChunk);
                                    $block = BlockFactory::getInstance()->fromFullBlock($fullBlock);
                                    if ($block->getId() === BlockLegacyIds::AIR) continue 2;
                                    $hash = World::blockHash($x, $y, $z);
                                    $this->blocks[$hash] = $fullBlock;
                            }
                        }
                    }
                }
                break;

            case self::TYPE_PLOT:
                for ($x = 0; $x < $this->sizePlot; $x++) {
                    $xInChunk = $x & 0x0f;
                    for ($z = 0; $z < $this->sizePlot; $z++) {
                        $zInChunk = $z & 0x0f;
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
                                    $fullBlock = $explorer->currentSubChunk->getFullBlock($xInChunk, $y & 0x0f, $zInChunk);
                                    if ($fullBlock === BlockLegacyIds::AIR) continue 2;
                                    $hash = World::blockHash($x, $y, $z);
                                    $this->blocks[$hash] = $fullBlock;
                            }
                        }
                    }
                }
                break;

            default: return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFile() : string {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getVersion() : int {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getCreationTime() : int {
        return $this->creationTime;
    }

    /**
     * @return int
     */
    public function getType() : int {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getRoadSize() : int {
        return $this->sizeRoad;
    }

    /**
     * @return int
     */
    public function getPlotSize() : int {
        return $this->sizePlot;
    }

    /**
     * @return int[]
     */
    public function getFullBlocks() : array {
        return $this->blocks;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     * @return int
     */
    public function getFullBlock(int $x, int $y, int $z) : int {
        $hash = World::blockHash($x, $y, $z);
        if (!isset($this->blocks[$hash])) return BlockLegacyIds::AIR;
        return $this->blocks[$hash];
    }
}