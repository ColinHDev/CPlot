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

    private int $roadSize;
    private int $plotSize;

    /** @var int[] */
    private array $blocks;

    public function __construct(string $name, string $file) {
        $this->name = $name;
        $this->file = $file;
    }

    public function save() : bool {
        $nbt = new CompoundTag();
        $nbt->setShort("version", $this->version);
        $nbt->setLong("creationTime", $this->creationTime);
        $nbt->setByte("type", $this->type);
        $nbt->setShort("roadSize", $this->roadSize);
        $nbt->setShort("plotSize", $this->plotSize);
        $nbt->setByteArray("blocks", json_encode($this->blocks));
        $nbtSerializer = new BigEndianNbtSerializer();
        file_put_contents($this->file, zlib_encode($nbtSerializer->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP));
        return true;
    }

    public function loadFromFile() : bool {
        if (!file_exists($this->file)) return false;

        $contents = file_get_contents($this->file);
        if ($contents === false) return false;

        $decompressed = @zlib_decode($contents);
        if ($decompressed === false) return false;

        $nbt = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        $this->version = $nbt->getShort("version");
        $this->creationTime = $nbt->getLong("creationTime");
        $this->type = $nbt->getByte("type");
        $this->roadSize = $nbt->getShort("roadSize");
        $this->plotSize = $nbt->getShort("plotSize");
        $this->blocks = json_decode($nbt->getByteArray("blocks"), true);
        return true;
    }

    public function loadFromWorld(ChunkManager $world, int $type, int $roadSize, int $plotSize) : bool {
        $this->version = self::SCHEMATIC_VERSION;
        $this->creationTime = (int) (round(microtime(true) * 1000));
        $this->type = $type;
        $this->roadSize = $roadSize;
        $this->plotSize = $plotSize;

        $explorer = new SubChunkExplorer($world);

        switch ($this->type) {

            case self::TYPE_ROAD:
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
                for ($x = 0; $x < $this->plotSize; $x++) {
                    $xInChunk = $x & 0x0f;
                    for ($z = 0; $z < $this->plotSize; $z++) {
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

    public function getType() : int {
        return $this->type;
    }

    public function getRoadSize() : int {
        return $this->roadSize;
    }

    public function getPlotSize() : int {
        return $this->plotSize;
    }

    /**
     * @return int[]
     */
    public function getFullBlocks() : array {
        return $this->blocks;
    }

    public function getFullBlock(int $x, int $y, int $z) : int {
        $hash = World::blockHash($x, $y, $z);
        if (!isset($this->blocks[$hash])) return BlockLegacyIds::AIR;
        return $this->blocks[$hash];
    }
}