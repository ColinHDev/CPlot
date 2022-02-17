<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots;

use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\NonWorldSettings;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class BasePlot {

    protected string $worldName;
    protected WorldSettings $worldSettings;
    protected int $x;
    protected int $z;

    public function __construct(string $worldName, WorldSettings $worldSettings, int $x, int $z) {
        $this->worldName = $worldName;
        $this->worldSettings = $worldSettings;
        $this->x = $x;
        $this->z = $z;
    }

    public function getWorldName() : string {
        return $this->worldName;
    }

    public function getWorldSettings() : WorldSettings {
        return $this->worldSettings;
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
    public function teleportTo(Player $player, bool $toPlotCenter = false) : bool {
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
                        $this->getVector3NonNull(
                            $this->worldSettings->getRoadSize(),
                            $this->worldSettings->getPlotSize(),
                            $this->worldSettings->getGroundSize()
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
            Facing::NORTH => new self($this->worldName, $this->worldSettings, $this->x, $this->z - $step),
            Facing::SOUTH => new self($this->worldName, $this->worldSettings, $this->x, $this->z + $step),
            Facing::WEST => new self($this->worldName, $this->worldSettings, $this->x - $step, $this->z),
            Facing::EAST => new self($this->worldName, $this->worldSettings, $this->x + $step, $this->z),
            default => null
        };
    }

    public function isSame(self $plot) : bool {
        return $this->worldName === $plot->getWorldName() && $this->x === $plot->getX() && $this->z === $plot->getZ();
    }

    public function isOnPlot(Position $position) : bool {
        if ($position->world?->getFolderName() !== $this->worldName) {
            return false;
        }
        $totalSize = $this->worldSettings->getRoadSize() + $this->worldSettings->getPlotSize();
        if ($position->getX() < $this->x * $totalSize + $this->worldSettings->getRoadSize()) {
            return false;
        }
        if ($position->getZ() < $this->z * $totalSize + $this->worldSettings->getRoadSize()) {
            return false;
        }
        if ($position->getX() > $this->x * $totalSize + ($totalSize - 1)) {
            return false;
        }
        if ($position->getZ() > $this->z * $totalSize + ($totalSize - 1)) {
            return false;
        }
        return true;
    }

    public function toString() : string {
        return $this->worldName . ";" . $this->x . ";" . $this->z;
    }

    public function toSmallString() : string {
        return $this->x . ";" . $this->z;
    }

    public function toSyncPlot() : ?Plot {
        return DataProvider::getInstance()->loadMergeOriginIntoCache($this);
    }

    /**
     * @phpstan-return \Generator<int, mixed, Plot|null, Plot|null>
     */
    public function toAsyncPlot() : \Generator {
        return yield from DataProvider::getInstance()->awaitMergeOrigin($this);
    }

    public function getVector3() : Vector3 {
        return $this->getVector3NonNull(
            $this->worldSettings->getRoadSize(),
            $this->worldSettings->getPlotSize(),
            $this->worldSettings->getGroundSize()
        );
    }

    public function getVector3NonNull(int $sizeRoad, int $sizePlot, int $sizeGround) : Vector3 {
        return new Vector3(
            $sizeRoad + ($sizeRoad + $sizePlot) * $this->x,
            $sizeGround,
            $sizeRoad + ($sizeRoad + $sizePlot) * $this->z
        );
    }

    public static function loadFromPositionIntoCache(Position $position) : ?self {
        $worldName = $position->getWorld()->getFolderName();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($worldName);
        if (!$worldSettings instanceof WorldSettings) {
            return null;
        }
        return self::fromVector3($worldName, $worldSettings, $position->asVector3());
    }

    /**
     * @phpstan-return \Generator<int, mixed, WorldSettings|NonWorldSettings|Plot|null, BasePlot|null>
     */
    public static function awaitFromPosition(Position $position) : \Generator {
        $worldName = $position->getWorld()->getFolderName();
        $worldSettings = yield DataProvider::getInstance()->awaitWorld($worldName);
        if (!$worldSettings instanceof WorldSettings) {
            return null;
        }
        return self::fromVector3($worldName, $worldSettings, $position->asVector3());
    }

    public static function fromVector3(string $worldName, WorldSettings $worldSettings, Vector3 $vector3) : ?self {
        $totalSize = $worldSettings->getPlotSize() + $worldSettings->getRoadSize();

        $x = $vector3->getFloorX() - $worldSettings->getRoadSize();
        if ($x >= 0) {
            $X = (int) floor($x / $totalSize);
            $difX = $x % $totalSize;
        } else {
            $X = (int) ceil(($x - $worldSettings->getPlotSize() + 1) / $totalSize);
            $difX = abs(($x - $worldSettings->getPlotSize() + 1) % $totalSize);
        }

        $z = $vector3->getFloorZ() - $worldSettings->getRoadSize();
        if ($z >= 0) {
            $Z = (int) floor($z / $totalSize);
            $difZ = $z % $totalSize;
        } else {
            $Z = (int) ceil(($z - $worldSettings->getPlotSize() + 1) / $totalSize);
            $difZ = abs(($z - $worldSettings->getPlotSize() + 1) % $totalSize);
        }

        if (($difX > $worldSettings->getPlotSize() - 1) || ($difZ > $worldSettings->getPlotSize() - 1)) {
            return null;
        }
        return new self($worldName, $worldSettings, $X, $Z);
    }

    /**
     * @phpstan-return array{worldName: string, worldSettings: string, x: int, z: int}
     */
    public function __serialize() : array {
        return [
            "worldName" => $this->worldName,
            "worldSettings" => serialize($this->worldSettings),
            "x" => $this->x,
            "z" => $this->z
        ];
    }

    /**
     * @phpstan-param array{worldName: string, worldSettings: string, x: int, z: int, originX?: int, originZ?: int, alias?: string|null, mergePlots?: string, plotPlayers?: string, flags?: string, plotRates?: string} $data
     */
    public function __unserialize(array $data) : void {
        $this->worldName = $data["worldName"];
        $worldSettings = unserialize($data["worldSettings"], ["allowed_classes" => [WorldSettings::class]]);
        assert($worldSettings instanceof WorldSettings);
        $this->worldSettings = $worldSettings;
        $this->x = $data["x"];
        $this->z = $data["z"];
    }
}