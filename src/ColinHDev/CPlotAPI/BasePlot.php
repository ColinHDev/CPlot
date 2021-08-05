<?php

namespace ColinHDev\CPlotAPI;

use ColinHDev\CPlot\CPlot;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class BasePlot {

    protected string $worldName;
    protected int $x;
    protected int $z;

    /**
     * BasePlot constructor.
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     */
    public function __construct(string $worldName, int $x, int $z) {
        $this->worldName = $worldName;
        $this->x = $x;
        $this->z = $z;
    }

    /**
     * @return string
     */
    public function getWorldName() : string {
        return $this->worldName;
    }

    /**
     * @return int
     */
    public function getX() : int {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getZ() : int {
        return $this->z;
    }

    /**
     * @param int           $side
     * @param int           $step
     * @return self | null
     */
    public function getSide(int $side, int $step = 1) : ?self {
        return match ($side) {
            Facing::NORTH => new self($this->worldName, $this->x, $this->z - $step),
            Facing::SOUTH => new self($this->worldName, $this->x, $this->z + $step),
            Facing::WEST => new self($this->worldName, $this->x - $step, $this->z),
            Facing::EAST => new self($this->worldName, $this->x + $step, $this->z),
            default => null,
        };
    }

    /**
     * @param BasePlot $plot
     * @return bool
     */
    public function isSame(self $plot) : bool {
        return $this->worldName === $plot->getWorldName() && $this->x === $plot->getX() && $this->z === $plot->getZ();
    }

    /**
     * @return string
     */
    public function toString() : string {
        return $this->worldName . ";" . $this->x . ";" . $this->z;
    }

    /**
     * @return string
     */
    public function toSmallString() : string {
        return $this->x . ";" . $this->z;
    }

    /**
     * @return Plot | null
     */
    public function toPlot() : ?Plot {
        return CPlot::getInstance()->getProvider()->getMergeOrigin($this);
    }

    /**
     * @return Vector3 | null
     */
    public function getPosition() : ?Vector3 {
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($this->worldName);
        if ($worldSettings === null) return null;
        return $this->getPositionNonNull($worldSettings->getSizeRoad(), $worldSettings->getSizePlot(), $worldSettings->getSizeGround());
    }

    /**
     * @param int       $sizeRoad
     * @param int       $sizePlot
     * @param int       $sizeGround
     * @return Vector3
     */
    public function getPositionNonNull(int $sizeRoad, int $sizePlot, int $sizeGround) : Vector3 {
        return new Vector3(
            $sizeRoad + ($sizeRoad + $sizePlot) * $this->x,
            $sizeGround,
            $sizeRoad + ($sizeRoad + $sizePlot) * $this->z
        );
    }

    /**
     * @param Position          $position
     * @return self | null
     */
    public static function fromPosition(Position $position) : ?self {
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName());
        if ($worldSettings === null) return null;

        $totalSize = $worldSettings->getSizePlot() + $worldSettings->getSizeRoad();

        $x = $position->getFloorX() - $worldSettings->getSizeRoad();
        if ($x >= 0) {
            $X = (int) floor($x / $totalSize);
            $difX = $x % $totalSize;
        } else {
            $X = (int) ceil(($x - $worldSettings->getSizePlot() + 1) / $totalSize);
            $difX = abs(($x - $worldSettings->getSizePlot() + 1) % $totalSize);
        }

        $z = $position->getFloorZ() - $worldSettings->getSizeRoad();
        if ($z >= 0) {
            $Z = (int) floor($z / $totalSize);
            $difZ = $z % $totalSize;
        } else {
            $Z = (int) ceil(($z - $worldSettings->getSizePlot() + 1) / $totalSize);
            $difZ = abs(($z - $worldSettings->getSizePlot() + 1) % $totalSize);
        }

        if (($difX > $worldSettings->getSizePlot() - 1) || ($difZ > $worldSettings->getSizePlot() - 1)) return null;
        return new self($position->getWorld()->getFolderName(), $X, $Z);
    }
}