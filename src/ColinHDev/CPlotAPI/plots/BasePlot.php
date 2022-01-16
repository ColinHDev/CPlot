<?php

namespace ColinHDev\CPlotAPI\plots;

use ColinHDev\CPlot\provider\cache\Cacheable;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class BasePlot implements Cacheable {

    protected string $worldName;
    protected int $x;
    protected int $z;

    public function __construct(string $worldName, int $x, int $z) {
        $this->worldName = $worldName;
        $this->x = $x;
        $this->z = $z;
    }

    public function getWorldName() : string {
        return $this->worldName;
    }

    public function getX() : int {
        return $this->x;
    }

    public function getZ() : int {
        return $this->z;
    }

    /**
     * @throws \RuntimeException when called outside of main thread.
     */
    public function getWorld() : ?World {
        $worldManager = Server::getInstance()->getWorldManager();
        if (!$worldManager->loadWorld($this->worldName)) {
            return null;
        }
        return $worldManager->getWorldByName($this->worldName);
    }

    /**
     * @throws \RuntimeException when called outside of main thread.
     */
    public function teleportTo(Player $player, bool $toPlotCenter = false) : \Generator {
        $worldSettings = yield DataProvider::getInstance()->getWorld($this->worldName);
        if ($worldSettings === null) {
            return false;
        }

        $flag = FlagManager::getInstance()->getFlagByID(FlagIDs::FLAG_SPAWN);
        $relativeSpawn = $flag?->getValue();
        if ($relativeSpawn instanceof Location) {
            $world = $this->getWorld();
            if ($world === null) {
                return false;
            }
            return $player->teleport(
                Location::fromObject(
                    $relativeSpawn->addVector(
                        $this->getPositionNonNull(
                            $worldSettings->getRoadSize(),
                            $worldSettings->getPlotSize(),
                            $worldSettings->getGroundSize()
                        )
                    ),
                    $world,
                    $relativeSpawn->getYaw(),
                    $relativeSpawn->getPitch()
                )
            );
        }
        return false;
    }

    public function getSide(int $side, int $step = 1) : ?self {
        return match ($side) {
            Facing::NORTH => new self($this->worldName, $this->x, $this->z - $step),
            Facing::SOUTH => new self($this->worldName, $this->x, $this->z + $step),
            Facing::WEST => new self($this->worldName, $this->x - $step, $this->z),
            Facing::EAST => new self($this->worldName, $this->x + $step, $this->z),
            default => null,
        };
    }

    public function isSame(self $plot) : \Generator {
        0 && yield;
        return $this->worldName === $plot->getWorldName() && $this->x === $plot->getX() && $this->z === $plot->getZ();
    }

    public function isOnPlot(Position $position) : \Generator {
        if ($position->getWorld()->getFolderName() !== $this->worldName) return false;

        $worldSettings = yield DataProvider::getInstance()->getWorld($this->worldName);
        if ($worldSettings === null) return false;

        $totalSize = $worldSettings->getRoadSize() + $worldSettings->getPlotSize();
        if ($position->getX() < $this->x * $totalSize + $worldSettings->getRoadSize()) return false;
        if ($position->getZ() < $this->z * $totalSize + $worldSettings->getRoadSize()) return false;
        if ($position->getX() > $this->x * $totalSize + ($totalSize - 1)) return false;
        if ($position->getZ() > $this->z * $totalSize + ($totalSize - 1)) return false;

        return true;
    }

    public function toString() : string {
        return $this->worldName . ";" . $this->x . ";" . $this->z;
    }

    public function toSmallString() : string {
        return $this->x . ";" . $this->z;
    }

    public function toPlot() : \Generator {
        return yield DataProvider::getInstance()->getMergeOrigin($this);
    }

    public function getPosition() : \Generator {
        $worldSettings = yield DataProvider::getInstance()->getWorld($this->worldName);
        if ($worldSettings === null) return null;
        return $this->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
    }

    public function getPositionNonNull(int $sizeRoad, int $sizePlot, int $sizeGround) : Vector3 {
        return new Vector3(
            $sizeRoad + ($sizeRoad + $sizePlot) * $this->x,
            $sizeGround,
            $sizeRoad + ($sizeRoad + $sizePlot) * $this->z
        );
    }

    public static function fromPosition(Position $position) : \Generator {
        $worldSettings = yield DataProvider::getInstance()->getWorld($position->getWorld()->getFolderName());
        if ($worldSettings === null) return null;

        $totalSize = $worldSettings->getPlotSize() + $worldSettings->getRoadSize();

        $x = $position->getFloorX() - $worldSettings->getRoadSize();
        if ($x >= 0) {
            $X = (int) floor($x / $totalSize);
            $difX = $x % $totalSize;
        } else {
            $X = (int) ceil(($x - $worldSettings->getPlotSize() + 1) / $totalSize);
            $difX = abs(($x - $worldSettings->getPlotSize() + 1) % $totalSize);
        }

        $z = $position->getFloorZ() - $worldSettings->getRoadSize();
        if ($z >= 0) {
            $Z = (int) floor($z / $totalSize);
            $difZ = $z % $totalSize;
        } else {
            $Z = (int) ceil(($z - $worldSettings->getPlotSize() + 1) / $totalSize);
            $difZ = abs(($z - $worldSettings->getPlotSize() + 1) % $totalSize);
        }

        if (($difX > $worldSettings->getPlotSize() - 1) || ($difZ > $worldSettings->getPlotSize() - 1)) return null;
        return new self($position->getWorld()->getFolderName(), $X, $Z);
    }

    public function __serialize() : array {
        return [
            "worldName" => $this->worldName,
            "x" => $this->x,
            "z" => $this->z
        ];
    }

    public function __unserialize(array $data) : void {
        $this->worldName = $data["worldName"];
        $this->x = $data["x"];
        $this->z = $data["z"];
    }
}