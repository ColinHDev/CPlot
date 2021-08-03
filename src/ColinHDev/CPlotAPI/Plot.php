<?php

namespace ColinHDev\CPlotAPI;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\FlagManager;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\Position;

class Plot extends BasePlot {

    private int $biomeID;
    private ?string $ownerUUID;
    private ?int $claimTime;
    private ?string $alias;

    /** @var null | BasePlot[] */
    private ?array $mergedPlotIDs = null;

    /** @var null | PlotPlayer[] */
    private ?array $plotPlayers = null;

    /** @var null | BaseFlag[] */
    private ?array $flags = null;

    /**
     * Plot constructor.
     * @param string            $worldName
     * @param int               $x
     * @param int               $z
     * @param int               $biomeID
     * @param null | string     $ownerUUID
     * @param null | int        $claimTime
     * @param null | string     $alias
     */
    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $ownerUUID = null, ?int $claimTime = null, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
        $this->ownerUUID = $ownerUUID;
        $this->claimTime = $claimTime;
        $this->alias = $alias;
    }

    /**
     * @return string | null
     */
    public function getOwnerUUID() : ?string {
        return $this->ownerUUID;
    }

    /**
     * @return int | null
     */
    public function getClaimTime() : ?int {
        return $this->claimTime;
    }

    /**
     * @return string | null
     */
    public function getAlias() : ?string {
        return $this->alias;
    }

    /**
     * @return BaseFlag[] | null
     */
    public function getFlags() : ?array {
        return $this->flags;
    }

    /**
     * @param string $flagID
     * @return BaseFlag | null
     */
    public function getFlagByID(string $flagID) : ?BaseFlag {
        if ($this->flags === null) return null;
        if (isset($this->flags[$flagID])) return $this->flags[$flagID];
        return FlagManager::getInstance()->getFlagByID($flagID);
    }

    /**
     * @param BaseFlag[] | null $flags
     */
    public function setFlags(?array $flags) : void {
        $this->flags = $flags;
    }

    /**
     * @param BaseFlag $flag
     * @return bool
     */
    public function addFlag(BaseFlag $flag) : bool {
        if ($this->flags === null) return false;
        $this->flags[$flag->getID()] = $flag;
        return true;
    }

    /**
     * @param string $flagID
     * @return bool
     */
    public function removeFlag(string $flagID) : bool {
        if ($this->flags === null) return false;
        unset($this->flags[$flagID]);
        return true;
    }
}